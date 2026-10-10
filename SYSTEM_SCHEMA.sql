-- ============================================================================
-- WORLD CHOICE PERFUMES — SYSTEM_SCHEMA.sql
-- Complete database structure required for the whole system to operate.
-- Run in a FRESH Supabase project:  SQL Editor -> SYSTEM_SCHEMA.sql -> Run.
-- Then run SYSTEM_SEED.sql in the same editor.
-- ============================================================================

-- Clean slate (safe on a fresh project; re-runnable).
DROP TABLE IF EXISTS public.migrations CASCADE;
DROP TABLE IF EXISTS public.info_email_attachments CASCADE;
DROP TABLE IF EXISTS public.info_email_replies CASCADE;
DROP TABLE IF EXISTS public.info_emails CASCADE;
DROP TABLE IF EXISTS public.stock_transfer_items CASCADE;
DROP TABLE IF EXISTS public.stock_transfers CASCADE;
DROP TABLE IF EXISTS public.news_posts CASCADE;
DROP TABLE IF EXISTS public.inquiries CASCADE;
DROP TABLE IF EXISTS public.admin_notifications CASCADE;
DROP TABLE IF EXISTS public.notifications CASCADE;
DROP TABLE IF EXISTS public.audit_logs CASCADE;
DROP TABLE IF EXISTS public.otp_records CASCADE;
DROP TABLE IF EXISTS public.discrepancies CASCADE;
DROP TABLE IF EXISTS public.cashier_accounts CASCADE;
DROP TABLE IF EXISTS public.expenses CASCADE;
DROP TABLE IF EXISTS public.order_notes CASCADE;
DROP TABLE IF EXISTS public.order_items CASCADE;
DROP TABLE IF EXISTS public.orders CASCADE;
DROP TABLE IF EXISTS public.sale_items CASCADE;
DROP TABLE IF EXISTS public.sales CASCADE;
DROP TABLE IF EXISTS public.stock_movements CASCADE;
DROP TABLE IF EXISTS public.branch_stock_varieties CASCADE;
DROP TABLE IF EXISTS public.branch_stock CASCADE;
DROP TABLE IF EXISTS public.oil_fragrance_movements CASCADE;
DROP TABLE IF EXISTS public.oil_fragrance_stock CASCADE;
DROP TABLE IF EXISTS public.bottle_accessories_movements CASCADE;
DROP TABLE IF EXISTS public.bottle_accessories CASCADE;
DROP TABLE IF EXISTS public.bottle_stock_movements CASCADE;
DROP TABLE IF EXISTS public.bottle_stock CASCADE;
DROP TABLE IF EXISTS public.customers CASCADE;
DROP TABLE IF EXISTS public.company_settings CASCADE;
DROP TABLE IF EXISTS public.brands CASCADE;
DROP TABLE IF EXISTS public.product_images CASCADE;
DROP TABLE IF EXISTS public.products CASCADE;
DROP TABLE IF EXISTS public.failed_jobs CASCADE;
DROP TABLE IF EXISTS public.job_batches CASCADE;
DROP TABLE IF EXISTS public.jobs CASCADE;
DROP TABLE IF EXISTS public.cache_locks CASCADE;
DROP TABLE IF EXISTS public.cache CASCADE;
DROP TABLE IF EXISTS public.sessions CASCADE;
DROP TABLE IF EXISTS public.password_reset_tokens CASCADE;
DROP TABLE IF EXISTS public.users CASCADE;
DROP TABLE IF EXISTS public.branches CASCADE;
DROP TYPE IF EXISTS public.cashier_account_status CASCADE;
DROP TYPE IF EXISTS public.discrepancy_reason CASCADE;
DROP TYPE IF EXISTS public.expense_category CASCADE;
DROP TYPE IF EXISTS public.order_status CASCADE;
DROP TYPE IF EXISTS public.otp_type CASCADE;
DROP TYPE IF EXISTS public.payment_status CASCADE;
DROP TYPE IF EXISTS public.stock_movement_type CASCADE;
DROP TYPE IF EXISTS public.user_role CASCADE;
DROP TYPE IF EXISTS public.user_status CASCADE;

