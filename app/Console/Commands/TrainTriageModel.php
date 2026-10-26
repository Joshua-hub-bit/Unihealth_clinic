<?php

namespace App\Console\Commands;

use App\Models\MlTriageLog;
use App\Services\TriagePredictionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Rubix\ML\Classifiers\ClassificationTree;
use Rubix\ML\Classifiers\RandomForest;
use Rubix\ML\CrossValidation\Metrics\Accuracy;
use Rubix\ML\CrossValidation\Metrics\FBeta;
use Rubix\ML\CrossValidation\Reports\ConfusionMatrix;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;

class TrainTriageModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ml:train-triage-model
                            {--csv= : Train from a reviewed-case CSV export instead of database logs}
                            {--force : Save even when accuracy or F1 thresholds are unmet}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Train the Rubix ML triage classifier from staff-reviewed triage records or CSV';

    private const MIN_SAMPLES_PER_CLASS = 5;

    private const MIN_ACCURACY = 0.90;

    private const MIN_F1 = 0.60;

    private const EMERGENCY_RECALL_FLOOR = 0.95;

    /**
     * Execute the console command.
     */
    public function handle(TriagePredictionService $triage): int
    {
        $this->info('🧠 Training Triage ML Model');
        $this->line('─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─');

        $csvPath = $this->option('csv');
        $trainingSource = is_string($csvPath) && $csvPath !== ''
            ? basename($csvPath)
            : 'ml_triage_logs';

        try {
            $records = is_string($csvPath) && $csvPath !== ''
                ? $this->readCsvRecords($csvPath)
                : MlTriageLog::query()
                    ->where('reviewed_by_staff', true)
                    ->whereNotNull('final_priority')
                    ->where('final_priority', '!=', '')
                    ->orderBy('created_at')
                    ->get()
                    ->all();
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($records === []) {
            $this->warn('⚠ No reviewed triage records found. At least one staff review is required.');
            $this->line('   Run in shadow mode first, then review predictions via the triage review endpoint.');

            return self::FAILURE;
        }

        $this->info('📊 Loaded '.count($records).' reviewed records from '.$trainingSource);

        $samples = [];
        $labels = [];
        $sampleGroupKeys = [];
        $groupLatestIndex = [];
        $groupLabelCounts = [];
        $featureNames = [];
        $labelCounts = array_fill_keys(['Priority 1', 'Priority 2', 'Priority 3'], 0);
        $excludedRecords = 0;

        foreach ($records as $record) {
            $features = is_array($record) ? ($record['input_features'] ?? null) : $record->input_features;
            $priority = is_array($record) ? ($record['final_priority'] ?? null) : $record->final_priority;

            if (! is_array($features) || ! is_string($priority) || ! array_key_exists($priority, $labelCounts)) {
                $excludedRecords++;

                continue;
            }

            $normalizedFeatures = $this->normalizeTrainingFeatures($features);

            if ($normalizedFeatures === null) {
                $excludedRecords++;

                continue;
            }

            $sample = $triage->extractFeatures($normalizedFeatures);
            $featureNames = array_keys($sample);
            $sample = array_values($sample);
            $groupKey = hash('sha256', json_encode($sample, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR));
            $sampleIndex = count($samples);
            $samples[] = $sample;
            $labels[] = $priority;
            $sampleGroupKeys[] = $groupKey;
            $groupLatestIndex[$groupKey] = $sampleIndex;
            $groupLabelCounts[$groupKey][$priority] = ($groupLabelCounts[$groupKey][$priority] ?? 0) + 1;
            $labelCounts[$priority]++;
        }

        $totalSamples = count($samples);
        if ($excludedRecords > 0) {
            $this->warn('⚠ Excluded '.$excludedRecords.' records with unsupported inputs or priority labels.');
        }

        $this->line('   Class distribution:');
        foreach ($labelCounts as $priority => $count) {
            $this->line('     '.$priority.': '.$count);
        }

        $underrepresented = array_keys(array_filter(
            $labelCounts,
            fn (int $count): bool => $count < self::MIN_SAMPLES_PER_CLASS
        ));

        if ($underrepresented) {
            $this->warn('⚠ Insufficient samples after feature validation for: '.implode(', ', $underrepresented));
            $this->warn('   Training aborted: at least '.$this->minSamplesPerClass().' examples per class are required; --force cannot bypass this safety gate.');

            return self::FAILURE;
        }

        if ($totalSamples === 0) {
            $this->warn('⚠ No usable training samples were found.');

            return self::FAILURE;
        }

        $featureSchema = $this->buildSchemaKey($featureNames);
        $this->info('   Feature schema: '.$featureSchema.' ('.$totalSamples.' samples)');

        if ($totalSamples < 10) {
            $this->warn('⚠ Only '.$totalSamples.' training samples. Need at least 10.');

            if (! $this->option('force')) {
                $this->warn('   Training aborted. Use --force to override.');

                return self::FAILURE;
            }
        }

        // Keep identical model inputs together and reserve groups last seen most recently for testing.
        asort($groupLatestIndex, SORT_NUMERIC);
        $orderedGroups = array_keys($groupLatestIndex);
        $trainingGroupCount = (int) floor(count($orderedGroups) * 0.8);
        $testingGroupKeys = array_fill_keys(array_slice($orderedGroups, $trainingGroupCount), true);
        $trainingSamples = [];
        $trainingLabels = [];
        $testingSamples = [];
        $testingLabels = [];

        foreach ($samples as $index => $sample) {
            if (isset($testingGroupKeys[$sampleGroupKeys[$index]])) {
                $testingSamples[] = $sample;
                $testingLabels[] = $labels[$index];
            } else {
                $trainingSamples[] = $sample;
                $trainingLabels[] = $labels[$index];
            }
        }

        $conflictingGroups = count(array_filter(
            $groupLabelCounts,
            static fn (array $counts): bool => count($counts) > 1,
        ));
        $this->line('   Unique model-input groups: '.count($groupLatestIndex));
        $this->line('   Groups with conflicting labels: '.$conflictingGroups);

        foreach (array_keys($labelCounts) as $priority) {
            if (! in_array($priority, $trainingLabels, true) || ! in_array($priority, $testingLabels, true)) {
                $this->error('Training rejected: the chronological train and test sets must each contain '.$priority.'.');

                return self::FAILURE;
            }
        }

        $training = new Labeled($trainingSamples, $trainingLabels);
        $testing = new Labeled($testingSamples, $testingLabels);

        $trainCount = $training->numSamples();
        $testCount = $testing->numSamples();
        $this->info('   Grouped chronological split: '.$trainCount.' train / '.$testCount.' test');

        // ── 5. Train the RandomForest ───────────────────────────────────
        $this->info('🏋️ Training RandomForest (200 trees, balanced)');

        $estimator = new RandomForest(
            new ClassificationTree,
            200,
            0.2,
            true  // balanced — compensates for class imbalance
        );

        $estimator->train($training);

        $this->info('   Training complete.');

        // ── 6. Evaluate ─────────────────────────────────────────────────
        $predictions = $estimator->predict($testing);
        $trueLabels = $testing->labels();

        $accuracy = (new Accuracy)->score($predictions, $trueLabels);
        $f1 = (new FBeta(1.0))->score($predictions, $trueLabels);

        $confusionMatrix = (new ConfusionMatrix)->generate($predictions, $trueLabels);
        $matrix = $confusionMatrix->toArray();

        $this->line('');
        $this->info('📈 Evaluation Metrics');
        $this->line('─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─');
        $this->line('   Accuracy: '.number_format($accuracy, 4));
        $this->line('   F1 Score: '.number_format($f1, 4));
        $this->line('');

        // Print confusion matrix
        $classes = array_keys($matrix);
        $this->line('   Confusion Matrix (rows = predicted, cols = actual):');
        $header = array_merge(['Predicted \ Actual'], $classes);
        $rows = [];
        foreach ($classes as $pred) {
            $row = [$pred];
            foreach ($classes as $actual) {
                $row[] = $matrix[$pred][$actual] ?? 0;
            }
            $rows[] = $row;
        }
        $this->table($header, $rows);

        // ── 7. Safety check: emergency recall ──────────────────────────
        $emergencyRecall = $this->computeEmergencyRecall($predictions, $trueLabels);
        $this->line('   Emergency (Priority 1) Recall: '.
            ($emergencyRecall !== null ? number_format($emergencyRecall, 4) : 'N/A (no emergencies in test set)'));

        if ($emergencyRecall === null) {
            $this->error('Model rejected: the test split contains no Priority 1 cases, so emergency recall cannot be evaluated.');

            return self::FAILURE;
        }

        if ($emergencyRecall < self::EMERGENCY_RECALL_FLOOR) {
            $this->error('Model rejected: emergency recall '.number_format($emergencyRecall, 4).' is below the required '.self::EMERGENCY_RECALL_FLOOR.'. --force cannot bypass this safety gate.');

            return self::FAILURE;
        }

        // ── 8. Decide whether to activate the model ─────────────────────
        $passesAccuracy = $accuracy >= self::MIN_ACCURACY;
        $passesF1 = $f1 >= self::MIN_F1;
        $passesEmergency = $emergencyRecall >= self::EMERGENCY_RECALL_FLOOR;

        $allMetricsPass = $passesAccuracy && $passesF1 && $passesEmergency;

        if (! $passesAccuracy) {
            $this->error('Model rejected: accuracy '.number_format($accuracy, 4).' is below the required '.self::MIN_ACCURACY.'. --force cannot bypass this requirement.');

            return self::FAILURE;
        }

        if (! $allMetricsPass && ! $this->option('force')) {
            $this->warn('');
            $this->warn('❌ Model rejected — safety metrics not met:');
            if (! $passesAccuracy) {
                $this->warn('   Accuracy '.number_format($accuracy, 2).' < '.self::MIN_ACCURACY);
            }
            if (! $passesF1) {
                $this->warn('   F1 Score '.number_format($f1, 2).' < '.self::MIN_F1);
            }
            if (! $passesEmergency) {
                $this->warn('   Emergency Recall '.number_format($emergencyRecall ?? 0, 2).' < '.self::EMERGENCY_RECALL_FLOOR);
            }

            return self::FAILURE;
        }

        // ── 9. Save the model ───────────────────────────────────────────
        $modelVersion = $this->generateVersion();
        $modelPath = config('ml.triage.model_path', storage_path('app/models/triage.model'));
        $modelDir = dirname($modelPath);

        if (! File::isDirectory($modelDir)) {
            File::makeDirectory($modelDir, 0755, true);
            $this->info('📁 Created model directory: '.$modelDir);
        }

        // Back up the existing model if present
        if (file_exists($modelPath)) {
            $backupPath = $modelPath.'.bak_'.now()->format('Ymd_His');
            File::copy($modelPath, $backupPath);
            $this->info('   Backed up previous model to: '.basename($backupPath));
        }

        $persistedModel = new PersistentModel($estimator, new Filesystem($modelPath));
        $persistedModel->save();

        $this->info('💾 Model saved to: '.$modelPath);

        // ── 10. Write metadata ──────────────────────────────────────────
        $metadata = [
            'model_version' => $modelVersion,
            'training_date' => now()->toIso8601String(),
            'dataset_size' => $totalSamples,
            'train_size' => $trainCount,
            'test_size' => $testCount,
            'unique_feature_groups' => count($groupLatestIndex),
            'conflicting_feature_groups' => $conflictingGroups,
            'train_feature_groups' => $trainingGroupCount,
            'test_feature_groups' => count($testingGroupKeys),
            'training_source' => $trainingSource,
            'split_strategy' => 'grouped_chronological_model_inputs_80_20',
            'feature_schema' => $featureSchema,
            'feature_schema_version' => config('ml.triage.feature_version', 'triage-v1'),
            'metrics' => [
                'accuracy' => $accuracy,
                'f1_score' => $f1,
                'emergency_recall' => $emergencyRecall,
            ],
            'class_distribution' => $labelCounts,
            'threshold_accuracy' => self::MIN_ACCURACY,
            'threshold_f1' => self::MIN_F1,
            'threshold_emergency_recall' => self::EMERGENCY_RECALL_FLOOR,
            'passed' => $allMetricsPass,
        ];

        $metadataPath = $modelPath.'.meta.json';
        File::put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('📋 Metadata saved to: '.basename($metadataPath));

        $this->warn('');
        if ($allMetricsPass) {
            $this->info('✅ Model trained and saved.');
        } else {
            $this->warn('⚠️ Model saved but metrics are below threshold.');
            $this->warn('   Keep ML_TRIAGE_MODE=shadow until performance improves.');
        }

        $this->line('');
        $this->info('   To activate the model, set:');
        $this->line('   ML_TRIAGE_MODEL_VERSION='.$modelVersion);
        $this->line('   ML_TRIAGE_MODE=active');

        return self::SUCCESS;
    }

    /**
     * @return list<array{created_at: string, input_features: array<string, mixed>, final_priority: string}>
     */
    private function readCsvRecords(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new \RuntimeException('CSV file is missing or unreadable.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('CSV file could not be opened.');
        }

        try {
            $headers = fgetcsv($handle, null, ',', '"', '');
            if (! is_array($headers)) {
                throw new \RuntimeException('CSV file is empty or has no header row.');
            }

            $headers = array_map(
                static fn ($header): string => trim(ltrim((string) $header, "\xEF\xBB\xBF")),
                $headers
            );

            if (count(array_unique($headers)) !== count($headers)) {
                throw new \RuntimeException('CSV header names must be unique.');
            }

            $cleanedHeaders = [
                'Visit_ID',
                'Visit_Timestamp',
                'Care_Type',
                'Complaint',
                'Diagnosis',
                'Symptoms',
                'Structured_Temperature_C',
                'Structured_BP',
                'Pulse_BPM',
                'Respiratory_Rate',
                'Oxygen_Saturation_Pct',
                'Priority_Level',
                'Clinician_Approved',
            ];
            if (array_diff($cleanedHeaders, $headers) === []) {
                return $this->readCleanedConsultationsCsv($handle, $headers);
            }

            $requiredHeaders = ['Clinic Visit ID', 'Created At', 'Input Features', 'Final Priority', 'Reviewed By Staff'];
            $missingHeaders = array_diff($requiredHeaders, $headers);
            if ($missingHeaders !== []) {
                throw new \RuntimeException('CSV is missing required headers: '.implode(', ', $missingHeaders).'.');
            }

            $records = [];
            $visitIds = [];
            $line = 1;
            while (($values = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $line++;

                if ($values === [null] || $values === []) {
                    continue;
                }

                if (count($values) !== count($headers)) {
                    throw new \RuntimeException('CSV row '.$line.' has a different number of fields than the header.');
                }

                $row = array_combine($headers, $values);
                $visitId = trim((string) $row['Clinic Visit ID']);
                if ($visitId === '') {
                    throw new \RuntimeException('CSV row '.$line.' is missing a clinic visit ID.');
                }

                if (isset($visitIds[$visitId])) {
                    throw new \RuntimeException('CSV contains duplicate clinic visit IDs; combine neither workbook versions nor repeated visits.');
                }
                $visitIds[$visitId] = true;

                try {
                    $createdAt = new \DateTimeImmutable(trim((string) $row['Created At']));
                } catch (\Exception) {
                    throw new \RuntimeException('CSV row '.$line.' has an invalid Created At date.');
                }

                $reviewed = filter_var(
                    trim((string) $row['Reviewed By Staff']),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );
                if ($reviewed !== true) {
                    throw new \RuntimeException('CSV row '.$line.' is not marked as staff-reviewed.');
                }

                $priority = trim((string) $row['Final Priority']);
                if (! in_array($priority, ['Priority 1', 'Priority 2', 'Priority 3'], true)) {
                    throw new \RuntimeException('CSV row '.$line.' has an invalid Final Priority label.');
                }

                try {
                    $features = json_decode((string) $row['Input Features'], true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    throw new \RuntimeException('CSV row '.$line.' contains invalid Input Features JSON.');
                }

                if (! is_array($features)) {
                    throw new \RuntimeException('CSV row '.$line.' Input Features must be a JSON object.');
                }

                $records[] = [
                    'created_at' => $createdAt->format('Y-m-d H:i:s.u'),
                    'input_features' => $features,
                    'final_priority' => $priority,
                ];
            }
        } finally {
            fclose($handle);
        }

        if ($records === []) {
            throw new \RuntimeException('CSV contains no reviewed cases.');
        }

        usort($records, static fn (array $left, array $right): int => strcmp($left['created_at'], $right['created_at']));

        return $records;
    }

    /**
     * @param resource $handle
     * @param list<string> $headers
     * @return list<array{created_at: string, input_features: array<string, mixed>, final_priority: string}>
     */
    private function readCleanedConsultationsCsv($handle, array $headers): array
    {
        $records = [];
        $visitIds = [];
        $line = 1;
        while (($values = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $line++;

            if ($values === [null] || $values === []) {
                continue;
            }

            if (count($values) !== count($headers)) {
                throw new \RuntimeException('CSV row '.$line.' has a different number of fields than the header.');
            }

            $row = array_combine($headers, $values);
            $visitId = trim((string) $row['Visit_ID']);
            if ($visitId === '' || isset($visitIds[$visitId])) {
                throw new \RuntimeException('CSV row '.$line.' has a missing or duplicate visit ID.');
            }
            $visitIds[$visitId] = true;

            try {
                $createdAt = new \DateTimeImmutable(trim((string) $row['Visit_Timestamp']));
            } catch (\Exception) {
                throw new \RuntimeException('CSV row '.$line.' has an invalid Visit_Timestamp.');
            }

            $approved = filter_var(
                trim((string) $row['Clinician_Approved']),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE,
            );
            if ($approved !== true) {
                throw new \RuntimeException('CSV row '.$line.' is not marked clinician-approved.');
            }

            $priority = trim((string) $row['Priority_Level']);
            if (! in_array($priority, ['Priority 1', 'Priority 2', 'Priority 3'], true)) {
                throw new \RuntimeException('CSV row '.$line.' has an unsupported Priority_Level.');
            }

            $careType = strtolower(trim((string) $row['Care_Type']));
            if (! in_array($careType, ['medical', 'dental'], true)) {
                throw new \RuntimeException('CSV row '.$line.' has an unsupported Care_Type.');
            }

            $records[] = [
                'created_at' => $createdAt->format('Y-m-d H:i:s.u'),
                'input_features' => [
                    'care_type' => $careType,
                    'complaint' => trim((string) $row['Complaint']),
                    'diagnosis' => trim((string) $row['Diagnosis']),
                    'symptoms' => trim((string) $row['Symptoms']),
                    'severity' => trim((string) ($row['Severity'] ?? '')),
                    'vital_signs' => [
                        'temp' => trim((string) $row['Structured_Temperature_C']),
                        'bp' => trim((string) $row['Structured_BP']),
                        'pulse' => trim((string) $row['Pulse_BPM']),
                        'rr' => trim((string) $row['Respiratory_Rate']),
                        'o2_sat' => trim((string) $row['Oxygen_Saturation_Pct']),
                    ],
                ],
                'final_priority' => $priority,
            ];
        }

        if ($records === []) {
            throw new \RuntimeException('CSV contains no clinician-approved consultations.');
        }

        usort($records, static fn (array $left, array $right): int => strcmp($left['created_at'], $right['created_at']));

        return $records;
    }

    /**
     * @param  array<string, mixed>  $features
     * @return array<string, mixed>|null
     */
    private function normalizeTrainingFeatures(array $features): ?array
    {
        $careType = $features['care_type'] ?? 'medical';
        if (! in_array($careType, ['medical', 'dental'], true)) {
            return null;
        }

        $vitalSigns = $features['vital_signs'] ?? [];
        if (! is_array($vitalSigns)) {
            return null;
        }

        $complaint = $this->normalizeTextValue($features['complaint'] ?? $features['chief_complaint'] ?? null);
        $diagnosis = $this->normalizeTextValue($features['diagnosis'] ?? null);
        $symptoms = $this->normalizeTextValue($features['symptoms'] ?? null);
        $vitalSigns['bp'] = $vitalSigns['bp'] ?? $features['bp'] ?? null;
        $vitalSigns['temp'] = $vitalSigns['temp'] ?? $features['temp'] ?? null;
        $vitalSigns['pulse'] = $vitalSigns['pulse'] ?? $features['pulse'] ?? $features['heart_rate'] ?? null;

        $hasVitalSigns = array_filter(
            $vitalSigns,
            static fn ($value): bool => $value !== null && $value !== ''
        ) !== [];

        if ($complaint === '' && $diagnosis === '' && $symptoms === '' && ! $hasVitalSigns) {
            return null;
        }

        return [
            'care_type' => $careType,
            'complaint' => $complaint,
            'diagnosis' => $diagnosis,
            'symptoms' => $symptoms,
            'severity' => $features['severity'] ?? null,
            'vital_signs' => $vitalSigns,
        ];
    }

    private function normalizeTextValue(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * Generate a semantic version string for the model artifact.
     */
    private function generateVersion(): string
    {
        $existingVersion = config('ml.triage.model_version', 'rules-v1');
        $base = 'triage-rf-';

        // If the current version is already a model version, increment it
        if (preg_match('/triage-rf-(\d+)/', $existingVersion, $matches)) {
            return $base.((int) $matches[1] + 1);
        }

        return $base.'1';
    }

    /**
     * @param  list<string>  $predictions
     * @param  list<string>  $labels
     * @return float|null Returns null when no emergencies exist in the test set
     */
    private function computeEmergencyRecall(array $predictions, array $labels): ?float
    {
        $emergencyCount = 0;
        $emergencyCorrect = 0;

        foreach ($labels as $i => $label) {
            if ($label === 'Priority 1') {
                $emergencyCount++;
                if ($predictions[$i] === 'Priority 1') {
                    $emergencyCorrect++;
                }
            }
        }

        if ($emergencyCount === 0) {
            return null;
        }

        return $emergencyCorrect / $emergencyCount;
    }

    private function buildSchemaKey(array $sample): string
    {
        return 'schema-'.implode(',', $sample);
    }

    private function minSamplesPerClass(): int
    {
        return self::MIN_SAMPLES_PER_CLASS;
    }
}
