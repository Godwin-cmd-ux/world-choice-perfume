<?php

namespace App\Http\Controllers\Api\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureStaffSessionApi;
use App\Services\BottleStockService;
use App\Services\ProductVarietyStockService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Shared plumbing for the Branch Admin JSON twins.
 *
 * Branch admin is a single-branch supervisory role: no cross-branch mode,
 * no Head Quarters tier — every screen is the member's own branch (the
 * website's `role:branch_admin` group is branch-scoped throughout). What
 * differs from the other staff roles is visibility, not scope: the order
 * queue shows every order at the branch instead of only the member's own,
 * and picking/serving still belongs to whoever claimed the order.
 */
abstract class BaBaseController extends Controller
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

    /** The admin's branch. */
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

    /** Apply the admin's branch to a Supabase param set. */
    protected function branchParams(Request $request, array $params): array
    {
        $params['branch_id'] = 'eq.'.$this->ownBranchId($request);

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
        $branchId = $this->ownBranchId($request);
        $branch = $this->supabase->find('branches', $branchId, 'id,name');

        return [
            'role' => 'branch_admin',
            'own_branch_id' => $branchId,
            'branch_id' => $branchId,
            'branch_name' => $branch['name'] ?? null,
        ];
    }
}
