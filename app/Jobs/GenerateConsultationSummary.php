<?php

namespace App\Jobs;

use App\Models\ConsultationRequest;
use App\Services\ClinicAiAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateConsultationSummary implements ShouldQueue
{
    use Queueable;

    private const SUMMARY_VERSION = 2;

    public int $tries = 2;

    public int $timeout = 90;

    public function __construct(
        public int $consultationRequestId,
        public string $sourceHash,
    ) {}

    public function backoff(): array
    {
        return [60];
    }

    public static function dispatchFor(ConsultationRequest $consultation): void
    {
        if (filled($consultation->nurse_summary) || $consultation->nurse_reviewed_at !== null) {
            return;
        }

        $source = self::sourceFor($consultation);
        if (! filled($source['written_concern']) && ! filled($source['audio_transcript'])) {
            return;
        }

        $sourceHash = self::hashSource($source);
        if (! Cache::add(self::cacheKey($consultation->id, $sourceHash), [
            'status' => 'pending',
            'summary' => null,
        ], now()->addDay())) {
            return;
        }

        self::dispatch($consultation->id, $sourceHash)
            ->onQueue(config('queue.transcription_queue'))
            ->afterCommit();
    }

    /**
     * @return array{status:string, summary:?string}
     */
    public static function stateFor(ConsultationRequest $consultation): array
    {
        if (filled($consultation->nurse_summary) || $consultation->nurse_reviewed_at !== null) {
            return ['status' => 'not_needed', 'summary' => null];
        }

        $source = self::sourceFor($consultation);
        if (! filled($source['written_concern']) && ! filled($source['audio_transcript'])) {
            return [
                'status' => $consultation->medicalTranscription?->status === 'processing'
                    ? 'waiting'
                    : 'unavailable',
                'summary' => null,
            ];
        }

        $state = Cache::get(self::cacheKey($consultation->id, self::hashSource($source)));
        if (! is_array($state) || ! in_array($state['status'] ?? null, ['pending', 'ready', 'unavailable'], true)) {
            return ['status' => 'pending', 'summary' => null];
        }

        return [
            'status' => $state['status'],
            'summary' => is_string($state['summary'] ?? null) ? $state['summary'] : null,
        ];
    }

    /**
     * @return array{written_concern:?string, audio_transcript:?string, symptom_onset:?string, severity:?int, care_type:?string}
     */
    public static function sourceFor(ConsultationRequest $consultation): array
    {
        $concern = trim((string) $consultation->concern);
        if ($concern === 'Audio recording submitted without written concern.') {
            $concern = '';
        }

        $transcription = $consultation->medicalTranscription;
        $transcript = trim((string) $transcription?->ai_draft);
        if ($transcript === '') {
            $transcript = trim((string) $transcription?->raw_transcript);
        }
        if ($transcript === $concern) {
            $transcript = '';
        }

        $onset = trim((string) $consultation->symptom_onset);

        return [
            'written_concern' => $concern !== '' ? $concern : null,
            'audio_transcript' => $transcript !== '' ? $transcript : null,
            'symptom_onset' => $onset !== '' ? $onset : null,
            'severity' => $consultation->severity !== null ? (int) $consultation->severity : null,
            'care_type' => $consultation->care_type,
        ];
    }

    /**
     * @param  array{written_concern:?string, audio_transcript:?string, symptom_onset:?string, severity:?int, care_type:?string}  $source
     */
    public static function hashSource(array $source): string
    {
        return hash('sha256', json_encode([
            'version' => self::SUMMARY_VERSION,
            'source' => $source,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public function handle(ClinicAiAnalysisService $analysis): void
    {
        $consultation = ConsultationRequest::query()
            ->with('medicalTranscription')
            ->find($this->consultationRequestId);
        if (! $consultation) {
            return;
        }

        $source = self::sourceFor($consultation);
        if (self::hashSource($source) !== $this->sourceHash
            || filled($consultation->nurse_summary)
            || $consultation->nurse_reviewed_at !== null
        ) {
            return;
        }

        if (! $analysis->isConfigured()) {
            Cache::put(self::cacheKey($consultation->id, $this->sourceHash), [
                'status' => 'unavailable',
                'summary' => null,
            ], now()->addMinutes(15));

            Log::warning('AI consultation summary is unavailable because the selected provider is not configured.', [
                'consultation_request_id' => $consultation->id,
            ]);

            return;
        }

        $summary = $analysis->generateConsultationSummary($source);
        Cache::put(self::cacheKey($consultation->id, $this->sourceHash), [
            'status' => 'ready',
            'summary' => $summary,
        ], now()->addDays(30));
    }

    public function failed(Throwable $exception): void
    {
        Cache::put(self::cacheKey($this->consultationRequestId, $this->sourceHash), [
            'status' => 'unavailable',
            'summary' => null,
        ], now()->addMinutes(15));

        Log::warning('AI consultation summary generation failed.', [
            'consultation_request_id' => $this->consultationRequestId,
            'exception_class' => $exception::class,
        ]);
    }

    private static function cacheKey(int $consultationRequestId, string $sourceHash): string
    {
        return 'consultation-ai-summary:'.$consultationRequestId.':'.$sourceHash;
    }
}
