<?php

namespace App\Http\Controllers\Api\StockManager;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureStaffSessionApi;
use App\Services\BottleStockService;
use App\Services\ProductVarietyStockService;
use App\Services\StockManagerScope;
use App\Services\SupabaseService;
use App\Support\BranchCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Shared plumbing for the Stock Manager JSON twins.
 *
 * The website resolves "which branch am I working in" from a session
 * (cross-branch monitoring) and "what may I see" from the branch's category.
 * The app is stateless, so the monitored branch arrives as ?branch_id= and
 * the branch category (autonomous vs products_based) is re-read on every
 * call — the same rules, without the cookie.
 */
abstract class SmBaseController extends Controller
{
    protected SupabaseService $supabase;

    protected BottleStockService $bottles;

    protected StockManagerScope $scope;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->bottles = new BottleStockService($this->supabase);
        $this->scope = new StockManagerScope($this->supabase);
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

    /** The manager's own branch — never the monitored one. */
    protected function ownBranchId(Request $request): int
    {
        return (int) ($this->user($request)['branch_id'] ?? 0);
    }

    /**
     * The id movements are stamped with. The session middleware reads the
     * Supabase users row, so its id IS the Supabase id — the same value the
     * website writes via supabase_id ?? auth()->id().
     */
    protected function performingUserId(Request $request): int
    {
        return (int) ($this->user($request)['id'] ?? 0);
    }

    /** Only the Kinondoni branch stock manager may monitor other branches. */
    protected function canMonitorCrossBranch(Request $request): bool
    {
        $user = $this->user($request);
        if (($user['role'] ?? '') !== 'stock_manager') {
            return false;
        }

        $name = $this->scope->branchName((int) ($user['branch_id'] ?? 0));

        return $name !== null
            && mb_strtolower(trim($name)) === mb_strtolower(trim(StockManagerScope::KINONDONI_BRANCH_NAME));
    }

    /**
     * Branch this request reads: the manager's own branch, or an explicit
     * ?branch_id= while monitoring — the stateless twin of the website's
     * enter/exit cross-branch session.
     */
    protected function activeBranchId(Request $request): int
    {
        $own = $this->ownBranchId($request);
        $requested = (int) $request->query('branch_id', 0);

        if ($requested > 0 && $requested !== $own && $this->canMonitorCrossBranch($request)) {
            return $requested;
        }

        return $own;
    }

    /** True while reading another branch's screens (monitoring session). */
    protected function inCrossBranchMode(Request $request): bool
    {
        return $this->activeBranchId($request) !== $this->ownBranchId($request);
    }

    /**
     * Products-only branch (branch.category = products_based): its stock
     * manager works with product stock alone — bottles, oil fragrance and
     * bottle accessories are closed to him, on every screen.
     */
    protected function isProductsOnly(Request $request): bool
    {
        $category = $this->scope->branchCategory($this->ownBranchId($request));

        return BranchCategory::from($category) === BranchCategory::PRODUCTS_BASED;
    }

    /** Website: the `stock-manager.bottle-access` middleware. */
    protected function assertBottleAccess(Request $request): void
    {
        if ($this->isProductsOnly($request)) {
            abort(403, 'Bottle, oil fragrance and bottle accessories management are not available for your branch.');
        }
    }

    /** A products-only branch only sends and receives Product Stock. */
    protected function assertTransferTypeAccess(Request $request, string $type): void
    {
        if (in_array($type, ['bottle', 'oil_fragrance', 'bottle_accessories'], true) && $this->isProductsOnly($request)) {
            abort(403, 'Products-only branches only receive and manage Product Stock transfers.');
        }
    }

    /** Website: the `cross-branch.access` middleware. */
    protected function assertCrossBranchMonitor(Request $request): void
    {
        if (! $this->canMonitorCrossBranch($request)) {
            abort(403, 'Only the Kinondoni branch stock manager or the Super Admin can monitor other branches.');
        }
    }

    /** Website: the `cross-branch.readonly` middleware — monitoring never writes. */
    protected function assertWritable(Request $request): void
    {
        if ($this->inCrossBranchMode($request)) {
            abort(403, 'Cross-branch monitoring is read-only. Exit the branch to make changes.');
        }
    }

    /** Sales and orders of the monitored branch stay hidden. */
    protected function assertOwnBranchOnly(Request $request): void
    {
        if ($this->inCrossBranchMode($request)) {
            abort(403, 'Sales and orders are hidden while monitoring another branch. Exit the branch to view them.');
        }
    }

