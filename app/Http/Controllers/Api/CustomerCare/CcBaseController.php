<?php

namespace App\Http\Controllers\Api\CustomerCare;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureStaffSessionApi;
use App\Services\BottleStockService;
use App\Services\CustomerCareScope;
use App\Services\InfoMailService;
use App\Services\ProductVarietyStockService;
use App\Services\RawEmailParser;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Shared plumbing for the Customer Care JSON twins.
 *
 * Customer care has no cross-branch mode — every screen is the member's own
 * branch. The Head Quarters-Mikocheni member additionally owns the three
 * company-wide sections (inquiries, news moderation, the info@ mailbox),
 * which the website guards with the `customer-care.hq` middleware; the same
 * rule lives here in assertHq() so the app can never reach them otherwise.
 */
abstract class CcBaseController extends Controller
{
    protected SupabaseService $supabase;

    protected BottleStockService $bottles;

    protected CustomerCareScope $hq;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->bottles = new BottleStockService($this->supabase);
        $this->hq = new CustomerCareScope($this->supabase);
    }

    /** Per-product bottling buckets (branch_stock_varieties). */
    protected function varieties(): ProductVarietyStockService
    {
        return new ProductVarietyStockService($this->supabase);
    }

    /** The info@ mailbox service (HQ sections only). */
    protected function mails(): InfoMailService
    {
        return new InfoMailService($this->supabase, new RawEmailParser());
    }

    /** The staff row the session middleware resolved for this request. */
    protected function user(Request $request): array
    {
        return EnsureStaffSessionApi::user($request) ?? [];
    }

    /** The member's branch — always their own. */
    protected function ownBranchId(Request $request): int
    {
        return (int) ($this->user($request)['branch_id'] ?? 0);
    }

    /**
     * The id movements and reviews are stamped with. The session middleware
     * reads the Supabase users row, so its id IS the Supabase id — the same
     * value the website writes via supabase_id ?? auth()->id().
     */
    protected function performingUserId(Request $request): int
    {
        return (int) ($this->user($request)['id'] ?? 0);
    }

    protected function userName(Request $request): string
    {
        return (string) ($this->user($request)['name'] ?? 'Customer Care');
    }

    /**
     * Head Quarters customer care: role customer_care on the
     * Head Quarters-Mikocheni branch — the same branch-name comparison
     * CustomerCareScope::isHqCustomerCare() makes for the website, but read
     * from the session's staff row instead of Auth::user().
     */
    protected function isHq(Request $request): bool
    {
        $user = $this->user($request);
        if (($user['role'] ?? '') !== 'customer_care') {
            return false;
        }

        $name = $this->hq->branchName((int) ($user['branch_id'] ?? 0));

        return $name !== null
            && mb_strtolower(trim($name)) === mb_strtolower(trim(CustomerCareScope::HQ_BRANCH_NAME));
    }

    /** Website: the `customer-care.hq` middleware. */
    protected function assertHq(Request $request): void
    {
        if (! $this->isHq($request)) {
            abort(403, 'Only customer care from Head Quarters-Mikocheni can access this page.');
        }
    }

    /** Apply the member's own branch to a Supabase param set. */
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

    /** The flags the app uses to shape navigation and hide HQ sections. */
    protected function scopePayload(Request $request): array
    {
        $branchId = $this->ownBranchId($request);

        return [
            'role' => 'customer_care',
            'own_branch_id' => $branchId,
            'branch_id' => $branchId,
            'branch_name' => $this->hq->branchName($branchId),
            'is_hq' => $this->isHq($request),
        ];
    }
}
