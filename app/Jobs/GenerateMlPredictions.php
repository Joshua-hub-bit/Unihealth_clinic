<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\Campus;
use App\Models\ClinicVisit;
use App\Models\MlAnomalyLog;
use App\Models\MlTrafficPrediction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMlPredictions implements ShouldQueue
{
    use Queueable;

    /**
     * The date to generate predictions for.  Defaults to today.
     */
    public ?string $date;

    /**
     * @param  string|null  $date  Y-m-d format; null = today
     */
    public function __construct(?string $date = null)
    {
        $this->date = $date;
    }

    /**
     * Execute the job.
     *
     * Generates daily traffic predictions for every active campus using a
     * historical-average baseline, and logs anomalies when observed
     * traffic deviates significantly from the prediction.
     *
     * Idempotent: uses updateOrCreate so re-running on the same date
     * will never produce duplicate predictions.
     */
    public function handle(): void
    {
        $date = Carbon::parse($this->date ?? today());
        $predictionDate = $date->toDateString();

        $campuses = Campus::where('is_active', true)->get();

        if ($campuses->isEmpty()) {
            Log::info('GenerateMlPredictions: no campuses found; skipping.');

            return;
        }

        foreach ($campuses as $campus) {
            $this->generateTrafficPrediction((int) $campus->id, $predictionDate);
        }

        // Anomaly detection: compare predicted vs actual for dates that have
        // already passed (i.e. we have real data to compare against).
        if ($date->isPast() || $date->isToday()) {
            $this->checkAnomalies($predictionDate);
        }
    }

    /**
     * Compute a simple historical-average prediction for a single campus
     * and store it idempotently.
     */
    private function generateTrafficPrediction(int $campusId, string $dateStr): void
    {
        // Historical average: visits in the 30 days before the prediction date
        $thirtyDaysAgo = Carbon::parse($dateStr)->subDays(30)->startOfDay();
        $predictionDate = Carbon::parse($dateStr)->startOfDay();

        $historicalVisits = ClinicVisit::query()
            ->where('campus_id', $campusId)
            ->where('visit_date', '>=', $thirtyDaysAgo)
            ->where('visit_date', '<', $predictionDate)
            ->count();

        $avgDaily = (int) round($historicalVisits / 30);

        // Also factor in confirmed appointments for the target date as a
        // minimum floor — if we already know N patients are scheduled,
        // we should predict at least N.
        $confirmedAppointments = Appointment::query()
            ->where('campus_id', $campusId)
            ->whereDate('appt_date', $dateStr)
            ->whereIn('status', ['confirmed', 'pending'])
            ->count();

        $predictedCount = max($avgDaily, $confirmedAppointments);

        // Idempotent insert / update — use DB::table to avoid model date-cast
        // format mismatches with unique constraints on SQLite.
        DB::table('ml_traffic_predictions')->updateOrInsert(
            ['campus_id' => $campusId, 'prediction_date' => $dateStr],
            [
                'predicted_count' => $predictedCount,
                'actual_count' => null,
                'mae' => null,
                'input_features' => json_encode([
                    'method' => 'historical_average',
                    'historical_window_days' => 30,
                    'historical_visits' => $historicalVisits,
                    'confirmed_appointments' => $confirmedAppointments,
                    'generated_at' => now()->toIso8601String(),
                ]),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Check if the actual traffic for a past date deviates significantly
     * from the prediction, and log anomalies.
     */
    private function checkAnomalies(string $dateStr): void
    {
        $predictions = MlTrafficPrediction::whereDate('prediction_date', $dateStr)
            ->get();

        foreach ($predictions as $prediction) {
            // Only check dates that have fully passed
            $targetDate = Carbon::parse($dateStr);
            if ($targetDate->isFuture()) {
                continue;
            }

            $actualCount = ClinicVisit::query()
                ->where('campus_id', $prediction->campus_id)
                ->whereDate('visit_date', $dateStr)
                ->count();

            $prediction->actual_count = $actualCount;

            $mae = null;
            $anomalyScore = null;
            $isAnomaly = false;
            $description = null;

            if ($actualCount >= 0 && $prediction->predicted_count > 0) {
                $mae = abs($actualCount - $prediction->predicted_count);
                $anomalyScore = $mae / $prediction->predicted_count;

                // Flag as anomaly if deviation exceeds 30 %
                if ($anomalyScore > 0.30) {
                    $isAnomaly = true;
                    $direction = $actualCount > $prediction->predicted_count
                        ? 'higher than'
                        : 'lower than';
                    $description = \sprintf(
                        'Visit volume %s prediction: %d actual vs %d predicted (%s).',
                        $direction,
                        $actualCount,
                        $prediction->predicted_count,
                        $targetDate->toDateString()
                    );
                }
            }

            $prediction->mae = $mae;
            $prediction->save();

            if ($isAnomaly) {
                MlAnomalyLog::create([
                    'campus_id' => $prediction->campus_id,
                    'log_date' => $dateStr,
                    'input_features' => [
                        'prediction_id' => $prediction->id,
                        'predicted_count' => $prediction->predicted_count,
                        'actual_count' => $actualCount,
                    ],
                    'anomaly_score' => $anomalyScore,
                    'is_anomaly' => true,
                    'description' => $description,
                    'reviewed' => false,
                ]);
            }
        }
    }
}
