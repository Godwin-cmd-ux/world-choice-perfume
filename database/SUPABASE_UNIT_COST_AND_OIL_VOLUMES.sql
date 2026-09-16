-- ============================================================================
-- PER-VOLUME OIL FRAGRANCE TRACKING (+ remove unit cost, if it was added)
-- Run this in the Supabase SQL editor. Idempotent — safe to re-run.
--
-- 1. oil_fragrance_stock — unique per (branch, name, volume) so e.g.
--    "Reef 33" tracks 500ml and 1000ml bottles separately
-- 2. Drops products.unit_cost / products.costing_volume if the unit-cost
--    feature had been applied to the cloud database
-- ============================================================================

-- 1) Oil fragrance stock: separate counts per volume
--    Drop the old (branch_id, name) unique constraint if it exists, then add
--    the per-volume one. The DO blocks make this idempotent.
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'oil_fragrance_stock_branch_id_name_key'
          AND conrelid = 'oil_fragrance_stock'::regclass
    ) THEN
        ALTER TABLE oil_fragrance_stock
            DROP CONSTRAINT oil_fragrance_stock_branch_id_name_key;
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'oil_fragrance_stock_branch_id_name_volume_key'
          AND conrelid = 'oil_fragrance_stock'::regclass
    ) THEN
        -- Merge any duplicate rows (same branch+name, multiple volumes recorded
        -- as a single row historically) before enforcing uniqueness.
        UPDATE oil_fragrance_stock
        SET volume = COALESCE(volume, 500)
        WHERE volume IS NULL;

        ALTER TABLE oil_fragrance_stock
            ADD CONSTRAINT oil_fragrance_stock_branch_id_name_volume_key
            UNIQUE (branch_id, name, volume);
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_oil_fragrance_stock_branch_name
    ON oil_fragrance_stock(branch_id, name);

-- 2) Remove the unit-cost columns if they exist (feature withdrawn)
ALTER TABLE products DROP COLUMN IF EXISTS unit_cost;
ALTER TABLE products DROP COLUMN IF EXISTS costing_volume;
