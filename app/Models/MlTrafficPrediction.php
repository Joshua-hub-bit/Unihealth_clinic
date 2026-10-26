<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MlTrafficPrediction extends Model
{
    protected $table = 'ml_traffic_predictions';

    protected $fillable = [
        'campus_id',
        'prediction_date',
        'predicted_count',
        'actual_count',
        'input_features',
        'mae',
    ];

    protected function casts(): array
    {
        return [
            'prediction_date' => 'date',
            'input_features' => 'array',
            'mae' => 'decimal:4',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }
}
