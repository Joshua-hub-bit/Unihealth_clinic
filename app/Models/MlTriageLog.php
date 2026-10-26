<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MlTriageLog extends Model
{
    protected $table = 'ml_triage_logs';

    protected $fillable = [
        'student_id',
        'clinic_visit_id',
        'campus_id',
        'input_features',
        'predicted_priority',
        'confidence_score',
        'reviewed_by_staff',
        'final_priority',
        'model_version',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'input_features' => 'array',
            'confidence_score' => 'decimal:4',
            'reviewed_by_staff' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function clinicVisit(): BelongsTo
    {
        return $this->belongsTo(ClinicVisit::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }
}
