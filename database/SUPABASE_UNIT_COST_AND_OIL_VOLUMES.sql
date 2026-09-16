-- ============================================================================
-- UNIT COST + PER-VOLUME OIL FRAGRANCE TRACKING
-- Run this in the Supabase SQL editor.
--
-- 1. products.unit_cost        — supplier cost per unit (hidden from customers)
-- 2. products.costing_volume   — 500 or 1000 for oil fragrance (per-bottle cost
--                                basis), NULL for brand perfume (per piece)
-- 3. oil_fragrance_stock       — unique per (branch, name, volume) so e.g.
--                                "Reef 33" tracks 500ml and 1000ml separately
-- ============================================================================

-- 1) Product unit cost
ALTER TABLE products
    ADD COLUMN IF NOT EXISTS unit_cost NUMERIC(12, 2) NOT NULL DEFAULT 0;

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS costing_volume INTEGER;

COMMENT ON COLUMN products.unit_cost IS 'Supplier cost per unit at stock-in. Oil fragrance: per costing_volume bottle. Brand perfume: per piece. Hidden from customers.';
COMMENT ON COLUMN products.costing_volume IS 'Bottle volume (ml) the unit_cost refers to for oil fragrances (500 or 1000). NULL = per piece (brand perfume).';

-- 2) Oil fragrance stock: separate counts per volume
--    Drop the old (branch_id, name) unique constraint if it exists, then add
--    the per-volume one. The DO block makes this idempotent.
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

-- If the new constraint already exists, this is a no-op (unique creates it only
-- when missing — guard anyway for reruns).
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
