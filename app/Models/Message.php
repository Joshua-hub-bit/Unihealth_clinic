<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    /**
     * Static cache for sender lookups keyed by "type:id".
     * Prevents N+1 queries when serializing a collection of messages.
     */
    protected static array $senderCache = [];

    protected $fillable = [
        'channel_id',
        'sender_id',
        'sender_type',
        'body',
        'external_message_id',
        'attachment_path',
        'attachment_disk',
        'attachment_mime',
        'attachment_name',
        'is_system',
        'is_unsent',
        'is_pinned',
        'pinned_by',
        'edited_at',
        'forwarded_from_id',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_unsent' => 'boolean',
            'is_pinned' => 'boolean',
            'edited_at' => 'datetime',
            'seen_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function channel(): BelongsTo
    {
        return $this->belongsTo(MessageChannel::class, 'channel_id');
    }

    /**
     * Clear the static sender cache.
     */
    public static function clearSenderCache(): void
    {
        static::$senderCache = [];
    }

    /**
     * Resolve sender as Staff or Student depending on sender_type.
     * Uses a static cache to avoid N+1 when serializing message collections.
     */
    public function getSenderNameAttribute(): string
    {
        if (! in_array($this->sender_type, ['staff', 'student'], true)) {
            return 'External contact';
        }

        $key = $this->sender_type.':'.$this->sender_id;

        if (! isset(static::$senderCache[$key])) {
            static::$senderCache[$key] = $this->sender_type === 'staff'
                ? Staff::find($this->sender_id)
                : Student::find($this->sender_id);
        }

        $sender = static::$senderCache[$key];

        if (! $sender) {
            return 'Unknown';
        }

        return trim(($sender->first_name ?? '').' '.($sender->last_name ?? ''));
    }

    /**
     * Appended attribute so sender_name is always in toArray().
     */
    protected $appends = ['sender_name'];
}