    /** Website: the returned-stock routes are the Kinondoni manager's only. */
    protected function assertReturnedStockAccess(Request $request): void
    {
        $name = $this->scope->branchName($this->ownBranchId($request));

        if ($name === null || mb_strtolower(trim($name)) !== mb_strtolower(trim(StockManagerScope::KINONDONI_BRANCH_NAME))) {
            abort(403, 'The Returned Stock module is reserved for the Kinondoni branch stock manager.');
        }
    }

    /** Apply the active-branch filter to a Supabase param set. */
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

    /** The branch/category flags the app uses to hide whole sections. */
    protected function scopePayload(Request $request): array
    {
        $ownBranchId = $this->ownBranchId($request);
        $category = \App\Support\BranchCategory::from($this->scope->branchCategory($ownBranchId));

        return [
            'is_products_only' => $this->isProductsOnly($request),
            'branch_category' => $category,
            'branch_category_label' => \App\Support\BranchCategory::label($category),
            'own_branch_id' => $ownBranchId,
            'branch_id' => $this->activeBranchId($request),
            'branch_name' => $this->scope->branchName($this->activeBranchId($request)),
            'in_cross_branch' => $this->inCrossBranchMode($request),
            'can_monitor_cross_branch' => $this->canMonitorCrossBranch($request),
            // The Returned Stock module is the Kinondoni manager's only — the
            // same person allowed to monitor branches, by website design.
            'can_returned_stock' => $this->canMonitorCrossBranch($request),
        ];
    }

    /**
     * Resolve performed_by ids to display names: prefer the Supabase user,
     * fall back through the local user's supabase_id mapping, then the local
     * user name — same chain the website uses.
     */
    protected function resolvePerformedByNames(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, fn ($id) => $id > 0);
        $map = [];
        if (empty($ids)) {
            return $map;
        }

        $supById = [];
        $supRows = $this->supabase->query('users', [
            'select' => 'id,name',
            'id' => 'in.('.implode(',', $ids).')',
            'limit' => 200,
        ]);
        foreach ($supRows as $u) {
            $supById[(int) $u['id']] = $u['name'];
        }

        $localUsers = \App\Models\User::whereIn('id', $ids)
            ->get(['id', 'name', 'supabase_id'])
            ->keyBy('id');

        foreach ($ids as $id) {
            $local = $localUsers->get($id);
            if ($local && ! empty($local->supabase_id) && (int) $local->supabase_id !== $id && isset($supById[(int) $local->supabase_id])) {
                $map[$id] = $supById[(int) $local->supabase_id];
            } elseif (isset($supById[$id])) {
                $map[$id] = $supById[$id];
            } elseif ($local && $local->name) {
                $map[$id] = $local->name;
            }
        }

        return $map;
    }

    /**
     * Load movements with a PHP-side join for performedBy (PostgREST
     * expansions like performedBy:users(id,name) silently return 0 rows).
     */
    protected function loadMovementsWithUser(string $table, int $branchId, int $limit, array $extraParams = []): array
    {
        $params = array_merge([
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => $limit,
        ], $extraParams);
        $params['branch_id'] = "eq.{$branchId}";

        $rows = $this->supabase->query($table, $params);

        $userIds = [];
        foreach ($rows as $r) {
            if (! empty($r['performed_by'])) {
                $userIds[$r['performed_by']] = true;
            }
        }

        $names = $this->resolvePerformedByNames(array_keys($userIds));

        return collect($rows)->map(function ($r) use ($names) {
            $r['performedBy'] = ! empty($r['performed_by']) && isset($names[$r['performed_by']])
                ? ['id' => (int) $r['performed_by'], 'name' => $names[$r['performed_by']]]
                : null;

            return $r;
        })->all();
    }

    /**
     * Branch ids the active stock manager may act on as the SENDER of a
     * transfer: the active branch, or every branch while monitoring —
     * same rule as StockTransferController::senderBranchIds().
     */
    protected function senderBranchIds(Request $request): array
    {
        if ($this->inCrossBranchMode($request)) {
            $rows = $this->supabase->query('branches', [
                'select' => 'id',
                'is_active' => 'eq.true',
            ]);

            return array_map(fn ($b) => (int) $b['id'], $rows);
        }

        return [$this->activeBranchId($request)];
    }
}
