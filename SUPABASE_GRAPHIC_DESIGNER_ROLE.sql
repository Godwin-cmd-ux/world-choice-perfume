-- =========================================================
-- ADD: graphic_designer role to Supabase user_role enum
--
-- Run in the Supabase SQL Editor (standalone statements).
-- Graphic designers manage the news/content module.
-- =========================================================

-- 1. See current role values
SELECT enumlabel
FROM pg_enum
JOIN pg_type ON pg_enum.enumtypid = pg_type.oid
WHERE pg_type.typname = 'user_role'
ORDER BY enumsortorder;

-- 2. Add the new role (idempotent)
ALTER TYPE "user_role" ADD VALUE IF NOT EXISTS 'graphic_designer';

-- 3. Confirm
SELECT enumlabel
FROM pg_enum
JOIN pg_type ON pg_enum.enumtypid = pg_type.oid
WHERE pg_type.typname = 'user_role'
ORDER BY enumsortorder;