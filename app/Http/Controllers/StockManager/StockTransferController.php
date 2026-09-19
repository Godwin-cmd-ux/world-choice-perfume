<?php

namespace App\Http\Controllers\StockManager;

use App\Http\Controllers\Controller;
use App\Services\BottleStockService;
use App\Services\StockManagerScope;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public const TYPES = ['product', 'bottle', 'oil_fragrance', 'bottle_accessories'];

    private SupabaseService $supabase;
    private BottleStockService $bottles;
    private StockManagerScope $scope;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
        $this->bottles = new BottleStockService($this->supabase);
        $this->scope = new StockManagerScope($this->supabase);
    }

    private function performingUserId(): int
    {
        return (int) (auth()->user()->supabase_id ?? auth()->id());
    }

    // .=====================================================================
    // Helpers
    // .=====================================================================

    private function isBottleType(string $type): bool
    {
        return in_array($type, ['bottle', 'oil_fragrance', 'bottle_accessories'], true);
    }

    private function assertValidType(?string $type): string
    {
        $type = (string) $type;
        if (!in_array($type, self::TYPES, true)) {
            abort(404);
        }
        return $type;
    }

    /**
     * Head Quarters-Mikocheni only receives Product Stock, and its stock
     * manager has no access to bottle / oil fragrance / bottle accessories
     * management — so it may not create or receive those transfer types.
     */
    private function assertBottleAccess(string $type, bool $receiving = false): void
    {
        if ($this->isBottleType($type) && $this->scope->isHQStockManager()) {
            abort(403, 'Head Quarters-Mikocheni only receives and manages Product Stock transfers.');
        }
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'product' => 'Product Stock',
            'bottle' => 'Bottle Stock',
            'oil_fragrance' => 'Oil Fragrance',
            'bottle_accessories' => 'Bottle Accessories',
            default => ucfirst($type),
        };
    }

    private function resolveUserNames(array $ids): array
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
            'id' => 'in.(' . implode(',', $ids) . ')',
            'limit' => 100,
        ]);
        foreach ($supRows as $u) {
            $supById[(int) $u['id']] = $u['name'];
        }

        $localUsers = \App\Models\User::whereIn('id', $ids)
            ->get(['id', 'name', 'supabase_id'])
            ->keyBy('id');

        foreach ($ids as $id) {
            $local = $localUsers->get($id);
            if ($local && !empty($local->supabase_id) && (int) $local->supabase_id !== $id && isset($supById[(int) $local->supabase_id])) {
                $map[$id] = $supById[(int) $local->supabase_id];
            } elseif (isset($supById[$id])) {
                $map[$id] = $supById[$id];
            } elseif ($local && $local->name) {
                $map[$id] = $local->name;
            }
        }

        return $map;
    }

    private function branchNameMap(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
        $map = [];
        if (empty($ids)) {
            return $map;
        }

        $rows = $this->supabase->query('branches', [
            'select' => 'id,name',
            'id' => 'in.(' . implode(',', $ids) . ')',
        ]);
        foreach ($rows as $b) {
            $map[(int) $b['id']] = $b['name'];
        }
        return $map;
    }

    /**
     * All branches a stock manager may issue a transfer to.
     * Head Quarters is excluded as a TARGET for bottle / oil fragrance /
     * bottle accessories transfers — that branch only receives Product Stock.
     */
    private function targetBranches(string $type, int $fromBranchId): array
    {
        $rows = $this->supabase->query('branches', [
            'select' => 'id,name,address',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]);

        $targets = [];
        foreach ($rows as $b) {
            $id = (int) $b['id'];
            if ($id === (int) $fromBranchId) {
                continue;
            }
            if ($this->isBottleType($type) && mb_strtolower(trim($b['name'] ?? '')) === mb_strtolower(trim(StockManagerScope::HQ_BRANCH_NAME))) {
                continue;
            }
            $targets[] = (object) $b;
        }

        return $targets;
    }

    // .=====================================================================
    // INDEX — outgoing + incoming transfers for the active branch
    // .=====================================================================

    public function index()
    {
        $branchId = $this->scope->activeBranchId();

        $out = $this->supabase->query('stock_transfers', [
            'select' => 'id,transfer_number,stock_type,from_branch_id,to_branch_id,status,note,officer_name,officer_phone,officer_id,created_by,received_at,created_at',
            'from_branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);
        $in = $this->supabase->query('stock_transfers', [
            'select' => 'id,transfer_number,stock_type,from_branch_id,to_branch_id,status,note,officer_name,officer_phone,officer_id,created_by,received_at,created_at',
            'to_branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);

        $merged = [];
        foreach (array_merge($out, $in) as $row) {
            $merged[(int) $row['id']] = $row;
        }
        usort($merged, fn ($a, $b) => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
        $merged = array_slice(array_values($merged), 0, 50);

        $transfers = [];
        $branchIds = [];
        $transferIds = [];
        foreach ($merged as $m) {
            $transfers[] = $m;
            $branchIds[] = (int) ($m['from_branch_id'] ?? 0);
            $branchIds[] = (int) ($m['to_branch_id'] ?? 0);
            $branchIds[] = (int) ($m['created_by'] ?? 0);
            $transferIds[] = (int) $m['id'];
        }

        $branchNames = $this->branchNameMap($branchIds);

        $itemCounts = [];
        if ($transferIds) {
            $items = $this->supabase->query('stock_transfer_items', [
                'select' => 'transfer_id,status',
                'transfer_id' => 'in.(' . implode(',', $transferIds) . ')',
            ]);
            foreach ($items as $it) {
                if (!isset($itemCounts[(int) $it['transfer_id']])) {
                    $itemCounts[(int) $it['transfer_id']] = ['total' => 0, 'pending' => 0];
                }
                $itemCounts[(int) $it['transfer_id']]['total']++;
                if (($it['status'] ?? 'in_transit') === 'in_transit') {
                    $itemCounts[(int) $it['transfer_id']]['pending']++;
                }
            }
        }

        $transfers = collect($transfers)->map(function ($t) use ($branchNames, $itemCounts) {
            return (object) [
                'id' => $t['id'],
                'transfer_number' => $t['transfer_number'] ?? null,
                'stock_type' => $t['stock_type'] ?? null,
                'stock_type_label' => $this->typeLabel($t['stock_type'] ?? ''),
                'from_branch_id' => (int) ($t['from_branch_id'] ?? 0),
                'to_branch_id' => (int) ($t['to_branch_id'] ?? 0),
                'from_branch_name' => $branchNames[(int) ($t['from_branch_id'] ?? 0)] ?? 'Branch #' . ($t['from_branch_id'] ?? '?'),
                'to_branch_name' => $branchNames[(int) ($t['to_branch_id'] ?? 0)] ?? 'Branch #' . ($t['to_branch_id'] ?? '?'),
                'status' => $t['status'] ?? 'in_transit',
                'officer_name' => $t['officer_name'] ?? null,
                'officer_phone' => $t['officer_phone'] ?? null,
                'received_at' => $t['received_at'] ?? null,
                'created_at' => $t['created_at'] ?? null,
                'items_total' => $itemCounts[(int) $t['id']]['total'] ?? 0,
                'items_pending' => $itemCounts[(int) $t['id']]['pending'] ?? 0,
            ];
        });

        return view('stock-manager.stock-transfers.index', [
            'transfers' => $transfers,
            'branchName' => $this->scope->activeBranchName(),
            'activeBranchId' => $branchId,
            'inCrossBranch' => $this->scope->inCrossBranchMode(),
        ]);
    }

    // .=====================================================================
    // CREATE — transfer out form (per stock type)
    // .=====================================================================

    public function create(Request $request)
    {
        $type = $this->assertValidType($request->query('type'));
        $branchId = $this->scope->activeBranchId();
        $this->assertBottleAccess($type);

        $branches = $this->targetBranches($type, $branchId);

        $data = [
            'type' => $type,
            'type_label' => $this->typeLabel($type),
            'branches' => $branches,
            'fromBranchId' => $branchId,
            'fromBranchName' => $this->scope->branchName($branchId),
        ];

        if ($type === 'product') {
            $rows = $this->supabase->query('branch_stock', [
                'select' => 'product_id,quantity,buying_cost,selling_price,supplier,category',
                'branch_id' => "eq.{$branchId}",
            ]);
            $productIds = array_values(array_unique(array_filter(array_map(fn ($r) => (int) ($r['product_id'] ?? 0), $rows), fn ($id) => $id > 0)));
            $products = [];
            if ($productIds) {
                $list = $this->supabase->query('products', [
                    'select' => 'id,name,brand,category',
                    'id' => 'in.(' . implode(',', $productIds) . ')',
                ]);
                foreach ($list as $p) {
                    $products[(int) $p['id']] = $p;
                }
            }

            // Oil fragrance products are bottled into small bottles of specific
            // volumes and varieties (box / logo / color). The exact bottling is
            // recorded per product at stock-in (branch_stock_varieties), so the
            // transfer form offers only the varieties THIS product actually has
            // at this branch — e.g. "Test perfume 50ml With Box · With Logo ·
            // Yellow" — not the branch's total bottle stock.
            $varietyRows = $this->supabase->query('branch_stock_varieties', [
                'select' => 'product_id,volume,variant,quantity',
                'branch_id' => "eq.{$branchId}",
            ]);
            $varietyStock = [];
            foreach ($varietyRows as $vr) {
                $pid = (int) ($vr['product_id'] ?? 0);
                if ($pid <= 0) {
                    continue;
                }
                $volume = (int) ($vr['volume'] ?? 0);
                $variant = (string) ($vr['variant'] ?? BottleStockService::VARIANT_PLAIN);
                $varietyStock[$pid][$volume][$variant] = ($varietyStock[$pid][$volume][$variant] ?? 0) + (int) ($vr['quantity'] ?? 0);
            }

            // Per-product variety buckets with stock > 0, keyed by product id —
            // the form shows each oil fragrance product only the varieties it
            // was actually bottled into at this branch.
            $productVarieties = [];
            foreach ($varietyStock as $pid => $pidVarieties) {
                $volumes = [];
                foreach ($pidVarieties as $v => $variantsInStock) {
                    if (array_sum($variantsInStock) <= 0) {
                        continue; // This product has none of this volume.
                    }
                    $variants = [];
                    foreach ($this->bottles->variantBuckets((int) $v) as $key) {
                        $qty = (int) ($variantsInStock[$key] ?? 0);
                        if ($qty <= 0) {
                            continue; // Variety has no stock.
                        }
                        $variants[] = [
                            'key' => $key,
                            'label' => $this->bottles->variantLabel($key, (int) $v),
                            'available' => $qty,
                        ];
                    }
                    if (empty($variants)) {
                        continue;
                    }
                    $volumes[] = [
                        'volume' => (int) $v,
                        'label' => $this->bottles->volumeLabel((int) $v),
                        'variants' => $variants,
                    ];
                }
                usort($volumes, fn ($a, $b) => $a['volume'] <=> $b['volume']);
                if (empty($volumes)) {
                    continue;
                }
                $productVarieties[(string) $pid] = $volumes;
            }
            $data['productVarieties'] = $productVarieties;
            $data['hasVarietyTracking'] = !empty($productVarieties);

            $options = [];
            foreach ($rows as $r) {
                $pid = (int) ($r['product_id'] ?? 0);
                $p = $products[$pid] ?? null;
                $options[] = [
                    'product_id' => $pid,
                    'name' => $p['name'] ?? ('Product #' . $pid),
                    'brand' => $p['brand'] ?? null,
                    'available' => (int) ($r['quantity'] ?? 0),
                    'unit_cost' => (float) ($r['buying_cost'] ?? 0),
                    'unit_price' => (float) ($r['selling_price'] ?? 0),
                    'supplier' => $r['supplier'] ?? null,
                    'category' => $p['category'] ?? ($r['category'] ?? null),
                ];
            }
            // Only products the branch actually has stock of are transferable.
            usort($options, fn ($a, $b) => strcmp((string) $a['name'], (string) $b['name']));
            $data['options'] = $options;
        }

        if ($type === 'bottle') {
            $rows = $this->supabase->query('bottle_stock', [
                'select' => 'volume,variant,quantity',
                'branch_id' => "eq.{$branchId}",
            ]);
            $options = [];
            foreach ($rows as $b) {
                $volume = $this->bottles->parseVolume((string) ($b['volume'] ?? ''));
                if ($volume === null) {
                    continue;
                }
                $variant = (string) ($b['variant'] ?? BottleStockService::VARIANT_PLAIN);
                $options[] = [
                    'volume' => $this->bottles->volumeLabel($volume),
                    'variant' => $variant,
                    'variant_label' => $this->bottles->variantLabel($variant, $volume),
                    'available' => (int) ($b['quantity'] ?? 0),
                ];
            }
            $data['options'] = $options;
            $data['volumes'] = array_map(fn ($v) => $this->bottles->volumeLabel($v), BottleStockService::VOLUMES);
        }

        if ($type === 'oil_fragrance') {
            $rows = $this->supabase->query('oil_fragrance_stock', [
                'select' => 'name,volume,quantity',
                'branch_id' => "eq.{$branchId}",
            ]);
            $options = [];
            foreach ($rows as $o) {
                $options[] = [
                    'name' => (string) ($o['name'] ?? ''),
                    'volume' => is_numeric($o['volume'] ?? null) ? (string) (int) $o['volume'] : '',
                    'available' => (int) ($o['quantity'] ?? 0),
                ];
            }
            usort($options, fn ($a, $b) => strcmp($a['name'], $b['name']) ?: strcmp($a['volume'], $b['volume']));
            $data['options'] = $options;
        }

        if ($type === 'bottle_accessories') {
            $rows = $this->supabase->query('bottle_accessories', [
                'select' => 'type,color,quantity',
                'branch_id' => "eq.{$branchId}",
            ]);
            if (empty($rows)) {
                $rows = $this->supabase->query('bottle_accessories', [
                    'select' => 'type,color,quantity',
                ]);
            }
            $options = [];
            foreach ($rows as $a) {
                $options[] = [
                    'type' => (string) ($a['type'] ?? ''),
                    'color' => (string) ($a['color'] ?? ''),
                    'available' => (int) ($a['quantity'] ?? 0),
                ];
            }
            $data['options'] = $options;
        }

        return view('stock-manager.stock-transfers.create', $data);
    }

    // .=====================================================================
    // STORE — create the transfer, stock out from the source branch
    // .=====================================================================

    public function store(Request $request)
    {
        $type = $this->assertValidType($request->input('type'));
        $fromBranchId = $this->scope->activeBranchId();
        $this->assertBottleAccess($type);

        $validated = $request->validate([
            'to_branch_id' => 'required|integer',
            'officer_name' => 'required|string|max:191',
            'officer_phone' => 'required|string|max:64',
            'officer_id' => 'nullable|string|max:64',
            'note' => 'nullable|string|max:1000',
        ]);

        $toBranchId = (int) $validated['to_branch_id'];
        if ($toBranchId === (int) $fromBranchId) {
            return back()->withErrors(['to_branch_id' => 'The target branch must be different from your branch.'])->withInput();
        }

        // Head Quarters may only be the target of Product Stock transfers.
        if ($this->isBottleType($type) && $this->isHQBranch($toBranchId)) {
            return back()->withErrors(['to_branch_id' => 'Head Quarters-Mikocheni only receives Product Stock transfers.'])->withInput();
        }

        $rawItems = $request->input('items', []);
        $resolved = $this->resolveItems($type, $rawItems, $fromBranchId);
        if ($resolved instanceof \Illuminate\Http\RedirectResponse) {
            return $resolved;
        }

        $toBranchName = $this->scope->branchName($toBranchId);
        $transferNumber = 'TF-' . now()->format('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        $transfer = $this->supabase->insert('stock_transfers', [
            'transfer_number' => $transferNumber,
            'stock_type' => $type,
            'from_branch_id' => $fromBranchId,
            'to_branch_id' => $toBranchId,
            'status' => 'in_transit',
            'note' => $validated['note'] ?? null,
            'officer_name' => $validated['officer_name'],
            'officer_phone' => $validated['officer_phone'],
            'officer_id' => $validated['officer_id'] ?? null,
            'created_by' => $this->performingUserId(),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        if (!$transfer || empty($transfer['id'])) {
            return back()->withErrors(['error' => 'Could not create the transfer record. Please run database/supabase_stock_transfers.sql in Supabase first, then retry.'])->withInput();
        }

        $transferId = (int) $transfer['id'];

        $itemRows = [];
        $now = now()->toIso8601String();
        foreach ($resolved as $index => $item) {
            $itemRows[] = array_merge([
                'transfer_id' => $transferId,
                'stock_type' => $type,
                'item_index' => $index + 1,
                'quantity' => $item['quantity'],
                'status' => 'in_transit',
                'created_at' => $now,
                'updated_at' => $now,
            ], $item['columns']);
        }

        $inserted = $this->supabase->insertMany('stock_transfer_items', $itemRows);
        if ($inserted === null) {
            $this->supabase->delete('stock_transfers', ['id' => $transferId]);
            return back()->withErrors(['error' => 'Could not save the transfer items. Please try again.'])->withInput();
        }

        // Stock out from the source branch and record movements.
        foreach ($resolved as $item) {
            $this->applyOut($type, $item, $fromBranchId, $transferNumber, $toBranchName);
        }

        return redirect()->route('stock-manager.stock-transfers.index')
            ->with('success', "Transfer {$transferNumber} created. {$this->typeLabel($type)} has been moved to transfer stock and is waiting for {$toBranchName} to receive it.");
    }

    private function isHQBranch(int $branchId): bool
    {
        $name = $this->scope->branchName($branchId);
        return $name !== null && mb_strtolower(trim($name)) === mb_strtolower(trim(StockManagerScope::HQ_BRANCH_NAME));
    }

    /**
     * Validate raw form items against the source branch stock and return the
     * enriched rows (with the columns needed for stock_transfer_items).
     */
    private function resolveItems(string $type, array $rawItems, int $fromBranchId): array|\Illuminate\Http\RedirectResponse
    {
        if (empty($rawItems) || !is_array($rawItems)) {
            return back()->withErrors(['items' => 'Add at least one item to transfer.'])->withInput();
        }

        $resolved = [];
        $now = now()->toIso8601String();

        if ($type === 'product') {
            $rows = $this->supabase->queryFresh('branch_stock', [
                'select' => 'product_id,quantity,buying_cost,selling_price,supplier,category',
                'branch_id' => "eq.{$fromBranchId}",
            ]);
            $stockMap = [];
            foreach ($rows as $r) {
                $stockMap[(int) $r['product_id']] = $r;
            }

            // Per-product bottle variety availability at the source branch,
            // used to validate the volume/variety picked for oil fragrance
            // product rows against what THIS product was actually bottled
            // into at stock-in.
            $varietyStock = [];
            foreach ($this->supabase->queryFresh('branch_stock_varieties', [
                'select' => 'product_id,volume,variant,quantity',
                'branch_id' => "eq.{$fromBranchId}",
            ]) as $vr) {
                $pid = (int) ($vr['product_id'] ?? 0);
                if ($pid <= 0) {
                    continue;
                }
                $volume = (int) ($vr['volume'] ?? 0);
                $variant = (string) ($vr['variant'] ?? BottleStockService::VARIANT_PLAIN);
                $varietyStock[$pid][$volume][$variant] = ($varietyStock[$pid][$volume][$variant] ?? 0) + (int) ($vr['quantity'] ?? 0);
            }

            $pidMap = [];
            foreach ($rawItems as $ri) {
                $pid = (int) ($ri['product_id'] ?? 0);
                if ($pid > 0) {
                    $pidMap[$pid] = true;
                }
            }
            $productMap = [];
            if ($pidMap) {
                $list = $this->supabase->query('products', [
                    'select' => 'id,name,brand,category',
                    'id' => 'in.(' . implode(',', array_keys($pidMap)) . ')',
                ]);
                foreach ($list as $p) {
                    $productMap[(int) $p['id']] = $p;
                }
            }

            $errors = [];
            foreach ($rawItems as $ri) {
                $pid = (int) ($ri['product_id'] ?? 0);
                $qty = (int) ($ri['quantity'] ?? 0);
                if ($pid <= 0) {
                    $errors[] = 'Every row must have a product selected.';
                    continue;
                }
                if ($qty <= 0) {
                    $errors[] = 'Quantity for ' . ($productMap[$pid]['name'] ?? 'product') . ' must be at least 1.';
                    continue;
                }
                $stock = $stockMap[$pid] ?? null;
                if (!$stock) {
                    $errors[] = (($productMap[$pid]['name'] ?? 'Product #' . $pid) . ' has no stock record at your branch.');
                    continue;
                }
                if (((int) ($stock['quantity'] ?? 0)) < $qty) {
                    $errors[] = 'Insufficient stock for ' . ($productMap[$pid]['name'] ?? 'product') . '. Available: ' . ($stock['quantity'] ?? 0) . '.';
                    continue;
                }

                // Oil fragrance products are bottled into specific volumes and
                // varieties (box / logo / color) — require the exact variety so
                // the transfer records which bottling was shipped. Only
                // varieties THIS product was bottled into at stock-in are
                // accepted. Products stocked in before variety tracking (no
                // records at all) may transfer without a variety.
                $isOil = ($productMap[$pid]['category'] ?? ($stock['category'] ?? '')) === 'Oil Fragrance';
                $varietyVolume = '';
                $varietyVariant = '';
                $productVarietyTotal = 0;
                foreach (($varietyStock[$pid] ?? []) as $volVariants) {
                    $productVarietyTotal += array_sum($volVariants);
                }
                if ($isOil && $productVarietyTotal > 0) {
                    $varietyVolume = trim((string) ($ri['volume'] ?? ''));
                    $varietyVariant = trim((string) ($ri['variant'] ?? ''));
                    $volumeInt = (int) $varietyVolume;
                    if ($varietyVolume === '' || !in_array($volumeInt, BottleStockService::VOLUMES, true)) {
                        $errors[] = 'Select the bottle volume for ' . ($productMap[$pid]['name'] ?? 'product') . '.';
                        continue;
                    }
                    if (!in_array($varietyVariant, $this->bottles->variantBuckets($volumeInt), true)) {
                        $errors[] = 'Select the bottle variety (box / logo / color) for ' . ($productMap[$pid]['name'] ?? 'product') . '.';
                        continue;
                    }
                    $varietyAvailable = (int) ($varietyStock[$pid][$volumeInt][$varietyVariant] ?? 0);
                    if ($varietyAvailable <= 0) {
                        $errors[] = ($productMap[$pid]['name'] ?? 'product') . ' — ' . $this->bottles->volumeLabel($volumeInt)
                            . ' (' . $this->bottles->variantLabel($varietyVariant, $volumeInt) . ') has no stock at your branch for this product.';
                        continue;
                    }
                    if ($varietyAvailable < $qty) {
                        $errors[] = 'Insufficient stock for ' . ($productMap[$pid]['name'] ?? 'product') . ' — ' . $this->bottles->volumeLabel($volumeInt)
                            . ' (' . $this->bottles->variantLabel($varietyVariant, $volumeInt) . '). Available: ' . $varietyAvailable . '.';
                        continue;
                    }
                }

                // Per-variety pricing: the price travels with the exact
                // bottling (50ml ≠ 30ml). The form may state it; otherwise
                // the bucket's recorded price is used, falling back to the
                // product's branch price.
                $varietyPrice = null;
                if ($isOil && $varietyVolume !== '' && $varietyVariant !== '') {
                    $varietyPrice = (float) trim((string) ($ri['variety_price'] ?? ''));
                    if ($varietyPrice <= 0) {
                        $varietyPrice = (new \App\Services\ProductVarietyStockService($this->supabase))
                            ->priceFor($fromBranchId, $pid, (int) $varietyVolume, $varietyVariant);
                    }
                    if ($varietyPrice <= 0) {
                        $varietyPrice = (float) ($stock['selling_price'] ?? 0);
                    }
                }

                $resolved[] = [
                    'quantity' => $qty,
                    'columns' => [
                        'product_id' => $pid,
                        'name' => $productMap[$pid]['name'] ?? null,
                        'unit_cost' => (float) ($stock['buying_cost'] ?? 0),
                        'unit_price' => (float) ($stock['selling_price'] ?? 0),
                        'variety_unit_price' => $varietyPrice !== null ? round($varietyPrice, 2) : null,
                        'category' => $stock['category'] ?? null,
                        'supplier' => $stock['supplier'] ?? null,
                        'volume' => $isOil ? (string) (int) $varietyVolume : null,
                        'variant' => $isOil ? $varietyVariant : null,
                    ],
                ];
            }
            if ($errors) {
                return back()->withErrors(['items' => implode(' ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? ' And more.' : '')])->withInput();
            }
            return $resolved;
        }

        if ($type === 'bottle') {
            $rows = $this->supabase->queryFresh('bottle_stock', [
                'select' => 'volume,variant,quantity',
                'branch_id' => "eq.{$fromBranchId}",
            ]);
            $stockMap = [];
            foreach ($rows as $b) {
                $volume = $this->bottles->parseVolume((string) ($b['volume'] ?? ''));
                if ($volume === null) {
                    continue;
                }
                $variant = (string) ($b['variant'] ?? BottleStockService::VARIANT_PLAIN);
                $stockMap[$volume . '|' . $variant] = (int) ($b['quantity'] ?? 0);
            }

            $errors = [];
            foreach ($rawItems as $ri) {
                $label = (string) ($ri['volume'] ?? '');
                $variant = (string) ($ri['variant'] ?? BottleStockService::VARIANT_PLAIN);
                $qty = (int) ($ri['quantity'] ?? 0);
                $volume = $this->bottles->parseVolume($label);
                if ($volume === null) {
                    $errors[] = 'Every bottle row must have a valid volume.';
                    continue;
                }
                if ($qty <= 0) {
                    $errors[] = "Quantity for {$label} bottles must be at least 1.";
                    continue;
                }
                if (!in_array($variant, $this->bottles->variantBuckets($volume), true)) {
                    $variant = BottleStockService::VARIANT_PLAIN;
                }
                $available = $stockMap[$volume . '|' . $variant] ?? 0;
                if ($available < $qty) {
                    $errors[] = "Insufficient {$label} ({$this->bottles->variantLabel($variant, $volume)}) bottles. Available: {$available}.";
                    continue;
                }
                $resolved[] = [
                    'quantity' => $qty,
                    'columns' => [
                        'volume' => $this->bottles->volumeLabel($volume),
                        'variant' => $variant,
                    ],
                ];
            }
            if ($errors) {
                return back()->withErrors(['items' => implode(' ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? ' And more.' : '')])->withInput();
            }
            return $resolved;
        }

        if ($type === 'oil_fragrance') {
            $rows = $this->supabase->queryFresh('oil_fragrance_stock', [
                'select' => 'name,volume,quantity',
                'branch_id' => "eq.{$fromBranchId}",
            ]);
            $stockMap = [];
            foreach ($rows as $o) {
                $stockMap[(string) ($o['name'] ?? '') . '|' . (string) (int) ($o['volume'] ?? 0)] = (int) ($o['quantity'] ?? 0);
            }

            $errors = [];
            foreach ($rawItems as $ri) {
                $name = (string) ($ri['name'] ?? '');
                $vol = (string) ($ri['volume'] ?? '');
                $qty = (int) ($ri['quantity'] ?? 0);
                if ($name === '') {
                    $errors[] = 'Every oil fragrance row must have a fragrance selected.';
                    continue;
                }
                if ($qty <= 0) {
                    $errors[] = "Quantity for {$name} must be at least 1.";
                    continue;
                }
                $available = $stockMap[$name . '|' . ($vol !== '' ? $vol : '0')] ?? 0;
                if ($available < $qty) {
                    $errors[] = "Insufficient {$name} (" . ($vol ? $vol . 'ml' : 'no volume') . ") oil fragrance. Available: {$available}.";
                    continue;
                }
                $resolved[] = [
                    'quantity' => $qty,
                    'columns' => [
                        'name' => $name,
                        'volume' => $vol,
                    ],
                ];
            }
            if ($errors) {
                return back()->withErrors(['items' => implode(' ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? ' And more.' : '')])->withInput();
            }
            return $resolved;
        }

        // bottle_accessories
        $rows = $this->supabase->queryFresh('bottle_accessories', [
            'select' => 'type,color,quantity',
            'branch_id' => "eq.{$fromBranchId}",
        ]);
        $stockMap = [];
        foreach ($rows as $a) {
            $stockMap[(string) ($a['type'] ?? '') . '|' . (string) ($a['color'] ?? '')] = (int) ($a['quantity'] ?? 0);
        }

        $errors = [];
        foreach ($rawItems as $ri) {
            $typeVal = (string) ($ri['type'] ?? '');
            $color = (string) ($ri['color'] ?? '');
            $qty = (int) ($ri['quantity'] ?? 0);
            if (!in_array($typeVal, ['straws', 'bottlenecks', 'bottle_tops'], true) || !in_array($color, ['silver', 'gold'], true)) {
                $errors[] = 'Every bottle accessories row must have a type and color.';
                continue;
            }
            if ($qty <= 0) {
                $errors[] = 'Quantity for bottle accessories must be at least 1.';
                continue;
            }
            $available = $stockMap[$typeVal . '|' . $color] ?? 0;
            if ($available < $qty) {
                $errors[] = "Insufficient " . ucfirst(str_replace('_', ' ', $typeVal)) . ' (' . ucfirst($color) . ') packets. Available: ' . $available . '.';
                continue;
            }
            $resolved[] = [
                'quantity' => $qty,
                'columns' => [
                    'type' => $typeVal,
                    'color' => $color,
                ],
            ];
        }
        if ($errors) {
            return back()->withErrors(['items' => implode(' ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? ' And more.' : '')])->withInput();
        }
        return $resolved;
    }

    /**
     * Increment (positive) or decrement (negative) the per-product variety
     * bucket in branch_stock_varieties for a branch.
     */
    private function adjustProductVarietyStock(int $branchId, int $productId, int $volume, string $variant, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        $existing = $this->supabase->findOne('branch_stock_varieties', [
            'branch_id' => $branchId,
            'product_id' => $productId,
            'volume' => $volume,
            'variant' => $variant,
        ]);

        if ($existing) {
            $this->supabase->update('branch_stock_varieties', [
                'quantity' => max(((int) ($existing['quantity'] ?? 0)) + $delta, 0),
                'updated_at' => now()->toIso8601String(),
            ], ['id' => $existing['id']]);
        } elseif ($delta > 0) {
            $this->supabase->insert('branch_stock_varieties', [
                'branch_id' => $branchId,
                'product_id' => $productId,
                'volume' => $volume,
                'variant' => $variant,
                'quantity' => $delta,
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);
        }
    }

    /**
     * Human-readable variety suffix (e.g. " — 30ml With Box · With Logo · Yellow")
     * for transfer items that carry a bottle volume/variety.
     */
    private function varietySuffix(array $columns): string
    {
        $volume = (string) ($columns['volume'] ?? '');
        $variant = (string) ($columns['variant'] ?? '');
        if ($volume === '' || (int) $volume <= 0) {
            return '';
        }
        $volumeInt = (int) $volume;
        $label = $this->bottles->volumeLabel($volumeInt);
        if ($variant !== '') {
            $label .= ' ' . $this->bottles->variantLabel($variant, $volumeInt);
        }
        return ' — ' . $label;
    }

    /**
     * Stock out a single resolved item from the source branch and record the
     * matching movement row.
     */
    private function applyOut(string $type, array $item, int $fromBranchId, string $transferNumber, ?string $toBranchName): void
    {
        $now = now()->toIso8601String();
        $performedBy = $this->performingUserId();
        $reason = "Stock transfer to {$toBranchName} ({$transferNumber})";

        if ($type === 'product') {
            $row = $this->supabase->queryFresh('branch_stock', [
                'select' => 'id,quantity',
                'branch_id' => "eq.{$fromBranchId}",
                'product_id' => "eq.{$item['columns']['product_id']}",
                'limit' => 1,
            ]);
            $current = $row[0] ?? null;
            if (!$current) {
                return;
            }
            $newQty = (int) ($current['quantity'] ?? 0) - $item['quantity'];
            $this->supabase->update('branch_stock', ['quantity' => max($newQty, 0), 'updated_at' => $now], ['id' => $current['id']]);

            // Deduct the exact per-product variety bucket this transfer ships
            // (volume/variety are set for oil fragrance products only).
            $varietyVolume = (int) ($item['columns']['volume'] ?? 0);
            $varietyVariant = (string) ($item['columns']['variant'] ?? '');
            if ($varietyVolume > 0 && $varietyVariant !== '') {
                $this->adjustProductVarietyStock(
                    $fromBranchId,
                    (int) $item['columns']['product_id'],
                    $varietyVolume,
                    $varietyVariant,
                    -$item['quantity']
                );
            }

            $this->supabase->insert('stock_movements', [
                'branch_id' => $fromBranchId,
                'product_id' => $item['columns']['product_id'],
                'type' => 'transfer_out',
                'quantity' => -$item['quantity'],
                'unit_cost' => $item['columns']['unit_cost'] ?? null,
                'unit_price' => $item['columns']['unit_price'] ?? null,
                'performed_by' => $performedBy,
                'notes' => $reason . $this->varietySuffix($item['columns']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            return;
        }

        if ($type === 'bottle') {
            $volume = $this->bottles->parseVolume((string) ($item['columns']['volume'] ?? ''));
            if ($volume !== null) {
                $this->bottles->deduct($fromBranchId, $volume, $item['quantity'], $reason, (string) $performedBy, (string) ($item['columns']['variant'] ?? ''));
            }
            return;
        }

        if ($type === 'oil_fragrance') {
            $params = [
                'select' => 'id,quantity',
                'branch_id' => "eq.{$fromBranchId}",
                'name' => "eq.{$item['columns']['name']}",
                'limit' => 1,
            ];
            if ($item['columns']['volume'] !== '') {
                $params['volume'] = "eq.{$item['columns']['volume']}";
            }
            $row = $this->supabase->queryFresh('oil_fragrance_stock', $params);
            $current = $row[0] ?? null;
            if (!$current) {
                return;
            }
            $newQty = (int) ($current['quantity'] ?? 0) - $item['quantity'];
            $this->supabase->update('oil_fragrance_stock', ['quantity' => max($newQty, 0), 'updated_at' => $now], ['id' => $current['id']]);
            $this->supabase->insert('oil_fragrance_movements', [
                'branch_id' => $fromBranchId,
                'name' => $item['columns']['name'],
                'volume' => $item['columns']['volume'] !== '' ? (int) $item['columns']['volume'] : null,
                'type' => 'stock_out',
                'quantity' => $item['quantity'],
                'reason' => $reason,
                'performed_by' => $performedBy,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            return;
        }

        // bottle_accessories
        $row = $this->supabase->queryFresh('bottle_accessories', [
            'select' => 'id,quantity',
            'branch_id' => "eq.{$fromBranchId}",
            'type' => "eq.{$item['columns']['type']}",
            'color' => "eq.{$item['columns']['color']}",
            'limit' => 1,
        ]);
        $current = $row[0] ?? null;
        if (!$current) {
            return;
        }
        $newQty = (int) ($current['quantity'] ?? 0) - $item['quantity'];
        $this->supabase->update('bottle_accessories', ['quantity' => max($newQty, 0), 'updated_at' => $now], ['id' => $current['id']]);
        $this->supabase->insert('bottle_accessories_movements', [
            'branch_id' => $fromBranchId,
            'type' => $item['columns']['type'],
            'color' => $item['columns']['color'],
            'movement_type' => 'stock_out',
            'quantity' => $item['quantity'],
            'reason' => $reason,
            'performed_by' => $performedBy,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    // .=====================================================================
    // INCOMING — stock waiting to be received by the active branch
    // .=====================================================================

    public function incoming()
    {
        $branchId = $this->scope->activeBranchId();

        $transfers = $this->supabase->query('stock_transfers', [
            'select' => 'id,transfer_number,stock_type,from_branch_id,to_branch_id,note,officer_name,officer_phone,officer_id,created_at',
            'to_branch_id' => "eq.{$branchId}",
            'status' => 'eq.in_transit',
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);

        $transferIds = array_map(fn ($t) => (int) $t['id'], $transfers);
        $items = [];
        if ($transferIds) {
            $items = $this->supabase->query('stock_transfer_items', [
                'select' => 'id,transfer_id,stock_type,item_index,product_id,name,volume,variant,type,color,quantity,unit_cost,unit_price,category,supplier',
                'transfer_id' => 'in.(' . implode(',', $transferIds) . ')',
                'status' => 'eq.in_transit',
                'limit' => 300,
            ]);
        }

        $transferMap = [];
        foreach ($transfers as $t) {
            $transferMap[(int) $t['id']] = $t;
        }

        $rows = [];
        $productIds = [];
        $branchIds = [];
        foreach ($items as $it) {
            if ((int) ($it['product_id'] ?? 0) > 0) {
                $productIds[(int) $it['product_id']] = true;
            }
            $t = $transferMap[(int) $it['transfer_id']] ?? null;
            if ($t) {
                $branchIds[] = (int) ($t['from_branch_id'] ?? 0);
            }
        }

        $productNames = [];
        if ($productIds) {
            $list = $this->supabase->query('products', [
                'select' => 'id,name,brand',
                'id' => 'in.(' . implode(',', array_keys($productIds)) . ')',
            ]);
            foreach ($list as $p) {
                $productNames[(int) $p['id']] = $p;
            }
        }
        $branchNames = $this->branchNameMap($branchIds);

        foreach ($items as $it) {
            $t = $transferMap[(int) $it['transfer_id']] ?? null;
            if (!$t) {
                continue;
            }
            $rows[] = (object) [
                'item' => (object) $it,
                'item_label' => $this->itemLabel($it, $productNames),
                'transfer_number' => $t['transfer_number'] ?? null,
                'stock_type' => $t['stock_type'] ?? null,
                'stock_type_label' => $this->typeLabel($t['stock_type'] ?? ''),
                'from_branch_name' => $branchNames[(int) ($t['from_branch_id'] ?? 0)] ?? 'Branch #' . ($t['from_branch_id'] ?? '?'),
                'note' => $t['note'] ?? null,
                'officer_name' => $t['officer_name'] ?? null,
                'officer_phone' => $t['officer_phone'] ?? null,
                'officer_id' => $t['officer_id'] ?? null,
                'created_at' => $t['created_at'] ?? null,
            ];
        }

        return view('stock-manager.stock-transfers.incoming', [
            'rows' => collect($rows),
            'branchName' => $this->scope->activeBranchName(),
            'activeBranchId' => $branchId,
            'inCrossBranch' => $this->scope->inCrossBranchMode(),
        ]);
    }

    private function itemLabel(array $item, array $productNames): string
    {
        $type = $item['stock_type'] ?? '';

        if ($type === 'product') {
            $id = (int) ($item['product_id'] ?? 0);
            $p = $productNames[$id] ?? null;
            return ($p['name'] ?? ('Product #' . $id)) . $this->varietySuffix($item);
        }

        if ($type === 'bottle') {
            $volume = (string) ($item['volume'] ?? '');
            $variant = (string) ($item['variant'] ?? BottleStockService::VARIANT_PLAIN);
            $label = $this->bottles->volumeLabel($this->bottles->parseVolume($volume) ?? 0);
            return $label . ' — ' . $this->bottles->variantLabel($variant, $this->bottles->parseVolume($volume) ?? 0);
        }

        if ($type === 'oil_fragrance') {
            return (string) ($item['name'] ?? '') . ($item['volume'] ? ' (' . $item['volume'] . 'ml)' : '');
        }

        if ($type === 'bottle_accessories') {
            return ucfirst(str_replace('_', ' ', (string) ($item['type'] ?? ''))) . ' — ' . ucfirst((string) ($item['color'] ?? ''));
        }

        return 'Item';
    }

    // .=====================================================================
    // RECEIVE — verify one transferred item, stock it into the branch
    // .=====================================================================

    public function receiveItem(Request $request, $itemId)
    {
        $branchId = $this->scope->activeBranchId();

        $item = $this->supabase->find('stock_transfer_items', $itemId);
        if (!$item) {
            return back()->withErrors(['error' => 'Transfer item not found.']);
        }

        $transfer = $this->supabase->find('stock_transfers', (int) ($item['transfer_id'] ?? 0));
        if (!$transfer || ($transfer['status'] ?? '') !== 'in_transit') {
            return back()->withErrors(['error' => 'This transfer is no longer awaiting receipt.']);
        }
        if ((int) ($transfer['to_branch_id'] ?? 0) !== (int) $branchId) {
            return back()->withErrors(['error' => 'This transfer is not destined for your branch.']);
        }
        if (($item['status'] ?? '') !== 'in_transit') {
            return back()->withErrors(['error' => 'This item has already been received.']);
        }

        $itemType = (string) ($item['stock_type'] ?? 'product');
        $this->assertBottleAccess($itemType, receiving: true);

        $now = now()->toIso8601String();
        $performedBy = $this->performingUserId();
        $fromBranchName = $this->scope->branchName((int) ($transfer['from_branch_id'] ?? 0));
        $reason = "Received from stock transfer " . ($transfer['transfer_number'] ?? '') . " (from " . ($fromBranchName ?? 'another branch') . ')';

        $received = $this->applyIn($itemType, $item, $branchId, $reason);
        if (!$received) {
            return back()->withErrors(['error' => 'Could not record the stock-in. Please try again.']);
        }

        $this->supabase->update('stock_transfer_items', [
            'status' => 'received',
            'received_by' => $performedBy,
            'received_at' => $now,
            'updated_at' => $now,
        ], ['id' => (int) $item['id']]);

        // Once every item is received the transfer is complete.
        $remaining = $this->supabase->query('stock_transfer_items', [
            'select' => 'id',
            'transfer_id' => "eq.{$transfer['id']}",
            'status' => 'eq.in_transit',
            'limit' => 1,
        ]);

        if (count($remaining) === 0) {
            $this->supabase->update('stock_transfers', [
                'status' => 'received',
                'received_by' => $performedBy,
                'received_at' => $now,
                'updated_at' => $now,
            ], ['id' => (int) $transfer['id']]);
        }

        return redirect()->route('stock-manager.stock-transfers.incoming')
            ->with('success', 'Item received and added to ' . $this->scope->activeBranchName() . ' stock.');
    }

    /**
     * Stock one transfer item into the receiving branch and record the
     * matching stock_in movement. Returns false on failure.
     */
    private function applyIn(string $type, array $item, int $branchId, string $reason): bool
    {
        $now = now()->toIso8601String();
        $performedBy = $this->performingUserId();
        $qty = (int) ($item['quantity'] ?? 0);

        if ($qty <= 0) {
            return false;
        }

        if ($type === 'product') {
            $productId = (int) ($item['product_id'] ?? 0);
            if ($productId <= 0) {
                return false;
            }

            $existing = $this->supabase->findOne('branch_stock', [
                'branch_id' => $branchId,
                'product_id' => $productId,
            ]);

            if ($existing) {
                $done = !empty($this->supabase->update('branch_stock', [
                    'quantity' => ((int) ($existing['quantity'] ?? 0)) + $qty,
                    'date_received' => now()->format('Y-m-d'),
                    'updated_at' => $now,
                ], ['id' => $existing['id']]));
            } else {
                $created = $this->supabase->insert('branch_stock', [
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'buying_cost' => (float) ($item['unit_cost'] ?? 0),
                    'selling_price' => (float) (($item['variety_unit_price'] ?? 0) > 0 ? $item['variety_unit_price'] : ($item['unit_price'] ?? 0)),
                    'category' => $item['category'] ?? null,
                    'supplier' => $item['supplier'] ?? null,
                    'date_received' => now()->format('Y-m-d'),
                    'entered_by' => $performedBy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $done = $created !== null;
            }

            if (!$done) {
                return false;
            }

            // Credit the receiving branch's per-product variety bucket so the
            // transferred bottling is tracked for its own future transfers.
            $inVolume = (int) ($item['volume'] ?? 0);
            $inVariant = (string) ($item['variant'] ?? '');
            if ($inVolume > 0 && $inVariant !== '') {
                $this->adjustProductVarietyStock($branchId, $productId, $inVolume, $inVariant, $qty);

                // The receiving branch inherits the sending branch's
                // per-variety selling price (50ml ≠ 30ml) until it sets
                // its own price for that bottling.
                $inPrice = (float) ($item['variety_unit_price'] ?? 0);
                if ($inPrice > 0) {
                    $bucket = $this->supabase->findOne('branch_stock_varieties', [
                        'branch_id' => $branchId,
                        'product_id' => $productId,
                        'volume' => $inVolume,
                        'variant' => $inVariant,
                    ]);
                    if ($bucket && (float) ($bucket['selling_price'] ?? 0) <= 0) {
                        $this->supabase->update('branch_stock_varieties', [
                            'selling_price' => round($inPrice, 2),
                            'updated_at' => $now,
                        ], ['id' => $bucket['id']]);
                    }
                }
            }

            $this->supabase->insert('stock_movements', [
                'branch_id' => $branchId,
                'product_id' => $productId,
                'type' => 'transfer_in',
                'quantity' => $qty,
                'unit_cost' => $item['unit_cost'] ?? null,
                'unit_price' => $item['unit_price'] ?? null,
                'performed_by' => $performedBy,
                'notes' => $reason . $this->varietySuffix($item),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            return true;
        }

        if ($type === 'bottle') {
            $label = (string) ($item['volume'] ?? '');
            $volume = $this->bottles->parseVolume($label);
            if ($volume === null) {
                return false;
            }
            $variant = (string) ($item['variant'] ?? BottleStockService::VARIANT_PLAIN);
            $useVariants = $this->supabase->tableHasColumn('bottle_stock', 'variant');
            $details = $this->bottles->variantDetails($variant);

            if ($useVariants) {
                $existing = $this->supabase->findOne('bottle_stock', [
                    'branch_id' => $branchId,
                    'volume' => $label,
                    'variant' => $variant,
                ]);
            } else {
                $existing = $this->supabase->findOne('bottle_stock', [
                    'branch_id' => $branchId,
                    'volume' => $label,
                ]);
            }

            if ($existing) {
                $done = !empty($this->supabase->update('bottle_stock', array_merge([
                    'quantity' => ((int) ($existing['quantity'] ?? 0)) + $qty,
                    'updated_at' => $now,
                ], $details), ['id' => $existing['id']]));
            } else {
                $row = [
                    'branch_id' => $branchId,
                    'volume' => $label,
                    'quantity' => $qty,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if ($useVariants) {
                    $row['variant'] = $variant;
                }
                $done = $this->supabase->insert('bottle_stock', array_merge($row, $details)) !== null;
            }

            if (!$done) {
                return false;
            }

            $movement = [
                'branch_id' => $branchId,
                'volume' => $label,
                'type' => 'stock_in',
                'quantity' => $qty,
                'reason' => $reason,
                'performed_by' => $performedBy,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if ($this->supabase->tableHasColumn('bottle_stock_movements', 'variant')) {
                $movement['variant'] = $variant;
            }
            $this->supabase->insert('bottle_stock_movements', array_merge($movement, $details));
            return true;
        }

        if ($type === 'oil_fragrance') {
            $name = (string) ($item['name'] ?? '');
            $volume = ($item['volume'] ?? null) !== '' && ($item['volume'] ?? null) !== null ? (int) $item['volume'] : null;

            $conditions = ['branch_id' => $branchId, 'name' => $name];
            if ($volume !== null) {
                $conditions['volume'] = $volume;
            }
            $existing = $this->supabase->findOne('oil_fragrance_stock', $conditions);

            if ($existing) {
                $done = !empty($this->supabase->update('oil_fragrance_stock', [
                    'quantity' => ((int) ($existing['quantity'] ?? 0)) + $qty,
                    'updated_at' => $now,
                ], ['id' => $existing['id']]));
            } else {
                $row = [
                    'branch_id' => $branchId,
                    'name' => $name,
                    'volume' => $volume,
                    'quantity' => $qty,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $done = $this->supabase->insert('oil_fragrance_stock', $row) !== null;
            }

            if (!$done) {
                return false;
            }

            $this->supabase->insert('oil_fragrance_movements', [
                'branch_id' => $branchId,
                'name' => $name,
                'volume' => $volume,
                'type' => 'stock_in',
                'quantity' => $qty,
                'reason' => $reason,
                'performed_by' => $performedBy,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            return true;
        }

        // bottle_accessories
        $typeVal = (string) ($item['type'] ?? '');
        $color = (string) ($item['color'] ?? '');
        $existing = $this->supabase->findOne('bottle_accessories', [
            'branch_id' => $branchId,
            'type' => $typeVal,
            'color' => $color,
        ]);

        if ($existing) {
            $done = !empty($this->supabase->update('bottle_accessories', [
                'quantity' => ((int) ($existing['quantity'] ?? 0)) + $qty,
                'updated_at' => $now,
            ], ['id' => $existing['id']]));
        } else {
            $done = $this->supabase->insert('bottle_accessories', [
                'branch_id' => $branchId,
                'type' => $typeVal,
                'color' => $color,
                'quantity' => $qty,
                'created_at' => $now,
                'updated_at' => $now,
            ]) !== null;
        }

        if (!$done) {
            return false;
        }

        $this->supabase->insert('bottle_accessories_movements', [
            'branch_id' => $branchId,
            'type' => $typeVal,
            'color' => $color,
            'movement_type' => 'stock_in',
            'quantity' => $qty,
            'reason' => $reason,
            'performed_by' => $performedBy,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return true;
    }

    // .=====================================================================
    // SHOW — full detail of one transfer (sender + receiver both able to view)
    // .=====================================================================

    public function show($transferId)
    {
        $transfer = $this->supabase->find('stock_transfers', $transferId);
        if (!$transfer) {
            abort(404);
        }

        $branchId = $this->scope->activeBranchId();
        $fromId = (int) ($transfer['from_branch_id'] ?? 0);
        $toId = (int) ($transfer['to_branch_id'] ?? 0);
        if ($fromId !== $branchId && $toId !== $branchId && !$this->scope->isSuperAdmin()) {
            abort(403);
        }

        $items = $this->supabase->query('stock_transfer_items', [
            'select' => '*',
            'transfer_id' => "eq.{$transferId}",
            'order' => 'item_index.asc',
        ]);

        $productIds = [];
        $userIds = [$transfer['created_by'] ?? null];
        foreach ($items as $it) {
            if ((int) ($it['product_id'] ?? 0) > 0) {
                $productIds[(int) $it['product_id']] = true;
            }
            $userIds[] = $it['received_by'] ?? null;
        }

        $productNames = [];
        if ($productIds) {
            $list = $this->supabase->query('products', [
                'select' => 'id,name,brand',
                'id' => 'in.(' . implode(',', array_keys($productIds)) . ')',
            ]);
            foreach ($list as $p) {
                $productNames[(int) $p['id']] = $p;
            }
        }

        $userNames = $this->resolveUserNames($userIds);
        $branchNames = $this->branchNameMap([$fromId, $toId]);

        $items = collect($items)->map(function ($it) use ($productNames, $userNames) {
            return (object) [
                'item' => (object) $it,
                'item_label' => $this->itemLabel($it, $productNames),
                'received_by_name' => $userNames[(int) ($it['received_by'] ?? 0)] ?? null,
                'received_at' => $it['received_at'] ?? null,
            ];
        });

        return view('stock-manager.stock-transfers.show', [
            'transfer' => (object) $transfer,
            'stock_type_label' => $this->typeLabel((string) ($transfer['stock_type'] ?? '')),
            'from_branch_name' => $branchNames[$fromId] ?? 'Branch #' . $fromId,
            'to_branch_name' => $branchNames[$toId] ?? 'Branch #' . $toId,
            'created_by_name' => $userNames[(int) ($transfer['created_by'] ?? 0)] ?? null,
            'received_by_name' => $userNames[(int) ($transfer['received_by'] ?? 0)] ?? null,
            'items' => $items,
            'pendingCount' => $items->filter(fn ($i) => $i->item->status === 'in_transit')->count(),
            'activeBranchId' => $branchId,
        ]);
    }
}