-- ============================================================
-- WORLD CHOICE PERFUMES — Order item bottle varieties
-- Run this in the Supabase SQL Editor ONCE.
--
-- What it does:
--   * Adds `volume` and `variant` to `order_items` so a customer
--     order line for an Oil Fragrance product states WHICH bottling
--     was asked for (e.g. "50ml With Box · With Logo · Yellow").
--     The shop only lists in-stock varieties and prices each one, so
--     the customer's choice has to survive from the order form to
--     the receipt instead of being guessed at serve time.
--   * The per-product variety counts live in `branch_stock_varieties`
--     (see supabase_branch_stock_varieties.sql) and the per-variety
--     price in `branch_stock_varieties.selling_price` (see
--     supabase_variety_selling_prices.sql). This file only records
--     the choice on the order.
--
-- Safe to run more than once.
-- ============================================================

ALTER TABLE public.order_items
    ADD COLUMN IF NOT EXISTS volume INTEGER;
ALTER TABLE public.order_items
    ADD COLUMN IF NOT EXISTS variant TEXT;

NOTIFY pgrst, 'reload schema';
