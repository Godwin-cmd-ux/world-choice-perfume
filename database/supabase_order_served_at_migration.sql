-- ============================================================
-- Run this directly in the Supabase SQL Editor (once)
-- World Choice Perfumes — add served_at to orders
-- The orders table is missing the served_at column, which makes
-- PostgREST reject "mark as served" PATCH requests with
-- "could not find the served_at column of orders".
-- ============================================================

ALTER TABLE public.orders
    ADD COLUMN IF NOT EXISTS served_at TIMESTAMP NULL;