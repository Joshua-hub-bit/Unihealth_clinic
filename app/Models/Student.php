<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Student extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The column used to store the hashed password.
     * Matches the 'password' column in the migration.
     */
    protected $fillable = [
        'student_id',
        'last_name',
        'first_name',
        'middle_name',
        'email',
        'password',
        'course_year',
        'department',
        'section',
        'contact',
        'address',
        'sex',
        'civil_status',
        'age',
        'date_of_birth',
        'blood_type',
        'profile_picture',
        'role',
        'patient_type',
        'is_active',
        'campus_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'age' => 'integer',
            'date_of_birth' => 'date',
        ];
    }

    /**
     * Return the student's full display name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class);
    }

    public function latestHealthRecord(): HasOne
    {
        return $this->hasOne(HealthRecord::class)->latestOfMany();
    }

    public function latestClinicVisit(): HasOne
    {
        return $this->hasOne(ClinicVisit::class)
            ->withoutSyntheticDemo()
            ->latestOfMany('visit_date');
    }

    public function latestConfirmedAppointment(): HasOne
    {
        return $this->hasOne(Appointment::class)
            ->where('status', 'confirmed')
            ->latestOfMany();
    }

    public function latestClinicianApprovedConsultation(): HasOne
    {
        return $this->hasOne(ConsultationRequest::class)
            ->where('status', 'physician_approved')
            ->latestOfMany();
    }

    public function clinicVisits(): HasMany
    {
        return $this->hasMany(ClinicVisit::class);
    }

    public function consultationRequests(): HasMany
    {
        return $this->hasMany(ConsultationRequest::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class);
    }

    public function formCertificates(): HasMany
    {
        return $this->hasMany(FormCertificate::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function dentalRecords(): HasMany
    {
        return $this->hasMany(DentalRecord::class);
    }

    public function medicalTranscriptions(): HasMany
    {
        return $this->hasMany(MedicalTranscription::class);
    }

    public function clinicNotifications(): HasMany
    {
        return $this->hasMany(ClinicNotification::class, 'recipient_id')
            ->where('recipient_type', 'student');
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeStudents(Builder $query): Builder
    {
        return $query->where('role', 'student');
    }

    public function scopeStudentAccounts(Builder $query): Builder
    {
        return $query->students()->where('patient_type', 'student');
    }

    public function scopeAdditionalPatientAccounts(Builder $query): Builder
    {
        return $query->students()->whereIn('patient_type', [
            'instructor_faculty',
            'staff',
            'faculty_staff',
            'faculty',
            'instructor',
        ]);
    }

    public function scopeForCampus(Builder $query, ?int $campusId): Builder
    {
        if ($campusId === null) {
            return $query;
        }

        return $query->where('campus_id', $campusId);
    }
}
