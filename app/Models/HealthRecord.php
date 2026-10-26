<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'record_date',
        'last_name',
        'first_name',
        'middle_name',
        'contact',
        'course_year',
        'age',
        'date_of_birth',
        'blood_type',
        'height_cm',
        'weight_kg',
        'ec_name',
        'ec_relationship',
        'ec_address',
        'ec_contact',
        'allergy_detail',
        'accident_detail',
        'hospitalization_detail',
        'surgical_detail',
        'family_history',
        'past_medical_history',
        'sym_headache',
        'sym_dizziness',
        'sym_fainting',
        'sym_blurring_vision',
        'sym_hearing_difficulty',
        'sym_back_pain',
        'sym_chest_pain',
        'sym_breathing_difficulty',
        'sym_cough',
        'sym_sore_throat',
        'sym_runny_nose',
        'sym_nose_bleed',
        'sym_vomiting',
        'sym_nausea',
        'sym_abdominal_pain',
        'sym_fever',
        'sym_frequent_urination',
        'sym_weight_loss',
        'sym_difficulty_sleeping',
        'sym_nervousness',
        'imm_chicken_pox',
        'imm_typhoid',
        'imm_hepatitis_a',
        'imm_hepatitis_b',
        'imm_tetanus',
        'imm_covid',
        'menstrual_regular',
        'menstrual_irregular',
        'excessive_blood_flow',
        'avg_days',
        'dysmenorrhea',
        'bp',
        'pr',
        'temp',
        'status',
        'findings_recommendation',
        'signature_name',
        'campus_id',
        'imported_medical_history',
        'imported_booking_choice',
        'imported_reason_symptoms',
        'imported_priority_level',
        'imported_prescriber',
        'imported_prescription',
        'source_dataset',
        'source_record_no',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'date_of_birth' => 'date',
            'height_cm' => 'decimal:2',
            'weight_kg' => 'decimal:2',
            'temp' => 'decimal:1',
            'age' => 'integer',
            'imm_chicken_pox' => 'boolean',
            'imm_typhoid' => 'boolean',
            'imm_hepatitis_a' => 'boolean',
            'imm_hepatitis_b' => 'boolean',
            'imm_tetanus' => 'boolean',
            'imm_covid' => 'boolean',
            'menstrual_regular' => 'boolean',
            'menstrual_irregular' => 'boolean',
        ];
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }
}
