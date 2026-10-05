-- ============================================================
-- Run this ONCE in the Supabase SQL Editor
-- World Choice Perfumes — reduce order status to three states
--
-- The app now only ever writes: pending, picked, served.
--
-- Postgres cannot remove enum labels, and a label added by
-- ALTER TYPE ... ADD VALUE cannot be used until it is committed
-- (55P04). So instead of adding 'picked' to the old enum, this
-- swaps the column onto a fresh three-value enum and drops the
-- old type. That runs in a single transaction and leaves the
-- enum holding exactly the three states we want.
--
-- The whole migration is wrapped in a guard, so re-running it
-- is a no-op once the enum is already down to three values.
-- ============================================================


-- ------------------------------------------------------------
-- STEP 0 — pre-flight (read-only, safe to run on its own)
--
-- Every row below must be public.orders / status, and nothing
-- else may use the order_status type — a second column or a view
-- would block the type swap. Run this first and check the output.
-- ------------------------------------------------------------
SELECT c.relname AS table_name,
       a.attname AS column_name,
       t.typname AS type_name
  FROM pg_attribute a
  JOIN pg_class c     ON c.oid = a.attrelid
  JOIN pg_type t      ON t.oid = a.atttypid
  JOIN pg_namespace n ON n.oid = c.relnamespace
 WHERE t.typname = 'order_status'
   AND n.nspname = 'public'
   AND a.attnum > 0
   AND NOT a.attisdropped
   AND c.relkind IN ('r', 'p');


-- ------------------------------------------------------------
-- STEP 1 — the migration. Run only after step 0 looks right.
-- ------------------------------------------------------------
DO $$
DECLARE
    has_legacy boolean;
BEGIN
    -- Already down to three values? Then there is nothing to do.
    SELECT EXISTS (
        SELECT 1
          FROM pg_enum e
          JOIN pg_type t      ON t.oid = e.enumtypid
          JOIN pg_namespace n ON n.oid = t.typnamespace
         WHERE t.typname = 'order_status'
           AND n.nspname = 'public'
           AND e.enumlabel IN ('assigned', 'ready', 'completed', 'cancelled')
    ) INTO has_legacy;

    IF NOT has_legacy THEN
        RAISE NOTICE 'order_status is already pending/picked/served - nothing to do.';
        RETURN;
    END IF;

    -- 1. Record who picked the order.
    --    Orders placed before assigned_to existed only ever had cashier_id
    --    written, so copy it across. Without this the per-staff order tabs
    --    would show nothing at all.
    UPDATE public.orders
       SET assigned_to = cashier_id
     WHERE assigned_to IS NULL
       AND cashier_id IS NOT NULL;

    -- 2. Move the column onto a clean three-value enum.
    --    pending stays, assigned/ready become picked (still being worked
    --    on), completed/cancelled become served (finished and closed).
    --    status is compared as text on purpose: matching it against an
    --    enum literal would cast that literal to the *old* enum and fail
    --    with 22P02, because 'picked' is not a label there yet.
    CREATE TYPE public.order_status_v2 AS ENUM ('pending', 'picked', 'served');

    ALTER TABLE public.orders ALTER COLUMN status DROP DEFAULT;

    ALTER TABLE public.orders
        ALTER COLUMN status TYPE public.order_status_v2
        USING (
            CASE status::text
                WHEN 'pending'   THEN 'pending'
                WHEN 'assigned'  THEN 'picked'
                WHEN 'ready'     THEN 'picked'
                WHEN 'completed' THEN 'served'
                WHEN 'cancelled' THEN 'served'
                WHEN 'picked'    THEN 'picked'
                WHEN 'served'    THEN 'served'
            END
        )::public.order_status_v2;

    -- 3. Retire the old type and put the new one under its name.
    DROP TYPE public.order_status;
    ALTER TYPE public.order_status_v2 RENAME TO order_status;
    ALTER TABLE public.orders ALTER COLUMN status SET DEFAULT 'pending';

    RAISE NOTICE 'order_status is now pending/picked/served.';
END $$;


-- ------------------------------------------------------------
-- STEP 2 — post-checks (read-only)
-- ------------------------------------------------------------

-- Should list only pending, picked and served.
SELECT status, COUNT(*) FROM public.orders GROUP BY status ORDER BY status;

-- Compare against the count from before the migration. A lower number
-- here means the CASE dropped rows, which must not happen.
SELECT COUNT(*) AS total_orders FROM public.orders;

-- Orders that no staff member will be able to open, because no picker
-- was ever recorded for them. Assign them by hand if support needs to
-- look one up.
SELECT order_number, status, branch_id
  FROM public.orders
 WHERE status IN ('picked', 'served')
   AND assigned_to IS NULL
   AND cashier_id IS NULL
 ORDER BY created_at DESC;
