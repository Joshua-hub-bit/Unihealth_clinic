<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ClinicAvailability;
use App\Models\Student;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AvailabilityController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function studentIndex(Request $request): JsonResponse
    {
        $identity = $this->identity($request);
        abort_unless($identity['role'] === 'student', 403);
        $student = Student::query()->findOrFail($identity['id']);
        $schedules = ClinicAvailability::query()
            ->with('campus:id,name')
            ->where('campus_id', $student->campus_id)
            ->whereDate('available_date', '>=', today())
            ->orderBy('available_date')
            ->orderBy('provider_type')
            ->get();
        $todaySchedules = $schedules->filter(fn (ClinicAvailability $item): bool => $item->available_date->isToday());
        $unavailableToday = $todaySchedules->contains(fn (ClinicAvailability $item): bool => ! $item->is_available);
        $availableToday = $todaySchedules->filter(fn (ClinicAvailability $item): bool => $item->is_available);
        $clinicStatus = $unavailableToday
            ? ['status' => 'not_available']
            : ($availableToday->isNotEmpty()
                ? [
                    'status' => 'open',
                    'opens_at' => $availableToday->min('opens_at'),
                    'closes_at' => $availableToday->max('closes_at'),
                ]
                : ['status' => 'no_schedule']);

        $availability = $schedules
            ->filter(fn (ClinicAvailability $item): bool => $item->is_available)
            ->map(function (ClinicAvailability $item): array {
                $activeAppointments = $item->appointments()
                    ->whereIn('status', ['pending', 'accepted', 'confirmed'])
                    ->get(['appt_time', 'status']);
                $bookedTimes = $activeAppointments
                    ->pluck('appt_time')
                    ->map(fn ($time): string => substr((string) $time, 0, 5))
                    ->all();
                $bookedCount = $activeAppointments->count();
                $availableTimes = $bookedCount < $item->capacity
                    ? array_values(array_diff($item->available_times ?? [], $bookedTimes))
                    : [];

                return [
                    'id' => $item->id,
                    'campus_id' => $item->campus_id,
                    'campus_name' => $item->campus?->name,
                    'provider_type' => $item->provider_type,
                    'available_date' => $item->available_date->toDateString(),
                    'opens_at' => substr($item->opens_at, 0, 5),
                    'closes_at' => substr($item->closes_at, 0, 5),
                    'capacity' => $item->capacity,
                    'booked_count' => $bookedCount,
                    'remaining_capacity' => max(0, (int) $item->capacity - $bookedCount),
                    'available_times' => $availableTimes,
                ];
            })
            ->filter(fn (array $item): bool => $item['available_times'] !== [])
            ->values();

        return response()->json([
            'success' => true,
            'availability' => $availability,
            'clinic_status' => $clinicStatus,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $identity = $this->identity($request);
        abort_unless(in_array($identity['role'], ['nurse', 'nurse_head'], true), 403);
        $campusId = $this->campusId($request, $identity);
        $items = ClinicAvailability::query()
            ->with('campus:id,name')
            ->where('campus_id', $campusId)
            ->whereDate('available_date', '>=', today())
            ->orderBy('available_date')
            ->orderBy('provider_type')
            ->get()
            ->map(fn (ClinicAvailability $item): array => $this->availabilityPayload($item));

        return response()->json(['success' => true, 'availability' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $identity = $this->identity($request);
        abort_unless(in_array($identity['role'], ['nurse', 'nurse_head'], true), 403);
        $data = $this->validatedSchedule($request);
        $campusId = $this->campusId($request, $identity, $data['campus_id'] ?? null);
        $isAvailable = (bool) ($data['is_available'] ?? true);
        $times = $isAvailable ? $this->validatedTimes($data) : null;
        $availability = DB::transaction(function () use ($data, $identity, $campusId, $times, $isAvailable, $request): ClinicAvailability {
            $availability = ClinicAvailability::query()
                ->where('campus_id', $campusId)
                ->where('provider_type', $data['provider_type'])
                ->whereDate('available_date', $data['available_date'])
                ->lockForUpdate()
                ->first();

            if ($availability) {
                $activeAppointments = $availability->appointments()
                    ->whereIn('status', ['pending', 'accepted', 'confirmed'])
                    ->get(['appt_time']);
                $bookedTimes = $activeAppointments
                    ->pluck('appt_time')
                    ->map(fn ($time): string => substr((string) $time, 0, 5))
                    ->all();

                if ($isAvailable) {
                    abort_unless(
                        count($bookedTimes) <= (int) $data['capacity']
                            && array_diff($bookedTimes, $times) === [],
                        409,
                        'Keep every booked time and set capacity to at least the number of pending or confirmed bookings.',
                    );
                }

                $availability->update([
                    'created_by' => $identity['id'],
                    'is_available' => $isAvailable,
                    ...($isAvailable ? [
                        'opens_at' => $data['opens_at'],
                        'closes_at' => $data['closes_at'],
                        'capacity' => $data['capacity'],
                        'available_times' => $times,
                    ] : []),
                ]);
            } else {
                $availability = ClinicAvailability::query()->create([
                    'campus_id' => $campusId,
                    'created_by' => $identity['id'],
                    'provider_type' => $data['provider_type'],
                    'available_date' => $data['available_date'],
                    'is_available' => $isAvailable,
                    'opens_at' => $data['opens_at'] ?? '08:00',
                    'closes_at' => $data['closes_at'] ?? '17:00',
                    'capacity' => $data['capacity'] ?? 10,
                    'available_times' => $times ?? [],
                ]);
            }

            $bookedCount = $availability->appointments()
                ->whereIn('status', ['pending', 'accepted', 'confirmed'])
                ->count();
            $this->audit->log(
                $request,
                $identity['id'],
                'staff',
                $isAvailable ? 'set_clinic_availability' : 'set_clinic_unavailability',
                'clinic_availabilities',
                $availability->id,
                null,
                [
                    'campus_id' => $campusId,
                    'provider_type' => $availability->provider_type,
                    'available_date' => $availability->available_date->toDateString(),
                    'is_available' => $isAvailable,
                    'capacity' => $availability->capacity,
                    'available_times' => $availability->available_times ?? [],
                    'active_appointments_retained' => $bookedCount,
                ],
            );

            return $availability->fresh('campus:id,name');
        });

        return response()->json([
            'success' => true,
            'availability' => $this->availabilityPayload($availability),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $identity = $this->identity($request);
        abort_unless(in_array($identity['role'], ['nurse', 'nurse_head'], true), 403);
        $campusId = $this->campusId($request, $identity);
        $availability = DB::transaction(function () use ($identity, $campusId, $id, $request): ClinicAvailability {
            $availability = ClinicAvailability::query()
                ->where('campus_id', $campusId)
                ->lockForUpdate()
                ->findOrFail($id);
            abort_if(
                $availability->appointments()->whereIn('status', ['pending', 'accepted', 'confirmed'])->exists(),
                409,
                'This availability has active appointment requests and cannot be removed.',
            );
            $this->audit->log(
                $request,
                $identity['id'],
                'staff',
                'remove_clinic_availability',
                'clinic_availabilities',
                $availability->id,
                ['available_date' => $availability->available_date->toDateString()],
            );
            $availability->delete();

            return $availability;
        });

        return response()->json(['success' => true, 'id' => $availability->id]);
    }

    private function validatedSchedule(Request $request): array
    {
        $isAvailable = $request->boolean('is_available', true);
        $requiredWhenAvailable = $isAvailable ? 'required' : 'sometimes';
        $availableTimesRules = [$requiredWhenAvailable, 'array', 'max:10'];
        if ($isAvailable) {
            $availableTimesRules[] = 'min:1';
        }

        return $request->validate([
            'campus_id' => ['sometimes', 'nullable', 'integer', 'exists:campuses,id'],
            'provider_type' => ['required', 'in:physician,dentist'],
            'available_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'is_available' => ['sometimes', 'boolean'],
            'opens_at' => [$requiredWhenAvailable, 'date_format:H:i', 'after_or_equal:08:00', 'before:17:00'],
            'closes_at' => [$requiredWhenAvailable, 'date_format:H:i', 'after:opens_at', 'before_or_equal:17:00'],
            'capacity' => [$requiredWhenAvailable, 'integer', 'between:1,10'],
            'available_times' => $availableTimesRules,
            'available_times.*' => ['required', 'date_format:H:i', 'distinct'],
        ]);
    }

    private function validatedTimes(array $data): array
    {
        foreach ($data['available_times'] as $time) {
            if ($time < $data['opens_at'] || $time >= $data['closes_at']) {
                throw ValidationException::withMessages([
                    'available_times' => 'Every appointment time must be within the opening hours.',
                ]);
            }
            if ($data['available_date'] === today()->toDateString() && $time <= now()->format('H:i')) {
                throw ValidationException::withMessages([
                    'available_times' => 'Appointment times for today must still be in the future.',
                ]);
            }
        }

        if (count($data['available_times']) > $data['capacity']) {
            throw ValidationException::withMessages([
                'available_times' => 'The number of appointment times cannot exceed daily capacity.',
            ]);
        }

        $times = array_values(array_unique($data['available_times']));
        sort($times);

        return $times;
    }

    private function availabilityPayload(ClinicAvailability $item): array
    {
        $activeAppointments = $item->appointments()
            ->whereIn('status', ['pending', 'accepted', 'confirmed'])
            ->get(['appt_time', 'status']);
        $bookedTimes = $activeAppointments
            ->pluck('appt_time')
            ->map(fn ($time): string => substr((string) $time, 0, 5))
            ->all();
        $bookedCount = count(array_unique($bookedTimes));

        return [
            'id' => $item->id,
            'campus_id' => $item->campus_id,
            'campus_name' => $item->campus?->name,
            'provider_type' => $item->provider_type,
            'available_date' => $item->available_date->toDateString(),
            'is_available' => $item->is_available,
            'opens_at' => substr($item->opens_at, 0, 5),
            'closes_at' => substr($item->closes_at, 0, 5),
            'capacity' => $item->capacity,
            'available_times' => $item->available_times ?? [],
            'booked_times' => array_values(array_unique($bookedTimes)),
            'booked_count' => $bookedCount,
            'pending_count' => $activeAppointments->where('status', 'pending')->count(),
            'confirmed_count' => $activeAppointments->whereIn('status', ['accepted', 'confirmed'])->count(),
            'remaining_capacity' => max(0, (int) $item->capacity - $bookedCount),
        ];
    }

    private function campusId(Request $request, array $identity, ?int $requestedCampusId = null): int
    {
        if ($identity['role'] === 'nurse') {
            abort_unless($identity['campus_id'] !== null, 403, 'The nurse account must be assigned to a campus.');
            abort_unless($requestedCampusId === null || $requestedCampusId === $identity['campus_id'], 403);

            return $identity['campus_id'];
        }

        $campusId = $requestedCampusId ?? ($request->query('campus_id') ? (int) $request->query('campus_id') : null);
        abort_unless($campusId !== null, 422, 'Choose a campus for this availability.');

        return $campusId;
    }

    private function identity(Request $request): array
    {
        $identity = $request->attributes->get('clinic_identity');
        abort_unless(is_array($identity) && isset($identity['id'], $identity['role']), 401, 'Authentication required.');

        return $identity;
    }
}
