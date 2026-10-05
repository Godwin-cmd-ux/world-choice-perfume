-- ============================================================
-- Run this directly in the Supabase SQL Editor (once)
-- World Choice Perfumes — add personal_order_name to orders
--
-- Staff can give an order a short memorable label ("Mama Asha",
-- "Customer from Mwanza") after they pick it, to help them
-- recognise the customer. The label is an ADDITIONAL identifier
-- only: it never replaces order_number, customer info or status.
--
-- Why a single column on orders and not a separate labels table:
-- picking an order is an exclusive claim (pending -> picked is a
-- compare-and-set, so exactly one staff member can win it), which
-- means an order can only ever have one picker at a time and
-- therefore only one personal label. A child table would allow
-- several labels for an order that only one person can own.
--
-- Ownership is enforced in the application layer: only the staff
-- member whose assigned_to matches may write this column, Super
-- Admin excepted. Nothing here grants write access on its own.
-- ============================================================

-- ------------------------------------------------------------
-- Step 0 — preflight. Run and read this on its own first.
-- Expect exactly one row: public.orders.
-- ------------------------------------------------------------
SELECT table_schema, table_name
FROM information_schema.tables
WHERE table_schema = 'public'
  AND table_name = 'orders';

-- ------------------------------------------------------------
-- Step 1 — add the column
-- ------------------------------------------------------------
ALTER TABLE public.orders
    ADD COLUMN IF NOT EXISTS personal_order_name TEXT NULL;

-- ------------------------------------------------------------
-- Step 2 — verify
-- Expect: personal_order_name | text | YES | null
-- ------------------------------------------------------------
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name = 'orders'
  AND column_name = 'personal_order_name';

-- ------------------------------------------------------------
-- Step 3 — the label must be free text, never an identity column
-- Expect 0 rows.
-- ------------------------------------------------------------
SELECT conname, contype
FROM pg_constraint
WHERE conrelid = 'public.orders'::regclass
  AND contype IN ('u', 'p')
  AND array_to_string(conkey, ',') = (
      SELECT string_agg(attname, ',' ORDER BY attnum)
      FROM pg_attribute
      WHERE attrelid = 'public.orders'::regclass
        AND attname = 'personal_order_name'
  );
