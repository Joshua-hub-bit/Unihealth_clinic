<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referral extends Model
{
    protected $fillable = [
        'referral_no',
        'student_id',
        'from_campus_id',
        'to_campus_id',
        'referred_by',
        'referred_to_name',
        'specialty',
        'referral_type',
        'route_to',
        'diagnosis',
        'history',
        'reason',
        'management_given',
        'vital_signs',
        'signature_name',
        'signature_role',
        'signature_license_no',
        'urgency',
        'status',
        'feedback',
        'accepted_at',
        'scheduled_at',
        'source_appointment_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromCampus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'from_campus_id');
    }

    public function toCampus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'to_campus_id');
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'referred_by');
    }

    public function sourceAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'source_appointment_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(FormCertificate::class);
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeForCampus(Builder $query, ?int $campusId): Builder
    {
        if ($campusId === null) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($campusId) {
            $q->where('from_campus_id', $campusId)
                ->orWhere('to_campus_id', $campusId)
                ->orWhere(function (Builder $q) use ($campusId) {
                    $q->whereNull('from_campus_id')
                        ->whereNull('to_campus_id')
                        ->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('campus_id', $campusId));
                });
        });
    }

    public function scopeUrgent(Builder $query): Builder
    {
        return $query->whereIn('urgency', ['urgent', 'emergency']);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Generate the next referral number (e.g. REF-2026-00042).
     */
    public static function generateReferralNo(): string
    {
        $year = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('REF-%d-%05d', $year, $count);
    }
}
