-- ============================================================
-- Run this directly in the Supabase SQL Editor (once)
-- World Choice Perfumes — allow orders.status = 'served'
-- The orders.status column uses the order_status enum, which is
-- missing the 'served' value, so PostgREST rejects "mark as
-- served" with: invalid input value for enum order_status: "served"
-- ============================================================

ALTER TYPE order_status ADD VALUE IF NOT EXISTS 'served';