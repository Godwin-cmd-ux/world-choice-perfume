-- ============================================================================
-- WORLD CHOICE PERFUMES — Seed Data for Supabase
-- Run AFTER supabase_full_schema.sql
-- Passwords are bcrypt hashes of "password"
-- ============================================================================

-- ============================================================================
-- SUPER ADMIN (password: password)
-- ============================================================================
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

-- ============================================================================
-- BRANCHES
-- ============================================================================
INSERT INTO branches (name, address, latitude, longitude, is_active, created_at, updated_at)
VALUES
    ('Posta Main Store', '15 Posta Road, Dar es Salaam', -6.7924, 39.2083, TRUE, NOW(), NOW()),
    ('Kariakoo Branch', '22 Kariakoo Market Area, Dar es Salaam', -6.8161, 39.2793, TRUE, NOW(), NOW()),
    ('Arusha Branch', '45 CIDC Area, Arusha', -3.3869, 36.6830, TRUE, NOW(), NOW()),
    ('Mwanza Flagship', '7 Airport Road, Mwanza', -2.5164, 32.9175, TRUE, NOW(), NOW()),
    ('Zanzibar Branch', '12 Stone Town, Zanzibar', -6.1622, 39.1924, TRUE, NOW(), NOW());

-- ============================================================================
-- BRANCH ADMINS (password: password)
-- ============================================================================
INSERT INTO users (name, email, password, phone, role, status, branch_id, otp_verified, created_at, updated_at)
VALUES
    ('Hassan Mwangi', 'hassan@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255754000001', 'branch_admin', 'active', 1, TRUE, NOW(), NOW()),
    ('Amina Juma', 'amina@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255754000002', 'branch_admin', 'active', 2, TRUE, NOW(), NOW()),
    ('David Mushi', 'david@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255754000003', 'branch_admin', 'active', 3, TRUE, NOW(), NOW()),
    ('Grace Kimaro', 'grace@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255754000004', 'branch_admin', 'active', 4, TRUE, NOW(), NOW()),
    ('Emmanuel Shirima', 'emmanuel@worldchoiceperfumes.co.tz', '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255754000005', 'branch_admin', 'active', 5, TRUE, NOW(), NOW());

-- ============================================================================
-- CASHIERS (password: password) — some pending, some active
-- ============================================================================
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

-- ============================================================================
-- PRODUCTS
-- ============================================================================
INSERT INTO products (name, description, brand, category, is_active, created_at, updated_at)
VALUES
    -- Men's Fragrances
    ('Bleu de Chanel EDP', 'Aromatic Woody fragrance for men. Top notes of lemon, bergamot and mint.', 'Chanel', 'Men', TRUE, NOW(), NOW()),
    ('Dior Sauvage EDT', 'Fresh spicy fragrance. Notes of Calabrian bergamot and Sichuan pepper.', 'Dior', 'Men', TRUE, NOW(), NOW()),
    ('Versace Pour Homme', 'Mediterranean fragrance for men. Notes of neroli, citrus, and amber.', 'Versace', 'Men', TRUE, NOW(), NOW()),
    ('Acqua di Gio Profumo', 'Marine and aromatic fragrance. Deep and mysterious aquatic notes.', 'Giorgio Armani', 'Men', TRUE, NOW(), NOW()),
    ('YSL Y EDP', 'Aromatic fougere for men. Notes of apple, ginger, and sage.', 'Yves Saint Laurent', 'Men', TRUE, NOW(), NOW()),
    ('Paco Rabanne 1 Million', 'Spicy woody leather fragrance. Notes of grapefruit, mint, and rose.', 'Paco Rabanne', 'Men', TRUE, NOW(), NOW()),
    ('Jean Paul Gaultier Le Male', 'Oriental fougere. Notes of mint, lavender, and vanilla.', 'Jean Paul Gaultier', 'Men', TRUE, NOW(), NOW()),
    ('Tom Ford Noir', 'Amber woody fragrance. Notes of bergamot, violet, and cocoa.', 'Tom Ford', 'Men', TRUE, NOW(), NOW()),
    -- Women's Fragrances
    ('Chanel No. 5 EDP', 'The iconic aldehydic floral. Notes of ylang-ylang, rose, and sandalwood.', 'Chanel', 'Women', TRUE, NOW(), NOW()),
    ('Miss Dior Blooming Bouquet', 'Floral fragrance. Notes of peony, rose, and white musk.', 'Dior', 'Women', TRUE, NOW(), NOW()),
    ('YSL Black Opium', 'Sweet vanilla coffee fragrance. Notes of coffee, vanilla, and white flowers.', 'Yves Saint Laurent', 'Women', TRUE, NOW(), NOW()),
    ('Lancôme La Vie Est Belle', 'Sweet gourmand fragrance. Notes of iris, praline, and vanilla.', 'Lancôme', 'Women', TRUE, NOW(), NOW()),
    ('Versace Bright Crystal', 'Fresh floral fragrance. Notes of yuzu, pomegranate, and peony.', 'Versace', 'Women', TRUE, NOW(), NOW()),
    ('Gucci Bloom', 'White floral fragrance. Notes of tuberose, jasmine, and Rangoon creeper.', 'Gucci', 'Women', TRUE, NOW(), NOW()),
    ('Dolce & Gabbana Light Blue', 'Fresh citrus fragrance. Notes of Sicilian lemon, apple, and cedar.', 'Dolce & Gabbana', 'Women', TRUE, NOW(), NOW()),
    ('Chanel Coco Mademoiselle', 'Orange-patchouli-vanilla. Fresh, sexy, and elegant oriental.', 'Chanel', 'Women', TRUE, NOW(), NOW()),
    -- Unisex Fragrances
    ('Creed Aventus', 'Fruity woody fragrance. Notes of pineapple, birch, and musk.', 'Creed', 'Unisex', TRUE, NOW(), NOW()),
    ('Tom Ford Oud Wood', 'Woody aromatic. Notes of exotic rosewood, cardamom, and oud.', 'Tom Ford', 'Unisex', TRUE, NOW(), NOW()),
    ('Le Labo Santal 33', 'Woody aromatic. Notes of cardamom, iris, and sandalwood.', 'Le Labo', 'Unisex', TRUE, NOW(), NOW()),
    ('Byredo Gypsy Water', 'Woody aromatic. Notes of bergamot, juniper, and vanilla.', 'Byredo', 'Unisex', TRUE, NOW(), NOW()),
    -- Sets & Gift Packs
    ('Chanel Discovery Set', 'Set of 4 mini EDT bottles (5ml each): No.5, Bleu, Coco, Chance.', 'Chanel', 'Gift Set', TRUE, NOW(), NOW()),
    ('Dior Prestige Gift Box', 'Luxury gift box with Sauvage EDT 100ml + Dior Homme After Shave.', 'Dior', 'Gift Set', TRUE, NOW(), NOW()),
    -- Accessories
    ('Perfume Storage Box', 'Elegant leather-lined storage box for up to 12 bottles.', 'Generic', 'Accessories', TRUE, NOW(), NOW()),
    ('Travel Atomizer 10ml', 'Refillable gold-plated travel spray atomizer.', 'Generic', 'Accessories', TRUE, NOW(), NOW());

