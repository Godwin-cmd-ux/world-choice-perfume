-- ============================================
-- ADD SEX CATEGORY TO PRODUCTS (run in Supabase SQL Editor)
-- Values: male | female | unisex | accessories | gift sets
-- ============================================

ALTER TABLE public.products ADD COLUMN IF NOT EXISTS sex_category VARCHAR(20);

-- Backfill existing products from the old category column where possible
UPDATE public.products
SET sex_category = CASE LOWER(category)
    WHEN 'men'      THEN 'male'
    WHEN 'male'     THEN 'male'
    WHEN 'women'    THEN 'female'
    WHEN 'female'   THEN 'female'
    WHEN 'ladies'   THEN 'female'
    WHEN 'unisex'   THEN 'unisex'
    WHEN 'gift set' THEN 'gift sets'
    WHEN 'gift sets' THEN 'gift sets'
    WHEN 'accessories' THEN 'accessories'
    WHEN 'accessory' THEN 'accessories'
    ELSE NULL
END
WHERE sex_category IS NULL AND category IS NOT NULL;