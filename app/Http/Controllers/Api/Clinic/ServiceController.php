<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ClinicService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct(private AuditService $audit) {}

    // -----------------------------------------------------------------------
    // GET /api/clinic/services
    // -----------------------------------------------------------------------
    public function index(Request $request): JsonResponse
    {
        $category = (string) $request->query('category', '');
        $activeOnly = ! $request->boolean('include_inactive');

        $services = ClinicService::query()
            ->when($activeOnly, fn ($q) => $q->active())
            ->when($category, fn ($q) => $q->byCategory($category))
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'services' => $services]);
    }

    // -----------------------------------------------------------------------
    // POST /api/clinic/services
    // -----------------------------------------------------------------------
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'in:Medical,Dental,Laboratory,Certificate'],
            'description' => ['sometimes', 'nullable', 'string'],
            'duration_minutes' => ['sometimes', 'nullable', 'string', 'max:20'],
            'requires_appointment' => ['sometimes', 'boolean'],
        ]);

        $service = ClinicService::create(array_merge($data, ['is_active' => true]));
        $this->audit->log($request, $this->staffId($request), 'staff', 'add_service', 'clinic_services', $service->id);

        return response()->json(['success' => true, 'id' => $service->id], 201);
    }

    // -----------------------------------------------------------------------
    // PUT /api/clinic/services/{id}
    // -----------------------------------------------------------------------
    public function update(Request $request, int $id): JsonResponse
    {
        $service = ClinicService::findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'category' => ['sometimes', 'string', 'in:Medical,Dental,Laboratory,Certificate'],
            'description' => ['sometimes', 'nullable', 'string'],
            'duration_minutes' => ['sometimes', 'nullable', 'string', 'max:20'],
            'requires_appointment' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $service->update($data);
        $this->audit->log($request, $this->staffId($request), 'staff', 'update_service', 'clinic_services', $id);

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // DELETE /api/clinic/services/{id}
    // -----------------------------------------------------------------------
    public function destroy(Request $request, int $id): JsonResponse
    {
        $service = ClinicService::findOrFail($id);
        $service->update(['is_active' => false]); // soft-deactivate
        $this->audit->log($request, $this->staffId($request), 'staff', 'deactivate_service', 'clinic_services', $id);

        return response()->json(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function staffId(Request $request): ?int
    {
        $raw = $request->query('staff_id') ?? $request->query('admin_id');

        return is_numeric($raw) ? (int) $raw : null;
    }
}
