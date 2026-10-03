-- ============================================================================
-- BRANCH CATEGORY
-- ============================================================================
-- Adds branches.category, the flag that decides how a branch runs.
--
--   autonomous      — an ordinary branch. Works like Dodoma: product stock,
--                     bottles, oil fragrance and bottle accessories.
--   products_based  — a products-only branch. Its stock manager works with
--                     product stock alone; bottles, oil fragrance and bottle
--                     accessories are hidden. Works like Head Quarters-Mikocheni.
--
-- This replaces the branch-NAME check that used to decide the products-only
-- rule. Behaviour is unchanged for Head Quarters-Mikocheni because the backfill
-- below gives it 'products_based'.
--
-- Run this in: Supabase Dashboard → SQL Editor → New Query.
-- RUN THIS BEFORE DEPLOYING THE CODE: until the column exists, every branch
-- reads as autonomous, and the HQ stock manager would be offered bottles.
-- ============================================================================

ALTER TABLE public.branches
    ADD COLUMN IF NOT EXISTS category VARCHAR(32);

-- Existing rows predate the column.
UPDATE public.branches
    SET category = 'autonomous'
    WHERE category IS NULL;

ALTER TABLE public.branches
    ALTER COLUMN category SET DEFAULT 'autonomous';

ALTER TABLE public.branches
    ALTER COLUMN category SET NOT NULL;

-- Only the two known categories.
ALTER TABLE public.branches
    DROP CONSTRAINT IF EXISTS branches_category_check;

ALTER TABLE public.branches
    ADD CONSTRAINT branches_category_check
    CHECK (category IN ('autonomous', 'products_based'));

-- Head Quarters-Mikocheni is the products-only branch, so it keeps the
-- behaviour its name used to carry.
UPDATE public.branches
    SET category = 'products_based'
    WHERE lower(trim(name)) = lower('Head Quarters-Mikocheni');

-- ---------------------------------------------------------------------------
-- Verify
-- ---------------------------------------------------------------------------
-- SELECT id, name, category, is_active FROM public.branches ORDER BY name;