-- ============================================================================
-- BRANCH STOCK (different prices per branch)
-- ============================================================================

-- Branch 1: Lekki Main Store (all products in stock)
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

-- Branch 2: Victoria Island (partial stock)
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

-- Branch 3: Ikeja
INSERT INTO branch_stock (branch_id, product_id, quantity, buying_cost, selling_price, supplier, date_received, entered_by, created_at, updated_at)
VALUES
    (3, 2, 18, 70000, 112000, 'Dior Supply Chain', '2026-02-10', 4, NOW(), NOW()),
    (3, 3, 15, 45000, 72000, 'Versace Imports', '2026-02-10', 4, NOW(), NOW()),
    (3, 6, 20, 40000, 65000, 'Paco Rabanne Nig.', '2026-02-20', 4, NOW(), NOW()),
    (3, 10, 18, 60000, 95000, 'Dior Supply Chain', '2026-02-10', 4, NOW(), NOW()),
    (3, 13, 15, 42000, 68000, 'Versace Imports', '2026-02-20', 4, NOW(), NOW()),
    (3, 15, 12, 48000, 76000, 'D&G Imports', '2026-03-05', 4, NOW(), NOW()),
    (3, 23, 20, 8000, 14000, 'Generic Accessories Ltd', '2026-02-01', 4, NOW(), NOW());

-- Branch 4: Mwanza
INSERT INTO branch_stock (branch_id, product_id, quantity, buying_cost, selling_price, supplier, date_received, entered_by, created_at, updated_at)
VALUES
    (4, 1, 10, 85000, 142000, 'Chanel Distribution Tanzania', '2026-03-01', 5, NOW(), NOW()),
    (4, 5, 12, 65000, 108000, 'YSL Distributors', '2026-03-01', 5, NOW(), NOW()),
    (4, 9, 8, 90000, 150000, 'Chanel Distribution Tanzania', '2026-03-01', 5, NOW(), NOW()),
    (4, 11, 15, 55000, 96000, 'YSL Distributors', '2026-03-01', 5, NOW(), NOW()),
    (4, 16, 10, 85000, 142000, 'Chanel Distribution Tanzania', '2026-03-01', 5, NOW(), NOW()),
    (4, 20, 8, 75000, 125000, 'Byredo Nordics', '2026-03-10', 5, NOW(), NOW()),
    (4, 24, 25, 3500, 8200, 'Generic Accessories Ltd', '2026-03-01', 5, NOW(), NOW());

-- Branch 5: Zanzibar
INSERT INTO branch_stock (branch_id, product_id, quantity, buying_cost, selling_price, supplier, date_received, entered_by, created_at, updated_at)
VALUES
    (5, 3, 12, 45000, 73000, 'Versace Imports', '2026-03-10', 6, NOW(), NOW()),
    (5, 6, 15, 40000, 66000, 'Paco Rabanne Nig.', '2026-03-10', 6, NOW(), NOW()),
    (5, 7, 10, 55000, 88000, 'JPG Africa', '2026-03-10', 6, NOW(), NOW()),
    (5, 12, 12, 50000, 83000, 'Lancôme West Africa', '2026-03-15', 6, NOW(), NOW()),
    (5, 14, 8, 58000, 94000, 'Gucci Distributors', '2026-03-15', 6, NOW(), NOW()),
    (5, 19, 6, 95000, 158000, 'Le Labo Direct', '2026-03-20', 6, NOW(), NOW()),
    (5, 24, 40, 3500, 7800, 'Generic Accessories Ltd', '2026-03-10', 6, NOW(), NOW());

