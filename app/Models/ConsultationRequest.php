<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConsultationRequest extends Model
{
    protected $fillable = [
        'student_id',
        'campus_id',
        'nurse_id',
        'physician_id',
        'clinic_visit_id',
        'appointment_id',
        'referral_id',
        'medical_transcription_id',
        'triage_log_id',
        'care_type',
        'concern',
        'symptom_onset',
        'severity',
        'status',
        'decline_reason',
        'triage_priority',
        'triage_confidence',
        'triage_details',
        'student_voice_path',
        'student_photo_path',
        'official_audio_path',
        'accepted_at',
        'identity_confirmed_at',
        'recording_consent_at',
        'recording_started_at',
        'audio_uploaded_at',
        'nurse_reviewed_at',
        'physician_reviewed_at',
        'scheduled_at',
        'nurse_summary',
        'staff_notes',
        'nursing_advice',
        'approved_instructions',
        'diagnosis',
        'treatment_plan',
        'follow_up',
        'emergency_instructions',
        'vitals',
    ];

    protected function casts(): array
    {
        return [
            'triage_details' => 'array',
            'vitals' => 'array',
            'triage_confidence' => 'float',
            'accepted_at' => 'datetime',
            'identity_confirmed_at' => 'datetime',
            'recording_consent_at' => 'datetime',
            'recording_started_at' => 'datetime',
            'audio_uploaded_at' => 'datetime',
            'nurse_reviewed_at' => 'datetime',
            'physician_reviewed_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'severity' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
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

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function medicalTranscription(): BelongsTo
    {
        return $this->belongsTo(MedicalTranscription::class);
    }

    public function recording(): HasOne
    {
        return $this->hasOne(ConsultationRecording::class);
    }

    public function triageLog(): BelongsTo
    {
        return $this->belongsTo(MlTriageLog::class, 'triage_log_id');
    }

    public function scopeForCampus(Builder $query, ?int $campusId): Builder
    {
        return $query->when($campusId !== null, fn (Builder $query) => $query->where('campus_id', $campusId));
    }
}
