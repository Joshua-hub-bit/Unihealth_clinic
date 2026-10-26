<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Throwable;

/**
 * Writes entries to the audit_log table.
 *
 * Drop this service into any controller via constructor injection:
 *   public function __construct(private AuditService $audit) {}
 *
 * Then call:
 *   $this->audit->log($request, $userId, 'student', 'login');
 */
class AuditService
{
    /**
     * Record an audit event.
     *
     * @param  array<string, mixed>|null  $oldData
     * @param  array<string, mixed>|null  $newData
     */
    public function log(
        Request $request,
        int|string|null $userId,
        string $userType,
        string $action,
        ?string $entityType = null,
        int|string|null $entityId = null,
        ?array $oldData = null,
        ?array $newData = null,
    ): void {
        try {
            AuditLog::create([
                'user_id' => $userId,
                'user_type' => $userType,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'old_data' => $oldData,
                'new_data' => $newData,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (Throwable $e) {
            // Audit failures must never crash the application.
            logger()->warning('[AuditService] Failed to write audit log: '.$e->getMessage());
        }
    }
}
