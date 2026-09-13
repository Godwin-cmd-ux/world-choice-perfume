-- =============================================================
-- WORLD CHOICE PERFUMES - Bottle Stock Variants
-- Run this in the Supabase SQL editor BEFORE the Blazor/Inertia UI
-- uses the new variant-based stock flow.
--
-- Summary:
--   * Adds a `variant` column to `bottle_stock` that identifies the
--     exact bucket of a stock record:
--         box_logo_yellow    -> With Box, With Logo, Yellow
--         box_logo_black     -> With Box, With Logo, Black
--         box_nologo_black   -> With Box, No Logo, Black
--         box_nologo_white   -> With Box, No Logo, White
--         no_box             -> Without Box
--         plain              -> 6ml / 12ml (no box/logo details)
--   * Backfills existing rows based on their current attributes.
--   * Replaces the old UNIQUE(branch_id, volume) with
--     UNIQUE(branch_id, volume, variant) so one row can exist per
--     per-variant bucket instead of one row per volume.
--   * Adds a `variant` column to `bottle_stock_movements`.
-- =============================================================

-- 1. Add the variant column (nullable for the backfill).
ALTER TABLE public.bottle_stock ADD COLUMN IF NOT EXISTS variant TEXT;

-- 2. Backfill existing records from their stored attributes.
--    With Box + With Logo -> logo color (yellow / black).
UPDATE public.bottle_stock
SET variant = 'box_logo_' || logo_color
WHERE has_box = 'yes'
  AND has_logo = 'yes'
  AND logo_color IN ('yellow', 'black');

--    With Box + No Logo -> marking color (black / white).
UPDATE public.bottle_stock
SET variant = 'box_nologo_' || logo_color
WHERE has_box = 'yes'
  AND (has_logo = 'no' OR has_logo IS NULL)
  AND logo_color IN ('black', 'white');

--    Without Box.
UPDATE public.bottle_stock
SET variant = 'no_box'
WHERE has_box = 'no'
  AND (variant IS NULL OR variant = '');

--    With Box but unknown logo state -> default black bucket.
UPDATE public.bottle_stock
SET variant = 'box_nologo_black'
WHERE has_box = 'yes'
  AND (variant IS NULL OR variant = '');

--    Everything else (6ml / 12ml and unknown) -> plain.
UPDATE public.bottle_stock
SET variant = 'plain'
WHERE variant IS NULL OR variant = '';

-- 3. Make the column mandatory going forward.
ALTER TABLE public.bottle_stock ALTER COLUMN variant SET DEFAULT 'plain';
ALTER TABLE public.bottle_stock ALTER COLUMN variant SET NOT NULL;

-- 4. Replace the old unique constraint with a per-variant one.
ALTER TABLE public.bottle_stock DROP CONSTRAINT IF EXISTS bottle_stock_branch_id_volume_key;
ALTER TABLE public.bottle_stock DROP CONSTRAINT IF EXISTS bottle_stock_branch_id_volume_variant_key;
ALTER TABLE public.bottle_stock
    ADD CONSTRAINT bottle_stock_branch_id_volume_variant_key
    UNIQUE (branch_id, volume, variant);

-- 5. Tag movements with the variant they affected.
ALTER TABLE public.bottle_stock_movements ADD COLUMN IF NOT EXISTS variant TEXT;