<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ClinicNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // -----------------------------------------------------------------------
    // GET /api/clinic/notifications
    // -----------------------------------------------------------------------
    public function index(Request $request): JsonResponse
    {
        $recipientId = (int) $request->query('recipient_id', 0);
        $recipientType = (string) $request->query('recipient_type', 'student');
        $unreadOnly = $request->boolean('unread');
        $limit = min(10, max(1, (int) $request->query('limit', 10)));

        if (! $recipientId) {
            return response()->json(['error' => 'recipient_id required'], 400);
        }

        $query = ClinicNotification::forRecipient($recipientId, $recipientType)
            ->when($unreadOnly, fn ($q) => $q->unread())
            ->latest()
            ->take($limit);

        $notifications = $query->get();
        $unreadCount = ClinicNotification::forRecipient($recipientId, $recipientType)->unread()->count();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // -----------------------------------------------------------------------
    // PATCH /api/clinic/notifications/{id}/read
    // -----------------------------------------------------------------------
    public function markRead(int $id): JsonResponse
    {
        $notification = ClinicNotification::findOrFail($id);
        $notification->markRead();

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/notifications/mark-all-read
    // -----------------------------------------------------------------------
    public function markAllRead(Request $request): JsonResponse
    {
        $recipientId = (int) $request->query('recipient_id', 0);
        $recipientType = (string) $request->query('recipient_type', 'student');

        if (! $recipientId) {
            return response()->json(['error' => 'recipient_id required'], 400);
        }

        ClinicNotification::forRecipient($recipientId, $recipientType)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // DELETE /api/clinic/notifications/{id}
    // -----------------------------------------------------------------------
    public function destroy(int $id): JsonResponse
    {
        ClinicNotification::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/notifications  (send a notification — admin/staff use)
    // -----------------------------------------------------------------------
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipient_id' => ['required', 'integer'],
            'recipient_type' => ['required', 'string', 'in:student,staff'],
            'type' => ['sometimes', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:150'],
            'body' => ['sometimes', 'nullable', 'string'],
            'data' => ['sometimes', 'nullable', 'array'],
        ]);

        $notification = ClinicNotification::create($data);

        return response()->json(['success' => true, 'id' => (int) $notification->getKey()], 201);
    }
}
