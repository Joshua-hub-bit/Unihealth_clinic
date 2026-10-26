<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\MlTriageLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentTriageService
{
    public function __construct(private TriagePredictionService $triage) {}

    public function assess(Appointment $appointment): Appointment
    {
        $reason = trim((string) $appointment->reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => "Add the patient's symptoms or reason for the appointment before running triage.",
            ]);
        }

        $serviceType = trim((string) $appointment->service_type);
        $triageContext = trim($serviceType.' '.$reason);
        $careType = preg_match('/dental|tooth|oral/i', $triageContext) === 1 ? 'dental' : 'medical';
        $result = $this->triage->predict([
            'care_type' => $careType,
            'complaint' => $triageContext,
        ]);

        return DB::transaction(function () use ($appointment, $result): Appointment {
            $triageLog = MlTriageLog::query()->create([
                'student_id' => $appointment->student_id,
                'campus_id' => $appointment->campus_id,
                'input_features' => $result['input_features'],
                'predicted_priority' => $result['predicted_priority'],
                'confidence_score' => $result['confidence_score'],
                'model_version' => $result['model_version'],
                'metadata' => [
                    'source' => 'appointment',
                    'appointment_id' => $appointment->id,
                    'mode' => $result['mode'],
                    'fallback_reason' => $result['fallback_reason'],
                    'ml_prediction' => $result['ml_prediction'],
                ],
            ]);

            $appointment->update([
                'triage_priority' => $result['predicted_priority'],
                'triage_confidence' => $result['confidence_score'],
                'triage_details' => [
                    'care_type' => $result['care_type'],
                    'mode' => $result['mode'],
                    'fallback_reason' => $result['fallback_reason'],
                    'ml_prediction' => $result['ml_prediction'],
                ],
                'triage_log_id' => $triageLog->id,
            ]);

            return $appointment->refresh();
        });
    }
}
