-- ============================================================
-- Run this in the Supabase SQL Editor ONCE, BEFORE
-- database/supabase_order_status_picked_migration.sql
--
-- World Choice Perfumes — add the order picker column
--
-- Why this file exists:
--   supabase_order_status_picked_migration.sql backfills and reads
--   orders.assigned_to, but it never created the column. On a database
--   that does not already have it, that migration fails with
--   "column assigned_to does not exist" and the whole pending ->
--   picked -> served migration is aborted.
--
--   assigned_to is the picker of record. cashier_id stays exactly as it
--   is: it is still the counter column on the order, and it is only
--   backfilled into assigned_to here for orders created before the
--   picker column existed.
--
-- Safe to run more than once.
-- ============================================================

-- Step 1: the picker column itself.
ALTER TABLE public.orders
    ADD COLUMN IF NOT EXISTS assigned_to BIGINT NULL;

-- Step 2: the foreign key to users, added separately so that re-running
-- this file on a database that already has the column is a no-op
-- instead of an error.
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conrelid = 'public.orders'::regclass
          AND conname = 'orders_assigned_to_fkey'
    ) THEN
        ALTER TABLE public.orders
            ADD CONSTRAINT orders_assigned_to_fkey
            FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE SET NULL;
    END IF;
END $$;

-- Step 3: backfill the picker from the legacy cashier_id column, so
-- orders picked before this column existed still have an owner and do
-- not disappear from "My Orders On Progress".
UPDATE public.orders
SET assigned_to = cashier_id
WHERE assigned_to IS NULL
  AND cashier_id IS NOT NULL;

-- Step 4: index behind the "My Orders On Progress" and
-- "My Completed Orders" tab lookups.
CREATE INDEX IF NOT EXISTS idx_orders_assigned_status
    ON public.orders (assigned_to, status);

-- Step 5: the staff-only private label column, added here as well so a
-- fresh database gets the complete set of order columns from one file.
ALTER TABLE public.orders
    ADD COLUMN IF NOT EXISTS personal_order_name TEXT NULL;

-- ============================================================
-- Verify: expect assigned_to and personal_order_name to be present.
-- ============================================================
SELECT column_name, data_type
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name = 'orders'
  AND column_name IN ('assigned_to', 'personal_order_name', 'assigned_at', 'served_at', 'cashier_id')
ORDER BY column_name;

-- ============================================================
-- After this, run:
--   1. database/supabase_order_served_at_migration.sql
--   2. database/supabase_order_notes_migration.sql
--   3. database/supabase_order_status_picked_migration.sql
-- and only then deploy the application.
-- ============================================================
