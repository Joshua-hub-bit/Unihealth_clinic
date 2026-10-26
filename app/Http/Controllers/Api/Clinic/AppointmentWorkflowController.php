<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ClinicAvailability;
use App\Models\ClinicNotification;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentWorkflowController extends Controller
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function accept(Request $request, int $id): JsonResponse
    {
        $identity = $this->identity($request);
        $appointment = DB::transaction(function () use ($identity, $id, $request): Appointment {
            $appointment = $this->appointmentQuery($identity)->lockForUpdate()->findOrFail($id);
            abort_unless($appointment->status === 'pending', 409, 'Only pending appointment requests can be accepted.');
            $oldAppointment = [
                'status' => $appointment->status,
                'appt_date' => $appointment->appt_date?->toDateString(),
                'appt_time' => $appointment->appt_time,
                'availability_id' => $appointment->availability_id,
            ];
            $appointment->update([
                'status' => 'accepted',
                'appt_date' => null,
                'appt_time' => null,
                'availability_id' => null,
            ]);
            $this->audit->log(
                $request,
                $identity['id'],
                'staff',
                'accept_appointment',
                'appointments',
                $appointment->id,
                $oldAppointment,
                [
                    'status' => $appointment->status,
                    'appt_date' => null,
                    'appt_time' => null,
                    'availability_id' => null,
                ],
            );

            return $appointment->fresh();
        });

        ClinicNotification::notifyStudent(
            $appointment->student_id,
            'appointment',
            'Appointment request accepted',
            'The clinic accepted your request. The clinic will arrange your initial check-up first.',
            ['appointment_id' => $appointment->id],
        );

        return response()->json(['success' => true, 'status' => $appointment->status]);
    }

    public function schedule(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'availability_id' => ['required', 'integer', 'exists:clinic_availabilities,id'],
            'appt_time' => ['required', 'date_format:H:i'],
        ]);
        $identity = $this->identity($request);
        $appointment = DB::transaction(function () use ($identity, $id, $request, $data): Appointment {
            $appointment = $this->appointmentQuery($identity)->lockForUpdate()->findOrFail($id);
            abort_unless($appointment->status === 'checkup_completed', 409, 'Complete the clinic check-up before scheduling a provider appointment.');

            $availability = ClinicAvailability::query()
                ->lockForUpdate()
                ->findOrFail($data['availability_id']);
            $providerType = $appointment->service_type === 'Dental Check-up' ? 'dentist' : 'physician';
            abort_unless(
                $availability->is_available
                    && (int) $availability->campus_id === (int) $appointment->campus_id
                    && $availability->provider_type === $providerType
                    && $availability->available_date->gte(today()),
                409,
                'Choose a published appointment time for this campus and service.',
            );
            abort_unless(
                in_array($data['appt_time'], $availability->available_times ?? [], true),
                409,
                'Choose a time published by the clinic.',
            );
            if ($availability->available_date->isToday() && $data['appt_time'] <= now()->format('H:i')) {
                abort(409, 'The selected appointment time has already passed.');
            }

            $otherAppointments = $availability->appointments()
                ->whereIn('status', ['pending', 'accepted', 'confirmed'])
                ->where('appointments.id', '<>', $appointment->id);
            abort_unless(
                (clone $otherAppointments)->count() < $availability->capacity,
                409,
                'This clinic schedule has reached its appointment capacity.',
            );
            abort_unless(
                ! (clone $otherAppointments)->where('appt_time', $data['appt_time'])->exists(),
                409,
                'This appointment time has already been reserved.',
            );

            $oldSchedule = [
                'status' => $appointment->status,
                'appt_date' => $appointment->appt_date?->toDateString(),
                'appt_time' => $appointment->appt_time,
                'availability_id' => $appointment->availability_id,
            ];
            $appointment->update([
                'status' => 'confirmed',
                'appt_date' => $availability->available_date,
                'appt_time' => $data['appt_time'],
                'preferred_time' => null,
                'availability_id' => $availability->id,
            ]);
            $this->audit->log(
                $request,
                $identity['id'],
                'staff',
                'schedule_accepted_appointment',
                'appointments',
                $appointment->id,
                $oldSchedule,
                [
                    'status' => $appointment->status,
                    'appt_date' => $appointment->appt_date?->toDateString(),
                    'appt_time' => $appointment->appt_time,
                    'availability_id' => $appointment->availability_id,
                ],
            );

            return $appointment->fresh();
        });

        ClinicNotification::notifyStudent(
            $appointment->student_id,
            'appointment',
            'Provider appointment scheduled',
            'Your physician or dentist appointment is confirmed for '.$appointment->appt_date?->format('M j').' at '.substr((string) $appointment->appt_time, 0, 5).'.',
            ['appointment_id' => $appointment->id],
        );

        return response()->json([
            'success' => true,
            'status' => $appointment->status,
            'appt_date' => $appointment->appt_date?->toDateString(),
            'appt_time' => substr((string) $appointment->appt_time, 0, 5),
        ]);
    }

    public function scheduleCheckup(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'checkup_date' => ['required', 'date', 'after_or_equal:today'],
            'checkup_time' => ['required', 'date_format:H:i'],
        ]);
        $identity = $this->identity($request);
        $appointment = DB::transaction(function () use ($identity, $id, $request, $data): Appointment {
            $appointment = $this->appointmentQuery($identity)->lockForUpdate()->findOrFail($id);
            abort_unless($appointment->status === 'accepted', 409, 'Only accepted requests can be scheduled for a clinic check-up.');
            if ($data['checkup_date'] === today()->toDateString()) {
                abort_unless($data['checkup_time'] > now()->format('H:i'), 409, 'The selected check-up time has already passed.');
            }

            $oldSchedule = [
                'status' => $appointment->status,
                'checkup_date' => $appointment->checkup_date?->toDateString(),
                'checkup_time' => $appointment->checkup_time,
            ];
            $appointment->update([
                'status' => 'checkup_scheduled',
                'checkup_date' => $data['checkup_date'],
                'checkup_time' => $data['checkup_time'],
            ]);
            $this->audit->log(
                $request,
                $identity['id'],
                'staff',
                'schedule_clinic_checkup',
                'appointments',
                $appointment->id,
                $oldSchedule,
                [
                    'status' => $appointment->status,
                    'checkup_date' => $appointment->checkup_date?->toDateString(),
                    'checkup_time' => $appointment->checkup_time,
                ],
            );

            return $appointment->fresh();
        });

        ClinicNotification::notifyStudent(
            $appointment->student_id,
            'appointment',
            'Clinic check-up scheduled',
            'Your clinic check-up is scheduled for '.$appointment->checkup_date?->format('M j').' at '.substr((string) $appointment->checkup_time, 0, 5).'.',
            ['appointment_id' => $appointment->id],
        );

        return response()->json([
            'success' => true,
            'status' => $appointment->status,
            'checkup_date' => $appointment->checkup_date?->toDateString(),
            'checkup_time' => substr((string) $appointment->checkup_time, 0, 5),
        ]);
    }

    public function completeCheckup(Request $request, int $id): JsonResponse
    {
        $identity = $this->identity($request);
        $appointment = DB::transaction(function () use ($identity, $id, $request): Appointment {
            $appointment = $this->appointmentQuery($identity)->lockForUpdate()->findOrFail($id);
            abort_unless($appointment->status === 'checkup_scheduled', 409, 'Only scheduled clinic check-ups can be marked complete.');
            abort_unless($appointment->checkup_date?->lte(today()), 409, 'The clinic check-up date has not arrived yet.');
            if ($appointment->checkup_date?->isToday()) {
                abort_unless(substr((string) $appointment->checkup_time, 0, 5) <= now()->format('H:i'), 409, 'The clinic check-up time has not arrived yet.');
            }

            $appointment->update([
                'status' => 'checkup_completed',
                'checkup_completed_at' => now(),
            ]);
            $this->audit->log(
                $request,
                $identity['id'],
                'staff',
                'complete_clinic_checkup',
                'appointments',
                $appointment->id,
                ['status' => 'checkup_scheduled'],
                ['status' => $appointment->status, 'checkup_completed_at' => $appointment->checkup_completed_at?->toDateTimeString()],
            );

            return $appointment->fresh();
        });

        ClinicNotification::notifyStudent(
            $appointment->student_id,
            'appointment',
            'Clinic check-up completed',
            'Your clinic check-up is recorded as complete. The clinic will schedule your physician or dentist appointment next.',
            ['appointment_id' => $appointment->id],
        );

        return response()->json(['success' => true, 'status' => $appointment->status]);
    }

    private function appointmentQuery(array $identity): Builder
    {
        $query = Appointment::query();
        $role = $identity['role'];

        if ($role === 'nurse_head') {
            return $query->when($identity['campus_id'] !== null, fn (Builder $query) => $query->forCampus($identity['campus_id']));
        }

        abort_unless($identity['campus_id'] !== null, 403);

        return $query->where('campus_id', $identity['campus_id']);
    }

    private function identity(Request $request): array
    {
        $identity = $request->attributes->get('clinic_identity');
        abort_unless(is_array($identity) && isset($identity['id'], $identity['role']), 401, 'Authentication required.');

        return $identity;
    }
}
