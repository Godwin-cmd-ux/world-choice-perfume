-- ============================================================
-- NEWS APPROVAL WORKFLOW — run ONCE in the Supabase SQL editor
-- Project: https://supabase.com/dashboard/project/vslkshwicrbmciqxadmb/sql
-- Adds a moderation status to news_posts so graphic designer posts
-- wait for approval from Head Quarters-Mikocheni customer care.
-- ============================================================

ALTER TABLE public.news_posts ADD COLUMN IF NOT EXISTS status text;
ALTER TABLE public.news_posts ADD COLUMN IF NOT EXISTS rejection_reason text;
ALTER TABLE public.news_posts ADD COLUMN IF NOT EXISTS reviewed_by bigint;
ALTER TABLE public.news_posts ADD COLUMN IF NOT EXISTS reviewed_at timestamptz;

-- Existing published posts were already approved.
UPDATE public.news_posts
   SET status = 'approved'
 WHERE status IS NULL AND is_published = true;

-- Any stray unpublished rows become pending.
UPDATE public.news_posts
   SET status = 'pending'
 WHERE status IS NULL AND is_published = false;