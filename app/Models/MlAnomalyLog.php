<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MlAnomalyLog extends Model
{
    protected $table = 'ml_anomaly_logs';

    protected $fillable = [
        'campus_id',
        'log_date',
        'input_features',
        'anomaly_score',
        'is_anomaly',
        'description',
        'reviewed',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'input_features' => 'array',
            'anomaly_score' => 'decimal:6',
            'is_anomaly' => 'boolean',
            'reviewed' => 'boolean',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }
}
