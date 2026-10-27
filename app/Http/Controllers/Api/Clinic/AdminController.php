<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMedicalRecordRequest;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\ClinicService;
use App\Models\ClinicProvider;
use App\Models\ClinicVisit;
use App\Models\ConsultationRequest;
use App\Models\DentalRecord;
use App\Models\FormCertificate;
use App\Models\HealthRecord;
use App\Models\Medication;
use App\Models\MlAnomalyLog;
use App\Models\MlTrafficPrediction;
use App\Models\MlTriageLog;
use App\Models\Referral;
use App\Models\Staff;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Services\AppointmentTriageService;
use App\Services\AuditService;
use App\Services\ClinicAiModelCatalog;
use App\Support\CampusCourseCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function __construct(private AuditService $audit) {}

    private function recordCampusId(Request $request): ?int
    {
        $identity = $request->attributes->get('clinic_identity', []);
        $campusId = $identity['campus_id'] ?? null;

        if (($identity['role'] ?? null) === 'nurse') {
            abort_unless($campusId !== null, 403, 'Nurse account must be assigned to a campus.');
        }

        return $campusId === null ? null : (int) $campusId;
    }

    private function assertRecordCampus(Request $request, int $requestedCampusId): ?int
    {
        $campusId = $this->recordCampusId($request);
        abort_unless(
            $campusId === null || $campusId === $requestedCampusId,
            403,
            'You can only access records for your assigned campus.',
        );

        return $campusId;
    }

    private function campusCourseOptions(int $campusId): array
    {
        $campusCode = Campus::query()->whereKey($campusId)->value('code');

        return $campusCode ? CampusCourseCatalog::courseNames($campusCode) : [];
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/admin/dashboard
    // Single endpoint that returns everything the dashboard needs in one shot.
    // Dashboard counts are read live so new student and visit records are reflected immediately.
    // -----------------------------------------------------------------------
    public function dashboard(Request $request): JsonResponse
    {
        $campusId = $this->parseCampusId($request);

        // ── Stats ─────────────────────────────────────────────────────────
        $stats = (function () use ($campusId) {
            $studentQuery = Student::active()->studentAccounts();
            $patientQuery = Student::active()->students();
            $allVisitsQuery = ClinicVisit::query();
            $appointmentQuery = Appointment::query();
            $referralQuery = Referral::query();

            if ($campusId) {
                $studentQuery->forCampus($campusId);
                $patientQuery->forCampus($campusId);
                $allVisitsQuery->forCampus($campusId);
                $appointmentQuery->forCampus($campusId);
                $referralQuery->forCampus($campusId);
            }

            $visitsQuery = (clone $allVisitsQuery)->withoutSyntheticDemo();
            // Student-only totals remain separate from the full patient roster.
            $totalStudents = (clone $studentQuery)->count();
            $totalPatients = (clone $patientQuery)->count();
            $studentsWithVisits = (clone $visitsQuery)
                ->whereHas('student', fn (Builder $query) => $query->studentAccounts())
                ->distinct('student_id')
                ->count('student_id');
            $totalVisits = (clone $visitsQuery)->count();

            $waitingAppointments = (clone $appointmentQuery)->where('status', 'pending')->count();
            $acceptedAppointments = (clone $appointmentQuery)->where('status', 'accepted')->count();
            $checkupScheduledAppointments = (clone $appointmentQuery)->where('status', 'checkup_scheduled')->count();
            $checkupCompletedAppointments = (clone $appointmentQuery)->where('status', 'checkup_completed')->count();
            $apptByStatus = [
                'pending' => $waitingAppointments + $acceptedAppointments + $checkupScheduledAppointments + $checkupCompletedAppointments,
                'waiting' => $waitingAppointments,
                'accepted' => $acceptedAppointments,
                'checkup_scheduled' => $checkupScheduledAppointments,
                'checkup_completed' => $checkupCompletedAppointments,
                'confirmed' => (clone $appointmentQuery)->where('status', 'confirmed')->count(),
                'completed' => (clone $appointmentQuery)->where('status', 'completed')->count(),
                'cancelled' => (clone $appointmentQuery)->where('status', 'cancelled')->count(),
                'deleted' => (clone $appointmentQuery)->where('status', 'deleted')->count(),
            ];

            $refByStatus = [
                'pending' => (clone $referralQuery)->where('status', 'pending')->count(),
                'accepted' => (clone $referralQuery)->where('status', 'accepted')->count(),
                'completed' => (clone $referralQuery)->where('status', 'completed')->count(),
                'cancelled' => (clone $referralQuery)->where('status', 'cancelled')->count(),
            ];

            $newStudentsThisWeek = (clone $studentQuery)->where('created_at', '>=', now()->startOfWeek())->count();

            return [
                // KPIs
                'total_patients' => $totalPatients,
                'students_with_visits' => $studentsWithVisits,
                'total_students' => $totalStudents,
                'total_records' => $totalVisits,
                'total_visits' => $totalVisits,
                'excluded_demo_visits' => (clone $allVisitsQuery)->count() - (clone $visitsQuery)->count(),
                'todays_visits' => (clone $visitsQuery)->today()->count(),
                'visits_this_month' => (clone $visitsQuery)
                    ->whereBetween('visit_date', [
                        now()->startOfMonth()->toDateString(),
                        now()->endOfMonth()->toDateString(),
                    ])
                    ->count(),
                'todays_appointments' => (clone $appointmentQuery)->whereDate('appt_date', today())->count(),
                'pending_appointments' => $apptByStatus['pending'],
                'pending_referrals' => $refByStatus['pending'],
                'urgent_referrals' => (clone $referralQuery)->urgent()->where('status', 'pending')->count(),
                'new_students_this_week' => $newStudentsThisWeek,
                // Status breakdowns (used by appt stat tiles and charts)
                'appointments_by_status' => $apptByStatus,
                'referrals_by_status' => $refByStatus,
                // Recent activity
                'recent_visits_30d' => (clone $visitsQuery)->where('visit_date', '>=', now()->subDays(30)->startOfDay())->count(),
                'recent_visits_7d' => (clone $visitsQuery)->where('visit_date', '>=', now()->subDays(7)->startOfDay())->count(),
            ];
        })();

        // ── Daily visits chart — last 13 days (cached 5 min) ────────────
        $chartKey = 'dashboard_chart_v3_campus_'.($campusId ?? 'all');
        $dailyVisits = Cache::remember($chartKey, 300, function () use ($campusId) {
            $query = ClinicVisit::query()
                ->withoutSyntheticDemo()
                ->when($campusId, fn (Builder $q) => $q->forCampus($campusId))
                ->where('visit_date', '>=', now()->subDays(12)->startOfDay());

            $driver = DB::getDriverName();
            if ($driver === 'pgsql') {
                $rawDate = 'visit_date::date';
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                $rawDate = 'DATE(visit_date)';
            } else {
                $rawDate = 'date(visit_date)';
            }

            $countsByDate = $query
                ->selectRaw("{$rawDate} as date_val, COUNT(*) as count")
                ->groupByRaw($rawDate)
                ->pluck('count', 'date_val')
                ->all();

            $result = [];
            for ($i = 12; $i >= 0; $i--) {
                $dateObj = now()->subDays($i);
                $dateStr = $dateObj->toDateString();
                $result[] = [
                    'day_label' => $dateObj->format('D'),
                    'date_val' => $dateStr,
                    'count' => (int) ($countsByDate[$dateStr] ?? 0),
                ];
            }

            return $result;
        });

        // ── Today's appointment preview (not cached — needs to be current) ──
        $todayAppointments = Appointment::with(['student:id,student_id,last_name,first_name', 'createdBy:id,first_name,last_name,role'])
            ->whereDate('appt_date', today())
            ->when($campusId, fn (Builder $q) => $q->forCampus($campusId))
            ->orderBy('appt_time')
            ->take(5)
            ->get(['id', 'student_id', 'appt_time', 'service_type', 'status', 'created_by']);

        // ── ML Traffic Prediction (DB-backed) ──
        $trafficPrediction = Schema::hasTable((new MlTrafficPrediction)->getTable())
            ? MlTrafficPrediction::query()
                ->when($campusId, fn (Builder $q) => $q->where('campus_id', $campusId))
                ->whereDate('prediction_date', today())
                ->first()
            : null;

        if ($trafficPrediction) {
            $predictedCount = $trafficPrediction->predicted_count;
        } else {
            // Intelligent moving average calculation from actual database clinic visits
            $past30dVisits = ClinicVisit::query()
                ->withoutSyntheticDemo()
                ->when($campusId, fn (Builder $q) => $q->forCampus($campusId))
                ->where('visit_date', '>=', now()->subDays(30)->startOfDay())
                ->count();
            $avgDaily = (int) round($past30dVisits / 30);
            $todaysApptCount = Appointment::query()
                ->when($campusId, fn (Builder $q) => $q->forCampus($campusId))
                ->whereDate('appt_date', today())
                ->count();
            $predictedCount = max($avgDaily, $todaysApptCount);
        }

        // ── ML Triage Classification (DB-backed from ml_triage_logs) ──
        $triageCounts = Schema::hasTable((new MlTriageLog)->getTable())
            ? MlTriageLog::query()
                ->when($campusId, fn (Builder $q) => $q->where('campus_id', $campusId))
                ->selectRaw('predicted_priority, COUNT(*) as count')
                ->groupBy('predicted_priority')
                ->pluck('count', 'predicted_priority')
                ->all()
            : [];

        $triageBreakdown = [
            [
                'priority' => 'Priority 1',
                'badge' => 'urgent',
                'count' => (int) ($triageCounts['Priority 1'] ?? 0),
                'condition' => 'Urgent / Emergency',
            ],
            [
                'priority' => 'Priority 2',
                'badge' => 'waiting',
                'count' => (int) ($triageCounts['Priority 2'] ?? 0),
                'condition' => 'Semi-urgent',
            ],
            [
                'priority' => 'Priority 3',
                'badge' => 'scheduled',
                'count' => (int) ($triageCounts['Priority 3'] ?? 0),
                'condition' => 'Routine / Non-urgent',
            ],
        ];

        // ── Operational Alert / Anomaly Detection (DB-backed from ml_anomaly_logs) ──
        $anomaly = Schema::hasTable((new MlAnomalyLog)->getTable())
            ? MlAnomalyLog::query()
                ->when($campusId, fn (Builder $q) => $q->where('campus_id', $campusId))
                ->where('is_anomaly', true)
                ->latest('log_date')
                ->first()
            : null;

        if ($anomaly) {
            $operationalAlert = [
                'is_alert' => true,
                'type' => 'danger',
                'title' => 'Operational Alert',
                'text' => $anomaly->description ?? 'Unusual patient volume detected. Consider staff reallocation.',
            ];
        } else {
            $operationalAlert = [
                'is_alert' => false,
                'type' => 'success',
                'title' => 'Operational Status',
                'text' => 'Normal patient flow across all campus clinics. No volume anomalies detected.',
            ];
        }

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'daily_visits' => $dailyVisits,
            'todays_appointments' => $todayAppointments,
            'predicted_traffic' => [
                'count' => $predictedCount,
                'meta' => 'ML-Assisted — Based on historical database visit patterns',
            ],
            'triage_breakdown' => $triageBreakdown,
            'operational_alert' => $operationalAlert,
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/admin/stats
    // -----------------------------------------------------------------------
    public function stats(Request $request): JsonResponse
    {
        $campusId = $this->parseCampusId($request);

        $studentQuery = Student::active()->studentAccounts();
        $patientQuery = Student::active()->students();
        $staffQuery = Staff::active();
        $allVisitsQuery = ClinicVisit::query();
        $appointmentQuery = Appointment::query();
        $referralQuery = Referral::query();

        if ($campusId) {
            $studentQuery->forCampus($campusId);
            $patientQuery->forCampus($campusId);
            $staffQuery->forCampus($campusId);
            $allVisitsQuery->forCampus($campusId);
            $appointmentQuery->forCampus($campusId);
            $referralQuery->forCampus($campusId);
        }

        $visitsQuery = (clone $allVisitsQuery)->withoutSyntheticDemo();
        $totalStudents = (clone $studentQuery)->count();
        $totalPatients = (clone $patientQuery)->count();
        $totalRecords = (clone $visitsQuery)->count();

        $waitingAppointments = (clone $appointmentQuery)->where('status', 'pending')->count();
        $acceptedAppointments = (clone $appointmentQuery)->where('status', 'accepted')->count();
        $checkupScheduledAppointments = (clone $appointmentQuery)->where('status', 'checkup_scheduled')->count();
        $checkupCompletedAppointments = (clone $appointmentQuery)->where('status', 'checkup_completed')->count();
        $apptByStatus = [
            'pending' => $waitingAppointments + $acceptedAppointments + $checkupScheduledAppointments + $checkupCompletedAppointments,
            'waiting' => $waitingAppointments,
            'accepted' => $acceptedAppointments,
            'checkup_scheduled' => $checkupScheduledAppointments,
            'checkup_completed' => $checkupCompletedAppointments,
            'confirmed' => (clone $appointmentQuery)->where('status', 'confirmed')->count(),
            'completed' => (clone $appointmentQuery)->where('status', 'completed')->count(),
            'cancelled' => (clone $appointmentQuery)->where('status', 'cancelled')->count(),
            'deleted' => (clone $appointmentQuery)->where('status', 'deleted')->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => [
                'total_students' => $totalStudents,
                'total_patients' => $totalPatients,
                'students_with_visits' => (clone $visitsQuery)
                    ->whereHas('student', fn (Builder $query) => $query->studentAccounts())
                    ->distinct('student_id')
                    ->count('student_id'),
                'total_records' => $totalRecords,
                'total_visits' => $totalRecords,
                'excluded_demo_visits' => (clone $allVisitsQuery)->count() - $totalRecords,
                'total_staff' => (clone $staffQuery)->count(),
                'todays_visits' => (clone $visitsQuery)->today()->count(),
                'pending_appointments' => $apptByStatus['pending'],
                'todays_appointments' => (clone $appointmentQuery)->whereDate('appt_date', today())->count(),
                'pending_referrals' => (clone $referralQuery)->where('status', 'pending')->count(),
                'appointments_by_status' => $apptByStatus,
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/admin/students
    // -----------------------------------------------------------------------
    public function students(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $limit = min(100, max(1, (int) $request->query('limit', 50)));
        $search = (string) $request->query('search', '');
        $campusId = $this->parseCampusId($request);

        $query = Student::active()->studentAccounts();

        if ($campusId) {
            $query->forCampus($campusId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('student_id', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $students = $query->with([
            'campus:id,name',
            'latestHealthRecord' => fn ($query) => $query->select([
                'health_records.id',
                'health_records.student_id',
                'health_records.age',
            ]),
        ])
            ->select([
                'id', 'student_id', 'patient_type', 'last_name', 'first_name', 'middle_name',
                'email', 'course_year', 'department', 'section', 'contact',
                'address', 'sex', 'civil_status', 'age', 'blood_type',
                'profile_picture', 'created_at', 'campus_id',
            ])
            ->selectSub(
                'SELECT COUNT(*) > 0 FROM health_records WHERE health_records.student_id = students.id',
                'has_health_record'
            )
            ->selectSub(
                ClinicVisit::query()
                    ->select('visit_date')
                    ->whereColumn('clinic_visits.student_id', 'students.id')
                    ->orderByDesc('visit_date')
                    ->limit(1),
                'last_clinic_visit'
            )
            ->selectSub(
                DentalRecord::query()
                    ->select('record_date')
                    ->whereColumn('dental_records.student_id', 'students.id')
                    ->orderByDesc('record_date')
                    ->limit(1),
                'last_dental_visit'
            )
            ->latest()
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $students->each(function (Student $student): void {
            if ($student->age === null && $student->latestHealthRecord?->age !== null) {
                $student->setAttribute('age', $student->latestHealthRecord->age);
            }

            $lastVisit = collect([
                $student->getAttribute('last_clinic_visit'),
                $student->getAttribute('last_dental_visit'),
            ])->filter()->sortDesc()->first();

            $student->setAttribute('last_visit', $lastVisit);
            $student->makeHidden(['last_clinic_visit', 'last_dental_visit']);
            $student->unsetRelation('latestHealthRecord');
        });

        return response()->json([
            'success' => true,
            'students' => $students,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function additionalPatientAccounts(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'all'])],
        ]);
        $page = (int) ($filters['page'] ?? 1);
        $limit = (int) ($filters['limit'] ?? 10);
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? 'active';

        $query = Student::query()
            ->additionalPatientAccounts()
            ->when($status === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when($this->parseCampusId($request), fn (Builder $query, int $campusId) => $query->forCampus($campusId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $accountQuery) use ($search): void {
                    $accountQuery->where('student_id', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%");
                });
            });

        $total = (clone $query)->count();
        $accounts = $query->with('campus:id,name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get([
                'id', 'student_id', 'patient_type', 'first_name', 'middle_name',
                'last_name', 'email', 'department', 'contact', 'campus_id',
                'is_active', 'created_at',
            ]);

        return response()->json([
            'success' => true,
            'patient_accounts' => $accounts,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function createAdditionalPatientAccount(Request $request): JsonResponse
    {
        $request->merge([
            'student_id' => strtoupper(trim((string) $request->input('student_id'))),
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);
        $data = $request->validate([
            'patient_type' => ['required', 'string', Rule::in(['instructor_faculty', 'staff'])],
            'student_id' => ['required', 'string', 'max:30', 'unique:students,student_id'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150', 'unique:students,email', Rule::unique('staff', 'email')],
            'password' => ['required', 'string', 'min:6', 'max:128', 'confirmed'],
            'department' => ['sometimes', 'nullable', 'string', 'max:120'],
            'contact' => ['sometimes', 'nullable', 'string', 'max:30'],
            'campus_id' => ['sometimes', 'nullable', 'integer', 'exists:campuses,id'],
        ]);
        $account = Student::create([
            'student_id' => strtoupper(trim($data['student_id'])),
            'patient_type' => $data['patient_type'],
            'first_name' => strip_tags(trim($data['first_name'])),
            'last_name' => strip_tags(trim($data['last_name'])),
            'middle_name' => strip_tags(trim($data['middle_name'] ?? '')),
            'email' => strtolower(trim($data['email'])),
            'password' => $data['password'],
            'department' => strip_tags(trim($data['department'] ?? '')),
            'contact' => strip_tags(trim($data['contact'] ?? '')),
            'role' => 'student',
            'is_active' => true,
            'campus_id' => $data['campus_id'] ?? null,
        ]);

        $this->audit->log($request, $this->adminId($request), 'admin', 'create_patient_account', 'students', $account->id, null, [
            'patient_type' => $account->patient_type,
            'student_id' => $account->student_id,
            'email' => $account->email,
        ]);

        return response()->json(['success' => true, 'id' => $account->id], 201);
    }

    public function updateAdditionalPatientAccount(Request $request, int $id): JsonResponse
    {
        $account = Student::query()->additionalPatientAccounts()->findOrFail($id);
        if ($request->has('email')) {
            $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        }
        $data = $request->validate([
            'patient_type' => ['sometimes', 'required', 'string', Rule::in(['instructor_faculty', 'staff'])],
            'first_name' => ['sometimes', 'required', 'string', 'max:80'],
            'last_name' => ['sometimes', 'required', 'string', 'max:80'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:80'],
            'email' => ['sometimes', 'required', 'email', 'max:150', Rule::unique('students', 'email')->ignore($account->id), Rule::unique('staff', 'email')],
            'password' => ['sometimes', 'nullable', 'string', 'min:6', 'max:128', 'confirmed'],
            'department' => ['sometimes', 'nullable', 'string', 'max:120'],
            'contact' => ['sometimes', 'nullable', 'string', 'max:30'],
            'campus_id' => ['sometimes', 'nullable', 'integer', 'exists:campuses,id'],
        ]);

        $oldData = $account->only(['patient_type', 'first_name', 'middle_name', 'last_name', 'email', 'department', 'contact', 'campus_id']);
        $updates = [];
        foreach (['patient_type', 'first_name', 'last_name', 'middle_name', 'department', 'contact'] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $field === 'patient_type'
                    ? $data[$field]
                    : strip_tags(trim((string) $data[$field]));
            }
        }
        if (array_key_exists('email', $data)) {
            $updates['email'] = strtolower(trim($data['email']));
        }
        if (array_key_exists('campus_id', $data)) {
            $updates['campus_id'] = $data['campus_id'];
        }
        if (! empty($data['password'])) {
            $updates['password'] = $data['password'];
        }

        if ($updates === []) {
            return response()->json(['success' => true, 'message' => 'No changes']);
        }

        $account->update($updates);
        $this->audit->log($request, $this->adminId($request), 'admin', 'update_patient_account', 'students', $account->id, $oldData, [
            'updated_fields' => array_keys($updates),
        ]);

        return response()->json(['success' => true, 'id' => $account->id]);
    }

    public function updateAdditionalPatientAccountStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);
        $account = Student::query()->additionalPatientAccounts()->findOrFail($id);
        $wasActive = $account->is_active;
        $account->update(['is_active' => $data['is_active']]);

        $this->audit->log($request, $this->adminId($request), 'admin', $data['is_active'] ? 'reactivate_patient_account' : 'deactivate_patient_account', 'students', $account->id, [
            'is_active' => $wasActive,
        ], [
            'is_active' => $account->is_active,
            'patient_type' => $account->patient_type,
        ]);

        return response()->json(['success' => true, 'is_active' => $account->is_active]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/admin/students/{studentNo}
    // -----------------------------------------------------------------------
    public function student(Request $request, string $studentNo): JsonResponse
    {
        $student = Student::studentAccounts()->with(['campus', 'latestHealthRecord'])
            ->where('student_id', strtoupper($studentNo))
            ->where('is_active', true)
            ->when($this->parseCampusId($request), fn (Builder $query, int $campusId) => $query->forCampus($campusId))
            ->firstOrFail();

        $record = $student->latestHealthRecord;

        return response()->json([
            'success' => true,
            'student' => array_merge(
                $student->makeHidden('password')->toArray(),
                [
                    'allergy_detail' => $record?->allergy_detail,
                    'hr_blood_type' => $record?->blood_type,
                ]
            ),
        ]);
    }

    // -----------------------------------------------------------------------
    // DELETE /api/clinic/admin/students/{studentNo}
    // -----------------------------------------------------------------------
    public function deleteStudent(Request $request, string $studentNo): JsonResponse
    {
        $student = Student::studentAccounts()->where('student_id', strtoupper($studentNo))
            ->where('is_active', true)
            ->firstOrFail();

        $student->delete();

        $this->audit->log($request, $this->adminId($request), 'admin', 'delete_student', 'students', $student->id, ['student_id' => $studentNo]);

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/admin/staff
    // -----------------------------------------------------------------------
    public function staff(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'between:1,100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'role' => ['sometimes', 'nullable', 'in:nurse,nurse_head,admin'],
        ]);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 10)));
        $search = trim((string) ($filters['search'] ?? ''));

        $query = Staff::query()
            ->with('campus')
            ->when($filters['role'] ?? null, fn (Builder $query, string $role) => $query->where('role', $role))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $staffQuery) use ($search): void {
                $like = '%'.$search.'%';
                $staffQuery->where('employee_id', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            }));

        $total = (clone $query)->count();
        $staffQuery = $query
            ->orderBy('role')
            ->orderBy('last_name');

        if ($request->hasAny(['page', 'limit', 'search', 'role'])) {
            $staffQuery->skip(($page - 1) * $limit)->take($limit);
        }

        $staff = $staffQuery->get([
            'id', 'employee_id', 'last_name', 'first_name', 'middle_name',
            'email', 'role', 'license_no', 'contact', 'is_active', 'created_at', 'campus_id',
        ]);

        return response()->json([
            'success' => true,
            'staff' => $staff,
            'pagination' => [
                'page' => $page,
                'limit' => $request->hasAny(['page', 'limit', 'search', 'role']) ? $limit : $total,
                'total' => $total,
                'pages' => $request->hasAny(['page', 'limit', 'search', 'role']) ? (int) ceil($total / $limit) : 1,
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/admin/staff
    // -----------------------------------------------------------------------
    public function addStaff(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'string', 'max:30', 'unique:staff,employee_id'],
            'last_name' => ['required', 'string', 'max:80'],
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150', 'unique:staff,email'],
            'password' => ['required', 'string', 'min:6', 'max:128', 'confirmed'],
            'role' => ['required', 'string', 'in:nurse,nurse_head,admin'],
            'license_no' => ['sometimes', 'nullable', 'string', 'max:50'],
            'contact' => ['sometimes', 'nullable', 'string', 'max:30'],
            'campus_id' => ['sometimes', 'nullable', 'integer', 'exists:campuses,id'],
        ]);

        $member = Staff::create([
            'employee_id' => strtoupper($data['employee_id']),
            'last_name' => strip_tags(trim($data['last_name'])),
            'first_name' => strip_tags(trim($data['first_name'])),
            'middle_name' => strip_tags(trim($data['middle_name'] ?? '')),
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'role' => $data['role'],
            'license_no' => strip_tags(trim($data['license_no'] ?? '')),
            'contact' => strip_tags(trim($data['contact'] ?? '')),
            'campus_id' => $data['campus_id'] ?? null,
        ]);

        $this->audit->log($request, $this->adminId($request), 'admin', 'add_staff', 'staff', $member->id);

        return response()->json(['success' => true, 'id' => $member->id]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/admin/staff/{employeeId}
    // -----------------------------------------------------------------------
    public function getStaff(string $employeeId): JsonResponse
    {
        $member = Staff::with('campus')
            ->where('employee_id', strtoupper($employeeId))
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'staff' => $member->makeHidden('password')->toArray(),
        ]);
    }

    // -----------------------------------------------------------------------
    // PUT /api/clinic/admin/staff/{employeeId}
    // Partial update — only fields present in the request are changed.
    // -----------------------------------------------------------------------
    public function updateStaff(Request $request, string $employeeId): JsonResponse
    {
        $member = Staff::where('employee_id', strtoupper($employeeId))->firstOrFail();

        $data = $request->validate([
            'employee_id' => ['sometimes', 'string', 'max:30', Rule::unique('staff', 'employee_id')->ignore($member->id)],
            'first_name' => ['sometimes', 'string', 'max:80'],
            'last_name' => ['sometimes', 'string', 'max:80'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:80'],
            'email' => ['sometimes', 'email', 'max:150', Rule::unique('staff', 'email')->ignore($member->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:6', 'max:128', 'confirmed'],
            'role' => ['sometimes', 'string', 'in:nurse,nurse_head,admin'],
            'license_no' => ['sometimes', 'nullable', 'string', 'max:50'],
            'contact' => ['sometimes', 'nullable', 'string', 'max:30'],
            'specialization' => ['sometimes', 'nullable', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
            'campus_id' => ['sometimes', 'nullable', 'integer', 'exists:campuses,id'],
        ]);

        $oldData = [
            'employee_id' => $member->employee_id,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'email' => $member->email,
            'role' => $member->role,
            'contact' => $member->contact,
            'specialization' => $member->specialization,
            'campus_id' => $member->campus_id,
        ];

        $updates = [];
        foreach (['employee_id', 'first_name', 'last_name', 'middle_name', 'license_no', 'contact', 'specialization'] as $f) {
            if (array_key_exists($f, $data)) {
                $updates[$f] = strip_tags(trim((string) $data[$f]));
            }
        }
        if (array_key_exists('employee_id', $data)) {
            $updates['employee_id'] = strtoupper($updates['employee_id']);
        }
        if (array_key_exists('email', $data)) {
            $updates['email'] = strtolower($data['email']);
        }
        if (array_key_exists('role', $data)) {
            $updates['role'] = $data['role'];
        }
        if (array_key_exists('is_active', $data)) {
            $updates['is_active'] = (bool) $data['is_active'];
        }
        if (array_key_exists('campus_id', $data)) {
            $updates['campus_id'] = $data['campus_id'];
        }
        if (! empty($data['password'])) {
            $updates['password'] = $data['password'];
        }

        if ($updates === []) {
            return response()->json(['success' => true, 'message' => 'No changes']);
        }

        $member->update($updates);

        $this->audit->log($request, $this->adminId($request), 'admin', 'update_staff', 'staff', $member->id, $oldData, [
            'updated_fields' => array_keys($updates),
        ]);

        return response()->json(['success' => true, 'id' => $member->id]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/admin/students
    // -----------------------------------------------------------------------
    public function createStudent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:30', 'unique:students,student_id'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150', 'unique:students,email'],
            'password' => ['required', 'string', 'min:6', 'max:128', 'confirmed'],
            'course_year' => ['sometimes', 'nullable', 'string', 'max:100'],
            'department' => ['sometimes', 'nullable', 'string', 'max:120'],
            'section' => ['sometimes', 'nullable', 'string', 'max:30'],
            'contact' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sex' => ['sometimes', 'nullable', 'string', 'max:20'],
            'civil_status' => ['sometimes', 'nullable', 'string', 'max:20'],
            'age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:150'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'blood_type' => ['sometimes', 'nullable', 'string', 'max:10'],
            'campus_id' => ['sometimes', 'nullable', 'integer', 'exists:campuses,id'],
        ]);

        $student = Student::create([
            'student_id' => strtoupper($data['student_id']),
            'first_name' => strip_tags(trim($data['first_name'])),
            'last_name' => strip_tags(trim($data['last_name'])),
            'middle_name' => strip_tags(trim($data['middle_name'] ?? '')),
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'course_year' => strip_tags(trim($data['course_year'] ?? '')),
            'department' => strip_tags(trim($data['department'] ?? '')),
            'section' => strip_tags(trim($data['section'] ?? '')),
            'contact' => strip_tags(trim($data['contact'] ?? '')),
            'address' => strip_tags(trim($data['address'] ?? '')),
            'sex' => strip_tags(trim($data['sex'] ?? '')),
            'civil_status' => strip_tags(trim($data['civil_status'] ?? '')),
            'age' => $data['age'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'blood_type' => strtoupper(strip_tags(trim($data['blood_type'] ?? ''))),
            'role' => 'student',
            'patient_type' => 'student',
            'is_active' => true,
            'campus_id' => $data['campus_id'] ?? null,
        ]);

        $this->audit->log($request, $this->adminId($request), 'admin', 'create_student', 'students', $student->id, null, [
            'student_id' => $student->student_id,
            'email' => $student->email,
        ]);

        return response()->json(['success' => true, 'id' => $student->id, 'student_id' => $student->student_id]);
    }

    // -----------------------------------------------------------------------
    // PUT /api/clinic/admin/students/{studentNo}
    // Partial update — only fields present in the request are changed.
    // -----------------------------------------------------------------------
    public function updateStudent(Request $request, string $studentNo): JsonResponse
    {
        $student = Student::studentAccounts()->where('student_id', strtoupper($studentNo))->firstOrFail();

        $data = $request->validate([
            'student_id' => ['sometimes', 'string', 'max:30', Rule::unique('students', 'student_id')->ignore($student->id)],
            'first_name' => ['sometimes', 'string', 'max:80'],
            'last_name' => ['sometimes', 'string', 'max:80'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:80'],
            'email' => ['sometimes', 'email', 'max:150', Rule::unique('students', 'email')->ignore($student->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:6', 'max:128', 'confirmed'],
            'course_year' => ['sometimes', 'nullable', 'string', 'max:100'],
            'department' => ['sometimes', 'nullable', 'string', 'max:120'],
            'section' => ['sometimes', 'nullable', 'string', 'max:30'],
            'contact' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sex' => ['sometimes', 'nullable', 'string', 'max:20'],
            'civil_status' => ['sometimes', 'nullable', 'string', 'max:20'],
            'age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:150'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'blood_type' => ['sometimes', 'nullable', 'string', 'max:10'],
            'campus_id' => ['sometimes', 'nullable', 'integer', 'exists:campuses,id'],
        ]);

        $oldData = [
            'student_id' => $student->student_id,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'email' => $student->email,
            'contact' => $student->contact,
            'department' => $student->department,
            'course_year' => $student->course_year,
            'campus_id' => $student->campus_id,
        ];

        // Normalize fields the way createStudent does
        $updates = [];
        foreach (['student_id', 'first_name', 'last_name', 'middle_name', 'course_year', 'department', 'section', 'contact', 'address', 'sex', 'civil_status'] as $f) {
            if (array_key_exists($f, $data)) {
                $updates[$f] = strip_tags(trim((string) $data[$f]));
            }
        }
        if (array_key_exists('student_id', $data)) {
            $updates['student_id'] = strtoupper($updates['student_id']);
        }
        if (array_key_exists('email', $data)) {
            $updates['email'] = strtolower($data['email']);
        }
        if (array_key_exists('blood_type', $data)) {
            $updates['blood_type'] = strtoupper(strip_tags(trim((string) $data['blood_type'])));
        }
        foreach (['age', 'date_of_birth', 'campus_id'] as $f) {
            if (array_key_exists($f, $data)) {
                $updates[$f] = $data[$f];
            }
        }
        // Only set the password if a non-empty value was provided
        if (! empty($data['password'])) {
            $updates['password'] = $data['password'];
        }

        if ($updates === []) {
            return response()->json(['success' => true, 'message' => 'No changes']);
        }

        $student->update($updates);

        $this->audit->log($request, $this->adminId($request), 'admin', 'update_student', 'students', $student->id, $oldData, [
            'updated_fields' => array_keys($updates),
        ]);

        return response()->json(['success' => true, 'id' => $student->id]);
    }

    // -----------------------------------------------------------------------
    // DELETE /api/clinic/admin/staff/{employeeId}
    // -----------------------------------------------------------------------
    public function deleteStaff(Request $request, string $employeeId): JsonResponse
    {
        $member = Staff::where('employee_id', strtoupper($employeeId))->firstOrFail();
        $member->delete();

        $this->audit->log($request, $this->adminId($request), 'admin', 'delete_staff', 'staff', $member->id, ['employee_id' => $employeeId]);

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/admin/appointments
    // -----------------------------------------------------------------------
    public function appointments(Request $request): JsonResponse
    {
        $campusId = $this->parseCampusId($request);
        $studentCampusId = $this->parseCampusId($request, 'student_campus_id');
        $filters = $request->validate([
            'appointment_group' => ['sometimes', 'in:upcoming,history'],
            'history_status' => ['sometimes', 'in:completed,cancelled,deleted'],
            'status' => ['sometimes', 'in:pending,accepted,checkup_scheduled,checkup_completed,confirmed,completed,cancelled,deleted'],
            'priority' => ['sometimes', 'nullable', 'in:high,medium,low'],
            'sort_order' => ['sometimes', 'in:asc,desc'],
        ]);
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $appointmentGroup = $filters['appointment_group'] ?? null;
        $historyStatus = $filters['history_status'] ?? null;
        $status = $filters['status'] ?? null;
        $priority = $filters['priority'] ?? null;
        $priorityValues = match ($priority) {
            'high' => ['Priority 1', 'high', 'High'],
            'medium' => ['Priority 2', 'medium', 'Medium'],
            'low' => ['Priority 3', 'low', 'Low'],
            default => [],
        };
        $page = max(1, (int) $request->query('page', 1));
        $limit = min(100, max(1, (int) $request->query('limit', 10)));
        $search = (string) $request->query('search', '');

        $query = Appointment::with([
            'student:id,student_id,patient_type,last_name,first_name,middle_name,course_year,department,campus_id',
            'createdBy:id,first_name,last_name,role',
            'assignedProfessional:id,first_name,last_name,role',
            'consultationRequest:id,appointment_id,physician_id,triage_priority,triage_confidence',
            'consultationRequest.physician:id,first_name,last_name,role',
        ])
            ->when(
                $request->attributes->get('exclude_referred_appointments'),
                fn ($q) => $q->whereDoesntHave('sourceReferrals'),
            )
            ->when($status, fn ($q, $v) => $q->where('status', $v))
            ->when($appointmentGroup === 'upcoming', fn ($q) => $q->whereIn('status', ['pending', 'accepted', 'checkup_scheduled', 'checkup_completed', 'confirmed']))
            ->when($appointmentGroup === 'history', fn ($q) => $q->whereIn('status', ['completed', 'cancelled', 'deleted']))
            ->when($appointmentGroup === 'history', fn (Builder $q) => $q->withoutSyntheticDemo())
            ->when($appointmentGroup === 'history' && $historyStatus, fn ($q) => $q->where('status', $historyStatus))
            ->when($priorityValues !== [], fn ($q) => $q->where(function (Builder $priorityQuery) use ($priorityValues): void {
                $priorityQuery->whereIn('triage_priority', $priorityValues)
                    ->orWhereHas(
                        'consultationRequest',
                        fn (Builder $consultationQuery) => $consultationQuery->whereIn('triage_priority', $priorityValues),
                    );
            }))
            ->when($request->query('date'), fn ($q, $v) => $q->where(fn (Builder $dateQuery) => $dateQuery
                ->whereDate('appt_date', $v)
                ->orWhereDate('checkup_date', $v)))
            ->when($campusId, fn ($q) => $q->forCampus($campusId))
            ->when($studentCampusId, fn ($q) => $q->whereHas('student', fn ($studentQuery) => $studentQuery->where('campus_id', $studentCampusId)))
            ->when($search, fn ($q) => $q->whereHas('student', fn ($sq) => $sq
                ->where('last_name', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('student_id', 'like', "%{$search}%")))
            ->orderByRaw("CASE WHEN status IN ('checkup_scheduled', 'checkup_completed') THEN checkup_date ELSE appt_date END {$sortOrder}")
            ->orderByRaw("CASE WHEN status IN ('checkup_scheduled', 'checkup_completed') THEN checkup_time ELSE appt_time END {$sortOrder}")
            ->orderBy('created_at', $sortOrder)
            ->orderBy('id', $sortOrder);

        $total = $query->count();
        $appointments = $query->skip(($page - 1) * $limit)->take($limit)->get()
            ->each(function (Appointment $appointment): void {
                $professional = $appointment->assignedProfessional
                    ?? $appointment->consultationRequest?->physician;
                if (! $professional && in_array($appointment->createdBy?->role, ['nurse', 'nurse_head'], true)) {
                    $professional = $appointment->createdBy;
                }

                $professionalName = trim(($professional?->first_name ?? '').' '.($professional?->last_name ?? ''));
                $appointment->setAttribute('healthcare_professional', $professionalName !== '' ? $professionalName : 'Not assigned');
                $appointment->setAttribute('priority', $appointment->priorityLevel());
                $appointment->setAttribute('model_prediction', data_get($appointment->triage_details, 'ml_prediction'));
                $appointment->setAttribute(
                    'confidence',
                    $appointment->triage_confidence ?? $appointment->consultationRequest?->triage_confidence,
                );
                $appointment->unsetRelation('consultationRequest');
                $appointment->unsetRelation('createdBy');
                $appointment->unsetRelation('assignedProfessional');
            });

        return response()->json([
            'success' => true,
            'appointments' => $appointments,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function triageAppointment(Request $request, int $id, AppointmentTriageService $triage): JsonResponse
    {
        $appointment = Appointment::query()->findOrFail($id);
        $assessedAppointment = $triage->assess($appointment);
        $identity = $request->attributes->get('clinic_identity');

        $this->audit->log(
            $request,
            $identity['id'],
            'staff',
            'triage_appointment',
            'appointments',
            $appointment->id,
            null,
            ['triage_priority' => $assessedAppointment->triage_priority],
        );

        return response()->json([
            'success' => true,
            'priority' => $assessedAppointment->priorityLevel(),
            'confidence' => $assessedAppointment->triage_confidence,
            'details' => $assessedAppointment->triage_details,
        ]);
    }

    // -----------------------------------------------------------------------
    // PATCH /api/clinic/admin/appointments/{id}
    // -----------------------------------------------------------------------
    public function updateAppointmentStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:pending,completed,cancelled'],
        ]);

        $appointment = Appointment::findOrFail($id);
        abort_unless($data['status'] !== 'completed' || ($appointment->appt_date !== null && $appointment->appt_date->isPast()), 409, 'Only a past scheduled appointment can be completed.');
        $appointment->update(['status' => $data['status']]);

        $this->audit->log($request, $this->adminId($request), 'admin', 'update_appointment_status', 'appointments', $id, null, ['status' => $data['status']]);

        return response()->json(['success' => true]);
    }

    public function deleteReferral(Request $request, int $id): JsonResponse
    {
        $referral = Referral::query()->findOrFail($id);
        $oldData = [
            'referral_no' => $referral->referral_no,
            'student_id' => $referral->student_id,
            'status' => $referral->status,
        ];

        $referral->delete();

        $this->audit->log(
            $request,
            $this->adminId($request),
            'admin',
            'delete_referral',
            'referrals',
            $id,
            $oldData,
        );

        return response()->json(['success' => true]);
    }

    public function referralPrescription(int $id): JsonResponse
    {
        $referral = Referral::query()->findOrFail($id);
        $form = $referral->prescriptions()
            ->whereIn('form_type', ['physician_prescription', 'dentist_prescription'])
            ->latest()
            ->first();

        if (! $form) {
            return response()->json(['success' => true, 'prescription' => null]);
        }

        return response()->json([
            'success' => true,
            'prescription' => [
                'id' => $form->id,
                'form_type' => $form->form_type,
                'created_at' => $form->created_at,
                'has_pdf' => $form->pdf_path !== null && Storage::disk('local')->exists($form->pdf_path),
            ],
        ]);
    }

    public function downloadReferralPrescription(int $id): StreamedResponse
    {
        $referral = Referral::query()->findOrFail($id);
        $form = $referral->prescriptions()
            ->whereIn('form_type', ['physician_prescription', 'dentist_prescription'])
            ->latest()
            ->firstOrFail();

        abort_unless($form->pdf_path && Storage::disk('local')->exists($form->pdf_path), 404);

        return Storage::disk('local')->download($form->pdf_path, 'referral-prescription-'.$form->id.'.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function viewReferralPrescription(int $id): StreamedResponse
    {
        $referral = Referral::query()->findOrFail($id);
        $form = $referral->prescriptions()
            ->whereIn('form_type', ['physician_prescription', 'dentist_prescription'])
            ->latest()
            ->firstOrFail();

        abort_unless($form->pdf_path && Storage::disk('local')->exists($form->pdf_path), 404);

        return Storage::disk('local')->response($form->pdf_path, 'referral-prescription-'.$form->id.'.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/admin/records
    // -----------------------------------------------------------------------
    public function records(Request $request): JsonResponse
    {
        $type = $request->query('type', 'medical');
        $page = max(1, (int) $request->query('page', 1));
        $limit = min(100, max(1, (int) $request->query('limit', 10)));
        $search = (string) $request->query('search', '');
        $campusId = $this->parseCampusId($request);
        $studentCampusId = $this->parseCampusId($request, 'student_campus_id');
        $referralStatus = $type === 'referral'
            ? ($request->validate([
                'status' => ['sometimes', 'in:pending,accepted,completed,cancelled'],
            ])['status'] ?? null)
            : null;
        $sortOrder = $type === 'referral'
            ? ($request->validate(['sort_order' => ['sometimes', 'in:asc,desc']])['sort_order'] ?? 'desc')
            : 'desc';

        $studentSearch = function ($q) use ($search) {
            $q->whereHas('student', fn ($sq) => $sq
                ->where('last_name', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('student_id', 'like', "%{$search}%"));
        };

        if ($type === 'medical') {
            $query = ClinicVisit::with([
                'student:id,student_id,last_name,first_name,middle_name',
                'nurse:id,last_name,first_name',
                'dentalRecord',
            ])
                ->withClinicalDocumentation()
                ->withoutSyntheticDemo()
                ->when($campusId, fn ($q) => $q->forCampus($campusId))
                ->when($studentCampusId, fn ($q) => $q->whereHas('student', fn ($studentQuery) => $studentQuery->where('campus_id', $studentCampusId)))
                ->when($search, $studentSearch)
                ->latest('visit_date');

        } elseif ($type === 'vital') {
            $query = ClinicVisit::with([
                'student:id,student_id,last_name,first_name,middle_name',
            ])
                ->withoutSyntheticDemo()
                ->where(function ($q) {
                    $q->whereNotNull('bp')
                        ->orWhereNotNull('temp')
                        ->orWhereNotNull('pulse')
                        ->orWhereNotNull('o2_sat');
                })
                ->when($campusId, fn ($q) => $q->forCampus($campusId))
                ->when($studentCampusId, fn ($q) => $q->whereHas('student', fn ($studentQuery) => $studentQuery->where('campus_id', $studentCampusId)))
                ->when($search, $studentSearch)
                ->latest('visit_date');

        } elseif ($type === 'referral') {
            $query = Referral::with([
                'student:id,student_id,last_name,first_name,middle_name',
                'fromCampus:id,name',
                'toCampus:id,name',
                'referredBy:id,last_name,first_name',
            ])
                ->when($campusId, fn ($q) => $q->forCampus($campusId))
                ->when($studentCampusId, fn ($q) => $q->whereHas('student', fn ($studentQuery) => $studentQuery->where('campus_id', $studentCampusId)))
                ->when($referralStatus, fn ($q) => $q->where('status', $referralStatus))
                ->when($search, $studentSearch)
                ->orderBy('created_at', $sortOrder)
                ->orderBy('id', $sortOrder);

        } elseif ($type === 'prescription') {
            $query = Medication::with([
                'student:id,student_id,last_name,first_name,middle_name',
                'prescribedBy:id,last_name,first_name',
            ])
                ->when($campusId, fn ($q) => $q->forCampus($campusId))
                ->when($search, $studentSearch)
                ->latest();
        } else {
            $query = FormCertificate::with('student:id,student_id,last_name,first_name,middle_name')
                ->where('form_type', $type)
                ->when($campusId, fn ($q) => $q->forCampus($campusId))
                ->when($search, $studentSearch)
                ->latest();
        }

        $total = $query->count();
        $rows = $query->skip(($page - 1) * $limit)->take($limit)->get();
        if (in_array($type, ['medical', 'vital'], true)) {
            $rows->makeHidden('weight_kg');
        }

        return response()->json([
            'success' => true,
            'records' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function medicalRecordCampuses(Request $request): JsonResponse
    {
        $campusId = $this->recordCampusId($request);

        $campuses = Campus::query()
            ->active()
            ->when(
                $campusId !== null,
                fn (Builder $query) => $query->whereKey($campusId),
            )
            ->withCount([
                'students as students_count' => fn (Builder $query) => $query->active()->students(),
            ])
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'campuses' => $campuses]);
    }

    public function dentalRecordCampuses(Request $request): JsonResponse
    {
        $campusId = $this->recordCampusId($request);

        $campuses = Campus::query()
            ->active()
            ->when(
                $campusId !== null,
                fn (Builder $query) => $query->whereKey($campusId),
            )
            ->withCount('dentalRecords')
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'campuses' => $campuses]);
    }

    public function dentalRecordsForCampus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campus_id' => ['required', 'integer', 'exists:campuses,id'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'course' => ['nullable', 'string', 'max:120'],
        ]);
        $campusId = $this->assertRecordCampus($request, (int) $validated['campus_id']);
        $page = (int) ($validated['page'] ?? 1);
        $search = trim((string) ($validated['search'] ?? ''));
        $course = trim((string) ($validated['course'] ?? ''));

        $query = DentalRecord::query()
            ->where('campus_id', $validated['campus_id'])
            ->when(
                $campusId !== null,
                fn (Builder $query) => $query->where('campus_id', $campusId),
            )
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('student', function (Builder $studentQuery) use ($search): void {
                    $studentQuery->where('student_id', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%");
                });
            })
            ->when($course !== '', fn (Builder $query) => $query->whereHas(
                'student',
                fn (Builder $studentQuery) => $studentQuery->where('department', $course),
            ));

        $records = $query
            ->with([
                'student:id,student_id,last_name,first_name,middle_name,department',
                'dentist:id,first_name,last_name,license_no',
                'dentistProvider:id,name,license_no',
            ])
            ->latest('record_date')
            ->latest('id')
            ->paginate(20, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'records' => $records->items(),
            'course_options' => $this->campusCourseOptions((int) $validated['campus_id']),
            'pagination' => [
                'page' => $records->currentPage(),
                'pages' => $records->lastPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    public function dentalRecordsForStudent(Request $request, int $studentId): JsonResponse
    {
        $validated = $request->validate([
            'campus_id' => ['required', 'integer', 'exists:campuses,id'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $campusId = (int) $validated['campus_id'];
        $assignedCampusId = $this->assertRecordCampus($request, $campusId);
        $page = (int) ($validated['page'] ?? 1);

        $student = Student::query()
            ->whereKey($studentId)
            ->where('campus_id', $campusId)
            ->when(
                $assignedCampusId !== null,
                fn (Builder $query) => $query->where('campus_id', $assignedCampusId),
            )
            ->firstOrFail();

        $records = DentalRecord::query()
            ->where('student_id', $student->id)
            ->where('campus_id', $campusId)
            ->when(
                $assignedCampusId !== null,
                fn (Builder $query) => $query->where('campus_id', $assignedCampusId),
            )
            ->with([
                'dentist:id,first_name,last_name,license_no',
                'dentistProvider:id,name,license_no',
            ])
            ->latest('record_date')
            ->latest('id')
            ->paginate(20, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'student' => $student->only(['id', 'student_id', 'first_name', 'middle_name', 'last_name']),
            'records' => $records->items(),
            'pagination' => [
                'page' => $records->currentPage(),
                'pages' => $records->lastPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    public function dentalRecord(Request $request, int $id): JsonResponse
    {
        $campusId = $this->recordCampusId($request);
        $record = DentalRecord::query()
            ->with([
                'student:id,student_id,last_name,first_name,middle_name,campus_id,date_of_birth,age,course_year,department,address,contact',
                'campus:id,name',
                'dentist:id,first_name,last_name,license_no',
                'dentistProvider:id,name,license_no',
            ])
            ->whereKey($id)
            ->when(
                $campusId !== null,
                fn (Builder $query) => $query->where('campus_id', $campusId),
            )
            ->firstOrFail();

        return response()->json(['success' => true, 'record' => $record]);
    }

    public function storeDentalRecord(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'string', 'max:50'],
            'dentist_provider_id' => [
                'nullable',
                'integer',
                Rule::exists('clinic_providers', 'id')->where(fn ($query) => $query
                    ->where('provider_type', 'dentist')
                    ->where('is_active', true)),
            ],
            'record_date' => ['required', 'date'],
            'oral_exam_findings' => ['nullable', 'string', 'max:10000'],
            'tooth_chart' => ['nullable', 'array'],
            'tooth_chart.tooth_markings' => ['sometimes', 'array', 'max:100'],
            'tooth_chart.tooth_markings.*' => ['string', 'max:100'],
            'tooth_chart.oral_condition_markings' => ['sometimes', 'array', 'max:50'],
            'tooth_chart.oral_condition_markings.*' => ['string', 'max:100'],
        ]);
        $campusId = $this->recordCampusId($request);
        /** @var array{id:int, role:string, campus_id:int|null} $identity */
        $identity = $request->attributes->get('clinic_identity');
        $student = Student::query()
            ->active()
            ->students()
            ->where('student_id', $validated['student_id'])
            ->when(
                $campusId !== null,
                fn (Builder $query) => $query->where('campus_id', $campusId),
            )
            ->firstOrFail();

        if (isset($validated['dentist_provider_id'])) {
            $dentistProvider = ClinicProvider::query()
                ->active()
                ->whereKey($validated['dentist_provider_id'])
                ->where('provider_type', 'dentist')
                ->where(function (Builder $query) use ($student): void {
                    $query->whereNull('campus_id')
                        ->orWhere('campus_id', $student->campus_id);
                })
                ->first();
            if (! $dentistProvider) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'dentist_provider_id' => 'The selected dentist is not available for this campus.',
                ]);
            }
        }

        $record = DentalRecord::query()->create([
            'student_id' => $student->id,
            'dentist_provider_id' => $validated['dentist_provider_id'] ?? null,
            'campus_id' => $student->campus_id,
            'record_date' => $validated['record_date'],
            'oral_exam_findings' => $validated['oral_exam_findings'] ?? null,
            'tooth_chart' => $validated['tooth_chart'] ?? null,
        ]);

        $this->audit->log(
            $request,
            $identity['id'],
            $identity['role'],
            'create_dental_record',
            'dental_records',
            $record->id,
            null,
            ['student_id' => $student->id, 'campus_id' => $student->campus_id],
        );

        return response()->json([
            'success' => true,
            'message' => 'Dental record saved.',
            'record' => $record->load([
                'student:id,student_id,last_name,first_name,middle_name',
                'dentist:id,first_name,last_name,license_no',
                'dentistProvider:id,name,license_no',
            ]),
        ], 201);
    }

    public function searchMedicalRecordStudents(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campus_id' => ['required', 'integer', 'exists:campuses,id'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'course' => ['nullable', 'string', 'max:120'],
        ]);
        $campusId = $this->assertRecordCampus($request, (int) $validated['campus_id']);
        $page = (int) ($validated['page'] ?? 1);
        $limit = 24;
        $search = trim((string) ($validated['search'] ?? ''));
        $course = trim((string) ($validated['course'] ?? ''));

        $query = Student::query()
            ->active()
            ->students()
            ->where('campus_id', $validated['campus_id'])
            ->when(
                $campusId !== null,
                fn (Builder $query) => $query->where('campus_id', $campusId),
            )
            ->when($course !== '', fn (Builder $query) => $query->where('department', $course))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $studentQuery) use ($search): void {
                    $studentQuery->where('student_id', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%");
                });
            });

        $total = (clone $query)->count();
        $students = $query
            ->with([
                'latestHealthRecord' => fn (HasOne $query) => $query->select([
                    'health_records.id',
                    'health_records.student_id',
                    'health_records.age',
                    'health_records.date_of_birth',
                    'health_records.contact',
                    'health_records.course_year',
                ]),
            ])
            ->select([
                'id',
                'student_id',
                'last_name',
                'first_name',
                'middle_name',
                'date_of_birth',
                'age',
                'course_year',
                'department',
                'sex',
                'address',
                'contact',
                'campus_id',
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $students->each(fn (Student $student): Student => $this->fillMissingMedicalRecordDemographics($student));

        return response()->json([
            'success' => true,
            'students' => $students,
            'course_options' => $this->campusCourseOptions((int) $validated['campus_id']),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function medicalRecordsForStudent(Request $request, int $studentId): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $campusId = $this->recordCampusId($request);
        $student = Student::query()
            ->active()
            ->students()
            ->with([
                'campus:id,name',
                'latestHealthRecord' => fn (HasOne $query) => $query->select([
                    'health_records.id',
                    'health_records.student_id',
                    'health_records.age',
                    'health_records.date_of_birth',
                    'health_records.contact',
                    'health_records.course_year',
                ]),
            ])
            ->whereKey($studentId)
            ->when(
                $campusId !== null,
                fn (Builder $query) => $query->where('campus_id', $campusId),
            )
            ->firstOrFail();

        $this->fillMissingMedicalRecordDemographics($student);

        $visitEvents = $student->clinicVisits()
            ->withClinicalDocumentation()
            ->withoutSyntheticDemo()
            ->whereDoesntHave(
                'consultationRequests',
                fn (Builder $query) => $query->whereNotIn('status', ['completed', 'physician_approved']),
            )
            ->select(['id', 'visit_date as record_date', 'visit_time as record_time'])
            ->selectRaw("'visit' as record_type");
        $consultationEvents = ConsultationRequest::query()
            ->where('student_id', $student->id)
            ->whereNull('clinic_visit_id')
            ->whereIn('status', ['completed', 'physician_approved'])
            ->select(['id'])
            ->selectRaw('DATE(created_at) as record_date, TIME(created_at) as record_time, ? as record_type', ['consultation']);
        $events = DB::query()
            ->fromSub($visitEvents->unionAll($consultationEvents), 'medical_record_events')
            ->orderByDesc('record_date')
            ->orderByDesc('record_time')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'page', (int) ($validated['page'] ?? 1));

        $visitIds = $events->getCollection()
            ->where('record_type', 'visit')
            ->pluck('id');
        $visits = ClinicVisit::query()
            ->with([
                'nurse:id,first_name,last_name,role',
                'physician:id,first_name,last_name,role',
                'dentalRecord',
            ])
            ->whereKey($visitIds)
            ->get()
            ->keyBy('id');
        $visitConsultations = ConsultationRequest::query()
            ->with([
                'medicalTranscription:id,ai_draft,raw_transcript',
                'nurse:id,first_name,last_name,role',
                'physician:id,first_name,last_name,role',
            ])
            ->whereIn('clinic_visit_id', $visitIds)
            ->orderBy('id')
            ->get()
            ->keyBy('clinic_visit_id');

        $consultationIds = $events->getCollection()
            ->where('record_type', 'consultation')
            ->pluck('id');
        $consultations = ConsultationRequest::query()
            ->with([
                'medicalTranscription:id,ai_draft,raw_transcript',
                'nurse:id,first_name,last_name,role',
                'physician:id,first_name,last_name,role',
            ])
            ->whereKey($consultationIds)
            ->get()
            ->keyBy('id');

        $records = $events->getCollection()->map(function (object $event) use ($visits, $visitConsultations, $consultations): array {
            if ($event->record_type === 'visit') {
                $visit = $visits->get($event->id);
                $record = array_merge($visit->makeHidden('weight_kg')->toArray(), ['record_type' => 'visit']);
                $consultation = $visitConsultations->get($visit->id);
                $visitNurse = $this->medicalRecordStaff($visit->nurse);
                $visitPhysician = $this->medicalRecordStaff($visit->physician);
                $consultationNurse = $this->medicalRecordStaff($consultation?->nurse);
                $consultationPhysician = $this->medicalRecordStaff($consultation?->physician);

                $record['nurse'] = $visitNurse ?? $consultationNurse;
                $record['physician'] = $visitPhysician ?? $consultationPhysician;
                $record['notes'] = collect([
                    ($visitNurse || $visitPhysician) ? $visit->notes : null,
                    $consultationNurse ? $consultation?->staff_notes : null,
                ])->filter()->unique()->implode("\n") ?: null;

                if (! $consultation) {
                    return $record;
                }

                $isAudioOnly = $consultation->concern === 'Audio recording submitted without written concern.';
                $consultationTreatment = $this->consultationTreatment($consultation);
                $transcript = $consultation->medicalTranscription?->ai_draft
                    ?: $consultation->medicalTranscription?->raw_transcript;
                $record['consultation_status'] = Str::headline($consultation->status);
                $record['reason'] = $isAudioOnly && filled($transcript)
                    ? $transcript
                    : ($visit->reason ?: $consultation->concern);
                $record['symptoms'] = $visit->symptoms ?: $consultation->nurse_summary;
                $record['treatment'] = $visit->treatment ?: $consultationTreatment;

                return $record;
            }

            $consultation = $consultations->get($event->id);
            $isAudioOnly = $consultation->concern === 'Audio recording submitted without written concern.';
            $treatment = $this->consultationTreatment($consultation);

            return [
                'id' => $consultation->id,
                'record_type' => 'consultation',
                'visit_date' => $consultation->created_at?->toDateString(),
                'visit_time' => $consultation->created_at?->copy()->timezone('Asia/Manila')->format('H:i:s'),
                'reason' => $isAudioOnly
                    ? ($consultation->medicalTranscription?->ai_draft
                        ?: $consultation->medicalTranscription?->raw_transcript
                        ?: 'Voice consultation submitted.')
                    : $consultation->concern,
                'symptoms' => $consultation->nurse_summary,
                'treatment' => $treatment,
                'bp' => data_get($consultation->vitals, 'bp'),
                'temp' => data_get($consultation->vitals, 'temp'),
                'pulse' => data_get($consultation->vitals, 'pulse'),
                'rr' => data_get($consultation->vitals, 'rr'),
                'o2_sat' => data_get($consultation->vitals, 'o2_sat'),
                'nurse' => $this->medicalRecordStaff($consultation->nurse),
                'physician' => $this->medicalRecordStaff($consultation->physician),
                'notes' => $this->medicalRecordStaff($consultation->nurse)
                    ? $consultation->staff_notes
                    : null,
            ];
        });

        return response()->json([
            'success' => true,
            'student' => $student,
            'records' => $records,
            'pagination' => [
                'page' => $events->currentPage(),
                'pages' => $events->lastPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    private function medicalRecordStaff(?Staff $staff): ?array
    {
        if (! $staff || ! in_array($staff->role, ['nurse', 'nurse_head'], true)) {
            return null;
        }

        return $staff->only(['id', 'first_name', 'last_name', 'role']);
    }

    private function consultationTreatment(ConsultationRequest $consultation): string
    {
        return collect([
            'Diagnosis' => $consultation->diagnosis,
            'Treatment plan' => $consultation->treatment_plan,
            'Nursing advice' => $consultation->nursing_advice,
            'Clinician instructions' => $consultation->approved_instructions,
            'Emergency instructions' => $consultation->emergency_instructions,
            'Follow-up' => $consultation->follow_up,
        ])->filter()->map(fn (string $value, string $label): string => $label.': '.$value)->implode("\n");
    }

    public function medicalRecords(Request $request): JsonResponse
    {
        $campusId = $this->recordCampusId($request);
        $records = ClinicVisit::query()
            ->with([
                'student:id,student_id,last_name,first_name,middle_name',
                'dentalRecord',
            ])
            ->withClinicalDocumentation()
            ->withoutSyntheticDemo()
            ->when(
                $campusId !== null,
                fn (Builder $query) => $query->where('campus_id', $campusId),
            )
            ->latest('visit_date')
            ->latest('visit_time')
            ->latest('id')
            ->limit(10)
            ->get();

        return response()->json(['success' => true, 'records' => $records]);
    }

    public function storeMedicalRecord(StoreMedicalRecordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $campusId = $this->recordCampusId($request);
        /** @var array{id:int, role:string, campus_id:int|null} $identity */
        $identity = $request->attributes->get('clinic_identity');
        $student = Student::query()
            ->active()
            ->students()
            ->whereKey($data['student_id'])
            ->when(
                $campusId !== null,
                fn (Builder $query) => $query->where('campus_id', $campusId),
            )
            ->firstOrFail();

        $record = $student->clinicVisits()->create([
            'visit_date' => $data['visit_date'],
            'visit_time' => now('Asia/Manila')->format('H:i:s'),
            'reason' => $data['reason'],
            'treatment' => $data['treatment'] ?? null,
            'notes' => $data['notes'] ?? null,
            'nurse_id' => $identity['role'] === 'nurse_head' && filled($data['notes'] ?? null)
                ? $identity['id']
                : null,
            'bp' => $data['bp'] ?? null,
            'temp' => $data['temp'] ?? null,
            'pulse' => $data['pulse'] ?? null,
            'rr' => $data['rr'] ?? null,
            'campus_id' => $student->campus_id,
        ]);

        $this->audit->log(
            $request,
            $identity['id'],
            $identity['role'],
            'create_medical_record',
            'clinic_visits',
            $record->id,
            null,
            ['student_id' => $student->id, 'campus_id' => $student->campus_id],
        );

        return response()->json([
            'success' => true,
            'message' => 'Medical record saved.',
            'record' => $record->load('student:id,student_id,last_name,first_name,middle_name'),
        ], 201);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/admin/records/{type}/{id}
    // Returns a single record by type with all the relationships the
    // detail modal needs. Routes by type so we keep one source of truth
    // for which relationships each record type exposes.
    // -----------------------------------------------------------------------
    public function record(Request $request, string $type, int $id): JsonResponse
    {
        $campusId = $this->parseCampusId($request);

        if ($type === 'medical-records') {
            $row = ClinicVisit::with([
                'student:id,student_id,last_name,first_name,middle_name,course_year,department,sex,age,address,blood_type',
                'nurse:id,last_name,first_name,role',
                'physician:id,last_name,first_name,role',
                'campus:id,name',
                'dentalRecord',
            ])
                ->withoutSyntheticDemo()
                ->when($campusId, fn ($q) => $q->forCampus($campusId))
                ->find($id);
        } elseif ($type === 'vital') {
            $row = ClinicVisit::with([
                'student:id,student_id,last_name,first_name,middle_name,course_year,department,sex,age,blood_type',
                'campus:id,name',
            ])
                ->when($campusId, fn ($q) => $q->forCampus($campusId))
                ->find($id);
        } elseif ($type === 'referral') {
            $row = Referral::with([
                'student:id,student_id,last_name,first_name,middle_name,course_year,department,sex,age,address,blood_type',
                'fromCampus:id,name',
                'toCampus:id,name',
                'referredBy:id,last_name,first_name,role',
            ])
                ->when($campusId, fn ($q) => $q->forCampus($campusId))
                ->find($id);
        } else {
            return response()->json(['success' => false, 'error' => 'Unknown record type.'], 400);
        }

        if (! $row) {
            return response()->json(['success' => false, 'error' => 'Record not found.'], 404);
        }

        if (in_array($type, ['medical-records', 'vital'], true)) {
            $row->makeHidden('weight_kg');
        }

        return response()->json(['success' => true, 'record' => $row]);
    }

    // -----------------------------------------------------------------------
    // PUT /api/clinic/admin/records/medical/{id}
    // Partial update of a clinic_visit (medical record). Only fields present
    // in the payload are changed. The identity fields (student, campus) stay
    // as-is so the audit trail and referrals remain consistent.
    // -----------------------------------------------------------------------
    public function updateMedicalRecord(Request $request, int $id): JsonResponse
    {
        $visit = ClinicVisit::findOrFail($id);

        $data = $request->validate([
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

        $updates = [];
        foreach ([
            'visit_date', 'visit_time', 'reason', 'symptoms', 'treatment',
            'bp', 'temp', 'pulse', 'rr', 'o2_sat',
            'physician_id', 'disposition', 'notes',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        if ($updates === []) {
            return response()->json(['success' => true, 'message' => 'No changes']);
        }

        $visit->update($updates);

        $this->audit->log($request, $this->adminId($request), 'admin', 'update_medical_record', 'clinic_visits', $visit->id, null, [
            'updated_fields' => array_keys($updates),
        ]);

        return response()->json(['success' => true, 'id' => $visit->id]);
    }

    // -----------------------------------------------------------------------
    // DELETE /api/clinic/admin/records/medical/{id}
    // Hard-deletes a clinic visit (medical record).
    // -----------------------------------------------------------------------
    public function deleteMedicalRecord(Request $request, int $id): JsonResponse
    {
        $visit = ClinicVisit::findOrFail($id);
        $visit->delete();

        $this->audit->log($request, $this->adminId($request), 'admin', 'delete_medical_record', 'clinic_visits', $id, ['visit_id' => $id]);

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/admin/reports
    // -----------------------------------------------------------------------
    public function reports(Request $request): JsonResponse
    {
        $type = $request->query('type', 'overview');
        $campusId = $this->parseCampusId($request);
        $driver = DB::getDriverName();

        if ($type === 'daily') {
            $days = min(30, max(1, (int) $request->query('days', 13)));
            $campusScope = fn (Builder $q) => $q->forCampus($campusId);

            $query = ClinicVisit::query()
                ->when($campusId, $campusScope)
                ->withoutSyntheticDemo()
                ->where('visit_date', '>=', now()->subDays($days - 1)->startOfDay());

            if ($driver === 'pgsql') {
                $rawDate = 'visit_date::date';
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                $rawDate = 'DATE(visit_date)';
            } else {
                $rawDate = 'date(visit_date)';
            }

            $countsByDate = $query
                ->selectRaw("{$rawDate} as date_val, COUNT(*) as count")
                ->groupByRaw($rawDate)
                ->pluck('count', 'date_val')
                ->all();

            $daily = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $dateObj = now()->subDays($i);
                $dateStr = $dateObj->toDateString();
                $daily[] = [
                    'day_label' => $dateObj->format('D'),
                    'date_val' => $dateStr,
                    'count' => (int) ($countsByDate[$dateStr] ?? 0),
                ];
            }

            return response()->json([
                'success' => true,
                'report' => ['daily_visits' => $daily],
            ]);
        }

        if ($type === 'campus_monthly') {
            $months = min(12, max(1, (int) $request->query('months', 6)));

            $campuses = Campus::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);

            // Build the N most-recent month labels (oldest first).
            $labels = [];
            $now = now();
            for ($i = $months - 1; $i >= 0; $i--) {
                $labels[] = $now->copy()->subMonths($i)->format('M');
            }

            $series = $campuses->map(function ($c) use ($months, $driver) {
                $base = ClinicVisit::query()->withoutSyntheticDemo()->where('campus_id', $c->id);

                if ($driver === 'pgsql') {
                    $rows = (clone $base)
                        ->selectRaw('EXTRACT(MONTH FROM visit_date)::int as month, COUNT(*) as count')
                        ->where('visit_date', '>=', now()->subMonths($months - 1)->startOfMonth())
                        ->groupByRaw('EXTRACT(MONTH FROM visit_date)')
                        ->pluck('count', 'month');
                } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                    $rows = (clone $base)
                        ->selectRaw('MONTH(visit_date) as month, COUNT(*) as count')
                        ->where('visit_date', '>=', now()->subMonths($months - 1)->startOfMonth())
                        ->groupByRaw('MONTH(visit_date)')
                        ->pluck('count', 'month');
                } else {
                    $rows = (clone $base)
                        ->selectRaw('CAST(strftime("%m", visit_date) AS INTEGER) as month, COUNT(*) as count')
                        ->where('visit_date', '>=', now()->subMonths($months - 1)->startOfMonth())
                        ->groupByRaw('strftime("%m", visit_date)')
                        ->pluck('count', 'month');
                }

                $data = [];
                $now = now();
                for ($i = $months - 1; $i >= 0; $i--) {
                    $m = (int) $now->copy()->subMonths($i)->format('n');
                    $data[] = (int) ($rows[$m] ?? 0);
                }

                return [
                    'campus_id' => $c->id,
                    'name' => $c->name,
                    'code' => $c->code,
                    'data' => $data,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'report' => [
                    'labels' => $labels,
                    'series' => $series,
                ],
            ]);
        }

        if ($type !== 'overview') {
            return response()->json(['error' => 'Unknown report type'], 400);
        }

        $year = (int) $request->query('year', now()->year);
        $periodStart = now()->subYear()->toDateString();
        $periodEnd = today()->toDateString();

        $campusScope = fn (Builder $q) => $q->forCampus($campusId);

        if ($driver === 'pgsql') {
            $monthly = ClinicVisit::query()
                ->when($campusId, $campusScope)
                ->withoutSyntheticDemo()
                ->selectRaw('EXTRACT(MONTH FROM visit_date)::int as month, COUNT(*) as count')
                ->whereYear('visit_date', $year)
                ->groupByRaw('EXTRACT(MONTH FROM visit_date)')
                ->orderBy('month')
                ->get();
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            $monthly = ClinicVisit::query()
                ->when($campusId, $campusScope)
                ->withoutSyntheticDemo()
                ->selectRaw('MONTH(visit_date) as month, COUNT(*) as count')
                ->whereYear('visit_date', $year)
                ->groupByRaw('MONTH(visit_date)')
                ->orderBy('month')
                ->get();
        } else {
            $monthly = ClinicVisit::query()
                ->when($campusId, $campusScope)
                ->withoutSyntheticDemo()
                ->selectRaw('CAST(strftime("%m", visit_date) AS INTEGER) as month, COUNT(*) as count')
                ->whereYear('visit_date', $year)
                ->groupByRaw('strftime("%m", visit_date)')
                ->orderBy('month')
                ->get();
        }

        $complaintRequests = ConsultationRequest::query()
            ->when(
                $campusId,
                fn (Builder $query) => $query->where('consultation_requests.campus_id', $campusId),
            )
            ->whereDate('consultation_requests.created_at', '>=', $periodStart)
            ->whereDate('consultation_requests.created_at', '<=', $periodEnd)
            ->whereRaw('LOWER(concern) NOT LIKE ?', ['%synthetic%'])
            ->whereRaw('LOWER(concern) NOT LIKE ?', ['%demo data%'])
            ->whereRaw('LOWER(concern) NOT LIKE ?', ['%not a real visit%']);

        $documentedComplaints = (clone $complaintRequests)
            ->whereNotNull('concern')
            ->whereRaw('TRIM(concern) <> ?', [''])
            ->whereRaw('LOWER(TRIM(concern)) <> ?', ['audio recording submitted without written concern.']);

        $eligibleComplaintCount = (clone $complaintRequests)->count();
        $documentedComplaintCount = (clone $documentedComplaints)->count();
        $topReasons = (clone $documentedComplaints)
            ->selectRaw('concern, COUNT(*) as count')
            ->groupBy('concern')
            ->get()
            ->groupBy(fn (object $item): string => mb_strtolower(trim($item->concern)))
            ->map(function ($items) use ($documentedComplaintCount): array {
                $count = (int) $items->sum('count');

                return [
                    'reason' => trim((string) $items->first()->concern),
                    'count' => $count,
                    'share_percent' => $documentedComplaintCount > 0
                        ? round(($count / $documentedComplaintCount) * 100, 1)
                        : 0,
                ];
            })
            ->sort(function (array $first, array $second): int {
                return ($second['count'] <=> $first['count'])
                    ?: strcasecmp($first['reason'], $second['reason']);
            })
            ->take(10)
            ->values();

        if ($driver === 'pgsql') {
            $complaintMonthExpression = "TO_CHAR(created_at, 'YYYY-MM')";
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            $complaintMonthExpression = "DATE_FORMAT(created_at, '%Y-%m')";
        } else {
            $complaintMonthExpression = "strftime('%Y-%m', created_at)";
        }

        $complaintCountsByMonth = (clone $documentedComplaints)
            ->selectRaw("{$complaintMonthExpression} as month_key, COUNT(*) as count")
            ->groupByRaw($complaintMonthExpression)
            ->pluck('count', 'month_key')
            ->all();

        $monthlyComplaints = [];
        for ($monthOffset = 11; $monthOffset >= 0; $monthOffset--) {
            $month = now()->startOfMonth()->subMonths($monthOffset);
            $monthKey = $month->format('Y-m');
            $monthlyComplaints[] = [
                'month' => $monthKey,
                'label' => $month->format('M Y'),
                'count' => (int) ($complaintCountsByMonth[$monthKey] ?? 0),
            ];
        }

        $complaintsByDepartment = (clone $documentedComplaints)
            ->leftJoin('students', 'students.id', '=', 'consultation_requests.student_id')
            ->selectRaw('students.department as department, COUNT(*) as count')
            ->groupBy('students.department')
            ->get()
            ->groupBy(fn (object $item): string => mb_strtolower(trim((string) $item->department)) ?: 'unspecified department')
            ->map(function ($items) use ($documentedComplaintCount): array {
                $count = (int) $items->sum('count');
                $department = trim((string) $items->first()->department) ?: 'Unspecified department';

                return [
                    'department' => $department,
                    'count' => $count,
                    'share_percent' => $documentedComplaintCount > 0
                        ? round(($count / $documentedComplaintCount) * 100, 1)
                        : 0,
                ];
            })
            ->sort(function (array $first, array $second): int {
                return ($second['count'] <=> $first['count'])
                    ?: strcasecmp($first['department'], $second['department']);
            })
            ->take(10)
            ->values();

        $complaintsByCampus = (clone $documentedComplaints)
            ->leftJoin('campuses', 'campuses.id', '=', 'consultation_requests.campus_id')
            ->selectRaw('campuses.id as campus_id, campuses.name as campus, COUNT(*) as count')
            ->groupBy('campuses.id', 'campuses.name')
            ->get()
            ->groupBy(fn (object $item): string => mb_strtolower(trim((string) $item->campus)) ?: 'unassigned campus')
            ->map(function ($items) use ($documentedComplaintCount): array {
                $count = (int) $items->sum('count');
                $campus = trim((string) $items->first()->campus) ?: 'Unassigned campus';

                return [
                    'campus' => $campus,
                    'count' => $count,
                    'share_percent' => $documentedComplaintCount > 0
                        ? round(($count / $documentedComplaintCount) * 100, 1)
                        : 0,
                ];
            })
            ->sort(function (array $first, array $second): int {
                return ($second['count'] <=> $first['count'])
                    ?: strcasecmp($first['campus'], $second['campus']);
            })
            ->values();

        $deptDist = Student::query()
            ->students()
            ->active()
            ->forCampus($campusId)
            ->selectRaw('department, COUNT(*) as patient_count')
            ->whereNotNull('department')
            ->whereRaw('TRIM(department) <> ?', [''])
            ->groupBy('department')
            ->orderByDesc('patient_count')
            ->orderBy('department')
            ->get();

        $dispositions = ClinicVisit::query()
            ->when($campusId, $campusScope)
            ->withoutSyntheticDemo()
            ->selectRaw('disposition, COUNT(*) as count')
            ->whereDate('visit_date', '>=', $periodStart)
            ->whereDate('visit_date', '<=', $periodEnd)
            ->whereNotNull('disposition')
            ->whereRaw('TRIM(disposition) <> ?', [''])
            ->groupBy('disposition')
            ->orderByDesc('count')
            ->get();

        $eligibleVisits = ClinicVisit::query()
            ->when($campusId, $campusScope)
            ->withoutSyntheticDemo()
            ->whereDate('visit_date', '>=', $periodStart)
            ->whereDate('visit_date', '<=', $periodEnd);

        $eligibleVisitCount = (clone $eligibleVisits)->count();
        $excludedDemoVisitCount = ClinicVisit::query()
            ->when($campusId, $campusScope)
            ->whereDate('visit_date', '>=', $periodStart)
            ->whereDate('visit_date', '<=', $periodEnd)
            ->count() - $eligibleVisitCount;
        $documentedVisitCount = (clone $eligibleVisits)
            ->whereNotNull('reason')
            ->whereRaw('TRIM(reason) <> ?', [''])
            ->count();

        return response()->json([
            'success' => true,
            'report' => [
                'monthly_visits' => $monthly,
                'monthly_complaints' => $monthlyComplaints,
                'top_reasons' => $topReasons,
                'complaints_by_department' => $complaintsByDepartment,
                'complaints_by_campus' => $complaintsByCampus,
                'dept_distribution' => $deptDist,
                'dispositions' => $dispositions,
                'year' => $year,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'eligible_visit_count' => $eligibleVisitCount,
                'documented_visit_count' => $documentedVisitCount,
                'excluded_demo_visit_count' => $excludedDemoVisitCount,
                'eligible_complaint_count' => $eligibleComplaintCount,
                'documented_complaint_count' => $documentedComplaintCount,
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/admin/audit-log
    // -----------------------------------------------------------------------
    public function auditLog(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $limit = min(200, max(1, (int) $request->query('limit', 50)));
        $search = trim((string) ($request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ])['search'] ?? ''));

        $query = AuditLog::query()
            ->when($request->query('user_id'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->query('action'), fn ($q, $v) => $q->where('action', $v))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $logQuery) use ($search): void {
                $like = '%'.$search.'%';
                $logQuery->where('action', 'like', $like)
                    ->orWhere('entity_type', 'like', $like)
                    ->orWhere('user_type', 'like', $like)
                    ->orWhere('ip_address', 'like', $like)
                    ->orWhere('user_agent', 'like', $like);

                if (is_numeric($search)) {
                    $logQuery->orWhere('user_id', (int) $search)
                        ->orWhere('entity_id', (int) $search);
                }
            }))
            ->latest();

        $total = $query->count();
        $logs = $query->skip(($page - 1) * $limit)->take($limit)->get();

        return response()->json([
            'success' => true,
            'logs' => $logs,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/admin/campuses
    // -----------------------------------------------------------------------
    public function campuses(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'campuses' => Campus::orderBy('id')->get(),
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/admin/staff/{employeeId}/campus
    // -----------------------------------------------------------------------
    public function assignStaffCampus(Request $request, string $employeeId): JsonResponse
    {
        $data = $request->validate([
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
        ]);

        $member = Staff::where('employee_id', strtoupper($employeeId))->firstOrFail();
        $member->update(['campus_id' => $data['campus_id'] ?? null]);

        $this->audit->log($request, $this->adminId($request), 'admin', 'assign_staff_campus', 'staff', $member->id, null, ['employee_id' => $employeeId, 'campus_id' => $data['campus_id'] ?? null]);

        return response()->json(['success' => true, 'message' => "Campus assignment updated for {$employeeId}"]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/admin/settings
    // -----------------------------------------------------------------------
    public function settings(): JsonResponse
    {
        $result = SystemSetting::orderBy('setting_key')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->setting_key => [
                    'value' => $row->setting_value,
                    'description' => $row->description,
                ],
            ]);

        $analysisModels = ClinicAiModelCatalog::options('analysis');
        $transcriptionModels = ClinicAiModelCatalog::options('transcription');

        return response()->json([
            'success' => true,
            'settings' => $result,
            'ai_models' => [
                'provider' => config('services.clinic_ai.provider', 'groq'),
                'analysis' => [
                    'current' => ClinicAiModelCatalog::selected('analysis'),
                    'options' => $analysisModels,
                    'custom_options' => ClinicAiModelCatalog::customOptions('analysis'),
                    'custom_models' => ClinicAiModelCatalog::customModels('analysis'),
                ],
                'transcription' => [
                    'current' => ClinicAiModelCatalog::selected('transcription'),
                    'options' => $transcriptionModels,
                    'custom_options' => ClinicAiModelCatalog::customOptions('transcription'),
                    'custom_models' => ClinicAiModelCatalog::customModels('transcription'),
                    'configured' => filled(config('services.groq.api_key')),
                ],
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // PUT /api/clinic/admin/settings
    // -----------------------------------------------------------------------
    public function updateSetting(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_key' => ['required', 'string', 'max:100'],
            'setting_value' => ['required'],
        ]);

        $modelSettingOptions = match ($data['setting_key']) {
            'clinic_ai_analysis_model' => ClinicAiModelCatalog::options('analysis'),
            'clinic_ai_transcription_model' => ClinicAiModelCatalog::options('transcription'),
            default => null,
        };
        if ($modelSettingOptions !== null) {
            abort_unless(config('services.clinic_ai.provider', 'groq') === 'groq', 409, 'AI model settings currently require the Groq provider.');
            $request->validate([
                'setting_value' => ['required', 'string', Rule::in(array_keys($modelSettingOptions))],
            ]);
        }

        SystemSetting::setValue($data['setting_key'], $data['setting_value']);

        $this->audit->log($request, $this->adminId($request), 'admin', 'update_setting', 'system_settings', null, null, ['setting_key' => $data['setting_key']]);

        return response()->json(['success' => true]);
    }

    public function saveAiModelSettings(Request $request): JsonResponse
    {
        abort_unless(config('services.clinic_ai.provider', 'groq') === 'groq', 409, 'AI model settings currently require the Groq provider.');

        $data = $request->validate([
            'analysis_models' => ['present', 'array', 'max:100'],
            'analysis_models.*.name' => ['required', 'string', 'max:100'],
            'analysis_models.*.id' => ['required', 'string', 'max:120', 'distinct', 'regex:/\A[A-Za-z0-9][A-Za-z0-9._:\/-]{0,119}\z/'],
            'transcription_models' => ['present', 'array', 'max:100'],
            'transcription_models.*.name' => ['required', 'string', 'max:100'],
            'transcription_models.*.id' => ['required', 'string', 'max:120', 'distinct', 'regex:/\A[A-Za-z0-9][A-Za-z0-9._:\/-]{0,119}\z/'],
        ]);
        $selection = $request->validate([
            'analysis_model' => ['required', 'string', Rule::in(array_unique(array_merge(
                array_keys(config('clinic_ai.analysis_models', [])),
                array_column($data['analysis_models'], 'id'),
            )))],
            'transcription_model' => ['required', 'string', Rule::in(array_unique(array_merge(
                array_keys(config('clinic_ai.transcription_models', [])),
                array_column($data['transcription_models'], 'id'),
            )))],
        ]);
        abort_unless(
            filled(config('services.groq.api_key'))
                || $selection['transcription_model'] === ClinicAiModelCatalog::selected('transcription'),
            422,
            'Configure GROQ_API_KEY on the server before changing the transcription model.',
        );

        DB::transaction(function () use ($data, $selection): void {
            SystemSetting::setValue('clinic_ai_analysis_model', $selection['analysis_model']);
            SystemSetting::setValue('clinic_ai_transcription_model', $selection['transcription_model']);
            SystemSetting::setValue('clinic_ai_analysis_custom_models', array_values($data['analysis_models']));
            SystemSetting::setValue('clinic_ai_transcription_custom_models', array_values($data['transcription_models']));
        });

        $this->audit->log(
            $request,
            $this->adminId($request),
            'admin',
            'update_ai_model_settings',
            'system_settings',
            null,
            null,
            [
                'analysis_model' => $selection['analysis_model'],
                'transcription_model' => $selection['transcription_model'],
                'analysis_custom_model_count' => count($data['analysis_models']),
                'transcription_custom_model_count' => count($data['transcription_models']),
            ],
        );

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/admin/export
    // -----------------------------------------------------------------------
    public function export(): JsonResponse
    {
        $exportData = [
            'students' => Student::get()->makeHidden('password')->toArray(),
            'staff' => Staff::get()->makeHidden('password')->toArray(),
            'health_records' => HealthRecord::all()->toArray(),
            'clinic_visits' => ClinicVisit::all()->toArray(),
            'appointments' => Appointment::all()->toArray(),
            'medications' => Medication::all()->toArray(),
            'forms_certificates' => FormCertificate::all()->toArray(),
            'system_settings' => SystemSetting::all()->toArray(),
        ];

        $filename = 'sunn_clinic_export_'.now()->format('Y-m-d').'.json';

        return response()->json($exportData)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Cache-Control', 'no-store');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function parseCampusId(Request $request, string $parameter = 'campus_id'): ?int
    {
        $raw = $request->query($parameter);

        return is_numeric($raw) ? (int) $raw : null;
    }

    private function fillMissingMedicalRecordDemographics(Student $student): Student
    {
        $record = $student->latestHealthRecord;

        foreach (['age', 'date_of_birth', 'contact', 'course_year'] as $field) {
            $studentValue = $student->getAttribute($field);
            $recordValue = $record?->getAttribute($field);

            if (($studentValue === null || $studentValue === '') && $recordValue !== null && $recordValue !== '') {
                $student->setAttribute($field, $recordValue);
            }
        }

        $student->unsetRelation('latestHealthRecord');

        return $student;
    }

    private function adminId(Request $request): ?int
    {
        $raw = $request->query('admin_id');

        return is_numeric($raw) ? (int) $raw : null;
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/admin/services
    // -----------------------------------------------------------------------
    public function services(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'services' => ClinicService::orderBy('category')->orderBy('name')->get(),
        ]);
    }

    public function clinicians(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'clinicians' => ClinicProvider::query()
                ->with('campus:id,name')
                ->orderBy('provider_type')
                ->orderBy('name')
                ->get(),
            'campuses' => Campus::active()->orderBy('id')->get(['id', 'name']),
        ]);
    }

    public function storeClinician(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'campus_id' => ['sometimes', 'nullable', 'integer', Rule::exists('campuses', 'id')->where('is_active', true)],
            'provider_type' => ['required', 'string', 'in:physician,dentist'],
            'designation' => ['sometimes', 'nullable', 'string', 'max:100'],
            'license_no' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'contact' => ['sometimes', 'nullable', 'string', 'max:30'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['name'] = strip_tags(trim($data['name']));
        $clinician = ClinicProvider::query()->create($data);
        $identity = $request->attributes->get('clinic_identity');
        $this->audit->log($request, $identity['id'], 'admin', 'add_clinician', 'clinic_providers', $clinician->id);

        return response()->json(['success' => true, 'id' => $clinician->id], 201);
    }

    public function updateClinician(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'campus_id' => ['sometimes', 'nullable', 'integer', Rule::exists('campuses', 'id')->where('is_active', true)],
            'provider_type' => ['sometimes', 'required', 'string', 'in:physician,dentist'],
            'designation' => ['sometimes', 'nullable', 'string', 'max:100'],
            'license_no' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'contact' => ['sometimes', 'nullable', 'string', 'max:30'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        if (isset($data['name'])) {
            $data['name'] = strip_tags(trim($data['name']));
        }
        $clinician = ClinicProvider::query()->findOrFail($id);
        $clinician->update($data);
        $identity = $request->attributes->get('clinic_identity');
        $this->audit->log($request, $identity['id'], 'admin', 'update_clinician', 'clinic_providers', $clinician->id);

        return response()->json(['success' => true]);
    }

    public function deleteClinician(Request $request, int $id): JsonResponse
    {
        $clinician = ClinicProvider::query()->findOrFail($id);
        $clinician->delete();
        $identity = $request->attributes->get('clinic_identity');
        $this->audit->log($request, $identity['id'], 'admin', 'delete_clinician', 'clinic_providers', $id);

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/admin/services
    // -----------------------------------------------------------------------
    public function addService(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:50'],
            'description' => ['sometimes', 'nullable', 'string'],
            'duration_minutes' => ['sometimes', 'nullable', 'string', 'max:20'],
            'requires_appointment' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $service = ClinicService::create([
            'name' => strip_tags(trim($data['name'])),
            'category' => strip_tags(trim($data['category'])),
            'description' => strip_tags(trim($data['description'] ?? '')),
            'duration_minutes' => strip_tags(trim($data['duration_minutes'] ?? '15')),
            'requires_appointment' => $data['requires_appointment'] ?? true,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->audit->log($request, $this->adminId($request), 'admin', 'add_service', 'clinic_services', $service->id);

        return response()->json(['success' => true, 'id' => $service->id, 'service' => $service]);
    }

    // -----------------------------------------------------------------------
    // DELETE /api/clinic/admin/services/{id}
    // -----------------------------------------------------------------------
    public function deleteService(Request $request, int $id): JsonResponse
    {
        $service = ClinicService::find($id);
        if (! $service) {
            return response()->json(['success' => false, 'error' => 'Service not found.'], 404);
        }
        $service->delete();
        $this->audit->log($request, $this->adminId($request), 'admin', 'delete_service', 'clinic_services', $id);

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/admin/settings/save
    // Bulk-save the four clinic-profile settings in one call. Used by the
    // admin Settings page's "Save Settings" button. The single-setting PUT
    // /api/clinic/admin/settings (updateSetting) is kept for other callers.
    // -----------------------------------------------------------------------
    public function saveClinicSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'clinic_name' => ['sometimes', 'nullable', 'string', 'max:200'],
            'clinic_address' => ['sometimes', 'nullable', 'string', 'max:300'],
            'clinic_contact' => ['sometimes', 'nullable', 'string', 'max:50'],
            'clinic_email' => ['sometimes', 'nullable', 'email', 'max:150'],
        ]);

        $updated = 0;
        foreach ($data as $key => $value) {
            SystemSetting::setValue($key, is_string($value) ? strip_tags(trim($value)) : $value);
            $updated++;
        }

        $this->audit->log($request, $this->adminId($request), 'admin', 'save_clinic_settings', 'system_settings', null, null, ['count' => $updated]);

        return response()->json(['success' => true, 'updated' => $updated]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/admin/backup/restore
    // -----------------------------------------------------------------------
    public function restoreBackup(Request $request): JsonResponse
    {
        if (! $request->hasFile('file')) {
            return response()->json(['success' => false, 'error' => 'No backup file uploaded.'], 422);
        }

        $file = $request->file('file');
        if ($file->getClientOriginalExtension() !== 'json') {
            return response()->json(['success' => false, 'error' => 'Only .json backup files are supported.'], 422);
        }

        $contents = file_get_contents($file->getRealPath());
        $payload = json_decode($contents, true);
        if (! is_array($payload) || ! isset($payload['exported_at'])) {
            return response()->json(['success' => false, 'error' => 'Invalid backup file format.'], 422);
        }

        // Log the restore attempt; the actual restore is a manual operation for safety.
        $this->audit->log(
            $request,
            $this->adminId($request),
            'admin',
            'restore_backup',
            'backup',
            null,
            null,
            [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'exported_at' => $payload['exported_at'] ?? null,
                'tables' => array_keys(array_filter($payload, 'is_array')),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Backup file received. Manual review and restore required by an administrator.',
            'filename' => $file->getClientOriginalName(),
            'exported_at' => $payload['exported_at'] ?? null,
        ]);
    }
}
