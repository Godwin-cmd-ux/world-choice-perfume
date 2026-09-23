-- ============================================================
-- WORLD CHOICE PERFUMES — Returned Stock module (lost / broken reports)
-- Run this in the Supabase SQL Editor ONCE, after
-- database/supabase_stock_transfers.sql and (ideally)
-- database/supabase_stock_transfer_returns.sql.
--
-- Fully idempotent — safe to run again (even after a partial run).
--
-- Summary:
--   * stock_transfer_items.status gains 'returned' / 'lost' (if the
--     returns migration was never run on this database).
--   * stock_transfer_items.return_status gains 'reported' — the sending
--     branch filed the mandatory lost / broken report.
--   * New damage-report columns:
--       damage_type         — 'lost' or 'broken'
--       damage_reason       — the stock manager's explanation
--       damage_reported_by  — who filed the report (users.id)
--       damage_reported_at  — when it was filed
-- ============================================================

-- 1. Make sure the returns columns exist (in case the returns migration
--    has not been run on this database yet).
ALTER TABLE public.stock_transfer_items ADD COLUMN IF NOT EXISTS return_reason TEXT;
ALTER TABLE public.stock_transfer_items ADD COLUMN IF NOT EXISTS return_status TEXT DEFAULT 'pending';
ALTER TABLE public.stock_transfer_items ADD COLUMN IF NOT EXISTS loss_reason TEXT;
ALTER TABLE public.stock_transfer_items ADD COLUMN IF NOT EXISTS resent_transfer_id BIGINT;
ALTER TABLE public.stock_transfer_items ADD COLUMN IF NOT EXISTS returned_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL;
ALTER TABLE public.stock_transfer_items ADD COLUMN IF NOT EXISTS returned_at TIMESTAMPTZ;

-- 2. Widen the item status CHECK to include 'returned' and 'lost'.
ALTER TABLE public.stock_transfer_items
    DROP CONSTRAINT IF EXISTS stock_transfer_items_status_check;

ALTER TABLE public.stock_transfer_items
    ADD CONSTRAINT stock_transfer_items_status_check
    CHECK (status IN ('in_transit', 'received', 'returned', 'lost'));

-- 3. Widen return_status with 'reported' (a lost / broken report was filed).
ALTER TABLE public.stock_transfer_items
    DROP CONSTRAINT IF EXISTS stock_transfer_items_return_status_check;

ALTER TABLE public.stock_transfer_items
    ADD CONSTRAINT stock_transfer_items_return_status_check
    CHECK (return_status IN ('pending', 'resent', 'written_off', 'reported'));

-- 4. Lost / broken damage-report columns.
ALTER TABLE public.stock_transfer_items
    ADD COLUMN IF NOT EXISTS damage_type TEXT
    CHECK (damage_type IN ('lost', 'broken'));
ALTER TABLE public.stock_transfer_items
    ADD COLUMN IF NOT EXISTS damage_reason TEXT;
ALTER TABLE public.stock_transfer_items
    ADD COLUMN IF NOT EXISTS damage_reported_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL;
ALTER TABLE public.stock_transfer_items
    ADD COLUMN IF NOT EXISTS damage_reported_at TIMESTAMPTZ;

-- 5. Indexes for the Returned Stock modules (sender + super admin).
CREATE INDEX IF NOT EXISTS idx_stock_transfer_items_return_status
    ON public.stock_transfer_items (return_status, status);
CREATE INDEX IF NOT EXISTS idx_stock_transfer_items_damage
    ON public.stock_transfer_items (return_status, damage_type);

NOTIFY pgrst, 'reload schema';