-- ============================================================================
-- CUSTOMERS
-- ============================================================================
INSERT INTO customers (name, phone, email, whatsapp, created_at, updated_at)
VALUES
    ('Abdul Nyerere', '+255712345678', 'abdul@email.com', '+255712345678', NOW(), NOW()),
    ('Fatima Omary', '+255723456789', 'fatima.c@email.com', '+255723456789', NOW(), NOW()),
    ('John Mwakasegela', '+255734567890', 'john@email.com', '+255734567890', NOW(), NOW()),
    ('Amina Hemed', '+255745678901', 'amina.h@email.com', '+255745678901', NOW(), NOW()),
    ('Peter Kimaro', '+255756789012', 'peter@email.com', '+255756789012', NOW(), NOW()),
    ('Rebecca Shirima', '+255767890123', 'rebecca@email.com', '+255767890123', NOW(), NOW()),
    ('Yusuf Kibona', '+255778901234', 'yusuf@email.com', '+255789012345', NOW(), NOW()),
    ('Neema Mwasaga', '+255789012345', 'neema@email.com', '+255790123456', NOW(), NOW()),
    ('Daniel Ndege', '+255790123456', 'daniel@email.com', '+255701234567', NOW(), NOW()),
    ('Happiness Mushi', '+255701234567', 'happiness@email.com', '+255711223344', NOW(), NOW());

-- ============================================================================
-- SALES (Branch 1 — Lekki, recent sales)
-- ============================================================================
INSERT INTO sales (sale_number, branch_id, cashier_id, customer_id, subtotal, discount, total, payment_status, notes, created_at, updated_at)
VALUES
    ('SALE-000001', 1, 6, 1, 233000, 0, 233000, 'paid', 'Walk-in customer, gift wrapped', DATE_TRUNC('day', NOW()) - INTERVAL '10 days', NOW()),
    ('SALE-000002', 1, 6, 2, 115000, 5000, 110000, 'paid', 'Regular customer discount', DATE_TRUNC('day', NOW()) - INTERVAL '9 days', NOW()),
    ('SALE-000003', 1, 7, 3, 310000, 10000, 300000, 'paid', 'Birthday purchase, large order', DATE_TRUNC('day', NOW()) - INTERVAL '8 days', NOW()),
    ('SALE-000004', 1, 6, NULL, 68000, 0, 68000, 'paid', 'Anonymous walk-in', DATE_TRUNC('day', NOW()) - INTERVAL '7 days', NOW()),
    ('SALE-000005', 1, 7, 4, 190000, 0, 190000, 'paid', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '6 days', NOW()),
    ('SALE-000006', 1, 6, 5, 345000, 15000, 330000, 'paid', 'VIP customer, corporate gift', DATE_TRUNC('day', NOW()) - INTERVAL '5 days', NOW()),
    ('SALE-000007', 1, 7, 6, 98000, 0, 98000, 'paid', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '4 days', NOW()),
    ('SALE-000008', 1, 6, NULL, 155000, 0, 155000, 'paid', 'Cash payment', DATE_TRUNC('day', NOW()) - INTERVAL '3 days', NOW()),
    ('SALE-000009', 1, 7, 7, 263000, 8000, 255000, 'paid', 'Anniversary gift set', DATE_TRUNC('day', NOW()) - INTERVAL '2 days', NOW()),
    ('SALE-000010', 1, 6, 8, 423000, 23000, 400000, 'paid', 'Bulk order, wholesale pricing', DATE_TRUNC('day', NOW()) - INTERVAL '1 day', NOW()),
    ('SALE-000011', 1, 6, NULL, 125000, 0, 125000, 'paid', 'Walk-in customer', NOW(), NOW()),
    ('SALE-000012', 1, 7, 9, 210000, 0, 210000, 'paid', 'Gift purchase', NOW(), NOW()),
    -- Branch 2 sales
    ('SALE-000013', 2, 8, 1, 140000, 0, 140000, 'paid', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '5 days', NOW()),
    ('SALE-000014', 2, 8, 4, 95000, 5000, 90000, 'paid', 'Repeat customer', DATE_TRUNC('day', NOW()) - INTERVAL '3 days', NOW()),
    ('SALE-000015', 2, 8, NULL, 250000, 0, 250000, 'paid', 'Creed Aventus purchased', DATE_TRUNC('day', NOW()) - INTERVAL '1 day', NOW()),
    -- Branch 3 sales
    ('SALE-000016', 3, 9, 5, 112000, 0, 112000, 'paid', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '4 days', NOW()),
    ('SALE-000017', 3, 9, 10, 184000, 0, 184000, 'paid', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '2 days', NOW());

