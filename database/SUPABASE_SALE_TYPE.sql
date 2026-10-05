-- Run this in the Supabase SQL editor to let the app persist the sale type.
-- Required for retail/wholesale to be stored on every sale.
ALTER TABLE public.sales ADD COLUMN IF NOT EXISTS sale_type VARCHAR(20);