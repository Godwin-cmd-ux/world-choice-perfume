-- ============================================================
-- Products: fundamental fragrance ingredient column
-- Lets customers filter the shop by scent family:
--     Floral, Fresh/Citrus, Wood, Amber/Spicy, Fruity, Oud,
--     Gourmand, Aromatic
-- Run this in the Supabase SQL Editor BEFORE deploying the code.
-- ============================================================

ALTER TABLE public.products
    ADD COLUMN IF NOT EXISTS fundamental_ingredient text;

-- Refresh PostgREST's cached schema so the new column is exposed
-- to the API immediately (no dashboard reload needed).
NOTIFY pgrst, 'reload schema';