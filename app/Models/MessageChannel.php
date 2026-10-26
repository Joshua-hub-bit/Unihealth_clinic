<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MessageChannel extends Model
{
    protected $table = 'message_channels';

    protected $fillable = [
        'name',
        'type',
        'campus_id',
        'participant_a',
        'participant_b',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'channel_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'channel_id')->latest();
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // -----------------------------------------------------------------------
    // DM helpers
    // -----------------------------------------------------------------------

    /**
     * Find an existing DM channel between two staff members, regardless of
     * which side they were stored on (participant_a vs participant_b).
     */
    public static function findDirect(int $staffA, int $staffB): ?self
    {
        [$low, $high] = $staffA < $staffB ? [$staffA, $staffB] : [$staffB, $staffA];

        return static::where('type', 'direct')
            ->where('participant_a', $low)
            ->where('participant_b', $high)
            ->first();
    }

    /**
     * Find or create a DM channel between two staff members.
     * Always stores the lower ID in participant_a to guarantee the unique
     * constraint covers both orderings.
     */
    public static function firstOrCreateDirect(int $staffA, int $staffB, string $nameA, string $nameB): self
    {
        [$low, $high, $nameL, $nameH] = $staffA < $staffB
            ? [$staffA, $staffB, $nameA, $nameB]
            : [$staffB, $staffA, $nameB, $nameA];

        return static::firstOrCreate(
            ['participant_a' => $low, 'participant_b' => $high, 'type' => 'direct'],
            ['name' => $nameL.' & '.$nameH, 'is_active' => true],
        );
    }
}