-- ============================================================================
-- SALE ITEMS
-- ============================================================================
INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, unit_cost, total, created_at, updated_at)
VALUES
    -- SALE-000001: Bleu de Chanel (135000) + Dior Sauvage (115000) - with discount context
    (1, 1, 1, 135000, 85000, 135000, NOW(), NOW()),
    (1, 2, 1, 98000, 70000, 98000, NOW(), NOW()),
    -- SALE-000002: Dior Sauvage
    (2, 2, 1, 115000, 70000, 115000, NOW(), NOW()),
    -- SALE-000003: YSL Black Opium + Lancôme La Vie Est Belle + Miss Dior
    (3, 11, 1, 92000, 55000, 92000, NOW(), NOW()),
    (3, 12, 1, 82000, 50000, 82000, NOW(), NOW()),
    (3, 10, 1, 98000, 60000, 98000, NOW(), NOW()),
    (3, 13, 1, 38000, 42000, 38000, NOW(), NOW()),
    -- SALE-000004: Paco Rabanne 1 Million
    (4, 6, 1, 68000, 40000, 68000, NOW(), NOW()),
    -- SALE-000005: Chanel No 5 + Miss Dior
    (5, 9, 1, 98000, 90000, 98000, NOW(), NOW()),
    (5, 10, 1, 92000, 60000, 92000, NOW(), NOW()),
    -- SALE-000006: Tom Ford Noir + Tom Ford Oud Wood
    (6, 8, 1, 195000, 120000, 195000, NOW(), NOW()),
    (6, 18, 1, 210000, 130000, 210000, NOW(), NOW()),
    -- SALE-000007: Gucci Bloom
    (7, 14, 1, 95000, 58000, 95000, NOW(), NOW()),
    -- SALE-000008: Jean Paul Gaultier + Versace Pour Homme
    (8, 7, 1, 90000, 55000, 90000, NOW(), NOW()),
    (8, 3, 1, 65000, 45000, 65000, NOW(), NOW()),
    -- SALE-000009: Le Labo Santal + Byredo Gypsy Water
    (9, 19, 1, 155000, 95000, 155000, NOW(), NOW()),
    (9, 20, 1, 108000, 75000, 108000, NOW(), NOW()),
    -- SALE-000010: D&G Light Blue + Chanel Coco Mad + YSL Y
    (10, 15, 1, 78000, 48000, 78000, NOW(), NOW()),
    (10, 16, 1, 138000, 85000, 138000, NOW(), NOW()),
    (10, 5, 1, 105000, 65000, 105000, NOW(), NOW()),
    (10, 23, 2, 15000, 8000, 30000, NOW(), NOW()),
    -- SALE-000011: Dior Sauvage
    (11, 2, 1, 115000, 70000, 115000, NOW(), NOW()),
    (11, 24, 1, 10000, 3500, 10000, NOW(), NOW()),
    -- SALE-000012: YSL Black Opium + Chanel Discovery Set
    (12, 11, 1, 92000, 55000, 92000, NOW(), NOW()),
    (12, 21, 1, 55000, 35000, 55000, NOW(), NOW()),
    (12, 24, 2, 8000, 3500, 16000, NOW(), NOW()),
    -- SALE-000013 (Branch 2): Chanel No 5
    (13, 9, 1, 140000, 90000, 140000, NOW(), NOW()),
    -- SALE-000014 (Branch 2): YSL Black Opium
    (14, 11, 1, 95000, 55000, 95000, NOW(), NOW()),
    -- SALE-000015 (Branch 2): Creed Aventus
    (15, 17, 1, 250000, 150000, 250000, NOW(), NOW()),
    -- SALE-000016 (Branch 3): Dior Sauvage + Versace Bright Crystal
    (16, 2, 1, 112000, 70000, 112000, NOW(), NOW()),
    (16, 13, 1, 68000, 42000, 68000, NOW(), NOW()),
    -- SALE-000017 (Branch 3): Dior Sauvage + Versace Pour Homme
    (17, 2, 1, 112000, 70000, 112000, NOW(), NOW()),
    (17, 3, 1, 72000, 45000, 72000, NOW(), NOW());

-- Update branch stock to reflect sales
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 1;  -- SALE-000001
UPDATE branch_stock SET quantity = quantity - 2 WHERE branch_id = 1 AND product_id = 2;  -- SALE-000002, 000004
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 11; -- SALE-000003
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 12; -- SALE-000003
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 10; -- SALE-000003
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 13; -- SALE-000003
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 6;  -- SALE-000004
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 9;  -- SALE-000005
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 8;  -- SALE-000006
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 18; -- SALE-000006
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 14; -- SALE-000007
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 7;  -- SALE-000008
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 3;  -- SALE-000008
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 19; -- SALE-000009
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 20; -- SALE-000009
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 15; -- SALE-000010
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 16; -- SALE-000010
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 5;  -- SALE-000010
UPDATE branch_stock SET quantity = quantity - 2 WHERE branch_id = 1 AND product_id = 23; -- SALE-000010
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 24; -- SALE-000011
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 1 AND product_id = 21; -- SALE-000012
UPDATE branch_stock SET quantity = quantity - 2 WHERE branch_id = 1 AND product_id = 24; -- SALE-000012
-- Branch 2 stock reductions
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 2 AND product_id = 9;  -- SALE-000013
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 2 AND product_id = 11; -- SALE-000014
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 2 AND product_id = 17; -- SALE-000015
-- Branch 3 stock reductions
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 3 AND product_id = 2;  -- SALE-000016
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 3 AND product_id = 13; -- SALE-000016
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 3 AND product_id = 2;  -- SALE-000017 (second sale of product 2)
UPDATE branch_stock SET quantity = quantity - 1 WHERE branch_id = 3 AND product_id = 3;  -- SALE-000017

