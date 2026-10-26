<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMedicalRecordRequest;
use App\Models\Appointment;
use App\Models\ClinicNotification;
use App\Models\Staff;
use App\Services\AppointmentTriageService;
use App\Services\AuditService;
use App\Services\ReferralSpecialistResolver;
use App\Services\TriagePredictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Nurse Head dashboard API.
 *
 * Nurse Head is a clinic-management role that sits between the regular nurse
 * (who only handles visits / forms / queues) and the admin (who owns the
 * whole system). The nurse head needs read/write access to the staff roster
 * and read access to all clinical records + reports across campuses.
 *
 * Rather than duplicating AdminController logic, this controller is a thin
 * wrapper that delegates to the existing admin methods. This keeps a single
 * source of truth for staff/records/reports behavior and lets us later
 * restrict specific operations (e.g. prevent nurse head from promoting
 * other staff to admin) without changing AdminController.
 *
 * The web-layer session middleware (`clinic.auth:nurse_head`) gates dashboard
 * access. Appointment status mutations additionally require nurse-head
 * identity middleware and are recorded in the audit log.
 */
class NurseHeadController extends Controller
{
    public function __construct(
        private AdminController $admin,
        private AuditService $audit,
        private ReferralSpecialistResolver $referralSpecialistResolver,
    ) {}

    // -----------------------------------------------------------------------
    // Staff
    // -----------------------------------------------------------------------
    public function staff(Request $request): JsonResponse
    {
        return $this->admin->staff($request);
    }

