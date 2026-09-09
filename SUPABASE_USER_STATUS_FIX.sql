-- =========================================================
-- FIX: allow super admin to block/unblock staff
--
-- Blocking a user currently fails with:
--   ERROR: 22P02: invalid input value for enum user_status: "blocked"
-- because the PostgreSQL enum `user_status` only contains
-- ['pending','approved','rejected','active'].
--
-- Run this SQL block in the Supabase SQL Editor.
-- =========================================================

-- 1. See what values currently exist
SELECT enumlabel
FROM pg_enum
JOIN pg_type ON pg_enum.enumtypid = pg_type.oid
WHERE pg_type.typname = 'user_status'
ORDER BY enumsortorder;

-- 2. Add the missing value (idempotent; must run as a standalone
--    statement, outside a transaction block on some Postgres setups)
ALTER TYPE "user_status" ADD VALUE IF NOT EXISTS 'blocked';

-- 3. Confirm the value is now present
SELECT enumlabel
FROM pg_enum
JOIN pg_type ON pg_enum.enumtypid = pg_type.oid
WHERE pg_type.typname = 'user_status'
ORDER BY enumsortorder;