-- ============================================================================
-- STOCK MOVEMENTS (for the sales above)
-- ============================================================================
INSERT INTO stock_movements (branch_id, product_id, type, quantity, unit_cost, unit_price, reference_type, reference_id, performed_by, notes, created_at, updated_at)
VALUES
    -- Branch 1 movements
    (1, 1, 'sale', -1, 85000, 135000, 'App\\Models\\Sale', 1, 6, 'Sale SALE-000001', NOW(), NOW()),
    (1, 2, 'sale', -1, 70000, 98000, 'App\\Models\\Sale', 1, 6, 'Sale SALE-000001', NOW(), NOW()),
    (1, 2, 'sale', -1, 70000, 115000, 'App\\Models\\Sale', 2, 6, 'Sale SALE-000002', NOW(), NOW()),
    (1, 11, 'sale', -1, 55000, 92000, 'App\\Models\\Sale', 3, 7, 'Sale SALE-000003', NOW(), NOW()),
    (1, 12, 'sale', -1, 50000, 82000, 'App\\Models\\Sale', 3, 7, 'Sale SALE-000003', NOW(), NOW()),
    (1, 10, 'sale', -1, 60000, 98000, 'App\\Models\\Sale', 3, 7, 'Sale SALE-000003', NOW(), NOW()),
    (1, 13, 'sale', -1, 42000, 38000, 'App\\Models\\Sale', 3, 7, 'Sale SALE-000003', NOW(), NOW()),
    (1, 6, 'sale', -1, 40000, 68000, 'App\\Models\\Sale', 4, 6, 'Sale SALE-000004', NOW(), NOW()),
    (1, 9, 'sale', -1, 90000, 98000, 'App\\Models\\Sale', 5, 7, 'Sale SALE-000005', NOW(), NOW()),
    (1, 10, 'sale', -1, 60000, 92000, 'App\\Models\\Sale', 5, 7, 'Sale SALE-000005', NOW(), NOW()),
    (1, 8, 'sale', -1, 120000, 195000, 'App\\Models\\Sale', 6, 6, 'Sale SALE-000006', NOW(), NOW()),
    (1, 18, 'sale', -1, 130000, 210000, 'App\\Models\\Sale', 6, 6, 'Sale SALE-000006', NOW(), NOW()),
    (1, 14, 'sale', -1, 58000, 95000, 'App\\Models\\Sale', 7, 7, 'Sale SALE-000007', NOW(), NOW()),
    (1, 7, 'sale', -1, 55000, 90000, 'App\\Models\\Sale', 8, 6, 'Sale SALE-000008', NOW(), NOW()),
    (1, 3, 'sale', -1, 45000, 65000, 'App\\Models\\Sale', 8, 6, 'Sale SALE-000008', NOW(), NOW()),
    (1, 19, 'sale', -1, 95000, 155000, 'App\\Models\\Sale', 9, 7, 'Sale SALE-000009', NOW(), NOW()),
    (1, 20, 'sale', -1, 75000, 108000, 'App\\Models\\Sale', 9, 7, 'Sale SALE-000009', NOW(), NOW()),
    (1, 15, 'sale', -1, 48000, 78000, 'App\\Models\\Sale', 10, 6, 'Sale SALE-000010', NOW(), NOW()),
    (1, 16, 'sale', -1, 85000, 138000, 'App\\Models\\Sale', 10, 6, 'Sale SALE-000010', NOW(), NOW()),
    (1, 5, 'sale', -1, 65000, 105000, 'App\\Models\\Sale', 10, 6, 'Sale SALE-000010', NOW(), NOW()),
    (1, 23, 'sale', -2, 8000, 15000, 'App\\Models\\Sale', 10, 6, 'Sale SALE-000010', NOW(), NOW()),
    (1, 2, 'sale', -1, 70000, 115000, 'App\\Models\\Sale', 11, 6, 'Sale SALE-000011', NOW(), NOW()),
    (1, 24, 'sale', -1, 3500, 10000, 'App\\Models\\Sale', 11, 6, 'Sale SALE-000011', NOW(), NOW()),
    (1, 11, 'sale', -1, 55000, 92000, 'App\\Models\\Sale', 12, 7, 'Sale SALE-000012', NOW(), NOW()),
    (1, 21, 'sale', -1, 35000, 55000, 'App\\Models\\Sale', 12, 7, 'Sale SALE-000012', NOW(), NOW()),
    (1, 24, 'sale', -2, 3500, 8000, 'App\\Models\\Sale', 12, 7, 'Sale SALE-000012', NOW(), NOW()),
    -- Branch 2 movements
    (2, 9, 'sale', -1, 90000, 140000, 'App\\Models\\Sale', 13, 8, 'Sale SALE-000013', NOW(), NOW()),
    (2, 11, 'sale', -1, 55000, 95000, 'App\\Models\\Sale', 14, 8, 'Sale SALE-000014', NOW(), NOW()),
    (2, 17, 'sale', -1, 150000, 250000, 'App\\Models\\Sale', 15, 8, 'Sale SALE-000015', NOW(), NOW()),
    -- Branch 3 movements
    (3, 2, 'sale', -1, 70000, 112000, 'App\\Models\\Sale', 16, 9, 'Sale SALE-000016', NOW(), NOW()),
    (3, 13, 'sale', -1, 42000, 68000, 'App\\Models\\Sale', 16, 9, 'Sale SALE-000016', NOW(), NOW()),
    (3, 2, 'sale', -1, 70000, 112000, 'App\\Models\\Sale', 17, 9, 'Sale SALE-000017', NOW(), NOW()),
    (3, 3, 'sale', -1, 45000, 72000, 'App\\Models\\Sale', 17, 9, 'Sale SALE-000017', NOW(), NOW());

