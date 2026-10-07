<?php

namespace App\Http\Controllers\Api\StockManager;

use App\Services\AuditService;
use Illuminate\Http\Request;

/**
 * JSON twin of StockTransferController — outgoing/incoming transfers,
 * receipt verification and rejection.
 *
 * Route order matters: the specific endpoints are declared before
 * /stock-transfers/{transfer} in routes/api.php.
 */
class SmTransferController extends SmBaseController
{
    use SmTransferSupport;

    /** Outgoing + incoming transfers for the active branch, newest first. */
    public function index(Request $request)
    {
        $branchId = $this->activeBranchId($request);

        $select = 'id,transfer_number,stock_type,from_branch_id,to_branch_id,status,note,officer_name,officer_phone,officer_id,created_by,received_at,created_at';
        $out = $this->supabase->query('stock_transfers', [
            'select' => $select,
            'from_branch_id' => "eq.{$branchId}",
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);
        $in = $this->supabase->query('stock_transfers', [
            'select' => $select,
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
            $transferIds[] = (int) $m['id'];
        }

        $branchNames = $this->branchNameMap($branchIds);

        $itemCounts = [];
        if ($transferIds) {
            $items = $this->supabase->query('stock_transfer_items', [
                'select' => 'transfer_id,status',
                'transfer_id' => 'in.('.implode(',', $transferIds).')',
            ]);
            foreach ($items as $it) {
                if (! isset($itemCounts[(int) $it['transfer_id']])) {
                    $itemCounts[(int) $it['transfer_id']] = ['total' => 0, 'pending' => 0, 'returned' => 0];
                }
                $itemCounts[(int) $it['transfer_id']]['total']++;
                if (($it['status'] ?? 'in_transit') === 'in_transit') {
                    $itemCounts[(int) $it['transfer_id']]['pending']++;
                } elseif (($it['status'] ?? '') === 'returned') {
                    $itemCounts[(int) $it['transfer_id']]['returned']++;
                }
            }
        }

        $rows = [];
        foreach ($transfers as $t) {
            $rows[] = [
                'id' => $t['id'],
                'transfer_number' => $t['transfer_number'] ?? null,
                'stock_type' => $t['stock_type'] ?? null,
                'stock_type_label' => $this->typeLabel($t['stock_type'] ?? ''),
                'from_branch_id' => (int) ($t['from_branch_id'] ?? 0),
                'to_branch_id' => (int) ($t['to_branch_id'] ?? 0),
                'from_branch_name' => $branchNames[(int) ($t['from_branch_id'] ?? 0)] ?? 'Branch #'.($t['from_branch_id'] ?? '?'),
                'to_branch_name' => $branchNames[(int) ($t['to_branch_id'] ?? 0)] ?? 'Branch #'.($t['to_branch_id'] ?? '?'),
                'status' => $t['status'] ?? 'in_transit',
                'officer_name' => $t['officer_name'] ?? null,
                'officer_phone' => $t['officer_phone'] ?? null,
                'received_at' => $t['received_at'] ?? null,
                'created_at' => $t['created_at'] ?? null,
                'items_total' => $itemCounts[(int) $t['id']]['total'] ?? 0,
                'items_pending' => $itemCounts[(int) $t['id']]['pending'] ?? 0,
                'items_returned' => $itemCounts[(int) $t['id']]['returned'] ?? 0,
            ];
        }

        return response()->json([
            'transfers' => $rows,
            'branchName' => $this->scope->branchName($branchId),
            'activeBranchId' => $branchId,
            'scope' => $this->scopePayload($request),
        ]);
    }

    /** Transfer-out form data per stock type (branches + branch stock). */
    public function create(Request $request)
    {
        $type = $this->assertValidType($request->query('type'));
        $branchId = $this->activeBranchId($request);
        $this->assertTransferTypeAccess($request, $type);
        $this->assertWritable($request);

        $branches = $this->targetBranches($type, $branchId);

        $data = [
            'type' => $type,
            'type_label' => $this->typeLabel($type),
            'branches' => $branches,
            'fromBranchId' => $branchId,
            'fromBranchName' => $this->scope->branchName($branchId),
            'is_products_only' => $this->isProductsOnly($request),
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
                    'id' => 'in.('.implode(',', $productIds).')',
                ]);
                foreach ($list as $p) {
                    $products[(int) $p['id']] = $p;
                }
            }

            // Per-product variety buckets with stock > 0, so the form offers
            // only the bottlings this branch actually has.
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
                $variant = (string) ($vr['variant'] ?? \App\Services\BottleStockService::VARIANT_PLAIN);
                $varietyStock[$pid][$volume][$variant] = ($varietyStock[$pid][$volume][$variant] ?? 0) + (int) ($vr['quantity'] ?? 0);
            }

