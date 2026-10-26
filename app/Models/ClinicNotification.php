<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ClinicNotification extends Model
{
    protected $table = 'clinic_notifications';

    protected $fillable = [
        'recipient_id',
        'recipient_type',
        'type',
        'title',
        'body',
        'data',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeForRecipient(Builder $query, int $recipientId, string $recipientType): Builder
    {
        return $query->where('recipient_id', $recipientId)
            ->where('recipient_type', $recipientType);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Mark this notification as read.
     */
    public function markRead(): void
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Create a notification for a student.
     */
    public static function notifyStudent(int $studentId, string $type, string $title, ?string $body = null, ?array $data = null): static
    {
        return static::create([
            'recipient_id' => $studentId,
            'recipient_type' => 'student',
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    /**
     * Create a notification for a staff member.
     */
    public static function notifyStaff(int $staffId, string $type, string $title, ?string $body = null, ?array $data = null): static
    {
        return static::create([
            'recipient_id' => $staffId,
            'recipient_type' => 'staff',
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    /**
     * Notify every active staff member with a given role (optionally scoped
     * to a campus). Used to alert the receiving physician/dentist when a
     * referral is routed to their role.
     */
    public static function notifyStaffByRole(string $role, string $type, string $title, ?string $body = null, ?array $data = null, ?int $campusId = null): void
    {
        $query = Staff::active()->where('role', $role);
        if ($campusId) {
            $query->where('campus_id', $campusId);
        }
        $query->each(function (Staff $staff) use ($type, $title, $body, $data) {
            static::notifyStaff($staff->id, $type, $title, $body, $data);
        });
    }
}
