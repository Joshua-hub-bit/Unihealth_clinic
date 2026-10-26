<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentalRecord extends Model
{
    protected $fillable = [
        'clinic_visit_id',
        'student_id',
        'dentist_id',
        'dentist_provider_id',
        'campus_id',
        'record_date',
        'chief_complaint',
        'dental_history',
        'oral_exam_findings',
        'tooth_chart',
        'treatment_done',
        'treatment_plan',
        'next_appointment',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'tooth_chart' => 'array',
        ];
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'dentist_id');
    }

    public function dentistProvider(): BelongsTo
    {
        return $this->belongsTo(ClinicProvider::class, 'dentist_provider_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function clinicVisit(): BelongsTo
    {
        return $this->belongsTo(ClinicVisit::class);
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeForCampus(Builder $query, ?int $campusId): Builder
    {
        if ($campusId === null) {
            return $query;
        }

        return $query->where('campus_id', $campusId);
    }
}
