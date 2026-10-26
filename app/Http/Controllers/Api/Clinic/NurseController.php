<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMedicalRecordRequest;
use App\Models\Appointment;
use App\Models\ClinicProvider;
use App\Models\ClinicNotification;
use App\Models\ClinicVisit;
use App\Models\FormCertificate;
use App\Models\HealthRecord;
use App\Models\Referral;
use App\Models\Staff;
use App\Models\Student;
use App\Services\AppointmentTriageService;
use App\Services\AuditService;
use App\Services\ClinicHelperService;
use App\Services\ReferralSpecialistResolver;
use App\Services\TriagePredictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NurseController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private ClinicHelperService $helper,
        private AdminController $admin,
        private ReferralSpecialistResolver $referralSpecialistResolver,
    ) {}

    // -----------------------------------------------------------------------
    // GET /api/clinic/nurse/search-student
    // -----------------------------------------------------------------------
    public function searchStudent(Request $request): JsonResponse
    {
        $campusId = $this->parseCampusId($request);
        $studentNo = (string) $request->query('student_id', '');
        $query = (string) $request->query('q', '');

        if ($studentNo) {
            $student = Student::with(['campus', 'latestHealthRecord'])
                ->where('student_id', strtoupper($studentNo))
                ->where('is_active', true)
                ->when($campusId, fn ($q) => $q->forCampus($campusId))
                ->first();

            if (! $student) {
                return response()->json(['error' => 'Student not found'], 404);
            }

            $payload = $student->makeHidden('password')->toArray();
            $record = $student->latestHealthRecord;
            $payload = array_merge($payload, [
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
            ]);

            return response()->json(['success' => true, 'student' => $payload]);
        }

        if ($query) {
            $students = Student::where('is_active', true)
                ->when($campusId, fn ($q) => $q->forCampus($campusId))
                ->where(function ($q) use ($query) {
                    $q->where('student_id', 'like', "%{$query}%")
                        ->orWhere('last_name', 'like', "%{$query}%")
                        ->orWhere('first_name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->take(20)
                ->get([
                    'id', 'student_id', 'last_name', 'first_name', 'middle_name',
                    'patient_type', 'email', 'course_year', 'department', 'section', 'contact',
                    'sex', 'age', 'blood_type',
                ]);

            return response()->json(['success' => true, 'students' => $students]);
        }

        return response()->json(['error' => 'Student ID or search query required'], 400);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/nurse/student-form/{studentNo}
    // -----------------------------------------------------------------------
    public function studentForForm(string $studentNo): JsonResponse
    {
        $student = Student::with(['latestHealthRecord', 'campus'])
            ->where('student_id', strtoupper($studentNo))
            ->where('is_active', true)
            ->first();

        if (! $student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $payload = $student->makeHidden('password')->toArray();
        $record = $student->latestHealthRecord;

        $lastVisit = $student->clinicVisits()
            ->withoutSyntheticDemo()
            ->latest('visit_date')
            ->latest('created_at')
            ->first([
                'visit_date',
                'reason',
                'treatment',
                'disposition',
                'bp',
                'temp',
                'pulse',
                'rr',
                'o2_sat',
                'notes',
            ]);

        if ($lastVisit) {
            $payload['last_visit_date'] = $lastVisit->visit_date;
            $payload['last_visit_reason'] = $lastVisit->reason;
            $payload['last_visit_treatment'] = $lastVisit->treatment;
            $payload['last_visit_disposition'] = $lastVisit->disposition;
            $payload['latest_vitals'] = [
                'recorded_at' => $lastVisit->visit_date?->toDateString(),
                'bp' => $lastVisit->bp,
                'temp' => $lastVisit->temp,
                'pulse' => $lastVisit->pulse,
                'rr' => $lastVisit->rr,
                'o2_sat' => $lastVisit->o2_sat,
                'notes' => $lastVisit->notes,
            ];
        } else {
            $payload['latest_vitals'] = null;
        }

        $payload = array_merge($payload, [
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
            'accident_detail' => $record?->accident_detail,
            'hospitalization_detail' => $record?->hospitalization_detail,
            'surgical_detail' => $record?->surgical_detail,
            'family_history' => $record?->family_history,
            'past_medical_history' => $record?->past_medical_history,
            'date_of_birth' => $record?->date_of_birth,
        ]);

        $medications = $student->medications()
            ->active()
            ->latest()
            ->get();

        $appointments = $student->appointments()
            ->active()
            ->orderBy('appt_date')
            ->orderBy('appt_time')
            ->get();

        return response()->json([
            'success' => true,
            'student' => $payload,
            'medications' => $medications,
            'appointments' => $appointments,
        ]);
    }

    public function medicalRecordCampuses(Request $request): JsonResponse
    {
        return $this->admin->medicalRecordCampuses($request);
    }

    public function searchMedicalRecordStudents(Request $request): JsonResponse
    {
        return $this->admin->searchMedicalRecordStudents($request);
    }

    public function medicalRecordsForStudent(Request $request, int $studentId): JsonResponse
    {
        return $this->admin->medicalRecordsForStudent($request, $studentId);
    }

    public function medicalRecords(Request $request): JsonResponse
    {
        return $this->admin->medicalRecords($request);
    }

    public function storeMedicalRecord(StoreMedicalRecordRequest $request): JsonResponse
    {
        return $this->admin->storeMedicalRecord($request);
    }

    public function dentalRecordCampuses(Request $request): JsonResponse
    {
        return $this->admin->dentalRecordCampuses($request);
    }

    public function dentalRecordsForCampus(Request $request): JsonResponse
    {
        return $this->admin->dentalRecordsForCampus($request);
    }

    public function dentalRecordsForStudent(Request $request, int $studentId): JsonResponse
    {
        return $this->admin->dentalRecordsForStudent($request, $studentId);
    }

    public function dentalRecord(Request $request, int $id): JsonResponse
    {
        return $this->admin->dentalRecord($request, $id);
    }

    public function storeDentalRecord(Request $request): JsonResponse
    {
        return $this->admin->storeDentalRecord($request);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/nurse/visits
    // -----------------------------------------------------------------------
    public function saveVisit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_no' => ['required', 'string'],
            'visit_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'visit_time' => ['sometimes', 'nullable', 'date_format:H:i,H:i:s'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'symptoms' => ['sometimes', 'nullable', 'string'],
            'treatment' => ['sometimes', 'nullable', 'string'],
            'bp' => ['sometimes', 'nullable', 'string', 'max:20'],
            'temp' => ['sometimes', 'nullable', 'numeric'],
            'pulse' => ['sometimes', 'nullable', 'integer'],
            'rr' => ['sometimes', 'nullable', 'integer'],
            'o2_sat' => ['sometimes', 'nullable', 'integer'],
            'physician_id' => ['sometimes', 'nullable', 'integer', 'exists:staff,id'],
            'disposition' => ['sometimes', 'nullable', 'string', 'max:150'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $studentId = $this->helper->resolveStudentId($data['student_no']);
        if (! $studentId) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $identity = $request->attributes->get('clinic_identity');
        $isAuthenticatedNurseHead = is_array($identity) && ($identity['role'] ?? null) === 'nurse_head';
        $staffId = $isAuthenticatedNurseHead ? (int) $identity['id'] : $this->parseStaffId($request);
        $campusId = $isAuthenticatedNurseHead
            ? Student::query()->whereKey($studentId)->value('campus_id')
            : $this->parseCampusId($request);

        $visit = ClinicVisit::create([
            'student_id' => $studentId,
            'visit_date' => $data['visit_date'] ?? today(),
            'visit_time' => $data['visit_time'] ?? now('Asia/Manila')->format('H:i:s'),
            'reason' => $this->helper->sanitize($data['reason'] ?? '', 255),
            'symptoms' => $data['symptoms'] ?? null,
            'treatment' => $data['treatment'] ?? null,
            'bp' => $this->helper->sanitize($data['bp'] ?? '', 20),
            'temp' => $this->helper->sqlNumeric($data['temp'] ?? null),
            'pulse' => $data['pulse'] ?? null,
            'rr' => $data['rr'] ?? null,
            'o2_sat' => $data['o2_sat'] ?? null,
            'nurse_id' => $staffId,
            'physician_id' => $data['physician_id'] ?? null,
            'disposition' => $this->helper->sanitize($data['disposition'] ?? '', 150),
            'notes' => $data['notes'] ?? null,
            'campus_id' => $campusId,
        ]);

        $this->audit->log($request, $staffId, 'staff', 'save_visit', 'clinic_visits', $visit->id, null, ['campus_id' => $campusId]);

        if ($request->input('type') === 'vitals') {
            $healthRecord = HealthRecord::where('student_id', $studentId)
                ->latest()
                ->first();

            $healthRecordData = array_filter([
                'record_date' => $visit->visit_date,
                'bp' => $visit->bp,
                'pr' => $visit->pulse,
                'temp' => $visit->temp,
                'findings_recommendation' => $visit->notes,
                'campus_id' => $campusId,
            ], fn ($value) => $value !== null && $value !== '');

            if ($healthRecord) {
                $healthRecord->update($healthRecordData);
            } else {
                HealthRecord::create(array_merge(
                    ['student_id' => $studentId],
                    $healthRecordData,
                ));
            }

            $notificationData = ['visit_id' => $visit->id, 'student_id' => $studentId];

            ClinicNotification::notifyStudent(
                $studentId,
                'vital_signs',
                'Vital Signs Recorded',
                'Your vital signs were recorded by clinic staff.',
                $notificationData,
            );

            Staff::active()
                ->whereIn('role', ['nurse_head'])
                ->forCampus($campusId)
                ->when($staffId, fn ($query) => $query->where('id', '!=', $staffId))
                ->pluck('id')
                ->each(fn (int $recipientId) => ClinicNotification::notifyStaff(
                    $recipientId,
                    'vital_signs',
                    'New Vital Signs Recorded',
                    'New vital signs are available for review.',
                    $notificationData,
                ));
        }

        return response()->json(['success' => true, 'id' => $visit->id]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/nurse/visits
    // -----------------------------------------------------------------------
    public function visits(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'days' => ['sometimes', 'integer', 'min:1', 'max:90'],
        ]);
        $days = isset($filters['days']) ? (int) $filters['days'] : null;
        $visitDate = $filters['date'] ?? today()->toDateString();
        $studentNo = (string) $request->query('student_id', '');
        $campusId = $this->parseCampusId($request);

        $query = ClinicVisit::withoutSyntheticDemo()
            ->with([
                'student:id,student_id,patient_type,last_name,first_name,middle_name,course_year,department,sex',
                'nurse:id,last_name,first_name',
            ])
            ->when($campusId, fn ($q) => $q->forCampus($campusId));

        if ($days !== null) {
            $today = today();
            $query->where('visit_date', '>=', $today->copy()->subDays($days - 1)->toDateString())
                ->where('visit_date', '<', $today->copy()->addDay()->toDateString());
        } else {
            $query->whereDate('visit_date', $visitDate);
        }

        if ($studentNo) {
            $sid = $this->helper->resolveStudentId($studentNo);
            if ($sid) {
                $query->where('student_id', $sid);
            }
        }

        $visits = $days !== null
            ? $query->orderByDesc('visit_date')->orderByDesc('visit_time')->get()
            : $query->orderBy('visit_time')->get();
        $visits->makeHidden('weight_kg');

        return response()->json(['success' => true, 'visits' => $visits]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/nurse/referrals
    // Forces the referral list to the authenticated nurse's registered campus.
    // -----------------------------------------------------------------------
    public function referrals(Request $request): JsonResponse
    {
        $campusId = $this->registeredCampusId($request);
        if ($campusId === null) {
            return response()->json(['error' => 'Your account is not assigned to a campus.'], 403);
        }

        $filters = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'in:pending,accepted,completed,cancelled'],
            'sort_order' => ['sometimes', 'in:asc,desc'],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $page = (int) ($filters['page'] ?? 1);
        $limit = (int) ($filters['limit'] ?? 10);

        $query = Referral::with([
            'student:id,student_id,patient_type,last_name,first_name,middle_name',
            'fromCampus:id,name',
            'toCampus:id,name',
            'referredBy:id,last_name,first_name',
        ])
            ->forCampus($campusId)
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when($search !== '', fn ($q) => $q->whereHas('student', fn ($studentQuery) => $studentQuery
                ->where('last_name', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('student_id', 'like', "%{$search}%")))
            ->orderBy('created_at', $sortOrder)
            ->orderBy('id', $sortOrder);

        $total = (clone $query)->count();
        $records = $query->skip(($page - 1) * $limit)->take($limit)->get();

        return response()->json([
            'success' => true,
            'records' => $this->referralSpecialistResolver->resolve($records->toArray()),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    public function referral(Request $request, int $id): JsonResponse
    {
        $campusId = $this->registeredCampusId($request);
        if ($campusId === null) {
            return response()->json(['error' => 'Your account is not assigned to a campus.'], 403);
        }

        $referral = Referral::with([
            'student:id,student_id,patient_type,last_name,first_name,middle_name,course_year,department,sex,age,blood_type',
            'fromCampus:id,name,code,contact',
            'toCampus:id,name,code,contact',
            'referredBy:id,first_name,last_name,role,license_no',
        ])
            ->forCampus($campusId)
            ->findOrFail($id);

        $referralData = $this->referralSpecialistResolver->resolve([$referral->toArray()])[0];

        return response()->json(['success' => true, 'referral' => $referralData]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/nurse/patients
    // -----------------------------------------------------------------------
    public function patients(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $campusId = $this->parseCampusId($request);

        $query = Student::active()->students()
            ->when($campusId, fn ($q) => $q->forCampus($campusId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('student_id', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%");
                });
            })
            ->with('latestHealthRecord')
            ->take(100);

        $patients = $query->get()->map(function (Student $s) {
            $hr = $s->latestHealthRecord;
            $row = $s->makeHidden('password')->toArray();

            return array_merge($row, [
                'blood_type' => $hr?->blood_type ?? $s->blood_type,
                'health_record_id' => $hr?->id,
                'health_record_status' => $hr?->status,
                'allergy_detail' => $hr?->allergy_detail,
                'bp' => $hr?->bp,
                'pr' => $hr?->pr,
                'temp' => $hr?->temp,
                'health_record_updated' => $hr?->updated_at,
            ]);
        });

        return response()->json(['success' => true, 'patients' => $patients]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/nurse/visits/{id}
    // -----------------------------------------------------------------------
    public function visit(int $id): JsonResponse
    {
        $visit = ClinicVisit::withoutSyntheticDemo()
            ->with([
                'student:id,student_id,last_name,first_name,middle_name,course_year,department,sex,age,blood_type',
                'nurse:id,last_name,first_name',
            ])->findOrFail($id);

        return response()->json(['success' => true, 'visit' => $visit->makeHidden('weight_kg')]);
    }

    // -----------------------------------------------------------------------
    // PATCH /api/clinic/nurse/visits/{id}
    // -----------------------------------------------------------------------
    public function updateVitalSigns(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'bp' => ['sometimes', 'nullable', 'string', 'max:20'],
            'temp' => ['sometimes', 'nullable', 'numeric'],
            'pulse' => ['sometimes', 'nullable', 'integer'],
            'rr' => ['sometimes', 'nullable', 'integer'],
            'o2_sat' => ['sometimes', 'nullable', 'integer'],
        ]);

        if ($data === []) {
            return response()->json(['error' => 'Provide at least one vital-sign field to update.'], 422);
        }

        /** @var array{id:int, role:string, campus_id:int|null} $identity */
        $identity = $request->attributes->get('clinic_identity');
        $visit = ClinicVisit::withoutSyntheticDemo()
            ->when($identity['campus_id'] !== null, fn ($query) => $query->where('campus_id', $identity['campus_id']))
            ->findOrFail($id);
        $oldData = $visit->only(array_keys($data));
        $visit->update($data);

        $this->audit->log(
            $request,
            $identity['id'],
            'staff',
            'update_vital_signs_record',
            'clinic_visits',
            $visit->id,
            $oldData,
            $data,
        );

        return response()->json(['success' => true, 'id' => $visit->id]);
    }

    // -----------------------------------------------------------------------
    // DELETE /api/clinic/nurse/visits/{id}
    // -----------------------------------------------------------------------
    public function deleteVitalSigns(Request $request, int $id): JsonResponse
    {
        /** @var array{id:int, role:string, campus_id:int|null} $identity */
        $identity = $request->attributes->get('clinic_identity');
        $visit = ClinicVisit::withoutSyntheticDemo()
            ->when($identity['campus_id'] !== null, fn ($query) => $query->where('campus_id', $identity['campus_id']))
            ->findOrFail($id);
        $visit->delete();

        $this->audit->log(
            $request,
            $identity['id'],
            'staff',
            'delete_vital_signs_record',
            'clinic_visits',
            $visit->id,
            $visit->only(['student_id', 'campus_id', 'visit_date', 'bp', 'temp', 'pulse', 'rr', 'o2_sat']),
        );

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/nurse/forms
    // -----------------------------------------------------------------------
    public function saveForm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'form_type' => ['required', 'string', 'max:50'],
            'student_no' => ['required', 'string'],
            'form_data' => ['sometimes', 'nullable', 'array'],
        ]);

        $identity = $request->attributes->get('clinic_identity');
        abort_unless(
            is_array($identity) && in_array($identity['role'] ?? null, ['nurse', 'nurse_head'], true),
            401,
            'Authentication required.',
        );

        $studentId = $this->helper->resolveStudentId($data['student_no']);
        if (! $studentId) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $student = Student::query()
            ->whereKey($studentId)
            ->when(
                is_numeric($identity['campus_id'] ?? null),
                fn ($query) => $query->where('campus_id', (int) $identity['campus_id']),
            )
            ->first();
        if (! $student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $staffId = (int) $identity['id'];
        $campusId = $student->campus_id;
        $designations = [
            'medical_certificate' => ['mc_sig_title' => 'School Physician'],
            'dental_certificate' => ['dc_sig_title' => 'School Dentist'],
            'physician_prescription' => ['rx_physician_role' => 'School Physician'],
            'dentist_prescription' => ['rxd_physician_role' => 'School Dentist'],
            'physician_referral' => ['rf_physician_role' => 'School Physician'],
            'dentist_referral' => ['rfd_physician_role' => 'School Dentist'],
        ];
        $formData = array_replace(
            $data['form_data'] ?? [],
            $designations[$data['form_type']] ?? [],
        );

        $form = DB::transaction(function () use ($data, $studentId, $staffId, $formData, $campusId): FormCertificate {
            $form = FormCertificate::create([
                'form_type' => $data['form_type'],
                'student_id' => $studentId,
                'staff_id' => $staffId,
                'form_data' => $formData,
                'status' => 'created',
                'campus_id' => $campusId,
            ]);

            if (in_array($form->form_type, ['physician_prescription', 'dentist_prescription'], true)) {
                $prescriptionType = $form->form_type === 'dentist_prescription' ? 'dental' : 'physician';
                ClinicNotification::notifyStudent(
                    $studentId,
                    'prescription_issued',
                    'New prescription available',
                    'A '.$prescriptionType.' prescription is available on your Prescriptions page.',
                    ['form_id' => $form->id, 'form_type' => $form->form_type],
                );
            }

            return $form;
        });

        $this->audit->log($request, $staffId, 'staff', 'create_form', 'forms_certificates', $form->id, null, ['form_type' => $data['form_type'], 'campus_id' => $campusId]);

        return response()->json(['success' => true, 'id' => $form->id]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/nurse/forms/{id}/print
    // -----------------------------------------------------------------------
    public function printForm(Request $request, int $id): JsonResponse
    {
        $form = FormCertificate::findOrFail($id);
        $form->increment('printed_count');
        $form->update(['status' => 'printed']);

        $this->audit->log($request, $this->parseStaffId($request), 'staff', 'print_form', 'forms_certificates', $id);

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/nurse/forms
    // -----------------------------------------------------------------------
    public function forms(Request $request): JsonResponse
    {
        $campusId = $this->parseCampusId($request);
        $formType = (string) $request->query('form_type', '');
        $studentNo = (string) $request->query('student_id', '');
        $limit = min(100, max(1, (int) $request->query('limit', 50)));

        $query = FormCertificate::with('student:id,student_id,last_name,first_name,middle_name')
            ->when($formType, fn ($q) => $q->where('form_type', $formType))
            ->when($campusId, fn ($q) => $q->forCampus($campusId))
            ->latest()
            ->take($limit);

        if ($studentNo) {
            $sid = $this->helper->resolveStudentId($studentNo);
            if ($sid) {
                $query->where('student_id', $sid);
            }
        }

        return response()->json(['success' => true, 'forms' => $query->get()]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/nurse/queue
    // Pending and confirmed appointments before the requested date remain
    // visible until they are completed, cancelled, or deleted. `days`
    // adds a future appointment window to the active queue.
    // -----------------------------------------------------------------------
    public function queue(Request $request, TriagePredictionService $triage): JsonResponse
    {
        $queueDate = $request->query('date', today()->toDateString());
        $days = (int) $request->query('days', 0);
        $campusId = $this->parseCampusId($request);

        $appointmentsInWindow = Appointment::query()
            ->whereDoesntHave('sourceReferrals')
            ->when($campusId, fn ($q) => $q->forCampus($campusId));

        $appointmentEndDate = now()->parse($queueDate)
            ->addDays(max(0, $days))
            ->toDateString();
        $appointmentWindow = function ($query) use ($queueDate, $appointmentEndDate, $days) {
            $query->where(function ($query) use ($queueDate) {
                $query->whereNull('appt_date')
                    ->whereIn('status', ['pending', 'accepted', 'checkup_scheduled', 'checkup_completed']);
            })->orWhereDate('appt_date', '<=', $queueDate);

            if ($days > 0) {
                $query->orWhereBetween('appt_date', [$queueDate, $appointmentEndDate]);
            }
            $query->orWhere(function ($query) use ($appointmentEndDate) {
                $query->where('status', 'checkup_scheduled')
                    ->whereDate('checkup_date', '<=', $appointmentEndDate);
            });
        };
        $appointmentsInWindow->where($appointmentWindow);

        $completedAppointments = (clone $appointmentsInWindow)
            ->where('status', 'completed')
            ->count();

        $appointmentItems = (clone $appointmentsInWindow)
            ->with([
                'student:id,student_id,patient_type,last_name,first_name,middle_name',
                'campus:id,name',
                'consultationRequest:id,appointment_id,triage_priority,triage_confidence,concern,medical_transcription_id',
                'consultationRequest.medicalTranscription:id,ai_draft,raw_transcript',
            ])
            ->active()
            ->get()
            ->map(function (Appointment $appointment): array {
                $consultation = $appointment->consultationRequest;
                $transcription = $consultation?->medicalTranscription;
                $transcript = trim((string) ($transcription?->ai_draft ?: $transcription?->raw_transcript));
                $audioOnlyRequest = $consultation?->concern === 'Audio recording submitted without written concern.';

                return [
                    'type' => 'appointment',
                    'id' => $appointment->id,
                    'consultation_id' => $consultation?->id,
                    '_created_at_sort' => ($appointment->created_at?->format('Y-m-d H:i:s.u') ?? '').sprintf('%020d', $appointment->id),
                    'created_at' => $appointment->created_at?->toDateTimeString(),
                    'date' => ($appointment->status === 'checkup_scheduled' ? $appointment->checkup_date : $appointment->appt_date)?->toDateString(),
                    'is_overdue' => ($appointment->status === 'checkup_scheduled' ? $appointment->checkup_date : $appointment->appt_date)?->lt(today()) ?? false,
                    'time' => $appointment->status === 'checkup_scheduled' ? $appointment->checkup_time : $appointment->appt_time,
                    'checkup_date' => $appointment->checkup_date?->toDateString(),
                    'checkup_time' => $appointment->checkup_time,
                    'appt_date' => $appointment->appt_date?->toDateString(),
                    'appt_time' => $appointment->appt_time,
                    'availability_id' => $appointment->availability_id,
                    'preferred_time' => $appointment->preferred_time,
                    'service_type' => $appointment->service_type,
                    'reason' => $audioOnlyRequest && $transcript !== '' ? $transcript : $appointment->reason,
                    'campus_id' => $appointment->campus_id,
                    'campus_name' => $appointment->campus?->name,
                    'priority' => $appointment->priorityLevel(),
                    'confidence' => $appointment->triage_confidence ?? $consultation?->triage_confidence,
                    'model_prediction' => data_get($appointment->triage_details, 'ml_prediction'),
                    'detail' => $appointment->service_type,
                    'status' => $appointment->status,
                    'student_id' => $appointment->student?->student_id,
                    'patient_type' => $appointment->student?->patient_type,
                    'last_name' => $appointment->student?->last_name,
                    'first_name' => $appointment->student?->first_name,
                    'middle_name' => $appointment->student?->middle_name,
                ];
            });

        $visitsInWindow = ClinicVisit::query()
            ->withoutSyntheticDemo()
            ->whereDate('visit_date', '<=', $queueDate)
            ->when($campusId, fn ($q) => $q->forCampus($campusId));

        $completedVisits = (clone $visitsInWindow)
            ->whereNotNull('disposition')
            ->count();

        $visitItems = (clone $visitsInWindow)
            ->with('student:id,student_id,patient_type,last_name,first_name,middle_name')
            ->pending()
            ->get()
            ->map(fn ($v) => [
                'type' => 'visit',
                'id' => $v->id,
                '_created_at_sort' => ($v->created_at?->format('Y-m-d H:i:s.u') ?? '').sprintf('%020d', $v->id),
                'date' => $v->visit_date?->toDateString(),
                'is_overdue' => $v->visit_date?->lt(today()) ?? false,
                'time' => $v->visit_time,
                'detail' => $v->reason,
                'status' => 'pending',
                'student_id' => $v->student?->student_id,
                'patient_type' => $v->student?->patient_type,
                'last_name' => $v->student?->last_name,
                'first_name' => $v->student?->first_name,
                'middle_name' => $v->student?->middle_name,
            ]);

        $queue = $appointmentItems->concat($visitItems)
            ->sortByDesc('_created_at_sort')
            ->map(function (array $item): array {
                unset($item['_created_at_sort']);

                return $item;
            })
            ->values();
        $queueByStatus = $queue->countBy('status');
        $queueSummary = [
            'in_queue' => $queue->count(),
            'waiting' => $queueByStatus->get('pending', 0) + $queueByStatus->get('accepted', 0),
            'confirmed' => $queueByStatus->get('confirmed', 0),
            'overdue' => $queue->where('is_overdue', true)->count(),
        ];

        return response()->json([
            'success' => true,
            'queue' => $queue,
            'model_accuracy' => $triage->modelAccuracy(),
            'summary' => $queueSummary,
            'completed_count' => $completedAppointments + $completedVisits,
        ]);
    }

    // -----------------------------------------------------------------------
    // PATCH /api/clinic/nurse/queue/{type}/{id}
    // -----------------------------------------------------------------------
    public function updateQueueStatus(Request $request, string $type, int $id): JsonResponse
    {
        /** @var array{id:int, role:string, campus_id:int|null} $identity */
        $identity = $request->attributes->get('clinic_identity');
        abort_unless($identity['campus_id'] !== null, 403);

        if ($type === 'appointment') {
            $data = $request->validate([
                'status' => ['required', 'string', 'in:pending,completed,cancelled'],
                'cancellation_reason' => ['required_if:status,cancelled', 'string', 'max:1000'],
            ]);
            $appointment = Appointment::query()
                ->where('campus_id', $identity['campus_id'])
                ->findOrFail($id);
            if ($data['status'] === 'completed' && $appointment->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'status' => 'Only provider appointments can be marked as completed.',
                ]);
            }
            if ($data['status'] === 'completed' && $appointment->status === 'confirmed'
                && ($appointment->appt_date === null || $appointment->appt_date->isFuture())) {
                throw ValidationException::withMessages([
                    'status' => 'A scheduled appointment cannot be completed before its scheduled date.',
                ]);
            }
            if ($data['status'] === 'pending' && $appointment->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Accepted or scheduled appointments cannot be returned to pending.',
                ]);
            }

            $oldStatus = $appointment->status;
            $oldSchedule = [
                'appt_date' => $appointment->appt_date?->toDateString(),
                'appt_time' => $appointment->appt_time,
            ];
            $newStatus = $data['status'];
            $updates = ['status' => $newStatus];
            if ($data['status'] === 'cancelled') {
                $updates['cancellation_reason'] = trim($data['cancellation_reason']);
            }
            $appointment->update($updates);
            $this->audit->log($request, $identity['id'], 'staff', 'update_appointment_status', 'appointments', $id, [
                'status' => $oldStatus,
            ], [
                'status' => $newStatus,
                'appt_date' => $updates['appt_date'] ?? $oldSchedule['appt_date'],
                'appt_time' => $updates['appt_time'] ?? $oldSchedule['appt_time'],
                'cancellation_reason' => $data['cancellation_reason'] ?? null,
            ]);
        } elseif ($type === 'visit') {
            $data = $request->validate([
                'disposition' => ['required', 'string', 'max:150'],
            ]);
            ClinicVisit::query()
                ->where('campus_id', $identity['campus_id'])
                ->findOrFail($id)
                ->update(['disposition' => $data['disposition']]);
        } else {
            return response()->json(['error' => 'Invalid type'], 400);
        }

        return response()->json(['success' => true]);
    }

    public function triageAppointment(Request $request, int $id, AppointmentTriageService $triage): JsonResponse
    {
        /** @var array{id:int, role:string, campus_id:int|null} $identity */
        $identity = $request->attributes->get('clinic_identity');
        abort_unless($identity['campus_id'] !== null, 403);

        $appointment = Appointment::query()
            ->where('campus_id', $identity['campus_id'])
            ->findOrFail($id);

        return $this->admin->triageAppointment($request, $appointment->id, $triage);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/nurse/stats
    // -----------------------------------------------------------------------
    public function nurseStats(Request $request): JsonResponse
    {
        $checkDate = $request->query('date', today()->toDateString());
        $campusId = $this->parseCampusId($request);
        $visitChartStartDate = today()->subDays(6);
        $visitChartEndDate = today();

        $visitCountsByDate = ClinicVisit::query()
            ->withoutSyntheticDemo()
            ->when($campusId, fn ($query) => $query->forCampus($campusId))
            ->whereDate('visit_date', '>=', $visitChartStartDate->toDateString())
            ->whereDate('visit_date', '<=', $visitChartEndDate->toDateString())
            ->select('visit_date')
            ->selectRaw('COUNT(*) as visit_count')
            ->groupBy('visit_date')
            ->get()
            ->mapWithKeys(fn (ClinicVisit $visit): array => [
                $visit->visit_date->toDateString() => (int) $visit->visit_count,
            ]);

        $dailyVisitVolume = collect(range(0, 6))
            ->map(function (int $dayOffset) use ($visitChartStartDate, $visitCountsByDate): array {
                $date = $visitChartStartDate->copy()->addDays($dayOffset)->toDateString();

                return [
                    'date' => $date,
                    'count' => (int) $visitCountsByDate->get($date, 0),
                ];
            })
            ->all();

        $todaysVisits = ClinicVisit::withoutSyntheticDemo()
            ->when($campusId, fn ($q) => $q->forCampus($campusId))
            ->whereDate('visit_date', $checkDate)
            ->count();

        $visitsThisMonth = ClinicVisit::withoutSyntheticDemo()
            ->when($campusId, fn ($q) => $q->forCampus($campusId))
            ->whereBetween('visit_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->count();

        $totalPatients = Student::active()->students()
            ->when($campusId, fn ($q) => $q->forCampus($campusId))
            ->count();

        $pendingAppts = Appointment::active()
            ->when($campusId, fn ($q) => $q->forCampus($campusId))
            ->whereDate('appt_date', $checkDate)
            ->count();

        $activeStaff = Staff::active()->clinicPersonnel()->count();

        $alertsCount = Student::active()->students()
            ->when($campusId, fn ($q) => $q->forCampus($campusId))
            ->whereHas('latestHealthRecord', function ($q) {
                $q->whereNotNull('allergy_detail')
                    ->where('allergy_detail', '!=', '')
                    ->whereRaw("LOWER(TRIM(allergy_detail)) != 'none'");
            })
            ->count();

        // Forms / certificates
        $formsQuery = FormCertificate::query()
            ->when($campusId, fn ($q) => $q->where('campus_id', $campusId));
        $formsTotal = (clone $formsQuery)->count();
        $formsIssued = (clone $formsQuery)->whereDate('created_at', $checkDate)->count();

        // Disposition summary
        $completedToday = $todaysVisits > 0
            ? ClinicVisit::withoutSyntheticDemo()
                ->when($campusId, fn ($q) => $q->forCampus($campusId))
                ->whereDate('visit_date', $checkDate)
                ->whereIn('disposition', ['Treated and sent home', 'Follow-up after 3 days', 'Follow-up after 1 week'])
                ->count()
            : 0;

        $waitingAppointments = Appointment::when($campusId, fn ($q) => $q->forCampus($campusId))->where('status', 'pending')->count();
        $acceptedAppointments = Appointment::when($campusId, fn ($q) => $q->forCampus($campusId))->where('status', 'accepted')->count();
        $checkupScheduledAppointments = Appointment::when($campusId, fn ($q) => $q->forCampus($campusId))->where('status', 'checkup_scheduled')->count();
        $checkupCompletedAppointments = Appointment::when($campusId, fn ($q) => $q->forCampus($campusId))->where('status', 'checkup_completed')->count();
        $apptByStatus = [
            'pending' => $waitingAppointments + $acceptedAppointments + $checkupScheduledAppointments + $checkupCompletedAppointments,
            'waiting' => $waitingAppointments,
            'accepted' => $acceptedAppointments,
            'checkup_scheduled' => $checkupScheduledAppointments,
            'checkup_completed' => $checkupCompletedAppointments,
            'confirmed' => Appointment::when($campusId, fn ($q) => $q->forCampus($campusId))->where('status', 'confirmed')->count(),
            'completed' => Appointment::when($campusId, fn ($q) => $q->forCampus($campusId))->where('status', 'completed')->count(),
            'cancelled' => Appointment::when($campusId, fn ($q) => $q->forCampus($campusId))->where('status', 'cancelled')->count(),
            'deleted' => Appointment::when($campusId, fn ($q) => $q->forCampus($campusId))->where('status', 'deleted')->count(),
        ];

        $recentVisits = ClinicVisit::withoutSyntheticDemo()
            ->with('student:id,student_id,last_name,first_name,middle_name')
            ->when($campusId, fn ($q) => $q->forCampus($campusId))
            ->whereDate('visit_date', $checkDate)
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'stats' => [
                'total_patients' => $totalPatients,
                'todays_visits' => $todaysVisits,
                'visits_this_month' => $visitsThisMonth,
                'pending_appointments' => $pendingAppts,
                'active_staff' => $activeStaff,
                'alerts_count' => $alertsCount,
                'forms_issued' => $formsIssued,
                'forms_total' => $formsTotal,
                'completed_today' => $completedToday,
                'appointments_by_status' => $apptByStatus,
                'recent_visits' => $recentVisits,
                'daily_visit_volume' => $dailyVisitVolume,
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/nurse/signing-personnel
    // -----------------------------------------------------------------------
    public function signingPersonnel(Request $request): JsonResponse
    {
        $campusId = $this->parseCampusId($request) ?? $this->registeredCampusId($request);
        $providers = ClinicProvider::query()
            ->active()
            ->when($campusId, function ($query) use ($campusId) {
                $query->where(function ($providerQuery) use ($campusId) {
                    $providerQuery->whereNull('campus_id')->orWhere('campus_id', $campusId);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'campus_id', 'provider_type', 'designation', 'license_no'])
            ->groupBy('provider_type');
        $personnel = Staff::query()
            ->active()
            ->where('role', 'nurse')
            ->when($campusId = $this->parseCampusId($request), function ($query) use ($campusId) {
                $query->where('campus_id', $campusId);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'role', 'license_no', 'specialization']);

        return response()->json([
            'success' => true,
            'personnel' => [
                'physician' => ($providers->get('physician') ?? collect())->values(),
                'dentist' => ($providers->get('dentist') ?? collect())->values(),
                'nurse' => $personnel->values(),
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function parseCampusId(Request $request): ?int
    {
        $raw = $request->query('staff_campus_id') ?? $request->query('campus_id');

        return is_numeric($raw) ? (int) $raw : null;
    }

    /**
     * Return the campus assigned to the authenticated nurse.
     */
    private function registeredCampusId(Request $request): ?int
    {
        $identity = $request->attributes->get('clinic_identity');
        $campusId = is_array($identity) ? ($identity['campus_id'] ?? null) : null;

        return is_numeric($campusId) ? (int) $campusId : null;
    }

    private function parseStaffId(Request $request): ?int
    {
        $raw = $request->query('staff_id');

        return is_numeric($raw) ? (int) $raw : null;
    }
}
