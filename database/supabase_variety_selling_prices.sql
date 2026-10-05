-- ============================================================
-- WORLD CHOICE PERFUMES — Per-variety selling price
-- Run this in the Supabase SQL Editor ONCE.
--
-- Problem this solves:
--   "Reef 33" of 50ml does not sell at the same price as 30ml,
--   so each variety bucket (branch, product, volume, variety)
--   holds its own selling price. Stock-in sets it, transfers carry
--   it to the receiving branch, and sales default to it when the
--   cashier picks that variety.
--
-- Summary:
--   * branch_stock_varieties.selling_price — price per unit for
--     THIS product in THIS bottling at THIS branch. Set at product
--     stock-in; transfer items snapshot it and receiving branches
--     inherit it; the sale form defaults to it when a variety is
--     picked (custom/discount prices still override).
--   * stock_transfer_items.variety_unit_price — the price the item
--     had at the sending branch, kept as history and inherited on
--     receive.
--   * Backfills branch_stock_varieties.selling_price from the
--     product's branch_stock selling_price for existing rows.
-- ============================================================

ALTER TABLE public.branch_stock_varieties
    ADD COLUMN IF NOT EXISTS selling_price NUMERIC(12,2) NOT NULL DEFAULT 0;

ALTER TABLE public.stock_transfer_items
    ADD COLUMN IF NOT EXISTS variety_unit_price NUMERIC(12,2);

-- Existing variety buckets inherit the product's branch price.
UPDATE public.branch_stock_varieties bsv
SET selling_price = COALESCE(bs.price, 0)
FROM (
    SELECT bs.branch_id, bs.product_id, bs.selling_price AS price
    FROM public.branch_stock bs
    WHERE bs.id IN (
        SELECT MIN(id) FROM public.branch_stock GROUP BY branch_id, product_id
    )
) bs
WHERE bs.branch_id = bsv.branch_id
  AND bs.product_id = bsv.product_id;

NOTIFY pgrst, 'reload schema';
