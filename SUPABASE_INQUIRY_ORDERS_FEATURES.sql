-- ============================================================
-- Run these directly in the Supabase SQL Editor (once each)
-- World Choice Perfumes — Customer care remarks + order locking
-- ============================================================

-- 1) Inquiries: let customer care feature a message as a homepage remark
ALTER TABLE public.inquiries
    ADD COLUMN IF NOT EXISTS is_featured BOOLEAN NOT NULL DEFAULT FALSE;

-- (optional) so the staff list can filter featured quickly
CREATE INDEX IF NOT EXISTS idx_inquiries_is_featured
    ON public.inquiries (is_featured, created_at DESC);

-- 2) Orders: lock an order to the staff member who clicked "Assigned"
--    so only that person can take it through Ready/Completed/Served.
ALTER TABLE public.orders
    ADD COLUMN IF NOT EXISTS assigned_to BIGINT REFERENCES public.users(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_orders_assigned_to
    ON public.orders (assigned_to);