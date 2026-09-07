-- =========================================================
-- FIX: register customer_care and seller roles in Supabase
--
-- The error when signing up is:
--   ERROR: 22P02: invalid input value for enum user_role: "customer_care"
-- This means the PostgreSQL enum `user_role` does not yet contain those
-- values. Run this entire SQL block in the Supabase SQL Editor.
-- =========================================================

-- 1. See what values already exist in the enum
SELECT enumlabel
FROM pg_enum
JOIN pg_type ON pg_enum.enumtypid = pg_type.oid
WHERE pg_type.typname = 'user_role'
ORDER BY enumsortorder;

-- 2. Add the missing role values (idempotent-safe: ALTER TYPE ADD VALUE
--    is a no-op if the value already exists, but note it cannot run inside
--    a transaction block on some Postgres setups, so these must be run as
--    standalone statements).
ALTER TYPE "user_role" ADD VALUE IF NOT EXISTS 'customer_care';
ALTER TYPE "user_role" ADD VALUE IF NOT EXISTS 'seller';

-- 3. (Optional safety) Make sure 'stock_manager' exists too — the
--    stock-manager registration path uses this role, so missing it would
--    break the stock-manager signup flow the same way.
ALTER TYPE "user_role" ADD VALUE IF NOT EXISTS 'stock_manager';

-- 4. Confirm the values are now present
SELECT enumlabel
FROM pg_enum
JOIN pg_type ON pg_enum.enumtypid = pg_type.oid
WHERE pg_type.typname = 'user_role'
ORDER BY enumsortorder;
