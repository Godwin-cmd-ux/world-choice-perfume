<?php

namespace App\Http\Controllers\Api\StockManager;

use App\Services\AuditService;
use Illuminate\Http\Request;

/**
 * JSON twin of the returns side of StockTransferController: returned items
 * (resend / write-off), the lost-items form, and the Kinondoni-only Returned
 * Stock module with its mandatory lost / broken report.
 */
class SmTransferReturnController extends SmBaseController
{
    use SmTransferSupport;

    /** Returned items on transfers this manager sent. */
    public function returns(Request $request)
    {
        $senderIds = $this->senderBranchIds($request);
        $rows = [];

        if (! empty($senderIds)) {
            $transfers = $this->supabase->query('stock_transfers', [
                'select' => 'id,transfer_number,stock_type,from_branch_id,to_branch_id,status,created_at',
                'from_branch_id' => 'in.('.implode(',', $senderIds).')',
                'order' => 'created_at.desc',
                'limit' => 100,
            ]);

            $transferIds = array_map(fn ($t) => (int) $t['id'], $transfers);
            $items = [];
            if ($transferIds) {
                $items = $this->supabase->query('stock_transfer_items', [
                    'select' => 'id,transfer_id,stock_type,item_index,product_id,name,volume,variant,type,color,quantity,unit_cost,unit_price,variety_unit_price,category,supplier,status,return_reason,return_status,returned_at,resent_transfer_id',
                    'transfer_id' => 'in.('.implode(',', $transferIds).')',
                    'status' => 'eq.returned',
                    'limit' => 300,
                ]);
            }

            $transferMap = [];
            foreach ($transfers as $t) {
                $transferMap[(int) $t['id']] = $t;
            }

            $productIds = [];
            foreach ($items as $it) {
                if ((int) ($it['product_id'] ?? 0) > 0) {
                    $productIds[(int) $it['product_id']] = true;
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

            foreach ($items as $it) {
                $t = $transferMap[(int) $it['transfer_id']] ?? null;
                if (! $t) {
                    continue;
                }
                $rows[] = [
                    'item' => $it,
                    'item_label' => $this->itemLabel($it, $productNames),
                    'transfer_number' => $t['transfer_number'] ?? null,
                    'stock_type' => $t['stock_type'] ?? null,
                    'stock_type_label' => $this->typeLabel($t['stock_type'] ?? ''),
                    'to_branch_name' => $this->scope->branchName((int) ($t['to_branch_id'] ?? 0)) ?? ('Branch #'.($t['to_branch_id'] ?? '?')),
                    'created_at' => $t['created_at'] ?? null,
                    'returned_at' => $it['returned_at'] ?? null,
                ];
            }
        }

        usort($rows, fn ($a, $b) => strcmp((string) ($b['returned_at'] ?? ''), (string) ($a['returned_at'] ?? '')));

        return response()->json([
            'rows' => $rows,
            'branchName' => $this->scope->branchName($this->activeBranchId($request)),
            'scope' => $this->scopePayload($request),
        ]);
    }

    /** Re-send a returned item to the same branch in a fresh transfer. */
    public function resend(Request $request, int $itemId)
    {
        $this->assertWritable($request);
        $senderIds = $this->senderBranchIds($request);

        $item = $this->supabase->find('stock_transfer_items', $itemId);
        if (! $item || ($item['status'] ?? '') !== 'returned') {
            $this->fail(['error' => 'Returned item not found.']);
        }

        $original = $this->supabase->find('stock_transfers', (int) ($item['transfer_id'] ?? 0));
        if (! $original || ! in_array((int) ($original['from_branch_id'] ?? 0), $senderIds, true)) {
            abort(403);
        }

        $type = (string) ($item['stock_type'] ?? 'product');
        $fromBranchId = (int) ($original['from_branch_id'] ?? 0);
        $toBranchId = (int) ($original['to_branch_id'] ?? 0);
        if ($toBranchId === $fromBranchId || ($this->isBottleType($type) && $this->isHQBranch($toBranchId))) {
            $this->fail(['error' => 'This item cannot be re-sent to that branch.']);
        }
        $this->assertTransferTypeAccess($request, $type);

        // Re-validate against CURRENT stock at the sending branch.
        $resolved = $this->resolveItems($type, [[
            'product_id' => $item['product_id'] ?? null,
            'name' => $item['name'] ?? null,
            'volume' => $item['volume'] ?? null,
            'variant' => $item['variant'] ?? null,
            'type' => $item['type'] ?? null,
            'color' => $item['color'] ?? null,
            'quantity' => $item['quantity'] ?? 0,
        ]], $fromBranchId);
        if (count($resolved) !== 1) {
            $this->fail(['error' => 'Could not re-validate this item for resending.']);
        }

        $performedBy = $this->performingUserId($request);
        $toBranchName = $this->scope->branchName($toBranchId) ?? 'the target branch';
        $transferNumber = 'TF-'.now()->format('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        $transfer = $this->supabase->insert('stock_transfers', [
            'transfer_number' => $transferNumber,
            'stock_type' => $type,
            'from_branch_id' => $fromBranchId,
            'to_branch_id' => $toBranchId,
            'status' => 'in_transit',
            'note' => 'Re-send of item from '.($original['transfer_number'] ?? 'transfer'),
            'officer_name' => $request->input('officer_name', $this->user($request)['name'] ?? 'Stock Manager'),
            'officer_phone' => $request->input('officer_phone', '-'),
            'officer_id' => $request->input('officer_id'),
            'created_by' => $performedBy,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        if (! $transfer || empty($transfer['id'])) {
            $this->fail(['error' => 'Could not create the re-send transfer record. Please try again.']);
        }

        $now = now()->toIso8601String();
        $inserted = $this->supabase->insertMany('stock_transfer_items', [[
            'transfer_id' => (int) $transfer['id'],
            'stock_type' => $type,
            'item_index' => 1,
            'quantity' => $resolved[0]['quantity'],
            'status' => 'in_transit',
            'created_at' => $now,
            'updated_at' => $now,
            ...$resolved[0]['columns'],
        ]]);

        if ($inserted === null) {
            $this->supabase->delete('stock_transfers', ['id' => (int) $transfer['id']]);

            $this->fail(['error' => 'Could not save the re-send transfer items. Please try again.']);
        }

        $this->applyOut($type, $resolved[0], $fromBranchId, $transferNumber, $toBranchName, $performedBy);

        $this->supabase->update('stock_transfer_items', [
            'return_status' => 'resent',
            'resent_transfer_id' => (int) $transfer['id'],
            'updated_at' => $now,
        ], ['id' => (int) $item['id']]);

        $this->recordTransferAudit(
            'transfer_item_resent',
            'Returned item re-sent',
            "{$this->typeLabel($type)} {$this->itemDescription($item)} (qty {$item['quantity']}) returned from "
                .$toBranchName.' was re-sent in transfer '.$transferNumber.'.'
        );

        return response()->json([
            'message' => "Item re-sent to {$toBranchName} in transfer {$transferNumber}.",
            'transfer' => $transfer,
        ]);
    }

    /**
     * Write a returned item off as lost. The Kinondoni manager is redirected
     * (code: damage_report_required) to the mandatory lost / broken report
     * instead of a bare write-off — same rule as the website.
     */
    public function writeOff(Request $request, int $itemId)
    {
        $validated = $request->validate([
            'loss_reason' => 'required|string|min:3|max:500',
        ]);

        $this->assertWritable($request);
        $senderIds = $this->senderBranchIds($request);

        $item = $this->supabase->find('stock_transfer_items', $itemId);
        if (! $item || ($item['status'] ?? '') !== 'returned') {
            $this->fail(['error' => 'Returned item not found.']);
        }

        $original = $this->supabase->find('stock_transfers', (int) ($item['transfer_id'] ?? 0));
        if (! $original || ! in_array((int) ($original['from_branch_id'] ?? 0), $senderIds, true)) {
            abort(403);
        }

        // Website redirects the Kinondoni manager to the damage-report form;
        // the app gets the same instruction as a structured error.
        $ownBranchName = $this->scope->branchName($this->ownBranchId($request));
        $isKinondoni = $ownBranchName !== null
            && mb_strtolower(trim($ownBranchName)) === mb_strtolower(trim(\App\Services\StockManagerScope::KINONDONI_BRANCH_NAME));

        if ($isKinondoni) {
            if (($item['return_status'] ?? '') === 'reported' || ! empty($item['damage_reported_at'])) {
                $this->fail(['error' => 'A lost / broken report was already filed for this item.']);
            }

            return response()->json([
                'message' => 'File the lost / broken report form first — the Super Admin needs the details and reasons.',
                'code' => 'damage_report_required',
                'item_id' => (int) $item['id'],
            ], 422);
        }

        $fromBranchId = (int) ($original['from_branch_id'] ?? 0);
        $type = (string) ($item['stock_type'] ?? 'product');
        $qty = (int) ($item['quantity'] ?? 0);
        $now = now()->toIso8601String();
        $reason = 'Written off as lost after return from '
            .($this->scope->branchName((int) ($original['to_branch_id'] ?? 0)) ?? 'another branch')
            .' (transfer '.($original['transfer_number'] ?? '').'): '.$validated['loss_reason'];

        $this->deductForLoss($type, $item, $fromBranchId, $reason, $this->performingUserId($request));

        $this->supabase->update('stock_transfer_items', [
            'return_status' => 'written_off',
            'loss_reason' => $validated['loss_reason'],
            'updated_at' => $now,
        ], ['id' => (int) $item['id']]);

        $this->recordTransferAudit(
            'transfer_item_written_off',
            'Returned item written off as lost',
            "{$this->typeLabel($type)} {$this->itemDescription($item)} (qty {$qty}) from transfer "
                .($original['transfer_number'] ?? '').' was written off as lost. Reason: '.$validated['loss_reason']
        );

        return response()->json(['message' => 'Item written off as lost.']);
    }

    /** Transfers the sender may declare items lost against. */
    public function lostForm(Request $request)
    {
        $senderIds = $this->senderBranchIds($request);
        $transfers = [];

        if (! empty($senderIds)) {
            $rows = $this->supabase->query('stock_transfers', [
                'select' => 'id,transfer_number,stock_type,from_branch_id,to_branch_id,created_at',
                'from_branch_id' => 'in.('.implode(',', $senderIds).')',
                'order' => 'created_at.desc',
                'limit' => 50,
            ]);
            $branchIds = array_map(fn ($t) => (int) ($t['to_branch_id'] ?? 0), $rows);
            $branchNames = $this->branchNameMap($branchIds);

            $transfers = collect($rows)->map(fn ($t) => [
                'id' => $t['id'],
                'transfer_number' => $t['transfer_number'] ?? null,
                'stock_type' => $t['stock_type'] ?? null,
                'stock_type_label' => $this->typeLabel($t['stock_type'] ?? ''),
                'to_branch_name' => $branchNames[(int) ($t['to_branch_id'] ?? 0)] ?? ('Branch #'.($t['to_branch_id'] ?? '?')),
                'created_at' => $t['created_at'] ?? null,
            ])->values()->all();
        }

        return response()->json([
            'transfers' => $transfers,
            'branchName' => $this->scope->branchName($this->activeBranchId($request)),
            'scope' => $this->scopePayload($request),
        ]);
    }

    public function declareLost(Request $request)
    {
        $validated = $request->validate([
            'transfer_id' => 'required|integer',
            'item' => 'required|string|min:1|max:191',
            'quantity' => 'required|integer|min:1|max:100000',
            'reason' => 'required|string|min:3|max:500',
        ]);

        $this->assertWritable($request);
        $senderIds = $this->senderBranchIds($request);

        $transfer = $this->supabase->find('stock_transfers', (int) $validated['transfer_id']);
        if (! $transfer || ! in_array((int) ($transfer['from_branch_id'] ?? 0), $senderIds, true)) {
            $this->fail(['error' => 'Transfer not found among your outgoing transfers.']);
        }

        $this->assertTransferTypeAccess($request, (string) ($transfer['stock_type'] ?? 'product'));

        $fromBranchId = (int) ($transfer['from_branch_id'] ?? 0);
        $type = (string) ($transfer['stock_type'] ?? 'product');
        $now = now()->toIso8601String();
        $performedBy = $this->performingUserId($request);
        $reason = 'Declared lost during transfer '.($transfer['transfer_number'] ?? '').': '.$validated['reason'];

        // Best-effort stock-out from the sending branch; the item name is
        // free text, so product/bottle stock is matched loosely.
        if ($type === 'product') {
            $productRow = $this->supabase->queryFresh('products', [
                'select' => 'id,name',
                'name' => "eq.{$validated['item']}",
                'limit' => 1,
            ]);
            $productId = (int) ($productRow[0]['id'] ?? 0);
            if ($productId > 0) {
                $row = $this->supabase->queryFresh('branch_stock', [
                    'select' => 'id,quantity',
                    'branch_id' => "eq.{$fromBranchId}",
                    'product_id' => "eq.{$productId}",
                    'limit' => 1,
                ]);
                $current = $row[0] ?? null;
                if ($current) {
                    $this->supabase->update('branch_stock', [
                        'quantity' => max(((int) ($current['quantity'] ?? 0)) - (int) $validated['quantity'], 0),
                        'updated_at' => $now,
                    ], ['id' => $current['id']]);
                }
                $this->supabase->insert('stock_movements', [
                    'branch_id' => $fromBranchId,
                    'product_id' => $productId,
                    'type' => 'transfer_out',
                    'quantity' => -(int) $validated['quantity'],
                    'performed_by' => $performedBy,
                    'notes' => $reason,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        } elseif ($type === 'bottle') {
            $volume = $this->bottles->parseVolume($validated['item']);
            if ($volume !== null) {
                $this->bottles->deduct($fromBranchId, $volume, (int) $validated['quantity'], $reason, (string) $performedBy);
            }
        } elseif ($type === 'oil_fragrance') {
            $isNumericVolume = is_numeric($validated['item']) && (int) $validated['item'] > 0;
            $conditions = ['branch_id' => "eq.{$fromBranchId}"];
            if ($isNumericVolume) {
                $conditions['volume'] = 'eq.'.(int) $validated['item'];
            } else {
                $conditions['name'] = 'eq.'.$validated['item'];
            }
            $row = $this->supabase->queryFresh('oil_fragrance_stock', array_merge([
                'select' => 'id,quantity,name,volume',
                'limit' => 1,
            ], $conditions));
            $current = $row[0] ?? null;
            if ($current) {
                $this->supabase->update('oil_fragrance_stock', [
                    'quantity' => max(((int) ($current['quantity'] ?? 0)) - (int) $validated['quantity'], 0),
                    'updated_at' => $now,
                ], ['id' => $current['id']]);
            }
            $this->supabase->insert('oil_fragrance_movements', [
                'branch_id' => $fromBranchId,
                'name' => $isNumericVolume ? 'Unknown fragrance' : $validated['item'],
                'volume' => $isNumericVolume ? (int) $validated['item'] : ($current['volume'] ?? null),
                'type' => 'stock_out',
                'quantity' => (int) $validated['quantity'],
                'reason' => $reason,
                'performed_by' => $performedBy,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        try {
            (new AuditService)->recordCriticalAction(
                'lost_items',
                'transfer_items_declared_lost',
                'Lost items declared during transfer',
                $this->scope->branchName($fromBranchId).' declared '.(int) $validated['quantity']." x '".$validated['item']
                    ."' lost during transfer ".($transfer['transfer_number'] ?? '').' to '
                    .($this->scope->branchName((int) ($transfer['to_branch_id'] ?? 0)) ?? 'another branch')
                    .'. Reason: '.$validated['reason'],
                ['transfer_id' => $transfer['id'] ?? null]
            );
        } catch (\Throwable $e) {
            // Audit is best-effort.
        }

        return response()->json(['message' => 'Lost items declared. The admin has been notified for cross-checking.']);
    }

    // ================================================================
    // RETURNED STOCK MODULE (Kinondoni branch stock manager only)
    // ================================================================

    public function returnedStockIndex(Request $request)
    {
        $this->assertReturnedStockAccess($request);

        $hasDamageColumns = $this->supabase->tableHasColumn('stock_transfer_items', 'damage_type');
        $damageSelect = $hasDamageColumns
            ? ',loss_reason,damage_type,damage_reason,damage_reported_by,damage_reported_at'
            : '';

        $transfers = $this->supabase->query('stock_transfers', [
            'select' => 'id,transfer_number,stock_type,from_branch_id,to_branch_id,status,officer_name,officer_phone,officer_id,created_at',
            'from_branch_id' => 'eq.'.$this->ownBranchId($request),
            'order' => 'created_at.desc',
            'limit' => 200,
        ]);

        $transferMap = [];
        foreach ($transfers as $t) {
            $transferMap[(int) $t['id']] = $t;
        }

        $transferIds = array_keys($transferMap);

        $items = [];
        if ($transferIds !== []) {
            $items = $this->supabase->query('stock_transfer_items', [
                'select' => 'id,transfer_id,stock_type,item_index,product_id,name,volume,variant,type,color,quantity,unit_cost,unit_price,variety_unit_price,category,supplier,status,return_reason,return_status,returned_by,returned_at,resent_transfer_id'.$damageSelect,
                'transfer_id' => 'in.('.implode(',', $transferIds).')',
                'status' => 'eq.returned',
                'limit' => 300,
            ]);
        }

        $branchIds = [];
        $userIds = [];
        $productIds = [];
        foreach ($items as $it) {
            $t = $transferMap[(int) ($it['transfer_id'] ?? 0)] ?? null;
            if (! $t) {
                continue;
            }
            $branchIds[] = (int) ($t['from_branch_id'] ?? 0);
            $branchIds[] = (int) ($t['to_branch_id'] ?? 0);
            foreach (['returned_by', 'damage_reported_by'] as $key) {
                if (! empty($it[$key])) {
                    $userIds[] = (int) $it[$key];
                }
            }
            if ((int) ($it['product_id'] ?? 0) > 0) {
                $productIds[] = (int) $it['product_id'];
            }
        }

        $branchNames = $this->branchNameMap($branchIds);
        $userNames = $this->resolveUserNames($userIds);

        $productNames = [];
        $productIds = array_values(array_unique(array_filter($productIds)));
        if ($productIds !== []) {
            $list = $this->supabase->query('products', [
                'select' => 'id,name,brand',
                'id' => 'in.('.implode(',', $productIds).')',
            ]);
            foreach ($list as $p) {
                $productNames[(int) $p['id']] = $p;
            }
        }

        $rows = [];
        foreach ($items as $it) {
            $t = $transferMap[(int) ($it['transfer_id'] ?? 0)] ?? null;
            if (! $t) {
                continue;
            }
            $rows[] = [
                'item' => $it,
                'item_label' => $this->itemLabel($it, $productNames),
                'transfer_number' => $t['transfer_number'] ?? null,
                'stock_type_label' => $this->typeLabel((string) ($t['stock_type'] ?? '')),
                'from_branch_id' => (int) ($t['from_branch_id'] ?? 0),
                'from_branch_name' => $branchNames[(int) ($t['from_branch_id'] ?? 0)] ?? ('Branch #'.($t['from_branch_id'] ?? '?')),
                'to_branch_name' => $branchNames[(int) ($t['to_branch_id'] ?? 0)] ?? ('Branch #'.($t['to_branch_id'] ?? '?')),
                'officer_name' => $t['officer_name'] ?? null,
                'officer_phone' => $t['officer_phone'] ?? null,
                'officer_id' => $t['officer_id'] ?? null,
                'return_reason' => $it['return_reason'] ?? null,
                'return_status' => $it['return_status'] ?? 'pending',
                'damage_type' => $it['damage_type'] ?? null,
                'damage_reason' => $it['damage_reason'] ?? null,
                'damage_reported_by_name' => $userNames[(int) ($it['damage_reported_by'] ?? 0)] ?? null,
                'damage_reported_at' => $it['damage_reported_at'] ?? null,
                'returned_at' => $it['returned_at'] ?? null,
            ];
        }

        usort($rows, fn ($a, $b) => strcmp((string) ($b['returned_at'] ?? ''), (string) ($a['returned_at'] ?? '')));

        return response()->json([
            'rows' => $rows,
            'branchName' => $this->scope->branchName($this->ownBranchId($request)),
            'activeBranchId' => $this->ownBranchId($request),
            'hasDamageColumns' => $hasDamageColumns,
        ]);
    }

    /** Damage-report form data for one returned item. */
    public function damageReportForm(Request $request, int $itemId)
    {
        $this->assertReturnedStockAccess($request);

        if ($this->inCrossBranchMode($request)) {
            $this->fail(['error' => 'Exit the branch monitoring session before filing lost / broken reports.']);
        }

        $item = $this->supabase->find('stock_transfer_items', $itemId);
        if (! $item || ($item['status'] ?? '') !== 'returned') {
            $this->fail(['error' => 'Returned item not found.']);
        }

        if (($item['return_status'] ?? '') === 'reported' || ! empty($item['damage_reported_at'])) {
            $this->fail(['error' => 'A lost / broken report has already been filed for this item.']);
        }

        $transfer = $this->supabase->find('stock_transfers', (int) ($item['transfer_id'] ?? 0));
        if (! $transfer) {
            $this->fail(['error' => 'Originating transfer not found.']);
        }

        if ((int) ($transfer['from_branch_id'] ?? 0) !== $this->ownBranchId($request)) {
            abort(403, 'Only items sent from your own branch can be reported as lost or broken.');
        }

        $productNames = [];
        $productId = (int) ($item['product_id'] ?? 0);
        if ($productId > 0) {
            $product = $this->supabase->find('products', $productId, 'id,name');
            if ($product) {
                $productNames[$productId] = $product;
            }
        }

        return response()->json([
            'item' => $item,
            'itemLabel' => $this->itemLabel($item, $productNames),
            'transfer' => $transfer,
            'fromBranchName' => $this->scope->branchName((int) ($transfer['from_branch_id'] ?? 0)) ?? 'your branch',
            'toBranchName' => $this->scope->branchName((int) ($transfer['to_branch_id'] ?? 0)) ?? 'another branch',
        ]);
    }

    public function damageReportStore(Request $request, int $itemId)
    {
        $this->assertReturnedStockAccess($request);

        if ($this->inCrossBranchMode($request)) {
            $this->fail(['error' => 'Exit the branch monitoring session before filing lost / broken reports.']);
        }

        $validated = $request->validate([
            'damage_type' => 'required|in:lost,broken',
            'damage_reason' => 'required|string|min:3|max:500',
        ]);

        $item = $this->supabase->find('stock_transfer_items', $itemId);
        if (! $item || ($item['status'] ?? '') !== 'returned') {
            $this->fail(['error' => 'Returned item not found.']);
        }

        $transfer = $this->supabase->find('stock_transfers', (int) ($item['transfer_id'] ?? 0));
        if (! $transfer) {
            $this->fail(['error' => 'Originating transfer not found.']);
        }

        $fromBranchId = (int) ($transfer['from_branch_id'] ?? 0);
        if ($fromBranchId !== $this->ownBranchId($request)) {
            abort(403, 'Only items sent from your own branch can be reported as lost or broken.');
        }

        if (($item['return_status'] ?? '') === 'reported' || ! empty($item['damage_reported_at'])) {
            $this->fail(['error' => 'A lost / broken report has already been filed for this item.']);
        }

        $now = now()->toIso8601String();
        $performedBy = $this->performingUserId($request);
        $qty = (int) ($item['quantity'] ?? 0);
        $toBranchId = (int) ($transfer['to_branch_id'] ?? 0);
        $toBranchName = $this->scope->branchName($toBranchId) ?? 'another branch';
        $damageType = (string) $validated['damage_type'];

        $productNames = [];
        $productId = (int) ($item['product_id'] ?? 0);
        if ($productId > 0) {
            $product = $this->supabase->find('products', $productId, 'id,name');
            if ($product) {
                $productNames[$productId] = $product;
            }
        }

        // Record the report FIRST so a failed stock write never files twice.
        $updated = $this->supabase->update('stock_transfer_items', [
            'return_status' => 'reported',
            'damage_type' => $damageType,
            'damage_reason' => $validated['damage_reason'],
            'damage_reported_by' => $performedBy,
            'damage_reported_at' => $now,
            'updated_at' => $now,
        ], ['id' => (int) $item['id']]);

        if (empty($updated)) {
            $this->fail(['error' =>
                'Could not save the report. Make sure database/supabase_returned_stock.sql has been run in the Supabase SQL editor, then try again.']);
        }

        // The quantity was credited back on return; take it out again now
        // that it is confirmed lost / broken.
        $writeOffReason = ucfirst($damageType).' after return from '.$toBranchName
            .' (transfer '.($transfer['transfer_number'] ?? '').'): '.$validated['damage_reason'];
        $this->deductForLoss((string) ($item['stock_type'] ?? 'product'), $item, $fromBranchId, $writeOffReason, $performedBy);

        $officerBits = [];
        if (! empty($transfer['officer_name'])) {
            $officerBits[] = 'Officer: '.$transfer['officer_name'];
        }
        if (! empty($transfer['officer_phone'])) {
            $officerBits[] = 'phone '.$transfer['officer_phone'];
        }
        if (! empty($transfer['officer_id'])) {
            $officerBits[] = 'ID '.$transfer['officer_id'];
        }
        $officerText = $officerBits === [] ? 'Officer: not recorded on the transfer' : implode(', ', $officerBits);

        try {
            (new AuditService)->recordCriticalAction(
                'returned_stock',
                'transfer_item_damage_report',
                'Lost / broken item report filed',
                $this->scope->branchName($fromBranchId).' reported '.ucfirst($damageType).' stock: '
                    .$this->itemLabel($item, $productNames).' (qty '.$qty.') from transfer '
                    .($transfer['transfer_number'] ?? '').' — sent to '.$toBranchName.', rejected for: '
                    .($item['return_reason'] ?? 'no reason given').'. '.$officerText
                    .'. Reason: '.$validated['damage_reason'],
                [
                    'transfer_id' => (int) ($transfer['id'] ?? 0),
                    'item_id' => (int) ($item['id'] ?? 0),
                    'damage_type' => $damageType,
                    'quantity' => $qty,
                    'officer_name' => $transfer['officer_name'] ?? null,
                    'officer_phone' => $transfer['officer_phone'] ?? null,
                    'officer_id' => $transfer['officer_id'] ?? null,
                    'from_branch_id' => $fromBranchId,
                    'to_branch_id' => $toBranchId,
                ]
            );
        } catch (\Throwable $e) {
            // Audit is best-effort; never block the report on it.
        }

        return response()->json([
            'message' => ucfirst($damageType).' report filed. The Super Admin has been notified to take action.',
        ]);
    }
}
