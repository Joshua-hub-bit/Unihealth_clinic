<?php

return [
    'triage' => [
        'mode' => env('ML_TRIAGE_MODE', 'shadow'),
        'model_path' => env('ML_TRIAGE_MODEL_PATH', storage_path('app/models/triage.model')),
        'model_version' => env('ML_TRIAGE_MODEL_VERSION', 'rules-v1'),
        'minimum_confidence' => (float) env('ML_TRIAGE_MIN_CONFIDENCE', 0.80),
        'feature_version' => 'triage-v1',
    ],
];
