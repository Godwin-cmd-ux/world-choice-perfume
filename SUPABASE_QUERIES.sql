-- ============================================================
-- SUPABASE SCHEMA CHANGES FOR WORLD CHOICE PERFUMES
-- Run these queries in the Supabase SQL Editor
--
-- NOTE: The `notifications` table already exists (used by the
-- Laravel notification system: user_id, type, data, read_at).
-- Super admin critical-action notifications use a NEW table
-- called `admin_notifications` to avoid conflicts.
-- ============================================================

-- ============================================================
-- 1. NEW TABLE: bottle_accessories
-- Items assembled with bottles: straws, bottlenecks, bottle tops
-- Each has Silver/Gold categories, counted by packets
-- ============================================================
CREATE TABLE IF NOT EXISTS bottle_accessories (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL,
    type VARCHAR(50) NOT NULL CHECK (type IN ('straws', 'bottlenecks', 'bottle_tops')),
    color VARCHAR(20) NOT NULL CHECK (color IN ('silver', 'gold')),
    quantity INTEGER NOT NULL DEFAULT 0 CHECK (quantity >= 0),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_bottle_accessories_branch ON bottle_accessories(branch_id);
CREATE INDEX IF NOT EXISTS idx_bottle_accessories_type_color ON bottle_accessories(type, color);

-- ============================================================
-- 2. NEW TABLE: bottle_accessories_movements
-- Tracks stock in/out for bottle accessories
-- ============================================================
CREATE TABLE IF NOT EXISTS bottle_accessories_movements (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL,
    type VARCHAR(50) NOT NULL,
    color VARCHAR(20) NOT NULL,
    movement_type VARCHAR(20) NOT NULL CHECK (movement_type IN ('stock_in', 'stock_out')),
    quantity INTEGER NOT NULL,
    reason TEXT,
    performed_by BIGINT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_bottle_accessories_movements_branch ON bottle_accessories_movements(branch_id);

-- ============================================================
-- 3. NEW TABLE: admin_notifications
-- Critical actions sent to super admin (discounts, price
-- customization, etc.) from any branch.
-- DO NOT touch the existing `notifications` table.
-- ============================================================
CREATE TABLE IF NOT EXISTS admin_notifications (
    id BIGSERIAL PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    branch_id BIGINT,
    user_id BIGINT,
    title VARCHAR(255),
    message TEXT,
    data JSONB,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_admin_notifications_type ON admin_notifications(type);
CREATE INDEX IF NOT EXISTS idx_admin_notifications_branch ON admin_notifications(branch_id);
CREATE INDEX IF NOT EXISTS idx_admin_notifications_read ON admin_notifications(is_read);

-- ============================================================
-- 4. NEW TABLE: news_posts
-- Customer care posts visible to public, labeled by branches
-- ============================================================
CREATE TABLE IF NOT EXISTS news_posts (
    id BIGSERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    branch_id BIGINT NOT NULL,
    author_id BIGINT,
    image_url TEXT,
    is_published BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_news_posts_branch ON news_posts(branch_id);
CREATE INDEX IF NOT EXISTS idx_news_posts_published ON news_posts(is_published);

-- ============================================================
-- 5. NEW TABLE: inquiries
-- Customer messages/attachments from users
-- ============================================================
CREATE TABLE IF NOT EXISTS inquiries (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT,
    branch_id BIGINT NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    attachments JSONB,
    is_read BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'read', 'replied', 'closed')),
    reply_message TEXT,
    replied_by BIGINT,
    replied_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_inquiries_branch ON inquiries(branch_id);
CREATE INDEX IF NOT EXISTS idx_inquiries_status ON inquiries(status);

-- ============================================================
-- 6. ALTER TABLE: bottle_stock_movements
-- Add category mapping fields for bottle state
-- (uses DO block so it succeeds even if columns already exist)
-- ============================================================
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'bottle_stock_movements' AND column_name = 'has_logo') THEN
        ALTER TABLE bottle_stock_movements ADD COLUMN has_logo VARCHAR(10) CHECK (has_logo IN ('yes', 'no'));
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'bottle_stock_movements' AND column_name = 'logo_color') THEN
        ALTER TABLE bottle_stock_movements ADD COLUMN logo_color VARCHAR(20) CHECK (logo_color IN ('yellow', 'black'));
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'bottle_stock_movements' AND column_name = 'has_box') THEN
        ALTER TABLE bottle_stock_movements ADD COLUMN has_box VARCHAR(10) CHECK (has_box IN ('yes', 'no'));
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'bottle_stock_movements' AND column_name = 'box_color') THEN
        ALTER TABLE bottle_stock_movements ADD COLUMN box_color VARCHAR(20) CHECK (box_color IN ('black', 'white'));
    END IF;
END $$;


-- ============================================================
-- 7. ADD ROLES: customer_care and seller
-- If you have a CHECK constraint on the users.role column,
-- update it (uncomment and run):
-- ============================================================
-- ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check;
-- ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN (
--     'super_admin', 'branch_admin', 'cashier', 'stock_manager', 'customer_care', 'seller'
-- ));

-- ============================================================
-- 8. ROW LEVEL SECURITY for new tables
-- ============================================================
ALTER TABLE bottle_accessories ENABLE ROW LEVEL SECURITY;
ALTER TABLE bottle_accessories_movements ENABLE ROW LEVEL SECURITY;
ALTER TABLE admin_notifications ENABLE ROW LEVEL SECURITY;
ALTER TABLE news_posts ENABLE ROW LEVEL SECURITY;
ALTER TABLE inquiries ENABLE ROW LEVEL SECURITY;

-- Allow service_role full access (the app authenticates with the service role key)
DROP POLICY IF EXISTS "Service role full access" ON bottle_accessories;
CREATE POLICY "Service role full access" ON bottle_accessories FOR ALL USING (true);

DROP POLICY IF EXISTS "Service role full access" ON bottle_accessories_movements;
CREATE POLICY "Service role full access" ON bottle_accessories_movements FOR ALL USING (true);

DROP POLICY IF EXISTS "Service role full access" ON admin_notifications;
CREATE POLICY "Service role full access" ON admin_notifications FOR ALL USING (true);

DROP POLICY IF EXISTS "Service role full access" ON news_posts;
CREATE POLICY "Service role full access" ON news_posts FOR ALL USING (true);

DROP POLICY IF EXISTS "Service role full access" ON inquiries;
CREATE POLICY "Service role full access" ON inquiries FOR ALL USING (true);

-- ============================================================
-- DONE! Run all queries above in order.
-- ============================================================