-- ----------------------------------------------------------------------------
-- 1. ENUM TYPES
-- ----------------------------------------------------------------------------
CREATE TYPE public.user_role AS ENUM ('super_admin', 'branch_admin', 'cashier', 'stock_manager', 'customer_care', 'seller', 'graphic_designer');
CREATE TYPE public.user_status AS ENUM ('pending', 'approved', 'rejected', 'active', 'blocked');
CREATE TYPE public.stock_movement_type AS ENUM ('entry', 'sale', 'return', 'adjustment', 'damage', 'missing', 'transfer_out', 'transfer_in');
CREATE TYPE public.payment_status AS ENUM ('pending', 'paid', 'refunded');
CREATE TYPE public.order_status AS ENUM ('pending', 'picked', 'served');
CREATE TYPE public.expense_category AS ENUM ('electricity', 'water', 'rent', 'transport', 'cleaning', 'packaging', 'other');
CREATE TYPE public.cashier_account_status AS ENUM ('pending', 'balanced', 'loss', 'surplus');
CREATE TYPE public.discrepancy_reason AS ENUM ('approved_expense', 'refund', 'discount', 'genuine_shortage', 'surplus', 'damaged_stock', 'missing_stock');
CREATE TYPE public.otp_type AS ENUM ('registration', 'password_reset', 'email_change');

-- ----------------------------------------------------------------------------
-- 2. TABLES
-- ----------------------------------------------------------------------------
-- branches
CREATE TABLE public.branches (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255),
    latitude NUMERIC(10, 8),
    longitude NUMERIC(11, 8),
    profile_picture VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    category VARCHAR(32) DEFAULT 'autonomous' NOT NULL
);

-- users
CREATE TABLE public.users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(255),
    role public.user_role DEFAULT 'cashier' NOT NULL,
    status public.user_status DEFAULT 'pending' NOT NULL,
    branch_id BIGINT REFERENCES public.branches(id) ON DELETE SET NULL,
    profile_picture VARCHAR(255),
    company_secret_code VARCHAR(255),
    otp_verified BOOLEAN DEFAULT FALSE NOT NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (email)
);

-- password_reset_tokens
CREATE TABLE public.password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP
);

-- sessions
CREATE TABLE public.sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);

-- cache
CREATE TABLE public.cache (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    expiration INTEGER NOT NULL
);

-- cache_locks
CREATE TABLE public.cache_locks (
    key VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INTEGER NOT NULL
);

-- jobs
CREATE TABLE public.jobs (
    id BIGSERIAL PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    attempts SMALLINT NOT NULL,
    reserved_at INTEGER,
    available_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL
);

-- job_batches
CREATE TABLE public.job_batches (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    total_jobs INTEGER NOT NULL,
    pending_jobs INTEGER NOT NULL,
    failed_jobs INTEGER NOT NULL,
    failed_job_ids TEXT NOT NULL,
    options TEXT,
    cancelled_at INTEGER,
    created_at INTEGER NOT NULL,
    finished_at INTEGER
);

-- failed_jobs
CREATE TABLE public.failed_jobs (
    id BIGSERIAL PRIMARY KEY,
    uuid VARCHAR(255) NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    UNIQUE (uuid)
);

-- products
CREATE TABLE public.products (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    brand VARCHAR(255),
    category VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    sex_category VARCHAR(20),
    unit_cost NUMERIC(12, 2) DEFAULT 0 NOT NULL,
    costing_volume INTEGER,
    fundamental_ingredient TEXT
);

