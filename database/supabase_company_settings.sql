-- ============================================================
-- Company Settings Table
-- Stores company-wide secret codes (super admin + staff)
-- Run this in the Supabase SQL Editor (world-choice-perfumes)
-- ============================================================

CREATE TABLE IF NOT EXISTS public.company_settings (
    key TEXT PRIMARY KEY,
    value TEXT,
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

INSERT INTO public.company_settings (key, value, updated_at)
VALUES
    ('super_admin_secret', 'WCP-SUPER-2026', NOW()),
    ('staff_secret_code', 'WCP-STAFF-2026', NOW())
ON CONFLICT (key) DO NOTHING;