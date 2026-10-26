<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicAvailability extends Model
{
    protected $fillable = [
        'campus_id',
        'created_by',
        'provider_type',
        'available_date',
        'is_available',
        'opens_at',
        'closes_at',
        'capacity',
        'available_times',
    ];

    protected function casts(): array
    {
        return [
            'available_date' => 'date',
            'is_available' => 'boolean',
            'available_times' => 'array',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'availability_id');
    }
}