-- product_images
CREATE TABLE public.product_images (
    id BIGSERIAL PRIMARY KEY,
    product_id BIGINT NOT NULL REFERENCES public.products(id) ON DELETE CASCADE,
    image_url VARCHAR(255) NOT NULL,
    sort_order INTEGER DEFAULT 0 NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- brands
CREATE TABLE public.brands (
    id BIGSERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    logo_url TEXT,
    is_active BOOLEAN DEFAULT TRUE NOT NULL,
    created_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    UNIQUE (name)
);

-- company_settings
CREATE TABLE public.company_settings (
    key TEXT PRIMARY KEY,
    value TEXT,
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- customers
CREATE TABLE public.customers (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255),
    phone VARCHAR(255),
    email VARCHAR(255),
    whatsapp VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- bottle_stock
CREATE TABLE public.bottle_stock (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE CASCADE,
    volume TEXT NOT NULL,
    quantity INTEGER DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    has_logo TEXT,
    logo_color TEXT,
    has_box TEXT,
    box_color TEXT,
    variant TEXT DEFAULT 'plain' NOT NULL,
    UNIQUE (branch_id, volume, variant)
);

-- bottle_stock_movements
CREATE TABLE public.bottle_stock_movements (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE CASCADE,
    volume TEXT NOT NULL,
    type TEXT NOT NULL,
    quantity INTEGER NOT NULL,
    reason TEXT,
    performed_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    has_logo VARCHAR(10),
    logo_color VARCHAR(20),
    has_box VARCHAR(10),
    box_color VARCHAR(20),
    variant TEXT
);

-- bottle_accessories
CREATE TABLE public.bottle_accessories (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL,
    type VARCHAR(50) NOT NULL,
    color VARCHAR(20) NOT NULL,
    quantity INTEGER DEFAULT 0 NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE (branch_id, type, color)
);

-- bottle_accessories_movements
CREATE TABLE public.bottle_accessories_movements (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL,
    type VARCHAR(50) NOT NULL,
    color VARCHAR(20) NOT NULL,
    movement_type VARCHAR(20) NOT NULL,
    quantity INTEGER NOT NULL,
    reason TEXT,
    performed_by BIGINT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- oil_fragrance_stock
CREATE TABLE public.oil_fragrance_stock (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    quantity INTEGER DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    volume INTEGER,
    UNIQUE (branch_id, name, volume)
);

-- oil_fragrance_movements
CREATE TABLE public.oil_fragrance_movements (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    type TEXT NOT NULL,
    quantity INTEGER NOT NULL,
    reason TEXT,
    performed_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    volume INTEGER
);

-- branch_stock
CREATE TABLE public.branch_stock (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES public.products(id) ON DELETE CASCADE,
    quantity INTEGER DEFAULT 0 NOT NULL,
    buying_cost NUMERIC(12, 2) DEFAULT 0 NOT NULL,
    selling_price NUMERIC(12, 2) DEFAULT 0 NOT NULL,
    supplier VARCHAR(255),
    date_received DATE,
    entered_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    category VARCHAR(50),
    UNIQUE (branch_id, product_id)
);

-- branch_stock_varieties
CREATE TABLE public.branch_stock_varieties (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE NO ACTION,
    product_id BIGINT NOT NULL REFERENCES public.products(id) ON DELETE NO ACTION,
    volume INTEGER NOT NULL,
    variant TEXT DEFAULT 'plain' NOT NULL,
    quantity INTEGER DEFAULT 0 NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    selling_price NUMERIC(12, 2) DEFAULT 0 NOT NULL,
    UNIQUE (branch_id, product_id, volume, variant)
);

-- stock_movements
CREATE TABLE public.stock_movements (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES public.products(id) ON DELETE CASCADE,
    type public.stock_movement_type NOT NULL,
    quantity INTEGER NOT NULL,
    unit_cost NUMERIC(12, 2),
    unit_price NUMERIC(12, 2),
    reference_type VARCHAR(255),
    reference_id BIGINT,
    performed_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- sales
CREATE TABLE public.sales (
    id BIGSERIAL PRIMARY KEY,
    sale_number VARCHAR(255) NOT NULL,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE CASCADE,
    cashier_id BIGINT NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    customer_id BIGINT REFERENCES public.customers(id) ON DELETE SET NULL,
    subtotal NUMERIC(12, 2) DEFAULT 0 NOT NULL,
    discount NUMERIC(12, 2) DEFAULT 0 NOT NULL,
    total NUMERIC(12, 2) DEFAULT 0 NOT NULL,
    payment_status public.payment_status DEFAULT 'paid' NOT NULL,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    supplier VARCHAR(255),
    payment_method VARCHAR(50),
    payment_summary TEXT,
    sale_type VARCHAR(20),
    UNIQUE (sale_number)
);

-- sale_items
CREATE TABLE public.sale_items (
    id BIGSERIAL PRIMARY KEY,
    sale_id BIGINT NOT NULL REFERENCES public.sales(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES public.products(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL,
    unit_price NUMERIC(12, 2) NOT NULL,
    unit_cost NUMERIC(12, 2) NOT NULL,
    total NUMERIC(12, 2) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    volume INTEGER,
    variant TEXT
);

-- orders
CREATE TABLE public.orders (
    id BIGSERIAL PRIMARY KEY,
    order_number VARCHAR(255) NOT NULL,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE CASCADE,
    cashier_id BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    customer_id BIGINT REFERENCES public.customers(id) ON DELETE SET NULL,
    status public.order_status DEFAULT 'pending' NOT NULL,
    total NUMERIC(12, 2) DEFAULT 0 NOT NULL,
    delivery_notes TEXT,
    assigned_at TIMESTAMP,
    completed_at TIMESTAMP,
    cancelled_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    payment_status TEXT DEFAULT 'unpaid' NOT NULL,
    payment_method TEXT,
    pesapal_tracking_id TEXT,
    pesapal_merchant_reference TEXT,
    payment_confirmation_code TEXT,
    paid_at TIMESTAMPTZ,
    assigned_to BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    served_at TIMESTAMP,
    personal_order_name TEXT,
    UNIQUE (order_number)
);

-- order_items
CREATE TABLE public.order_items (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES public.orders(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES public.products(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL,
    unit_price NUMERIC(12, 2) NOT NULL,
    total NUMERIC(12, 2) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    volume INTEGER,
    variant TEXT
);

-- order_notes
CREATE TABLE public.order_notes (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES public.orders(id) ON DELETE CASCADE,
    note TEXT NOT NULL,
    created_by BIGINT REFERENCES public.users(id) ON DELETE NO ACTION,
    created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT NOW() NOT NULL
);

-- expenses
CREATE TABLE public.expenses (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    category public.expense_category NOT NULL,
    amount NUMERIC(12, 2) NOT NULL,
    description TEXT NOT NULL,
    date DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- cashier_accounts
CREATE TABLE public.cashier_accounts (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE CASCADE,
    cashier_id BIGINT NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    expected_cash NUMERIC(12, 2) DEFAULT 0 NOT NULL,
    actual_cash NUMERIC(12, 2),
    difference NUMERIC(12, 2),
    status public.cashier_account_status DEFAULT 'pending' NOT NULL,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (cashier_id, date)
);

-- discrepancies
CREATE TABLE public.discrepancies (
    id BIGSERIAL PRIMARY KEY,
    cashier_account_id BIGINT NOT NULL REFERENCES public.cashier_accounts(id) ON DELETE CASCADE,
    branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE CASCADE,
    cashier_id BIGINT NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    reason public.discrepancy_reason NOT NULL,
    amount NUMERIC(12, 2) NOT NULL,
    description TEXT,
    reference_type VARCHAR(255),
    reference_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- otp_records
CREATE TABLE public.otp_records (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES public.users(id) ON DELETE CASCADE,
    email VARCHAR(255) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    type public.otp_type NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- audit_logs
CREATE TABLE public.audit_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    branch_id BIGINT REFERENCES public.branches(id) ON DELETE SET NULL,
    action VARCHAR(255) NOT NULL,
    auditable_type VARCHAR(255),
    auditable_id BIGINT,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- notifications
CREATE TABLE public.notifications (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    type VARCHAR(255) NOT NULL,
    data JSONB NOT NULL,
    read_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- admin_notifications
CREATE TABLE public.admin_notifications (
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

-- inquiries
CREATE TABLE public.inquiries (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT,
    branch_id BIGINT NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    attachments JSONB,
    is_read BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'pending',
    reply_message TEXT,
    replied_by BIGINT,
    replied_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    is_featured BOOLEAN DEFAULT FALSE NOT NULL
);

-- news_posts
CREATE TABLE public.news_posts (
    id BIGSERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    branch_id BIGINT NOT NULL,
    author_id BIGINT,
    image_url TEXT,
    is_published BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    status TEXT,
    rejection_reason TEXT,
    reviewed_by BIGINT,
    reviewed_at TIMESTAMPTZ
);

-- stock_transfers
CREATE TABLE public.stock_transfers (
    id BIGSERIAL PRIMARY KEY,
    transfer_number TEXT NOT NULL,
    stock_type TEXT NOT NULL,
    from_branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE RESTRICT,
    to_branch_id BIGINT NOT NULL REFERENCES public.branches(id) ON DELETE RESTRICT,
    status TEXT DEFAULT 'in_transit' NOT NULL,
    note TEXT,
    officer_name TEXT,
    officer_phone TEXT,
    officer_id TEXT,
    created_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    received_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    received_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    UNIQUE (transfer_number)
);

-- stock_transfer_items
CREATE TABLE public.stock_transfer_items (
    id BIGSERIAL PRIMARY KEY,
    transfer_id BIGINT NOT NULL REFERENCES public.stock_transfers(id) ON DELETE CASCADE,
    stock_type TEXT NOT NULL,
    item_index INTEGER DEFAULT 0 NOT NULL,
    product_id BIGINT REFERENCES public.products(id) ON DELETE RESTRICT,
    name TEXT,
    volume TEXT,
    variant TEXT,
    type TEXT,
    color TEXT,
    quantity INTEGER DEFAULT 0 NOT NULL,
    unit_cost NUMERIC(12, 2),
    unit_price NUMERIC(12, 2),
    category TEXT,
    supplier TEXT,
    status TEXT DEFAULT 'in_transit' NOT NULL,
    received_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    received_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    variety_unit_price NUMERIC(12, 2),
    return_reason TEXT,
    return_status TEXT DEFAULT 'pending',
    loss_reason TEXT,
    resent_transfer_id BIGINT,
    returned_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    returned_at TIMESTAMPTZ,
    damage_type TEXT,
    damage_reason TEXT,
    damage_reported_by BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    damage_reported_at TIMESTAMPTZ
);

-- info_emails
CREATE TABLE public.info_emails (
    id BIGSERIAL PRIMARY KEY,
    message_id TEXT,
    in_reply_to TEXT,
    reference_ids TEXT,
    from_email TEXT NOT NULL,
    from_name TEXT,
    to_email TEXT,
    cc TEXT,
    bcc TEXT,
    reply_to TEXT,
    subject TEXT,
    body_text TEXT,
    body_html TEXT,
    raw_email TEXT,
    headers JSONB,
    attachment_names TEXT,
    has_attachments BOOLEAN DEFAULT FALSE NOT NULL,
    spf_result TEXT,
    dkim_result TEXT,
    is_read BOOLEAN DEFAULT FALSE NOT NULL,
    is_starred BOOLEAN DEFAULT FALSE NOT NULL,
    status TEXT DEFAULT 'new' NOT NULL,
    thread_key TEXT,
    parent_id BIGINT REFERENCES public.info_emails(id) ON DELETE SET NULL,
    received_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    read_at TIMESTAMPTZ,
    replied_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT NOW() NOT NULL
);

-- info_email_replies
CREATE TABLE public.info_email_replies (
    id BIGSERIAL PRIMARY KEY,
    info_email_id BIGINT NOT NULL REFERENCES public.info_emails(id) ON DELETE CASCADE,
    from_email TEXT NOT NULL,
    to_email TEXT NOT NULL,
    subject TEXT,
    body TEXT NOT NULL,
    message_id TEXT,
    in_reply_to TEXT,
    status TEXT DEFAULT 'sent' NOT NULL,
    error TEXT,
    sent_by BIGINT,
    sent_by_name TEXT,
    sent_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    attachment_names TEXT
);

-- info_email_attachments
CREATE TABLE public.info_email_attachments (
    id BIGSERIAL PRIMARY KEY,
    info_email_id BIGINT NOT NULL REFERENCES public.info_emails(id) ON DELETE CASCADE,
    file_name TEXT NOT NULL,
    mime_type TEXT,
    size_bytes BIGINT,
    storage_path TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL
);

-- migrations
CREATE TABLE public.migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
);

-- ----------------------------------------------------------------------------
-- 3. INDEXES
-- ----------------------------------------------------------------------------
CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);
CREATE INDEX idx_cache_expiration ON cache(expiration);
CREATE INDEX idx_jobs_queue ON jobs(queue);
CREATE INDEX idx_customers_phone ON customers(phone);
CREATE INDEX idx_stock_movements_branch_product_type ON stock_movements(branch_id, product_id, type);
CREATE INDEX idx_stock_movements_reference ON stock_movements(reference_type, reference_id);
CREATE INDEX idx_sales_branch_created ON sales(branch_id, created_at);
CREATE INDEX idx_sales_cashier_created ON sales(cashier_id, created_at);
CREATE INDEX idx_orders_branch_status ON orders(branch_id, status);
CREATE INDEX idx_orders_assigned_status ON orders(assigned_to, status);
CREATE INDEX idx_orders_assigned_to ON orders(assigned_to);
CREATE INDEX idx_orders_pesapal_tracking ON orders(pesapal_tracking_id);
CREATE INDEX idx_orders_payment_status ON orders(payment_status);
CREATE INDEX idx_order_notes_order ON order_notes(order_id);
CREATE INDEX idx_expenses_branch_created ON expenses(branch_id, created_at);
CREATE INDEX idx_discrepancies_cashier_account ON discrepancies(cashier_account_id);
CREATE INDEX idx_otp_records_email_type ON otp_records(email, type);
CREATE INDEX idx_audit_logs_auditable ON audit_logs(auditable_type, auditable_id);
CREATE INDEX idx_audit_logs_user_created ON audit_logs(user_id, created_at);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, read_at);
CREATE INDEX idx_brands_active ON brands(is_active, name);
CREATE INDEX idx_brands_name ON brands(name);
CREATE INDEX idx_inquiries_is_featured ON inquiries(is_featured, created_at DESC);
CREATE INDEX idx_oil_fragrance_stock_branch_name ON oil_fragrance_stock(branch_id, name);
CREATE INDEX idx_branch_stock_varieties_branch_product ON branch_stock_varieties(branch_id, product_id);
CREATE INDEX idx_stock_transfers_to ON stock_transfers(to_branch_id, status);
CREATE INDEX idx_stock_transfers_from ON stock_transfers(from_branch_id);
CREATE INDEX idx_stock_transfer_items_transfer ON stock_transfer_items(transfer_id);
CREATE INDEX idx_stock_transfer_items_status ON stock_transfer_items(transfer_id, status);
CREATE INDEX idx_stock_transfer_items_return_status ON stock_transfer_items(return_status, status);
CREATE INDEX idx_stock_transfer_items_damage ON stock_transfer_items(return_status, damage_type);
CREATE INDEX idx_info_emails_thread_key ON info_emails(thread_key);
CREATE INDEX idx_info_emails_received_at ON info_emails(received_at DESC);
CREATE UNIQUE INDEX idx_info_emails_message_id ON info_emails(message_id) WHERE message_id IS NOT NULL;
CREATE INDEX idx_info_email_replies_email ON info_email_replies(info_email_id);
CREATE UNIQUE INDEX idx_info_email_replies_message_id ON info_email_replies(message_id) WHERE message_id IS NOT NULL;
CREATE INDEX idx_info_email_attachments_email ON info_email_attachments(info_email_id);

-- ----------------------------------------------------------------------------
-- 4. PRIVILEGES (Supabase roles)
-- ----------------------------------------------------------------------------
GRANT USAGE ON SCHEMA public TO anon, authenticated, service_role;
GRANT ALL ON ALL TABLES IN SCHEMA public TO anon, authenticated, service_role;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO anon, authenticated, service_role;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO anon, authenticated, service_role;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO anon, authenticated, service_role;

-- ----------------------------------------------------------------------------
-- 5. STORAGE BUCKET (private, for info@ mail attachments)
-- ----------------------------------------------------------------------------
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.schemata WHERE schema_name = 'storage') THEN
        INSERT INTO storage.buckets (id, name, public, file_size_limit)
        VALUES ('info-mail-attachments', 'info-mail-attachments', false, 26214400)
        ON CONFLICT (id) DO NOTHING;
    END IF;
END $$;

NOTIFY pgrst, 'reload schema';
