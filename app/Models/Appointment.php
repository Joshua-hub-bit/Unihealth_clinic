<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'appt_date',
        'checkup_date',
        'checkup_time',
        'checkup_completed_at',
        'appt_time',
        'preferred_time',
        'service_type',
        'reason',
        'status',
        'triage_priority',
        'triage_confidence',
        'triage_details',
        'triage_log_id',
        'cancellation_reason',
        'notes',
        'created_by',
        'assigned_professional_id',
        'campus_id',
        'availability_id',
    ];

    protected function casts(): array
    {
        return [
            'appt_date' => 'date',
            'checkup_date' => 'date',
            'checkup_completed_at' => 'datetime',
            'triage_confidence' => 'float',
            'triage_details' => 'array',
        ];
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function assignedProfessional(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_professional_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function availability(): BelongsTo
    {
        return $this->belongsTo(ClinicAvailability::class, 'availability_id');
    }

    public function consultationRequest(): HasOne
    {
        return $this->hasOne(ConsultationRequest::class);
    }

    public function sourceReferrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'source_appointment_id');
    }

    public function triageLog(): BelongsTo
    {
        return $this->belongsTo(MlTriageLog::class);
    }

    public function priorityLevel(): ?string
    {
        $priority = $this->triage_priority;
        if (! $priority && $this->relationLoaded('consultationRequest')) {
            $priority = $this->consultationRequest?->triage_priority;
        }

        return match (strtolower((string) $priority)) {
            'priority 1', 'high' => 'high',
            'priority 2', 'medium' => 'medium',
            'priority 3', 'low' => 'low',
            default => null,
        };
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'accepted', 'checkup_scheduled', 'checkup_completed', 'confirmed']);
    }

    public function scopeForCampus(Builder $query, ?int $campusId): Builder
    {
        if ($campusId === null) {
            return $query;
        }

        return $query->where('campus_id', $campusId);
    }

    public function scopeWithoutSyntheticDemo(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $query): void {
                $query->whereNull('notes')
                    ->orWhere(function (Builder $query): void {
                        $query->where('notes', 'not like', 'DEMO DATA:%')
                            ->where('notes', 'not like', 'SAMPLE DATA:%');
                    });
            })
            ->where(function (Builder $query): void {
                $query->whereNull('cancellation_reason')
                    ->orWhere(function (Builder $query): void {
                        $query->where('cancellation_reason', 'not like', 'DEMO DATA:%')
                            ->where('cancellation_reason', 'not like', 'SAMPLE DATA:%');
                    });
            });
    }
}
