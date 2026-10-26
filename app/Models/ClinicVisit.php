<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClinicVisit extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (ClinicVisit $visit): void {
            if (filled($visit->visit_time)) {
                return;
            }

            $visit->visit_time = filled($visit->getOriginal('visit_time'))
                ? $visit->getOriginal('visit_time')
                : ($visit->created_at?->copy()->timezone('Asia/Manila') ?? now('Asia/Manila'))->format('H:i:s');
        });
    }

    protected $fillable = [
        'student_id',
        'visit_date',
        'visit_time',
        'reason',
        'symptoms',
        'treatment',
        'bp',
        'temp',
        'pulse',
        'rr',
        'o2_sat',
        'weight_kg',
        'nurse_id',
        'physician_id',
        'disposition',
        'notes',
        'campus_id',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'temp' => 'decimal:1',
            'weight_kg' => 'decimal:2',
            'pulse' => 'integer',
            'rr' => 'integer',
            'o2_sat' => 'integer',
        ];
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'nurse_id');
    }

    public function physician(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'physician_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function consultationRequests(): HasMany
    {
        return $this->hasMany(ConsultationRequest::class);
    }

    public function dentalRecord(): HasOne
    {
        return $this->hasOne(DentalRecord::class);
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

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('visit_date', today());
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('disposition');
    }

    public function scopeWithoutSyntheticDemo(Builder $query): Builder
    {
        foreach (['reason', 'symptoms', 'treatment', 'disposition', 'notes'] as $column) {
            $query->where(function (Builder $query) use ($column): void {
                $query->whereNull($column)
                    ->orWhere(function (Builder $query) use ($column): void {
                        foreach (['%synthetic%', '%demo data%', '%demo placeholder%', '%not a real visit%', '%generated for dashboard testing%'] as $pattern) {
                            $query->whereRaw("LOWER({$column}) NOT LIKE ?", [$pattern]);
                        }
                    });
            });
        }

        return $query;
    }

    public function scopeWithClinicalDocumentation(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where(fn (Builder $query) => $query->whereNotNull('reason')->where('reason', '!=', ''))
                ->orWhere(fn (Builder $query) => $query->whereNotNull('treatment')->where('treatment', '!=', ''))
                ->orWhereHas(
                    'consultationRequests',
                    fn (Builder $query) => $query->whereIn('status', ['completed', 'physician_approved']),
                );
        });
    }
}
