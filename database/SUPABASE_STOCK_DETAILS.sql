-- ============================================================
-- Oil fragrance & bottle stock schema fixes
-- Run this in the Supabase SQL Editor BEFORE deploying the code.
--
-- What it does:
--   1. Creates the stock/movement tables if they don't exist yet.
--   2. Adds the missing columns that the app now relies on:
--        * oil_fragrance_stock.volume          (500 / 1000 ml)
--        * oil_fragrance_movements.volume
--        * bottle_stock.has_logo / logo_color / has_box / box_color
--
-- It is safe to run multiple times (idempotent).
-- ============================================================

-- ---------- Bottle stock ----------
CREATE TABLE IF NOT EXISTS public.bottle_stock (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE CASCADE,
    volume VARCHAR(20) NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 0,
    has_logo TEXT,
    logo_color TEXT,
    has_box TEXT,
    box_color TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (branch_id, volume)
);

CREATE TABLE IF NOT EXISTS public.bottle_stock_movements (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE CASCADE,
    volume VARCHAR(20) NOT NULL,
    type TEXT NOT NULL,
    quantity INTEGER NOT NULL,
    reason TEXT,
    has_logo TEXT,
    logo_color TEXT,
    has_box TEXT,
    box_color TEXT,
    performed_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

ALTER TABLE public.bottle_stock
    ADD COLUMN IF NOT EXISTS has_logo TEXT,
    ADD COLUMN IF NOT EXISTS logo_color TEXT,
    ADD COLUMN IF NOT EXISTS has_box TEXT,
    ADD COLUMN IF NOT EXISTS box_color TEXT;

-- ---------- Oil fragrance stock ----------
CREATE TABLE IF NOT EXISTS public.oil_fragrance_stock (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    volume INTEGER,
    quantity INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (branch_id, name)
);

CREATE TABLE IF NOT EXISTS public.oil_fragrance_movements (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    volume INTEGER,
    type TEXT NOT NULL,
    quantity INTEGER NOT NULL,
    reason TEXT,
    performed_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

ALTER TABLE public.oil_fragrance_stock
    ADD COLUMN IF NOT EXISTS volume INTEGER;

ALTER TABLE public.oil_fragrance_movements
    ADD COLUMN IF NOT EXISTS volume INTEGER;