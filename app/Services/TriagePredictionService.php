<?php

namespace App\Services;

use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;

class TriagePredictionService
{
    /**
     * Keyword indicators used to convert free-text clinical notes into
     * numerical features the RandomForest can consume.  These overlap with
     * the deterministic rules in TriageRulesService, which is fine — the
     * ML model learns *combinations* of indicators, not individual terms.
     *
     * Keys are the feature names stored in the model; values are the
     * space-separated phrases searched in the lowercased text.  Using
     * natural-language phrases (spaces) ensures they match real clinical
     * free-text as it is stored in the database and CSV exports.
     */
    private const INDICATOR_KEYWORDS = [
        // Emergency indicators
        'difficulty_breathing' => 'difficulty breathing',
        'shortness_of_breath'  => 'shortness of breath',
        'breathlessness'       => 'breathlessness',
        'unconscious'          => 'unconscious',
        'seizure'              => 'seizure',
        'severe_trauma'        => 'severe trauma',
        'uncontrolled_bleeding' => 'uncontrolled bleeding',
        'chest_pain'           => 'chest pain',
        'chest_tightness'      => 'chest tightness',
        'airway_swelling'      => 'airway swelling',
        'facial_swelling'      => 'facial swelling',
        'facial_trauma'        => 'facial trauma',
        'dental_abscess'       => 'dental abscess',
        'severe_headache'      => 'severe headache',
        'stiff_neck'           => 'stiff neck',
        'weakness'             => 'weakness',
        'numbness'             => 'numbness',
        'severe_fever'         => 'severe fever',
        'wheezing'             => 'wheezing',
        'altered_consciousness' => 'altered consciousness',
        'head_trauma'          => 'head trauma',
        'palpitations'         => 'palpitations',
        // Urgent / concerning indicators
        'fever'                => 'fever',
        'vomiting'             => 'vomiting',
        'severe_pain'          => 'severe pain',
        'pain'                 => 'pain',
        'injury'               => 'injury',
        'trauma'               => 'trauma',
        'abdominal_pain'       => 'abdominal pain',
        'headache'             => 'headache',
        'toothache'            => 'toothache',
        'tooth_pain'           => 'tooth pain',
        'gum_swelling'         => 'gum swelling',
        'dental_pain'          => 'dental pain',
        'swelling'             => 'swelling',
        'bleeding'             => 'bleeding',
        'dizziness'            => 'dizziness',
        'nausea'               => 'nausea',
        'laceration'           => 'laceration',
        'wound'                => 'wound',
        'fracture'             => 'fracture',
        'concussion'           => 'concussion',
        'anxiety'              => 'anxiety',
        'stress'               => 'stress',
        // Routine indicators
        'checkup'              => 'checkup',
        'routine'              => 'routine',
        'follow_up'            => 'follow-up',
        'consultation'         => 'consultation',
        'minor'                => 'minor',
        'preventive'           => 'preventive',
        'vaccination'          => 'vaccination',
        'prophylaxis'          => 'prophylaxis',
    ];

    private ?PersistentModel $loadedModel = null;

    private bool $modelLoadAttempted = false;

    public function __construct(private TriageRulesService $rules) {}

