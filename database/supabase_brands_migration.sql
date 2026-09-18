-- ============================================================
-- Brands table — Featured Brands shown on the home page.
-- Brands are managed by the Graphic Designer (create/edit name,
-- logo/photo, active toggle). Product brand selection on the
-- stock-manager forms is limited to the brands in this table.
-- Run this in the Supabase SQL Editor BEFORE deploying the code.
-- ============================================================

CREATE TABLE IF NOT EXISTS public.brands (
    id BIGSERIAL PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    logo_url TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Index for lookups (home page featured section / all-brands page)
CREATE INDEX IF NOT EXISTS idx_brands_active ON public.brands (is_active, name);
CREATE INDEX IF NOT EXISTS idx_brands_name ON public.brands (name);

-- Refresh PostgREST's cached schema so the new table is exposed
-- to the API immediately (no dashboard reload needed).
NOTIFY pgrst, 'reload schema';