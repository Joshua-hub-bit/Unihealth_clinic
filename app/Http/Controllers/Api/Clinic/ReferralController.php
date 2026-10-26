<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Campus;
use App\Models\ClinicAvailability;
use App\Models\ClinicNotification;
use App\Models\ConsultationRequest;
use App\Models\Referral;
use App\Models\Student;
use App\Services\AuditService;
use App\Services\ClinicHelperService;
use App\Services\ReferralSpecialistResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private ClinicHelperService $helper,
        private ReferralSpecialistResolver $referralSpecialistResolver,
    ) {}

    // -----------------------------------------------------------------------
    // GET /api/clinic/referrals
    // -----------------------------------------------------------------------
    public function index(Request $request): JsonResponse
    {
        $identity = $this->identity($request);
        $campusId = $this->parseCampusId($request);
        $status = (string) $request->query('status', '');
        $studentNo = (string) $request->query('student_no', '');
        $routeTo = (string) $request->query('route_to', '');
        $page = max(1, (int) $request->query('page', 1));
        $limit = min(200, max(1, (int) $request->query('limit', 50)));
        $filters = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'referred_to' => ['sometimes', 'nullable', 'string', 'max:150'],
            'sort_order' => ['sometimes', 'in:asc,desc'],
            'statuses' => ['sometimes', 'array'],
            'statuses.*' => ['in:pending,accepted,completed,cancelled'],
        ]);
        $search = trim((string) ($filters['q'] ?? ''));
        $referredTo = trim((string) ($filters['referred_to'] ?? ''));
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $isClinician = false;
        $query = Referral::query()->when($campusId, fn ($q) => $q->forCampus($campusId));
        $query->with([
            'student:id,student_id,last_name,first_name,middle_name,course_year,department',
            'fromCampus:id,name,code',
            'toCampus:id,name,code',
            'referredBy:id,first_name,last_name,role',
        ])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($filters['statuses'] ?? [], fn ($q, array $statuses) => $q->whereIn('status', $statuses))
            ->when($routeTo, fn ($q) => $q->where('route_to', $routeTo));

        if ($identity['role'] === 'student') {
            $query->where('student_id', $identity['id']);
        }

        if ($studentNo) {
            $sid = $this->helper->resolveStudentId($studentNo);
            if ($sid) {
                $query->where('student_id', $sid);
            }
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($referralQuery) use ($like) {
                $referralQuery->where('referral_no', 'like', $like)
                    ->orWhere('referred_to_name', 'like', $like)
                    ->orWhere('specialty', 'like', $like)
                    ->orWhere('reason', 'like', $like)
                    ->orWhere('diagnosis', 'like', $like)
                    ->orWhereHas('student', function ($studentQuery) use ($like) {
                        $studentQuery->where('student_id', 'like', $like)
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('middle_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like);
                    });
            });
        }

        if ($referredTo !== '') {
            $like = '%'.$referredTo.'%';
            $query->where(function ($referralQuery) use ($like) {
                $referralQuery->where('referred_to_name', 'like', $like)
                    ->orWhere('route_to', 'like', $like)
                    ->orWhere('specialty', 'like', $like)
                    ->orWhereHas('toCampus', fn ($campusQuery) => $campusQuery
                        ->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like));
            });
        }

        $total = (clone $query)->count();
        $referrals = $query->orderBy('created_at', $sortOrder)->orderBy('id', $sortOrder)
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();
        $resolvedReferrals = $this->referralSpecialistResolver->resolve($referrals->toArray());

        return response()->json([
            'success' => true,
            'total' => $total,
            'referrals' => $resolvedReferrals,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function awaitingSchedule(Request $request): JsonResponse
    {
        $identity = $this->identity($request);
        $filters = $request->validate([
            'campus_id' => ['sometimes', 'nullable', 'integer', 'exists:campuses,id'],
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $campusId = null;

        if ($identity['role'] === 'nurse') {
            $campusId = $identity['campus_id'] ?? null;
            abort_unless($campusId !== null, 403, 'Your account is not assigned to a campus.');
        } elseif ($identity['role'] === 'nurse_head') {
            $campusId = $filters['campus_id'] ?? null;
        } else {
            abort(403, 'You are not authorized to view the referral scheduling queue.');
        }

        $query = Referral::query()
            ->whereIn('route_to', ['physician', 'dentist'])
            ->whereNull('scheduled_at')
            ->whereIn('status', ['pending', 'accepted'])
            ->with([
                'student:id,student_id,last_name,first_name,middle_name,campus_id',
                'fromCampus:id,name',
                'referredBy:id,first_name,last_name',
            ])
            ->when($campusId, fn (Builder $builder) => $builder->whereHas(
                'student',
                fn (Builder $studentQuery) => $studentQuery->where('campus_id', $campusId),
            ));

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $builder) use ($like): void {
                $builder->where('referral_no', 'like', $like)
                    ->orWhere('reason', 'like', $like)
                    ->orWhere('referred_to_name', 'like', $like)
                    ->orWhereHas('student', function (Builder $studentQuery) use ($like): void {
                        $studentQuery->where('student_id', 'like', $like)
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('middle_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like);
                    });
            });
        }

        $total = (clone $query)->count();
        $page = (int) ($filters['page'] ?? 1);
        $limit = (int) ($filters['limit'] ?? 10);
        $referrals = $query->orderBy('created_at')->orderBy('id')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'referrals' => $this->referralSpecialistResolver->resolve($referrals->toArray()),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
            'campuses' => $identity['role'] === 'nurse_head'
                ? Campus::active()->orderBy('id')->get(['id', 'name'])
                : [],
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/referrals
    // -----------------------------------------------------------------------
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_no' => ['required', 'string'],
            'source_appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'availability_id' => ['nullable', 'required_with:appt_time', 'integer', 'exists:clinic_availabilities,id'],
            'appt_time' => ['nullable', 'required_with:availability_id', 'date_format:H:i'],
            'to_campus_id' => ['sometimes', 'nullable', 'integer', 'exists:campuses,id'],
            'referred_to_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'specialty' => ['sometimes', 'nullable', 'string', 'max:150'],
            'referral_type' => ['sometimes', 'string', 'in:inter-campus,external'],
            'route_to' => ['required', 'string', 'in:external,inter-campus,physician,dentist'],
            'diagnosis' => ['sometimes', 'nullable', 'string'],
            'history' => ['sometimes', 'nullable', 'string'],
            'reason' => ['required', 'string'],
            'management_given' => ['sometimes', 'nullable', 'string'],
            'vital_signs' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'signature_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'signature_role' => ['sometimes', 'nullable', 'string', 'max:150'],
            'signature_license_no' => ['sometimes', 'nullable', 'string', 'max:50'],
            'urgency' => ['sometimes', 'string', 'in:routine,urgent,emergency'],
        ]);

        $studentId = $this->helper->resolveStudentId($data['student_no']);
        if (! $studentId) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $student = Student::query()->findOrFail($studentId);
        /** @var array{id:int, role:string, campus_id:int|null} $identity */
        $identity = $request->attributes->get('clinic_identity');
        $isNurseHead = $identity['role'] === 'nurse_head';
        abort_unless(
            $isNurseHead || ($identity['campus_id'] !== null && $identity['campus_id'] === $student->campus_id),
            404,
        );

        $isClinicianRoute = in_array($data['route_to'], ['physician', 'dentist'], true);
        $result = DB::transaction(function () use ($data, $identity, $student, $studentId, $request, $isNurseHead, $isClinicianRoute): array {
            $sourceAppointmentId = $data['source_appointment_id'] ?? null;
            $sourceAppointment = null;
            $consultation = null;
            if ($isClinicianRoute && $sourceAppointmentId !== null) {
                $consultation = ConsultationRequest::query()
                    ->where('appointment_id', $sourceAppointmentId)
                    ->lockForUpdate()
                    ->first();
                if ($consultation) {
                    abort_unless(
                        $consultation->status === 'nurse_review_complete'
                            && $consultation->referral_id === null
                            && ($isNurseHead || $consultation->nurse_id === $identity['id']),
                        409,
                        'Complete the nurse review before referring this consultation.',
                    );
                }
            }

            if ($sourceAppointmentId !== null) {
                $sourceAppointment = Appointment::query()
                    ->lockForUpdate()
                    ->findOrFail($sourceAppointmentId);
                abort_unless(
                    $sourceAppointment->student_id === $studentId
                        && in_array($sourceAppointment->status, ['accepted', 'confirmed', 'checkup_scheduled', 'checkup_completed'], true)
                        && ($isNurseHead
                            || $sourceAppointment->campus_id === $identity['campus_id']
                            || ($sourceAppointment->campus_id === null && $student->campus_id === $identity['campus_id'])),
                    409,
                    'Only this student’s active appointment at your campus can be referred.',
                );
            }

            $availability = null;
            $scheduledAt = null;
            if ($isClinicianRoute && isset($data['availability_id'], $data['appt_time'])) {
                $availability = ClinicAvailability::query()
                    ->lockForUpdate()
                    ->findOrFail($data['availability_id']);
                abort_unless(
                    $availability->is_available
                        && (int) $availability->campus_id === (int) $student->campus_id
                        && $availability->provider_type === $data['route_to']
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

                $activeAppointments = $availability->appointments()
                    ->whereIn('status', ['pending', 'accepted', 'confirmed']);
                if ($sourceAppointment !== null) {
                    $activeAppointments->where('appointments.id', '<>', $sourceAppointment->id);
                }
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
                $scheduledAt = \Illuminate\Support\Carbon::parse(
                    $availability->available_date->toDateString().' '.$data['appt_time'],
                );
            }

            $referral = Referral::create([
                'referral_no' => Referral::generateReferralNo(),
                'student_id' => $studentId,
                'from_campus_id' => $student->campus_id,
                'to_campus_id' => $data['to_campus_id'] ?? null,
                'referred_by' => $identity['id'],
                'referred_to_name' => $this->helper->sanitize($data['referred_to_name'] ?? '', 150),
                'specialty' => $this->helper->sanitize($data['specialty'] ?? '', 150),
                'referral_type' => $data['referral_type'] ?? 'inter-campus',
                'route_to' => $data['route_to'],
                'diagnosis' => $data['diagnosis'] ?? null,
                'history' => $data['history'] ?? null,
                'reason' => $data['reason'],
                'management_given' => $data['management_given'] ?? null,
                'vital_signs' => $data['vital_signs'] ?? null,
                'signature_name' => $this->helper->sanitize($data['signature_name'] ?? '', 150),
                'signature_role' => $this->helper->sanitize($data['signature_role'] ?? '', 150),
                'signature_license_no' => $this->helper->sanitize($data['signature_license_no'] ?? '', 50),
                'urgency' => $data['urgency'] ?? 'routine',
                'status' => 'pending',
                'scheduled_at' => $scheduledAt,
                'source_appointment_id' => $sourceAppointment?->id,
            ]);

            $scheduledAppointment = null;
            if ($isClinicianRoute && $scheduledAt !== null) {
                $scheduledAppointment = Appointment::query()->create([
                    'student_id' => $studentId,
                    'appt_date' => $availability->available_date,
                    'appt_time' => $data['appt_time'],
                    'service_type' => $data['route_to'],
                    'reason' => $data['reason'],
                    'status' => 'confirmed',
                    'created_by' => $identity['id'],
                    'campus_id' => $availability->campus_id,
                    'availability_id' => $availability->id,
                    'notes' => 'Scheduled from referral '.$referral->referral_no,
                ]);
                $this->audit->log(
                    $request,
                    $identity['id'],
                    'staff',
                    'schedule_referral_appointment',
                    'appointments',
                    $scheduledAppointment->id,
                    [],
                    ['status' => 'confirmed', 'referral_id' => $referral->id],
                );
            }

            if ($sourceAppointment) {
                $oldStatus = $sourceAppointment->status;
                $appointmentChanges = [
                    'status' => 'completed',
                    'cancellation_reason' => null,
                ];
                $sourceAppointment->update($appointmentChanges);
                if ($consultation && $isClinicianRoute) {
                    $consultation->update([
                        'referral_id' => $referral->id,
                        'status' => 'completed',
                        'scheduled_at' => $scheduledAt,
                    ]);
                }
                $this->audit->log(
                    $request,
                    $identity['id'],
                    'staff',
                    'refer_appointment',
                    'appointments',
                    $sourceAppointment->id,
                    ['status' => $oldStatus],
                    array_merge($appointmentChanges, [
                        'referral_id' => $referral->id,
                        'scheduled_appointment_id' => $scheduledAppointment?->id,
                    ]),
                );
            }

            return ['referral' => $referral, 'scheduled_at' => $scheduledAt];
        });
        $referral = $result['referral'];

        // Notify student
        ClinicNotification::notifyStudent(
            $studentId,
            'referral',
            'Referral Created',
            'A referral has been created for you.'
                .($result['scheduled_at'] ? ' Your appointment is scheduled for '.$result['scheduled_at']->format('M j').' at '.$result['scheduled_at']->format('g:i A').'.' : '')
                .' Referral No: '.$referral->referral_no,
            [
                'referral_id' => $referral->id,
                'referral_no' => $referral->referral_no,
                'scheduled_at' => $result['scheduled_at']?->toIso8601String(),
            ],
        );

        $this->audit->log($request, $identity['id'], 'staff', 'create_referral', 'referrals', $referral->id);

        return response()->json([
            'success' => true,
            'id' => $referral->id,
            'referral_no' => $referral->referral_no,
            'scheduled_at' => $result['scheduled_at']?->toIso8601String(),
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/referrals/{id}
    // -----------------------------------------------------------------------
    public function show(Request $request, int $id): JsonResponse
    {
        $identity = $this->identity($request);
        $query = Referral::query();
        if ($identity['role'] === 'student') {
            $query->where('student_id', $identity['id']);
        }
        $referral = $query->with([
            'student:id,student_id,last_name,first_name,middle_name,course_year,department,sex,age,blood_type',
            'fromCampus:id,name,code,contact',
            'toCampus:id,name,code,contact',
            'referredBy:id,first_name,last_name,role,license_no',
        ])->findOrFail($id);
        $resolvedReferral = $this->referralSpecialistResolver->resolve([$referral->toArray()])[0];

        return response()->json(['success' => true, 'referral' => $resolvedReferral]);
    }

    // -----------------------------------------------------------------------
    // GET /api/clinic/referrals/stats
    // -----------------------------------------------------------------------
    public function stats(Request $request): JsonResponse
    {
        $identity = $this->identity($request);
        $campusId = $this->parseCampusId($request);
        $base = Referral::query();
        if ($identity['role'] === 'student') {
            $base->where('student_id', $identity['id']);
        } else {
            $base->when($campusId, fn ($q) => $q->forCampus($campusId));
        }

        return response()->json([
            'success' => true,
            'stats' => [
                'total' => (clone $base)->count(),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'accepted' => (clone $base)->where('status', 'accepted')->count(),
                'completed' => (clone $base)->where('status', 'completed')->count(),
                'urgent' => (clone $base)->urgent()->where('status', 'pending')->count(),
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
     * @return array{id:int, role:string, campus_id:int|null}
     */
    private function identity(Request $request): array
    {
        $identity = $request->attributes->get('clinic_identity');
        abort_unless(is_array($identity) && isset($identity['id'], $identity['role']), 401, 'Authentication required.');

        return $identity;
    }

}
