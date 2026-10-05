-- ============================================================
-- Order progress notes table
-- Staff must enter a note every time they change an order's
-- status (what point they have reached). These notes are shown
-- to the customer when they track the order.
-- Run this in the Supabase SQL Editor BEFORE deploying the code.
-- ============================================================

CREATE TABLE IF NOT EXISTS public.order_notes (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id bigint NOT NULL REFERENCES public.orders(id) ON DELETE CASCADE,
    note text NOT NULL,
    created_by bigint REFERENCES public.users(id),
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

-- Index for fast lookups (order detail / customer tracking)
CREATE INDEX IF NOT EXISTS idx_order_notes_order ON public.order_notes (order_id);