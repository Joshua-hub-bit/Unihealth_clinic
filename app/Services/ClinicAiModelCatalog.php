<?php

namespace App\Services;

use App\Models\SystemSetting;

class ClinicAiModelCatalog
{
    /**
     * @return array<string, string>
     */
    public static function options(string $type): array
    {
        abort_unless(in_array($type, ['analysis', 'transcription'], true), 404);

        $options = config("clinic_ai.{$type}_models", []);
        foreach (self::customModels($type) as $model) {
            $options[$model['id']] = $model['name'];
        }

        return $options;
    }

    public static function selected(string $type): string
    {
        abort_unless(in_array($type, ['analysis', 'transcription'], true), 404);

        $default = $type === 'analysis'
            ? (string) config('services.groq.analytics_model', 'openai/gpt-oss-20b')
            : (string) config('services.groq.transcription_model', 'whisper-large-v3-turbo');
        $selected = SystemSetting::getValue("clinic_ai_{$type}_model", $default);
        $options = self::options($type);

        return is_string($selected) && array_key_exists($selected, $options)
            ? $selected
            : (array_key_first($options) ?? $default);
    }

    /**
     * @return array<string, string>
     */
    public static function customOptions(string $type): array
    {
        $options = [];
        foreach (self::customModels($type) as $model) {
            $options[$model['id']] = $model['name'];
        }

        return $options;
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public static function customModels(string $type): array
    {
        abort_unless(in_array($type, ['analysis', 'transcription'], true), 404);

        $customModels = SystemSetting::getValue("clinic_ai_{$type}_custom_models", []);
        if (! is_array($customModels)) {
            return [];
        }

        $models = [];
        foreach ($customModels as $model) {
            if (is_string($model)) {
                $id = $model;
                $name = $model;
            } elseif (is_array($model)) {
                $id = $model['id'] ?? null;
                $name = $model['name'] ?? $id;
            } else {
                continue;
            }

            if (
                is_string($id)
                && preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:\/-]{0,119}\z/', $id)
                && is_string($name)
                && trim($name) !== ''
                && mb_strlen(trim($name)) <= 100
            ) {
                $models[$id] = ['id' => $id, 'name' => trim($name)];
            }
        }

        return array_values($models);
    }
}