-- ============================================================================
-- ORDERS (Customer online orders)
-- ============================================================================
INSERT INTO orders (order_number, branch_id, cashier_id, customer_id, status, total, delivery_notes, assigned_at, created_at, updated_at)
VALUES
    ('ORD-000001', 1, 6, 1, 'completed', 248000, 'Deliver to 12 Admiralty Way, Lekki', DATE_TRUNC('day', NOW()) - INTERVAL '8 days', DATE_TRUNC('day', NOW()) - INTERVAL '10 days', NOW()),
    ('ORD-000002', 1, NULL, 3, 'pending', 350000, 'Gift wrapping needed, deliver to Victoria Island office', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '2 days', NOW()),
    ('ORD-000003', 1, 7, 5, 'ready', 190000, 'Call before delivery', DATE_TRUNC('day', NOW()) - INTERVAL '1 day', DATE_TRUNC('day', NOW()) - INTERVAL '3 days', NOW()),
    ('ORD-000004', 2, 8, 4, 'completed', 95000, 'Deliver to Chevron Estate, Lekki', DATE_TRUNC('day', NOW()) - INTERVAL '4 days', DATE_TRUNC('day', NOW()) - INTERVAL '6 days', NOW()),
    ('ORD-000005', 2, NULL, 7, 'pending', 250000, 'Deliver to Mwanza office', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '1 day', NOW()),
    ('ORD-000006', 3, 9, 9, 'assigned', 180000, 'Deliver to Stone Town, Zanzibar', DATE_TRUNC('day', NOW()) - INTERVAL '1 day', DATE_TRUNC('day', NOW()) - INTERVAL '2 days', NOW()),
    ('ORD-000007', 1, NULL, 10, 'cancelled', 138000, 'Customer cancelled', NULL, DATE_TRUNC('day', NOW()) - INTERVAL '5 days', NOW());

-- Update cancelled order timestamp
UPDATE orders SET cancelled_at = created_at + INTERVAL '1 day' WHERE order_number = 'ORD-000007';

-- Update completed orders
UPDATE orders SET completed_at = created_at + INTERVAL '2 days' WHERE order_number = 'ORD-000001';
UPDATE orders SET completed_at = created_at + INTERVAL '3 days' WHERE order_number = 'ORD-000004';

-- ============================================================================
-- ORDER ITEMS
-- ============================================================================
INSERT INTO order_items (order_id, product_id, quantity, unit_price, total, created_at, updated_at)
VALUES
    -- ORD-000001: Bleu de Chanel + Miss Dior
    (1, 1, 1, 135000, 135000, NOW(), NOW()),
    (1, 10, 1, 113000, 113000, NOW(), NOW()),
    -- ORD-000002: Tom Ford Noir + Chanel No 5 + Dior Sauvage
    (2, 8, 1, 195000, 195000, NOW(), NOW()),
    (2, 9, 1, 145000, 145000, NOW(), NOW()),
    (2, 24, 1, 10000, 10000, NOW(), NOW()),
    -- ORD-000003: YSL Black Opium + Gucci Bloom
    (3, 11, 1, 95000, 95000, NOW(), NOW()),
    (3, 14, 1, 95000, 95000, NOW(), NOW()),
    -- ORD-000004: YSL Black Opium
    (4, 11, 1, 95000, 95000, NOW(), NOW()),
    -- ORD-000005: Creed Aventus
    (5, 17, 1, 250000, 250000, NOW(), NOW()),
    -- ORD-000006: Versace Pour Homme + Paco Rabanne
    (6, 3, 1, 72000, 72000, NOW(), NOW()),
    (6, 6, 1, 108000, 108000, NOW(), NOW()),
    -- ORD-000007: Chanel Coco Mademoiselle (cancelled)
    (7, 16, 1, 138000, 138000, NOW(), NOW());

