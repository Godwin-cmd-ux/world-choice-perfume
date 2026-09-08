<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Central recorder for critical system actions (discounts, price changes,
 * deletions, account changes...). Each action is stored twice:
 *   - audit_logs: durable trail with actor, IP and old/new values
 *   - admin_notifications: feed for the super-admin notification page
 * Any failure is swallowed so it never blocks the business action itself.
 */
class AuditService
{
    private SupabaseService $supabase;

    public function __construct(?SupabaseService $supabase = null)
    {
        $this->supabase = $supabase ?? new SupabaseService();
    }

    public function recordCriticalAction(
        string $type,
        string $action,
        string $title,
        string $message,
        array $data = [],
        ?string $auditableType = null,
        ?string $auditableId = null,
        array $oldValues = [],
        array $newValues = []
    ): void {
        $user = auth()->user();
        $userId = $user?->supabase_id ?? $user?->id ?? null;
        $branchId = $user?->branch_id ?? null;
        $now = now()->toIso8601String();

        try {
            $this->supabase->insert('audit_logs', [
                'user_id' => $userId,
                'action' => $action,
                'auditable_type' => $auditableType ?: null,
                'auditable_id' => $auditableId,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => request()->ip(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Throwable $e) {
            Log::warning("AuditService: audit_logs insert failed for {$action}: " . $e->getMessage());
        }

        try {
            $this->supabase->insert('admin_notifications', [
                'type' => $type,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'data' => $data ? json_encode($data) : null,
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Throwable $e) {
            Log::warning("AuditService: admin_notifications insert failed for {$action}: " . $e->getMessage());
        }
    }
}