    public function modelAccuracy(): ?float
    {
        $modelPath = config('ml.triage.model_path');
        if (! is_string($modelPath) || $modelPath === '') {
            return null;
        }

        $metadataPath = $modelPath.'.meta.json';
        if (! is_file($metadataPath)) {
            return null;
        }

        $metadata = json_decode(
            file_get_contents($metadataPath),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $accuracy = $metadata['metrics']['accuracy'] ?? null;

        if (! is_numeric($accuracy) || ! is_finite((float) $accuracy) || $accuracy < 0 || $accuracy > 1) {
            return null;
        }

        return (float) $accuracy;
    }

    /**
     * Predict the triage priority for a consultation.
     *
     * Flow:
     *  1. Always run the deterministic emergency rules — they are the
     *     safety net and can never be downgraded by the model.
     *  2. If an emergency is flagged → return Priority 1 immediately.
     *  3. Attempt to load + run the Rubix ML model.
     *     - Shadow mode: rules are authoritative; the ML prediction is
     *       attached to input_features for later comparison.
     *     - Active mode: use the ML prediction when confidence ≥ threshold,
     *       otherwise fall back to rules.
     *  4. If no model is available or active, use rules.
     *
     * @param array{
     *     care_type?: string,
     *     complaint?: string,
     *     diagnosis?: string,
     *     symptoms?: string,
     *     severity?: int|null,
     *     vital_signs?: array<string, mixed>
     * } $features
     * @return array{
     *     predicted_priority: string,
     *     confidence_score: float|null,
     *     care_type: string,
     *     feature_version: string,
     *     model_version: string,
     *     mode: string,
     *     fallback_reason: string|null,
     *     input_features: array<string, mixed>,
     *     ml_prediction: array{priority: string, confidence: float}|null,
     * }
     */
    public function predict(array $features): array
    {
        $careType = $features['care_type'] ?? 'medical';
        if (! in_array($careType, ['medical', 'dental'], true)) {
            $careType = 'medical';
        }

        $normalizedFeatures = [
            'care_type' => $careType,
            'complaint' => trim((string) ($features['complaint'] ?? '')),
            'diagnosis' => trim((string) ($features['diagnosis'] ?? '')),
            'symptoms' => trim((string) ($features['symptoms'] ?? '')),
            'severity' => $features['severity'] ?? null,
            'vital_signs' => $features['vital_signs'] ?? [],
        ];

        $featureVersion = config('ml.triage.feature_version', 'triage-v1');
        $modelVersion = config('ml.triage.model_version', 'rules-v1');
        $mode = config('ml.triage.mode', 'shadow');
        $minConfidence = (float) config('ml.triage.minimum_confidence', 0.80);

        // ── Step 1: Emergency rules are the hard safety floor ──────────────
        $ruleResult = $this->rules->classify($normalizedFeatures);

        if ($ruleResult['priority'] === 'Priority 1' || (int) ($normalizedFeatures['severity'] ?? 0) >= 8) {
            $emergency = $ruleResult['priority'] === 'Priority 1';

            return [
                'predicted_priority' => $ruleResult['priority'],
                'confidence_score' => $emergency ? 1.0 : null,
                'care_type' => $careType,
                'feature_version' => $featureVersion,
                'model_version' => $modelVersion,
                'mode' => 'rules',
                'fallback_reason' => $emergency
                    ? 'Emergency rule triggered; ML bypass.'
                    : 'High reported severity; ML bypass.',
                'input_features' => $normalizedFeatures,
                'ml_prediction' => null,
            ];
        }

        // ── Step 2: Attempt an ML prediction (for shadow logging or,
        //             in active mode, for the actual triage decision) ────────
        $mlResult = $this->tryMlPrediction($normalizedFeatures);

        if ($mlResult !== null) {
            // We have both a rules prediction and an ML prediction.

            if ($mode === 'shadow') {
                // Shadow mode: rules are authoritative, ML is logged for
                // comparison against staff-reviewed outcomes.
                return [
                    'predicted_priority' => $ruleResult['priority'],
                    'confidence_score' => null,
                    'care_type' => $careType,
                    'feature_version' => $featureVersion,
                    'model_version' => $modelVersion,
                    'mode' => $mode,
                    'fallback_reason' => 'Shadow mode: rules-based triage active; ML prediction logged for comparison.',
                    'input_features' => $normalizedFeatures,
                    'ml_prediction' => $mlResult,
                ];
            }

            // Active mode: prefer the ML prediction when confidence is high enough.
            if ($mlResult['confidence'] >= $minConfidence) {
                return [
                    'predicted_priority' => $mlResult['priority'],
                    'confidence_score' => $mlResult['confidence'],
                    'care_type' => $careType,
                    'feature_version' => $featureVersion,
                    'model_version' => $modelVersion,
                    'mode' => $mode,
                    'fallback_reason' => null,
                    'input_features' => $normalizedFeatures,
                    'ml_prediction' => null, // ML was the authoritative result
                ];
            }

            // Low confidence — fall back to rules, but still log the ML result
            // so we can audit borderline cases.
            return [
                'predicted_priority' => $ruleResult['priority'],
                'confidence_score' => $mlResult['confidence'],
                'care_type' => $careType,
                'feature_version' => $featureVersion,
                'model_version' => $modelVersion,
                'mode' => $mode,
                'fallback_reason' => 'ML confidence ('.$mlResult['confidence'].') below threshold ('.$minConfidence.');',
                'input_features' => $normalizedFeatures,
                'ml_prediction' => $mlResult,
            ];
        }

        // ── Step 3: No model available — deterministic fallback ─────────────
        return [
            'predicted_priority' => $ruleResult['priority'],
            'confidence_score' => null,
            'care_type' => $careType,
            'feature_version' => $featureVersion,
            'model_version' => $modelVersion,
            'mode' => $mode,
            'fallback_reason' => 'No trained model available; deterministic triage rules applied.',
            'input_features' => $normalizedFeatures,
            'ml_prediction' => null,
        ];
    }

    /**
     * Attempt to run the Rubix ML model on the given features.
     *
     * Returns null when the model file is missing, unreadable, or the
     * prediction throws.
     *
     * @return array{priority: string, confidence: float}|null
     */
    private function tryMlPrediction(array $normalizedFeatures): ?array
    {
        $model = $this->loadModel();

        if ($model === null || ! $model->trained()) {
            return null;
        }

        try {
            $sample = $this->extractFeatures($normalizedFeatures);

            $dataset = new Unlabeled([array_values($sample)]);

            /** @var list<string> $predictions */
            $predictions = $model->predict($dataset);

            /** @var list<array<string, float>> $probabilities */
            $probabilities = $model->proba($dataset);

            $predictedLabel = $predictions[0];
            $confidence = max($probabilities[0]);

            return [
                'priority' => $this->normalizePriority($predictedLabel),
                'confidence' => $confidence,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Lazily load the trained model from the configured path.  The result
     * is cached for the lifetime of the request.
     */
    private function loadModel(): ?PersistentModel
    {
        if ($this->modelLoadAttempted) {
            return $this->loadedModel;
        }

        $this->modelLoadAttempted = true;

        $modelPath = config('ml.triage.model_path');

        if ($modelPath === null || ! file_exists($modelPath)) {
            return null;
        }

        try {
            $model = PersistentModel::load(new Filesystem($modelPath));

            if ($model->trained()) {
                $this->loadedModel = $model;
            }
        } catch (\Throwable $e) {
            $this->loadedModel = null;
        }

        return $this->loadedModel;
    }

    /**
     * Convert normalized clinical features into a numerical sample vector
     * suitable for the RandomForest classifier.
     *
     * Public so the training command can build a consistent feature schema.
     *
     * @param  array<string, mixed>  $normalizedFeatures
     * @return array<string, float>
     */
    public function extractFeatures(array $normalizedFeatures): array
    {
        $text = strtolower(implode(' ', array_filter([
            $normalizedFeatures['complaint'] ?? null,
            $normalizedFeatures['diagnosis'] ?? null,
            $normalizedFeatures['symptoms'] ?? null,
        ])));

        $vitals = $normalizedFeatures['vital_signs'] ?? [];

        $features = [];

        // Binary keyword indicators — keys are feature names, values are the
        // natural-language phrases searched in the lowercased clinical text.
        foreach (self::INDICATOR_KEYWORDS as $featureName => $phrase) {
            $features[$featureName] = str_contains($text, $phrase) ? 1.0 : 0.0;
        }

        // Vital-sign numerical features (clamped / null-safe)
        $features['bp_sys'] = $this->parseBpSystolic($vitals['bp'] ?? null);
        $features['temp'] = $this->parseNumeric($vitals['temp'] ?? null, 30.0, 45.0);
        $features['pulse'] = $this->parseNumeric($vitals['pulse'] ?? null, 30.0, 250.0);
        $features['rr'] = $this->parseNumeric($vitals['rr'] ?? null, 0.0, 60.0);
        $features['o2_sat'] = $this->parseNumeric($vitals['o2_sat'] ?? null, 0.0, 100.0);
        $features['weight_kg'] = $this->parseNumeric($vitals['weight_kg'] ?? null, 0.0, 500.0);

        // Severity ordinal: low=1, moderate=2, severe=3, unknown=0
        $features['severity'] = $this->parseSeverity($normalizedFeatures['severity'] ?? null);

        // Vital-sign threshold flags — explicit binary features that give the
        // RandomForest clear clinical decision boundaries.
        // These are derived from the continuous vitals already present, but
        // expressing them as binary flags dramatically helps tree-based learners.
        $temp   = $features['temp'];
        $pulse  = $features['pulse'];
        $o2     = $features['o2_sat'];
        $bpSys  = $features['bp_sys'];
        $rr     = $features['rr'];

        $features['flag_fever']        = ($temp  >  0.0 && $temp  >= 38.0) ? 1.0 : 0.0;  // ≥ 38.0 °C
        $features['flag_high_fever']   = ($temp  >  0.0 && $temp  >= 39.0) ? 1.0 : 0.0;  // ≥ 39.0 °C
        $features['flag_tachycardia']  = ($pulse >  0.0 && $pulse >= 100.0) ? 1.0 : 0.0; // ≥ 100 bpm
        $features['flag_tachycardia_mild'] = ($pulse > 0.0 && $pulse >= 90.0) ? 1.0 : 0.0; // ≥ 90 bpm
        $features['flag_bradycardia']  = ($pulse >  0.0 && $pulse <  60.0) ? 1.0 : 0.0;  // < 60 bpm
        $features['flag_hypoxia']      = ($o2    >  0.0 && $o2    <  95.0) ? 1.0 : 0.0;  // < 95 %
        $features['flag_hypoxia_mild'] = ($o2    >  0.0 && $o2    <  97.0) ? 1.0 : 0.0;  // < 97 %
        $features['flag_severe_hypoxia'] = ($o2  >  0.0 && $o2    <  90.0) ? 1.0 : 0.0;  // < 90 %
        $features['flag_hypertension'] = ($bpSys >  0.0 && $bpSys >= 140.0) ? 1.0 : 0.0; // ≥ 140 mmHg
        $features['flag_hypertension_mild'] = ($bpSys > 0.0 && $bpSys >= 130.0) ? 1.0 : 0.0; // ≥ 130 mmHg
        $features['flag_hypotension']  = ($bpSys >  0.0 && $bpSys <  90.0) ? 1.0 : 0.0;  // < 90 mmHg
        $features['flag_tachypnea']    = ($rr    >  0.0 && $rr    >= 20.0) ? 1.0 : 0.0;  // ≥ 20 br/min
        $features['flag_tachypnea_mild'] = ($rr  >  0.0 && $rr    >= 16.0) ? 1.0 : 0.0;  // ≥ 16 br/min
        $features['flag_vitals_absent'] = (
            $temp === 0.0 && $pulse === 0.0 && $o2 === 0.0 && $bpSys === 0.0
        ) ? 1.0 : 0.0;

        // Specialty indicator
        $features['is_dental'] = ($normalizedFeatures['care_type'] === 'dental') ? 1.0 : 0.0;

        ksort($features);

        return $features;
    }

    /**
     * Parse a blood-pressure string like "120/80" and return the systolic
     * value, or 0 when unavailable.
     */
    /**
     * Convert a text or numeric severity value into an ordinal float.
     *
     * Accepts:
     *  - String labels: "low" → 1.0, "moderate" → 2.0, "severe" → 3.0
     *  - Numeric strings/integers on a 1-10 scale: mapped proportionally to [1, 3]
     *  - Unknown / missing → 0.0
     */
    private function parseSeverity(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'low'      => 1.0,
            'mild'     => 1.0,
            'moderate' => 2.0,
            'medium'   => 2.0,
            'high'     => 3.0,
            'severe'   => 3.0,
            'critical' => 3.0,
            default    => is_numeric($normalized)
                ? max(0.0, min(3.0, round((float) $normalized / 10.0 * 3.0, 2)))
                : 0.0,
        };
    }

    private function parseBpSystolic(?string $bp): float
    {
        if ($bp === null || $bp === '') {
            return 0.0;
        }

        // Accept "120/80", "120", or a numeric value
        if (str_contains($bp, '/')) {
            $parts = explode('/', $bp);

            return $this->parseNumeric($parts[0] ?? null, 50.0, 300.0);
        }

        return $this->parseNumeric($bp, 50.0, 300.0);
    }

    private function parseNumeric($value, float $min, float $max): float
    {
        if (! is_numeric($value)) {
            return 0.0;
        }

        $val = (float) $value;

        return max($min, min($max, $val));
    }

    /**
     * Normalise the class label predicted by the model into the
     * "Priority N" format used throughout the application.
     */
    private function normalizePriority(string $label): string
    {
        if (preg_match('/priority\s*([123])/i', $label, $matches)) {
            return 'Priority '.$matches[1];
        }

        // Accept raw numeric labels like "1", "2", "3"
        if (in_array($label, ['1', '2', '3'], true)) {
            return 'Priority '.$label;
        }

        return $label;
    }
}
