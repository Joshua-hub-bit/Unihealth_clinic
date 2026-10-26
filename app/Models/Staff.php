<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Staff extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'staff';

    protected $fillable = [
        'employee_id',
        'last_name',
        'first_name',
        'middle_name',
        'email',
        'password',
        'role',
        'license_no',
        'contact',
        'specialization',
        'profile_picture',
        'is_active',
        'last_seen_at',
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
            'last_seen_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function nurseVisits(): HasMany
    {
        return $this->hasMany(ClinicVisit::class, 'nurse_id');
    }

    public function physicianVisits(): HasMany
    {
        return $this->hasMany(ClinicVisit::class, 'physician_id');
    }

    public function prescribedMedications(): HasMany
    {
        return $this->hasMany(Medication::class, 'prescribed_by');
    }

    public function formCertificates(): HasMany
    {
        return $this->hasMany(FormCertificate::class, 'staff_id');
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCampus(Builder $query, ?int $campusId): Builder
    {
        if ($campusId === null) {
            return $query;
        }

        return $query->where('campus_id', $campusId);
    }

    public function scopeClinicPersonnel(Builder $query): Builder
    {
        return $query->whereIn('role', ['nurse', 'nurse_head']);
    }
}
