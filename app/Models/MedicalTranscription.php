<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalTranscription extends Model
{
    protected $fillable = [
        'student_id',
        'consultation_request_id',
        'nurse_id',
        'physician_id',
        'clinic_visit_id',
        'campus_id',
        'consultation_date',
        'raw_transcript',
        'ai_draft',
        'approved_notes',
        'status',
        'audio_duration_seconds',
        'processing_duration_seconds',
        'processing_started_at',
        'rejection_reason',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'consultation_date' => 'date',
            'reviewed_at' => 'datetime',
            'processing_started_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function consultationRequest(): BelongsTo
    {
        return $this->belongsTo(ConsultationRequest::class);
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'nurse_id');
    }

    public function physician(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'physician_id');
    }

    public function clinicVisit(): BelongsTo
    {
        return $this->belongsTo(ClinicVisit::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeForCampus(Builder $query, ?int $campusId): Builder
    {
        if ($campusId === null) {
            return $query;
        }

        return $query->where('campus_id', $campusId);
    }
}
