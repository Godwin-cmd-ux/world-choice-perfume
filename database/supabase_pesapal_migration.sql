-- ============================================================
-- PesaPal live payments - orders payment columns
-- Run this in the Supabase SQL Editor BEFORE deploying the code.
-- ============================================================

ALTER TABLE public.orders
    ADD COLUMN IF NOT EXISTS payment_status text NOT NULL DEFAULT 'unpaid',
    ADD COLUMN IF NOT EXISTS payment_method text DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS pesapal_tracking_id text DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS pesapal_merchant_reference text DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS payment_confirmation_code text DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS paid_at timestamptz DEFAULT NULL;

-- Indexes for fast status lookups (used by callback/IPN)
CREATE INDEX IF NOT EXISTS idx_orders_pesapal_tracking ON public.orders (pesapal_tracking_id);
CREATE INDEX IF NOT EXISTS idx_orders_payment_status ON public.orders (payment_status);