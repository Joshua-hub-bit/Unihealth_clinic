<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateConsultationSummary;
use App\Jobs\ProcessConsultationAudio;
use App\Models\Appointment;
use App\Models\Campus;
use App\Models\ClinicAvailability;
use App\Models\ClinicNotification;
use App\Models\ConsultationRecording;
use App\Models\ClinicVisit;
use App\Models\ConsultationRequest;
use App\Models\HealthRecord;
use App\Models\MedicalTranscription;
use App\Models\MlTriageLog;
use App\Models\Referral;
use App\Models\Staff;
use App\Models\Student;
use App\Services\AuditService;
use App\Services\TriagePredictionService;
use App\Services\WhisperTranscriptionService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

class ConsultationController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private TriagePredictionService $triage,
        private WhisperTranscriptionService $whisper,
    ) {}

    public function studentIndex(Request $request): JsonResponse
    {
        $identity = $this->identity($request);

        $consultations = ConsultationRequest::query()
            ->with('appointment')
            ->where('student_id', $identity['id'])
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'consultations' => $consultations->map(fn (ConsultationRequest $consultation): array => $this->studentPayload($consultation)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $identity = $this->identity($request);
        $student = Student::query()->findOrFail($identity['id']);
        $data = $request->validate([
            'concern' => ['required', 'string', 'max:5000', 'regex:/\S/'],
            'symptom_onset' => ['nullable', 'string', 'max:120'],
            'severity' => ['nullable', 'integer', 'between:1,10'],
            'care_type' => ['sometimes', 'in:medical,dental'],
            'voice_description' => [
                'nullable',
                'file',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile || ! $value->isValid()) {
                        return;
                    }

                    $supportedExtensions = ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm', 'ogg', 'flac'];
                    if (
                        ! in_array($value->guessExtension(), $supportedExtensions, true)
                        && $value->getMimeType() !== 'audio/webm'
                    ) {
                        $fail('The voice description must be a supported audio file.');
                    }
                },
                'max:20480',
            ],
            'voice_duration_seconds' => ['nullable', 'integer', 'between:1,7200'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'concern.required' => 'Please enter a short description of your consultation.',
            'concern.regex' => 'Please enter a short description of your consultation.',
        ]);

        $concern = trim($data['concern']);
        $careType = $data['care_type'] ?? 'medical';
        $severity = $data['severity'] ?? null;
        $triage = $this->triage->predict([
            'care_type' => $careType,
            'complaint' => $concern,
            'severity' => $severity,
        ]);

        $voiceFile = $request->file('voice_description');
        $voiceData = $voiceFile ? file_get_contents($voiceFile->getRealPath()) : null;
        if ($voiceFile && $voiceData === false) {
            throw new RuntimeException('Unable to read the voice recording for database storage.');
        }

        $voicePath = $voiceFile?->store(
            'consultations/'.$student->id.'/request-audio',
            'local',
        );
        $photoPath = $request->file('photo')?->store(
            'consultations/'.$student->id.'/request-photos',
            'local',
        );

        try {
            $consultation = DB::transaction(function () use ($student, $data, $concern, $careType, $triage, $voiceFile, $voiceData, $voicePath, $photoPath): ConsultationRequest {
                $triageLog = $triage ? MlTriageLog::create([
                    'student_id' => $student->id,
                    'campus_id' => $student->campus_id,
                    'input_features' => $triage['input_features'],
                    'predicted_priority' => $triage['predicted_priority'],
                    'confidence_score' => $triage['confidence_score'],
                    'model_version' => $triage['model_version'],
                    'metadata' => [
                        'consultation_triage' => true,
                        'mode' => $triage['mode'],
                        'fallback_reason' => $triage['fallback_reason'],
                        'ml_prediction' => $triage['ml_prediction'],
                    ],
                ]) : null;

                $consultation = ConsultationRequest::create([
                    'student_id' => $student->id,
                    'campus_id' => $student->campus_id,
                    'care_type' => $careType,
                    'concern' => $concern,
                    'symptom_onset' => $data['symptom_onset'] ?? null,
                    'severity' => $data['severity'] ?? null,
                    'status' => $voicePath ? 'audio_processing' : 'submitted',
                    'triage_priority' => $triage['predicted_priority'] ?? null,
                    'triage_confidence' => $triage['confidence_score'] ?? null,
                    'triage_details' => $triage ? [
                        'mode' => $triage['mode'],
                        'fallback_reason' => $triage['fallback_reason'],
                    ] : null,
                    'triage_log_id' => $triageLog?->id,
                    'student_voice_path' => $voicePath,
                    'student_photo_path' => $photoPath,
                ]);

                $transcription = MedicalTranscription::create([
                    'student_id' => $student->id,
                    'consultation_request_id' => $consultation->id,
                    'campus_id' => $student->campus_id,
                    'consultation_date' => today(),
                    'audio_duration_seconds' => $voicePath ? ($data['voice_duration_seconds'] ?? null) : null,
                    'raw_transcript' => $voicePath ? null : $consultation->concern,
                    'ai_draft' => $voicePath ? null : $consultation->concern,
                    'status' => $voicePath ? 'processing' : 'nurse_review',
                ]);
                $consultation->update(['medical_transcription_id' => $transcription->id]);

                if ($voiceFile && is_string($voiceData)) {
                    ConsultationRecording::query()->create([
                        'consultation_request_id' => $consultation->id,
                        'student_voice_data' => $voiceData,
                        'student_voice_mime' => $voiceFile->getMimeType() ?: 'application/octet-stream',
                        'student_voice_filename' => basename($voiceFile->getClientOriginalName()),
                    ]);
                }

                return $consultation;
            });
        } catch (Throwable $exception) {
            if ($voicePath) {
                Storage::disk('local')->delete($voicePath);
            }
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }

            throw $exception;
        }

        if ($voicePath) {
            $this->dispatchConsultationAudio($consultation->id);
        }

        $consultation->refresh()->load('medicalTranscription');
        GenerateConsultationSummary::dispatchFor($consultation->load('medicalTranscription'));

        Staff::query()
            ->active()
            ->where('role', 'nurse')
            ->where('campus_id', $student->campus_id)
            ->each(fn (Staff $nurse) => ClinicNotification::notifyStaff(
                $nurse->id,
                'consultation',
                'New student consultation request',
                'A student has submitted a health concern for review.',
                ['consultation_id' => $consultation->id],
            ));

        $this->audit->log($request, $student->id, 'student', 'submit_consultation_request', 'consultation_requests', $consultation->id);

        return response()->json([
            'success' => true,
            'consultation' => $this->studentPayload($consultation),
        ], 201);
    }

    public function studentShow(Request $request, int $consultation): JsonResponse
    {
        $identity = $this->identity($request);
        $record = ConsultationRequest::query()
            ->where('student_id', $identity['id'])
            ->findOrFail($consultation);

        return response()->json(['success' => true, 'consultation' => $this->studentPayload($record)]);
    }

    public function updateStudentRequest(Request $request, int $consultation): JsonResponse
    {
        $identity = $this->identity($request);
        $record = ConsultationRequest::query()
            ->where('student_id', $identity['id'])
            ->findOrFail($consultation);
        $data = $request->validate([
            'concern' => ['required', 'string', 'max:5000', 'regex:/\S/'],
        ]);
        $concern = trim($data['concern']);
        abort_unless($this->studentCanEditRequest($record), 409, 'This consultation can no longer be edited online.');
        $triage = $this->triage->predict([
            'care_type' => $record->care_type,
            'complaint' => $concern,
            'severity' => $record->severity,
        ]);

        DB::transaction(function () use ($record, $concern, $triage, $request, $identity): void {
            $lockedRecord = ConsultationRequest::query()->lockForUpdate()->findOrFail($record->id);
            abort_unless(
                $lockedRecord->student_id === $identity['id']
                    && $this->studentCanEditRequest($lockedRecord),
                409,
                'This consultation can no longer be edited online.',
            );

            $triageLog = MlTriageLog::create([
                'student_id' => $lockedRecord->student_id,
                'campus_id' => $lockedRecord->campus_id,
                'input_features' => $triage['input_features'],
                'predicted_priority' => $triage['predicted_priority'],
                'confidence_score' => $triage['confidence_score'],
                'model_version' => $triage['model_version'],
                'metadata' => [
                    'consultation_triage' => true,
                    'mode' => $triage['mode'],
                    'fallback_reason' => $triage['fallback_reason'],
                    'ml_prediction' => $triage['ml_prediction'],
                ],
            ]);
            $lockedRecord->update([
                'concern' => $concern,
                'triage_priority' => $triage['predicted_priority'],
                'triage_confidence' => $triage['confidence_score'],
                'triage_details' => [
                    'mode' => $triage['mode'],
                    'fallback_reason' => $triage['fallback_reason'],
                ],
                'triage_log_id' => $triageLog->id,
            ]);
            if (! $lockedRecord->student_voice_path) {
                $lockedRecord->medicalTranscription?->update([
                    'raw_transcript' => $concern,
                    'ai_draft' => $concern,
                ]);
            }
            $lockedRecord->appointment?->update(['reason' => $concern]);
            $this->audit->log(
                $request,
                $identity['id'],
                'student',
                'update_consultation_request',
                'consultation_requests',
                $lockedRecord->id,
                ['concern' => $record->concern],
                ['concern' => $concern],
            );
        });

        return response()->json(['success' => true, 'consultation' => $this->studentPayload($record->fresh())]);
    }

    public function destroyStudentRequest(Request $request, int $consultation): JsonResponse
    {
        $identity = $this->identity($request);
        $record = ConsultationRequest::query()
            ->where('student_id', $identity['id'])
            ->findOrFail($consultation);

        $this->deletePendingRequest($request, $record, $identity['id'], 'student');

        return response()->json(['success' => true]);
    }

    public function destroyNurseRequest(Request $request, int $consultation): JsonResponse
    {
        $record = $this->nurseRecord($request, $consultation);

        $this->deletePendingRequest($request, $record, $this->identity($request)['id'], 'staff');

        return response()->json(['success' => true]);
    }

    public function nurseIndex(Request $request): JsonResponse
    {
        $identity = $this->identity($request);
        $filters = $request->validate([
            'campus_id' => ['sometimes', 'nullable', 'integer', 'exists:campuses,id'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'priority' => ['sometimes', 'nullable', 'in:Priority 1,Priority 2,Priority 3'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $isNurseHead = $identity['role'] === 'nurse_head';
        $priorityValues = match ($filters['priority'] ?? null) {
            'Priority 1' => ['Priority 1', 'high', 'High'],
            'Priority 2' => ['Priority 2', 'medium', 'Medium'],
            'Priority 3' => ['Priority 3', 'low', 'Low'],
            default => [],
        };

        if (! $isNurseHead && isset($filters['campus_id'])) {
            abort_unless((int) $filters['campus_id'] === $identity['campus_id'], 403);
        }

        $consultations = ConsultationRequest::query()
            ->with([
                'student:id,student_id,first_name,middle_name,last_name,age,sex,campus_id',
                'student.campus:id,name',
            ])
            ->whereNull('accepted_at')
            ->whereNull('appointment_id')
            ->whereIn('status', [
                'submitted', 'nurse_reviewing', 'consultation_scheduled',
                'consultation_in_progress', 'audio_processing', 'transcript_ready',
                'transcription_failed', 'nurse_review_complete',
            ])
            ->when($priorityValues !== [], fn (Builder $query) => $query->whereIn('triage_priority', $priorityValues))
            ->when(
                $isNurseHead,
                fn (Builder $query) => $query->when(
                    $filters['campus_id'] ?? null,
                    fn (Builder $query, int|string $campusId) => $query->where('campus_id', $campusId),
                ),
                fn (Builder $query) => $query->where('campus_id', $identity['campus_id'])
                    ->where(fn (Builder $query) => $query->whereNull('nurse_id')->orWhere('nurse_id', $identity['id'])),
            )
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $terms = preg_split('/\s+/', trim($filters['search']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $query->whereHas('student', function (Builder $studentQuery) use ($terms): void {
                    foreach ($terms as $term) {
                        $searchTerm = '%'.$term.'%';
                        $studentQuery->where(function (Builder $query) use ($searchTerm): void {
                            $query->where('student_id', 'like', $searchTerm)
                                ->orWhere('first_name', 'like', $searchTerm)
                                ->orWhere('middle_name', 'like', $searchTerm)
                                ->orWhere('last_name', 'like', $searchTerm);
                        });
                    }
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'page', (int) ($filters['page'] ?? 1));

        return response()->json([
            'success' => true,
            'consultations' => $consultations->items(),
            'pagination' => [
                'current_page' => $consultations->currentPage(),
                'last_page' => $consultations->lastPage(),
                'per_page' => $consultations->perPage(),
                'total' => $consultations->total(),
                'from' => $consultations->firstItem(),
                'to' => $consultations->lastItem(),
            ],
        ]);
    }

    public function nurseConsultationCampuses(Request $request): JsonResponse
    {
        $identity = $this->identity($request);
        $campuses = $identity['role'] === 'nurse_head'
            ? Campus::query()->active()->orderBy('name')->get(['id', 'name'])
            : Campus::query()->whereKey($identity['campus_id'])->get(['id', 'name']);

        return response()->json(['success' => true, 'campuses' => $campuses]);
    }

    public function nurseShow(Request $request, int $consultation): JsonResponse
    {
        $record = $this->nurseRecord($request, $consultation);
        $student = $record->student()->with('campus')->firstOrFail();
        $lastVisit = ClinicVisit::query()
            ->withoutSyntheticDemo()
            ->where('student_id', $student->id)
            ->latest('visit_date')
            ->first(['visit_date', 'reason', 'symptoms', 'treatment', 'bp', 'temp', 'pulse', 'rr', 'o2_sat', 'disposition']);
        $healthRecord = $lastVisit ? null : HealthRecord::query()
            ->where('student_id', $student->id)
            ->latest('record_date')
            ->first([
                'record_date', 'allergy_detail', 'sym_headache', 'sym_dizziness',
                'sym_fainting', 'sym_blurring_vision', 'sym_hearing_difficulty',
                'sym_back_pain', 'sym_chest_pain', 'sym_breathing_difficulty',
                'sym_cough', 'sym_sore_throat', 'sym_runny_nose', 'sym_nose_bleed',
                'sym_vomiting', 'sym_nausea', 'sym_abdominal_pain', 'sym_fever',
                'sym_frequent_urination', 'sym_weight_loss', 'sym_difficulty_sleeping',
                'sym_nervousness', 'bp', 'pr', 'temp',
            ]);
        $transcription = $record->medicalTranscription;
        GenerateConsultationSummary::dispatchFor($record);
        $audioOnlyRequest = $record->student_voice_path
            && $record->concern === 'Audio recording submitted without written concern.';
        $requestText = $audioOnlyRequest
            ? ($transcription?->ai_draft ?? $transcription?->raw_transcript ?? '')
            : $record->concern;
        $triagePending = $audioOnlyRequest && $transcription?->status === 'processing';
        $triageFailed = $audioOnlyRequest && $transcription?->status === 'failed';
        $transcriptionTiming = $transcription
            ? $this->transcriptionTimingEstimate($transcription)
            : ['processing_elapsed_seconds' => null, 'estimated_total_seconds' => null, 'sample_count' => 0];

        return response()->json([
            'success' => true,
            'consultation' => array_merge($record->toArray(), [
                'student' => [
                    'student_id' => $student->student_id,
                    'name' => $student->full_name,
                    'age' => $student->age,
                    'sex' => $student->sex,
                    'department' => $student->department,
                    'campus' => $student->campus?->name,
                ],
                'clinical_context' => $this->consultationClinicalContext($lastVisit, $healthRecord, $requestText),
                'ai_summary' => GenerateConsultationSummary::stateFor($record),
                'triage_insight' => [
                    'priority' => $triagePending || $triageFailed ? null : $record->triage_priority,
                    'mode' => data_get($record->triage_details, 'mode'),
                    'confidence' => $record->triage_confidence,
                    'model_suggestion' => data_get($record->triageLog?->metadata, 'ml_prediction'),
                    'fallback_reason' => data_get($record->triage_details, 'fallback_reason'),
                    'pending' => $triagePending,
                    'failed' => $triageFailed,
                ],
                'transcription' => $transcription ? [
                    'id' => $transcription->id,
                    'raw_transcript' => $transcription->raw_transcript,
                    'ai_draft' => $transcription->ai_draft,
                    'status' => $transcription->status,
                    'processing_elapsed_seconds' => $transcriptionTiming['processing_elapsed_seconds'],
                    'processing_started' => $transcription->processing_started_at !== null,
                    'can_restart_unstarted' => app()->environment('local')
                        && $record->status === 'audio_processing'
                        && $transcription->status === 'processing'
                        && $transcription->processing_started_at === null,
                    'audio_duration_seconds' => $transcription->audio_duration_seconds,
                    'processing_duration_seconds' => $transcription->processing_duration_seconds,
                    'estimated_total_seconds' => $transcriptionTiming['estimated_total_seconds'],
                    'eta_sample_count' => $transcriptionTiming['sample_count'],
                ] : null,
                'media' => [
                    'voice_description' => url('/api/clinic/student/consultations/'.$record->id.'/media/student_voice'),
                    'photo' => url('/api/clinic/student/consultations/'.$record->id.'/media/student_photo'),
                    'official_audio' => $record->official_audio_path
                        ? url('/api/clinic/nurse/consultations/'.$record->id.'/media/official_audio')
                        : null,
                ],
            ]),
        ]);
    }

    /**
     * @return array{processing_elapsed_seconds: int|null, estimated_total_seconds: int|null, sample_count: int}
     */
    private function transcriptionTimingEstimate(MedicalTranscription $transcription): array
    {
        if ($transcription->status !== 'processing' || ! $transcription->processing_started_at) {
            return [
                'processing_elapsed_seconds' => null,
                'estimated_total_seconds' => null,
                'sample_count' => 0,
            ];
        }

        $elapsedSeconds = max(0, (int) $transcription->processing_started_at->diffInSeconds(now()));
        if (! $transcription->audio_duration_seconds) {
            return [
                'processing_elapsed_seconds' => $elapsedSeconds,
                'estimated_total_seconds' => null,
                'sample_count' => 0,
            ];
        }

        $samples = MedicalTranscription::query()
            ->whereIn('status', ['nurse_review', 'nurse_reviewed'])
            ->where('audio_duration_seconds', '>', 0)
            ->where('processing_duration_seconds', '>', 0)
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get(['audio_duration_seconds', 'processing_duration_seconds']);
        $sampleAudioSeconds = (int) $samples->sum('audio_duration_seconds');

        if ($sampleAudioSeconds === 0) {
            return [
                'processing_elapsed_seconds' => $elapsedSeconds,
                'estimated_total_seconds' => null,
                'sample_count' => 0,
            ];
        }

        $sampleProcessingSeconds = (int) $samples->sum('processing_duration_seconds');
        $estimatedTotalSeconds = (int) ceil(
            $transcription->audio_duration_seconds * $sampleProcessingSeconds / $sampleAudioSeconds,
        );

        return [
            'processing_elapsed_seconds' => $elapsedSeconds,
            'estimated_total_seconds' => $estimatedTotalSeconds,
            'sample_count' => $samples->count(),
        ];
    }

    /**
     * Return only the latest visit, or issue-relevant health-record facts when
     * no prior visit exists.
     *
     * @return array{source: string, date: string|null, details: list<array{label: string, value: string}>, assessment_needed: bool}
     */
    private function consultationClinicalContext(?ClinicVisit $lastVisit, ?HealthRecord $healthRecord, string $requestText): array
    {
        if ($lastVisit) {
            $details = array_filter([
                'Reason for visit' => $lastVisit->reason,
                'Symptoms recorded' => $lastVisit->symptoms,
                'Treatment recorded' => $lastVisit->treatment,
                'Blood pressure' => $lastVisit->bp,
                'Temperature' => $lastVisit->temp,
                'Pulse' => $lastVisit->pulse,
                'Respiratory rate' => $lastVisit->rr,
                'Oxygen saturation' => $lastVisit->o2_sat,
                'Disposition' => $lastVisit->disposition,
            ], fn ($value): bool => $value !== null && $value !== '');

            return [
                'source' => 'last_visit',
                'date' => filled($lastVisit->visit_date)
                    ? Carbon::parse((string) $lastVisit->visit_date)->toDateString()
                    : null,
                'details' => collect($details)
                    ->map(fn ($value, $label): array => ['label' => $label, 'value' => (string) $value])
                    ->values()
                    ->all(),
                'assessment_needed' => false,
            ];
        }

        if ($healthRecord) {
            $issue = mb_strtolower($requestText);
            $symptoms = [
                'sym_headache' => ['headache', 'head pain'],
                'sym_dizziness' => ['dizz'],
                'sym_fainting' => ['faint', 'blackout'],
                'sym_blurring_vision' => ['blur', 'vision'],
                'sym_hearing_difficulty' => ['hearing', 'hear'],
                'sym_back_pain' => ['back pain'],
                'sym_chest_pain' => ['chest pain'],
                'sym_breathing_difficulty' => ['breath', 'shortness of breath'],
                'sym_cough' => ['cough'],
                'sym_sore_throat' => ['sore throat', 'throat pain'],
                'sym_runny_nose' => ['runny nose', 'nasal congestion'],
                'sym_nose_bleed' => ['nose bleed', 'nosebleed'],
                'sym_vomiting' => ['vomit'],
                'sym_nausea' => ['nausea'],
                'sym_abdominal_pain' => ['abdominal pain', 'stomach pain'],
                'sym_fever' => ['fever'],
                'sym_frequent_urination' => ['frequent urination', 'urinate often'],
                'sym_weight_loss' => ['weight loss'],
                'sym_difficulty_sleeping' => ['sleep', 'insomnia'],
                'sym_nervousness' => ['nervous', 'anxious'],
            ];
            $details = [];

            if (filled($healthRecord->allergy_detail)) {
                $details[] = ['label' => 'Recorded allergies', 'value' => $healthRecord->allergy_detail];
            }

            foreach ($symptoms as $field => $keywords) {
                $matchesIssue = collect($keywords)->contains(fn (string $keyword): bool => str_contains($issue, $keyword));
                if ($matchesIssue) {
                    $details[] = [
                        'label' => 'Related health-record symptom',
                        'value' => str($field)->after('sym_')->replace('_', ' ')->title()->toString()
                            .' — '.(strtoupper((string) $healthRecord->{$field}) === 'Y' ? 'reported' : 'not reported'),
                    ];
                }
            }

            $recordedVitals = [];
            if (str_contains($issue, 'blood pressure') || str_contains($issue, 'hypertension')) {
                $recordedVitals['Blood pressure'] = $healthRecord->bp;
            }
            if (str_contains($issue, 'fever') || str_contains($issue, 'temperature')) {
                $recordedVitals['Temperature'] = $healthRecord->temp;
            }
            if (str_contains($issue, 'pulse') || str_contains($issue, 'heart rate')) {
                $recordedVitals['Pulse'] = $healthRecord->pr;
            }

            foreach ($recordedVitals as $label => $value) {
                if ($value !== null && $value !== '') {
                    $details[] = ['label' => $label, 'value' => (string) $value];
                }
            }

            return [
                'source' => 'health_record',
                'date' => filled($healthRecord->record_date)
                    ? Carbon::parse((string) $healthRecord->record_date)->toDateString()
                    : null,
                'details' => $details,
                'assessment_needed' => false,
            ];
        }

        return [
            'source' => 'none',
            'date' => null,
            'details' => [],
            'assessment_needed' => true,
        ];
    }

    public function accept(Request $request, int $consultation): JsonResponse
    {
        $record = $this->nurseRecord($request, $consultation);
        $identity = $this->identity($request);
        $data = $request->validate([
            'transcript' => ['sometimes', 'required', 'string', 'max:50000'],
        ]);

        if ($identity['role'] === 'nurse' && $record->accepted_at !== null && $record->appointment_id !== null && $record->nurse_id === null) {
            DB::transaction(function () use ($record, $identity): void {
                $lockedRecord = ConsultationRequest::query()->lockForUpdate()->findOrFail($record->id);
                abort_unless(
                    $lockedRecord->accepted_at !== null
                        && $lockedRecord->appointment_id !== null
                        && $lockedRecord->nurse_id === null,
                    409,
                    'This consultation has already been assigned to a nurse.',
                );
                $lockedRecord->update(['nurse_id' => $identity['id']]);
                $lockedRecord->medicalTranscription?->update(['nurse_id' => $identity['id']]);
            });

            return response()->json(['success' => true, 'status' => $record->status, 'appointment_id' => $record->appointment_id]);
        }

        abort_unless(
            in_array($record->status, ['submitted', 'audio_processing', 'transcript_ready', 'transcription_failed'], true)
                && $record->accepted_at === null
                && $record->appointment_id === null,
            409,
            'Only unaccepted consultation requests can be accepted.',
        );

        $record = DB::transaction(function () use ($request, $record, $identity, $data): ConsultationRequest {
            $lockedRecord = ConsultationRequest::query()->lockForUpdate()->findOrFail($record->id);
            abort_unless(
                in_array($lockedRecord->status, ['submitted', 'audio_processing', 'transcript_ready', 'transcription_failed'], true)
                    && $lockedRecord->accepted_at === null
                    && $lockedRecord->appointment_id === null,                     
                409,
                'This consultation request has already been accepted or declined.',
            );

            $oldStatus = $lockedRecord->status;
            $transcriptionStatus = $lockedRecord->medicalTranscription?->status;
            $status = match ($transcriptionStatus) {
                'processing' => 'audio_processing',
                'nurse_review', 'nurse_reviewed' => 'transcript_ready',
                'failed' => 'transcription_failed',
                default => $lockedRecord->status === 'submitted' ? 'nurse_reviewing' : $lockedRecord->status,
            };
            $nurseId = $identity['role'] === 'nurse'
                ? $identity['id']
                : $lockedRecord->nurse_id;
            $transcription = $lockedRecord->medicalTranscription;
            if (array_key_exists('transcript', $data)) {
                abort_unless($transcription, 409, 'The transcript cannot be saved because its transcription record is missing.');
                $transcription->update([
                    'raw_transcript' => $data['transcript'],
                    'ai_draft' => $data['transcript'],
                    'status' => 'nurse_reviewed',
                ]);
            }
            $appointment = Appointment::query()->create([
                'student_id' => $lockedRecord->student_id,
                'appt_date' => null,
                'appt_time' => null,
                'service_type' => $lockedRecord->care_type === 'dental' ? 'Dental consultation' : 'Medical consultation',
                'reason' => trim($lockedRecord->concern),
                'status' => 'accepted',
                'created_by' => $identity['id'],
                'campus_id' => $lockedRecord->campus_id,
            ]);
            $lockedRecord->update([
                'appointment_id' => $appointment->id,
                'nurse_id' => $nurseId,
                'status' => $status,
                'accepted_at' => now(),
            ]);
            $lockedRecord->medicalTranscription?->update(['nurse_id' => $nurseId]);

            $this->audit->log(
                $request,
                $identity['id'],
                'staff',
                'accept_consultation_request',
                'consultation_requests',
                $lockedRecord->id,
                ['status' => $oldStatus],
                ['status' => $status, 'appointment_id' => $appointment->id, 'accepted_at' => $lockedRecord->accepted_at?->toIso8601String()],
            );

            return $lockedRecord->fresh();
        });

        ClinicNotification::notifyStudent(
            $record->student_id,
            'consultation',
            'Consultation request accepted',
            'Your consultation request has been accepted. The clinic will schedule your appointment and notify you of the date and time.',
            ['consultation_id' => $record->id, 'appointment_id' => $record->appointment_id],
        );

        return response()->json([
            'success' => true,
            'status' => $record->status,
            'appointment_id' => $record->appointment_id,
            'scheduled_at' => $record->scheduled_at?->format('Y-m-d\TH:i:s'),
        ]);
    }

    public function decline(Request $request, int $consultation): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000', 'regex:/\\S/'],
        ]);
        $record = $this->nurseRecord($request, $consultation);
        $identity = $this->identity($request);

        abort_unless(
            in_array($record->status, ['submitted', 'audio_processing', 'transcript_ready', 'transcription_failed'], true)
                && $record->accepted_at === null
                && $record->appointment_id === null,
            409,
            'Only unaccepted consultation requests can be declined.',
        );

        $reason = trim($data['reason']);
        DB::transaction(function () use ($request, $record, $identity, $reason): void {
            $lockedRecord = ConsultationRequest::query()->lockForUpdate()->findOrFail($record->id);
            abort_unless(
                in_array($lockedRecord->status, ['submitted', 'audio_processing', 'transcript_ready', 'transcription_failed'], true)
                    && $lockedRecord->accepted_at === null
                    && $lockedRecord->appointment_id === null,
                409,
                'This consultation request has already been accepted or declined.',
            );
            $lockedRecord->update([
                'status' => 'declined',
                'decline_reason' => $reason,
            ]);
            $this->audit->log(
                $request,
                $identity['id'],
                'staff',
                'decline_consultation_request',
                'consultation_requests',
                $lockedRecord->id,
                ['status' => 'submitted'],
                ['status' => 'declined', 'decline_reason' => $reason],
            );
        });

        ClinicNotification::notifyStudent(
            $record->student_id,
            'consultation',
            'Consultation request declined',
            'The clinic declined your consultation request. Reason: '.$reason,
            ['consultation_id' => $record->id, 'status' => 'declined'],
        );

        return response()->json(['success' => true, 'status' => 'declined']);
    }

    public function retryTranscription(Request $request, int $consultation): JsonResponse
    {
        $record = $this->nurseRecord($request, $consultation);
        $identity = $this->identity($request);
        if ($identity['role'] !== 'nurse_head') {
            abort_unless($record->nurse_id === null || $record->nurse_id === $identity['id'], 403);
        }

        $status = DB::transaction(function () use ($record): string {
            $lockedRecord = ConsultationRequest::query()
                ->with('medicalTranscription')
                ->lockForUpdate()
                ->findOrFail($record->id);
            $transcription = $lockedRecord->medicalTranscription;
            $transcriptionStatus = $transcription?->status;
            $isRetryableFailure = $lockedRecord->status === 'transcription_failed'
                && $transcriptionStatus === 'failed';
            $isManualStart = $lockedRecord->status === 'submitted'
                && $lockedRecord->accepted_at === null
                && $lockedRecord->appointment_id === null
                && ($transcriptionStatus === null || in_array($transcriptionStatus, ['failed', 'pending', 'queued'], true));
            $isUnstartedQueueItem = app()->environment('local')
                && $lockedRecord->status === 'audio_processing'
                && $transcriptionStatus === 'processing'
                && $transcription?->processing_started_at === null;
            abort_unless(
                $isRetryableFailure || $isManualStart || $isUnstartedQueueItem,
                409,
                'This recording is already processing or is not ready for transcription.',
            );

            $audioPath = $lockedRecord->official_audio_path ?? $lockedRecord->student_voice_path;
            $recording = $lockedRecord->recording;
            $hasDatabaseAudio = filled($recording?->official_audio_data)
                || filled($recording?->student_voice_data);
            abort_unless($hasDatabaseAudio || $audioPath, 409, 'This consultation has no recording to transcribe.');
            if (! $hasDatabaseAudio) {
                abort_unless(Storage::disk('local')->exists($audioPath), 404, 'The retained consultation recording is unavailable.');
            }

            if (! $transcription) {
                $transcription = MedicalTranscription::query()->create([
                    'student_id' => $lockedRecord->student_id,
                    'consultation_request_id' => $lockedRecord->id,
                    'nurse_id' => $lockedRecord->nurse_id,
                    'campus_id' => $lockedRecord->campus_id,
                    'consultation_date' => today(),
                    'status' => 'processing',
                ]);
                $lockedRecord->update(['medical_transcription_id' => $transcription->id]);
            } else {
                $transcription->update([
                    'status' => 'processing',
                    'raw_transcript' => null,
                    'ai_draft' => null,
                    'processing_duration_seconds' => null,
                    'processing_started_at' => app()->environment('local') ? now() : null,
                ]);
            }

            if ($lockedRecord->status === 'transcription_failed') {
                $lockedRecord->update(['status' => 'audio_processing']);
            }

            return $lockedRecord->fresh()->status;
        });
        $this->dispatchConsultationAudio($record->id);

        return response()->json(['success' => true, 'status' => $status], 202);
    }

    private function dispatchConsultationAudio(int $consultationId): void
    {
        if (app()->environment('local')) {
            ProcessConsultationAudio::dispatchSync($consultationId);

            return;
        }

        ProcessConsultationAudio::dispatch($consultationId)
            ->onQueue(config('queue.transcription_queue'));
    }

    public function completeNurseReview(Request $request, int $consultation): JsonResponse
    {
        $data = $request->validate([
            'transcript' => ['required', 'string', 'max:50000'],
            'nurse_summary' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ]);
        $record = $this->nurseRecord($request, $consultation);
        abort_unless($record->nurse_id === $this->identity($request)['id'], 403);
        abort_unless($record->status === 'transcript_ready', 409, 'The official transcript must finish processing before nurse review.');

        DB::transaction(function () use ($record, $data): void {
            $transcription = $record->medicalTranscription;
            $transcription?->update([
                'raw_transcript' => $data['transcript'],
                'ai_draft' => $data['transcript'],
                'status' => 'nurse_reviewed',
            ]);
            $recordUpdates = [
                'nurse_reviewed_at' => now(),
                'status' => 'nurse_review_complete',
            ];
            if (array_key_exists('nurse_summary', $data)) {
                $recordUpdates['nurse_summary'] = $data['nurse_summary'];
            }
            $record->update($recordUpdates);
        });

        return response()->json(['success' => true, 'status' => 'nurse_review_complete']);
    }

    public function giveNursingAdvice(Request $request, int $consultation): JsonResponse
    {
        $data = $request->validate([
            'nursing_advice' => ['required', 'string', 'max:5000'],
            'emergency_instructions' => ['nullable', 'string', 'max:2000'],
        ]);
        $record = $this->nurseRecord($request, $consultation);
        abort_unless($record->nurse_id === $this->identity($request)['id'], 403);
        abort_unless($record->status === 'nurse_review_complete', 409);

        $record = DB::transaction(function () use ($record, $data): ConsultationRequest {
            $lockedRecord = ConsultationRequest::query()
                ->with(['clinicVisit', 'medicalTranscription'])
                ->lockForUpdate()
                ->findOrFail($record->id);
            abort_unless($lockedRecord->status === 'nurse_review_complete', 409);

            $lockedRecord->update([
                'nursing_advice' => $data['nursing_advice'],
                'emergency_instructions' => $data['emergency_instructions'] ?? null,
                'status' => 'completed',
            ]);
            $visit = $this->saveCompletedConsultationVisit($lockedRecord);
            $lockedRecord->update(['clinic_visit_id' => $visit->id]);
            $lockedRecord->medicalTranscription?->update(['status' => 'nurse_completed']);

            return $lockedRecord->fresh();
        });

        ClinicNotification::notifyStudent(
            $record->student_id,
            'consultation',
            'Nurse advice is ready',
            'Your consultation has been completed. Review the instructions in your consultation status.',
            ['consultation_id' => $record->id],
        );

        return response()->json(['success' => true, 'status' => $record->status]);
    }

    public function referToClinician(Request $request, int $consultation): JsonResponse
    {
        $data = $request->validate([
            'clinician_role' => ['required', 'in:physician,dentist'],
            'availability_id' => ['required', 'integer', 'exists:clinic_availabilities,id'],
            'appt_time' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:5000'],
            'urgency' => ['required', 'in:routine,urgent,emergency'],
        ]);
        $record = $this->nurseRecord($request, $consultation);
        $identity = $this->identity($request);
        abort_unless(
            $identity['role'] === 'nurse_head' || $record->nurse_id === $identity['id'],
            403,
        );
        abort_unless($record->status === 'nurse_review_complete', 409);

        $result = DB::transaction(function () use ($data, $record, $identity, $request): array {
            $lockedRecord = ConsultationRequest::query()
                ->with('medicalTranscription')
                ->lockForUpdate()
                ->findOrFail($record->id);
            abort_unless(
                $lockedRecord->status === 'nurse_review_complete'
                    && ($identity['role'] === 'nurse_head' || $lockedRecord->nurse_id === $identity['id'])
                    && $lockedRecord->referral_id === null,
                409,
                'This consultation is no longer available to refer.',
            );

            $availability = ClinicAvailability::query()
                ->lockForUpdate()
                ->findOrFail($data['availability_id']);
            abort_unless(
                $availability->is_available
                    && (int) $availability->campus_id === (int) $lockedRecord->campus_id
                    && $availability->provider_type === $data['clinician_role']
                    && $availability->available_date->gte(today()),
                409,
                'The selected availability is not valid for this student or referral type.',
            );
            abort_unless(
                in_array($data['appt_time'], $availability->available_times ?? [], true),
                409,
                'Choose a time published on the selected availability.',
            );
            if ($availability->available_date->isToday() && $data['appt_time'] <= now()->format('H:i')) {
                abort(409, 'The selected appointment time has already passed.');
            }

            $appointment = $lockedRecord->appointment_id
                ? Appointment::query()->lockForUpdate()->findOrFail($lockedRecord->appointment_id)
                : null;
            if ($appointment) {
                abort_unless(in_array($appointment->status, ['accepted', 'confirmed'], true), 409);
                $oldAppointmentStatus = $appointment->status;
            } else {
                abort(409, 'Accept the consultation request before scheduling its referral.');
            }

            $activeAppointments = $availability->appointments()
                ->whereIn('status', ['pending', 'accepted', 'confirmed'])
                ->where('appointments.id', '<>', $appointment->id);
            abort_unless(
                $activeAppointments->count() < $availability->capacity,
                409,
                'This availability has reached its student referral limit.',
            );
            abort_unless(
                ! $activeAppointments->where('appt_time', $data['appt_time'])->exists(),
                409,
                'This appointment time has already been reserved.',
            );

            $scheduledAt = Carbon::parse(
                $availability->available_date->toDateString().' '.$data['appt_time'],
            );
            $referral = Referral::create([
                'referral_no' => Referral::generateReferralNo(),
                'student_id' => $lockedRecord->student_id,
                'from_campus_id' => $lockedRecord->campus_id,
                'referred_by' => $identity['id'],
                'referred_to_name' => $data['clinician_role'] === 'dentist' ? 'Dentist' : 'Physician',
                'specialty' => $data['clinician_role'] === 'dentist' ? 'Dentist' : 'Physician',
                'route_to' => $data['clinician_role'],
                'history' => $lockedRecord->nurse_summary,
                'reason' => $data['reason'],
                'management_given' => $lockedRecord->nursing_advice,
                'urgency' => $data['urgency'],
                'status' => 'pending',
                'scheduled_at' => $scheduledAt,
                'source_appointment_id' => $appointment?->id,
            ]);

            $scheduledAppointment = Appointment::query()->create([
                'student_id' => $lockedRecord->student_id,
                'appt_date' => $availability->available_date,
                'appt_time' => $data['appt_time'],
                'service_type' => $data['clinician_role'],
                'reason' => $data['reason'],
                'status' => 'confirmed',
                'created_by' => $identity['id'],
                'campus_id' => $availability->campus_id,
                'availability_id' => $availability->id,
                'notes' => 'Scheduled from referral '.$referral->referral_no,
            ]);

            $appointment->update([
                'status' => 'completed',
                'cancellation_reason' => null,
            ]);
            $this->audit->log(
                $request,
                $identity['id'],
                'staff',
                'complete_referred_appointment',
                'appointments',
                $appointment->id,
                ['status' => $oldAppointmentStatus],
                [
                    'status' => 'completed',
                    'referral_id' => $referral->id,
                    'scheduled_appointment_id' => $scheduledAppointment->id,
                ],
            );
            $this->audit->log(
                $request,
                $identity['id'],
                'staff',
                'schedule_referred_appointment',
                'appointments',
                $scheduledAppointment->id,
                [],
                [
                    'status' => 'confirmed',
                    'appt_date' => $availability->available_date->toDateString(),
                    'appt_time' => $data['appt_time'],
                    'campus_id' => $availability->campus_id,
                    'availability_id' => $availability->id,
                    'referral_id' => $referral->id,
                ],
            );
            $lockedRecord->update([
                'referral_id' => $referral->id,
                'status' => 'completed',
                'scheduled_at' => $scheduledAt,
            ]);

            return ['referral' => $referral, 'appointment' => $scheduledAppointment, 'scheduled_at' => $scheduledAt];
        });

        $referral = $result['referral'];
        ClinicNotification::notifyStudent(
            $record->student_id,
            'consultation',
            'Consultation referred',
            'Your nurse referred you to a '.($data['clinician_role'] === 'dentist' ? 'dentist' : 'physician')
                .' on '.$result['scheduled_at']->format('M j').' at '.$result['scheduled_at']->format('g:i A').'.',
            ['consultation_id' => $record->id, 'appointment_id' => $result['appointment']->id, 'referral_id' => $referral->id],
        );
        $this->audit->log($request, $identity['id'], 'staff', 'refer_consultation', 'consultation_requests', $record->id);

        return response()->json([
            'success' => true,
            'status' => 'completed',
            'referral_id' => $referral->id,
            'appointment_id' => $result['appointment']->id,
            'scheduled_at' => $result['scheduled_at']->toIso8601String(),
        ]);
    }

    private function consultationTreatment(ConsultationRequest $record): string
    {
        return collect([
            'Diagnosis' => $record->diagnosis,
            'Treatment plan' => $record->treatment_plan,
            'Nursing advice' => $record->nursing_advice,
            'Clinician instructions' => $record->approved_instructions,
            'Emergency instructions' => $record->emergency_instructions,
            'Follow-up' => $record->follow_up,
        ])->filter()->map(fn (string $value, string $label): string => $label.': '.$value)->implode("\n");
    }

    private function saveCompletedConsultationVisit(ConsultationRequest $record): ClinicVisit
    {
        $vitals = $record->vitals ?? [];
        $audioOnlyRequest = $record->concern === 'Audio recording submitted without written concern.';
        $transcript = $record->medicalTranscription?->ai_draft
            ?: $record->medicalTranscription?->raw_transcript;
        $visitData = [
            'student_id' => $record->student_id,
            'visit_date' => today(),
            'visit_time' => now('Asia/Manila')->format('H:i:s'),
            'reason' => $audioOnlyRequest && filled($transcript) ? trim($transcript) : $record->concern,
            'symptoms' => $record->nurse_summary,
            'treatment' => $this->consultationTreatment($record),
            'notes' => $record->staff_notes,
            'nurse_id' => $record->nurse_id,
            'campus_id' => $record->campus_id,
        ];

        foreach (['bp', 'temp', 'pulse', 'rr', 'o2_sat'] as $field) {
            $value = $vitals[$field] ?? $record->clinicVisit?->{$field};
            if ($value !== null) {
                $visitData[$field] = $value;
            }
        }

        $visit = $record->clinicVisit;
        if ($visit) {
            $visit->update($visitData);

            return $visit->fresh();
        }

        return ClinicVisit::query()->create($visitData);
    }

    public function downloadMedia(Request $request, int $consultation, string $kind): HttpResponse
    {
        $identity = $this->identity($request);
        $record = $identity['role'] === 'student'
            ? ConsultationRequest::query()->where('student_id', $identity['id'])->findOrFail($consultation)
            : $this->nurseRecord($request, $consultation);

        $recording = in_array($kind, ['student_voice', 'official_audio'], true)
            ? $record->recording
            : null;
        [$path, $audioData, $mime, $filename] = match ($kind) {
            'student_voice' => [
                $record->student_voice_path,
                $recording?->student_voice_data,
                $recording?->student_voice_mime,
                $recording?->student_voice_filename,
            ],
            'student_photo' => [$record->student_photo_path, null, null, null],
            'official_audio' => [
                $record->official_audio_path,
                $recording?->official_audio_data,
                $recording?->official_audio_mime,
                $recording?->official_audio_filename,
            ],
            default => [null, null, null, null],
        };

        if (is_string($audioData) && $audioData !== '') {
            return response($audioData)
                ->header('Content-Type', is_string($mime) ? $mime : 'application/octet-stream')
                ->header('Content-Disposition', HeaderUtils::makeDisposition(
                    'inline',
                    is_string($filename) && $filename !== '' ? basename($filename) : 'consultation-recording',
                ))
                ->header('X-Content-Type-Options', 'nosniff');
        }

        abort_unless(is_string($path) && $path !== '', 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return response()->download(Storage::disk('local')->path($path));
    }

    private function identity(Request $request): array
    {
        $identity = $request->attributes->get('clinic_identity');
        abort_unless(is_array($identity) && isset($identity['id'], $identity['role']), 401, 'Authentication required.');

        return $identity;
    }

    private function nurseRecord(Request $request, int $id): ConsultationRequest
    {
        $identity = $this->identity($request);
        $query = ConsultationRequest::query()
            ->with(['student', 'medicalTranscription'])
            ->whereIn('status', [
                'submitted', 'nurse_reviewing', 'consultation_scheduled',
                'consultation_in_progress', 'audio_processing', 'transcript_ready',
                'transcription_failed', 'nurse_review_complete',
            ]);

        if ($identity['role'] !== 'nurse_head') {
            $query->where('campus_id', $identity['campus_id'])
                ->where(fn (Builder $query) => $query->whereNull('nurse_id')->orWhere('nurse_id', $identity['id']));
        }

        return $query->findOrFail($id);
    }

    private function physicianRecord(): never
    {
        abort(404);
    }

    private function studentPayload(ConsultationRequest $record): array
    {
        return [
            'id' => $record->id,
            'care_type' => $record->care_type,
            'concern' => $record->concern,
            'status' => $record->status,
            'appointment_id' => $record->appointment_id,
            'can_edit' => $this->studentCanEditRequest($record),
            'can_delete' => $this->studentCanDeleteRequest($record),
            'decline_reason' => $record->decline_reason,
            'submitted_at' => $record->created_at,
            'scheduled_at' => $record->scheduled_at?->toIso8601String(),
            'nursing_advice' => $record->status === 'completed' ? $record->nursing_advice : null,
            'emergency_instructions' => $record->status === 'completed' ? $record->emergency_instructions : null,
        ];
    }

    private function studentCanEditRequest(ConsultationRequest $record): bool
    {
        return in_array($record->status, [
            'submitted', 'nurse_reviewing', 'consultation_scheduled',
            'audio_processing', 'transcript_ready', 'transcription_failed',
        ], true)
            && $record->clinic_visit_id === null
            && $record->referral_id === null;
    }

    private function studentCanDeleteRequest(ConsultationRequest $record): bool
    {
        if (! in_array($record->status, [
            'submitted', 'declined', 'nurse_reviewing', 'consultation_scheduled',
            'audio_processing', 'transcript_ready', 'transcription_failed',
        ], true) || $record->clinic_visit_id !== null || $record->referral_id !== null) {
            return false;
        }

        if (! $record->appointment_id) {
            return true;
        }

        $appointment = $record->appointment;

        return $appointment !== null
            && in_array($appointment->status, ['accepted', 'confirmed'], true)
            && ($appointment->status === 'accepted'
                || ($appointment->appt_date !== null
            && Carbon::parse(
                $appointment->appt_date->format('Y-m-d').' '.($appointment->appt_time ?? '23:59:59'),
                config('app.timezone'),
            )->isFuture()));
    }

    private function deletePendingRequest(Request $request, ConsultationRequest $record, int $actorId, string $actorType): void
    {
        DB::transaction(function () use ($request, $record, $actorId, $actorType): void {
            $record = ConsultationRequest::query()
                ->lockForUpdate()
                ->findOrFail($record->id);

            $canDelete = $actorType === 'student'
                ? $record->student_id === $actorId
                    && $this->studentCanDeleteRequest($record)
                    && ($record->appointment_id === null || $record->appointment?->student_id === $actorId)
                : $record->appointment_id === null
                    && in_array($record->status, [
                        'submitted', 'nurse_reviewing', 'consultation_scheduled',
                        'consultation_in_progress', 'audio_processing', 'transcript_ready',
                        'transcription_failed',
                    ], true)
                    && $record->clinic_visit_id === null
                    && $record->referral_id === null;

            abort_unless(
                $canDelete,
                409,
                $actorType === 'student'
                    ? 'Only active consultation requests without a completed clinic visit can be deleted.'
                    : 'Only active consultation requests without a completed nurse review can be deleted.',
            );

            $appointmentId = $record->appointment_id;
            if ($actorType === 'student' && $appointmentId !== null) {
                $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointmentId);
                abort_unless(
                    $appointment->student_id === $actorId
                        && in_array($appointment->status, ['accepted', 'confirmed'], true)
                        && ($appointment->status === 'accepted'
                            || ($appointment->appt_date !== null
                                && Carbon::parse(
                                    $appointment->appt_date->format('Y-m-d').' '.($appointment->appt_time ?? '23:59:59'),
                                    config('app.timezone'),
                                )->isFuture())),
                    409,
                    'Only unscheduled or upcoming consultation appointments can be deleted.',
                );
                $appointment->update([
                    'status' => 'deleted',
                    'cancellation_reason' => 'Consultation request deleted by student.',
                ]);
            }

            $paths = array_values(array_filter([
                $record->student_voice_path,
                $record->student_photo_path,
                $record->official_audio_path,
            ]));

            if ($paths !== [] && ! Storage::disk('local')->delete($paths)) {
                throw new RuntimeException('Unable to remove the consultation attachments.');
            }

            $transcription = $record->medicalTranscription;
            if ($transcription?->consultation_request_id === $record->id && $transcription->clinic_visit_id === null) {
                $transcription->delete();
            }

            $record->delete();
            $this->audit->log(
                $request,
                $actorId,
                $actorType,
                'delete_consultation_request',
                'consultation_requests',
                $record->id,
                ['student_id' => $record->student_id, 'status' => $record->status, 'appointment_id' => $appointmentId],
            );
        });
    }

    /**
     * Transcribe a student's voice recording and return the text so the
     * student can review and edit it before submitting the consultation form.
     *
     * POST /api/clinic/student/consultations/preview-transcript
     * Throttle: 6 requests / minute (per student).
     */
    public function previewTranscript(Request $request): JsonResponse
    {
        $request->validate([
            'voice_description' => ['required', 'file', 'max:20480'],
        ]);

        $file = $request->file('voice_description');
        $audio = file_get_contents($file->getRealPath());
        if ($audio === false) {
            return response()->json(['error' => 'Could not read the audio file.'], 422);
        }

        try {
            $result = $this->whisper->transcribeContents(
                $audio,
                $file->getClientOriginalName() ?: 'recording.webm',
                $file->getMimeType() ?: 'audio/webm',
            );
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }

        return response()->json([
            'success'    => true,
            'transcript' => $result['text'],
        ]);
    }
}