            $productVarieties = [];
            foreach ($varietyStock as $pid => $pidVarieties) {
                $volumes = [];
                foreach ($pidVarieties as $v => $variantsInStock) {
                    if (array_sum($variantsInStock) <= 0) {
                        continue;
                    }
                    $variants = [];
                    foreach ($this->bottles->variantBuckets((int) $v) as $key) {
                        $qty = (int) ($variantsInStock[$key] ?? 0);
                        if ($qty <= 0) {
                            continue;
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
            $data['hasVarietyTracking'] = ! empty($productVarieties);

            $options = [];
            foreach ($rows as $r) {
                $pid = (int) ($r['product_id'] ?? 0);
                $p = $products[$pid] ?? null;
                $options[] = [
                    'product_id' => $pid,
                    'name' => $p['name'] ?? ('Product #'.$pid),
                    'brand' => $p['brand'] ?? null,
                    'available' => (int) ($r['quantity'] ?? 0),
                    'unit_cost' => (float) ($r['buying_cost'] ?? 0),
                    'unit_price' => (float) ($r['selling_price'] ?? 0),
                    'supplier' => $r['supplier'] ?? null,
                    'category' => $p['category'] ?? ($r['category'] ?? null),
                ];
            }
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
                $variant = (string) ($b['variant'] ?? \App\Services\BottleStockService::VARIANT_PLAIN);
                $options[] = [
                    'volume' => $this->bottles->volumeLabel($volume),
                    'variant' => $variant,
                    'variant_label' => $this->bottles->variantLabel($variant, $volume),
                    'available' => (int) ($b['quantity'] ?? 0),
                ];
            }
            $data['options'] = $options;
            $data['volumes'] = array_map(fn ($v) => $this->bottles->volumeLabel($v), \App\Services\BottleStockService::VOLUMES);
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

        return response()->json($data);
    }

    /** Create the transfer, stock out from the source branch. */
    public function store(Request $request)
    {
        $type = $this->assertValidType($request->input('type'));
        $fromBranchId = $this->activeBranchId($request);
        $this->assertTransferTypeAccess($request, $type);
        $this->assertWritable($request);

        $validated = $request->validate([
            'to_branch_id' => 'required|integer',
            'officer_name' => 'required|string|max:191',
            'officer_phone' => 'required|string|max:64',
            'officer_id' => 'nullable|string|max:64',
            'note' => 'nullable|string|max:1000',
        ]);

        $toBranchId = (int) $validated['to_branch_id'];
        if ($toBranchId === (int) $fromBranchId) {
            $this->fail(['to_branch_id' => 'The target branch must be different from your branch.']);
        }

        // Head Quarters may only be the target of Product Stock transfers.
        if ($this->isBottleType($type) && $this->isHQBranch($toBranchId)) {
            $this->fail(['to_branch_id' => 'Head Quarters-Mikocheni only receives Product Stock transfers.']);
        }

        $rawItems = $request->input('items', []);
        $resolved = $this->resolveItems($type, is_array($rawItems) ? $rawItems : [], $fromBranchId);

        $toBranchName = $this->scope->branchName($toBranchId);
        $transferNumber = 'TF-'.now()->format('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

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
            'created_by' => $this->performingUserId($request),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        if (! $transfer || empty($transfer['id'])) {
            $this->fail(['error' => 'Could not create the transfer record. Please run database/supabase_stock_transfers.sql in Supabase first, then retry.']);
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

            $this->fail(['error' => 'Could not save the transfer items. Please try again.']);
        }

        foreach ($resolved as $item) {
            $this->applyOut($type, $item, $fromBranchId, $transferNumber, $toBranchName, $this->performingUserId($request));
        }

        $this->recordTransferAudit(
            'transfer_created',
            'Stock transfer confirmed',
            'Transfer '.$transferNumber.' of '.$this->typeLabel($type).' with '.count($resolved)
                .' item(s) created from '.$this->scope->branchName($fromBranchId).' to '.$toBranchName.'.'
        );

        return response()->json([
            'message' => "Transfer {$transferNumber} created. {$this->typeLabel($type)} has been moved to transfer stock and is waiting for {$toBranchName} to receive it.",
            'transfer' => $transfer,
        ]);
    }

    /** Stock waiting to be received by the active branch. */
    public function incoming(Request $request)
    {
        $branchId = $this->activeBranchId($request);

        $transfers = $this->supabase->query('stock_transfers', [
            'select' => 'id,transfer_number,stock_type,from_branch_id,to_branch_id,note,officer_name,officer_phone,officer_id,created_at',
            'to_branch_id' => "eq.{$branchId}",
            'status' => 'eq.in_transit',
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);

        // Head Quarters-Mikocheni only receives Product Stock.
        if ($this->isHQBranch((int) $branchId)) {
            $transfers = array_values(array_filter(
                $transfers,
                fn ($t) => ! $this->isBottleType((string) ($t['stock_type'] ?? ''))
            ));
        }

        $transferIds = array_map(fn ($t) => (int) $t['id'], $transfers);
        $items = [];
        if ($transferIds) {
            $items = $this->supabase->query('stock_transfer_items', [
                'select' => 'id,transfer_id,stock_type,item_index,product_id,name,volume,variant,type,color,quantity,unit_cost,unit_price,variety_unit_price,category,supplier,status',
                'transfer_id' => 'in.('.implode(',', $transferIds).')',
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
                'id' => 'in.('.implode(',', array_keys($productIds)).')',
            ]);
            foreach ($list as $p) {
                $productNames[(int) $p['id']] = $p;
            }
        }
        $branchNames = $this->branchNameMap($branchIds);

        foreach ($items as $it) {
            $t = $transferMap[(int) $it['transfer_id']] ?? null;
            if (! $t) {
                continue;
            }
            $labels = $this->incomingLabels($it, $productNames);
            $rows[] = [
                'item' => $it,
                'item_name' => $labels['name'],
                'variety_label' => $labels['variety'],
                'oil_type' => $labels['type'],
                'item_label' => $this->itemLabel($it, $productNames),
                'transfer_number' => $t['transfer_number'] ?? null,
                'stock_type' => $t['stock_type'] ?? null,
                'stock_type_label' => $this->typeLabel($t['stock_type'] ?? ''),
                'from_branch_id' => (int) ($t['from_branch_id'] ?? 0),
                'from_branch_name' => $branchNames[(int) ($t['from_branch_id'] ?? 0)] ?? 'Branch #'.($t['from_branch_id'] ?? '?'),
                'note' => $t['note'] ?? null,
                'officer_name' => $t['officer_name'] ?? null,
                'officer_phone' => $t['officer_phone'] ?? null,
                'officer_id' => $t['officer_id'] ?? null,
                'created_at' => $t['created_at'] ?? null,
            ];
        }

        return response()->json([
            'rows' => $rows,
            'branchName' => $this->scope->branchName($branchId),
            'activeBranchId' => $branchId,
            'scope' => $this->scopePayload($request),
        ]);
    }

    /** Verify one transferred item and stock it into the branch. */
    public function receive(Request $request, int $itemId)
    {
        $branchId = $this->activeBranchId($request);
        $this->assertWritable($request);

        $item = $this->supabase->find('stock_transfer_items', $itemId);
        if (! $item) {
            $this->fail(['error' => 'Transfer item not found.']);
        }

        $transfer = $this->supabase->find('stock_transfers', (int) ($item['transfer_id'] ?? 0));
        if (! $transfer || ($transfer['status'] ?? '') !== 'in_transit') {
            $this->fail(['error' => 'This transfer is no longer awaiting receipt.']);
        }
        if ((int) ($transfer['to_branch_id'] ?? 0) !== (int) $branchId) {
            $this->fail(['error' => 'This transfer is not destined for your branch.']);
        }
        if (($item['status'] ?? '') !== 'in_transit') {
            $this->fail(['error' => 'This item has already been received.']);
        }

        $itemType = (string) ($item['stock_type'] ?? 'product');
        if ($itemType !== (string) ($transfer['stock_type'] ?? '')) {
            $this->fail(['error' => 'The item type does not match the transfer. Verification blocked.']);
        }
        $this->assertTransferTypeAccess($request, $itemType);

        // The verified quantity is always the exact quantity sent.
        if ((int) ($item['quantity'] ?? 0) <= 0) {
            $this->fail(['error' => 'This transfer item has an invalid quantity and cannot be verified.']);
        }

        $now = now()->toIso8601String();
        $performedBy = $this->performingUserId($request);
        $fromBranchName = $this->scope->branchName((int) ($transfer['from_branch_id'] ?? 0));
        $reason = 'Received from stock transfer '.($transfer['transfer_number'] ?? '').' (from '.($fromBranchName ?? 'another branch').')';

        $received = $this->applyIn($itemType, $item, $branchId, $reason, $performedBy);
        if (! $received) {
            $this->fail(['error' => 'Could not record the stock-in. Please try again.']);
        }

        $this->supabase->update('stock_transfer_items', [
            'status' => 'received',
            'received_by' => $performedBy,
            'received_at' => $now,
            'updated_at' => $now,
        ], ['id' => (int) $item['id']]);

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

        $this->recordTransferAudit(
            'transfer_item_received',
            'Stock item verified & received',
            "{$this->typeLabel($itemType)} {$this->itemDescription($item)} (qty {$item['quantity']}) verified and added to "
                .$this->scope->branchName($branchId).' from transfer '.($transfer['transfer_number'] ?? '')
        );

        return response()->json([
            'message' => 'Item verified and added to '.$this->scope->branchName($branchId).' stock.',
        ]);
    }

    /** Reject an item: return it to the sending branch and alert the admins. */
    public function receiveInvalid(Request $request, int $itemId)
    {
        $branchId = $this->activeBranchId($request);
        $this->assertWritable($request);

        $validated = $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ]);

        $item = $this->supabase->find('stock_transfer_items', $itemId);
        if (! $item) {
            $this->fail(['error' => 'Transfer item not found.']);
        }

        $transfer = $this->supabase->find('stock_transfers', (int) ($item['transfer_id'] ?? 0));
        if (! $transfer || ($transfer['status'] ?? '') !== 'in_transit') {
            $this->fail(['error' => 'This transfer is no longer awaiting receipt.']);
        }
        if ((int) ($transfer['to_branch_id'] ?? 0) !== (int) $branchId) {
            $this->fail(['error' => 'This transfer is not destined for your branch.']);
        }
        if (($item['status'] ?? '') !== 'in_transit') {
            $this->fail(['error' => 'This item has already been received or returned.']);
        }

        $itemType = (string) ($item['stock_type'] ?? 'product');
        if ($itemType !== (string) ($transfer['stock_type'] ?? '')) {
            $this->fail(['error' => 'The item type does not match the transfer. Verification blocked.']);
        }
        $this->assertTransferTypeAccess($request, $itemType);

        $now = now()->toIso8601String();
        $performedBy = $this->performingUserId($request);
        $fromBranchId = (int) ($transfer['from_branch_id'] ?? 0);
        $fromBranchName = $this->scope->branchName($fromBranchId) ?? 'the sending branch';

        $restored = $this->applyReturnIn($itemType, $item, $fromBranchId,
            'Returned by '.($this->scope->branchName($branchId) ?? 'receiver')
            .' — invalid item from stock transfer '.($transfer['transfer_number'] ?? ''), $performedBy);

        if (! $restored) {
            $this->fail(['error' => 'Could not return the item to '.$fromBranchName.'. Please try again.']);
        }

        $this->supabase->update('stock_transfer_items', [
            'status' => 'returned',
            'return_reason' => $validated['reason'],
            'return_status' => 'pending',
            'returned_by' => $performedBy,
            'returned_at' => $now,
            'updated_at' => $now,
        ], ['id' => (int) $item['id']]);

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

        $this->recordTransferAudit(
            'transfer_item_returned',
            'Transfer item rejected (invalid)',
            "{$this->typeLabel($itemType)} {$this->itemDescription($item)} (qty {$item['quantity']}) from transfer "
                .($transfer['transfer_number'] ?? '').' was rejected by '.$this->scope->branchName($branchId)
                .' and returned to '.$fromBranchName.'. Reason: '.$validated['reason']
        );

        return response()->json([
            'message' => 'Item rejected and returned to '.$fromBranchName." stock. Their stock manager has been notified.",
        ]);
    }

    /** Full detail of one transfer (sender and receiver both may view). */
    public function show(Request $request, int $transferId)
    {
        $transfer = $this->supabase->find('stock_transfers', $transferId);
        if (! $transfer) {
            abort(404, 'Transfer not found.');
        }

        $branchId = $this->activeBranchId($request);
        $fromId = (int) ($transfer['from_branch_id'] ?? 0);
        $toId = (int) ($transfer['to_branch_id'] ?? 0);
        if ($fromId !== $branchId && $toId !== $branchId) {
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
                'id' => 'in.('.implode(',', array_keys($productIds)).')',
            ]);
            foreach ($list as $p) {
                $productNames[(int) $p['id']] = $p;
            }
        }

        $userNames = $this->resolveUserNames($userIds);
        $branchNames = $this->branchNameMap([$fromId, $toId]);

        $itemRows = [];
        $pendingCount = 0;
        foreach ($items as $it) {
            if (($it['status'] ?? '') === 'in_transit') {
                $pendingCount++;
            }
            $itemRows[] = [
                'item' => $it,
                'item_label' => $this->itemLabel($it, $productNames),
                'received_by_name' => $userNames[(int) ($it['received_by'] ?? 0)] ?? null,
                'received_at' => $it['received_at'] ?? null,
            ];
        }

        return response()->json([
            'transfer' => $transfer,
            'stock_type_label' => $this->typeLabel((string) ($transfer['stock_type'] ?? '')),
            'from_branch_name' => $branchNames[$fromId] ?? 'Branch #'.$fromId,
            'to_branch_name' => $branchNames[$toId] ?? 'Branch #'.$toId,
            'created_by_name' => $userNames[(int) ($transfer['created_by'] ?? 0)] ?? null,
            'received_by_name' => $userNames[(int) ($transfer['received_by'] ?? 0)] ?? null,
            'items' => $itemRows,
            'pendingCount' => $pendingCount,
            'activeBranchId' => $branchId,
        ]);
    }
}