    public function addStaff(Request $request): JsonResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'in:nurse'],
        ]);

        return $this->admin->addStaff($request);
    }

    public function getStaff(string $employeeId): JsonResponse
    {
        if (! $this->canManageStaff($employeeId)) {
            return response()->json(['error' => 'Staff member cannot be managed by this role'], 403);
        }

        return $this->admin->getStaff($employeeId);
    }

    public function updateStaff(Request $request, string $employeeId): JsonResponse
    {
        if (! $this->canManageStaff($employeeId)) {
            return response()->json(['error' => 'Staff member cannot be managed by this role'], 403);
        }

        $request->validate([
            'role' => ['sometimes', 'string', 'in:nurse'],
        ]);

        return $this->admin->updateStaff($request, $employeeId);
    }

    public function deleteStaff(Request $request, string $employeeId): JsonResponse
    {
        if (! $this->canManageStaff($employeeId)) {
            return response()->json(['error' => 'Staff member cannot be managed by this role'], 403);
        }

        return $this->admin->deleteStaff($request, $employeeId);
    }

    public function assignStaffCampus(Request $request, string $employeeId): JsonResponse
    {
        if (! $this->canManageStaff($employeeId)) {
            return response()->json(['error' => 'Staff member cannot be managed by this role'], 403);
        }

        return $this->admin->assignStaffCampus($request, $employeeId);
    }

    private function canManageStaff(string $employeeId): bool
    {
        return Staff::query()
            ->where('employee_id', strtoupper($employeeId))
            ->whereIn('role', ['nurse'])
            ->exists();
    }

    /**
     * List active campuses. Used to populate the Add Staff modal
     * and the campus filter dropdown on the nurse-head dashboard.
     */
    public function campuses(): JsonResponse
    {
        return $this->admin->campuses();
    }

    // -----------------------------------------------------------------------
    // Records & reports delegate to the admin controller to keep shared query
    // behavior centralized; medical-record creation is separately audited.
    // -----------------------------------------------------------------------
    public function records(Request $request): JsonResponse
    {
        return $this->admin->records($request);
    }

    public function medicalRecordCampuses(Request $request): JsonResponse
    {
        return $this->admin->medicalRecordCampuses($request);
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

    /**
     * Vital-signs ledger view (clinic_visits with at least one objective
     * vital recorded). Forces type=vital on the underlying records call so
     * the JS client doesn't need to know about the records router.
     */
    public function recordsVital(Request $request): JsonResponse
    {
        $request->query->set('type', 'vital');

        return $this->admin->records($request);
    }

    /**
     * Referrals list (Referral rows with student + campus relationships).
     * Forces type=referral on the underlying records call.
     */
    public function recordsReferral(Request $request): JsonResponse
    {
        $request->query->set('type', 'referral');

        $response = $this->admin->records($request);
        $payload = $response->getData(true);
        $payload['records'] = $this->referralSpecialistResolver->resolve($payload['records'] ?? []);

        return response()->json($payload, $response->getStatusCode());
    }

    // -----------------------------------------------------------------------
    // Single-record detail endpoints. Each forwards to AdminController::record
    // with the right `type` value so the JS layer doesn't need to know about
    // the records router.
    // -----------------------------------------------------------------------
    public function recordMedical(Request $request, int $id): JsonResponse
    {
        return $this->admin->record($request, 'medical-records', $id);
    }

    public function recordVital(Request $request, int $id): JsonResponse
    {
        return $this->admin->record($request, 'vital', $id);
    }

    public function recordReferral(Request $request, int $id): JsonResponse
    {
        $response = $this->admin->record($request, 'referral', $id);
        $payload = $response->getData(true);

        if (isset($payload['record']) && is_array($payload['record'])) {
            $payload['record'] = $this->referralSpecialistResolver->resolve([$payload['record']])[0];
        }

        return response()->json($payload, $response->getStatusCode());
    }

    /**
     * Partial update of a medical record (clinic_visit). Only the provided
     * fields are changed; patient/campus identities are immutable.
     */
    public function updateMedicalRecord(Request $request, int $id): JsonResponse
    {
        return $this->admin->updateMedicalRecord($request, $id);
    }

    /**
     * Delete a medical record (clinic_visit).
     */
    public function deleteMedicalRecord(Request $request, int $id): JsonResponse
    {
        return $this->admin->deleteMedicalRecord($request, $id);
    }

    /**
     * Student detail (used by the Patient Information View button).
     * Reuses AdminController::student — same shape, same eager loads.
     */
    public function student(Request $request, string $studentNo): JsonResponse
    {
        return $this->admin->student($request, $studentNo);
    }

    /**
     * Paginated, searchable student list for the Patient Information table.
     * Delegates to AdminController::students (supports ?page, ?limit, ?search,
     * ?campus_id).
     */
    public function students(Request $request): JsonResponse
    {
        return $this->admin->students($request);
    }

    /**
     * Update a student (partial PUT). Nurse heads may edit patient
     * demographics but not the immutable identity fields (student_id stays
     * the URL key).
     */
    public function updateStudent(Request $request, string $studentNo): JsonResponse
    {
        return $this->admin->updateStudent($request, $studentNo);
    }

    /**
     * Delete a student. Nurse heads may remove patient records.
     */
    public function deleteStudent(Request $request, string $studentNo): JsonResponse
    {
        return $this->admin->deleteStudent($request, $studentNo);
    }

    public function reports(Request $request): JsonResponse
    {
        return $this->admin->reports($request);
    }

    // -----------------------------------------------------------------------
    // Appointments — read + nurse-head status update.
    // -----------------------------------------------------------------------
    public function appointments(Request $request, TriagePredictionService $triage): JsonResponse
    {
        $request->attributes->set('exclude_referred_appointments', true);
        $response = $this->admin->appointments($request);
        $payload = $response->getData(true);
        $payload['model_accuracy'] = $triage->modelAccuracy();

        return response()->json($payload, $response->getStatusCode());
    }

    public function triageAppointment(Request $request, int $id, AppointmentTriageService $triage): JsonResponse
    {
        return $this->admin->triageAppointment($request, $id, $triage);
    }

    public function updateAppointmentStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:pending,completed,cancelled'],
            'cancellation_reason' => ['required_if:status,cancelled', 'string', 'max:1000'],
        ]);
        $appointment = Appointment::findOrFail($id);
        if ($data['status'] === 'completed' && $appointment->status !== 'confirmed') {
            abort(409, 'Only provider appointments can be marked as completed.');
        }
        if ($data['status'] === 'completed' && $appointment->status === 'confirmed'
            && ($appointment->appt_date === null || $appointment->appt_date->isFuture())) {
            abort(409, 'A scheduled appointment cannot be completed before its scheduled date.');
        }
        if ($data['status'] === 'pending' && $appointment->status !== 'pending') {
            abort(409, 'Accepted or scheduled appointments cannot be returned to pending.');
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

        /** @var array{id:int, role:string, campus_id:int|null} $identity */
        $identity = $request->attributes->get('clinic_identity');
        $this->audit->log(
            $request,
            $identity['id'],
            'staff',
            'update_appointment_status',
            'appointments',
            $appointment->id,
            ['status' => $oldStatus],
            [
                'status' => $newStatus,
                'appt_date' => $updates['appt_date'] ?? $oldSchedule['appt_date'],
                'appt_time' => $updates['appt_time'] ?? $oldSchedule['appt_time'],
                'cancellation_reason' => $data['cancellation_reason'] ?? null,
            ],
        );
        return response()->json(['success' => true]);
    }
}
