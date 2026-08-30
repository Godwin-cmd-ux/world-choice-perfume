-- ============================================================================
-- WORLD CHOICE PERFUMES — COMPLETE SETUP (Schema + Seed)
-- Run this single file in: Supabase Dashboard → SQL Editor
-- ============================================================================

-- ============================================================================
-- 1. DROP everything (nuclear option - guaranteed clean slate)
-- ============================================================================
-- Revoke access from anon and service_role to avoid FK issues during drop
REVOKE ALL ON ALL TABLES IN SCHEMA public FROM anon;
REVOKE ALL ON ALL TABLES IN SCHEMA public FROM service_role;
REVOKE ALL ON ALL FUNCTIONS IN SCHEMA public FROM anon;
REVOKE ALL ON ALL FUNCTIONS IN SCHEMA public FROM service_role;

-- Drop all tables
DROP SCHEMA public CASCADE;
CREATE SCHEMA public;

-- Re-grant permissions for Supabase
GRANT USAGE ON SCHEMA public TO anon;
GRANT USAGE ON SCHEMA public TO service_role;
GRANT ALL ON ALL TABLES IN SCHEMA public TO anon;
GRANT ALL ON ALL TABLES IN SCHEMA public TO service_role;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO anon;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO service_role;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO anon;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO service_role;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO anon;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO service_role;

-- ============================================================================
-- 2. CREATE ENUM TYPES
-- ============================================================================
CREATE TYPE user_role AS ENUM ('super_admin', 'branch_admin', 'cashier');
CREATE TYPE user_status AS ENUM ('pending', 'approved', 'rejected', 'active');
CREATE TYPE stock_movement_type AS ENUM ('entry', 'sale', 'return', 'adjustment', 'damage', 'missing');
CREATE TYPE payment_status AS ENUM ('pending', 'paid', 'refunded');
CREATE TYPE order_status AS ENUM ('pending', 'assigned', 'ready', 'completed', 'cancelled');
CREATE TYPE expense_category AS ENUM ('electricity', 'water', 'rent', 'transport', 'cleaning', 'packaging', 'other');
CREATE TYPE cashier_account_status AS ENUM ('pending', 'balanced', 'loss', 'surplus');
CREATE TYPE discrepancy_reason AS ENUM ('approved_expense', 'refund', 'discount', 'genuine_shortage', 'surplus', 'damaged_stock', 'missing_stock');
CREATE TYPE otp_type AS ENUM ('registration', 'password_reset', 'email_change');

-- ============================================================================
-- 3. CREATE TABLES
-- ============================================================================

-- branches
CREATE TABLE branches (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255),
    latitude NUMERIC(10, 8),
    longitude NUMERIC(11, 8),
    profile_picture VARCHAR(255),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- users (depends on branches)
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(255),
    role user_role NOT NULL DEFAULT 'cashier',
    status user_status NOT NULL DEFAULT 'pending',
    branch_id BIGINT REFERENCES branches(id) ON DELETE SET NULL,
    profile_picture VARCHAR(255),
    company_secret_code VARCHAR(255),
    otp_verified BOOLEAN NOT NULL DEFAULT FALSE,
    remember_token VARCHAR(100),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- password_reset_tokens
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);

-- sessions
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);
CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);

-- cache
CREATE TABLE cache (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    expiration INTEGER NOT NULL
);
CREATE INDEX idx_cache_expiration ON cache(expiration);

-- cache_locks
CREATE TABLE cache_locks (
    key VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INTEGER NOT NULL
);

-- jobs
CREATE TABLE jobs (
    id BIGSERIAL PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    attempts SMALLINT NOT NULL,
    reserved_at INTEGER,
    available_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL
);
CREATE INDEX idx_jobs_queue ON jobs(queue);

