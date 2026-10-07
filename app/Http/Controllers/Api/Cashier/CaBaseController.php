<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureStaffSessionApi;
use App\Services\BottleStockService;
use App\Services\ProductVarietyStockService;
use App\Services\SupabaseService;
use App\Support\BranchAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Shared plumbing for the Cashier JSON twins.
 *
 * The website's cashier group is `role:cashier,super_admin` — the same two
 * roles this API group accepts — and its screens follow one member through
 * CashierScope: reads resolve an ACTIVE branch (own branch, or another
 * branch while an HQ cashier / Super Admin is monitoring it), while every
 * write is refused during monitoring ("read-only. Exit the branch").
 *
 * CashierScope keeps that mode in the HTTP session; this API is stateless
 * (an encrypted token, no cookie jar), so the monitored branch travels with
 * each request as an optional `monitor_branch` query/body value and the
 * server re-checks monitor rights on every call — the app can never grant
 * itself the HQ tier.
 */
abstract class CaBaseController extends Controller
{
    protected SupabaseService $supabase;

    protected BottleStockService $bottles;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->bottles = new BottleStockService($this->supabase);
    }

    /** Per-product bottling buckets (branch_stock_varieties). */
    protected function varieties(): ProductVarietyStockService
    {
        return new ProductVarietyStockService($this->supabase);
    }

    /** The staff row the session middleware resolved for this request. */
    protected function user(Request $request): array
    {
        return EnsureStaffSessionApi::user($request) ?? [];
    }

    /** The cashier's home branch. */
    protected function ownBranchId(Request $request): int
    {
        return (int) ($this->user($request)['branch_id'] ?? 0);
    }

    /**
     * The id sales, movements and reviews are stamped with. The session
     * middleware reads the Supabase users row, so its id IS the Supabase id
     * — the same value the website writes via supabase_id ?? auth()->id().
     */
    protected function performingUserId(Request $request): int
    {
        return (int) ($this->user($request)['id'] ?? 0);
    }

    /**
     * Website rule (CashierScope::isCrossBranchMonitor): only the HQ cashier
     * and the Super Admin may monitor other branches. The check is recomputed
     * from the database row on every request, never from anything the app
     * sends.
     */
    protected function isCrossBranchMonitor(Request $request): bool
    {
        $user = $this->user($request);
        $role = (string) ($user['role'] ?? '');

        if ($role === 'super_admin') {
            return true;
        }

        if ($role !== 'cashier') {
            return false;
        }

        $branch = $this->supabase->find('branches', (int) ($user['branch_id'] ?? 0), 'id,name');
        $name = mb_strtolower(trim((string) ($branch['name'] ?? '')));

        return $name !== '' && $name === mb_strtolower(trim(BranchAccess::HEAD_QUARTERS));
    }

    /**
     * The branch this request reads: the monitored branch when an allowed
     * monitor asked for one, otherwise the cashier's own branch — the same
     * resolution CashierScope::activeBranchId() performs from the session.
     */
    protected function activeBranchId(Request $request): int
    {
        $own = $this->ownBranchId($request);
        $requested = (int) $request->query('monitor_branch', 0);

        if ($requested > 0 && $requested !== $own && $this->isCrossBranchMonitor($request)) {
            return $requested;
        }

        return $own;
    }

    /**
     * Website rule (EnsureCashierCrossBranchReadOnly): while monitoring
     * another branch everything is read-only. Any write from a request that
     * names another branch is refused with the website's message.
     *
     * @never
     */
    protected function assertNotMonitoring(Request $request): void
    {
        $own = $this->ownBranchId($request);
        $requested = (int) $request->input('monitor_branch', $request->query('monitor_branch', 0));

        if ($requested > 0 && $requested !== $own && $this->isCrossBranchMonitor($request)) {
            abort(403, 'Cross-branch monitoring is read-only. Exit the branch to make changes.');
        }
    }

    /** Apply a read scope to a Supabase param set. */
    protected function branchParams(Request $request, array $params): array
    {
        $params['branch_id'] = 'eq.'.$this->activeBranchId($request);

        return $params;
    }

    /**
     * JSON twin of the website's `back()->withErrors([...])`: a 422 carrying
     * the same field errors the app maps onto its forms.
     *
     * @never
     */
    protected function fail(array $errors): void
    {
        throw ValidationException::withMessages($errors);
    }

    /** The flags the app shapes navigation from. */
    protected function scopePayload(Request $request): array
    {
        $own = $this->ownBranchId($request);
        $active = $this->activeBranchId($request);
        $monitor = $this->isCrossBranchMonitor($request);
        $branch = $this->supabase->find('branches', $active, 'id,name');

        return [
            'role' => 'cashier',
            'own_branch_id' => $own,
            'branch_id' => $active,
            'branch_name' => $branch['name'] ?? null,
            'is_cross_branch_monitor' => $monitor,
            'in_cross_branch' => $monitor && $active !== $own,
        ];
    }
}
