-- ============================================================
-- WORLD CHOICE PERFUMES — Sale item bottle varieties
-- Run this in the Supabase SQL Editor ONCE.
--
-- What it does:
--   * Adds `volume` and `variant` columns to `sale_items` so a sale
--     line for an Oil Fragrance product records WHICH bottling was
--     sold (e.g. "Test perfume 50ml With Box · With Logo · Yellow").
--   * The per-product variety counts live in `branch_stock_varieties`
--     (see supabase_branch_stock_varieties.sql) and are deducted at
--     sale time; sale_items stores the sold breakdown for receipts
--     and history.
-- ============================================================

ALTER TABLE public.sale_items
    ADD COLUMN IF NOT EXISTS volume INTEGER;
ALTER TABLE public.sale_items
    ADD COLUMN IF NOT EXISTS variant TEXT;

NOTIFY pgrst, 'reload schema';
