<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    /**
     * system_settings has only updated_at; no created_at column.
     */
    const CREATED_AT = null;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'setting_value' => 'array',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Retrieve a setting value by key, with an optional default.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('setting_key', $key)->first();

        return $setting?->setting_value ?? $default;
    }

    /**
     * Upsert a single setting value.
     */
    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value],
        );
    }
}
