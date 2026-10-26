<?php

namespace App\Services;

use App\Services\ClinicAiModelCatalog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class WhisperTranscriptionService
{
    /**
     * @return array{text: string, metadata: array<string, mixed>}
     */
    public function transcribe(string $audioPath): array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($audioPath)) {
            throw new RuntimeException('The private consultation audio file is missing.');
        }

        $inputPath = $disk->path($audioPath);
        $audio = file_get_contents($inputPath);
        if ($audio === false) {
            throw new RuntimeException('Unable to read consultation audio for transcription.');
        }

        return $this->transcribeContents(
            $audio,
            basename($inputPath),
            mime_content_type($inputPath) ?: 'application/octet-stream',
        );
    }

    /**
     * @return array{text: string, metadata: array<string, mixed>}
     */
    public function transcribeContents(string $audio, string $filename, string $contentType): array
    {
        $apiKey = config('services.groq.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('Transcription is not configured. Ask the clinic administrator to add GROQ_API_KEY.');
        }

        $model = $this->selectedModel();

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout((int) config('services.groq.timeout', 180))
                ->attach(
                    'file',
                    $audio,
                    basename($filename),
                    ['Content-Type' => $contentType],
                )
                ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                    'model' => $model,
                    'response_format' => 'verbose_json',
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Unable to connect to the transcription service. Check the network and retry.',
                previous: $exception,
            );
        }

        if (! $response->successful()) {
            throw match (true) {
                in_array($response->status(), [401, 403], true) => new RuntimeException(
                    'The transcription service credentials were rejected. Ask the clinic administrator to verify GROQ_API_KEY.',
                ),
                $response->status() === 429 => new RuntimeException(
                    'The transcription service is busy. Please wait a moment and retry.',
                ),
                $response->status() >= 500 => new RuntimeException(
                    'The transcription service is temporarily unavailable. Please retry later.',
                ),
                default => new RuntimeException(
                    'The transcription service could not process this audio. Check the file and retry.',
                ),
            };
        }

        $payload = $response->json();
        $transcript = is_array($payload) ? ($payload['text'] ?? null) : null;
        if (! is_string($transcript) || trim($transcript) === '') {
            throw new RuntimeException('The transcription service returned an unexpected response. Please retry.');
        }

        return [
            'text' => trim($transcript),
            'metadata' => array_intersect_key($payload, array_flip([
                'language',
                'duration',
                'segments',
                'task',
            ])),
        ];
    }

    private function selectedModel(): string
    {
        return ClinicAiModelCatalog::selected('transcription');
    }
}
