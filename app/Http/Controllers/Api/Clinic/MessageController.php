<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Message;
use App\Models\MessageChannel;
use App\Models\MessageReport;
use App\Models\Staff;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageController extends Controller
{
    public function __construct(private AuditService $audit) {}

    // -----------------------------------------------------------------------
    // GET /api/clinic/messages/channels
    // -----------------------------------------------------------------------
    public function channels(Request $request): JsonResponse
    {
        $campusId = $this->parseCampusId($request);

        $channels = MessageChannel::active()
            ->where('type', '!=', 'messenger')
            ->when($campusId, fn ($q) => $q->where(function ($inner) use ($campusId) {
                $inner->where('campus_id', $campusId)->orWhereNull('campus_id');
            }))
            ->with('campus:id,name,code')
            ->addSelect([
                'last_message_body' => Message::select('body')
                    ->whereColumn('channel_id', 'message_channels.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'last_message_attachment_path' => Message::select('attachment_path')
                    ->whereColumn('channel_id', 'message_channels.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'last_message_at' => Message::select('created_at')
                    ->whereColumn('channel_id', 'message_channels.id')
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(function (MessageChannel $ch) {
                return [
                    'id' => $ch->id,
                    'name' => $ch->name,
                    'type' => $ch->type,
                    'campus_id' => $ch->campus_id,
                    'campus_name' => $ch->campus?->name ?? 'All Campuses',
                    'last_message' => $ch->last_message_body
                        ?: ($ch->last_message_attachment_path ? 'Attachment' : null),
                    'last_at' => $ch->last_message_at,
                    'unread_count' => 0, // extended later with per-user read tracking
                ];
            });

        return response()->json(['success' => true, 'channels' => $channels]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/messages/{channelId}
    // -----------------------------------------------------------------------
    public function messages(Request $request, int $channelId): JsonResponse
    {
        $channel = MessageChannel::findOrFail($channelId);
        $staffId = (int) $request->attributes->get('clinic_identity')['id'];
        $this->authorizeInternalChannel($channel);

        if (
            $channel->type === 'direct'
            && ! in_array($staffId, [
                (int) $channel->participant_a,
                (int) $channel->participant_b,
            ], true)
        ) {
            return response()->json(['error' => 'You are not authorized to access this conversation'], 403);
        }
        $data = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'before' => ['sometimes', 'integer', 'min:1'],
            'after' => ['sometimes', 'integer', 'min:0'],
            'changed_after' => ['sometimes', 'date'],
        ]);
        $limit = (int) ($data['limit'] ?? 50);
        $channelState = DB::table('message_channel_user_states')
            ->where('channel_id', $channelId)
            ->where('staff_id', $staffId)
            ->first();
        $query = Message::query()->where('channel_id', $channelId);
        if ($channelState?->hidden_before_message_id !== null) {
            $query->where('id', '>', (int) $channelState->hidden_before_message_id);
        }
        if (isset($data['changed_after'])) {
            $changedAfter = Carbon::parse($data['changed_after'])->subSeconds(5);
            $messages = $query
                ->where('updated_at', '>', $changedAfter)
                ->oldest('id')
                ->take($limit)
                ->get();
        } elseif (isset($data['after'])) {
            $messages = $query
                ->where('id', '>', $data['after'])
                ->oldest('id')
                ->take($limit)
                ->get();
        } else {
            $messages = $query
                ->when(isset($data['before']), fn ($builder) => $builder->where('id', '<', $data['before']))
                ->latest('id')
                ->take($limit)
                ->get()
                ->reverse()
                ->values();
        }

        if ($channel->type === 'direct') {
            $staffId = (int) $request->attributes->get('clinic_identity')['id'];
            $unseenMessageIds = $messages
                ->filter(fn (Message $message) => $message->sender_type === 'staff'
                    && (int) $message->sender_id !== $staffId
                    && ! $message->is_system
                    && ! $message->is_unsent
                    && $message->seen_at === null)
                ->pluck('id');

            if ($unseenMessageIds->isNotEmpty()) {
                $seenAt = now();
                Message::query()
                    ->where('channel_id', $channel->id)
                    ->whereKey($unseenMessageIds)
                    ->whereNull('seen_at')
                    ->update([
                        'seen_at' => $seenAt,
                        'updated_at' => $seenAt,
                    ]);

                $updatedMessages = Message::query()
                    ->whereKey($unseenMessageIds)
                    ->get()
                    ->keyBy('id');
                $messages = $messages->map(fn (Message $message) => $updatedMessages->get($message->id, $message));
            }
        }

        $messages = $this->serializeMessages($messages);

        return response()->json([
            'success' => true,
            'channel_name' => $channel->name,
            'messages' => $messages,
            'has_more' => $messages->count() === $limit,
        ]);
    }

    public function attachment(Request $request, int $channelId, int $messageId): Response|StreamedResponse
    {
        $channel = MessageChannel::findOrFail($channelId);
        $staffId = (int) $request->attributes->get('clinic_identity')['id'];
        $this->authorizeInternalChannel($channel);

        if (
            $channel->type === 'direct'
            && ! in_array($staffId, [(int) $channel->participant_a, (int) $channel->participant_b], true)
        ) {
            abort(403, 'You are not authorized to access this conversation');
        }

        $message = Message::query()
            ->where('channel_id', $channel->id)
            ->whereKey($messageId)
            ->where('is_unsent', false)
            ->firstOrFail();
        abort_unless(is_string($message->attachment_path), 404);
        $diskName = $message->attachment_disk ?: 'local';
        $disk = Storage::disk($diskName);

        abort_unless($disk->exists($message->attachment_path), 404);

        $mime = $message->attachment_mime ?: 'application/octet-stream';
        $inlineImageMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $filename = $message->attachment_name
            ? basename(str_replace('\\', '/', $message->attachment_name))
            : basename($message->attachment_path);
        $disposition = $request->boolean('download') || ! in_array($mime, $inlineImageMimes, true)
            ? 'attachment'
            : 'inline';
        $response = $disk->response($message->attachment_path, $filename, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ], $disposition);

        return $response;
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/messages/{channelId}
    // -----------------------------------------------------------------------
    public function send(Request $request, int $channelId): JsonResponse
    {
        $channel = MessageChannel::findOrFail($channelId);
        $senderId = (int) $request->attributes->get('clinic_identity')['id'];
        $this->authorizeInternalChannel($channel);

        $data = $request->validate([
            'body' => ['nullable', 'required_without:attachment,image', 'string', 'max:2000'],
            'attachment' => ['nullable', 'required_without:body', 'file', 'max:51200'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:51200'],
        ]);

        if ($request->hasFile('attachment') && $request->hasFile('image')) {
            throw ValidationException::withMessages([
                'attachment' => 'Attach one file per message.',
            ]);
        }

        if (
            $channel->type === 'direct'
            && ! in_array($senderId, [(int) $channel->participant_a, (int) $channel->participant_b], true)
        ) {
            return response()->json(['error' => 'You are not authorized to access this conversation'], 403);
        }

        $body = trim((string) ($data['body'] ?? ''));
        $attachment = $request->file('attachment') ?? $request->file('image');
        if ($body === '' && ! $attachment) {
            return response()->json(['error' => 'Write a message or choose a file to attach.'], 422);
        }

        $attachmentMime = $attachment?->getMimeType() ?: 'application/octet-stream';
        $inlineImageMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $imageInfo = $attachment && in_array($attachmentMime, $inlineImageMimes, true)
            ? getimagesizefromstring($attachment->get())
            : false;
        if (
            $attachment
            && in_array($attachmentMime, $inlineImageMimes, true)
            && ($imageInfo === false || ! in_array($imageInfo['mime'], $inlineImageMimes, true))
        ) {
            throw ValidationException::withMessages([
                'attachment' => 'Choose a valid JPG, PNG, or WEBP image.',
            ]);
        }

        $attachmentName = $attachment
            ? mb_substr(
                preg_replace('/[\x00-\x1F\x7F]/u', '', basename(str_replace('\\', '/', $attachment->getClientOriginalName()))) ?: 'attachment',
                0,
                255,
            )
            : null;
        $attachmentDisk = config('filesystems.messaging_attachments_disk', 'local');
        $attachmentPath = $attachment ? $attachment->store('messages/attachments', $attachmentDisk) : null;
        if ($attachment && ! $attachmentPath) {
            throw new \RuntimeException('Unable to store the message attachment.');
        }

        $message = DB::transaction(function () use (
            $channel,
            $senderId,
            $body,
            $attachmentName,
            $attachmentPath,
            $attachmentDisk,
            $attachmentMime,
            $imageInfo,
        ): Message {
            $message = Message::create([
                'channel_id' => $channel->id,
                'sender_id' => $senderId,
                'sender_type' => 'staff',
                'body' => $body,
                'attachment_path' => $attachmentPath,
                'attachment_disk' => $attachmentPath ? $attachmentDisk : null,
                'attachment_mime' => $attachmentPath ? ($imageInfo['mime'] ?? $attachmentMime) : null,
                'attachment_name' => $attachmentName,
                'is_system' => false,
            ]);

            return $message;
        });
        $this->showChannelForParticipants($channel);

        return response()->json([
            'success' => true,
            'message' => $this->serializeMessages(collect([$message]))->first(),
        ], 201);
    }

    public function deleteConversation(Request $request, int $channelId): JsonResponse
    {
        $staffId = (int) $request->attributes->get('clinic_identity')['id'];
        $channel = $this->messageChannelForStaff($channelId, $staffId);
        $lastMessageId = Message::query()
            ->where('channel_id', $channel->id)
            ->max('id');
        $now = now();
        $createdAt = DB::table('message_channel_user_states')
            ->where('channel_id', $channel->id)
            ->where('staff_id', $staffId)
            ->value('created_at') ?? $now;

        DB::table('message_channel_user_states')->updateOrInsert(
            ['channel_id' => $channel->id, 'staff_id' => $staffId],
            [
                'hidden_before_message_id' => $lastMessageId,
                'person_hidden' => false,
                'updated_at' => $now,
                'created_at' => $createdAt,
            ],
        );

        return response()->json(['success' => true]);
    }

    public function deletePerson(Request $request, int $channelId): JsonResponse
    {
        $staffId = (int) $request->attributes->get('clinic_identity')['id'];
        $channel = $this->messageChannelForStaff($channelId, $staffId);
        $now = now();
        $createdAt = DB::table('message_channel_user_states')
            ->where('channel_id', $channel->id)
            ->where('staff_id', $staffId)
            ->value('created_at') ?? $now;

        DB::table('message_channel_user_states')->updateOrInsert(
            ['channel_id' => $channel->id, 'staff_id' => $staffId],
            [
                'person_hidden' => true,
                'updated_at' => $now,
                'created_at' => $createdAt,
            ],
        );

        return response()->json(['success' => true]);
    }

    private function messageChannelForStaff(int $channelId, int $staffId): MessageChannel
    {
        $channel = MessageChannel::findOrFail($channelId);

        abort_unless(
            $channel->type === 'direct'
            && in_array($staffId, [(int) $channel->participant_a, (int) $channel->participant_b], true),
            403,
            'You are not authorized to access this conversation',
        );
        return $channel;
    }

    private function authorizeInternalChannel(MessageChannel $channel): void
    {
        abort_unless($channel->type !== 'messenger', 404);
    }

    private function showChannelForParticipants(MessageChannel $channel): void
    {
        if ($channel->type !== 'direct') {
            return;
        }

        $query = DB::table('message_channel_user_states')
            ->where('channel_id', $channel->id)
            ->whereIn('staff_id', [
                (int) $channel->participant_a,
                (int) $channel->participant_b,
            ]);

        $query->where('person_hidden', true)->update([
            'person_hidden' => false,
            'updated_at' => now(),
        ]);
    }

    public function updateMessage(Request $request, int $channelId, int $messageId): JsonResponse
    {
        $channel = $this->directChannelFor($request, $channelId);
        $identity = $request->attributes->get('clinic_identity');
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);
        $message = $this->ownedMessage($channel, $messageId, (int) $identity['id']);

        $message->update([
            'body' => trim($data['body']),
            'edited_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $this->serializeMessages(collect([$message->refresh()]))->first(),
        ]);
    }

    public function unsend(Request $request, int $channelId, int $messageId): JsonResponse
    {
        $channel = $this->directChannelFor($request, $channelId);
        $identity = $request->attributes->get('clinic_identity');
        $message = $this->ownedMessage($channel, $messageId, (int) $identity['id']);

        $message->update([
            'body' => 'This message was unsent.',
            'is_unsent' => true,
            'is_pinned' => false,
            'pinned_by' => null,
        ]);

        return response()->json(['success' => true]);
    }

    public function pin(Request $request, int $channelId, int $messageId): JsonResponse
    {
        $channel = $this->directChannelFor($request, $channelId);
        $identity = $request->attributes->get('clinic_identity');
        $data = $request->validate([
            'pinned' => ['required', 'boolean'],
        ]);
        $message = Message::query()
            ->where('channel_id', $channel->id)
            ->whereKey($messageId)
            ->where('is_unsent', false)
            ->firstOrFail();

        $message->update([
            'is_pinned' => $data['pinned'],
            'pinned_by' => $data['pinned'] ? (int) $identity['id'] : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $this->serializeMessages(collect([$message->refresh()]))->first(),
        ]);
    }

    public function report(Request $request, int $channelId, int $messageId): JsonResponse
    {
        $channel = $this->directChannelFor($request, $channelId);
        $identity = $request->attributes->get('clinic_identity');
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $message = Message::query()
            ->where('channel_id', $channel->id)
            ->whereKey($messageId)
            ->firstOrFail();

        $reported = MessageReport::query()->insertOrIgnore([
            'message_id' => $message->id,
            'reported_by' => (int) $identity['id'],
            'reason' => trim((string) ($data['reason'] ?? '')) ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'reported' => true,
            'already_reported' => $reported === 0,
        ]);
    }

    public function forward(Request $request, int $channelId, int $messageId): JsonResponse
    {
        $sourceChannel = $this->directChannelFor($request, $channelId);
        $identity = $request->attributes->get('clinic_identity');
        $data = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:staff,id'],
        ]);
        $senderId = (int) $identity['id'];
        $recipient = Staff::query()
            ->whereKey($data['recipient_id'])
            ->whereIn('role', ['nurse', 'nurse_head'])
            ->where('is_active', true)
            ->firstOrFail();

        if ((int) $recipient->id === $senderId) {
            return response()->json(['error' => 'You cannot forward a message to yourself'], 422);
        }

        $sourceMessage = Message::query()
            ->where('channel_id', $sourceChannel->id)
            ->whereKey($messageId)
            ->where('is_unsent', false)
            ->firstOrFail();
        $sender = Staff::findOrFail($senderId);
        /** @var array{0: MessageChannel, 1: Message} $forwarded */
        $forwarded = DB::transaction(function () use ($sender, $recipient, $senderId, $sourceMessage): array {
            $channel = MessageChannel::firstOrCreateDirect(
                $senderId,
                (int) $recipient->id,
                trim($sender->first_name.' '.$sender->last_name),
                trim($recipient->first_name.' '.$recipient->last_name),
            );

            $forwardedMessage = Message::create([
                'channel_id' => $channel->id,
                'sender_id' => $senderId,
                'sender_type' => 'staff',
                'body' => $sourceMessage->body,
                'attachment_path' => $sourceMessage->attachment_path,
                'attachment_disk' => $sourceMessage->attachment_disk ?: 'local',
                'attachment_mime' => $sourceMessage->attachment_mime,
                'attachment_name' => $sourceMessage->attachment_name,
                'is_system' => false,
                'forwarded_from_id' => $sourceMessage->id,
            ]);

            return [$channel, $forwardedMessage];
        });
        [$channel, $forwardedMessage] = $forwarded;

        Message::clearSenderCache();

        return response()->json([
            'success' => true,
            'channel' => [
                'id' => $channel->id,
                'recipient_id' => $recipient->id,
                'recipient_name' => trim($recipient->first_name.' '.$recipient->last_name),
            ],
            'message' => $this->serializeMessages(collect([$forwardedMessage]))->first(),
        ], 201);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/messages/channels (create a channel)
    // -----------------------------------------------------------------------
    public function createChannel(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['sometimes', 'string', 'in:campus,team,referrals,direct'],
            'campus_id' => ['sometimes', 'nullable', 'integer', 'exists:campuses,id'],
        ]);

        $channel = MessageChannel::create([
            'name' => $data['name'],
            'type' => $data['type'] ?? 'campus',
            'campus_id' => $data['campus_id'] ?? null,
            'is_active' => true,
        ]);

        return response()->json(['success' => true, 'id' => $channel->id], 201);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/messages/seed-channels
    // Idempotently creates default channels for every active campus.
    // -----------------------------------------------------------------------
    public function seedChannels(): JsonResponse
    {
        $defaults = ['Referrals', 'Nursing Team', 'Doctors', 'General'];

        Campus::active()->each(function (Campus $campus) {
            // One channel per campus
            MessageChannel::firstOrCreate(
                ['name' => $campus->name, 'type' => 'campus'],
                ['campus_id' => $campus->id, 'is_active' => true],
            );
        });

        // Global channels (no campus_id)
        foreach ($defaults as $name) {
            MessageChannel::firstOrCreate(
                ['name' => $name, 'type' => match ($name) {
                    'Referrals' => 'referrals',
                    'Nursing Team' => 'team',
                    'Doctors' => 'team',
                    default => 'campus',
                }],
                ['campus_id' => null, 'is_active' => true],
            );
        }

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function parseCampusId(Request $request): ?int
    {
        $raw = $request->query('staff_campus_id') ?? $request->query('campus_id');

        return is_numeric($raw) ? (int) $raw : null;
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/messages/staff-list
    // Returns all active staff who can participate in DMs:
    // nurse, nurse_head, physician, dentist.
    // -----------------------------------------------------------------------
    public function staffList(): JsonResponse
    {
        $pictureIds = $this->staffPictureIds();
        $staff = Staff::whereIn('role', ['nurse', 'nurse_head'])
            ->where('is_active', true)
            ->orderByRaw("CASE role WHEN 'nurse_head' THEN 1 ELSE 2 END")
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'role', 'last_seen_at'])
            ->map(function (Staff $staff) use ($pictureIds) {
                return [
                    'id' => $staff->id,
                    'name' => trim($staff->first_name.' '.$staff->last_name),
                    'role' => $staff->role,
                    'role_label' => match ($staff->role) {
                        'nurse_head' => 'Head Nurse',
                        default => 'Nurse',
                    },
                    'profile_picture_url' => in_array($staff->id, $pictureIds, true)
                        ? $this->profilePictureUrl($staff->id)
                        : null,
                ] + $this->presenceData($staff->last_seen_at);
            });

        return response()->json(['success' => true, 'staff' => $staff]);
    }

    public function presence(Request $request): JsonResponse
    {
        $identity = $request->attributes->get('clinic_identity');
        $lastSeenAt = now();
        $updated = Staff::query()
            ->whereKey((int) $identity['id'])
            ->whereIn('role', ['nurse', 'nurse_head'])
            ->where('is_active', true)
            ->update(['last_seen_at' => $lastSeenAt]);

        if ($updated !== 1) {
            return response()->json(['success' => false, 'error' => 'Active staff account not found.'], 403);
        }

        return response()->json(['success' => true, 'last_seen_at' => $lastSeenAt->toISOString()]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/messages/direct
    // Find or create a DM channel between the current user and a recipient.
    // Body: { recipient_id }
    // Returns the channel + recent messages so the UI can open it immediately.
    // -----------------------------------------------------------------------
    public function direct(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:staff,id'],
        ]);

        $senderId = (int) $request->attributes->get('clinic_identity')['id'];
        if ((int) $data['recipient_id'] === $senderId) {
            return response()->json(['error' => 'You cannot start a conversation with yourself'], 422);
        }

        $sender = Staff::findOrFail($senderId);
        $recipient = Staff::query()
            ->whereKey($data['recipient_id'])
            ->whereIn('role', ['nurse', 'nurse_head'])
            ->where('is_active', true)
            ->firstOrFail();

        $senderName = trim($sender->first_name.' '.$sender->last_name);
        $recipientName = trim($recipient->first_name.' '.$recipient->last_name);

        $channel = MessageChannel::firstOrCreateDirect(
            $senderId,
            $data['recipient_id'],
            $senderName,
            $recipientName,
        );
        DB::table('message_channel_user_states')
            ->where('channel_id', $channel->id)
            ->where('staff_id', $senderId)
            ->where('person_hidden', true)
            ->update([
                'person_hidden' => false,
                'updated_at' => now(),
            ]);

        // Return the 50 most-recent messages so the UI can render immediately.
        Message::clearSenderCache();
        $channelState = DB::table('message_channel_user_states')
            ->where('channel_id', $channel->id)
            ->where('staff_id', $senderId)
            ->first();
        $messageQuery = Message::where('channel_id', $channel->id);
        if ($channelState?->hidden_before_message_id !== null) {
            $messageQuery->where('id', '>', (int) $channelState->hidden_before_message_id);
        }
        $messages = $messageQuery
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();
        $pictureIds = $this->staffPictureIds([$recipient->id]);

        return response()->json([
            'success' => true,
            'channel' => [
                'id' => $channel->id,
                'name' => $channel->name,
                'recipient_id' => $recipient->id,
                'recipient_name' => $recipientName,
                'recipient_picture_url' => in_array($recipient->id, $pictureIds, true)
                    ? $this->profilePictureUrl($recipient->id)
                    : null,
            ] + $this->presenceData($recipient->last_seen_at),
            'messages' => $this->serializeMessages($messages),
        ], 200);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/messages/conversations?staff_id=X
    // All DM channels the given staff member is part of, ordered by most recent
    // message so the conversation list is usefully sorted.
    // -----------------------------------------------------------------------
    public function conversations(Request $request): JsonResponse
    {
        $staffId = (int) $request->attributes->get('clinic_identity')['id'];

        $channels = MessageChannel::where('type', 'direct')
            ->where(fn ($q) => $q
                ->where('participant_a', $staffId)
                ->orWhere('participant_b', $staffId))
            ->addSelect([
                'last_message_body' => Message::select('body')
                    ->whereColumn('channel_id', 'message_channels.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'last_message_attachment_path' => Message::select('attachment_path')
                    ->whereColumn('channel_id', 'message_channels.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'last_message_attachment_mime' => Message::select('attachment_mime')
                    ->whereColumn('channel_id', 'message_channels.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'last_message_sender_id' => Message::select('sender_id')
                    ->whereColumn('channel_id', 'message_channels.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'last_message_seen_at' => Message::select('seen_at')
                    ->whereColumn('channel_id', 'message_channels.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'last_message_at' => Message::select('created_at')
                    ->whereColumn('channel_id', 'message_channels.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'last_message_id' => Message::select('id')
                    ->whereColumn('channel_id', 'message_channels.id')
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->orderByDesc('last_message_at')
            ->get();
        $directStates = DB::table('message_channel_user_states')
            ->where('staff_id', $staffId)
            ->whereIn('channel_id', $channels->pluck('id'))
            ->get()
            ->keyBy('channel_id');
        $otherIds = $channels->map(fn (MessageChannel $channel) => (int) (
            (int) $channel->participant_a === $staffId
                ? $channel->participant_b
                : $channel->participant_a
        ))->unique()->values();
        $otherStaff = Staff::query()
            ->whereIn('id', $otherIds)
            ->whereIn('role', ['nurse', 'nurse_head'])
            ->where('is_active', true)
            ->get(['id', 'first_name', 'last_name', 'last_seen_at'])
            ->keyBy('id');
        $pictureIds = $this->staffPictureIds($otherIds->all());

        $conversations = $channels->map(function (MessageChannel $ch) use ($staffId, $otherStaff, $pictureIds, $directStates) {
            $state = $directStates->get($ch->id);
            $lastMessageId = (int) ($ch->last_message_id ?? 0);
            if (
                $state !== null
                && ((bool) $state->person_hidden
                    || $lastMessageId <= (int) ($state->hidden_before_message_id ?? 0))
            ) {
                return null;
            }

            $otherId = (int) ((int) $ch->participant_a === $staffId
                ? $ch->participant_b
                : $ch->participant_a);
            $other = $otherStaff->get($otherId);

            return [
                'id' => $ch->id,
                'recipient_id' => $otherId,
                'recipient_name' => $other
                    ? trim($other->first_name.' '.$other->last_name)
                    : 'Unknown',
                'recipient_picture_url' => in_array($otherId, $pictureIds, true)
                    ? $this->profilePictureUrl($otherId)
                    : null,
                'type' => 'direct',
                'last_message' => $ch->last_message_body ?: ($ch->last_message_attachment_path ? 'Attachment' : null),
                'last_message_sender_id' => $ch->last_message_sender_id,
                'last_message_seen_at' => $ch->last_message_seen_at,
                'last_at' => $ch->last_message_at,
            ] + $this->presenceData($other?->last_seen_at);
        });

        return response()->json([
            'success' => true,
            'conversations' => $conversations
                ->filter()
                ->sortByDesc('last_at')
                ->values(),
        ]);
    }

    /**
     * @return array{is_online: bool, last_online_at: string|null}
     */
    private function presenceData(?Carbon $lastSeenAt): array
    {
        return [
            'is_online' => $lastSeenAt?->greaterThanOrEqualTo(now()->subSeconds(90)) ?? false,
            'last_online_at' => $lastSeenAt?->toISOString(),
        ];
    }

    public function staffPicture(int $staffId): Response
    {
        $picture = Staff::query()
            ->whereKey($staffId)
            ->whereIn('role', ['nurse', 'nurse_head'])
            ->where('is_active', true)
            ->value('profile_picture');

        if (! is_string($picture) || ! preg_match('/^data:image\/(jpeg|png|webp);base64,([A-Za-z0-9+\/=]+)$/', $picture, $matches)) {
            abort(404);
        }

        $contents = base64_decode($matches[2], true);
        $image = $contents === false ? false : getimagesizefromstring($contents);

        if ($contents === false || $image === false || $image['mime'] !== 'image/'.$matches[1]) {
            abort(404);
        }

        return response($contents)
            ->header('Content-Type', $image['mime'])
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    /**
     * @param  Collection<int, Message>  $messages
     * @return Collection<int, array<string, mixed>>
     */
    private function serializeMessages(Collection $messages): Collection
    {
        $channels = MessageChannel::query()
            ->whereIn('id', $messages->pluck('channel_id')->unique())
            ->get(['id', 'name', 'type'])
            ->keyBy('id');
        $senderIds = $messages
            ->where('sender_type', 'staff')
            ->pluck('sender_id')
            ->unique()
            ->values()
            ->all();
        $pictureIds = $this->staffPictureIds($senderIds);

        return $messages->map(fn (Message $message) => [
            'id' => $message->id,
            'channel_id' => $message->channel_id,
            'channel_type' => $channels->get($message->channel_id)?->type,
            'sender_id' => $message->sender_id,
            'sender_type' => $message->sender_type,
            'sender_name' => $message->sender_name,
            'sender_picture_url' => $message->sender_type === 'staff'
                && in_array((int) $message->sender_id, $pictureIds, true)
                    ? $this->profilePictureUrl((int) $message->sender_id)
                    : null,
            'body' => $message->body,
            'attachment_name' => $message->is_unsent ? null : $message->attachment_name,
            'attachment_url' => $message->attachment_path
                && ! $message->is_unsent
                ? "/api/clinic/messages/{$message->channel_id}/{$message->id}/attachment"
                : null,
            'attachment_mime' => $message->is_unsent ? null : $message->attachment_mime,
            'is_system' => $message->is_system,
            'is_unsent' => $message->is_unsent,
            'is_pinned' => $message->is_pinned,
            'is_forwarded' => $message->forwarded_from_id !== null,
            'edited_at' => $message->edited_at,
            'seen_at' => $message->seen_at,
            'created_at' => $message->created_at,
            'updated_at' => $message->updated_at,
        ]);
    }

    /**
     * @param  array<int, int>  $staffIds
     * @return array<int, int>
     */
    private function staffPictureIds(?array $staffIds = null): array
    {
        return Staff::query()
            ->whereIn('role', ['nurse', 'nurse_head'])
            ->where('is_active', true)
            ->when($staffIds !== null, fn ($query) => $query->whereIn('id', $staffIds))
            ->whereNotNull('profile_picture')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function profilePictureUrl(int $staffId): string
    {
        return "/api/clinic/messages/staff/{$staffId}/profile-picture";
    }

    private function directChannelFor(Request $request, int $channelId): MessageChannel
    {
        $channel = MessageChannel::query()
            ->whereKey($channelId)
            ->where('type', 'direct')
            ->firstOrFail();
        $identity = $request->attributes->get('clinic_identity');

        abort_unless(
            in_array((int) $identity['id'], [(int) $channel->participant_a, (int) $channel->participant_b], true),
            403,
            'You are not authorized to access this conversation',
        );

        return $channel;
    }

    private function ownedMessage(MessageChannel $channel, int $messageId, int $staffId): Message
    {
        $message = Message::query()
            ->where('channel_id', $channel->id)
            ->whereKey($messageId)
            ->firstOrFail();

        abort_unless(
            $message->sender_type === 'staff'
                && (int) $message->sender_id === $staffId
                && ! $message->is_system
                && ! $message->is_unsent,
            403,
            'You can only edit or unsend your own messages',
        );

        return $message;
    }
}
