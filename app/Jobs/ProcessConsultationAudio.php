<?php

namespace App\Jobs;

use App\Models\ClinicNotification;
use App\Models\ConsultationRequest;
use App\Models\MedicalTranscription;
use App\Models\MlTriageLog;
use App\Services\TriagePredictionService;
use App\Services\WhisperTranscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessConsultationAudio implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 360;

    public function __construct(public int $consultationRequestId) {}

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(WhisperTranscriptionService $whisper, TriagePredictionService $triage): void
    {
        $consultation = ConsultationRequest::query()->find($this->consultationRequestId);
        if (! $consultation) {
            return;
        }

        if (! in_array($consultation->status, ['submitted', 'audio_processing'], true)) {
            return;
        }

        $recording = $consultation->recording;
        $audioData = $recording?->official_audio_data ?? $recording?->student_voice_data;
        $audioPath = $consultation->official_audio_path ?? $consultation->student_voice_path;
        if (! is_string($audioData) && ! $audioPath) {
            throw new \RuntimeException('The consultation recording is missing.');
        }

        $transcription = $consultation->medicalTranscription;
        if (! $transcription) {
            throw new \RuntimeException('The consultation transcription record is missing.');
        }
        if ($transcription->status !== 'processing') {
            return;
        }

        $processingStartedAt = now();
        $transcription->update([
            'status' => 'processing',
            'processing_duration_seconds' => null,
            'processing_started_at' => $processingStartedAt,
        ]);
        $processingStarted = microtime(true);
        $result = is_string($audioData)
            ? $whisper->transcribeContents(
                $audioData,
                $recording?->official_audio_data !== null
                    ? ($recording->official_audio_filename ?: 'official-recording')
                    : ($recording?->student_voice_filename ?: 'student-recording'),
                $recording?->official_audio_data !== null
                    ? ($recording->official_audio_mime ?: 'application/octet-stream')
                    : ($recording?->student_voice_mime ?: 'application/octet-stream'),
            )
            : $whisper->transcribe($audioPath);
        $transcript = $result['text'];
        $audioDurationSeconds = $transcription->audio_duration_seconds
            ?? (is_numeric($result['metadata']['duration'] ?? null)
                ? (int) ceil((float) $result['metadata']['duration'])
                : null);
        $processingDurationSeconds = max(1, (int) ceil(microtime(true) - $processingStarted));
        if (trim($transcript) === '') {
            throw new \RuntimeException('The transcription service returned an empty transcript.');
        }

        $triageResult = $consultation->concern === 'Audio recording submitted without written concern.'
            ? $triage->predict([
                'care_type' => $consultation->care_type,
                'complaint' => $transcript,
            ])
            : null;

        DB::transaction(function () use ($consultation, $transcription, $transcript, $audioDurationSeconds, $triageResult, $processingDurationSeconds): void {
            $transcription->update([
                'raw_transcript' => $transcript,
                'ai_draft' => $transcript,
                'status' => 'nurse_review',
                'audio_duration_seconds' => $audioDurationSeconds,
                'processing_duration_seconds' => $processingDurationSeconds,
            ]);

            if ($triageResult) {
                $triageLog = MlTriageLog::create([
                    'student_id' => $consultation->student_id,
                    'campus_id' => $consultation->campus_id,
                    'input_features' => $triageResult['input_features'],
                    'predicted_priority' => $triageResult['predicted_priority'],
                    'confidence_score' => $triageResult['confidence_score'],
                    'model_version' => $triageResult['model_version'],
                    'metadata' => [
                        'consultation_triage' => true,
                        'mode' => $triageResult['mode'],
                        'fallback_reason' => $triageResult['fallback_reason'],
                        'ml_prediction' => $triageResult['ml_prediction'],
                        'source' => 'verified_transcript_pending_nurse_review',
                    ],
                ]);

                $consultation->update([
                    'triage_priority' => $triageResult['predicted_priority'],
                    'triage_confidence' => $triageResult['confidence_score'],
                    'triage_details' => [
                        'mode' => $triageResult['mode'],
                        'fallback_reason' => $triageResult['fallback_reason'],
                    ],
                    'triage_log_id' => $triageLog->id,
                ]);
            }

            ConsultationRequest::query()
                ->whereKey($consultation->id)
                ->where('status', 'audio_processing')
                ->update(['status' => 'transcript_ready']);
        });

        GenerateConsultationSummary::dispatchFor($consultation->load('medicalTranscription'));

        if ($consultation->nurse_id) {
            ClinicNotification::notifyStaff(
                $consultation->nurse_id,
                'transcription',
                'Consultation transcript ready for review',
                'The Whisper transcript is ready for nurse correction.',
                ['consultation_id' => $consultation->id],
            );
        }
    }

    public function failed(Throwable $exception): void
    {
        ConsultationRequest::query()
            ->whereKey($this->consultationRequestId)
            ->where('status', 'audio_processing')
            ->update(['status' => 'transcription_failed']);

        MedicalTranscription::query()
            ->where('consultation_request_id', $this->consultationRequestId)
            ->where('status', 'processing')
            ->update(['status' => 'failed']);

        Log::error('Consultation transcription failed.', [
            'consultation_request_id' => $this->consultationRequestId,
            'message' => $exception->getMessage(),
        ]);
    }
}
