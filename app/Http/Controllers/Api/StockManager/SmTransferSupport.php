<?php

namespace App\Http\Controllers\Api\StockManager;

use App\Services\AuditService;
use App\Services\BottleStockService;
use App\Services\ProductVarietyStockService;
use App\Services\StockManagerScope;

/**
 * Port of StockTransferController's private helpers for the JSON twins.
 * The website controller is ~2,700 lines; the logic that actually moves
 * stock lives here so both transfer endpoints share one implementation.
 */
trait SmTransferSupport
{
    private function isBottleType(string $type): bool
    {
        return in_array($type, ['bottle', 'oil_fragrance', 'bottle_accessories'], true);
    }

    private function assertValidType(?string $type): string
    {
        $type = (string) $type;
        if (! in_array($type, ['product', 'bottle', 'oil_fragrance', 'bottle_accessories'], true)) {
            abort(404, 'Transfer type not found.');
        }

        return $type;
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
            $label .= ' '.$this->bottles->variantLabel($variant, $volumeInt);
        }

        return ' — '.$label;
    }

    private function itemLabel(array $item, array $productNames): string
    {
        $type = $item['stock_type'] ?? '';

        if ($type === 'product') {
            $id = (int) ($item['product_id'] ?? 0);
            $p = $productNames[$id] ?? null;

            return ($p['name'] ?? ('Product #'.$id)).$this->varietySuffix($item);
        }

        if ($type === 'bottle') {
            $volume = (string) ($item['volume'] ?? '');
            $variant = (string) ($item['variant'] ?? BottleStockService::VARIANT_PLAIN);
            $label = $this->bottles->volumeLabel($this->bottles->parseVolume($volume) ?? 0);

            return $label.' — '.$this->bottles->variantLabel($variant, $this->bottles->parseVolume($volume) ?? 0);
        }

        if ($type === 'oil_fragrance') {
            return (string) ($item['name'] ?? '').($item['volume'] ? ' ('.$item['volume'].'ml)' : '');
        }

        if ($type === 'bottle_accessories') {
            return ucfirst(str_replace('_', ' ', (string) ($item['type'] ?? ''))).' — '.ucfirst((string) ($item['color'] ?? ''));
        }

        return 'Item';
    }

    /** Split an incoming item into display fields (name / variety / type). */
    private function incomingLabels(array $item, array $productNames): array
    {
        $type = (string) ($item['stock_type'] ?? '');
        $name = 'Item';
        $variety = '';
        $oilType = $this->typeLabel($type);

        if ($type === 'product') {
            $id = (int) ($item['product_id'] ?? 0);
            $p = $productNames[$id] ?? null;
            $name = $p['name'] ?? ('Product #'.$id);
            $volume = $this->bottles->parseVolume((string) ($item['volume'] ?? ''));
            $variant = (string) ($item['variant'] ?? '');
            if ($volume !== null && $volume > 0) {
                $variety = $this->bottles->volumeLabel($volume);
                if ($variant !== '') {
                    $variety .= ' · '.$this->bottles->variantLabel($variant, $volume);
                }
            }
            $category = (string) ($item['category'] ?? '');
            if ($category !== '') {
                $oilType = $category;
            }
        } elseif ($type === 'bottle') {
            $volume = $this->bottles->parseVolume((string) ($item['volume'] ?? ''));
            $name = $this->bottles->volumeLabel($volume ?? 0);
            $variant = (string) ($item['variant'] ?? BottleStockService::VARIANT_PLAIN);
            $variety = $this->bottles->variantLabel($variant, $volume ?? 0);
            $oilType = 'Bottle Stock';
        } elseif ($type === 'oil_fragrance') {
            $name = (string) ($item['name'] ?? '');
            $vol = (string) ($item['volume'] ?? '');
            $variety = $vol !== '' ? $vol.'ml' : '';
            $oilType = 'Oil Fragrance';
        } elseif ($type === 'bottle_accessories') {
            $name = ucfirst(str_replace('_', ' ', (string) ($item['type'] ?? '')));
            $variety = ucfirst((string) ($item['color'] ?? ''));
            $oilType = 'Bottle Accessories';
        }

        return ['name' => $name, 'variety' => $variety, 'type' => $oilType];
    }

    /** Short human description of a transfer item for audit messages. */
    private function itemDescription(array $item, array $productNames = []): string
    {
        $label = $this->itemLabel($item, $productNames);

        return $label !== 'Item' ? "'{$label}'" : '';
    }

    private function itemDescriptionText(array $item): string
    {
        return $this->itemDescription($item);
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
            'id' => 'in.('.implode(',', $ids).')',
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

    private function branchNameMap(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
        $map = [];
        if (empty($ids)) {
            return $map;
        }

        $rows = $this->supabase->query('branches', [
            'select' => 'id,name',
            'id' => 'in.('.implode(',', $ids).')',
        ]);
        foreach ($rows as $b) {
            $map[(int) $b['id']] = $b['name'];
        }

        return $map;
    }

    private function isHQBranch(int $branchId): bool
    {
        $name = $this->scope->branchName($branchId);

        return $name !== null
            && mb_strtolower(trim($name)) === mb_strtolower(trim(StockManagerScope::HQ_BRANCH_NAME));
    }

    /**
     * All branches a stock manager may issue a transfer to — Head Quarters is
     * excluded as a TARGET for bottle / oil fragrance / accessories transfers.
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
            $targets[] = $b;
        }

        return $targets;
    }

    /** Record a transfer action in the durable audit trail (best-effort). */
    private function recordTransferAudit(string $action, string $title, string $message): void
    {
        try {
            (new AuditService)->recordCriticalAction('stock_transfer', $action, $title, $message);
        } catch (\Throwable $e) {
            // Audit is best-effort; never block the business action on it.
        }
    }

    /**
     * Validate raw items against the source branch stock and return the
     * enriched rows (with the columns needed for stock_transfer_items).
     * Business errors surface as 422 via $this->fail().
     */
    private function resolveItems(string $type, array $rawItems, int $fromBranchId): array
    {
        if (empty($rawItems) || ! is_array($rawItems)) {
            $this->fail(['items' => 'Add at least one item to transfer.']);
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
                    'id' => 'in.('.implode(',', array_keys($pidMap)).')',
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
                    $errors[] = 'Quantity for '.($productMap[$pid]['name'] ?? 'product').' must be at least 1.';

                    continue;
                }
                $stock = $stockMap[$pid] ?? null;
                if (! $stock) {
                    $errors[] = (($productMap[$pid]['name'] ?? 'Product #'.$pid).' has no stock record at your branch.');

                    continue;
                }
                if (((int) ($stock['quantity'] ?? 0)) < $qty) {
                    $errors[] = 'Insufficient stock for '.($productMap[$pid]['name'] ?? 'product').'. Available: '.($stock['quantity'] ?? 0).'.';

                    continue;
                }

                // Oil fragrance products must state the exact bottling, but
                // only when the product has variety records at all.
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
                    if ($varietyVolume === '' || ! in_array($volumeInt, BottleStockService::VOLUMES, true)) {
                        $errors[] = 'Select the bottle volume for '.($productMap[$pid]['name'] ?? 'product').'.';

                        continue;
                    }
                    if (! in_array($varietyVariant, $this->bottles->variantBuckets($volumeInt), true)) {
                        $errors[] = 'Select the bottle variety (box / logo / color) for '.($productMap[$pid]['name'] ?? 'product').'.';

                        continue;
                    }
                    $varietyAvailable = (int) ($varietyStock[$pid][$volumeInt][$varietyVariant] ?? 0);
                    if ($varietyAvailable <= 0) {
                        $errors[] = ($productMap[$pid]['name'] ?? 'product').' — '.$this->bottles->volumeLabel($volumeInt)
                            .' ('.$this->bottles->variantLabel($varietyVariant, $volumeInt).') has no stock at your branch for this product.';

                        continue;
                    }
                    if ($varietyAvailable < $qty) {
                        $errors[] = 'Insufficient stock for '.($productMap[$pid]['name'] ?? 'product').' — '.$this->bottles->volumeLabel($volumeInt)
                            .' ('.$this->bottles->variantLabel($varietyVariant, $volumeInt).'). Available: '.$varietyAvailable.'.';

                        continue;
                    }
                }

                $varietyPrice = null;
                if ($isOil && $varietyVolume !== '' && $varietyVariant !== '') {
                    $varietyPrice = (float) trim((string) ($ri['variety_price'] ?? ''));
                    if ($varietyPrice <= 0) {
                        $varietyPrice = (new ProductVarietyStockService($this->supabase))
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
                $this->fail(['items' => implode(' ', array_slice($errors, 0, 3)).(count($errors) > 3 ? ' And more.' : '')]);
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
                $stockMap[$volume.'|'.$variant] = (int) ($b['quantity'] ?? 0);
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
                if (! in_array($variant, $this->bottles->variantBuckets($volume), true)) {
                    $variant = BottleStockService::VARIANT_PLAIN;
                }
                $available = $stockMap[$volume.'|'.$variant] ?? 0;
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
                $this->fail(['items' => implode(' ', array_slice($errors, 0, 3)).(count($errors) > 3 ? ' And more.' : '')]);
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
                $stockMap[(string) ($o['name'] ?? '').'|'.(string) (int) ($o['volume'] ?? 0)] = (int) ($o['quantity'] ?? 0);
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
                $available = $stockMap[$name.'|'.($vol !== '' ? $vol : '0')] ?? 0;
                if ($available < $qty) {
                    $errors[] = "Insufficient {$name} (".($vol ? $vol.'ml' : 'no volume').") oil fragrance. Available: {$available}.";

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
                $this->fail(['items' => implode(' ', array_slice($errors, 0, 3)).(count($errors) > 3 ? ' And more.' : '')]);
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
            $stockMap[(string) ($a['type'] ?? '').'|'.(string) ($a['color'] ?? '')] = (int) ($a['quantity'] ?? 0);
        }

        $errors = [];
        foreach ($rawItems as $ri) {
            $typeVal = (string) ($ri['type'] ?? '');
            $color = (string) ($ri['color'] ?? '');
            $qty = (int) ($ri['quantity'] ?? 0);
            if (! in_array($typeVal, ['straws', 'bottlenecks', 'bottle_tops'], true) || ! in_array($color, ['silver', 'gold'], true)) {
                $errors[] = 'Every bottle accessories row must have a type and color.';

                continue;
            }
            if ($qty <= 0) {
                $errors[] = 'Quantity for bottle accessories must be at least 1.';

                continue;
            }
            $available = $stockMap[$typeVal.'|'.$color] ?? 0;
            if ($available < $qty) {
                $errors[] = 'Insufficient '.ucfirst(str_replace('_', ' ', $typeVal)).' ('.ucfirst($color).') packets. Available: '.$available.'.';

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
            $this->fail(['items' => implode(' ', array_slice($errors, 0, 3)).(count($errors) > 3 ? ' And more.' : '')]);
        }

        return $resolved;
    }

    /** Increment (positive) or decrement (negative) a variety bucket. */
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
     * Stock out one resolved item from the source branch and record the
     * matching movement row.
     */
    private function applyOut(string $type, array $item, int $fromBranchId, string $transferNumber, ?string $toBranchName, int $performedBy): void
    {
        $now = now()->toIso8601String();
        $reason = "Stock transfer to {$toBranchName} ({$transferNumber})";

        if ($type === 'product') {
            $row = $this->supabase->queryFresh('branch_stock', [
                'select' => 'id,quantity',
                'branch_id' => "eq.{$fromBranchId}",
                'product_id' => "eq.{$item['columns']['product_id']}",
                'limit' => 1,
            ]);
            $current = $row[0] ?? null;
            if (! $current) {
                return;
            }
            $newQty = (int) ($current['quantity'] ?? 0) - $item['quantity'];
            $this->supabase->update('branch_stock', ['quantity' => max($newQty, 0), 'updated_at' => $now], ['id' => $current['id']]);

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
                'notes' => $reason.$this->varietySuffix($item['columns']),
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
            if (! $current) {
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
        if (! $current) {
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

    /**
     * Stock one transfer item into the receiving branch (receipt) and record
     * the matching stock_in movement. Returns false on failure.
     */
    private function applyIn(string $type, array $item, int $branchId, string $reason, int $performedBy): bool
    {
        $now = now()->toIso8601String();
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
                $done = ! empty($this->supabase->update('branch_stock', [
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

            if (! $done) {
                return false;
            }

            $inVolume = (int) ($item['volume'] ?? 0);
            $inVariant = (string) ($item['variant'] ?? '');
            if ($inVolume > 0 && $inVariant !== '') {
                $this->adjustProductVarietyStock($branchId, $productId, $inVolume, $inVariant, $qty);

                // The receiver inherits the sender's per-variety price until
                // it sets its own for that bottling.
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
                'notes' => $reason.$this->varietySuffix($item),
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
                $done = ! empty($this->supabase->update('bottle_stock', array_merge([
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

            if (! $done) {
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
                $done = ! empty($this->supabase->update('oil_fragrance_stock', [
                    'quantity' => ((int) ($existing['quantity'] ?? 0)) + $qty,
                    'updated_at' => $now,
                ], ['id' => $existing['id']]));
            } else {
                $done = $this->supabase->insert('oil_fragrance_stock', [
                    'branch_id' => $branchId,
                    'name' => $name,
                    'volume' => $volume,
                    'quantity' => $qty,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]) !== null;
            }

            if (! $done) {
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
            $done = ! empty($this->supabase->update('bottle_accessories', [
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

        if (! $done) {
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

    /**
     * Stock a rejected item back into the SENDING branch and record the
     * matching return movement. Returns false on failure.
     */
    private function applyReturnIn(string $type, array $item, int $branchId, string $reason, int $performedBy): bool
    {
        $now = now()->toIso8601String();
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
                $done = ! empty($this->supabase->update('branch_stock', [
                    'quantity' => ((int) ($existing['quantity'] ?? 0)) + $qty,
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

            if (! $done) {
                return false;
            }

            $volume = (int) ($item['volume'] ?? 0);
            $variant = (string) ($item['variant'] ?? '');
            if ($volume > 0 && $variant !== '') {
                $this->adjustProductVarietyStock($branchId, $productId, $volume, $variant, $qty);
            }

            $this->supabase->insert('stock_movements', [
                'branch_id' => $branchId,
                'product_id' => $productId,
                'type' => 'transfer_in',
                'quantity' => $qty,
                'unit_cost' => $item['unit_cost'] ?? null,
                'unit_price' => $item['unit_price'] ?? null,
                'performed_by' => $performedBy,
                'notes' => $reason.$this->varietySuffix($item),
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
                $done = ! empty($this->supabase->update('bottle_stock', array_merge([
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

            if (! $done) {
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
                $done = ! empty($this->supabase->update('oil_fragrance_stock', [
                    'quantity' => ((int) ($existing['quantity'] ?? 0)) + $qty,
                    'updated_at' => $now,
                ], ['id' => $existing['id']]));
            } else {
                $done = $this->supabase->insert('oil_fragrance_stock', [
                    'branch_id' => $branchId,
                    'name' => $name,
                    'volume' => $volume,
                    'quantity' => $qty,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]) !== null;
            }

            if (! $done) {
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
            $done = ! empty($this->supabase->update('bottle_accessories', [
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

        if (! $done) {
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

    /**
     * Deduct a written-off item's quantity from the sending branch's stock,
     * recording the matching movement. Best-effort per stock type.
     */
    private function deductForLoss(string $type, array $item, int $branchId, string $reason, int $performedBy): void
    {
        $now = now()->toIso8601String();
        $qty = (int) ($item['quantity'] ?? 0);
        if ($qty <= 0) {
            return;
        }

        if ($type === 'product') {
            $productId = (int) ($item['product_id'] ?? 0);
            $row = $productId > 0 ? $this->supabase->queryFresh('branch_stock', [
                'select' => 'id,quantity',
                'branch_id' => "eq.{$branchId}",
                'product_id' => "eq.{$productId}",
                'limit' => 1,
            ]) : [];
            $current = $row[0] ?? null;
            if ($current) {
                $this->supabase->update('branch_stock', [
                    'quantity' => max(((int) ($current['quantity'] ?? 0)) - $qty, 0),
                    'updated_at' => $now,
                ], ['id' => $current['id']]);
            }

            $volume = (int) ($item['volume'] ?? 0);
            $variant = (string) ($item['variant'] ?? '');
            if ($productId > 0 && $volume > 0 && $variant !== '') {
                $this->adjustProductVarietyStock($branchId, $productId, $volume, $variant, -$qty);
            }

            $this->supabase->insert('stock_movements', [
                'branch_id' => $branchId,
                'product_id' => $productId > 0 ? $productId : null,
                'type' => 'transfer_out',
                'quantity' => -$qty,
                'unit_cost' => $item['unit_cost'] ?? null,
                'unit_price' => $item['unit_price'] ?? null,
                'performed_by' => $performedBy,
                'notes' => $reason.$this->varietySuffix($item),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        if ($type === 'bottle') {
            $volume = $this->bottles->parseVolume((string) ($item['volume'] ?? ''));
            if ($volume !== null) {
                $this->bottles->deduct($branchId, $volume, $qty, $reason, (string) $performedBy, (string) ($item['variant'] ?? ''));
            }

            return;
        }

        if ($type === 'oil_fragrance') {
            $params = [
                'select' => 'id,quantity',
                'branch_id' => "eq.{$branchId}",
                'name' => "eq.{$item['name']}",
                'limit' => 1,
            ];
            if (($item['volume'] ?? '') !== '') {
                $params['volume'] = "eq.{$item['volume']}";
            }
            $row = $this->supabase->queryFresh('oil_fragrance_stock', $params);
            $current = $row[0] ?? null;
            if ($current) {
                $this->supabase->update('oil_fragrance_stock', [
                    'quantity' => max(((int) ($current['quantity'] ?? 0)) - $qty, 0),
                    'updated_at' => $now,
                ], ['id' => $current['id']]);
            }
            $this->supabase->insert('oil_fragrance_movements', [
                'branch_id' => $branchId,
                'name' => $item['name'] ?? null,
                'volume' => ($item['volume'] ?? '') !== '' ? (int) $item['volume'] : null,
                'type' => 'stock_out',
                'quantity' => $qty,
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
            'branch_id' => "eq.{$branchId}",
            'type' => 'eq.'.($item['type'] ?? ''),
            'color' => 'eq.'.($item['color'] ?? ''),
            'limit' => 1,
        ]);
        $current = $row[0] ?? null;
        if ($current) {
            $this->supabase->update('bottle_accessories', [
                'quantity' => max(((int) ($current['quantity'] ?? 0)) - $qty, 0),
                'updated_at' => $now,
            ], ['id' => $current['id']]);
        }
        $this->supabase->insert('bottle_accessories_movements', [
            'branch_id' => $branchId,
            'type' => $item['type'] ?? null,
            'color' => $item['color'] ?? null,
            'movement_type' => 'stock_out',
            'quantity' => $qty,
            'reason' => $reason,
            'performed_by' => $performedBy,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