-- ============================================================================
-- EXPENSES
-- ============================================================================
INSERT INTO expenses (branch_id, user_id, category, amount, description, date, created_at, updated_at)
VALUES
    -- Branch 1 expenses
    (1, 2, 'electricity', 45000, 'Monthly electricity bill for January 2026. PHCN prepaid meter top-up for the entire store including showroom, storage room and back office.', '2026-01-31', NOW(), NOW()),
    (1, 2, 'water', 8000, 'Water tanker delivery for store cleaning and staff use. Two 1000L deliveries.', '2026-01-15', NOW(), NOW()),
    (1, 6, 'transport', 12000, 'Uber rides for product pickup from supplier warehouse at Kariakoo. Two round trips.', '2026-02-05', NOW(), NOW()),
    (1, 2, 'cleaning', 25000, 'Professional deep cleaning service for showroom display cases and storage area. Includes glass polish for perfume display cabinets.', '2026-02-14', NOW(), NOW()),
    (1, 2, 'packaging', 35000, 'Premium gift boxes (50 units at 400 each), tissue paper, gift bags, and wrapping materials for Valentine season.', '2026-02-01', NOW(), NOW()),
    (1, 7, 'other', 15000, 'Replacement of broken display shelf light bulb, new price tag holders, and display stand maintenance.', '2026-02-20', NOW(), NOW()),
    (1, 2, 'electricity', 48000, 'February 2026 electricity bill. Slight increase due to extended AC usage during hot weather.', '2026-02-28', NOW(), NOW()),
    (1, 2, 'transport', 18000, 'Fuel for company van used to deliver bulk orders to three customers in different parts of Dar es Salaam.', '2026-03-05', NOW(), NOW()),
    (1, 2, 'cleaning', 25000, 'Monthly cleaning service for March.', '2026-03-14', NOW(), NOW()),
    (1, 6, 'packaging', 20000, 'Restocking packaging materials: 30 gift boxes, 50 ribbons, 100 tissue papers.', '2026-03-10', NOW(), NOW()),
    -- Branch 2 expenses
    (2, 3, 'electricity', 52000, 'January electricity for VI branch. Higher due to larger showroom with more display cases.', '2026-01-31', NOW(), NOW()),
    (2, 3, 'rent', 350000, 'Monthly shop rent for Victoria Island prime location, January 2026.', '2026-01-05', NOW(), NOW()),
    (2, 8, 'transport', 8000, 'Taxi fare to collect restocked items from clearing agent at Apapa port.', '2026-02-10', NOW(), NOW()),
    (2, 3, 'cleaning', 30000, 'Bi-weekly professional cleaning for February. Includes glass and marble floor treatment.', '2026-02-15', NOW(), NOW()),
    -- Branch 3 expenses
    (3, 4, 'electricity', 35000, 'January electricity for Ikeja branch.', '2026-01-31', NOW(), NOW()),
    (3, 4, 'water', 5000, 'Monthly water bill, Ikeja branch.', '2026-01-31', NOW(), NOW()),
    (3, 9, 'transport', 10000, 'Transportation cost for inventory audit supplies pickup.', '2026-02-20', NOW(), NOW()),
    (3, 4, 'other', 45000, 'Annual CCTV camera maintenance and servicing by SecurityTech Tanzania.', '2026-03-01', NOW(), NOW()),
    -- Branch 4 expenses
    (4, 5, 'electricity', 38000, 'January electricity for Mwanza branch.', '2026-01-31', NOW(), NOW()),
    (4, 5, 'packaging', 28000, 'Custom-branded gift boxes (40 units) and perfumed tissue paper for Mwanza corporate clients.', '2026-02-15', NOW(), NOW()),
    (4, 5, 'cleaning', 20000, 'Monthly cleaning for February, Mwanza branch.', '2026-02-28', NOW(), NOW()),
    -- Branch 5 expenses
    (5, 6, 'electricity', 32000, 'January electricity for Zanzibar branch.', '2026-01-31', NOW(), NOW()),
    (5, 6, 'transport', 15000, 'Fuel and toll for product pickup from PH warehouse and delivery to two customers.', '2026-02-10', NOW(), NOW()),
    (5, 6, 'other', 10000, 'Printing of new promotional flyers and business cards for the branch.', '2026-03-01', NOW(), NOW());

-- ============================================================================
-- CASHIER ACCOUNTS (daily accountability)
-- ============================================================================
INSERT INTO cashier_accounts (branch_id, cashier_id, date, expected_cash, actual_cash, difference, status, notes, created_at, updated_at)
VALUES
    -- Fatima (cashier_id=6, branch 1) — last 5 days
    (1, 6, CURRENT_DATE - 5, 330000, 330000, 0, 'balanced', 'All transactions accounted for.', NOW(), NOW()),
    (1, 6, CURRENT_DATE - 4, 98000, 98000, 0, 'balanced', NULL, NOW(), NOW()),
    (1, 6, CURRENT_DATE - 3, 155000, 150000, -5000, 'loss', 'Short by 5000. Investigating — possible wrong change given to customer.', NOW(), NOW()),
    (1, 6, CURRENT_DATE - 2, 400000, 402000, 2000, 'surplus', 'Overage of 2000. Customer may have paid extra. No record found.', NOW(), NOW()),
    (1, 6, CURRENT_DATE - 1, 125000, 125000, 0, 'balanced', 'Clean day.', NOW(), NOW()),
    -- Blessing (cashier_id=7, branch 1) — last 5 days
    (1, 7, CURRENT_DATE - 5, 190000, 190000, 0, 'balanced', NULL, NOW(), NOW()),
    (1, 7, CURRENT_DATE - 4, 98000, 98000, 0, 'balanced', NULL, NOW(), NOW()),
    (1, 7, CURRENT_DATE - 3, 255000, 252000, -3000, 'loss', 'Minor shortage. May be unrecorded discount.', NOW(), NOW()),
    (1, 7, CURRENT_DATE - 2, 210000, 210000, 0, 'balanced', NULL, NOW(), NOW()),
    (1, 7, CURRENT_DATE - 1, 0, NULL, NULL, 'pending', 'Not yet submitted.', NOW(), NOW()),
    -- Ibrahim (cashier_id=8, branch 2)
    (2, 8, CURRENT_DATE - 3, 90000, 90000, 0, 'balanced', NULL, NOW(), NOW()),
    (2, 8, CURRENT_DATE - 1, 250000, 250000, 0, 'balanced', 'Creed Aventus cash sale.', NOW(), NOW()),
    -- Grace (cashier_id=9, branch 3)
    (3, 9, CURRENT_DATE - 2, 137000, 137000, 0, 'balanced', NULL, NOW(), NOW());

-- ============================================================================
-- DISCREPANCIES (for cashier accounts with losses/surpluses)
-- ============================================================================
INSERT INTO discrepancies (cashier_account_id, branch_id, cashier_id, reason, amount, description, created_at, updated_at)
VALUES
    -- Fatima's 5000 loss on CURRENT_DATE - 3
    (3, 1, 6, 'genuine_shortage', 5000, 'Unaccounted shortage of 5000 naira. No pending refunds or unrecorded expenses found. Likely incorrect change given to customer during rush hour.', NOW(), NOW()),
    -- Fatima's 2000 surplus on CURRENT_DATE - 2
    (4, 1, 6, 'surplus', 2000, 'Unexpected surplus of 2000 naira. No overpayment found in transaction logs. May be from a customer who did not collect change.', NOW(), NOW()),
    -- Blessing's 3000 loss on CURRENT_DATE - 3
    (8, 1, 7, 'discount', 3000, 'Possibly unrecorded 5% discount given to regular customer without supervisor approval. To be verified with CCTV footage.', NOW(), NOW());

