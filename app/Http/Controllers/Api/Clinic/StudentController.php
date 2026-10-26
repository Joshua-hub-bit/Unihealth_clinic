<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ClinicAvailability;
use App\Models\ClinicNotification;
use App\Models\FormCertificate;
use App\Models\HealthRecord;
use App\Models\Medication;
use App\Models\Student;
use App\Services\AuditService;
use App\Services\ClinicHelperService;
use App\Services\PrescriptionPdfService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    /**
     * Allowed fields on the health record form.
     *
     * @var list<string>
     */
    private const HEALTH_FIELDS = [
        'last_name', 'first_name', 'middle_name', 'contact',
        'course_year', 'age', 'date_of_birth', 'blood_type',
        'height_cm', 'weight_kg',
        'ec_name', 'ec_relationship', 'ec_address', 'ec_contact',
        'allergy_detail', 'accident_detail', 'hospitalization_detail',
        'surgical_detail', 'family_history', 'past_medical_history',
        'sym_headache', 'sym_dizziness', 'sym_fainting',
        'sym_blurring_vision', 'sym_hearing_difficulty',
        'sym_back_pain', 'sym_chest_pain', 'sym_breathing_difficulty',
        'sym_cough', 'sym_sore_throat', 'sym_runny_nose',
        'sym_nose_bleed', 'sym_vomiting', 'sym_nausea',
        'sym_abdominal_pain', 'sym_fever', 'sym_frequent_urination',
        'sym_weight_loss', 'sym_difficulty_sleeping', 'sym_nervousness',
        'imm_chicken_pox', 'imm_typhoid', 'imm_hepatitis_a',
        'imm_hepatitis_b', 'imm_tetanus', 'imm_covid',
        'menstrual_regular', 'menstrual_irregular',
        'excessive_blood_flow', 'avg_days', 'dysmenorrhea',
        'signature_name', 'record_date',
    ];

    /** @var list<string> */
    private const BOOL_FIELDS = [
        'imm_chicken_pox', 'imm_typhoid', 'imm_hepatitis_a',
        'imm_hepatitis_b', 'imm_tetanus', 'imm_covid',
        'menstrual_regular', 'menstrual_irregular',
    ];

    public function __construct(
        private AuditService $audit,
        private ClinicHelperService $helper,
    ) {}

    // -----------------------------------------------------------------------
    // POST /api/clinic/student/health-record
    // -----------------------------------------------------------------------
    public function saveHealthRecord(Request $request): JsonResponse
    {
        $body = $request->all();
        $validatedDates = $request->validate([
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'record_date' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $studentId = $this->helper->resolveStudentId($body['student_no'] ?? '');
        if (! $studentId) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $student = Student::findOrFail($studentId);

        // Build the record data from the permitted field list
        $data = ['student_id' => $studentId];
        foreach (self::HEALTH_FIELDS as $field) {
            $data[$field] = $body[$field] ?? null;
        }

        foreach (['middle_name', 'contact', 'excessive_blood_flow', 'dysmenorrhea'] as $field) {
            $data[$field] ??= '';
        }

        foreach ([
            'sym_headache', 'sym_dizziness', 'sym_fainting', 'sym_blurring_vision',
            'sym_hearing_difficulty', 'sym_back_pain', 'sym_chest_pain',
            'sym_breathing_difficulty', 'sym_cough', 'sym_sore_throat',
            'sym_runny_nose', 'sym_nose_bleed', 'sym_vomiting', 'sym_nausea',
            'sym_abdominal_pain', 'sym_fever', 'sym_frequent_urination',
            'sym_weight_loss', 'sym_difficulty_sleeping', 'sym_nervousness',
        ] as $field) {
            $data[$field] ??= 'N';
        }

        // Nullify empty numerics
        foreach (['age', 'height_cm', 'weight_kg'] as $numericField) {
            if ($data[$numericField] === '' || $data[$numericField] === null) {
                $data[$numericField] = null;
            }
        }

        // Validate & normalise dates
        foreach (['date_of_birth', 'record_date'] as $dateField) {
            $data[$dateField] = $this->helper->sqlDate($validatedDates[$dateField] ?? null);
        }

        // Boolean immunisation & menstrual fields
        foreach (self::BOOL_FIELDS as $boolField) {
            $data[$boolField] = $this->helper->normaliseBool($data[$boolField]);
        }

        // Mirror profile columns back on the students row
        $profileData = array_filter(
            array_intersect_key($data, array_flip([
                'last_name', 'first_name', 'middle_name', 'contact',
                'course_year', 'age', 'date_of_birth', 'blood_type',
            ])),
            fn ($v) => $v !== null,
        );

        foreach (['address', 'sex', 'civil_status'] as $extra) {
            if (isset($body[$extra])) {
                $profileData[$extra] = $body[$extra];
            }
        }

        if ($profileData) {
            $student->update($profileData);
        }

        // Upsert the health record
        $existing = HealthRecord::where('student_id', $studentId)
            ->latest()
            ->first();

        if ($existing) {
            $recordData = $data;
            unset($recordData['student_id']);
            $existing->update($recordData);
            $record = $existing;
            $actionTaken = 'updated';
        } else {
            $record = HealthRecord::create($data);
            $actionTaken = 'created';
        }

        $this->audit->log($request, $studentId, 'student', 'save_health_record', 'health_records', $record->id);

        return response()->json([
            'success' => true,
            'record_id' => $record->id,
            'action' => $actionTaken,
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/student/health-record
    // -----------------------------------------------------------------------
    public function getHealthRecord(Request $request): JsonResponse
    {
        $studentId = $this->helper->resolveStudentId((string) $request->query('student_no', ''));
        if (! $studentId) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $record = HealthRecord::where('student_id', $studentId)->latest()->first();

        return response()->json(['success' => true, 'record' => $record]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/staff/students/{studentNo}/health-record
    // -----------------------------------------------------------------------
    public function staffHealthRecord(string $studentNo): JsonResponse
    {
        $student = Student::with(['campus', 'latestHealthRecord'])
            ->students()
            ->where('student_id', strtoupper($studentNo))
            ->where('is_active', true)
            ->first();

        if (! $student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $visits = $student->clinicVisits()
            ->withClinicalDocumentation()
            ->withoutSyntheticDemo()
            ->with([
                'nurse:id,first_name,last_name',
                'physician:id,first_name,last_name',
            ])
            ->latest('visit_date')
            ->latest('created_at')
            ->limit(10)
            ->get([
                'id', 'student_id', 'visit_date', 'visit_time', 'reason', 'symptoms',
                'treatment', 'bp', 'temp', 'pulse', 'rr', 'o2_sat',
                'nurse_id', 'physician_id', 'disposition', 'notes',
            ]);

        return response()->json([
            'success' => true,
            'student' => $student->makeHidden('password'),
            'record' => $student->latestHealthRecord,
            'visits' => $visits,
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/student/profile
    // -----------------------------------------------------------------------
    public function profile(Request $request): JsonResponse
    {
        $studentNo = (string) $request->query('student_no', '');
        if (! $studentNo) {
            return response()->json(['error' => 'Student number required'], 400);
        }

        $student = Student::with('campus')
            ->where('student_id', strtoupper($studentNo))
            ->where('is_active', true)
            ->first();

        if (! $student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        return response()->json([
            'success' => true,
            'profile' => array_merge(
                $student->makeHidden('password')->toArray(),
                ['campus_name' => $student->campus?->name ?? '']
            ),
        ]);
    }

    // -----------------------------------------------------------------------
    // PUT /api/clinic/student/profile
    // -----------------------------------------------------------------------
    public function updateProfile(Request $request): JsonResponse
    {
        $body = $request->all();

        $studentId = $this->helper->resolveStudentId($body['student_no'] ?? '');
        if (! $studentId) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $student = Student::findOrFail($studentId);

        $allowed = [
            'first_name', 'last_name', 'middle_name', 'contact',
            'address', 'course_year', 'department', 'section',
            'sex', 'civil_status', 'age', 'date_of_birth', 'blood_type',
        ];
        $nullableFields = ['age', 'date_of_birth', 'blood_type'];

        $updates = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $body)) {
                $value = $body[$field];
                $updates[$field] = in_array($field, $nullableFields, true) && ($value === null || trim((string) $value) === '')
                    ? null
                    : trim((string) ($value ?? ''));
            }
        }

        // Optional password change
        $newPassword = $body['new_password'] ?? '';
        if ($newPassword) {
            if (strlen($newPassword) < 6) {
                return response()->json(['error' => 'Password must be at least 6 characters'], 400);
            }
            if (($body['new_password_confirmation'] ?? '') !== $newPassword) {
                return response()->json(['error' => 'New passwords do not match'], 422);
            }
            if (! Hash::check($body['current_password'] ?? '', $student->password)) {
                return response()->json(['error' => 'Current password is incorrect'], 401);
            }
            $updates['password'] = $newPassword; // hashed by the model cast
        }

        if (empty($updates)) {
            return response()->json(['error' => 'Nothing to update'], 400);
        }

        $student->update($updates);
        $student->load('campus');

        return response()->json([
            'success' => true,
            'profile' => array_merge(
                $student->makeHidden('password')->toArray(),
                ['campus_name' => $student->campus?->name ?? '']
            ),
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/student/profile/picture
    // -----------------------------------------------------------------------
    public function uploadPicture(Request $request): JsonResponse
    {
        $body = $request->all();
        $picture = $body['picture'] ?? '';

        if (! $picture) {
            return response()->json(['error' => 'No image data'], 400);
        }
        if (! preg_match('/^data:image\/(jpeg|png|webp|gif);base64,/', $picture)) {
            return response()->json(['error' => 'Invalid image format'], 400);
        }
        if (strlen($picture) > 2_800_000) {
            return response()->json(['error' => 'Image too large. Max 2MB.'], 400);
        }

        $studentId = $this->helper->resolveStudentId($body['student_no'] ?? '');
        if (! $studentId) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        Student::where('id', $studentId)->update(['profile_picture' => $picture]);

        return response()->json(['success' => true, 'picture' => $picture]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/student/stats
    // Per-student dashboard counters.
    // -----------------------------------------------------------------------
    public function stats(Request $request): JsonResponse
    {
        $studentId = $this->helper->resolveStudentId((string) $request->query('student_no', ''));
        if (! $studentId) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $student = Student::findOrFail($studentId);

        $totalVisits = (clone $student)->clinicVisits()->withoutSyntheticDemo()->count();
        $totalAppointments = (clone $student)->appointments()->count();
        $activePrescriptions = (clone $student)->medications()->where('active', true)->count();
        $openReferrals = (clone $student)->referrals()->where('status', 'pending')->count();
        $formsIssued = (clone $student)->formCertificates()->count();
        $hasHealthRecord = $student->latestHealthRecord()->exists();

        $nextAppt = (clone $student)->appointments()
            ->whereIn('status', ['pending', 'accepted', 'checkup_scheduled', 'checkup_completed', 'confirmed'])
            ->orderByRaw("(CASE WHEN status IN ('checkup_scheduled', 'checkup_completed') THEN checkup_date ELSE appt_date END) IS NULL")
            ->orderByRaw("CASE WHEN status IN ('checkup_scheduled', 'checkup_completed') THEN checkup_date ELSE appt_date END")
            ->orderByRaw("CASE WHEN status IN ('checkup_scheduled', 'checkup_completed') THEN checkup_time ELSE appt_time END")
            ->first(['id', 'appt_date', 'appt_time', 'checkup_date', 'checkup_time', 'service_type', 'status', 'reason']);

        $recentVisits = (clone $student)->clinicVisits()
            ->withoutSyntheticDemo()
            ->latest('visit_date')
            ->take(5)
            ->get(['id', 'visit_date', 'reason', 'treatment', 'disposition']);

        $trendStart = now()->startOfDay()->subDays(29);
        $trendEnd = now()->endOfDay();
        $appointmentsByDay = (clone $student)->appointments()
            ->whereBetween('appt_date', [$trendStart->toDateString(), $trendEnd->toDateString()])
            ->get(['appt_date'])
            ->countBy(fn (Appointment $appointment): string => Carbon::parse((string) $appointment->appt_date)->format('Y-m-d'));

        $appointmentTrends = collect(range(0, 29))
            ->map(function (int $dayOffset) use ($trendStart, $appointmentsByDay): array {
                $day = $trendStart->copy()->addDays($dayOffset);
                $dayKey = $day->format('Y-m-d');

                return [
                    'day' => $dayKey,
                    'label' => $day->format('M j'),
                    'count' => $appointmentsByDay->get($dayKey, 0),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'stats' => [
                'total_visits' => $totalVisits,
                'total_appointments' => $totalAppointments,
                'active_prescriptions' => $activePrescriptions,
                'open_referrals' => $openReferrals,
                'forms_issued' => $formsIssued,
                'health_record_status' => $hasHealthRecord ? 'Complete' : 'Not completed',
                'next_appointment' => $nextAppt ? [
                    'id' => $nextAppt->id,
                    'appt_date' => $nextAppt->appt_date,
                    'appt_time' => $nextAppt->appt_time,
                    'checkup_date' => $nextAppt->checkup_date,
                    'checkup_time' => $nextAppt->checkup_time,
                    'service_type' => $nextAppt->service_type,
                    'status' => $nextAppt->status,
                    'reason' => $nextAppt->reason,
                ] : null,
                'recent_visits' => $recentVisits,
                'appointment_trends' => $appointmentTrends,
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/student/visits
    // -----------------------------------------------------------------------
    public function visits(Request $request): JsonResponse
    {
        $studentId = $this->helper->resolveStudentId((string) $request->query('student_no', ''));
        if (! $studentId) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $visits = Student::findOrFail($studentId)
            ->clinicVisits()
            ->withoutSyntheticDemo()
            ->with([
                'nurse:id,last_name,first_name',
                'dentalRecord',
            ])
            ->latest('visit_date')
            ->latest('created_at')
            ->get();

        return response()->json(['success' => true, 'visits' => $visits]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/student/appointments
    // -----------------------------------------------------------------------
    public function appointments(Request $request): JsonResponse
    {
        $identity = $request->attributes->get('clinic_identity');
        abort_unless(is_array($identity) && ($identity['role'] ?? null) === 'student', 401, 'Authentication required.');
        $studentId = (int) $identity['id'];

        $appointments = Student::findOrFail($studentId)
            ->appointments()
            ->withoutSyntheticDemo()
            ->latest('appt_date')
            ->latest('appt_time')
            ->latest('created_at')
            ->latest('id')
            ->get();

        return response()->json(['success' => true, 'appointments' => $appointments]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/student/appointments
    // -----------------------------------------------------------------------
    public function saveAppointment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_type' => ['required', 'string', 'in:Medical Check-up,Dental Check-up,Medical Certificate'],
            'reason' => ['sometimes', 'nullable', 'string'],
        ]);

        $identity = $request->attributes->get('clinic_identity');
        abort_unless(is_array($identity) && ($identity['role'] ?? null) === 'student', 401, 'Authentication required.');
        $studentId = (int) $identity['id'];
        $student = Student::query()->findOrFail($studentId);

        $appointment = DB::transaction(function () use ($data, $studentId, $student): Appointment {
            $student = Student::query()->lockForUpdate()->findOrFail($studentId);
            return $student->appointments()->create([
                'appt_date' => null,
                'appt_time' => null,
                'preferred_time' => null,
                'availability_id' => null,
                'service_type' => $this->helper->sanitize($data['service_type'] ?? '', 100),
                'reason' => $data['reason'] ?? '',
                'status' => 'pending',
                'campus_id' => $student->campus_id,
            ]);
        });

        ClinicNotification::notifyStaffByRole(
            'nurse',
            'appointment',
            'New appointment request',
            'A student requested '.$appointment->service_type.'. The clinic check-up needs to be scheduled.',
            ['appointment_id' => $appointment->id],
            $student->campus_id,
        );
        ClinicNotification::notifyStaffByRole(
            'nurse_head',
            'appointment',
            'New appointment request',
            'A student requested '.$appointment->service_type.'. The clinic check-up needs to be scheduled.',
            ['appointment_id' => $appointment->id],
        );
        ClinicNotification::notifyStudent(
            $studentId,
            'appointment',
            'Appointment request submitted',
            'Your appointment request is waiting for clinic review. The clinic will schedule your initial check-up.',
            ['appointment_id' => $appointment->id],
        );

        return response()->json(['success' => true, 'id' => $appointment->id]);
    }

    // -----------------------------------------------------------------------
    // DELETE /api/clinic/student/appointments/{id}
    // -----------------------------------------------------------------------
    public function cancelAppointment(Request $request, int $id): JsonResponse
    {
        $identity = $request->attributes->get('clinic_identity');
        abort_unless(is_array($identity) && ($identity['role'] ?? null) === 'student', 401, 'Authentication required.');

        Appointment::query()
            ->where('student_id', $identity['id'])
            ->findOrFail($id)
            ->update(['status' => 'deleted']);

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/student/medications
    // -----------------------------------------------------------------------
    public function medications(Request $request): JsonResponse
    {
        $studentId = $this->helper->resolveStudentId((string) $request->query('student_no', ''));
        if (! $studentId) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $medications = Student::findOrFail($studentId)
            ->medications()
            ->with('prescribedBy:id,last_name,first_name')
            ->latest()
            ->get();

        return response()->json(['success' => true, 'medications' => $medications]);
    }

    public function prescriptionForms(Request $request, PrescriptionPdfService $pdfService): JsonResponse
    {
        $identity = $request->attributes->get('clinic_identity');
        $student = Student::query()->findOrFail($identity['id']);
        $forms = FormCertificate::query()
            ->with('student')
            ->whereBelongsTo($student)
            ->whereIn('form_type', ['physician_prescription', 'dentist_prescription'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(function (FormCertificate $form) use ($pdfService): array {
                $hasPdf = $pdfService->ensureStored($form, $form->student);

                return [
                    'id' => $form->id,
                    'form_type' => $form->form_type,
                    'form_data' => $form->form_data,
                    'created_at' => $form->created_at,
                    'has_pdf' => $hasPdf,
                ];
            });

        return response()->json(['success' => true, 'prescription_forms' => $forms]);
    }

    public function downloadPrescriptionForm(Request $request, int $id, PrescriptionPdfService $pdfService): StreamedResponse
    {
        $identity = $request->attributes->get('clinic_identity');
        $student = Student::query()->findOrFail($identity['id']);
        $form = FormCertificate::query()
            ->with('student')
            ->whereKey($id)
            ->whereBelongsTo($student)
            ->whereIn('form_type', ['physician_prescription', 'dentist_prescription'])
            ->firstOrFail();

        abort_unless($pdfService->ensureStored($form, $student), 500);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->download($form->pdf_path, 'prescription-'.$form->id.'.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/student/medications
    // -----------------------------------------------------------------------
    public function saveMedication(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_no' => ['required', 'string'],
            'name' => ['required', 'string', 'max:150'],
            'dosage' => ['sometimes', 'nullable', 'string', 'max:80'],
            'schedule' => ['sometimes', 'nullable', 'string', 'max:100'],
            'purpose' => ['sometimes', 'nullable', 'string', 'max:150'],
            'school_kept' => ['sometimes', 'boolean'],
            'consent_on_file' => ['sometimes', 'boolean'],
            'prescribed_by' => ['sometimes', 'nullable', 'integer', 'exists:staff,id'],
        ]);

        $studentId = $this->helper->resolveStudentId($data['student_no']);
        if (! $studentId) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $medication = Medication::create([
            'student_id' => $studentId,
            'name' => $data['name'],
            'dosage' => $data['dosage'] ?? '',
            'schedule' => $data['schedule'] ?? '',
            'purpose' => $data['purpose'] ?? '',
            'school_kept' => (bool) ($data['school_kept'] ?? false),
            'consent_on_file' => (bool) ($data['consent_on_file'] ?? false),
            'prescribed_by' => $data['prescribed_by'] ?? null,
        ]);

        return response()->json(['success' => true, 'id' => $medication->id]);
    }

    // -----------------------------------------------------------------------
    // DELETE /api/clinic/student/medications/{id}
    // -----------------------------------------------------------------------
    public function deleteMedication(int $id): JsonResponse
    {
        Medication::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/student/search
    // -----------------------------------------------------------------------
    public function searchStudent(Request $request): JsonResponse
    {
        $studentNo = (string) $request->query('student_id', '');
        if (! $studentNo) {
            return response()->json(['error' => 'Student ID required'], 400);
        }

        $student = Student::with(['campus', 'latestHealthRecord'])
            ->where('student_id', strtoupper($studentNo))
            ->where('is_active', true)
            ->first();

        if (! $student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $record = $student->latestHealthRecord;
        $payload = array_merge(
            $student->makeHidden('password')->toArray(),
            [
                'allergy_detail' => $record?->allergy_detail,
                'hr_blood_type' => $record?->blood_type,
                'height_cm' => $record?->height_cm,
                'weight_kg' => $record?->weight_kg,
                'bp' => $record?->bp,
                'pr' => $record?->pr,
                'temp' => $record?->temp,
                'ec_name' => $record?->ec_name,
                'ec_relationship' => $record?->ec_relationship,
                'ec_address' => $record?->ec_address,
                'ec_contact' => $record?->ec_contact,
                'campus_name' => $student->campus?->name ?? '',
            ]
        );

        return response()->json(['success' => true, 'student' => $payload]);
    }
}
