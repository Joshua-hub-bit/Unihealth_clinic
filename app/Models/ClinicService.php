<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ClinicService extends Model
{
    protected $table = 'clinic_services';

    protected $fillable = [
        'name',
        'category',
        'description',
        'duration_minutes',
        'requires_appointment',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_appointment' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }
}