-- ============================================================================
-- OTP RECORDS (sample — most should be expired)
-- ============================================================================
INSERT INTO otp_records (user_id, email, otp, type, expires_at, used, created_at, updated_at)
VALUES
    (1, 'admin@worldchoiceperfumes.co.tz', '123456', 'registration', NOW() + INTERVAL '10 minutes', TRUE, NOW() - INTERVAL '30 days', NOW()),
    (6, 'fatima@worldchoiceperfumes.co.tz', '654321', 'registration', NOW() + INTERVAL '10 minutes', TRUE, NOW() - INTERVAL '25 days', NOW()),
    (7, 'blessing@worldchoiceperfumes.co.tz', '112233', 'registration', NOW() + INTERVAL '10 minutes', TRUE, NOW() - INTERVAL '25 days', NOW());

-- ============================================================================
-- AUDIT LOGS
-- ============================================================================
INSERT INTO audit_logs (user_id, branch_id, action, auditable_type, auditable_id, new_values, ip_address, created_at, updated_at)
VALUES
    (1, NULL, 'user.login', 'App\\Models\\User', 1, '{"status": "active"}', '192.168.1.100', NOW() - INTERVAL '5 days', NOW()),
    (2, 1, 'stock.entry', 'App\\Models\\BranchStock', 1, '{"quantity": 25, "buying_cost": 85000, "selling_price": 135000}', '192.168.1.101', NOW() - INTERVAL '30 days', NOW()),
    (6, 1, 'sale.created', 'App\\Models\\Sale', 11, '{"total": 125000, "payment_status": "paid"}', '192.168.1.102', NOW() - INTERVAL '1 day', NOW()),
    (7, 1, 'sale.created', 'App\\Models\\Sale', 12, '{"total": 210000, "payment_status": "paid"}', '192.168.1.103', NOW(), NOW()),
    (3, 2, 'cashier.approved', 'App\\Models\\User', 8, '{"status": "active"}', '192.168.2.100', NOW() - INTERVAL '20 days', NOW()),
    (4, 3, 'order.picked', 'App\\Models\\Order', 6, '{"status": "assigned", "cashier_id": 9}', '192.168.3.100', NOW() - INTERVAL '1 day', NOW()),
    (1, NULL, 'branch.created', 'App\\Models\\Branch', 5, '{"name": "Zanzibar Branch", "address": "12 Stone Town, Zanzibar"}', '192.168.1.100', NOW() - INTERVAL '35 days', NOW()),
    (6, 1, 'expense.created', 'App\\Models\\Expense', 8, '{"category": "transport", "amount": 18000}', '192.168.1.102', NOW() - INTERVAL '15 days', NOW());

-- ============================================================================
-- NOTIFICATIONS
-- ============================================================================
INSERT INTO notifications (user_id, type, data, read_at, created_at, updated_at)
VALUES
    -- New cashier registration notifications for branch admins
    (2, 'cashier_registration', '{"message": "New cashier Pending Cashier One has registered and is awaiting approval.", "user_id": 12}', NULL, NOW() - INTERVAL '2 days', NOW()),
    (3, 'cashier_registration', '{"message": "New cashier Pending Cashier Two has registered and is awaiting approval.", "user_id": 13}', NULL, NOW() - INTERVAL '1 day', NOW()),
    -- Order notifications
    (2, 'new_order', '{"message": "New order ORD-000002 has been placed. Total: 350,000. Action required.", "order_id": 2}', NULL, NOW() - INTERVAL '2 days', NOW()),
    (7, 'order_assigned', '{"message": "You have been assigned order ORD-000003. Please prepare and deliver.", "order_id": 3}', NULL, NOW() - INTERVAL '1 day', NOW()),
    (7, 'order_ready', '{"message": "Order ORD-000003 is ready for delivery.", "order_id": 3}', NULL, NOW() - INTERVAL '1 day', NOW()),
    -- Stock alerts
    (2, 'low_stock', '{"message": "Product Tom Ford Oud Wood is running low at Lekki Main Store (7 remaining).", "product_id": 18, "branch_id": 1}', NULL, NOW() - INTERVAL '3 days', NOW()),
    (2, 'low_stock', '{"message": "Product Creed Aventus is running low at Lekki Main Store (9 remaining).", "product_id": 17, "branch_id": 1}', NULL, NOW() - INTERVAL '3 days', NOW()),
    -- Discrepancy alerts
    (2, 'discrepancy_alert', '{"message": "Cashier Fatima Yusuf reported a loss of 5,000 on " . CURRENT_DATE - 3 || ".", "cashier_account_id": 3}', NULL, NOW() - INTERVAL '2 days', NOW()),
    -- Read notifications
    (2, 'cashier_approved', '{"message": "Cashier Fatima Yusuf has been approved.", "user_id": 6}', NOW() - INTERVAL '20 days', NOW() - INTERVAL '25 days', NOW()),
    (5, 'order_completed', '{"message": "Order ORD-000004 has been marked as completed.", "order_id": 4}', NOW() - INTERVAL '3 days', NOW() - INTERVAL '4 days', NOW());

-- ============================================================================
-- DONE! All seed data inserted.
-- ============================================================================
