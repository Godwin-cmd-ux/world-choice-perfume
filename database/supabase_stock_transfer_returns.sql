-- ============================================================
-- WORLD CHOICE PERFUMES — Stock Transfer Returns / Lost Items
-- Run this in the Supabase SQL Editor ONCE, after
-- database/supabase_stock_transfers.sql.
--
-- Fully idempotent — safe to run again (even after a partial run).
--
-- Summary:
--   * stock_transfer_items.status gains 'returned' (receiver marked the
--     item invalid — quantity travels back to the sender branch) and
--     'lost' (sender declared the item lost during transfer, no stock
--     movement either way).
--   * New item columns for the receive decisions:
--       return_reason / return_status  — why the receiver rejected it
--       loss_reason                    — why the sender declared it lost
--       resent_transfer_id             — links a resend to the item
--       returned_by / returned_at      — who returned it and when
--   * admin_notifications.type CHECK constraint is DROPPED — the app writes
--     many types (staff_*, price_customization, discount_used, orders, news,
--     stock_*, stock_transfer, lost_items, ...) and a static CHECK breaks
--     every time a new notification type is introduced.
-- ============================================================

-- 1. Widen the item status CHECK to include 'returned' and 'lost'.
ALTER TABLE public.stock_transfer_items
    DROP CONSTRAINT IF EXISTS stock_transfer_items_status_check;

ALTER TABLE public.stock_transfer_items
    ADD CONSTRAINT stock_transfer_items_status_check
    CHECK (status IN ('in_transit', 'received', 'returned', 'lost'));

-- 2. Columns used by the Valid / Invalid / Lost workflow (idempotent).
ALTER TABLE public.stock_transfer_items
    ADD COLUMN IF NOT EXISTS return_reason TEXT;
ALTER TABLE public.stock_transfer_items
    ADD COLUMN IF NOT EXISTS return_status TEXT DEFAULT 'pending'
        CHECK (return_status IN ('pending', 'resent', 'written_off'));
ALTER TABLE public.stock_transfer_items
    ADD COLUMN IF NOT EXISTS loss_reason TEXT;
ALTER TABLE public.stock_transfer_items
    ADD COLUMN IF NOT EXISTS resent_transfer_id BIGINT;
ALTER TABLE public.stock_transfer_items
    ADD COLUMN IF NOT EXISTS returned_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL;
ALTER TABLE public.stock_transfer_items
    ADD COLUMN IF NOT EXISTS returned_at TIMESTAMPTZ;

-- 3. Remove the type CHECK from admin_notifications. The app writes many
--    notification types; keeping any static list here blocks new types,
--    so the constraint is dropped for good.
ALTER TABLE public.admin_notifications
    DROP CONSTRAINT IF EXISTS admin_notifications_type_check;

-- 4. Index for the sender-side "returned items" listing.
CREATE INDEX IF NOT EXISTS idx_stock_transfer_items_return_status
    ON public.stock_transfer_items (return_status, status);

NOTIFY pgrst, 'reload schema';