-- job_batches
CREATE TABLE job_batches (
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
CREATE TABLE failed_jobs (
    id BIGSERIAL PRIMARY KEY,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- products
CREATE TABLE products (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    brand VARCHAR(255),
    category VARCHAR(255),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- product_images (depends on products)
CREATE TABLE product_images (
    id BIGSERIAL PRIMARY KEY,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    image_url VARCHAR(255) NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- branch_stock (depends on branches, products, users)
CREATE TABLE branch_stock (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL DEFAULT 0,
    buying_cost NUMERIC(12, 2) NOT NULL DEFAULT 0,
    selling_price NUMERIC(12, 2) NOT NULL DEFAULT 0,
    supplier VARCHAR(255),
    date_received DATE,
    entered_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE (branch_id, product_id)
);

-- stock_movements (depends on branches, products, users)
CREATE TABLE stock_movements (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    type stock_movement_type NOT NULL,
    quantity INTEGER NOT NULL,
    unit_cost NUMERIC(12, 2),
    unit_price NUMERIC(12, 2),
    reference_type VARCHAR(255),
    reference_id BIGINT,
    performed_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    notes TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX idx_stock_movements_branch_product_type ON stock_movements(branch_id, product_id, type);
CREATE INDEX idx_stock_movements_reference ON stock_movements(reference_type, reference_id);

-- customers
CREATE TABLE customers (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255),
    phone VARCHAR(255),
    email VARCHAR(255),
    whatsapp VARCHAR(255),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX idx_customers_phone ON customers(phone);

-- sales (depends on branches, users, customers)
CREATE TABLE sales (
    id BIGSERIAL PRIMARY KEY,
    sale_number VARCHAR(255) NOT NULL UNIQUE,
    branch_id BIGINT NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
    cashier_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    customer_id BIGINT REFERENCES customers(id) ON DELETE SET NULL,
    subtotal NUMERIC(12, 2) NOT NULL DEFAULT 0,
    discount NUMERIC(12, 2) NOT NULL DEFAULT 0,
    total NUMERIC(12, 2) NOT NULL DEFAULT 0,
    payment_status payment_status NOT NULL DEFAULT 'paid',
    notes TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX idx_sales_branch_created ON sales(branch_id, created_at);
CREATE INDEX idx_sales_cashier_created ON sales(cashier_id, created_at);

-- sale_items (depends on sales, products)
CREATE TABLE sale_items (
    id BIGSERIAL PRIMARY KEY,
    sale_id BIGINT NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL,
    unit_price NUMERIC(12, 2) NOT NULL,
    unit_cost NUMERIC(12, 2) NOT NULL,
    total NUMERIC(12, 2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- orders (depends on branches, users, customers)
CREATE TABLE orders (
    id BIGSERIAL PRIMARY KEY,
    order_number VARCHAR(255) NOT NULL UNIQUE,
    branch_id BIGINT NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
    cashier_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    customer_id BIGINT REFERENCES customers(id) ON DELETE SET NULL,
    status order_status NOT NULL DEFAULT 'pending',
    total NUMERIC(12, 2) NOT NULL DEFAULT 0,
    delivery_notes TEXT,
    assigned_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX idx_orders_branch_status ON orders(branch_id, status);

-- order_items (depends on orders, products)
CREATE TABLE order_items (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL,
    unit_price NUMERIC(12, 2) NOT NULL,
    total NUMERIC(12, 2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- expenses (depends on branches, users)
CREATE TABLE expenses (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    category expense_category NOT NULL,
    amount NUMERIC(12, 2) NOT NULL,
    description TEXT NOT NULL,
    date DATE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX idx_expenses_branch_created ON expenses(branch_id, created_at);

-- cashier_accounts (depends on branches, users)
CREATE TABLE cashier_accounts (
    id BIGSERIAL PRIMARY KEY,
    branch_id BIGINT NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
    cashier_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    expected_cash NUMERIC(12, 2) NOT NULL DEFAULT 0,
    actual_cash NUMERIC(12, 2),
    difference NUMERIC(12, 2),
    status cashier_account_status NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE (cashier_id, date)
);

-- discrepancies (depends on cashier_accounts, branches, users)
CREATE TABLE discrepancies (
    id BIGSERIAL PRIMARY KEY,
    cashier_account_id BIGINT NOT NULL REFERENCES cashier_accounts(id) ON DELETE CASCADE,
    branch_id BIGINT NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
    cashier_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    reason discrepancy_reason NOT NULL,
    amount NUMERIC(12, 2) NOT NULL,
    description TEXT,
    reference_type VARCHAR(255),
    reference_id BIGINT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX idx_discrepancies_cashier_account ON discrepancies(cashier_account_id);

-- otp_records (depends on users)
CREATE TABLE otp_records (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    email VARCHAR(255) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    type otp_type NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX idx_otp_records_email_type ON otp_records(email, type);

-- audit_logs (depends on users, branches)
CREATE TABLE audit_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    branch_id BIGINT REFERENCES branches(id) ON DELETE SET NULL,
    action VARCHAR(255) NOT NULL,
    auditable_type VARCHAR(255),
    auditable_id BIGINT,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX idx_audit_logs_auditable ON audit_logs(auditable_type, auditable_id);
CREATE INDEX idx_audit_logs_user_created ON audit_logs(user_id, created_at);

-- notifications (depends on users)
CREATE TABLE notifications (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(255) NOT NULL,
    data JSONB NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, read_at);

-- Laravel migrations tracking
CREATE TABLE migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
);

-- ============================================================================
-- 4. SEED DATA — All passwords are: password
-- ============================================================================

-- BRANCHES (must come first — users depend on them)
INSERT INTO branches (name, address, latitude, longitude, is_active, created_at, updated_at)
VALUES
    ('Posta Main Store', '15 Posta Road, Dar es Salaam', -6.7924, 39.2083, TRUE, NOW(), NOW()),
    ('Kariakoo Branch', '22 Kariakoo Market Area, Dar es Salaam', -6.8161, 39.2793, TRUE, NOW(), NOW()),
    ('Arusha Branch', '45 CIDC Area, Arusha', -3.3869, 36.6830, TRUE, NOW(), NOW()),
    ('Mwanza Flagship', '7 Airport Road, Mwanza', -2.5164, 32.9175, TRUE, NOW(), NOW()),
    ('Zanzibar Branch', '12 Stone Town, Zanzibar', -6.1622, 39.1924, TRUE, NOW(), NOW());

-- USERS — Super Admin
INSERT INTO users (name, email, password, phone, role, status, otp_verified, created_at, updated_at)
VALUES (
    'Super Admin',
    'admin@worldchoiceperfumes.co.tz',
    '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j',
    '+255754000000',
    'super_admin',
    'active',
    TRUE,
    NOW(),
    NOW()
);

-- USERS — Branch Admins
INSERT INTO users (name, email, password, phone, role, status, branch_id, otp_verified, created_at, updated_at)
VALUES
    ('Hassan Mwangi', 'hassan@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255754000001', 'branch_admin', 'active', 1, TRUE, NOW(), NOW()),
    ('Amina Juma', 'amina@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255754000002', 'branch_admin', 'active', 2, TRUE, NOW(), NOW()),
    ('David Mushi', 'david@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255754000003', 'branch_admin', 'active', 3, TRUE, NOW(), NOW()),
    ('Grace Kimaro', 'grace@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255754000004', 'branch_admin', 'active', 4, TRUE, NOW(), NOW()),
    ('Emmanuel Shirima', 'emmanuel@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255754000005', 'branch_admin', 'active', 5, TRUE, NOW(), NOW());

-- USERS — Cashiers
INSERT INTO users (name, email, password, phone, role, status, branch_id, otp_verified, created_at, updated_at)
VALUES
    ('Fatima Omari', 'fatima@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000001', 'cashier', 'active', 1, TRUE, NOW(), NOW()),
    ('Blessing Mwakasege', 'blessing@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000002', 'cashier', 'active', 1, TRUE, NOW(), NOW()),
    ('Ibrahim Kibona', 'ibrahim@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000003', 'cashier', 'active', 2, TRUE, NOW(), NOW()),
    ('Grace Mwasaga', 'grace@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000004', 'cashier', 'active', 3, TRUE, NOW(), NOW()),
    ('Samuel Ndege', 'samuel@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000005', 'cashier', 'active', 4, TRUE, NOW(), NOW()),
    ('Hauwa Ramadhani', 'hauwa@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000006', 'cashier', 'active', 5, TRUE, NOW(), NOW()),
    ('Pending Cashier One', 'pending1@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000007', 'cashier', 'pending', 1, TRUE, NOW(), NOW()),
    ('Pending Cashier Two', 'pending2@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000008', 'cashier', 'pending', 2, TRUE, NOW(), NOW()),
    ('Rejected Cashier', 'rejected@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000009', 'cashier', 'rejected', 3, TRUE, NOW(), NOW());

-- PRODUCTS
INSERT INTO products (name, description, brand, category, is_active, created_at, updated_at)
VALUES
    ('Bleu de Chanel EDP', 'Aromatic Woody fragrance for men.', 'Chanel', 'Men', TRUE, NOW(), NOW()),
    ('Dior Sauvage EDT', 'Fresh spicy fragrance for men.', 'Dior', 'Men', TRUE, NOW(), NOW()),
    ('Versace Pour Homme', 'Mediterranean fragrance for men.', 'Versace', 'Men', TRUE, NOW(), NOW()),
    ('Acqua di Gio Profumo', 'Marine and aromatic fragrance.', 'Giorgio Armani', 'Men', TRUE, NOW(), NOW()),
    ('YSL Y EDP', 'Aromatic fougere for men.', 'Yves Saint Laurent', 'Men', TRUE, NOW(), NOW()),
    ('Paco Rabanne 1 Million', 'Spicy woody leather fragrance.', 'Paco Rabanne', 'Men', TRUE, NOW(), NOW()),
    ('Jean Paul Gaultier Le Male', 'Oriental fougere.', 'Jean Paul Gaultier', 'Men', TRUE, NOW(), NOW()),
    ('Tom Ford Noir', 'Amber woody fragrance.', 'Tom Ford', 'Men', TRUE, NOW(), NOW()),
    ('Chanel No. 5 EDP', 'The iconic aldehydic floral.', 'Chanel', 'Women', TRUE, NOW(), NOW()),
    ('Miss Dior Blooming Bouquet', 'Floral fragrance.', 'Dior', 'Women', TRUE, NOW(), NOW()),
    ('YSL Black Opium', 'Sweet vanilla coffee fragrance.', 'Yves Saint Laurent', 'Women', TRUE, NOW(), NOW()),
    ('Lancôme La Vie Est Belle', 'Sweet gourmand fragrance.', 'Lancôme', 'Women', TRUE, NOW(), NOW()),
    ('Versace Bright Crystal', 'Fresh floral fragrance.', 'Versace', 'Women', TRUE, NOW(), NOW()),
    ('Gucci Bloom', 'White floral fragrance.', 'Gucci', 'Women', TRUE, NOW(), NOW()),
    ('Dolce & Gabbana Light Blue', 'Fresh citrus fragrance.', 'Dolce & Gabbana', 'Women', TRUE, NOW(), NOW()),
    ('Chanel Coco Mademoiselle', 'Orange-patchouli-vanilla.', 'Chanel', 'Women', TRUE, NOW(), NOW()),
    ('Creed Aventus', 'Fruity woody fragrance.', 'Creed', 'Unisex', TRUE, NOW(), NOW()),
    ('Tom Ford Oud Wood', 'Woody aromatic.', 'Tom Ford', 'Unisex', TRUE, NOW(), NOW()),
    ('Le Labo Santal 33', 'Woody aromatic.', 'Le Labo', 'Unisex', TRUE, NOW(), NOW()),
    ('Byredo Gypsy Water', 'Woody aromatic.', 'Byredo', 'Unisex', TRUE, NOW(), NOW()),
    ('Chanel Discovery Set', 'Set of 4 mini EDT bottles.', 'Chanel', 'Gift Set', TRUE, NOW(), NOW()),
    ('Dior Prestige Gift Box', 'Luxury gift box.', 'Dior', 'Gift Set', TRUE, NOW(), NOW()),
    ('Perfume Storage Box', 'Elegant storage for 12 bottles.', 'Generic', 'Accessories', TRUE, NOW(), NOW()),
    ('Travel Atomizer 10ml', 'Refillable gold-plated atomizer.', 'Generic', 'Accessories', TRUE, NOW(), NOW());

-- CUSTOMERS
INSERT INTO customers (name, phone, email, whatsapp, created_at, updated_at)
VALUES
    ('Abdul Nyerere', '+255712345678', 'abdul@email.com', '+255712345678', NOW(), NOW()),
    ('Fatima Omary', '+255723456789', 'fatima.c@email.com', '+255723456789', NOW(), NOW()),
    ('John Mwakasegela', '+255734567890', 'john@email.com', '+255734567890', NOW(), NOW()),
    ('Amina Hemed', '+255745678901', 'amina.h@email.com', '+255745678901', NOW(), NOW()),
    ('Peter Kimaro', '+255756789012', 'peter@email.com', '+255756789012', NOW(), NOW()),
    ('Rebecca Shirima', '+255767890123', 'rebecca@email.com', '+255767890123', NOW(), NOW()),
    ('Yusuf Kibona', '+255778901234', 'yusuf@email.com', '+255778901234', NOW(), NOW()),
    ('Neema Mwasaga', '+255789012345', 'neema@email.com', '+255789012345', NOW(), NOW()),
    ('Daniel Ndege', '+255790123456', 'daniel@email.com', '+255790123456', NOW(), NOW()),
    ('Happiness Mushi', '+255701234567', 'happiness@email.com', '+255701234567', NOW(), NOW());

-- BRANCH STOCK — Branch 1 (Lekki)
INSERT INTO branch_stock (branch_id, product_id, quantity, buying_cost, selling_price, supplier, date_received, entered_by, created_at, updated_at)
VALUES
    (1, 1, 25, 85000, 135000, 'Chanel Distribution Tanzania', '2026-01-15', 2, NOW(), NOW()),
    (1, 2, 30, 70000, 115000, 'Dior Supply Chain', '2026-01-15', 2, NOW(), NOW()),
    (1, 3, 20, 45000, 75000, 'Versace Imports', '2026-01-20', 2, NOW(), NOW()),
    (1, 4, 15, 75000, 125000, 'Armani Wholesale', '2026-01-20', 2, NOW(), NOW()),
    (1, 5, 18, 65000, 105000, 'YSL Distributors', '2026-02-01', 2, NOW(), NOW()),
    (1, 6, 22, 40000, 68000, 'Paco Rabanne Nig.', '2026-02-01', 2, NOW(), NOW()),
    (1, 7, 12, 55000, 90000, 'JPG Africa', '2026-02-10', 2, NOW(), NOW()),
    (1, 8, 8, 120000, 195000, 'Tom Ford Luxury', '2026-02-10', 2, NOW(), NOW()),
    (1, 9, 20, 90000, 145000, 'Chanel Distribution Tanzania', '2026-01-15', 2, NOW(), NOW()),
    (1, 10, 25, 60000, 98000, 'Dior Supply Chain', '2026-01-15', 2, NOW(), NOW()),
    (1, 11, 30, 55000, 92000, 'YSL Distributors', '2026-02-01', 2, NOW(), NOW()),
    (1, 12, 28, 50000, 82000, 'Lancôme West Africa', '2026-02-05', 2, NOW(), NOW()),
    (1, 13, 22, 42000, 70000, 'Versace Imports', '2026-01-20', 2, NOW(), NOW()),
    (1, 14, 18, 58000, 95000, 'Gucci Distributors', '2026-02-10', 2, NOW(), NOW()),
    (1, 15, 20, 48000, 78000, 'D&G Imports', '2026-02-15', 2, NOW(), NOW()),
    (1, 16, 22, 85000, 138000, 'Chanel Distribution Tanzania', '2026-01-15', 2, NOW(), NOW()),
    (1, 17, 10, 150000, 245000, 'Creed International', '2026-02-20', 2, NOW(), NOW()),
    (1, 18, 8, 130000, 210000, 'Tom Ford Luxury', '2026-02-10', 2, NOW(), NOW()),
    (1, 19, 12, 95000, 155000, 'Le Labo Direct', '2026-03-01', 2, NOW(), NOW()),
    (1, 20, 10, 75000, 122000, 'Byredo Nordics', '2026-03-01', 2, NOW(), NOW()),
    (1, 21, 15, 35000, 55000, 'Chanel Distribution Tanzania', '2026-03-05', 2, NOW(), NOW()),
    (1, 22, 10, 80000, 128000, 'Dior Supply Chain', '2026-03-05', 2, NOW(), NOW()),
    (1, 23, 30, 8000, 15000, 'Generic Accessories Ltd', '2026-01-10', 2, NOW(), NOW()),
    (1, 24, 50, 3500, 8000, 'Generic Accessories Ltd', '2026-01-10', 2, NOW(), NOW());

-- BRANCH STOCK — Branch 2 (Victoria Island)
INSERT INTO branch_stock (branch_id, product_id, quantity, buying_cost, selling_price, supplier, date_received, entered_by, created_at, updated_at)
VALUES
    (2, 1, 15, 85000, 140000, 'Chanel Distribution Tanzania', '2026-02-01', 3, NOW(), NOW()),
    (2, 2, 20, 70000, 118000, 'Dior Supply Chain', '2026-02-01', 3, NOW(), NOW()),
    (2, 4, 10, 75000, 128000, 'Armani Wholesale', '2026-02-15', 3, NOW(), NOW()),
    (2, 9, 12, 90000, 148000, 'Chanel Distribution Tanzania', '2026-02-01', 3, NOW(), NOW()),
    (2, 11, 18, 55000, 95000, 'YSL Distributors', '2026-02-15', 3, NOW(), NOW()),
    (2, 12, 15, 50000, 85000, 'Lancôme West Africa', '2026-03-01', 3, NOW(), NOW()),
    (2, 17, 5, 150000, 250000, 'Creed International', '2026-03-01', 3, NOW(), NOW()),
    (2, 24, 30, 3500, 8500, 'Generic Accessories Ltd', '2026-02-01', 3, NOW(), NOW());

-- BRANCH STOCK — Branch 3 (Ikeja)
INSERT INTO branch_stock (branch_id, product_id, quantity, buying_cost, selling_price, supplier, date_received, entered_by, created_at, updated_at)
VALUES
    (3, 2, 18, 70000, 112000, 'Dior Supply Chain', '2026-02-10', 4, NOW(), NOW()),
    (3, 3, 15, 45000, 72000, 'Versace Imports', '2026-02-10', 4, NOW(), NOW()),
    (3, 6, 20, 40000, 65000, 'Paco Rabanne Nig.', '2026-02-20', 4, NOW(), NOW()),
    (3, 10, 18, 60000, 95000, 'Dior Supply Chain', '2026-02-10', 4, NOW(), NOW()),
    (3, 13, 15, 42000, 68000, 'Versace Imports', '2026-02-20', 4, NOW(), NOW()),
    (3, 15, 12, 48000, 76000, 'D&G Imports', '2026-03-05', 4, NOW(), NOW()),
    (3, 23, 20, 8000, 14000, 'Generic Accessories Ltd', '2026-02-01', 4, NOW(), NOW());

-- BRANCH STOCK — Branch 4 (Mwanza)
INSERT INTO branch_stock (branch_id, product_id, quantity, buying_cost, selling_price, supplier, date_received, entered_by, created_at, updated_at)
VALUES
    (4, 1, 10, 85000, 142000, 'Chanel Distribution Tanzania', '2026-03-01', 5, NOW(), NOW()),
    (4, 5, 12, 65000, 108000, 'YSL Distributors', '2026-03-01', 5, NOW(), NOW()),
    (4, 9, 8, 90000, 150000, 'Chanel Distribution Tanzania', '2026-03-01', 5, NOW(), NOW()),
    (4, 11, 15, 55000, 96000, 'YSL Distributors', '2026-03-01', 5, NOW(), NOW()),
    (4, 16, 10, 85000, 142000, 'Chanel Distribution Tanzania', '2026-03-01', 5, NOW(), NOW()),
    (4, 20, 8, 75000, 125000, 'Byredo Nordics', '2026-03-10', 5, NOW(), NOW()),
    (4, 24, 25, 3500, 8200, 'Generic Accessories Ltd', '2026-03-01', 5, NOW(), NOW());

-- BRANCH STOCK — Branch 5 (Zanzibar)
INSERT INTO branch_stock (branch_id, product_id, quantity, buying_cost, selling_price, supplier, date_received, entered_by, created_at, updated_at)
VALUES
    (5, 3, 12, 45000, 73000, 'Versace Imports', '2026-03-10', 6, NOW(), NOW()),
    (5, 6, 15, 40000, 66000, 'Paco Rabanne Nig.', '2026-03-10', 6, NOW(), NOW()),
    (5, 7, 10, 55000, 88000, 'JPG Africa', '2026-03-10', 6, NOW(), NOW()),
    (5, 12, 12, 50000, 83000, 'Lancôme West Africa', '2026-03-15', 6, NOW(), NOW()),
    (5, 14, 8, 58000, 94000, 'Gucci Distributors', '2026-03-15', 6, NOW(), NOW()),
    (5, 19, 6, 95000, 158000, 'Le Labo Direct', '2026-03-20', 6, NOW(), NOW()),
    (5, 24, 40, 3500, 7800, 'Generic Accessories Ltd', '2026-03-10', 6, NOW(), NOW());

-- SALES
INSERT INTO sales (sale_number, branch_id, cashier_id, customer_id, subtotal, discount, total, payment_status, notes, created_at, updated_at)
VALUES
    ('SALE-000001', 1, 6, 1, 233000, 0, 233000, 'paid', 'Walk-in customer', DATE_TRUNC('day', NOW()) - INTERVAL '10 days', NOW()),
    ('SALE-000002', 1, 6, 2, 115000, 5000, 110000, 'paid', 'Regular customer discount', DATE_TRUNC('day', NOW()) - INTERVAL '9 days', NOW()),
    ('SALE-000003', 1, 7, 3, 310000, 10000, 300000, 'paid', 'Birthday purchase', DATE_TRUNC('day', NOW()) - INTERVAL '8 days', NOW()),
    ('SALE-000004', 1, 6, NULL, 68000, 0, 68000, 'paid', 'Anonymous walk-in', DATE_TRUNC('day', NOW()) - INTERVAL '7 days', NOW()),
    ('SALE-000005', 1, 7, 4, 190000, 0, 190000, 'paid', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '6 days', NOW()),
    ('SALE-000006', 1, 6, 5, 345000, 15000, 330000, 'paid', 'VIP customer', DATE_TRUNC('day', NOW()) - INTERVAL '5 days', NOW()),
    ('SALE-000007', 1, 7, 6, 98000, 0, 98000, 'paid', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '4 days', NOW()),
    ('SALE-000008', 1, 6, NULL, 155000, 0, 155000, 'paid', 'Cash payment', DATE_TRUNC('day', NOW()) - INTERVAL '3 days', NOW()),
    ('SALE-000009', 1, 7, 7, 263000, 8000, 255000, 'paid', 'Anniversary gift set', DATE_TRUNC('day', NOW()) - INTERVAL '2 days', NOW()),
    ('SALE-000010', 1, 6, 8, 423000, 23000, 400000, 'paid', 'Bulk order', DATE_TRUNC('day', NOW()) - INTERVAL '1 day', NOW()),
    ('SALE-000011', 1, 6, NULL, 125000, 0, 125000, 'paid', 'Walk-in', NOW(), NOW()),
    ('SALE-000012', 1, 7, 9, 210000, 0, 210000, 'paid', 'Gift purchase', NOW(), NOW()),
    ('SALE-000013', 2, 8, 1, 140000, 0, 140000, 'paid', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '5 days', NOW()),
    ('SALE-000014', 2, 8, 4, 95000, 5000, 90000, 'paid', 'Repeat customer', DATE_TRUNC('day', NOW()) - INTERVAL '3 days', NOW()),
    ('SALE-000015', 2, 8, NULL, 250000, 0, 250000, 'paid', 'Creed Aventus', DATE_TRUNC('day', NOW()) - INTERVAL '1 day', NOW()),
    ('SALE-000016', 3, 9, 5, 112000, 0, 112000, 'paid', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '4 days', NOW()),
    ('SALE-000017', 3, 9, 10, 184000, 0, 184000, 'paid', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '2 days', NOW());

-- SALE ITEMS
INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, unit_cost, total, created_at, updated_at)
VALUES
    (1, 1, 1, 135000, 85000, 135000, NOW(), NOW()),
    (1, 2, 1, 98000, 70000, 98000, NOW(), NOW()),
    (2, 2, 1, 115000, 70000, 115000, NOW(), NOW()),
    (3, 11, 1, 92000, 55000, 92000, NOW(), NOW()),
    (3, 12, 1, 82000, 50000, 82000, NOW(), NOW()),
    (3, 10, 1, 98000, 60000, 98000, NOW(), NOW()),
    (3, 13, 1, 38000, 42000, 38000, NOW(), NOW()),
    (4, 6, 1, 68000, 40000, 68000, NOW(), NOW()),
    (5, 9, 1, 98000, 90000, 98000, NOW(), NOW()),
    (5, 10, 1, 92000, 60000, 92000, NOW(), NOW()),
    (6, 8, 1, 195000, 120000, 195000, NOW(), NOW()),
    (6, 18, 1, 210000, 130000, 210000, NOW(), NOW()),
    (7, 14, 1, 95000, 58000, 95000, NOW(), NOW()),
    (8, 7, 1, 90000, 55000, 90000, NOW(), NOW()),
    (8, 3, 1, 65000, 45000, 65000, NOW(), NOW()),
    (9, 19, 1, 155000, 95000, 155000, NOW(), NOW()),
    (9, 20, 1, 108000, 75000, 108000, NOW(), NOW()),
    (10, 15, 1, 78000, 48000, 78000, NOW(), NOW()),
    (10, 16, 1, 138000, 85000, 138000, NOW(), NOW()),
    (10, 5, 1, 105000, 65000, 105000, NOW(), NOW()),
    (10, 23, 2, 15000, 8000, 30000, NOW(), NOW()),
    (11, 2, 1, 115000, 70000, 115000, NOW(), NOW()),
    (11, 24, 1, 10000, 3500, 10000, NOW(), NOW()),
    (12, 11, 1, 92000, 55000, 92000, NOW(), NOW()),
    (12, 21, 1, 55000, 35000, 55000, NOW(), NOW()),
    (12, 24, 2, 8000, 3500, 16000, NOW(), NOW()),
    (13, 9, 1, 140000, 90000, 140000, NOW(), NOW()),
    (14, 11, 1, 95000, 55000, 95000, NOW(), NOW()),
    (15, 17, 1, 250000, 150000, 250000, NOW(), NOW()),
    (16, 2, 1, 112000, 70000, 112000, NOW(), NOW()),
    (17, 2, 1, 112000, 70000, 112000, NOW(), NOW()),
    (17, 3, 1, 72000, 45000, 72000, NOW(), NOW());

-- ORDERS
INSERT INTO orders (order_number, branch_id, cashier_id, customer_id, status, total, delivery_notes, assigned_at, created_at, updated_at)
VALUES
    ('ORD-000001', 1, 6, 1, 'completed', 248000, 'Deliver to 12 Admiralty Way', DATE_TRUNC('day', NOW()) - INTERVAL '8 days', DATE_TRUNC('day', NOW()) - INTERVAL '10 days', NOW()),
    ('ORD-000002', 1, NULL, 3, 'pending', 350000, 'Gift wrapping needed', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '2 days', NOW()),
    ('ORD-000003', 1, 7, 5, 'ready', 190000, 'Call before delivery', DATE_TRUNC('day', NOW()) - INTERVAL '1 day', DATE_TRUNC('day', NOW()) - INTERVAL '3 days', NOW()),
    ('ORD-000004', 2, 8, 4, 'completed', 95000, 'Deliver to Chevron Estate', DATE_TRUNC('day', NOW()) - INTERVAL '4 days', DATE_TRUNC('day', NOW()) - INTERVAL '6 days', NOW()),
    ('ORD-000005', 2, NULL, 7, 'pending', 250000, 'Deliver to Mwanza office', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '1 day', NOW()),
    ('ORD-000006', 3, 9, 9, 'assigned', 180000, 'Deliver to GRA Phase 4', DATE_TRUNC('day', NOW()) - INTERVAL '1 day', DATE_TRUNC('day', NOW()) - INTERVAL '2 days', NOW()),
    ('ORD-000007', 1, NULL, 10, 'cancelled', 138000, 'Customer cancelled', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '5 days', NOW());

UPDATE orders SET cancelled_at = created_at + INTERVAL '1 day' WHERE order_number = 'ORD-000007';
UPDATE orders SET completed_at = created_at + INTERVAL '2 days' WHERE order_number = 'ORD-000001';
UPDATE orders SET completed_at = created_at + INTERVAL '3 days' WHERE order_number = 'ORD-000004';

-- ORDER ITEMS
INSERT INTO order_items (order_id, product_id, quantity, unit_price, total, created_at, updated_at)
VALUES
    (1, 1, 1, 135000, 135000, NOW(), NOW()),
    (1, 10, 1, 113000, 113000, NOW(), NOW()),
    (2, 8, 1, 195000, 195000, NOW(), NOW()),
    (2, 9, 1, 145000, 145000, NOW(), NOW()),
    (2, 24, 1, 10000, 10000, NOW(), NOW()),
    (3, 11, 1, 95000, 95000, NOW(), NOW()),
    (3, 14, 1, 95000, 95000, NOW(), NOW()),
    (4, 11, 1, 95000, 95000, NOW(), NOW()),
    (5, 17, 1, 250000, 250000, NOW(), NOW()),
    (6, 3, 1, 72000, 72000, NOW(), NOW()),
    (6, 6, 1, 108000, 108000, NOW(), NOW()),
    (7, 16, 1, 138000, 138000, NOW(), NOW());

-- EXPENSES
INSERT INTO expenses (branch_id, user_id, category, amount, description, date, created_at, updated_at)
VALUES
    (1, 2, 'electricity', 45000, 'Monthly electricity bill January 2026', '2026-01-31', NOW(), NOW()),
    (1, 2, 'water', 8000, 'Water tanker delivery for cleaning', '2026-01-15', NOW(), NOW()),
    (1, 6, 'transport', 12000, 'Uber rides for product pickup', '2026-02-05', NOW(), NOW()),
    (1, 2, 'cleaning', 25000, 'Professional deep cleaning service', '2026-02-14', NOW(), NOW()),
    (1, 2, 'packaging', 35000, 'Premium gift boxes for Valentine season', '2026-02-01', NOW(), NOW()),
    (1, 7, 'other', 15000, 'Replacement of broken display shelf', '2026-02-20', NOW(), NOW()),
    (1, 2, 'electricity', 48000, 'February 2026 electricity bill', '2026-02-28', NOW(), NOW()),
    (1, 2, 'transport', 18000, 'Fuel for bulk order deliveries', '2026-03-05', NOW(), NOW()),
    (1, 2, 'rent', 200000, 'Monthly shop rent for Lekki store, March 2026', '2026-03-01', NOW(), NOW()),
    (2, 3, 'electricity', 52000, 'January electricity for VI branch', '2026-01-31', NOW(), NOW()),
    (2, 3, 'rent', 350000, 'Monthly shop rent for Victoria Island', '2026-01-05', NOW(), NOW()),
    (2, 8, 'transport', 8000, 'Taxi to collect restocked items', '2026-02-10', NOW(), NOW()),
    (3, 4, 'electricity', 35000, 'January electricity for Ikeja branch', '2026-01-31', NOW(), NOW()),
    (3, 4, 'water', 5000, 'Monthly water bill', '2026-01-31', NOW(), NOW()),
    (4, 5, 'electricity', 38000, 'January electricity for Mwanza', '2026-01-31', NOW(), NOW()),
    (4, 5, 'packaging', 28000, 'Custom-branded gift boxes', '2026-02-15', NOW(), NOW()),
    (5, 6, 'electricity', 32000, 'January electricity for PH branch', '2026-01-31', NOW(), NOW()),
    (5, 6, 'transport', 15000, 'Fuel for product pickup and delivery', '2026-02-10', NOW(), NOW()),
    (5, 6, 'other', 10000, 'Printing promotional flyers', '2026-03-01', NOW(), NOW());

-- CASHIER ACCOUNTS
INSERT INTO cashier_accounts (branch_id, cashier_id, date, expected_cash, actual_cash, difference, status, notes, created_at, updated_at)
VALUES
    (1, 6, CURRENT_DATE - 5, 330000, 330000, 0, 'balanced', 'All accounted for', NOW(), NOW()),
    (1, 6, CURRENT_DATE - 4, 98000, 98000, 0, 'balanced', NULL, NOW(), NOW()),
    (1, 6, CURRENT_DATE - 3, 155000, 150000, -5000, 'loss', 'Short by 5000', NOW(), NOW()),
    (1, 6, CURRENT_DATE - 2, 400000, 402000, 2000, 'surplus', 'Overage of 2000', NOW(), NOW()),
    (1, 6, CURRENT_DATE - 1, 125000, 125000, 0, 'balanced', 'Clean day', NOW(), NOW()),
    (1, 7, CURRENT_DATE - 5, 190000, 190000, 0, 'balanced', NULL, NOW(), NOW()),
    (1, 7, CURRENT_DATE - 3, 255000, 252000, -3000, 'loss', 'Minor shortage', NOW(), NOW()),
    (2, 8, CURRENT_DATE - 3, 90000, 90000, 0, 'balanced', NULL, NOW(), NOW()),
    (2, 8, CURRENT_DATE - 1, 250000, 250000, 0, 'balanced', 'Creed Aventus sale', NOW(), NOW()),
    (3, 9, CURRENT_DATE - 2, 137000, 137000, 0, 'balanced', NULL, NOW(), NOW());

-- DISCREPANCIES
INSERT INTO discrepancies (cashier_account_id, branch_id, cashier_id, reason, amount, description, created_at, updated_at)
VALUES
    (3, 1, 6, 'genuine_shortage', 5000, 'Unaccounted shortage. Likely incorrect change.', NOW(), NOW()),
    (4, 1, 6, 'surplus', 2000, 'Unexpected surplus. May be uncollected change.', NOW(), NOW()),
    (7, 1, 7, 'discount', 3000, 'Possibly unrecorded discount to regular customer.', NOW(), NOW());

-- OTP RECORDS
INSERT INTO otp_records (user_id, email, otp, type, expires_at, used, created_at, updated_at)
VALUES
    (1, 'admin@worldchoiceperfumes.co.tz', '123456', 'registration', NOW() + INTERVAL '10 minutes', TRUE, NOW() - INTERVAL '30 days', NOW()),
    (6, 'fatima@worldchoiceperfumes.co.tz', '654321', 'registration', NOW() + INTERVAL '10 minutes', TRUE, NOW() - INTERVAL '25 days', NOW());

-- AUDIT LOGS
INSERT INTO audit_logs (user_id, branch_id, action, auditable_type, auditable_id, new_values, ip_address, created_at, updated_at)
VALUES
    (1, NULL, 'user.login', 'App\\Models\\User', 1, '{"status": "active"}', '192.168.1.100', NOW() - INTERVAL '5 days', NOW()),
    (2, 1, 'stock.entry', 'App\\Models\\BranchStock', 1, '{"quantity": 25}', '192.168.1.101', NOW() - INTERVAL '30 days', NOW()),
    (6, 1, 'sale.created', 'App\\Models\\Sale', 11, '{"total": 125000}', '192.168.1.102', NOW() - INTERVAL '1 day', NOW()),
    (3, 2, 'cashier.approved', 'App\\Models\\User', 8, '{"status": "active"}', '192.168.2.100', NOW() - INTERVAL '20 days', NOW());

-- NOTIFICATIONS
INSERT INTO notifications (user_id, type, data, read_at, created_at, updated_at)
VALUES
    (2, 'cashier_registration', '{"message": "New cashier awaiting approval."}', NULL, NOW() - INTERVAL '2 days', NOW()),
    (3, 'cashier_registration', '{"message": "New cashier awaiting approval."}', NULL, NOW() - INTERVAL '1 day', NOW()),
    (2, 'new_order', '{"message": "New order ORD-000002 placed. Total: 350,000."}', NULL, NOW() - INTERVAL '2 days', NOW()),
    (7, 'order_assigned', '{"message": "Order ORD-000003 assigned to you."}', NULL, NOW() - INTERVAL '1 day', NOW()),
    (2, 'low_stock', '{"message": "Tom Ford Oud Wood running low (7 remaining)."}', NULL, NOW() - INTERVAL '3 days', NOW()),
    (2, 'discrepancy_alert', '{"message": "Cashier reported loss of 5,000."}', NULL, NOW() - INTERVAL '2 days', NOW());

-- ============================================================================
-- DONE! All tables created and seeded.
-- ============================================================================
