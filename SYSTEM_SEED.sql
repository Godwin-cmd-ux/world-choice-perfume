-- ============================================================================
-- WORLD CHOICE PERFUMES — SYSTEM_SEED.sql
-- Complete snapshot of the live data. Run AFTER SYSTEM_SCHEMA.sql.
--
-- Source of the data: one PostgREST GET per table with no limit,
-- all rows and all columns:
--
--   GET {SUPABASE_URL}/rest/v1/<table>?select=*
--     apikey: <service_role_key>
--     Authorization: Bearer <service_role_key>
--
-- (each call paged with the HTTP Range header: Range: 0-999, 1000-1999,
--  ... until fewer than 1000 rows came back, so nothing was left behind)
--
-- Tables pulled (43):
-- branches, users, password_reset_tokens, sessions, cache, cache_locks, jobs, job_batches, failed_jobs, products, product_images, brands, company_settings, customers, bottle_stock, bottle_stock_movements, bottle_accessories, bottle_accessories_movements, oil_fragrance_stock, oil_fragrance_movements, branch_stock, branch_stock_varieties, stock_movements, sales, sale_items, orders, order_items, order_notes, expenses, cashier_accounts, discrepancies, otp_records, audit_logs, notifications, admin_notifications, inquiries, news_posts, stock_transfers, stock_transfer_items, info_emails, info_email_replies, info_email_attachments, migrations
-- ============================================================================

BEGIN;

-- branches (4 rows)
INSERT INTO public.branches (id, name, address, latitude, longitude, profile_picture, is_active, created_at, updated_at, category) VALUES
(8, 'Kinondoni branch', 'Dar es salaam kinondoni', -6.792667, 39.259804, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789480035/branches/logo%20wcp.png_1789480034.png', TRUE, '2026-08-24T19:38:30', '2026-09-22T09:16:16', 'autonomous'),
(11, 'Kinondoni branch', NULL, NULL, NULL, NULL, FALSE, '2026-09-30T08:25:09', '2026-09-30T16:13:18', 'autonomous'),
(10, 'Head Quarters-Mikocheni', 'Dar es salaam', -6.827, 39.2675, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789480010/branches/logo%20wcp.png_1789480010.png', TRUE, '2026-08-31T04:25:09', '2026-09-15T13:46:51', 'products_based'),
(9, 'Head Quarters-Mikocheni', 'dodoma', -6.827, 39.2675, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789479982/branches/logo%20wcp.png_1789479982.png', TRUE, '2026-08-30T15:23:33', '2026-09-30T08:25:18', 'products_based');

-- users (41 rows)
INSERT INTO public.users (id, name, email, email_verified_at, password, phone, role, status, branch_id, profile_picture, company_secret_code, otp_verified, remember_token, created_at, updated_at) VALUES
(35, 'FRANK GODWIN', 'graphics@worldchoiceperfume.com', NULL, '$2y$12$sx173GNMa4FJq/M8hYyeCuS0iSmd1drLbQgaI/By8k4gYWN0TM9ri', '0616675940', 'graphic_designer', 'active', NULL, NULL, NULL, TRUE, NULL, '2026-09-12T19:27:35', '2026-09-12T19:27:35'),
(1, 'Super Admin', 'admin@worldchoiceperfumes.co.tz', NULL, '$2y$12$ACnYuSz49I2.ELdmbYyspOF9xLH7IBOjGzP833zXZVMylUZH1RMKu', NULL, 'super_admin', 'active', NULL, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789545590/profiles/test.jpg_1789545589.jpg', NULL, TRUE, NULL, '2026-08-22T05:23:02.148567', '2026-09-16T07:59:51'),
(24, 'Gideon Msuya', 'gideonmsuya143@gmail.com', NULL, '$2y$12$tKaID7I8IrUvm8jd40PPPet6Vaf.4ZIN.pD9mIeVIGNCzf7XYuinC', '0682601154', 'branch_admin', 'rejected', 10, NULL, NULL, FALSE, NULL, '2026-08-31T04:33:02', '2026-08-31T04:33:02'),
(19, 'FRANK GODWIN', 'godwinfranklin419@gmail.com', NULL, '$2y$12$4c5q6IOzuTu7oLHmOD3dKuNzNhVkOPgkcJLrio6OTqS/x4.p5iTSq', '0616675940', 'cashier', 'active', 8, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1787870221/profiles/status.jpg_1787870218.jpg', NULL, TRUE, NULL, '2026-08-24T22:16:42', '2026-08-27T22:37:02'),
(31, 'Gideon Msuya', 'gideonmsuya140@gmail.com', NULL, '$2y$12$Ix20p8eHwmRgA7gNy5AMDeCe4nuiEVS3pVtLGszEUC38zz34wywD.', '0682601154', 'customer_care', 'active', 8, NULL, NULL, TRUE, NULL, '2026-09-07T03:48:00', '2026-09-07T03:48:00'),
(23, 'Gideon', 'gideonmsuya144@gmail.com', NULL, '$2y$12$oMMao6wIh0kekffOLio/c.XPWik6X0HbxFZ7aTbxJq7O3zdNEwALO', '0682601154', 'cashier', 'active', 8, NULL, NULL, TRUE, NULL, '2026-08-30T15:29:16', '2026-08-30T15:29:16'),
(18, 'FRANK GODWIN', 'worldchoiceperfumes@gmail.com', NULL, '$2y$12$EuTfxrPr/El.8zf5BzGT1OSv8zb/qcIwsjWHmstomqdSmKsgYnSrW', '0616675940', 'branch_admin', 'active', 8, NULL, NULL, FALSE, NULL, '2026-08-24T19:38:32', '2026-08-30T23:04:54'),
(34, 'FRANK GODWIN', 'seller@worldchoiceperfume.com', NULL, '$2y$12$zir8I3zaonDcssY0mfl3xO2uurxoUmV3XccblNJ7HXwIVQ0iGv2mq', '0616675940', 'seller', 'active', 8, NULL, NULL, TRUE, NULL, '2026-09-11T19:55:45', '2026-09-11T19:55:45'),
(33, 'Gideon Msuya', 'gideonmsuya142@gmail.com', NULL, '$2y$12$arQd8gY7Cxi1lCHJpYQe9eDBu4YCQ.v99TFC2HsioWpsNB0rMAcOq', '0682601154', 'seller', 'active', 8, NULL, NULL, TRUE, NULL, '2026-09-07T03:56:45', '2026-09-07T03:56:45'),
(29, 'FRANK GODWIN', 'admin@worldchoiceperfume.com', NULL, '$2y$12$yKAL5DCpF00cR/M/Qt6vWuMc2Naet.eAAJIgrBI6ca.RTpVC09Xv.', '0616675940', 'stock_manager', 'active', 8, NULL, NULL, TRUE, NULL, '2026-09-06T06:03:58', '2026-09-07T22:38:02'),
(32, 'Gideon Msuya', 'gideonmsuya141@gmail.com', NULL, '$2y$12$zgQviPlKIg8X9dMNYHDyYOMHMJ7Z1bQgSPnpK2i43nTukPHM/2F4W', '0682601154', 'stock_manager', 'active', 8, NULL, NULL, TRUE, NULL, '2026-09-07T03:51:34', '2026-09-07T03:51:34'),
(20, 'COSMA COSMA VICTORINI', 'godwinfranklin418@gmail.com', NULL, '$2y$12$qA56FchG0SlufxU8MLqGEu.d.FWyWbX4WF7ApVHMQHGmhgytavF4m', '0616675940', 'cashier', 'active', 8, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1787871867/profiles/mycapture.jpg_1787871865.jpg', NULL, TRUE, NULL, '2026-08-27T23:00:29', '2026-09-10T04:13:31'),
(21, 'Management', 'gideonmsuya146@gmail.com', NULL, '$2y$12$QBOHsRy34L27f.l/w8shb.JY4mQanZWfZS6z1hZheAepmAvaCmKGe', '0682601154', 'super_admin', 'active', NULL, NULL, NULL, TRUE, NULL, '2026-08-30T15:08:24', '2026-09-11T15:15:11'),
(37, 'Gideon Msuya', 'gideonmsuya148@gmail.com', NULL, '$2y$12$HdhQvMk/zjUXnNDNrMxD8uH4eXqDatKaw7IzXag/nHE0g5rC3Y0Am', '0682601154', 'stock_manager', 'active', 10, NULL, NULL, TRUE, NULL, '2026-09-13T12:56:35', '2026-09-13T12:56:35'),
(36, 'Gideon Msuya', 'gideonmsuya147@gmail.com', NULL, '$2y$12$g9d/zZSxlIMbj5vGz0eK0elIsa1DartYGUMDaA1UyM584DpLauGAa', '0682601154', 'stock_manager', 'active', 9, NULL, NULL, TRUE, NULL, '2026-09-13T12:41:00', '2026-09-13T12:41:00'),
(38, 'Gideon Msuya', 'gideonmsuya149@gmail.com', NULL, '$2y$12$MFoHWLG48gcHfmnId2Iw3OC0is/hpWqVbZSmrC0Wp2cevmuA7SAhK', '0682601154', 'graphic_designer', 'active', NULL, NULL, NULL, TRUE, NULL, '2026-09-13T13:03:18', '2026-09-13T13:03:18'),
(22, 'Gideon', 'gideonmsuya145@gmail.com', NULL, '$2y$12$iRIfR2pDHaKp3y7Ibfyrw.LPrxT0tzarHIypll3aCco2t1dKSAyHS', '0682601154', 'branch_admin', 'active', 9, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789313365/profiles/imigation.png_1789313365.png', NULL, TRUE, NULL, '2026-08-30T15:23:35', '2026-09-13T15:29:26'),
(2, 'Hassan Mwangi', 'hassan@worldchoiceperfumes.co.tz', NULL, '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255754000001', 'branch_admin', 'active', NULL, NULL, NULL, TRUE, NULL, '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(40, 'Gideon Msuya', 'gideonmsuya151@gmail.com', NULL, '$2y$12$iJ9qoLRekDo9Zla6YZS./ubPo3ktpIpRSPfCAULdF5eNOy3JnET7y', '0682601154', 'seller', 'active', 10, NULL, NULL, TRUE, NULL, '2026-09-13T15:50:36', '2026-09-13T15:50:36'),
(42, 'Gideon Msuya', 'gideonmsuya153@gmail.com', NULL, '$2y$12$ZX92sJkRdkEAP8y8xj27BeptfMJDQrobQMUnTxf.0UYgMf/5mXubq', '0682601154', 'cashier', 'active', 10, NULL, NULL, TRUE, NULL, '2026-09-13T15:56:58', '2026-09-13T15:56:58'),
(41, 'Gideon Msuya', 'gideonmsuya152@gmail.com', NULL, '$2y$12$48Vte8ngxbZhT/q.8ccGFuaVYbGpanQ3dxc0BfO8y58dQY9J732qC', '0682601154', 'branch_admin', 'active', 10, NULL, NULL, TRUE, NULL, '2026-09-13T15:55:20', '2026-09-13T15:55:20'),
(44, 'Gideon Msuya', 'gideonmsuya155@gmail.com', NULL, '$2y$12$LOxGaHL5eb79Z.QIofCviejVv4cWvzO1itZH99MCgaVcpo2gCQX0q', '0682601154', 'branch_admin', 'active', 9, NULL, NULL, TRUE, NULL, '2026-09-13T16:00:40', '2026-09-13T16:00:40'),
(30, 'FRANK GODWIN', 'godwinfranklin415@gmail.com', NULL, '$2y$12$ACnYuSz49I2.ELdmbYyspOF9xLH7IBOjGzP833zXZVMylUZH1RMKu', '0616675940', 'customer_care', 'active', 8, NULL, NULL, TRUE, NULL, '2026-09-07T03:25:29', '2026-09-07T03:25:29'),
(39, 'Gideon Msuya', 'gideonmsuya150@gmail.com', NULL, '$2y$12$ACnYuSz49I2.ELdmbYyspOF9xLH7IBOjGzP833zXZVMylUZH1RMKu', '0682601154', 'customer_care', 'active', 10, NULL, NULL, TRUE, NULL, '2026-09-13T13:13:01', '2026-09-13T13:13:01'),
(46, 'Gideon Msuya', 'gideonmsuya157@gmail.com', NULL, '$2y$12$Y4UuoG.9b9L/Hw8moWDyEu9FAJ2KqiCTdhVrrhqcimp1uPcY1M3bC', '0682601154', 'customer_care', 'approved', 9, NULL, NULL, TRUE, NULL, '2026-09-13T16:03:32', '2026-09-13T16:03:32'),
(7, 'Fatima Omari', 'fatima@worldchoiceperfumes.co.tz', NULL, '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000001', 'cashier', 'active', NULL, NULL, NULL, TRUE, NULL, '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(8, 'Blessing Mwakasege', 'blessing@worldchoiceperfumes.co.tz', NULL, '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000002', 'cashier', 'active', NULL, NULL, NULL, TRUE, NULL, '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(13, 'Pending Cashier One', 'pending1@worldchoiceperfumes.co.tz', NULL, '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000007', 'cashier', 'approved', NULL, NULL, NULL, TRUE, NULL, '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(3, 'Amina Juma', 'amina@worldchoiceperfumes.co.tz', NULL, '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255754000002', 'branch_admin', 'active', NULL, NULL, NULL, TRUE, NULL, '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(9, 'Ibrahim Kibona', 'ibrahim@worldchoiceperfumes.co.tz', NULL, '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000003', 'cashier', 'active', NULL, NULL, NULL, TRUE, NULL, '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(14, 'Pending Cashier Two', 'pending2@worldchoiceperfumes.co.tz', NULL, '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000008', 'cashier', 'approved', NULL, NULL, NULL, TRUE, NULL, '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(4, 'David Mushi', 'david@worldchoiceperfumes.co.tz', NULL, '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255754000003', 'branch_admin', 'active', NULL, NULL, NULL, TRUE, NULL, '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(10, 'Grace Mwasaga', 'grace@worldchoiceperfumes.com', NULL, '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000004', 'cashier', 'active', NULL, NULL, NULL, TRUE, NULL, '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(15, 'Rejected Cashier', 'rejected@worldchoiceperfumes.co.tz', NULL, '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000009', 'cashier', 'rejected', NULL, NULL, NULL, TRUE, NULL, '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(5, 'Grace Kimaro', 'grace@worldchoiceperfumes.co.tz', NULL, '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255754000004', 'branch_admin', 'active', NULL, NULL, NULL, TRUE, NULL, '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(11, 'Samuel Ndege', 'samuel@worldchoiceperfumes.co.tz', NULL, '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000005', 'cashier', 'active', NULL, NULL, NULL, TRUE, NULL, '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(6, 'Emmanuel Shirima', 'emmanuel@worldchoiceperfumes.co.tz', NULL, '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255754000005', 'branch_admin', 'active', NULL, NULL, NULL, TRUE, NULL, '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(12, 'Hauwa Ramadhani', 'hauwa@worldchoiceperfumes.co.tz', NULL, '$2y$12$QJWxPVV5h6B0b2l9a5H5qOy7lKx8B1v9Qr2tW4u6yA8cE0gF2hI4j', '+255755000006', 'cashier', 'active', NULL, NULL, NULL, TRUE, NULL, '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(47, 'Gideon Msuya', 'gideonmsuya158@gmail.com', NULL, '$2y$12$IGOxwi0Y7HGw2JnGScld/uN8L1ZGfdxFWEgazePoACiIyhpttmuG6', '0682601154', 'branch_admin', 'active', 8, NULL, NULL, FALSE, NULL, '2026-09-22T09:08:12', '2026-09-22T09:08:12'),
(43, 'Gideon Msuya', 'gideonmsuya154@gmail.com', NULL, '$2y$12$DDMYqu0IFZCd.b4GPS0dnOas5O.RQDmaZNttrI2XzZNehcXXpyDXa', '0682601154', 'seller', 'active', 9, NULL, NULL, TRUE, NULL, '2026-09-13T15:59:33', '2026-09-13T15:59:33'),
(45, 'Gideon Msuya', 'gideonmsuya156@gmail.com', NULL, '$2y$12$c3FoM4wAl9Te5eqSXytN1ebHKFbCd6as8cfYTPHqIoMS3au6izQG2', '0682601154', 'cashier', 'rejected', 9, NULL, NULL, TRUE, NULL, '2026-09-13T16:01:41', '2026-09-13T16:01:41');

-- products (315 rows)
INSERT INTO public.products (id, name, description, brand, category, is_active, created_at, updated_at, sex_category, unit_cost, costing_volume, fundamental_ingredient) VALUES
(93, 'Candy Rush', NULL, 'World Choice Perfumes', 'Oil Fragrance', TRUE, '2026-09-06T16:50:11', '2026-09-18T16:31:53', 'female', 0.0, NULL, 'Gourmand'),
(178, 'Vintage Radio', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:22:00', '2026-09-18T16:34:06', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(331, 'Reef 31', NULL, 'Reef', 'Oil Fragrance', FALSE, '2026-09-16T09:37:25', '2026-09-17T13:24:46', NULL, 0.0, 1000, 'Fresh/Citrus'),
(252, 'Shaghaf Oud Tonka', NULL, 'Swiss Arabian', 'Brand Perfume', TRUE, '2026-09-06T19:53:58', '2026-09-18T16:28:04', 'unisex', 0.0, NULL, 'Oud'),
(248, 'Atlas', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:52:16', '2026-09-18T16:28:02', 'male', 0.0, NULL, 'Fresh/Citrus'),
(246, 'MARMARA', NULL, 'French Avenue', 'Brand Perfume', TRUE, '2026-09-06T19:51:46', '2026-09-12T20:08:27', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(337, 'Sugar Candy', NULL, NULL, 'Brand Perfume', TRUE, '2026-09-19T08:40:00', '2026-09-19T08:40:00', 'female', 0.0, NULL, 'Fruity'),
(323, 'Pocket perfume', NULL, NULL, 'Oil Fragrance', TRUE, '2026-09-14T12:15:40', '2026-09-14T12:15:40', 'accessories', 0.0, NULL, NULL),
(327, 'Empty Bottle 30ml', NULL, 'Empty Bottles', NULL, FALSE, '2026-09-15T13:26:39', '2026-09-15T13:26:39', NULL, 0.0, NULL, NULL),
(328, 'Empty Bottle 50ml', NULL, 'Empty Bottles', NULL, FALSE, '2026-09-15T13:26:40', '2026-09-15T13:26:40', NULL, 0.0, NULL, NULL),
(219, 'Club De Nuit urban man elixir', NULL, 'Armaf', 'Brand Perfume', TRUE, '2026-09-06T19:43:03', '2026-09-18T16:39:17', 'male', 0.0, NULL, 'Amber/Spicy'),
(335, 'Diffuser', NULL, NULL, 'Brand Perfume', TRUE, '2026-09-17T14:06:16', '2026-09-17T14:06:16', 'accessories', 0.0, NULL, NULL),
(211, '9 pm night out', NULL, 'Afnan', 'Brand Perfume', TRUE, '2026-09-06T19:39:49', '2026-09-18T16:38:58', 'male', 0.0, NULL, 'Amber/Spicy'),
(66, 'Legent Mont Blank Men', NULL, 'Montblanc', 'Oil Fragrance', TRUE, '2026-09-06T16:36:02', '2026-09-18T16:27:42', 'male', 0.0, NULL, 'Aromatic'),
(139, 'SEASONS RISE', NULL, 'Riiffs', 'Brand Perfume', TRUE, '2026-09-06T18:53:13', '2026-09-18T16:33:05', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(71, 'Midnight Fantasy', NULL, 'Britney Spears', 'Oil Fragrance', TRUE, '2026-09-06T16:39:06', '2026-09-18T16:27:44', 'female', 0.0, NULL, 'Fruity'),
(70, 'Jadore Women', NULL, 'Dior', 'Oil Fragrance', TRUE, '2026-09-06T16:37:59', '2026-09-18T16:31:12', 'female', 0.0, NULL, 'Floral'),
(98, 'OUD Maracuja Maison Criveli', NULL, 'Maison Crivelli', 'Oil Fragrance', TRUE, '2026-09-06T16:53:33', '2026-09-18T16:27:54', 'unisex', 0.0, NULL, 'Oud'),
(128, 'Kouros', NULL, 'Yves Saint Laurent', 'Brand Perfume', TRUE, '2026-09-06T18:48:31', '2026-09-18T16:32:48', 'male', 0.0, NULL, 'Aromatic'),
(101, 'Amouage Guidance 46 Top', NULL, 'Amouage', 'Oil Fragrance', TRUE, '2026-09-06T16:56:17', '2026-09-18T16:32:08', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(283, 'Sydney', NULL, 'Arabiyat Prestige', 'Brand Perfume', FALSE, '2026-09-06T20:10:06', '2026-09-24T07:41:49', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(183, 'Vulcan Feu', NULL, 'French Avenue', 'Brand Perfume', TRUE, '2026-09-06T19:23:55', '2026-09-18T16:34:13', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(305, 'SWISS ARABIAN SHANGAF OUD TONKA', NULL, 'Swiss Arabian', 'Brand Perfume', FALSE, '2026-09-06T20:24:46', '2026-09-18T16:28:07', 'unisex', 0.0, NULL, 'Oud'),
(181, 'Second Song Angham', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:23:15', '2026-09-18T16:34:09', 'male', 0.0, NULL, 'Amber/Spicy'),
(180, 'Dubai night Midnight', NULL, 'Armaf', 'Brand Perfume', TRUE, '2026-09-06T19:22:42', '2026-09-18T16:34:07', 'male', 0.0, NULL, 'Amber/Spicy'),
(173, 'Lynked Forever', NULL, 'Afnan', 'Brand Perfume', TRUE, '2026-09-06T19:20:11', '2026-09-18T16:33:58', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(172, 'Private Key', NULL, 'Armaf', 'Brand Perfume', TRUE, '2026-09-06T19:19:43', '2026-09-18T16:33:56', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(78, 'Bleu de Chanel', NULL, 'Chanel', 'Oil Fragrance', TRUE, '2026-09-06T16:43:10', '2026-09-18T16:27:47', 'male', 0.0, NULL, 'Aromatic'),
(168, 'Vanguard', NULL, 'Maison Asrar', 'Brand Perfume', TRUE, '2026-09-06T19:18:22', '2026-09-18T16:33:51', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(157, 'Plum Liquor', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T19:02:55', '2026-09-18T16:33:34', 'unisex', 0.0, NULL, 'Fruity'),
(149, 'REEF 33 Black', NULL, 'Reef', 'Brand Perfume', TRUE, '2026-09-06T18:58:40', '2026-09-18T16:33:20', 'male', 0.0, NULL, 'Wood'),
(52, 'Allure Homme Sport Super Leggera', NULL, 'Chanel', 'Oil Fragrance', TRUE, '2026-09-06T16:26:58', '2026-09-18T16:30:43', 'male', 0.0, NULL, 'Fresh/Citrus'),
(82, 'YSL for men', NULL, 'Yves Saint Laurent', 'Oil Fragrance', TRUE, '2026-09-06T16:45:26', '2026-09-18T16:27:49', 'male', 0.0, NULL, 'Fresh/Citrus'),
(87, 'Issey Miyake Men', NULL, 'Issey Miyake', 'Oil Fragrance', TRUE, '2026-09-06T16:47:22', '2026-09-18T16:27:50', 'male', 0.0, NULL, 'Fresh/Citrus'),
(110, 'Sauvage Dior', NULL, 'Dior', 'Brand Perfume', TRUE, '2026-09-06T18:35:25', '2026-09-18T16:32:19', 'male', 0.0, NULL, 'Aromatic'),
(184, 'Riwayah', NULL, 'Riwayat', 'Brand Perfume', TRUE, '2026-09-06T19:24:08', '2026-09-18T16:34:16', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(120, 'Hawas Glitz', NULL, 'Rasasi', 'Brand Perfume', TRUE, '2026-09-06T18:43:11', '2026-09-18T16:32:37', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(255, 'Island Hadlaj', NULL, 'Hadlaj', 'Brand Perfume', TRUE, '2026-09-06T19:55:14', '2026-09-12T20:08:42', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(266, 'Al-nashana plane', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T20:02:20', '2026-09-12T20:09:00', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(299, 'Zodiac Solmaris', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T20:17:22', '2026-09-12T20:09:57', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(34, 'Hibiscus Mahajad-Maison Criveli', NULL, 'Maison Crivelli', 'Oil Fragrance', TRUE, '2026-09-06T16:12:58', '2026-09-18T16:27:36', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(57, 'Olympea', NULL, 'Paco Rabanne', 'Oil Fragrance', TRUE, '2026-09-06T16:29:07', '2026-09-18T16:30:52', 'female', 0.0, NULL, 'Amber/Spicy'),
(61, 'Barcode', NULL, 'Paris Corner', 'Oil Fragrance', TRUE, '2026-09-06T16:32:14', '2026-09-18T16:30:59', 'male', 0.0, NULL, 'Amber/Spicy'),
(68, 'Golden Dust', NULL, 'Sunnamusk', 'Oil Fragrance', TRUE, '2026-09-06T16:37:04', '2026-09-18T16:31:08', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(69, 'Black Orchid', NULL, 'Tom Ford', 'Oil Fragrance', TRUE, '2026-09-06T16:37:26', '2026-09-18T16:31:10', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(72, 'Sauvage Elixir', NULL, 'Dior', 'Oil Fragrance', TRUE, '2026-09-06T16:39:49', '2026-09-18T16:31:14', 'male', 0.0, NULL, 'Amber/Spicy'),
(79, 'Scandal', NULL, 'Jean Paul Gaultier', 'Oil Fragrance', TRUE, '2026-09-06T16:44:03', '2026-09-18T16:31:24', 'female', 0.0, NULL, 'Amber/Spicy'),
(84, 'Arman Code', NULL, 'Giorgio Armani', 'Oil Fragrance', TRUE, '2026-09-06T16:46:04', '2026-09-18T16:31:32', 'male', 0.0, NULL, 'Amber/Spicy'),
(62, 'Eclair', NULL, 'Lattafa', 'Oil Fragrance', TRUE, '2026-09-06T16:32:44', '2026-09-18T16:27:40', 'female', 0.0, NULL, 'Gourmand'),
(76, 'Black Opium Ysl', NULL, 'Yves Saint Laurent', 'Oil Fragrance', TRUE, '2026-09-06T16:42:23', '2026-09-18T16:27:46', 'female', 0.0, NULL, 'Gourmand'),
(81, 'Coconut Passion', NULL, 'Victoria''s Secret', 'Oil Fragrance', TRUE, '2026-09-06T16:44:56', '2026-09-18T16:31:28', 'female', 0.0, NULL, 'Gourmand'),
(92, 'My Way Sunny Vanilla', NULL, 'Giorgio Armani', 'Oil Fragrance', TRUE, '2026-09-06T16:49:53', '2026-09-18T16:27:52', 'female', 0.0, NULL, 'Gourmand'),
(346, 'Dolce & Gabbana The one', NULL, 'Dolce & Gabbana', 'Brand Perfume', TRUE, '2026-09-21T16:29:47', '2026-09-21T16:29:47', 'male', 0.0, NULL, 'Amber/Spicy'),
(153, 'Safari Breeze', NULL, 'French Avenue', 'Brand Perfume', TRUE, '2026-09-06T19:00:27', '2026-09-18T16:28:26', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(127, 'Angels'' Share', NULL, 'Kilian', 'Brand Perfume', TRUE, '2026-09-06T18:48:08', '2026-09-18T16:28:16', 'unisex', 0.0, NULL, 'Gourmand'),
(226, 'Khamrah Waha', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:45:21', '2026-09-18T16:39:31', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(177, 'Aromatic Magnetic', NULL, 'French Avenue', 'Brand Perfume', TRUE, '2026-09-06T19:21:42', '2026-09-18T16:34:03', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(131, 'Tomford Ombre Leather', NULL, 'Tom Ford', 'Brand Perfume', TRUE, '2026-09-06T18:49:31', '2026-09-18T16:28:18', 'unisex', 0.0, NULL, 'Wood'),
(194, 'AZM', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T19:31:59', '2026-09-18T16:28:33', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(204, 'Precieux', NULL, 'Armaf', 'Brand Perfume', TRUE, '2026-09-06T19:36:04', '2026-09-18T16:28:36', 'unisex', 0.0, NULL, 'Fruity'),
(100, 'Yum boujee Marshimallow81 -Kayali', NULL, 'Kayali', 'Oil Fragrance', TRUE, '2026-09-06T16:55:17', '2026-09-18T16:32:04', 'female', 0.0, NULL, 'Gourmand'),
(279, 'Intense Man Essencia de flores', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T20:08:39', '2026-09-12T20:09:21', 'male', 0.0, NULL, 'Amber/Spicy'),
(104, 'My Devotion', NULL, 'Dolce & Gabbana', 'Oil Fragrance', TRUE, '2026-09-06T16:58:03', '2026-09-18T16:27:55', 'female', 0.0, NULL, 'Gourmand'),
(262, 'Nebras New', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T20:00:32', '2026-09-18T16:28:10', 'female', 0.0, NULL, 'Gourmand'),
(108, 'Valentino Donna Born in Roma Intense', NULL, 'Valentino', 'Brand Perfume', TRUE, '2026-09-06T18:34:23', '2026-09-18T16:28:09', 'female', 0.0, NULL, 'Gourmand'),
(121, 'Louis Vuitton Imagination', NULL, 'Louis Vuitton', 'Brand Perfume', TRUE, '2026-09-06T18:43:40', '2026-09-18T16:28:14', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(165, 'AL-DIRGHAM', NULL, 'Ard Al Zaafaran', 'Brand Perfume', TRUE, '2026-09-06T19:06:05', '2026-09-18T16:28:29', 'male', 0.0, NULL, 'Amber/Spicy'),
(115, 'UTOPIA Vanilla Coco intense', NULL, 'Kayali', 'Brand Perfume', TRUE, '2026-09-06T18:38:45', '2026-09-18T16:27:57', 'unisex', 0.0, NULL, 'Gourmand'),
(136, 'Burberry Her', NULL, 'Burberry', 'Brand Perfume', TRUE, '2026-09-06T18:51:59', '2026-09-18T16:28:20', 'female', 0.0, NULL, 'Fruity'),
(141, 'Club De Nuit Precieux iv', NULL, 'Armaf', 'Brand Perfume', TRUE, '2026-09-06T18:54:48', '2026-09-18T16:28:22', 'male', 0.0, NULL, 'Fresh/Citrus'),
(148, 'HAWAS London', NULL, 'Rasasi', 'Brand Perfume', TRUE, '2026-09-06T18:57:42', '2026-09-18T16:28:24', 'male', 0.0, NULL, 'Fresh/Citrus'),
(265, 'Al-nashana caprice', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T20:02:00', '2026-09-18T16:28:58', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(160, 'Life Journal', NULL, 'Eddie Milliz', 'Brand Perfume', TRUE, '2026-09-06T19:04:06', '2026-09-18T16:28:28', 'unisex', 0.0, NULL, 'Aromatic'),
(167, 'Thriller III', NULL, 'Maison Asrar', 'Brand Perfume', TRUE, '2026-09-06T19:18:04', '2026-09-18T16:33:49', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(169, 'Milk way', NULL, 'Maison Asrar', 'Brand Perfume', TRUE, '2026-09-06T19:18:40', '2026-09-18T16:28:31', 'unisex', 0.0, NULL, 'Gourmand'),
(338, 'Empty Bottle 12ml', NULL, 'Empty Bottles', NULL, FALSE, '2026-09-19T14:54:40', '2026-09-19T14:54:40', NULL, 0.0, NULL, NULL),
(241, 'FATIMA zimaya', NULL, 'Zimaya', 'Brand Perfume', TRUE, '2026-09-06T19:49:50', '2026-09-12T20:08:19', 'female', 0.0, NULL, 'Amber/Spicy'),
(256, '9 pm pour femme', NULL, 'Afnan', 'Brand Perfume', TRUE, '2026-09-06T19:55:36', '2026-09-12T20:08:44', 'female', 0.0, NULL, 'Amber/Spicy'),
(257, 'Karus', NULL, 'Khadlaj', 'Brand Perfume', TRUE, '2026-09-06T19:55:52', '2026-09-12T20:08:45', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(258, 'Art of Universe', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:56:11', '2026-09-18T16:28:12', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(259, 'Night club', NULL, 'Fragrance World', 'Brand Perfume', TRUE, '2026-09-06T19:56:52', '2026-09-12T20:08:48', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(264, 'Fusion intense', NULL, 'Maison Alhambra', 'Brand Perfume', TRUE, '2026-09-06T20:01:10', '2026-09-12T20:08:57', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(270, 'Qaed Al Fursan', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T20:03:53', '2026-09-12T20:09:06', 'male', 0.0, NULL, 'Amber/Spicy'),
(272, 'OPHIDIAN', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T20:05:12', '2026-09-12T20:09:09', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(273, 'RA''ED LUXE', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T20:05:36', '2026-09-18T16:39:15', 'male', 0.0, NULL, 'Amber/Spicy'),
(274, 'Scepter Malachite', NULL, 'Maison Alhambra', 'Brand Perfume', TRUE, '2026-09-06T20:06:08', '2026-09-12T20:09:13', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(281, 'Khamrah', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T20:09:33', '2026-09-12T20:09:25', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(292, 'RAMZ lattafa silver', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T20:14:53', '2026-09-12T20:09:45', 'male', 0.0, NULL, 'Amber/Spicy'),
(293, 'Barcode Autograph', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T20:15:12', '2026-09-12T20:09:47', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(294, 'Barcode Signature', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T20:15:33', '2026-09-15T15:51:26', 'male', 0.0, NULL, 'Amber/Spicy'),
(295, 'RAMZ lattafa gold', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T20:15:54', '2026-09-12T20:09:50', 'male', 0.0, NULL, 'Amber/Spicy'),
(296, 'ASAD Elixir', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T20:16:10', '2026-09-12T20:09:52', 'male', 0.0, NULL, 'Amber/Spicy'),
(306, 'SWISS ARABIAN MUSK 01', NULL, 'Swiss Arabian', 'Brand Perfume', TRUE, '2026-09-06T20:25:10', '2026-09-18T16:28:56', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(310, 'SWISS ARABIAN Incense 01', NULL, 'Swiss Arabian', 'Brand Perfume', TRUE, '2026-09-06T20:27:15', '2026-09-12T20:10:15', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(232, 'Intense Noir', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T19:47:12', '2026-09-18T16:39:44', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(242, 'Marshmallow Blush', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T19:50:10', '2026-09-18T16:28:00', 'unisex', 0.0, NULL, 'Gourmand'),
(289, 'Art Of Nature', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T20:13:00', '2026-09-12T20:09:40', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(332, 'Sex Gravity', NULL, 'World Choice Perfumes', 'Oil Fragrance', TRUE, '2026-09-17T12:50:58', '2026-09-18T16:28:05', 'female', 0.0, NULL, 'Amber/Spicy'),
(347, 'Empty Bottle 6ml', NULL, 'Empty Bottles', NULL, FALSE, '2026-09-25T17:04:12', '2026-09-25T17:04:12', NULL, 0.0, NULL, NULL),
(329, 'Purpose Amouage', NULL, 'Amouage', 'Oil Fragrance', TRUE, '2026-09-16T09:00:05', '2026-09-18T16:28:53', NULL, 0.0, 1000, 'Amber/Spicy'),
(280, 'Faris Al Atrab', NULL, 'L''Affair', 'Brand Perfume', TRUE, '2026-09-06T20:09:15', '2026-09-12T20:09:23', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(238, 'Now Black', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:48:47', '2026-09-12T20:08:14', 'male', 0.0, NULL, 'Amber/Spicy'),
(324, 'Stronger with you', NULL, 'ARMANI', 'Oil Fragrance', TRUE, '2026-09-14T14:05:58', '2026-09-18T16:28:43', 'female', 0.0, NULL, 'Amber/Spicy'),
(236, 'Cookie Bite', NULL, 'Gulf Orchid', 'Brand Perfume', TRUE, '2026-09-06T19:48:20', '2026-09-18T16:27:59', 'unisex', 0.0, NULL, 'Gourmand'),
(225, 'Ombre Dor', NULL, 'Armaf', 'Brand Perfume', TRUE, '2026-09-06T19:45:04', '2026-09-18T16:39:29', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(216, 'Club De Nuit Maleka', NULL, 'Armaf', 'Brand Perfume', TRUE, '2026-09-06T19:41:55', '2026-09-18T16:28:38', 'female', 0.0, NULL, 'Fruity'),
(190, 'Vanilla Addiction', NULL, 'Gulf Orchid', 'Brand Perfume', TRUE, '2026-09-06T19:26:42', '2026-09-18T16:34:31', 'unisex', 0.0, NULL, 'Gourmand'),
(300, 'Secreto', NULL, 'World Choice Perfumes', 'Oil Fragrance', TRUE, '2026-09-06T20:20:54', '2026-09-12T20:09:58', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(301, 'Secreto 02', NULL, 'World Choice Perfumes', 'Oil Fragrance', TRUE, '2026-09-06T20:21:14', '2026-09-12T20:10:00', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(302, 'Secreto 04', NULL, 'World Choice Perfumes', 'Oil Fragrance', TRUE, '2026-09-06T20:21:34', '2026-09-12T20:10:02', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(333, 'Diffuser', NULL, NULL, 'Brand Perfume', FALSE, '2026-09-17T13:56:45', '2026-09-17T13:58:49', 'accessories', 0.0, NULL, NULL),
(325, 'Coco Vanilla', NULL, 'World Choice Perfumes', 'Oil Fragrance', TRUE, '2026-09-14T14:12:28', '2026-09-18T16:29:16', NULL, 0.0, NULL, 'Gourmand'),
(330, 'Hugo Boss Bold Citrus men', NULL, 'Hugo Boss', 'Oil Fragrance', FALSE, '2026-09-16T09:17:01', '2026-09-18T16:29:18', 'male', 0.0, 1000, 'Fresh/Citrus'),
(336, 'Club De Nuit intense man', NULL, 'Armaf', 'Brand Perfume', TRUE, '2026-09-17T16:33:41', '2026-09-18T16:29:20', NULL, 0.0, NULL, 'Fruity'),
(233, 'Pride Intense', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T19:47:26', '2026-09-18T16:39:47', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(224, 'Queen of Roses', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:44:45', '2026-09-18T16:39:26', 'female', 0.0, NULL, 'Floral'),
(215, 'Club De Nuit imperial', NULL, 'Armaf', 'Brand Perfume', TRUE, '2026-09-06T19:41:04', '2026-09-18T16:39:08', 'male', 0.0, NULL, 'Fresh/Citrus'),
(210, '9 pm rebel', NULL, 'Afnan', 'Brand Perfume', TRUE, '2026-09-06T19:39:31', '2026-09-18T16:29:13', 'male', 0.0, NULL, 'Amber/Spicy'),
(209, '9 pm elixir', NULL, 'Afnan', 'Brand Perfume', TRUE, '2026-09-06T19:39:16', '2026-09-18T16:29:11', 'male', 0.0, NULL, 'Amber/Spicy'),
(199, 'Eternal Vanille', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T19:34:33', '2026-09-18T16:29:09', 'female', 0.0, NULL, 'Gourmand'),
(339, 'YARA PINK', NULL, 'Lattafa', 'Oil Fragrance', TRUE, '2026-09-19T15:08:56', '2026-09-19T15:08:56', 'female', 0.0, NULL, 'Fruity'),
(189, 'Raghba wood intense', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:26:17', '2026-09-18T16:29:07', 'unisex', 0.0, NULL, 'Wood'),
(185, 'Kaaf', NULL, 'Ahmed Al Maghribi', 'Brand Perfume', TRUE, '2026-09-06T19:24:21', '2026-09-18T16:29:04', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(179, 'Dubai night Umbra', NULL, 'Armaf', 'Brand Perfume', TRUE, '2026-09-06T19:22:19', '2026-09-18T16:29:02', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(174, 'Marwa', NULL, 'Arabiyat Prestige', 'Brand Perfume', TRUE, '2026-09-06T19:20:31', '2026-09-18T16:29:00', 'male', 0.0, NULL, 'Amber/Spicy'),
(348, 'ATLAS', NULL, NULL, 'Oil Fragrance', TRUE, '2026-09-30T08:16:43', '2026-09-30T08:16:43', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(27, 'Sauvage Dior', NULL, 'Dior', 'Oil Fragrance', TRUE, '2026-09-06T16:04:01', '2026-09-18T16:29:26', 'male', 0.0, NULL, 'Aromatic'),
(45, 'Reef 33', NULL, 'Reef', 'Oil Fragrance', TRUE, '2026-09-06T16:20:47', '2026-09-18T16:30:31', 'male', 0.0, NULL, 'Aromatic'),
(29, 'Taj Sunset', NULL, 'Escada', 'Oil Fragrance', TRUE, '2026-09-06T16:05:12', '2026-09-18T16:29:29', 'female', 0.0, NULL, 'Fruity'),
(33, 'Dior Homme Perfume', NULL, 'Dior', 'Oil Fragrance', TRUE, '2026-09-06T16:11:46', '2026-09-18T16:30:11', 'male', 0.0, NULL, 'Wood'),
(77, 'Berries Weekend', NULL, 'Fragrance World', 'Oil Fragrance', TRUE, '2026-09-06T16:42:45', '2026-09-18T16:31:22', 'female', 0.0, NULL, 'Fruity'),
(37, 'Now Rave', NULL, 'Lattafa', 'Oil Fragrance', TRUE, '2026-09-06T16:16:05', '2026-09-18T16:30:17', 'male', 0.0, NULL, 'Fruity'),
(30, 'My Way', NULL, 'Giorgio Armani', 'Oil Fragrance', TRUE, '2026-09-06T16:05:30', '2026-09-18T16:30:03', 'female', 0.0, NULL, 'Floral'),
(32, 'Pink Sugar', NULL, 'Aquolina', 'Oil Fragrance', TRUE, '2026-09-06T16:11:06', '2026-09-18T16:30:07', 'female', 0.0, NULL, 'Gourmand'),
(38, 'Pink Shiffon', NULL, 'Bath & Body Works', 'Oil Fragrance', TRUE, '2026-09-06T16:16:27', '2026-09-18T16:30:19', 'female', 0.0, NULL, 'Fruity'),
(46, 'Tomford Ombre Leather', NULL, 'Tom Ford', 'Oil Fragrance', TRUE, '2026-09-06T16:21:25', '2026-09-18T16:30:33', 'unisex', 0.0, NULL, 'Wood'),
(26, '9 pm rebel', NULL, 'Afnan', 'Oil Fragrance', TRUE, '2026-09-06T16:02:57', '2026-09-18T16:29:24', 'male', 0.0, NULL, 'Amber/Spicy'),
(35, 'Emarude Super', NULL, 'World Choice Perfumes', 'Oil Fragrance', TRUE, '2026-09-06T16:14:02', '2026-09-18T16:30:13', 'female', 0.0, NULL, 'Floral'),
(42, 'Strawberry', NULL, 'World Choice Perfumes', 'Oil Fragrance', TRUE, '2026-09-06T16:19:23', '2026-09-18T16:30:26', 'female', 0.0, NULL, 'Fruity'),
(50, 'Mousof', NULL, 'World Choice Perfumes', 'Oil Fragrance', TRUE, '2026-09-06T16:24:06', '2026-09-18T16:30:40', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(340, 'OUD WOOD', NULL, NULL, 'Oil Fragrance', TRUE, '2026-09-19T15:13:18', '2026-09-19T15:13:18', 'unisex', 0.0, NULL, 'Oud'),
(51, 'Sugar Baby', NULL, 'World Choice Perfumes', 'Oil Fragrance', TRUE, '2026-09-06T16:24:24', '2026-09-18T16:30:42', 'female', 0.0, NULL, 'Gourmand'),
(74, 'Rashiqa', NULL, 'World Choice Perfumes', 'Oil Fragrance', TRUE, '2026-09-06T16:41:28', '2026-09-18T16:31:18', 'female', 0.0, NULL, 'Floral'),
(75, 'Sweet Camilla', NULL, 'World Choice Perfumes', 'Oil Fragrance', TRUE, '2026-09-06T16:41:57', '2026-09-18T16:31:20', 'female', 0.0, NULL, 'Floral'),
(83, 'Sweet Passion', NULL, 'World Choice Perfumes', 'Oil Fragrance', TRUE, '2026-09-06T16:45:45', '2026-09-18T16:31:30', 'female', 0.0, NULL, 'Gourmand'),
(341, 'YSL LIBRE', NULL, NULL, 'Oil Fragrance', TRUE, '2026-09-19T15:13:50', '2026-09-19T15:13:50', 'unisex', 0.0, NULL, NULL),
(95, 'Butterfly', NULL, 'World Choice Perfumes', 'Oil Fragrance', TRUE, '2026-09-06T16:51:20', '2026-09-18T16:31:57', 'female', 0.0, NULL, 'Floral'),
(342, 'DELINA', NULL, NULL, 'Oil Fragrance', TRUE, '2026-09-19T15:14:28', '2026-09-19T15:14:28', 'female', 0.0, NULL, NULL),
(222, 'Tiramisu Zimaya', NULL, 'Zimaya', 'Brand Perfume', TRUE, '2026-09-06T19:44:06', '2026-09-18T16:32:06', 'female', 0.0, NULL, 'Gourmand'),
(56, '9 pm Black', NULL, 'Afnan', 'Oil Fragrance', TRUE, '2026-09-06T16:28:44', '2026-09-18T16:30:51', 'male', 0.0, NULL, 'Amber/Spicy');
INSERT INTO public.products (id, name, description, brand, category, is_active, created_at, updated_at, sex_category, unit_cost, costing_volume, fundamental_ingredient) VALUES
(44, 'Creed Aventus', NULL, 'Creed', 'Oil Fragrance', TRUE, '2026-09-06T16:20:32', '2026-09-18T16:30:30', 'male', 0.0, NULL, 'Fruity'),
(53, '212 VIP Man', NULL, 'Carolina Herrera', 'Oil Fragrance', TRUE, '2026-09-06T16:27:33', '2026-09-18T16:30:45', 'male', 0.0, NULL, 'Fruity'),
(39, 'Valaya Perfume de Marly Super', NULL, 'Parfums de Marly', 'Oil Fragrance', TRUE, '2026-09-06T16:17:16', '2026-09-18T16:30:21', 'female', 0.0, NULL, 'Floral'),
(91, 'Classic Stone', NULL, 'Lattafa', 'Oil Fragrance', TRUE, '2026-09-06T16:49:07', '2026-09-18T16:31:48', 'unisex', 0.0, NULL, 'Wood'),
(28, 'Khamrah Lattaffa', NULL, 'Lattafa', 'Oil Fragrance', TRUE, '2026-09-06T16:04:51', '2026-09-18T16:29:27', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(59, 'Good Girl', NULL, 'Carolina Herrera', 'Oil Fragrance', TRUE, '2026-09-06T16:30:33', '2026-09-18T16:30:56', 'female', 0.0, NULL, 'Gourmand'),
(73, 'Eclaire Banoffi Lotfa', NULL, 'Lattafa', 'Oil Fragrance', TRUE, '2026-09-06T16:40:43', '2026-09-18T16:31:16', 'female', 0.0, NULL, 'Gourmand'),
(58, 'Strawberry Letter Philur-2LZ', NULL, 'Phlur', 'Oil Fragrance', TRUE, '2026-09-06T16:30:13', '2026-09-18T16:30:54', 'female', 0.0, NULL, 'Fruity'),
(47, 'Crystal Emerald Versacea', NULL, 'Versace', 'Oil Fragrance', TRUE, '2026-09-06T16:22:09', '2026-09-18T16:30:35', 'female', 0.0, NULL, 'Floral'),
(55, 'Ajwad Pink', NULL, 'Lattafa', 'Oil Fragrance', TRUE, '2026-09-06T16:28:24', '2026-09-18T16:30:49', 'female', 0.0, NULL, 'Floral'),
(31, 'Baccarat Rouge', NULL, 'Maison Francis Kurkdjian', 'Oil Fragrance', TRUE, '2026-09-06T16:06:13', '2026-09-18T16:30:05', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(36, 'Wanted Azzaro', NULL, 'Azzaro', 'Oil Fragrance', TRUE, '2026-09-06T16:15:02', '2026-09-18T16:30:15', 'male', 0.0, NULL, 'Amber/Spicy'),
(63, 'Chance Eau Splendide Chanel', NULL, 'Chanel', 'Oil Fragrance', TRUE, '2026-09-06T16:33:24', '2026-09-18T16:31:00', 'female', 0.0, NULL, 'Floral'),
(80, 'Erba Pura', NULL, 'Xerjoff', 'Oil Fragrance', TRUE, '2026-09-06T16:44:37', '2026-09-18T16:31:26', 'unisex', 0.0, NULL, 'Fruity'),
(88, 'Yara Candy', NULL, 'Lattafa', 'Oil Fragrance', TRUE, '2026-09-06T16:47:47', '2026-09-18T16:31:40', 'female', 0.0, NULL, 'Gourmand'),
(64, 'Yum me, Sunny Escadae', NULL, 'Escada', 'Oil Fragrance', TRUE, '2026-09-06T16:34:58', '2026-09-18T16:31:02', 'female', 0.0, NULL, 'Fruity'),
(48, 'Million Gold for Man Paco Rabane', NULL, 'Paco Rabanne', 'Oil Fragrance', TRUE, '2026-09-06T16:23:02', '2026-09-18T16:30:37', 'male', 0.0, NULL, 'Amber/Spicy'),
(86, 'Roberto Carvali', NULL, 'Roberto Cavalli', 'Oil Fragrance', TRUE, '2026-09-06T16:46:57', '2026-09-18T16:31:37', 'female', 0.0, NULL, 'Floral'),
(67, 'Escada Ocean Lounge', NULL, 'Escada', 'Oil Fragrance', TRUE, '2026-09-06T16:36:42', '2026-09-18T16:31:06', 'female', 0.0, NULL, 'Fruity'),
(97, 'La Nuit Tresore', NULL, 'Lancome', 'Oil Fragrance', TRUE, '2026-09-06T16:52:48', '2026-09-18T16:32:00', 'female', 0.0, NULL, 'Fruity'),
(99, 'Club De Nuit', NULL, 'Armaf', 'Oil Fragrance', TRUE, '2026-09-06T16:53:59', '2026-09-18T16:32:02', 'male', 0.0, NULL, 'Fruity'),
(89, 'Vanilla 28', NULL, 'Kayali', 'Oil Fragrance', TRUE, '2026-09-06T16:48:05', '2026-09-18T16:31:42', 'unisex', 0.0, NULL, 'Gourmand'),
(94, 'Million Gold for Woman Paco Rabane', NULL, 'Paco Rabanne', 'Oil Fragrance', TRUE, '2026-09-06T16:50:52', '2026-09-18T16:31:55', 'female', 0.0, NULL, 'Gourmand'),
(49, '1 Million', NULL, 'Paco Rabanne', 'Oil Fragrance', TRUE, '2026-09-06T16:23:18', '2026-09-18T16:30:38', 'male', 0.0, NULL, 'Amber/Spicy'),
(54, 'Scandal Man', NULL, 'Jean Paul Gaultier', 'Oil Fragrance', TRUE, '2026-09-06T16:27:56', '2026-09-18T16:30:47', 'male', 0.0, NULL, 'Amber/Spicy'),
(25, 'ACQUA DI GIO PROFONDO ARMANI', NULL, 'Giorgio Armani', 'Oil Fragrance', TRUE, '2026-09-06T16:02:07', '2026-09-18T16:29:22', 'male', 0.0, NULL, 'Fresh/Citrus'),
(142, 'EMIR factor edition', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T18:55:16', '2026-09-18T16:33:09', 'unisex', 0.0, NULL, 'Aromatic'),
(102, 'Valentino Donna Born in Roma Extradose', NULL, 'Valentino', 'Oil Fragrance', TRUE, '2026-09-06T16:57:12', '2026-09-18T16:32:09', 'female', 0.0, NULL, 'Gourmand'),
(107, 'Imagination', NULL, 'Louis Vuitton', 'Oil Fragrance', TRUE, '2026-09-06T16:59:34', '2026-09-18T16:32:16', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(140, 'Club De Nuit Overdose', NULL, 'Armaf', 'Brand Perfume', TRUE, '2026-09-06T18:53:47', '2026-09-18T16:33:07', 'male', 0.0, NULL, 'Fruity'),
(163, 'Enchantment', NULL, 'Pendora Scents', 'Brand Perfume', FALSE, '2026-09-06T19:05:10', '2026-09-24T07:44:31', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(343, 'GUCCI GUILTY', NULL, NULL, 'Oil Fragrance', TRUE, '2026-09-19T15:15:51', '2026-09-19T15:15:51', 'unisex', 0.0, NULL, NULL),
(176, 'Aromatic Forbidden fruit', NULL, 'French Avenue', 'Brand Perfume', TRUE, '2026-09-06T19:21:21', '2026-09-18T16:34:02', 'unisex', 0.0, NULL, 'Fruity'),
(106, 'Stronger with you Powerfully', NULL, 'Emporio Armani', 'Oil Fragrance', TRUE, '2026-09-06T16:59:14', '2026-09-18T16:32:14', 'male', 0.0, NULL, 'Amber/Spicy'),
(158, 'IMPRESSION', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T19:03:24', '2026-09-18T16:33:36', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(175, 'Aromatic FrostBite', NULL, 'French Avenue', 'Brand Perfume', TRUE, '2026-09-06T19:20:57', '2026-09-18T16:34:00', 'unisex', 0.0, NULL, 'Aromatic'),
(171, 'Yum yum', NULL, 'Armaf', 'Brand Perfume', TRUE, '2026-09-06T19:19:27', '2026-09-18T16:33:55', 'female', 0.0, NULL, 'Gourmand'),
(164, 'Cocktail', NULL, 'Fragrance World', 'Brand Perfume', TRUE, '2026-09-06T19:05:30', '2026-09-18T16:33:45', 'unisex', 0.0, NULL, 'Fruity'),
(162, 'Ignite oud', NULL, 'Ahmed Al Maghribi', 'Brand Perfume', TRUE, '2026-09-06T19:04:54', '2026-09-18T16:33:42', 'unisex', 0.0, NULL, 'Oud'),
(159, 'Oputent Dubai', NULL, 'Oud Potent', 'Brand Perfume', TRUE, '2026-09-06T19:03:46', '2026-09-18T16:33:38', 'unisex', 0.0, NULL, 'Oud'),
(156, 'ANA ABIYEDH coral', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:01:58', '2026-09-18T16:33:32', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(155, 'Amber Oud Gold edition', NULL, 'Al Haramain', 'Brand Perfume', TRUE, '2026-09-06T19:01:14', '2026-09-18T16:33:30', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(152, 'Supremacy Gala', NULL, 'Afnan', 'Brand Perfume', TRUE, '2026-09-06T19:00:04', '2026-09-18T16:33:26', 'female', 0.0, NULL, 'Floral'),
(146, 'HAWAS Pink', NULL, 'Rasasi', 'Brand Perfume', TRUE, '2026-09-06T18:57:08', '2026-09-18T16:33:16', 'female', 0.0, NULL, 'Fruity'),
(145, 'INFINITY', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T18:56:41', '2026-09-18T16:33:15', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(138, 'ASWAAR', NULL, 'Riiffs', 'Brand Perfume', TRUE, '2026-09-06T18:52:47', '2026-09-18T16:33:03', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(137, 'XER JOFF', NULL, 'Xerjoff', 'Brand Perfume', TRUE, '2026-09-06T18:52:26', '2026-09-18T16:33:01', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(135, 'My Burberry Black', NULL, 'Burberry', 'Brand Perfume', TRUE, '2026-09-06T18:51:32', '2026-09-18T16:32:59', 'female', 0.0, NULL, 'Floral'),
(134, 'UNIQUE''E LUXURY CRUSH ON ME', NULL, 'Unique''e Luxury', 'Brand Perfume', TRUE, '2026-09-06T18:51:02', '2026-09-18T16:32:57', 'unisex', 0.0, NULL, 'Fruity'),
(133, 'Black Opium', NULL, 'Yves Saint Laurent', 'Brand Perfume', TRUE, '2026-09-06T18:50:13', '2026-09-18T16:32:55', 'female', 0.0, NULL, 'Gourmand'),
(132, 'Stronger with you intensely', NULL, 'Emporio Armani', 'Brand Perfume', TRUE, '2026-09-06T18:49:56', '2026-09-18T16:32:54', 'male', 0.0, NULL, 'Amber/Spicy'),
(129, 'Miss Dior', NULL, 'Dior', 'Brand Perfume', TRUE, '2026-09-06T18:48:48', '2026-09-18T16:32:50', 'female', 0.0, NULL, 'Floral'),
(126, 'Hibiscus Mahajad', NULL, 'Maison Crivelli', 'Brand Perfume', TRUE, '2026-09-06T18:46:57', '2026-09-18T16:32:47', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(124, 'Amouage Love hibiscus', NULL, 'Amouage', 'Brand Perfume', TRUE, '2026-09-06T18:45:09', '2026-09-18T16:32:43', 'unisex', 0.0, NULL, 'Floral'),
(123, 'Amouage Guidance 46', NULL, 'Amouage', 'Brand Perfume', TRUE, '2026-09-06T18:44:39', '2026-09-18T16:32:41', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(119, 'Lady Reef', NULL, 'Reef', 'Brand Perfume', TRUE, '2026-09-06T18:42:37', '2026-09-18T16:32:35', 'female', 0.0, NULL, 'Floral'),
(161, 'Extract', NULL, 'World Choice Perfumes', 'Brand Perfume', FALSE, '2026-09-06T19:04:33', '2026-09-24T08:20:28', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(118, 'Billie Eilish', NULL, 'Billie Eilish', 'Brand Perfume', TRUE, '2026-09-06T18:42:22', '2026-09-18T16:32:33', 'female', 0.0, NULL, 'Gourmand'),
(114, 'Vanilla candy', NULL, 'Kayali', 'Brand Perfume', TRUE, '2026-09-06T18:38:09', '2026-09-18T16:32:27', 'female', 0.0, NULL, 'Gourmand'),
(103, 'Pure Seduction', NULL, 'Victoria''s Secret', 'Oil Fragrance', TRUE, '2026-09-06T16:57:38', '2026-09-18T16:32:11', 'female', 0.0, NULL, 'Fruity'),
(117, 'Dolce & Gabbana The one', NULL, 'Dolce & Gabbana', 'Brand Perfume', FALSE, '2026-09-06T18:41:38', '2026-09-21T16:27:48', 'female', 0.0, NULL, 'Amber/Spicy'),
(116, 'Yum Pistachio Gelato', NULL, 'Kayali', 'Brand Perfume', TRUE, '2026-09-06T18:39:25', '2026-09-18T16:32:29', 'female', 0.0, NULL, 'Gourmand'),
(113, 'Yum boujee Marshimallow 2 intense', NULL, 'Kayali', 'Brand Perfume', FALSE, '2026-09-06T18:37:41', '2026-09-21T16:46:18', 'female', 0.0, NULL, 'Gourmand'),
(112, 'Yum boujee Marshimallow intense', NULL, 'Kayali', 'Brand Perfume', TRUE, '2026-09-06T18:36:50', '2026-09-18T16:32:23', 'female', 0.0, NULL, 'Gourmand'),
(105, 'Supremacy Afnan', NULL, 'Afnan', 'Oil Fragrance', TRUE, '2026-09-06T16:58:42', '2026-09-18T16:32:13', 'male', 0.0, NULL, 'Fruity'),
(111, 'KAY ALI FREEDO MUSK SANTAL', NULL, 'Kayali', 'Brand Perfume', TRUE, '2026-09-06T18:36:20', '2026-09-18T16:32:21', 'unisex', 0.0, NULL, 'Wood'),
(109, 'Givenchy Irresistible', NULL, 'Givenchy', 'Brand Perfume', TRUE, '2026-09-06T18:35:09', '2026-09-18T16:32:18', 'female', 0.0, NULL, 'Floral'),
(197, 'Giorgio Black Special edition', NULL, 'Giorgio Beverly Hills', 'Brand Perfume', TRUE, '2026-09-06T19:33:28', '2026-09-12T20:07:02', 'male', 0.0, NULL, 'Amber/Spicy'),
(196, 'Ely Sia Vanilla Sugar', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T19:32:56', '2026-09-12T20:07:01', 'female', 0.0, NULL, 'Gourmand'),
(212, 'Electric Turath', NULL, 'Afnan', 'Brand Perfume', TRUE, '2026-09-06T19:40:09', '2026-09-18T16:39:03', 'unisex', 0.0, NULL, 'Wood'),
(228, 'Sugar Lollipop', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T19:45:59', '2026-09-18T16:39:36', 'female', 0.0, NULL, 'Gourmand'),
(208, 'Supremacy Silver', NULL, 'Afnan', 'Brand Perfume', TRUE, '2026-09-06T19:37:37', '2026-09-12T20:07:23', 'male', 0.0, NULL, 'Fruity'),
(207, 'Supremacy in heaven', NULL, 'Afnan', 'Brand Perfume', TRUE, '2026-09-06T19:37:15', '2026-09-12T20:07:21', 'unisex', 0.0, NULL, 'Fruity'),
(206, 'Supremacy not only intense', NULL, 'Afnan', 'Brand Perfume', TRUE, '2026-09-06T19:36:51', '2026-09-12T20:07:19', 'male', 0.0, NULL, 'Fruity'),
(195, 'Khair Peach Delulu', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T19:32:26', '2026-09-12T20:06:59', 'unisex', 0.0, NULL, 'Fruity'),
(231, 'Sugar Kiss', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T19:46:57', '2026-09-18T16:39:42', 'female', 0.0, NULL, 'Gourmand'),
(260, 'Pink blush', NULL, 'Ard Al Zaafaran', 'Brand Perfume', TRUE, '2026-09-06T19:57:14', '2026-09-12T20:08:50', 'female', 0.0, NULL, 'Floral'),
(249, 'Eclaire', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:52:47', '2026-09-12T20:08:32', 'female', 0.0, NULL, 'Gourmand'),
(251, 'Victoria Lattafa', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:53:34', '2026-09-12T20:08:35', 'female', 0.0, NULL, 'Floral'),
(250, 'Eshal Vanila', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T19:53:12', '2026-09-12T20:08:34', 'female', 0.0, NULL, 'Gourmand'),
(245, 'Taskeen Caramel cascade', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T19:51:32', '2026-09-12T20:08:25', 'unisex', 0.0, NULL, 'Gourmand'),
(263, 'Legend', NULL, 'Montblanc', 'Brand Perfume', TRUE, '2026-09-06T20:00:49', '2026-09-12T20:08:55', 'male', 0.0, NULL, 'Aromatic'),
(244, 'Angham', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:50:56', '2026-09-12T20:08:24', 'female', 0.0, NULL, 'Floral'),
(243, 'Now Women', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:50:28', '2026-09-12T20:08:22', 'female', 0.0, NULL, 'Floral'),
(240, 'YARA candy', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:49:23', '2026-09-12T20:08:17', 'female', 0.0, NULL, 'Gourmand'),
(239, 'YARA elixir', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:49:05', '2026-09-12T20:08:16', 'female', 0.0, NULL, 'Gourmand'),
(237, 'Now white', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:48:34', '2026-09-12T20:08:12', 'unisex', 0.0, NULL, 'Floral'),
(193, 'Couture Noir', NULL, 'Ahmed Al Maghribi', 'Brand Perfume', TRUE, '2026-09-06T19:31:05', '2026-09-18T16:34:41', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(235, 'Candy Bite', NULL, 'Gulf Orchid', 'Brand Perfume', TRUE, '2026-09-06T19:48:00', '2026-09-18T16:39:57', 'unisex', 0.0, NULL, 'Gourmand'),
(234, 'Pride pour Home', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T19:47:47', '2026-09-18T16:39:54', 'male', 0.0, NULL, 'Aromatic'),
(227, 'Sugar Rush', NULL, NULL, 'Brand Perfume', TRUE, '2026-09-06T19:45:43', '2026-09-18T16:39:34', 'female', 0.0, NULL, 'Gourmand'),
(230, 'Sugar punch', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T19:46:42', '2026-09-18T16:39:38', 'female', 0.0, NULL, 'Gourmand'),
(223, 'Vanilla Voyage', NULL, 'Maison Asrar', 'Brand Perfume', TRUE, '2026-09-06T19:44:28', '2026-09-18T16:39:24', 'unisex', 0.0, NULL, 'Gourmand'),
(268, 'Night Club Green Tweed', NULL, 'Fragrance World', 'Brand Perfume', TRUE, '2026-09-06T20:03:05', '2026-09-12T20:09:03', 'male', 0.0, NULL, 'Aromatic'),
(221, 'Club De Nuit Lion heart woman', NULL, 'Armaf', 'Brand Perfume', TRUE, '2026-09-06T19:43:48', '2026-09-18T16:39:21', 'female', 0.0, NULL, 'Fruity'),
(205, 'Supremacy Collectors Edition', NULL, 'Afnan', 'Brand Perfume', TRUE, '2026-09-06T19:36:29', '2026-09-12T20:07:17', 'male', 0.0, NULL, 'Fruity'),
(220, 'Club De Nuit lion heart man', NULL, 'Armaf', 'Brand Perfume', TRUE, '2026-09-06T19:43:23', '2026-09-18T16:39:19', 'male', 0.0, NULL, 'Fruity'),
(217, 'Club De Nuit iconic', NULL, 'Armaf', 'Brand Perfume', TRUE, '2026-09-06T19:42:14', '2026-09-18T16:39:13', 'male', 0.0, NULL, 'Aromatic'),
(214, 'Marj', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T19:40:45', '2026-09-18T16:39:07', 'female', 0.0, NULL, 'Floral'),
(203, 'Kingdom', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:35:46', '2026-09-12T20:07:13', 'male', 0.0, NULL, 'Amber/Spicy'),
(202, 'Tonquin Giza Rayhan', NULL, 'Rayhaan', 'Brand Perfume', TRUE, '2026-09-06T19:35:32', '2026-09-18T16:36:47', 'male', 0.0, NULL, 'Amber/Spicy'),
(200, 'Petra', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:34:46', '2026-09-12T20:07:08', 'female', 0.0, NULL, 'Floral'),
(198, 'Indomitable', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T19:34:09', '2026-09-12T20:07:04', 'unisex', 0.0, NULL, 'Amber/Spicy'),
(192, 'Nebras', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:27:23', '2026-09-18T16:34:36', 'female', 0.0, NULL, 'Gourmand'),
(344, 'LADY MILLION', NULL, NULL, 'Oil Fragrance', TRUE, '2026-09-19T15:17:10', '2026-09-19T15:17:10', 'female', 0.0, NULL, NULL),
(191, 'Freeze', NULL, 'Riiffs', 'Brand Perfume', TRUE, '2026-09-06T19:26:58', '2026-09-18T16:34:33', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(188, 'Nebras Elixir', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:25:32', '2026-09-18T16:34:29', 'female', 0.0, NULL, 'Gourmand'),
(187, 'Taskeen Wowie', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T19:25:01', '2026-09-18T16:34:27', 'unisex', 0.0, NULL, 'Fruity'),
(186, 'Teriaq intense', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:24:38', '2026-09-18T16:34:19', 'female', 0.0, NULL, 'Gourmand'),
(218, 'Club De Nuit intense man', NULL, 'Armaf', 'Brand Perfume', FALSE, '2026-09-06T19:42:38', '2026-09-17T16:31:37', 'male', 0.0, NULL, 'Fruity'),
(253, 'SWISS ARABIAN CASABLANCA', NULL, 'Swiss Arabian', 'Brand Perfume', TRUE, '2026-09-06T19:54:26', '2026-09-12T20:08:39', 'unisex', 0.0, NULL, 'Floral'),
(254, 'FAYORA', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T19:54:42', '2026-09-12T20:08:40', 'female', 0.0, NULL, 'Floral'),
(269, 'Lail Maleki', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T20:03:31', '2026-09-12T20:09:05', 'unisex', 0.0, NULL, 'Floral'),
(261, 'Affection', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T19:58:19', '2026-09-12T20:08:52', 'unisex', 0.0, NULL, 'Gourmand'),
(275, 'FLORENZA', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T20:06:29', '2026-09-12T20:09:14', 'female', 0.0, NULL, 'Floral'),
(278, 'Creme Of Clouds', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T20:07:45', '2026-09-12T20:09:19', 'female', 0.0, NULL, 'Gourmand'),
(282, 'Delila pour Femme', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T20:09:50', '2026-09-12T20:09:27', 'female', 0.0, NULL, 'Floral'),
(291, 'SHALINA ROYAL ESSENCE', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T20:14:26', '2026-09-12T20:09:43', 'female', 0.0, NULL, 'Floral'),
(298, 'WAYFARES INFUSION', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T20:17:02', '2026-09-12T20:09:55', 'unisex', 0.0, NULL, 'Aromatic'),
(166, 'Lynked Freedom', NULL, 'Afnan', 'Brand Perfume', TRUE, '2026-09-06T19:17:32', '2026-09-18T16:33:47', 'male', 0.0, NULL, 'Fresh/Citrus'),
(154, 'CIAO citrus', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T19:00:47', '2026-09-18T16:33:28', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(326, 'Blue de Channel', NULL, 'Chanel', 'Oil Fragrance', TRUE, '2026-09-14T16:11:33', '2026-09-14T16:11:33', 'unisex', 0.0, NULL, 'Aromatic'),
(229, 'Sugar Marshmallow', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T19:46:29', '2026-09-12T20:08:00', 'female', 0.0, NULL, 'Gourmand'),
(213, 'HAWAS ice', NULL, 'Rasasi', 'Brand Perfume', TRUE, '2026-09-06T19:40:24', '2026-09-18T16:39:05', 'male', 0.0, NULL, 'Fresh/Citrus'),
(201, 'RAYHAN', NULL, 'Rayhaan', 'Brand Perfume', TRUE, '2026-09-06T19:35:02', '2026-09-18T16:36:41', 'male', 0.0, NULL, 'Fresh/Citrus'),
(182, 'Season Drift', NULL, 'Riiffs', 'Brand Perfume', TRUE, '2026-09-06T19:23:32', '2026-09-18T16:34:11', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(170, 'Ravin Ginger', NULL, 'World Choice Perfumes', 'Brand Perfume', TRUE, '2026-09-06T19:19:08', '2026-09-18T16:33:52', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(151, 'Reef 33 white', NULL, 'Reef', 'Brand Perfume', TRUE, '2026-09-06T18:59:19', '2026-09-18T16:33:24', 'male', 0.0, NULL, 'Fresh/Citrus'),
(150, 'REEF Summer', NULL, 'Reef', 'Brand Perfume', TRUE, '2026-09-06T18:59:00', '2026-09-18T16:33:22', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(147, 'HAWAS viper', NULL, 'Rasasi', 'Brand Perfume', TRUE, '2026-09-06T18:57:25', '2026-09-18T16:33:18', 'male', 0.0, NULL, 'Fresh/Citrus'),
(40, 'Blue Talisman Ex Nihilo', NULL, 'Ex Nihilo', 'Oil Fragrance', TRUE, '2026-09-06T16:18:26', '2026-09-18T16:30:22', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(144, 'RAYHAN AZUL', NULL, 'Rayhaan', 'Brand Perfume', TRUE, '2026-09-06T18:56:25', '2026-09-18T16:33:13', 'male', 0.0, NULL, 'Fresh/Citrus'),
(143, 'RAYHAN AQUATICA', NULL, 'Rayhaan', 'Brand Perfume', TRUE, '2026-09-06T18:55:45', '2026-09-18T16:33:11', 'male', 0.0, NULL, 'Fresh/Citrus'),
(43, 'Eden Juicy Apple', NULL, 'Kayali', 'Oil Fragrance', TRUE, '2026-09-06T16:20:03', '2026-09-18T16:30:28', 'female', 0.0, NULL, 'Fruity'),
(271, 'MAWJ APPLETINI', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T20:04:15', '2026-09-12T20:09:07', 'unisex', 0.0, NULL, 'Fruity'),
(41, 'Hugo Boss Bottle Bold Citrus Men', NULL, 'Hugo Boss', 'Oil Fragrance', TRUE, '2026-09-06T16:19:06', '2026-09-18T16:30:24', 'male', 0.0, NULL, 'Fresh/Citrus'),
(60, 'Polo Blue', NULL, 'Ralph Lauren', 'Oil Fragrance', TRUE, '2026-09-06T16:31:24', '2026-09-18T16:30:57', 'male', 0.0, NULL, 'Fresh/Citrus'),
(247, '9 pm Black', NULL, 'Afnan', 'Brand Perfume', FALSE, '2026-09-06T19:52:02', '2026-09-12T20:08:29', 'male', 0.0, NULL, 'Amber/Spicy'),
(65, 'Invictus', NULL, 'Paco Rabanne', 'Oil Fragrance', TRUE, '2026-09-06T16:35:26', '2026-09-18T16:31:04', 'male', 0.0, NULL, 'Fresh/Citrus'),
(85, 'Lacoste White', NULL, 'Lacoste', 'Oil Fragrance', TRUE, '2026-09-06T16:46:23', '2026-09-18T16:31:34', 'male', 0.0, NULL, 'Fresh/Citrus'),
(90, 'CR7 Legacy', NULL, 'Cristiano Ronaldo', 'Oil Fragrance', TRUE, '2026-09-06T16:48:50', '2026-09-18T16:31:46', 'male', 0.0, NULL, 'Fresh/Citrus'),
(96, 'Blue Talisman Ex Nihilo-Top', NULL, 'Ex Nihilo', 'Oil Fragrance', TRUE, '2026-09-06T16:51:47', '2026-09-18T16:31:59', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(277, 'Taskeen', NULL, 'Paris Corner', 'Brand Perfume', TRUE, '2026-09-06T20:07:04', '2026-09-12T20:09:18', 'unisex', 0.0, NULL, 'Fruity'),
(130, 'MEGAMARE', NULL, 'Xerjoff', 'Brand Perfume', TRUE, '2026-09-06T18:49:11', '2026-09-18T16:32:52', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(334, 'Diffuser', NULL, NULL, 'Brand Perfume', FALSE, '2026-09-17T14:00:39', '2026-09-17T14:04:46', 'accessories', 0.0, NULL, NULL),
(276, 'Valentino Donna', NULL, 'Valentino', 'Brand Perfume', TRUE, '2026-09-06T20:06:49', '2026-09-12T20:09:16', 'female', 0.0, NULL, 'Gourmand'),
(125, 'Ex Nihilo Blue Talisman', NULL, 'Ex Nihilo', 'Brand Perfume', TRUE, '2026-09-06T18:46:25', '2026-09-18T16:32:45', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(122, 'Louis Vuitton Afternoon swim', NULL, 'Louis Vuitton', 'Brand Perfume', TRUE, '2026-09-06T18:44:12', '2026-09-18T16:32:39', 'unisex', 0.0, NULL, 'Fresh/Citrus'),
(309, 'SWISS ARABIAN ROSE 01', NULL, 'Swiss Arabian', 'Brand Perfume', TRUE, '2026-09-06T20:26:43', '2026-09-12T20:10:13', 'unisex', 0.0, NULL, 'Floral'),
(312, 'SWISS ARABIAN ESSENCE OF CASABLANCA', NULL, 'Swiss Arabian', 'Brand Perfume', TRUE, '2026-09-06T20:28:12', '2026-09-12T20:10:19', 'unisex', 0.0, NULL, 'Floral');
INSERT INTO public.products (id, name, description, brand, category, is_active, created_at, updated_at, sex_category, unit_cost, costing_volume, fundamental_ingredient) VALUES
(284, 'BADE''E AL OUD SUBLIME', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T20:10:49', '2026-09-12T20:09:31', 'unisex', 0.0, NULL, 'Oud'),
(345, 'Test perfume', 'fragrance', 'Creed', 'Oil Fragrance', FALSE, '2026-09-19T15:44:36', '2026-09-25T08:02:20', 'female', 0.0, NULL, 'Wood'),
(285, 'BADE''E AL OUD honour and glory', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T20:11:33', '2026-09-12T20:09:33', 'unisex', 0.0, NULL, 'Oud'),
(322, 'Empty Bottle 100ml', NULL, 'Empty Bottles', NULL, FALSE, '2026-09-13T10:14:10', '2026-09-13T10:14:10', NULL, 0.0, NULL, NULL),
(288, 'Liquid Brun', NULL, 'French Avenue', 'Brand Perfume', TRUE, '2026-09-06T20:12:41', '2026-09-12T20:09:38', 'unisex', 0.0, NULL, 'Gourmand'),
(290, 'Unique extremely Pista', NULL, 'Unique''e Luxury', 'Brand Perfume', TRUE, '2026-09-06T20:13:52', '2026-09-12T20:09:42', 'unisex', 0.0, NULL, 'Gourmand'),
(267, 'Meethaq', NULL, 'Ard Al Zaafaran', 'Brand Perfume', TRUE, '2026-09-06T20:02:38', '2026-09-12T20:09:02', 'male', 0.0, NULL, 'Amber/Spicy'),
(286, 'BADE''E AL OUD for glory', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T20:11:53', '2026-09-12T20:09:35', 'male', 0.0, NULL, 'Oud'),
(307, 'SWISS ARABIAN VANILLA 01', NULL, 'Swiss Arabian', 'Brand Perfume', TRUE, '2026-09-06T20:25:58', '2026-09-18T16:30:09', 'unisex', 0.0, NULL, 'Gourmand'),
(297, 'Khashabi', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T20:16:41', '2026-09-12T20:09:53', 'unisex', 0.0, NULL, 'Wood'),
(304, 'Room Spray', NULL, NULL, 'Brand Perfume', TRUE, '2026-09-06T20:22:27', '2026-09-12T20:10:05', 'accessories', 0.0, NULL, NULL),
(311, 'SWISS ARABIAN PATCHOULI 01', NULL, 'Swiss Arabian', 'Brand Perfume', TRUE, '2026-09-06T20:27:45', '2026-09-12T20:10:17', 'unisex', 0.0, NULL, 'Wood'),
(303, 'Diffuser', NULL, NULL, 'Brand Perfume', FALSE, '2026-09-06T20:21:59', '2026-09-17T13:48:09', 'accessories', 0.0, NULL, NULL),
(287, 'BADE''E AL OUD AMETHYST', NULL, 'Lattafa', 'Brand Perfume', TRUE, '2026-09-06T20:12:22', '2026-09-12T20:09:37', 'unisex', 0.0, NULL, 'Oud'),
(308, 'SWISS ARABIAN OUD 01', NULL, 'Swiss Arabian', 'Brand Perfume', TRUE, '2026-09-06T20:26:22', '2026-09-12T20:10:11', 'unisex', 0.0, NULL, 'Oud');

-- product_images (208 rows)
INSERT INTO public.product_images (id, product_id, image_url, sort_order, created_at, updated_at) VALUES
(1, 330, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789573615/products/Barcode%20Autograph.webp_1789573615.webp', 0, '2026-09-16T15:46:56', '2026-09-16T15:46:56'),
(2, 331, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789641433/products/imigation.png_1789641432.png', 0, '2026-09-17T10:37:13', '2026-09-17T10:37:13'),
(4, 218, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789644238/products/Barcode%20Autograph.webp_1789644238.webp', 0, '2026-09-17T11:23:58', '2026-09-17T11:23:58'),
(5, 312, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789651312/products/SWISS%20ARABIAN%20ESSENCE%20OF%20CASABLANCA.webp_1789651311.webp', 0, '2026-09-17T13:21:52', '2026-09-17T13:21:52'),
(6, 311, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789651555/products/swiss%20arabian%20patchouli.jpg_1789651555.jpg', 0, '2026-09-17T13:25:56', '2026-09-17T13:25:56'),
(7, 310, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789651584/products/swiss%20incense%2001.jpg_1789651584.jpg', 0, '2026-09-17T13:26:25', '2026-09-17T13:26:25'),
(8, 309, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789651618/products/swiss%20arabian%20rose%2001.jpg_1789651617.jpg', 0, '2026-09-17T13:26:58', '2026-09-17T13:26:58'),
(9, 308, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789651679/products/swiss%20arabian%20oud%2001.jpg_1789651679.jpg', 0, '2026-09-17T13:28:00', '2026-09-17T13:28:00'),
(10, 307, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789651705/products/swiss%20arabian%20vanilla%2001.jpg_1789651704.jpg', 0, '2026-09-17T13:28:25', '2026-09-17T13:28:25'),
(11, 306, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789651769/products/SWISS%20ARABIAN%20musk%20%2001.jpg_1789651769.jpg', 0, '2026-09-17T13:29:29', '2026-09-17T13:29:29'),
(12, 305, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789651802/products/SWISS%20ARABIAN%20SHANGAF%20OUD%20TONKA.jpg_1789651802.jpg', 0, '2026-09-17T13:30:03', '2026-09-17T13:30:03'),
(13, 304, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789652096/products/Room%20Spray.webp_1789652095.webp', 0, '2026-09-17T13:34:56', '2026-09-17T13:34:56'),
(14, 303, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789652416/products/images.jpg_1789652416.jpg', 0, '2026-09-17T13:40:16', '2026-09-17T13:40:16'),
(15, 295, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789652962/products/RAMZ%20lattafa%20gold.jpg_1789652962.jpg', 0, '2026-09-17T13:49:23', '2026-09-17T13:49:23'),
(16, 299, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789653008/products/zodiac-solinaris.jpg_1789653008.jpg', 0, '2026-09-17T13:50:10', '2026-09-17T13:50:10'),
(17, 298, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789653061/products/WAYFARER-INFUSION.png_1789653061.png', 0, '2026-09-17T13:51:02', '2026-09-17T13:51:02'),
(18, 297, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789653091/products/Khashabi.webp_1789653091.webp', 0, '2026-09-17T13:51:32', '2026-09-17T13:51:32'),
(19, 296, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789653130/products/ASAD%20Elixir.webp_1789653130.webp', 0, '2026-09-17T13:52:11', '2026-09-17T13:52:11'),
(20, 294, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789653171/products/Barcode-Signature-For-Men-100ml.png_1789653170.png', 0, '2026-09-17T13:52:51', '2026-09-17T13:52:51'),
(21, 293, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789653213/products/Barcode%20Autograph.webp_1789653212.webp', 0, '2026-09-17T13:53:33', '2026-09-17T13:53:33'),
(22, 333, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789653406/products/images.jpg_1789653406.jpg', 0, '2026-09-17T13:56:46', '2026-09-17T13:56:46'),
(23, 334, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789653838/products/diffuser.jpg_1789653838.jpg', 0, '2026-09-17T14:03:59', '2026-09-17T14:03:59'),
(24, 291, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789655707/products/SHALINA%20ROYAL%20ESSENCE.jpeg_1789655707.jpg', 0, '2026-09-17T14:35:08', '2026-09-17T14:35:08'),
(25, 292, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789656727/products/ramz%20silver.jpg_1789656727.jpg', 0, '2026-09-17T14:52:08', '2026-09-17T14:52:08'),
(26, 290, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789656920/products/Unique%20extremely%20Pista.jpg_1789656920.jpg', 0, '2026-09-17T14:55:20', '2026-09-17T14:55:20'),
(27, 289, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789657149/products/Art%20Of%20Nature.jpg_1789657149.jpg', 0, '2026-09-17T14:59:10', '2026-09-17T14:59:10'),
(28, 288, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789657314/products/Liquid%20Brun.jpg_1789657314.jpg', 0, '2026-09-17T15:01:55', '2026-09-17T15:01:55'),
(29, 287, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789657497/products/BADE%27E%20AL%20OUD%20AMETHYST.jpg_1789657496.jpg', 0, '2026-09-17T15:04:57', '2026-09-17T15:04:57'),
(30, 286, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789657576/products/BADE%27E%20AL%20OUD%20for%20glory.jpg_1789657576.jpg', 0, '2026-09-17T15:06:16', '2026-09-17T15:06:16'),
(31, 285, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789657642/products/BADE%27E%20AL%20OUD%20honour%20and%20glory.jpg_1789657642.jpg', 0, '2026-09-17T15:07:23', '2026-09-17T15:07:23'),
(32, 284, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789657725/products/BADE%27E%20AL%20OUD%20SUBLIME.jpg_1789657725.jpg', 0, '2026-09-17T15:08:46', '2026-09-17T15:08:46'),
(33, 282, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789658055/products/Delila%20pour%20Femme.jpg_1789658054.jpg', 0, '2026-09-17T15:14:15', '2026-09-17T15:14:15'),
(34, 281, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789658145/products/Khamrah.jpg_1789658145.jpg', 0, '2026-09-17T15:15:46', '2026-09-17T15:15:46'),
(35, 278, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789658426/products/Creme%20Of%20Clouds.jpg_1789658426.jpg', 0, '2026-09-17T15:20:27', '2026-09-17T15:20:27'),
(36, 277, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789658579/products/Taskeen.jpg_1789658578.jpg', 0, '2026-09-17T15:22:59', '2026-09-17T15:22:59'),
(37, 108, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789658965/products/Valentino%20Donna%20born%20in%20romma.jpg_1789658965.jpg', 0, '2026-09-17T15:29:26', '2026-09-17T15:29:26'),
(38, 276, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789659065/products/Valentino%20Donna.jpg_1789659065.jpg', 0, '2026-09-17T15:31:06', '2026-09-17T15:31:06'),
(39, 335, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789659355/products/diffuser.jpg_1789659355.jpg', 0, '2026-09-17T15:35:56', '2026-09-17T15:35:56'),
(40, 279, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789659504/products/Intense%20Man%20Essencia%20de%20flores.jpg_1789659504.jpg', 0, '2026-09-17T15:38:25', '2026-09-17T15:38:25'),
(41, 275, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789659868/products/FLORENZA.jpg_1789659867.jpg', 0, '2026-09-17T15:44:28', '2026-09-17T15:44:28'),
(42, 274, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789660042/products/Scepter%20Malachite.jpg_1789660042.jpg', 0, '2026-09-17T15:47:23', '2026-09-17T15:47:23'),
(43, 273, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789660190/products/RA%27ED%20LUXE.jpg_1789660189.jpg', 0, '2026-09-17T15:49:50', '2026-09-17T15:49:50'),
(44, 272, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789660273/products/OPHIDIAN.jpg_1789660272.jpg', 0, '2026-09-17T15:51:13', '2026-09-17T15:51:13'),
(45, 271, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789660446/products/MAWJ%20APPLETINI.jpg_1789660446.jpg', 0, '2026-09-17T15:54:07', '2026-09-17T15:54:07'),
(46, 270, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789660610/products/Qaed%20Al%20Fursan.jpg_1789660609.jpg', 0, '2026-09-17T15:56:50', '2026-09-17T15:56:50'),
(47, 269, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789660706/products/Lail%20Maleki.jpg_1789660706.jpg', 0, '2026-09-17T15:58:27', '2026-09-17T15:58:27'),
(48, 268, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789660835/products/Night%20Club%20Green%20Tweed.jpg_1789660835.jpg', 0, '2026-09-17T16:00:36', '2026-09-17T16:00:36'),
(49, 267, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789660963/products/Meethaq.jpg_1789660962.jpg', 0, '2026-09-17T16:02:43', '2026-09-17T16:02:43'),
(50, 266, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789661095/products/Al-nashana%20plane.jpg_1789661095.jpg', 0, '2026-09-17T16:04:55', '2026-09-17T16:04:55'),
(51, 265, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789661179/products/Al-nashana%20caprice.jpg_1789661179.jpg', 0, '2026-09-17T16:06:20', '2026-09-17T16:06:20'),
(52, 264, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789661334/products/Fusion%20intense.jpg_1789661334.jpg', 0, '2026-09-17T16:08:55', '2026-09-17T16:08:55'),
(53, 263, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789661456/products/Legend%20Montblanc.jpg_1789661456.jpg', 0, '2026-09-17T16:10:56', '2026-09-17T16:10:56'),
(54, 263, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789661459/products/Legend%20Montblanc.jpg_1789661458.jpg', 1, '2026-09-17T16:10:59', '2026-09-17T16:10:59'),
(55, 262, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789661534/products/Nebras%20New.jpg_1789661533.jpg', 0, '2026-09-17T16:12:14', '2026-09-17T16:12:14'),
(56, 261, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789661676/products/Affection.jpg_1789661676.jpg', 0, '2026-09-17T16:14:36', '2026-09-17T16:14:36'),
(57, 241, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789661953/products/fatima%20zimaya.jpg_1789661952.jpg', 0, '2026-09-17T16:19:13', '2026-09-17T16:19:13'),
(58, 259, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789662086/products/Night%20club.jpg_1789662086.jpg', 0, '2026-09-17T16:21:27', '2026-09-17T16:21:27'),
(59, 258, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789662171/products/Art%20of%20Universe.jpg_1789662171.jpg', 0, '2026-09-17T16:22:52', '2026-09-17T16:22:52'),
(60, 257, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789662326/products/Karus.jpg_1789662326.jpg', 0, '2026-09-17T16:25:27', '2026-09-17T16:25:27'),
(61, 256, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789662466/products/9%20pm%20pour%20femme.jpg_1789662466.jpg', 0, '2026-09-17T16:27:47', '2026-09-17T16:27:47'),
(62, 255, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789662592/products/Island%20Hadlaj.jpg_1789662591.jpg', 0, '2026-09-17T16:29:52', '2026-09-17T16:29:52'),
(63, 336, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789662822/products/club%20de%20nuit%20intense%20man.jpg_1789662822.jpg', 0, '2026-09-17T16:33:43', '2026-09-17T16:33:43'),
(64, 254, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789663042/products/FAYORA.jpg_1789663042.jpg', 0, '2026-09-17T16:37:22', '2026-09-17T16:37:22'),
(65, 253, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789663153/products/SWISS%20ARABIAN%20CASABLANCA.jpg_1789663152.jpg', 0, '2026-09-17T16:39:13', '2026-09-17T16:39:13'),
(66, 260, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789753837/products/pink%20blush.jpg_1789753837.jpg', 0, '2026-09-18T17:50:38', '2026-09-18T17:50:38'),
(67, 252, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789753981/products/Shaghaf%20Oud%20Tonka.jpg_1789753981.jpg', 0, '2026-09-18T17:53:01', '2026-09-18T17:53:01'),
(68, 251, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789754048/products/Victoria%20Lattafa.jpg_1789754047.jpg', 0, '2026-09-18T17:54:08', '2026-09-18T17:54:08'),
(69, 250, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789754330/products/Eshal%20Vanila.jpg_1789754330.jpg', 0, '2026-09-18T17:58:51', '2026-09-18T17:58:51'),
(70, 249, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789754430/products/Eclaire.jpg_1789754430.jpg', 0, '2026-09-18T18:00:31', '2026-09-18T18:00:31'),
(71, 248, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789754674/products/Atlas.jpg_1789754673.jpg', 0, '2026-09-18T18:04:34', '2026-09-18T18:04:34'),
(72, 246, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789754740/products/MARMARA.jpg_1789754739.jpg', 0, '2026-09-18T18:05:40', '2026-09-18T18:05:40'),
(73, 245, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789754829/products/Taskeen%20Caramel%20cascade.jpg_1789754829.jpg', 0, '2026-09-18T18:07:10', '2026-09-18T18:07:10'),
(74, 244, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789754901/products/Angham.jpg_1789754900.jpg', 0, '2026-09-18T18:08:21', '2026-09-18T18:08:21'),
(75, 280, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789755402/products/Faris%20Al%20Atrab.jpg_1789755402.jpg', 0, '2026-09-18T18:16:42', '2026-09-18T18:16:42'),
(76, 243, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789756319/products/Now%20Women.jpg_1789756319.jpg', 0, '2026-09-18T18:32:00', '2026-09-18T18:32:00'),
(77, 242, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789756423/products/Marshmallow%20Blush.jpg_1789756423.jpg', 0, '2026-09-18T18:33:44', '2026-09-18T18:33:44'),
(78, 240, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789756556/products/YARA%20candy.jpg_1789756555.jpg', 0, '2026-09-18T18:35:56', '2026-09-18T18:35:56'),
(79, 239, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789756840/products/YARA%20elixir.jpg_1789756839.jpg', 0, '2026-09-18T18:40:40', '2026-09-18T18:40:40'),
(80, 238, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789757000/products/Now%20Black.jpg_1789757000.jpg', 0, '2026-09-18T18:43:20', '2026-09-18T18:43:20'),
(81, 237, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789757113/products/Now%20white.jpg_1789757113.jpg', 0, '2026-09-18T18:45:14', '2026-09-18T18:45:14'),
(82, 236, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789757277/products/Cookie%20Bite.jpg_1789757277.jpg', 0, '2026-09-18T18:47:58', '2026-09-18T18:47:58'),
(83, 235, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789757397/products/Candy%20Bite.jpg_1789757397.jpg', 0, '2026-09-18T18:49:58', '2026-09-18T18:49:58'),
(84, 234, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789757637/products/Pride%20pour%20Home.jpg_1789757636.jpg', 0, '2026-09-18T18:53:57', '2026-09-18T18:53:57'),
(85, 233, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789758140/products/Pride%20Intense.jpg_1789758140.jpg', 0, '2026-09-18T19:02:21', '2026-09-18T19:02:21'),
(86, 232, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789758355/products/Intense%20Noir.jpg_1789758354.jpg', 0, '2026-09-18T19:05:55', '2026-09-18T19:05:55'),
(87, 337, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789807201/products/sugar%20candy.jpg_1789807200.jpg', 0, '2026-09-19T08:40:01', '2026-09-19T08:40:01'),
(88, 227, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789808011/products/sugar%20rush%202.jpg_1789808011.jpg', 0, '2026-09-19T08:53:32', '2026-09-19T08:53:32'),
(89, 228, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789808438/products/sugar%20lollipop.jpg_1789808438.jpg', 0, '2026-09-19T09:00:39', '2026-09-19T09:00:39'),
(90, 229, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789808520/products/sugar%20marshmallow.jpg_1789808520.jpg', 0, '2026-09-19T09:02:00', '2026-09-19T09:02:00'),
(91, 231, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789808808/products/sugar%20kiss.jpg_1789808808.jpg', 0, '2026-09-19T09:06:49', '2026-09-19T09:06:49'),
(92, 230, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789808933/products/sugar%20punch.jpg_1789808932.jpg', 0, '2026-09-19T09:08:53', '2026-09-19T09:08:53'),
(93, 226, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789809484/products/Khamrah%20Waha.jpg_1789809484.jpg', 0, '2026-09-19T09:18:04', '2026-09-19T09:18:04'),
(94, 225, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789809616/products/Ombre%20Dor.jpg_1789809615.jpg', 0, '2026-09-19T09:20:16', '2026-09-19T09:20:16'),
(95, 224, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789809846/products/Queen%20of%20Roses.jpg_1789809845.jpg', 0, '2026-09-19T09:24:06', '2026-09-19T09:24:06'),
(96, 223, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789810197/products/Vanilla%20Voyage.jpg_1789810197.jpg', 0, '2026-09-19T09:29:58', '2026-09-19T09:29:58'),
(97, 222, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789810356/products/Tiramisu%20Zimaya.jpg_1789810355.jpg', 0, '2026-09-19T09:32:36', '2026-09-19T09:32:36'),
(98, 221, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789811016/products/Club%20De%20Nuit%20Lion%20heart%20woman.jpg_1789811015.jpg', 0, '2026-09-19T09:43:36', '2026-09-19T09:43:36'),
(99, 220, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789811088/products/Club%20De%20Nuit%20Lion%20heart%20man.jpg_1789811088.jpg', 0, '2026-09-19T09:44:49', '2026-09-19T09:44:49'),
(100, 220, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789811124/products/Club%20De%20Nuit%20Lion%20heart%20man.jpg_1789811124.jpg', 1, '2026-09-19T09:45:25', '2026-09-19T09:45:25'),
(101, 219, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789811278/products/Club%20De%20Nuit%20urban%20man%20elixir.jpg_1789811278.jpg', 0, '2026-09-19T09:47:59', '2026-09-19T09:47:59'),
(102, 217, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789811394/products/Club%20De%20Nuit%20iconic.jpg_1789811394.jpg', 0, '2026-09-19T09:49:55', '2026-09-19T09:49:55'),
(103, 216, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789811512/products/Club%20De%20Nuit%20Maleka.jpg_1789811512.jpg', 0, '2026-09-19T09:51:53', '2026-09-19T09:51:53'),
(104, 215, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789811670/products/Club%20De%20Nuit%20imperial.jpg_1789811670.jpg', 0, '2026-09-19T09:54:31', '2026-09-19T09:54:31'),
(105, 214, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789811881/products/Marj.jpg_1789811881.jpg', 0, '2026-09-19T09:58:02', '2026-09-19T09:58:02'),
(106, 213, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789812012/products/HAWAS%20ice.jpg_1789812012.jpg', 0, '2026-09-19T10:00:13', '2026-09-19T10:00:13'),
(107, 212, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789812149/products/Electric%20Turath.jpg_1789812148.jpg', 0, '2026-09-19T10:02:29', '2026-09-19T10:02:29'),
(108, 211, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789812225/products/9%20pm%20night%20out.jpg_1789812225.jpg', 0, '2026-09-19T10:03:46', '2026-09-19T10:03:46'),
(109, 210, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789812322/products/9%20pm%20rebel.jpg_1789812321.jpg', 0, '2026-09-19T10:05:22', '2026-09-19T10:05:22'),
(110, 209, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789812452/products/9%20pm%20elixir.jpg_1789812452.jpg', 0, '2026-09-19T10:07:33', '2026-09-19T10:07:33'),
(111, 208, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789812527/products/Supremacy%20Silver.jpg_1789812527.jpg', 0, '2026-09-19T10:08:47', '2026-09-19T10:08:47'),
(112, 207, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789812660/products/Supremacy%20in%20heaven.jpg_1789812659.jpg', 0, '2026-09-19T10:11:00', '2026-09-19T10:11:00'),
(113, 206, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789813158/products/Supremacy%20not%20only%20intense.jpg_1789813158.jpg', 0, '2026-09-19T10:19:19', '2026-09-19T10:19:19'),
(114, 205, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789813350/products/Supremacy%20Collectors%20Edition.jpg_1789813349.jpg', 0, '2026-09-19T10:22:30', '2026-09-19T10:22:30'),
(115, 205, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789813389/products/Supremacy%20Collectors%20Edition.jpg_1789813388.jpg', 1, '2026-09-19T10:23:10', '2026-09-19T10:23:10'),
(116, 204, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789814074/products/preciux.jpg_1789814074.jpg', 0, '2026-09-19T10:34:34', '2026-09-19T10:34:34'),
(117, 203, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789814270/products/Kingdom.jpg_1789814269.jpg', 0, '2026-09-19T10:37:50', '2026-09-19T10:37:50'),
(118, 202, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789814381/products/Tonquin%20Giza%20Rayhan.jpg_1789814381.jpg', 0, '2026-09-19T10:39:42', '2026-09-19T10:39:42'),
(119, 201, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789815320/products/RAYHAN.jpg_1789815320.jpg', 0, '2026-09-19T10:55:21', '2026-09-19T10:55:21'),
(120, 200, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789815398/products/Petra.jpg_1789815397.jpg', 0, '2026-09-19T10:56:38', '2026-09-19T10:56:38'),
(121, 199, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789815503/products/Eternal%20Vanille.jpg_1789815503.jpg', 0, '2026-09-19T10:58:23', '2026-09-19T10:58:23'),
(122, 198, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789815727/products/Indomitable.jpg_1789815727.jpg', 0, '2026-09-19T11:02:08', '2026-09-19T11:02:08'),
(123, 197, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789815849/products/Giorgio%20Black%20Special%20edition.jpg_1789815848.jpg', 0, '2026-09-19T11:04:10', '2026-09-19T11:04:10'),
(124, 196, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789816078/products/Ely%20Sia%20Vanilla%20Sugar.jpg_1789816078.jpg', 0, '2026-09-19T11:07:59', '2026-09-19T11:07:59'),
(125, 195, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789816300/products/Khair%20Peach%20Delulu.jpg_1789816300.jpg', 0, '2026-09-19T11:11:41', '2026-09-19T11:11:41'),
(126, 194, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789816368/products/AZM.jpg_1789816367.jpg', 0, '2026-09-19T11:12:49', '2026-09-19T11:12:49'),
(127, 193, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789816481/products/Couture%20Noir.jpg_1789816481.jpg', 0, '2026-09-19T11:14:42', '2026-09-19T11:14:42'),
(128, 192, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789816628/products/Nebras.jpg_1789816628.jpg', 0, '2026-09-19T11:17:09', '2026-09-19T11:17:09'),
(129, 191, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789992081/products/Freeze.jpg_1789992080.jpg', 0, '2026-09-21T12:01:22', '2026-09-21T12:01:22'),
(130, 190, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789992406/products/Vanilla%20Addiction.jpg_1789992406.jpg', 0, '2026-09-21T12:06:47', '2026-09-21T12:06:47'),
(131, 189, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789992663/products/Raghba%20wood%20intense.jpg_1789992663.jpg', 0, '2026-09-21T12:11:04', '2026-09-21T12:11:04'),
(132, 188, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789993463/products/Nebras%20Elixir.jpg_1789993463.jpg', 0, '2026-09-21T12:24:23', '2026-09-21T12:24:23'),
(133, 187, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789993681/products/Taskeen%20Wowie.jpg_1789993681.jpg', 0, '2026-09-21T12:28:02', '2026-09-21T12:28:02'),
(134, 186, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789993787/products/Teriaq%20intense.jpg_1789993786.jpg', 0, '2026-09-21T12:29:48', '2026-09-21T12:29:48'),
(135, 185, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789993924/products/Kaaf.jpg_1789993924.jpg', 0, '2026-09-21T12:32:05', '2026-09-21T12:32:05'),
(136, 184, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789994215/products/Riwayah.jpg_1789994215.jpg', 0, '2026-09-21T12:36:56', '2026-09-21T12:36:56'),
(137, 183, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789994369/products/Vulcan%20Feu.jpg_1789994369.jpg', 0, '2026-09-21T12:39:30', '2026-09-21T12:39:30'),
(138, 182, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789994497/products/Season%20Drift.jpg_1789994497.jpg', 0, '2026-09-21T12:41:38', '2026-09-21T12:41:38'),
(139, 181, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789994634/products/Second%20Song%20Angham.jpg_1789994634.jpg', 0, '2026-09-21T12:43:55', '2026-09-21T12:43:55'),
(140, 180, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789994930/products/Dubai%20night%20Midnight.jpg_1789994930.jpg', 0, '2026-09-21T12:48:51', '2026-09-21T12:48:51'),
(141, 179, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789995095/products/Dubai%20night%20Umbra.jpg_1789995095.jpg', 0, '2026-09-21T12:51:36', '2026-09-21T12:51:36'),
(142, 178, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789995170/products/Vintage%20Radio.jpg_1789995169.jpg', 0, '2026-09-21T12:52:50', '2026-09-21T12:52:50'),
(143, 177, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789995245/products/Aromatic%20Magnetic.jpg_1789995244.jpg', 0, '2026-09-21T12:54:06', '2026-09-21T12:54:06'),
(144, 176, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789995333/products/Aromatic%20Forbidden%20fruit.jpg_1789995332.jpg', 0, '2026-09-21T12:55:33', '2026-09-21T12:55:33'),
(145, 175, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789995433/products/Aromatic%20FrostBite.jpg_1789995432.jpg', 0, '2026-09-21T12:57:13', '2026-09-21T12:57:13'),
(146, 174, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789995524/products/Marwa.jpg_1789995524.jpg', 0, '2026-09-21T12:58:45', '2026-09-21T12:58:45'),
(147, 173, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789995556/products/Marwa.jpg_1789995556.jpg', 0, '2026-09-21T12:59:17', '2026-09-21T12:59:17'),
(148, 172, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789995635/products/Private%20Key.jpg_1789995635.jpg', 0, '2026-09-21T13:00:36', '2026-09-21T13:00:36'),
(149, 171, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789995866/products/Yum%20yum.jpg_1789995866.jpg', 0, '2026-09-21T13:04:27', '2026-09-21T13:04:27'),
(150, 170, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789995975/products/Ravin%20Ginger.jpg_1789995975.jpg', 0, '2026-09-21T13:06:15', '2026-09-21T13:06:15'),
(151, 169, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789996090/products/Milk%20way.jpg_1789996090.jpg', 0, '2026-09-21T13:08:11', '2026-09-21T13:08:11');
INSERT INTO public.product_images (id, product_id, image_url, sort_order, created_at, updated_at) VALUES
(152, 168, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789996369/products/Vanguard.jpg_1789996368.jpg', 0, '2026-09-21T13:12:49', '2026-09-21T13:12:49'),
(153, 167, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789997072/products/Thriller%20III.jpg_1789997072.jpg', 0, '2026-09-21T13:24:33', '2026-09-21T13:24:33'),
(154, 167, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789997081/products/Thriller%20III.jpg_1789997080.jpg', 1, '2026-09-21T13:24:41', '2026-09-21T13:24:41'),
(155, 166, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789997207/products/Lynked%20Freedom.jpg_1789997207.jpg', 0, '2026-09-21T13:26:47', '2026-09-21T13:26:47'),
(156, 165, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789997277/products/AL-DIRGHAM.jpg_1789997276.jpg', 0, '2026-09-21T13:27:57', '2026-09-21T13:27:57'),
(157, 164, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789997598/products/Cocktail.jpg_1789997597.jpg', 0, '2026-09-21T13:33:18', '2026-09-21T13:33:18'),
(158, 162, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789998948/products/Ignite%20oud.jpg_1789998948.jpg', 0, '2026-09-21T13:55:49', '2026-09-21T13:55:49'),
(159, 160, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789999326/products/Life%20Journal%20perfume.jpg_1789999326.jpg', 0, '2026-09-21T14:02:06', '2026-09-21T14:02:06'),
(160, 159, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1789999462/products/Oputent%20Dubai.jpg_1789999462.jpg', 0, '2026-09-21T14:04:23', '2026-09-21T14:04:23'),
(161, 157, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790000219/products/Plum%20Liquor.jpg_1790000219.jpg', 0, '2026-09-21T14:17:00', '2026-09-21T14:17:00'),
(162, 156, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790000450/products/ANA%20ABIYEDH%20coral.jpg_1790000450.jpg', 0, '2026-09-21T14:20:51', '2026-09-21T14:20:51'),
(163, 155, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790000716/products/Amber%20Oud%20Gold%20edition.jpg_1790000716.jpg', 0, '2026-09-21T14:25:17', '2026-09-21T14:25:17'),
(164, 154, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790000790/products/CIAO%20citrus.jpg_1790000789.jpg', 0, '2026-09-21T14:26:30', '2026-09-21T14:26:30'),
(165, 153, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790000904/products/Safari%20Breeze.jpg_1790000903.jpg', 0, '2026-09-21T14:28:24', '2026-09-21T14:28:24'),
(166, 152, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790001017/products/Supremacy%20Gala.jpg_1790001017.jpg', 0, '2026-09-21T14:30:18', '2026-09-21T14:30:18'),
(167, 151, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790001647/products/Reef%2033%20white.jpg_1790001647.jpg', 0, '2026-09-21T14:40:47', '2026-09-21T14:40:47'),
(168, 150, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790001815/products/REEF%20Summer.jpg_1790001815.jpg', 0, '2026-09-21T14:43:36', '2026-09-21T14:43:36'),
(169, 149, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790001989/products/REEF%2033%20Black.jpg_1790001988.jpg', 0, '2026-09-21T14:46:30', '2026-09-21T14:46:30'),
(170, 148, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790002046/products/HAWAS%20London.jpg_1790002046.jpg', 0, '2026-09-21T14:47:26', '2026-09-21T14:47:26'),
(171, 147, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790002083/products/HAWAS%20viper.jpg_1790002082.jpg', 0, '2026-09-21T14:48:03', '2026-09-21T14:48:03'),
(172, 146, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790002118/products/HAWAS%20Pink.jpg_1790002118.jpg', 0, '2026-09-21T14:48:39', '2026-09-21T14:48:39'),
(173, 145, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790002234/products/INFINITY.jpg_1790002233.jpg', 0, '2026-09-21T14:50:34', '2026-09-21T14:50:34'),
(174, 144, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790002315/products/RAYHAN%20AZUL.jpg_1790002315.jpg', 0, '2026-09-21T14:51:55', '2026-09-21T14:51:55'),
(175, 143, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790002440/products/RAYHAN%20AQUATICA.jpg_1790002440.jpg', 0, '2026-09-21T14:54:01', '2026-09-21T14:54:01'),
(176, 142, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790002621/products/EMIR%20factor%20edition.jpg_1790002620.jpg', 0, '2026-09-21T14:57:01', '2026-09-21T14:57:01'),
(177, 139, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790002699/products/SEASONS%20RISE.jpg_1790002699.jpg', 0, '2026-09-21T14:58:19', '2026-09-21T14:58:19'),
(178, 141, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790003077/products/Club%20De%20Nuit%20Precieux%20iv.jpg_1790003077.jpg', 0, '2026-09-21T15:04:38', '2026-09-21T15:04:38'),
(179, 140, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790003452/products/overdose.jpg_1790003452.jpg', 0, '2026-09-21T15:10:53', '2026-09-21T15:10:53'),
(180, 138, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790003581/products/ASWAAR.jpg_1790003580.jpg', 0, '2026-09-21T15:13:02', '2026-09-21T15:13:02'),
(181, 137, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790003809/products/XER%20JOFF.jpg_1790003808.jpg', 0, '2026-09-21T15:16:49', '2026-09-21T15:16:49'),
(182, 136, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790003925/products/Burberry%20Her.jpg_1790003924.jpg', 0, '2026-09-21T15:18:45', '2026-09-21T15:18:45'),
(183, 135, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790004007/products/My%20Burberry%20Black.jpg_1790004007.jpg', 0, '2026-09-21T15:20:07', '2026-09-21T15:20:07'),
(184, 134, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790004128/products/UNIQUE%27E%20LUXURY%20CRUSH%20ON%20ME.jpg_1790004127.jpg', 0, '2026-09-21T15:22:08', '2026-09-21T15:22:08'),
(185, 133, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790004392/products/Black%20Opium.jpg_1790004391.jpg', 0, '2026-09-21T15:26:32', '2026-09-21T15:26:32'),
(186, 132, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790004536/products/Stronger%20with%20you%20intensely.jpg_1790004535.jpg', 0, '2026-09-21T15:28:56', '2026-09-21T15:28:56'),
(187, 131, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790004761/products/Tomford%20Ombre%20Leather.jpg_1790004760.jpg', 0, '2026-09-21T15:32:41', '2026-09-21T15:32:41'),
(188, 130, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790004876/products/MEGAMARE.jpg_1790004876.jpg', 0, '2026-09-21T15:34:37', '2026-09-21T15:34:37'),
(189, 129, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790005045/products/Miss%20Dior.jpg_1790005045.jpg', 0, '2026-09-21T15:37:25', '2026-09-21T15:37:25'),
(190, 128, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790005258/products/Kouros.jpg_1790005258.jpg', 0, '2026-09-21T15:40:58', '2026-09-21T15:40:58'),
(191, 127, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790005794/products/Angels%27%20Share.jpg_1790005793.jpg', 0, '2026-09-21T15:49:54', '2026-09-21T15:49:54'),
(192, 126, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790005989/products/Hibiscus%20Mahajad.jpg_1790005989.jpg', 0, '2026-09-21T15:53:10', '2026-09-21T15:53:10'),
(193, 125, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790006248/products/Ex%20Nihilo%20Blue%20Talisman.jpg_1790006248.jpg', 0, '2026-09-21T15:57:29', '2026-09-21T15:57:29'),
(194, 124, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790006340/products/Amouage%20Love%20hibiscus.jpg_1790006340.jpg', 0, '2026-09-21T15:59:01', '2026-09-21T15:59:01'),
(195, 123, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790006511/products/Amouage%20Guidance%2046.jpg_1790006511.jpg', 0, '2026-09-21T16:01:52', '2026-09-21T16:01:52'),
(196, 122, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790006668/products/Louis%20Vuitton%20Afternoon%20swim.jpg_1790006668.jpg', 0, '2026-09-21T16:04:28', '2026-09-21T16:04:28'),
(197, 121, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790006755/products/Louis%20Vuitton%20Imagination.jpg_1790006755.jpg', 0, '2026-09-21T16:05:55', '2026-09-21T16:05:55'),
(198, 120, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790007182/products/Hawas%20Glitz.jpg_1790007182.jpg', 0, '2026-09-21T16:13:03', '2026-09-21T16:13:03'),
(199, 119, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790007261/products/Lady%20Reef.jpg_1790007260.jpg', 0, '2026-09-21T16:14:21', '2026-09-21T16:14:21'),
(200, 118, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790007475/products/Billie%20Eilish.jpg_1790007475.jpg', 0, '2026-09-21T16:17:56', '2026-09-21T16:17:56'),
(201, 114, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790007858/products/Vanilla%20candy.jpg_1790007858.jpg', 0, '2026-09-21T16:24:18', '2026-09-21T16:24:18'),
(202, 110, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790008378/products/Sauvage%20Dior.jpg_1790008378.jpg', 0, '2026-09-21T16:32:59', '2026-09-21T16:32:59'),
(204, 116, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790008761/products/Yum%20Pistachio%20Gelato.jpg_1790008760.jpg', 0, '2026-09-21T16:39:21', '2026-09-21T16:39:21'),
(205, 115, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790008951/products/UTOPIA%20Vanilla%20Coco%20intense.jpg_1790008951.jpg', 0, '2026-09-21T16:42:31', '2026-09-21T16:42:31'),
(206, 112, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790009295/products/Yum%20boujee%20Marshimallow%20intense.jpg_1790009295.jpg', 0, '2026-09-21T16:48:16', '2026-09-21T16:48:16'),
(207, 111, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790009384/products/KAY%20ALI%20FREEDO%20MUSK%20SANTAL.jpg_1790009384.jpg', 0, '2026-09-21T16:49:44', '2026-09-21T16:49:44'),
(208, 109, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790009523/products/Givenchy%20Irresistible.jpg_1790009523.jpg', 0, '2026-09-21T16:52:04', '2026-09-21T16:52:04'),
(211, 346, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790235241/products/gee.jpg_1790235241.jpg', 0, '2026-09-24T07:34:02', '2026-09-24T07:34:02'),
(212, 158, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790237935/products/Impression.jpg_1790237935.jpg', 0, '2026-09-24T08:18:55', '2026-09-24T08:18:55');

-- brands (72 rows)
INSERT INTO public.brands (id, name, logo_url, is_active, created_by, created_at, updated_at) VALUES
(1, 'Afnan', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790092427/brands/Afnan.jpg_1790092427.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T15:53:47+00:00'),
(2, 'Ahmed Al Maghribi', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790092537/brands/Ahmed%20Al%20Maghribi.jpg_1790092537.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T15:55:37+00:00'),
(3, 'Al Haramain', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790092606/brands/Al%20Haramain.jpg_1790092606.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T15:56:46+00:00'),
(4, 'Amouage', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790092672/brands/Amouage.jpg_1790092672.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T15:57:52+00:00'),
(5, 'Aquolina', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790092795/brands/Aquolina.jpg_1790092794.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T15:59:54+00:00'),
(6, 'Arabiyat Prestige', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790092887/brands/Arabiyat%20Prestige.jpg_1790092887.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T16:01:27+00:00'),
(15, 'Carolina Herrera', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790094183/brands/Carolina%20Herrera.jpg_1790094183.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T16:23:03+00:00'),
(7, 'Ard Al Zaafaran', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790092960/brands/Ard%20Al%20Zaafaran.jpg_1790092960.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T16:02:40+00:00'),
(8, 'Armaf', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790093026/brands/Armaf.jpg_1790093026.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T16:03:46+00:00'),
(9, 'ARMANI', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790093131/brands/ARMANI.jpg_1790093130.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T16:05:30+00:00'),
(10, 'Azzaro', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790093203/brands/Azzaro.jpg_1790093203.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T16:06:43+00:00'),
(16, 'Chanel', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790094247/brands/Chanel.jpg_1790094247.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T16:24:07+00:00'),
(17, 'Creed', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790094343/brands/Creed.jpg_1790094342.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T16:25:42+00:00'),
(24, 'Escada', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790096153/brands/Escada.jpg_1790096153.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T16:55:53+00:00'),
(12, 'Billie Eilish', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790093658/brands/Billie%20Eilish.jpg_1790093657.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T16:14:17+00:00'),
(13, 'Britney Spears', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790093941/brands/Britney%20Spears.jpg_1790093940.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T16:19:00+00:00'),
(14, 'Burberry', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790094066/brands/Burberry.jpg_1790094066.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T16:21:06+00:00'),
(18, 'Cristiano Ronaldo', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790094532/brands/Cristiano%20Ronaldo.jpg_1790094532.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T16:28:52+00:00'),
(19, 'Dior', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790094609/brands/Dior.jpg_1790094609.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T16:30:09+00:00'),
(22, 'Emporio Armani', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790094965/brands/Emporio%20Armani.jpg_1790094965.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T16:36:05+00:00'),
(32, 'Hadlaj', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790170415/brands/Hadlaj%20logo.png_1790170415.png', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T13:33:35+00:00'),
(25, 'Ex Nihilo', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790098258/brands/Ex%20Nihilo.jpg_1790098257.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T17:30:57+00:00'),
(26, 'Fragrance World', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790098359/brands/Fragrance%20World.jpg_1790098359.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T17:32:39+00:00'),
(27, 'French Avenue', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790098433/brands/French%20Avenue.jpg_1790098432.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T17:33:52+00:00'),
(28, 'Giorgio Armani', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790098529/brands/Giorgio%20Armani.jpg_1790098528.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T17:35:28+00:00'),
(30, 'Givenchy', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790099077/brands/Givenchy.jpg_1790099077.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T17:44:37+00:00'),
(34, 'Issey Miyake', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790170646/brands/Issey%20Miyake.jpg_1790170646.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T13:37:26+00:00'),
(36, 'Kayali', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790170941/brands/Kayali.jpg_1790170941.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T13:42:21+00:00'),
(39, 'L''Affair', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790171622/brands/L%27Affair.jpg_1790171622.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T13:53:42+00:00'),
(41, 'Lancome', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790171947/brands/Lancome.jpg_1790171947.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T13:59:07+00:00'),
(44, 'Maison Alhambra', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790172223/brands/Maison%20Alhambra.jpg_1790172223.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:03:43+00:00'),
(46, 'Maison Crivelli', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790173867/brands/Maison%20Crivelli.png_1790173866.png', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:31:06+00:00'),
(49, 'Oud Potent', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790174335/brands/Oud%20Potent.png_1790174335.png', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:38:55+00:00'),
(51, 'Parfums de Marly', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790174599/brands/Parfums%20de%20Marly.jpg_1790174599.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:43:19+00:00'),
(54, 'Phlur', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790174994/brands/Phlur.jpg_1790174994.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:49:54+00:00'),
(56, 'Rasasi', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790175130/brands/Rasasi.jpg_1790175130.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:52:10+00:00'),
(58, 'Reef', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790175358/brands/Reef.jpg_1790175358.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:55:58+00:00'),
(61, 'Roberto Cavalli', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790176429/brands/Roberto%20Cavalli.jpg_1790176429.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T15:13:49+00:00'),
(70, 'World Choice Perfumes', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790261446/brands/logo%20wcp.png_1790261445.png', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-24T14:50:45+00:00'),
(73, 'Zimaya', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790261752/brands/Zimaya.jpg_1790261752.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-24T14:55:52+00:00'),
(68, 'Versace', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790265431/brands/Versace.jpg_1790265431.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-24T15:57:11+00:00'),
(63, 'Sunnamusk', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790266373/brands/Sunnamusk.png_1790266373.png', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-24T16:12:53+00:00'),
(65, 'Tom Ford', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790266627/brands/Tom%20Ford.jpg_1790266626.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-24T16:17:06+00:00'),
(23, 'Empty Bottles', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790269758/brands/Empty%20Bottles.jpg_1790269758.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-24T17:09:18+00:00'),
(29, 'Giorgio Beverly Hills', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790098998/brands/Giorgio%20Beverly%20Hills.jpg_1790098998.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-22T17:43:18+00:00'),
(31, 'Gulf Orchid', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790170169/brands/gulf%20orchid.png_1790170169.png', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T13:29:29+00:00'),
(33, 'Hugo Boss', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790170497/brands/Hugo%20Boss.jpg_1790170497.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T13:34:57+00:00'),
(35, 'Jean Paul Gaultier', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790170770/brands/Jean%20Paul%20Gaultier.jpg_1790170770.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T13:39:30+00:00'),
(37, 'Khadlaj', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790171227/brands/Khadlaj.jpg_1790171227.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T13:47:07+00:00'),
(38, 'Kilian', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790171447/brands/Kilian.jpg_1790171447.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T13:50:47+00:00'),
(40, 'Lacoste', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790171710/brands/Lacoste.jpg_1790171709.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T13:55:09+00:00'),
(42, 'Lattafa', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790172017/brands/Lattafa.jpg_1790172017.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:00:17+00:00'),
(43, 'Louis Vuitton', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790172097/brands/Louis%20Vuitton.jpg_1790172097.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:01:37+00:00'),
(45, 'Maison Asrar', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790173625/brands/Maison%20Asrar.png_1790173625.png', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:27:05+00:00'),
(47, 'Maison Francis Kurkdjian', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790173941/brands/Maison%20Francis%20Kurkdjian.jpg_1790173940.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:32:20+00:00'),
(48, 'Montblanc', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790174013/brands/Montblanc.jpg_1790174013.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:33:33+00:00'),
(50, 'Paco Rabanne', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790174437/brands/Paco%20Rabanne.jpg_1790174437.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:40:37+00:00'),
(52, 'Paris Corner', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790174690/brands/Paris%20Corner.jpg_1790174690.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:44:50+00:00'),
(53, 'Pendora Scents', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790174757/brands/Pendora%20Scents.jpg_1790174756.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:45:56+00:00'),
(55, 'Ralph Lauren', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790175065/brands/Ralph%20Lauren.jpg_1790175065.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:51:05+00:00'),
(57, 'Rayhaan', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790175203/brands/Rayhaan.jpg_1790175203.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:53:23+00:00'),
(59, 'Riiffs', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790175477/brands/Riiffs.jpg_1790175476.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T14:57:56+00:00'),
(60, 'Riwayat', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790176233/brands/Riwayah.jpg_1790176232.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-23T15:10:32+00:00'),
(71, 'Xerjoff', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790261216/brands/xer%20j.jpg_1790261215.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-24T14:46:55+00:00'),
(72, 'Yves Saint Laurent', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790261322/brands/Yves%20Saint%20Laurent.jpg_1790261322.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-24T14:48:42+00:00'),
(64, 'Swiss Arabian', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790261584/brands/swiss.jpg_1790261584.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-24T14:53:04+00:00'),
(69, 'Victoria''s Secret', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790261843/brands/Victoria%27s%20Secret.jpg_1790261843.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-24T14:57:23+00:00'),
(66, 'Unique''e Luxury', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790266503/brands/Unique%27e%20Luxury.jpg_1790266503.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-24T16:15:03+00:00'),
(67, 'Valentino', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790266571/brands/Valentino.jpg_1790266571.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-24T16:16:11+00:00'),
(20, 'Dolce & Gabbana', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790266986/brands/g.jpg_1790266986.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-24T16:23:06+00:00'),
(21, 'Eddie Milliz', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790267846/brands/Eddie%20Milliz%20logo.jpg_1790267846.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-24T16:37:26+00:00'),
(11, 'Bath & Body Works', 'https://res.cloudinary.com/zcmci5mi/image/upload/v1790270378/brands/g2.jpg_1790270378.jpg', TRUE, 38, '2026-09-18T17:41:40+00:00', '2026-09-24T17:19:38+00:00');

-- company_settings (2 rows)
INSERT INTO public.company_settings (key, value, updated_at) VALUES
('super_admin_secret', 'WCP-SUPER-2026', '2026-09-14T09:53:07+00:00'),
('staff_secret_code', 'WCP-STAFF-2026', '2026-09-14T09:53:07+00:00');

-- customers (16 rows)
INSERT INTO public.customers (id, name, phone, email, whatsapp, created_at, updated_at) VALUES
(1, 'Abdul Nyerere', '+255712345678', 'abdul@email.com', '+255712345678', '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(2, 'Fatima Omary', '+255723456789', 'fatima.c@email.com', '+255723456789', '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(3, 'John Mwakasegela', '+255734567890', 'john@email.com', '+255734567890', '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(4, 'Amina Hemed', '+255745678901', 'amina.h@email.com', '+255745678901', '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(5, 'Peter Kimaro', '+255756789012', 'peter@email.com', '+255756789012', '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(6, 'Rebecca Shirima', '+255767890123', 'rebecca@email.com', '+255767890123', '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(7, 'Yusuf Kibona', '+255778901234', 'yusuf@email.com', '+255778901234', '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(8, 'Neema Mwasaga', '+255789012345', 'neema@email.com', '+255789012345', '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(9, 'Daniel Ndege', '+255790123456', 'daniel@email.com', '+255790123456', '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(10, 'Happiness Mushi', '+255701234567', 'happiness@email.com', '+255701234567', '2026-08-22T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(11, 'FRANK GODWINY', '0616675940', 'worldchoiceperfumes@gmail.com', '0616675940', NULL, NULL),
(12, 'COSMA COSMA VICTORINI', '0769010240', 'godwinfranklin419@gmail.com', '0769010240', NULL, NULL),
(13, 'Gideon', '0682601154', 'gideonmsuya146@gmail.com', '0682601154', NULL, NULL),
(14, 'Payment Test', '0711999000', 'test@example.com', '0711999000', NULL, NULL),
(15, 'Adam Mashaka', '0616675940', NULL, '0616675940', NULL, NULL),
(16, 'EX NIHILO', '0622385322', NULL, '0622385322', NULL, NULL);

-- bottle_stock (16 rows)
INSERT INTO public.bottle_stock (id, branch_id, volume, quantity, created_at, updated_at, has_logo, logo_color, has_box, box_color, variant) VALUES
(43, 9, '100ml', 2, '2026-09-16T15:13:48+00:00', '2026-09-16T15:16:41+00:00', 'yes', 'yellow', 'yes', NULL, 'box_logo_yellow'),
(46, 8, '30ml', 25, '2026-09-19T13:40:02+00:00', '2026-09-19T13:40:02+00:00', NULL, NULL, 'no', NULL, 'no_box'),
(51, 8, '100ml', 134, '2026-09-19T13:51:56+00:00', '2026-09-19T13:55:14+00:00', 'no', 'white', 'yes', NULL, 'box_nologo_white'),
(58, 9, '12ml', 22, '2026-09-22T07:50:00+00:00', '2026-09-23T15:33:47+00:00', NULL, NULL, NULL, NULL, 'plain'),
(54, 8, '30ml', 100, '2026-09-19T14:00:23+00:00', '2026-09-19T14:00:23+00:00', 'no', 'white', 'yes', NULL, 'box_nologo_white'),
(50, 8, '50ml', 30, '2026-09-19T13:51:09+00:00', '2026-09-19T14:41:49+00:00', NULL, NULL, 'no', NULL, 'no_box'),
(49, 8, '100ml', 376, '2026-09-19T13:50:04+00:00', '2026-09-29T09:41:31+00:00', 'yes', 'yellow', 'yes', NULL, 'box_logo_yellow'),
(55, 8, '50ml', 403, '2026-09-19T14:01:45+00:00', '2026-09-29T10:08:45+00:00', 'yes', 'black', 'yes', NULL, 'box_logo_black'),
(48, 8, '50ml', 263, '2026-09-19T13:48:57+00:00', '2026-10-01T10:29:42+00:00', 'yes', 'yellow', 'yes', NULL, 'box_logo_yellow'),
(45, 8, '6ml', 226, '2026-09-19T13:38:57+00:00', '2026-10-01T10:44:28+00:00', NULL, NULL, NULL, NULL, 'plain'),
(44, 8, '12ml', 188, '2026-09-19T13:38:27+00:00', '2026-10-01T10:44:29+00:00', NULL, NULL, NULL, NULL, 'plain'),
(47, 8, '30ml', 103, '2026-09-19T13:46:43+00:00', '2026-10-01T10:44:30+00:00', 'no', 'black', 'yes', NULL, 'box_nologo_black'),
(57, 8, '100ml', 38, '2026-09-19T14:41:11+00:00', '2026-10-01T10:44:31+00:00', NULL, NULL, 'no', NULL, 'no_box'),
(53, 8, '50ml', 42, '2026-09-19T13:56:35+00:00', '2026-10-01T10:44:31+00:00', 'no', 'white', 'yes', NULL, 'box_nologo_white'),
(52, 8, '100ml', 78, '2026-09-19T13:52:49+00:00', '2026-10-01T10:44:32+00:00', 'no', 'black', 'yes', NULL, 'box_nologo_black'),
(56, 8, '50ml', 2, '2026-09-19T14:37:26+00:00', '2026-09-22T08:45:00+00:00', 'no', 'black', 'yes', NULL, 'box_nologo_black');

-- bottle_stock_movements (94 rows)
INSERT INTO public.bottle_stock_movements (id, branch_id, volume, type, quantity, reason, performed_by, created_at, updated_at, has_logo, logo_color, has_box, box_color, variant) VALUES
(32, 8, '100ml', 'stock_out', 20, 'Sold as empty bottle - Sale SALE-20260913101409-4BCC', 29, '2026-09-13T10:14:11+00:00', '2026-09-13T10:14:11+00:00', NULL, NULL, NULL, NULL, 'box_logo_yellow'),
(35, 8, '100ml', 'stock_out', 20, 'Sold as empty bottle - Sale SALE-20260913101716-9C9B', 29, '2026-09-13T10:17:18+00:00', '2026-09-13T10:17:18+00:00', NULL, NULL, NULL, NULL, 'box_nologo_white'),
(37, 8, '100ml', 'stock_out', 20, 'Sold as empty bottle - Sale SALE-20260913114900-8315', 29, '2026-09-13T11:49:04+00:00', '2026-09-13T11:49:04+00:00', NULL, NULL, NULL, NULL, 'no_box'),
(23, 8, '6ml', 'stock_in', 60, 'imported [Logo: Yellow]', 29, '2026-09-07T23:39:32+00:00', '2026-09-07T23:39:32+00:00', 'yes', 'yellow', NULL, NULL, NULL),
(26, 8, '12ml', 'stock_in', 56, 'imported [Logo: Black]', 29, '2026-09-12T22:02:35+00:00', '2026-09-12T22:02:35+00:00', 'yes', 'black', NULL, NULL, NULL),
(27, 8, '50ml', 'stock_out', 40, 'Auto outstock for oil fragrance stock entry', 29, '2026-09-13T06:23:46+00:00', '2026-09-13T06:23:46+00:00', NULL, NULL, NULL, NULL, NULL),
(31, 8, '100ml', 'stock_in', 50, 'imported [With Box Â· With Logo Â· Yellow]', 29, '2026-09-13T10:12:44+00:00', '2026-09-13T10:12:44+00:00', 'yes', 'yellow', 'yes', NULL, 'box_logo_yellow'),
(36, 8, '100ml', 'stock_in', 80, 'imported [Without Box]', 29, '2026-09-13T10:38:49+00:00', '2026-09-13T10:38:49+00:00', NULL, NULL, 'no', NULL, 'no_box'),
(24, 8, '30ml', 'stock_in', 71, 'import [No Logo, No Box]', 1, '2026-09-10T06:54:35+00:00', '2026-09-10T06:54:35+00:00', 'no', NULL, 'no', NULL, NULL),
(25, 8, '50ml', 'stock_in', 144, 'import [Logo: Yellow]', 1, '2026-09-10T06:56:56+00:00', '2026-09-10T06:56:56+00:00', 'yes', 'yellow', NULL, NULL, NULL),
(38, 8, '30ml', 'stock_in', 71, 'imported [Without Box]', 32, '2026-09-14T10:55:42+00:00', '2026-09-14T10:55:42+00:00', NULL, NULL, 'no', NULL, 'no_box'),
(39, 8, '50ml', 'stock_in', 144, 'imported [With Box Â· With Logo Â· Yellow]', 32, '2026-09-14T11:14:25+00:00', '2026-09-14T11:14:25+00:00', 'yes', 'yellow', 'yes', NULL, 'box_logo_yellow'),
(40, 8, '50ml', 'stock_in', 79, 'imported [With Box Â· With Logo Â· Black]', 32, '2026-09-14T11:15:27+00:00', '2026-09-14T11:15:27+00:00', 'yes', 'black', 'yes', NULL, 'box_logo_black'),
(41, 8, '50ml', 'stock_in', 137, 'imported [With Box Â· No Logo Â· Black]', 32, '2026-09-14T11:26:27+00:00', '2026-09-14T11:26:27+00:00', 'no', 'black', 'yes', NULL, 'box_nologo_black'),
(44, 8, '50ml', 'stock_in', 55, 'imported [Without Box]', 32, '2026-09-14T11:28:37+00:00', '2026-09-14T11:28:37+00:00', NULL, NULL, 'no', NULL, 'no_box'),
(45, 8, '100ml', 'stock_in', 67, 'imported [With Box Â· With Logo Â· Yellow]', 32, '2026-09-14T11:31:45+00:00', '2026-09-14T11:31:45+00:00', 'yes', 'yellow', 'yes', NULL, 'box_logo_yellow'),
(46, 8, '100ml', 'stock_in', 114, 'Stock in [With Box Â· No Logo Â· Black]', 32, '2026-09-14T11:34:44+00:00', '2026-09-14T11:34:44+00:00', 'no', 'black', 'yes', NULL, 'box_nologo_black'),
(49, 8, '100ml', 'stock_in', 112, 'imported [Without Box]', 32, '2026-09-14T11:39:04+00:00', '2026-09-14T11:39:04+00:00', NULL, NULL, 'no', NULL, 'no_box'),
(50, 8, '12ml', 'stock_in', 141, 'imported', 32, '2026-09-14T11:58:16+00:00', '2026-09-14T11:58:16+00:00', NULL, NULL, NULL, NULL, 'plain'),
(51, 8, '6ml', 'stock_in', 104, 'imported', 32, '2026-09-14T11:59:25+00:00', '2026-09-14T11:59:25+00:00', NULL, NULL, NULL, NULL, 'plain'),
(52, 8, '6ml', 'stock_in', 240, 'imported', 32, '2026-09-14T12:09:34+00:00', '2026-09-14T12:09:34+00:00', NULL, NULL, NULL, NULL, 'plain'),
(53, 8, '12ml', 'stock_in', 300, 'imported', 32, '2026-09-14T12:17:54+00:00', '2026-09-14T12:17:54+00:00', NULL, NULL, NULL, NULL, 'plain'),
(54, 8, '30ml', 'stock_in', 257, 'imported [With Box Â· No Logo Â· Black]', 32, '2026-09-14T12:24:26+00:00', '2026-09-14T12:24:26+00:00', 'no', 'black', 'yes', NULL, 'box_nologo_black'),
(57, 8, '50ml', 'stock_in', 864, 'imported [With Box Â· With Logo Â· Yellow]', 32, '2026-09-14T12:49:58+00:00', '2026-09-14T12:49:58+00:00', 'yes', 'yellow', 'yes', NULL, 'box_logo_yellow'),
(58, 8, '50ml', 'stock_in', 70, 'imported [With Box Â· No Logo Â· Black]', 32, '2026-09-14T12:50:37+00:00', '2026-09-14T12:50:37+00:00', 'no', 'black', 'yes', NULL, 'box_nologo_black'),
(61, 8, '100ml', 'stock_in', 504, 'imported [With Box Â· With Logo Â· Yellow]', 32, '2026-09-14T12:53:30+00:00', '2026-09-14T12:53:30+00:00', 'yes', 'yellow', 'yes', NULL, 'box_logo_yellow'),
(62, 8, '100ml', 'stock_in', 67, 'imported [With Box Â· No Logo Â· Black]', 32, '2026-09-14T12:55:21+00:00', '2026-09-14T12:55:21+00:00', 'no', 'black', 'yes', NULL, 'box_nologo_black'),
(65, 8, '30ml', 'stock_out', 5, 'Sold as empty bottle - Sale SALE-20260915132638-FA9C', 32, '2026-09-15T13:26:42+00:00', '2026-09-15T13:26:42+00:00', NULL, NULL, NULL, NULL, 'box_nologo_black'),
(66, 8, '50ml', 'stock_out', 5, 'Sold as empty bottle - Sale SALE-20260915132638-FA9C', 32, '2026-09-15T13:26:44+00:00', '2026-09-15T13:26:44+00:00', NULL, NULL, NULL, NULL, 'box_logo_black'),
(67, 8, '100ml', 'stock_out', 2, 'Sold as empty bottle - Sale SALE-20260915132638-FA9C', 32, '2026-09-15T13:26:45+00:00', '2026-09-15T13:26:45+00:00', NULL, NULL, NULL, NULL, 'box_nologo_black'),
(68, 9, '100ml', 'stock_in', 4, 'Stock in [With Box Â· With Logo Â· Yellow]', 36, '2026-09-16T15:13:48+00:00', '2026-09-16T15:13:48+00:00', 'yes', 'yellow', 'yes', NULL, 'box_logo_yellow'),
(69, 9, '100ml', 'stock_out', 2, 'Auto outstock for oil fragrance stock entry', 36, '2026-09-16T15:16:41+00:00', '2026-09-16T15:16:41+00:00', NULL, NULL, NULL, NULL, 'box_logo_yellow'),
(70, 8, '50ml', 'stock_out', 5, 'Auto outstock for oil fragrance stock entry', 32, '2026-09-17T11:48:55+00:00', '2026-09-17T11:48:55+00:00', NULL, NULL, NULL, NULL, 'no_box'),
(71, 8, '12ml', 'stock_in', 36, 'imported', 32, '2026-09-19T13:38:27+00:00', '2026-09-19T13:38:27+00:00', NULL, NULL, NULL, NULL, 'plain'),
(72, 8, '6ml', 'stock_in', 48, 'imported', 32, '2026-09-19T13:38:57+00:00', '2026-09-19T13:38:57+00:00', NULL, NULL, NULL, NULL, 'plain'),
(73, 8, '30ml', 'stock_in', 25, 'imported [Without Box]', 32, '2026-09-19T13:40:02+00:00', '2026-09-19T13:40:02+00:00', NULL, NULL, 'no', NULL, 'no_box'),
(74, 8, '30ml', 'stock_in', 40, 'imported [With Box Â· No Logo Â· Black]', 32, '2026-09-19T13:46:43+00:00', '2026-09-19T13:46:43+00:00', 'no', 'black', 'yes', NULL, 'box_nologo_black'),
(75, 8, '50ml', 'stock_in', 9, 'imported [With Box Â· With Logo Â· Yellow]', 32, '2026-09-19T13:48:57+00:00', '2026-09-19T13:48:57+00:00', 'yes', 'yellow', 'yes', NULL, 'box_logo_yellow'),
(76, 8, '100ml', 'stock_in', 5, 'imported [With Box Â· With Logo Â· Yellow]', 32, '2026-09-19T13:50:04+00:00', '2026-09-19T13:50:04+00:00', 'yes', 'yellow', 'yes', NULL, 'box_logo_yellow'),
(77, 8, '50ml', 'stock_in', 20, 'imported [Without Box]', 32, '2026-09-19T13:51:09+00:00', '2026-09-19T13:51:09+00:00', NULL, NULL, 'no', NULL, 'no_box'),
(80, 8, '100ml', 'stock_in', 50, 'imported [With Box Â· No Logo Â· Black]', 32, '2026-09-19T13:52:49+00:00', '2026-09-19T13:52:49+00:00', 'no', 'black', 'yes', NULL, 'box_nologo_black'),
(85, 8, '100ml', 'stock_in', 67, 'imported [With Box Â· No Logo Â· Black]', 32, '2026-09-19T13:57:23+00:00', '2026-09-19T13:57:23+00:00', 'no', 'black', 'yes', NULL, 'box_nologo_black'),
(86, 8, '30ml', 'stock_in', 80, 'imported [With Box Â· No Logo Â· Black]', 32, '2026-09-19T13:58:58+00:00', '2026-09-19T13:58:58+00:00', 'no', 'black', 'yes', NULL, 'box_nologo_black'),
(89, 8, '50ml', 'stock_in', 380, 'imported [With Box Â· With Logo Â· Black]', 32, '2026-09-19T14:01:45+00:00', '2026-09-19T14:01:45+00:00', 'yes', 'black', 'yes', NULL, 'box_logo_black'),
(90, 8, '50ml', 'stock_in', 320, 'imported [With Box Â· With Logo Â· Yellow]', 32, '2026-09-19T14:02:33+00:00', '2026-09-19T14:02:33+00:00', 'yes', 'yellow', 'yes', NULL, 'box_logo_yellow'),
(91, 8, '100ml', 'stock_in', 444, 'imported [With Box Â· With Logo Â· Yellow]', 32, '2026-09-19T14:03:52+00:00', '2026-09-19T14:03:52+00:00', 'yes', 'yellow', 'yes', NULL, 'box_logo_yellow'),
(92, 8, '100ml', 'stock_in', 4, 'imported [With Box Â· No Logo Â· Black]', 32, '2026-09-19T14:27:53+00:00', '2026-09-19T14:27:53+00:00', 'no', 'black', 'yes', NULL, 'box_nologo_black'),
(93, 8, '50ml', 'stock_in', 15, 'imported [With Box Â· No Logo Â· Black]', 32, '2026-09-19T14:37:26+00:00', '2026-09-19T14:37:26+00:00', 'no', 'black', 'yes', NULL, 'box_nologo_black'),
(96, 8, '50ml', 'stock_in', 34, 'imported [With Box Â· With Logo Â· Black]', 32, '2026-09-19T14:39:14+00:00', '2026-09-19T14:39:14+00:00', 'yes', 'black', 'yes', NULL, 'box_logo_black'),
(97, 8, '100ml', 'stock_in', 50, 'imported [Without Box]', 32, '2026-09-19T14:41:11+00:00', '2026-09-19T14:41:11+00:00', NULL, NULL, 'no', NULL, 'no_box'),
(98, 8, '50ml', 'stock_in', 10, 'imported [Without Box]', 32, '2026-09-19T14:41:49+00:00', '2026-09-19T14:41:49+00:00', NULL, NULL, 'no', NULL, 'no_box'),
(99, 8, '12ml', 'stock_in', 6, 'imported', 32, '2026-09-19T14:52:52+00:00', '2026-09-19T14:52:52+00:00', NULL, NULL, NULL, NULL, 'plain'),
(100, 8, '12ml', 'stock_out', 6, 'Sold as empty bottle - Sale SALE-20260919145439-06DE', 32, '2026-09-19T14:54:41+00:00', '2026-09-19T14:54:41+00:00', NULL, NULL, NULL, NULL, 'plain'),
(101, 8, '12ml', 'stock_in', 6, 'imported', 32, '2026-09-19T14:55:44+00:00', '2026-09-19T14:55:44+00:00', NULL, NULL, NULL, NULL, 'plain'),
(102, 8, '12ml', 'stock_out', 6, 'Auto outstock for oil fragrance stock entry', 32, '2026-09-19T14:57:40+00:00', '2026-09-19T14:57:40+00:00', NULL, NULL, NULL, NULL, 'plain'),
(103, 8, '50ml', 'stock_out', 1, 'Auto outstock for oil fragrance stock entry', 32, '2026-09-19T15:24:46+00:00', '2026-09-19T15:24:46+00:00', NULL, NULL, NULL, NULL, 'box_logo_black'),
(104, 8, '50ml', 'stock_out', 200, 'Auto outstock for oil fragrance stock entry', 29, '2026-09-19T15:47:36+00:00', '2026-09-19T15:47:36+00:00', NULL, NULL, NULL, NULL, 'box_logo_yellow'),
(105, 8, '50ml', 'stock_out', 200, 'Auto outstock for oil fragrance stock entry', 29, '2026-09-19T17:27:20+00:00', '2026-09-19T17:27:20+00:00', NULL, NULL, NULL, NULL, 'box_logo_yellow'),
(106, 8, '50ml', 'stock_out', 100, 'Auto outstock for oil fragrance stock entry', 29, '2026-09-19T19:38:26+00:00', '2026-09-19T19:38:26+00:00', NULL, NULL, NULL, NULL, 'box_logo_yellow'),
(107, 8, '100ml', 'stock_out', 50, 'Auto outstock for oil fragrance stock entry', 29, '2026-09-19T19:40:38+00:00', '2026-09-19T19:40:38+00:00', NULL, NULL, NULL, NULL, 'box_logo_yellow'),
(108, 8, '50ml', 'stock_out', 50, 'Auto outstock for oil fragrance stock entry', 29, '2026-09-19T19:42:24+00:00', '2026-09-19T19:42:24+00:00', NULL, NULL, NULL, NULL, 'box_logo_yellow'),
(109, 8, '100ml', 'stock_in', 50, 'imported [With Box Â· With Logo Â· Yellow]', 29, '2026-09-19T19:43:57+00:00', '2026-09-19T19:43:57+00:00', 'yes', 'yellow', 'yes', NULL, 'box_logo_yellow'),
(110, 8, '30ml', 'stock_out', 20, 'Auto outstock for oil fragrance stock entry', 29, '2026-09-19T20:41:29+00:00', '2026-09-19T20:41:29+00:00', NULL, NULL, NULL, NULL, 'box_nologo_black'),
(111, 8, '12ml', 'stock_in', 6, 'imported', 32, '2026-09-21T08:03:02+00:00', '2026-09-21T08:03:02+00:00', NULL, NULL, NULL, NULL, 'plain'),
(112, 8, '12ml', 'stock_out', 12, 'Stock transfer to Dodoma branch (TF-20260922074823-2AAE)', 32, '2026-09-22T07:48:23+00:00', '2026-09-22T07:48:23+00:00', NULL, NULL, NULL, NULL, 'plain'),
(113, 9, '12ml', 'stock_in', 12, 'Received from stock transfer TF-20260922074823-2AAE (from Kinondoni branch)', 36, '2026-09-22T07:50:00+00:00', '2026-09-22T07:50:00+00:00', NULL, NULL, NULL, NULL, 'plain'),
(114, 8, '100ml', 'stock_out', 1, 'Sold as empty bottle - Sale SALE-20260923090932-1DD3', 32, '2026-09-23T09:09:34+00:00', '2026-09-23T09:09:34+00:00', NULL, NULL, NULL, NULL, 'box_logo_yellow'),
(115, 8, '100ml', 'stock_out', 1, 'Stock transfer to Dodoma branch (TF-20260923124518-F1E9)', 32, '2026-09-23T12:45:19+00:00', '2026-09-23T12:45:19+00:00', NULL, NULL, NULL, NULL, 'box_logo_yellow'),
(116, 8, '12ml', 'stock_in', 10, 'imported', 32, '2026-09-23T15:31:57+00:00', '2026-09-23T15:31:57+00:00', NULL, NULL, NULL, NULL, 'plain'),
(117, 8, '12ml', 'stock_out', 10, 'Stock transfer to Dodoma branch (TF-20260923153236-7B85)', 32, '2026-09-23T15:32:36+00:00', '2026-09-23T15:32:36+00:00', NULL, NULL, NULL, NULL, 'plain'),
(118, 9, '12ml', 'stock_in', 10, 'Received from stock transfer TF-20260923153236-7B85 (from Kinondoni branch)', 36, '2026-09-23T15:33:47+00:00', '2026-09-23T15:33:47+00:00', NULL, NULL, NULL, NULL, 'plain'),
(119, 8, '100ml', 'stock_in', 1, 'Returned by Dodoma branch â€” invalid item from stock transfer TF-20260923124518-F1E9', 36, '2026-09-24T06:43:30+00:00', '2026-09-24T06:43:30+00:00', 'yes', 'yellow', 'yes', NULL, 'box_logo_yellow'),
(120, 8, '100ml', 'stock_out', 1, 'Broken after return from Dodoma branch (transfer TF-20260923124518-F1E9): poor packing of item', 32, '2026-09-24T06:45:55+00:00', '2026-09-24T06:45:55+00:00', NULL, NULL, NULL, NULL, 'box_logo_yellow'),
(121, 8, '6ml', 'stock_in', 240, 'imported', 32, '2026-09-25T12:55:27+00:00', '2026-09-25T12:55:27+00:00', NULL, NULL, NULL, NULL, 'plain'),
(122, 8, '12ml', 'stock_in', 180, 'imported', 32, '2026-09-25T12:56:24+00:00', '2026-09-25T12:56:24+00:00', NULL, NULL, NULL, NULL, 'plain'),
(123, 8, '100ml', 'stock_out', 27, 'Auto outstock for oil fragrance stock entry', 32, '2026-09-25T15:59:59+00:00', '2026-09-25T15:59:59+00:00', NULL, NULL, NULL, NULL, 'box_logo_yellow'),
(124, 8, '50ml', 'stock_out', 19, 'Auto outstock for oil fragrance stock entry', 32, '2026-09-25T16:03:33+00:00', '2026-09-25T16:03:33+00:00', NULL, NULL, NULL, NULL, 'box_logo_yellow'),
(125, 8, '100ml', 'stock_out', 7, 'Sold as empty bottle - Sale SALE-20260925162429-713A', 32, '2026-09-25T16:24:32+00:00', '2026-09-25T16:24:32+00:00', NULL, NULL, NULL, NULL, 'box_nologo_black'),
(126, 8, '50ml', 'stock_out', 5, 'Sold as empty bottle - Sale SALE-20260925162429-713A', 32, '2026-09-25T16:24:33+00:00', '2026-09-25T16:24:33+00:00', NULL, NULL, NULL, NULL, 'box_logo_black'),
(127, 8, '6ml', 'stock_out', 6, 'Sold as empty bottle - Sale SALE-20260925170411-A4C8', 32, '2026-09-25T17:04:14+00:00', '2026-09-25T17:04:14+00:00', NULL, NULL, NULL, NULL, 'plain'),
(128, 8, '12ml', 'stock_out', 6, 'Sold as empty bottle - Sale SALE-20260925170411-A4C8', 32, '2026-09-25T17:04:15+00:00', '2026-09-25T17:04:15+00:00', NULL, NULL, NULL, NULL, 'plain'),
(129, 8, '50ml', 'stock_out', 15, 'Sold as empty bottle - Sale SALE-20260925170411-A4C8', 32, '2026-09-25T17:04:16+00:00', '2026-09-25T17:04:16+00:00', NULL, NULL, NULL, NULL, 'box_nologo_white'),
(130, 8, '6ml', 'stock_out', 12, 'Sold as empty bottle - Sale SALE-20260925170551-B2C8', 32, '2026-09-25T17:05:53+00:00', '2026-09-25T17:05:53+00:00', NULL, NULL, NULL, NULL, 'plain'),
(131, 8, '6ml', 'stock_out', 12, 'Sold as empty bottle - Sale SALE-20260925170604-5C36', 32, '2026-09-25T17:06:06+00:00', '2026-09-25T17:06:06+00:00', NULL, NULL, NULL, NULL, 'plain'),
(132, 8, '100ml', 'stock_out', 44, 'Auto outstock for oil fragrance stock entry', 32, '2026-09-29T09:41:31+00:00', '2026-09-29T09:41:31+00:00', NULL, NULL, NULL, NULL, 'box_logo_yellow'),
(133, 8, '50ml', 'stock_out', 47, 'Auto outstock for oil fragrance stock entry', 32, '2026-10-01T10:29:42+00:00', '2026-10-01T10:29:42+00:00', NULL, NULL, NULL, NULL, 'box_logo_yellow'),
(134, 8, '50ml', 'stock_out', 12, 'Sold as empty bottle - Sale SALE-20261001104425-B2E8', 32, '2026-10-01T10:44:28+00:00', '2026-10-01T10:44:28+00:00', NULL, NULL, NULL, NULL, 'box_nologo_white'),
(135, 8, '6ml', 'stock_out', 20, 'Sold as empty bottle - Sale SALE-20261001104425-B2E8', 32, '2026-10-01T10:44:29+00:00', '2026-10-01T10:44:29+00:00', NULL, NULL, NULL, NULL, 'plain'),
(136, 8, '12ml', 'stock_out', 10, 'Sold as empty bottle - Sale SALE-20261001104425-B2E8', 32, '2026-10-01T10:44:29+00:00', '2026-10-01T10:44:29+00:00', NULL, NULL, NULL, NULL, 'plain'),
(137, 8, '30ml', 'stock_out', 5, 'Sold as empty bottle - Sale SALE-20261001104425-B2E8', 32, '2026-10-01T10:44:30+00:00', '2026-10-01T10:44:30+00:00', NULL, NULL, NULL, NULL, 'box_nologo_black'),
(138, 8, '50ml', 'stock_out', 4, 'Sold as empty bottle - Sale SALE-20261001104425-B2E8', 32, '2026-10-01T10:44:30+00:00', '2026-10-01T10:44:30+00:00', NULL, NULL, NULL, NULL, 'box_nologo_white'),
(139, 8, '100ml', 'stock_out', 12, 'Sold as empty bottle - Sale SALE-20261001104425-B2E8', 32, '2026-10-01T10:44:31+00:00', '2026-10-01T10:44:31+00:00', NULL, NULL, NULL, NULL, 'no_box'),
(140, 8, '50ml', 'stock_out', 12, 'Sold as empty bottle - Sale SALE-20261001104425-B2E8', 32, '2026-10-01T10:44:32+00:00', '2026-10-01T10:44:32+00:00', NULL, NULL, NULL, NULL, 'box_nologo_white'),
(141, 8, '100ml', 'stock_out', 12, 'Sold as empty bottle - Sale SALE-20261001104425-B2E8', 32, '2026-10-01T10:44:32+00:00', '2026-10-01T10:44:32+00:00', NULL, NULL, NULL, NULL, 'box_nologo_black');

-- bottle_accessories (6 rows)
INSERT INTO public.bottle_accessories (id, branch_id, type, color, quantity, created_at, updated_at) VALUES
(16, 8, 'straws', 'silver', 0, '2026-09-14T11:41:16+00:00', '2026-09-25T08:43:03+00:00'),
(20, 8, 'straws', 'gold', 0, '2026-09-14T12:37:37+00:00', '2026-09-25T08:43:21+00:00'),
(17, 8, 'bottlenecks', 'silver', 0, '2026-09-14T11:42:09+00:00', '2026-09-25T08:44:15+00:00'),
(21, 8, 'bottlenecks', 'gold', 0, '2026-09-14T12:38:52+00:00', '2026-09-25T08:44:28+00:00'),
(18, 8, 'bottle_tops', 'silver', 0, '2026-09-14T11:43:34+00:00', '2026-09-25T08:45:02+00:00'),
(19, 8, 'bottle_tops', 'gold', 0, '2026-09-14T11:47:50+00:00', '2026-09-25T08:45:32+00:00');

-- bottle_accessories_movements (19 rows)
INSERT INTO public.bottle_accessories_movements (id, branch_id, type, color, movement_type, quantity, reason, performed_by, created_at, updated_at) VALUES
(24, 8, 'straws', 'gold', 'stock_in', 5, 'imported', 29, '2026-09-13T12:26:25+00:00', '2026-09-13T12:26:25+00:00'),
(25, 8, 'straws', 'gold', 'stock_in', 5, 'imported', 29, '2026-09-13T12:26:26+00:00', '2026-09-13T12:26:26+00:00'),
(22, 8, 'bottle_tops', 'gold', 'stock_in', 20, 'imported', 29, '2026-09-07T22:33:36+00:00', '2026-09-07T22:33:36+00:00'),
(23, 8, 'straws', 'gold', 'stock_in', 20, 'imported', 29, '2026-09-08T09:09:40+00:00', '2026-09-08T09:09:40+00:00'),
(26, 8, 'straws', 'silver', 'stock_in', 1, 'imported', 32, '2026-09-14T11:41:16+00:00', '2026-09-14T11:41:16+00:00'),
(27, 8, 'bottlenecks', 'silver', 'stock_in', 1, 'imported', 32, '2026-09-14T11:42:09+00:00', '2026-09-14T11:42:09+00:00'),
(28, 8, 'bottle_tops', 'silver', 'stock_in', 2, 'imported', 32, '2026-09-14T11:43:34+00:00', '2026-09-14T11:43:34+00:00'),
(29, 8, 'bottle_tops', 'gold', 'stock_in', 1, 'imported', 32, '2026-09-14T11:47:50+00:00', '2026-09-14T11:47:50+00:00'),
(30, 8, 'straws', 'silver', 'stock_in', 2, 'imported', 32, '2026-09-14T12:36:35+00:00', '2026-09-14T12:36:35+00:00'),
(31, 8, 'straws', 'gold', 'stock_in', 2, 'imported', 32, '2026-09-14T12:37:38+00:00', '2026-09-14T12:37:38+00:00'),
(32, 8, 'bottlenecks', 'silver', 'stock_in', 1, 'imported', 32, '2026-09-14T12:38:25+00:00', '2026-09-14T12:38:25+00:00'),
(33, 8, 'bottlenecks', 'gold', 'stock_in', 1, 'imported', 32, '2026-09-14T12:38:52+00:00', '2026-09-14T12:38:52+00:00'),
(34, 8, 'bottle_tops', 'gold', 'stock_in', 1, 'imported', 32, '2026-09-14T12:42:04+00:00', '2026-09-14T12:42:04+00:00'),
(35, 8, 'bottle_tops', 'silver', 'stock_in', 3, 'imported', 32, '2026-09-14T12:43:15+00:00', '2026-09-14T12:43:15+00:00'),
(36, 8, 'bottle_tops', 'gold', 'stock_in', 4, 'imported', 32, '2026-09-14T12:45:32+00:00', '2026-09-14T12:45:32+00:00'),
(37, 8, 'bottle_tops', 'silver', 'stock_in', 3, 'imported', 32, '2026-09-14T12:47:03+00:00', '2026-09-14T12:47:03+00:00'),
(38, 9, 'straws', 'silver', 'stock_in', 1, 'imported', 36, '2026-09-22T08:07:55+00:00', '2026-09-22T08:07:55+00:00'),
(39, 8, 'straws', 'silver', 'stock_out', 2, 'Stock transfer to Dodoma branch (TF-20260922081728-4298)', 32, '2026-09-22T08:17:28+00:00', '2026-09-22T08:17:28+00:00'),
(40, 9, 'straws', 'silver', 'stock_in', 2, 'Received from stock transfer TF-20260922081728-4298 (from Kinondoni branch)', 36, '2026-09-22T08:28:26+00:00', '2026-09-22T08:28:26+00:00');

-- oil_fragrance_stock (97 rows)
INSERT INTO public.oil_fragrance_stock (id, branch_id, name, quantity, created_at, updated_at, volume) VALUES
(51, 8, 'Golden Dust', 0, '2026-09-14T15:41:30+00:00', '2026-09-14T15:44:14+00:00', 1000),
(2, 8, 'ACQUA DI GIO PROFONDO ARMANI', 1, '2026-09-14T13:06:56+00:00', '2026-09-14T13:06:56+00:00', 1000),
(45, 8, 'Eclair', 2, '2026-09-14T15:12:54+00:00', '2026-09-25T12:52:24+00:00', 1000),
(16, 8, 'Now Rave', 2, '2026-09-14T13:49:43+00:00', '2026-09-25T12:52:57+00:00', 1000),
(6, 8, 'Taj Sunset', 1, '2026-09-14T13:21:03+00:00', '2026-09-14T13:21:03+00:00', 1000),
(7, 8, 'My Way', 1, '2026-09-14T13:22:04+00:00', '2026-09-14T13:22:04+00:00', 1000),
(8, 8, 'Baccarat Rouge', 1, '2026-09-14T13:22:55+00:00', '2026-09-14T13:22:55+00:00', 1000),
(10, 8, 'Dior Homme Perfume', 1, '2026-09-14T13:38:59+00:00', '2026-09-14T13:38:59+00:00', 1000),
(52, 8, 'Black Orchid', 0, '2026-09-14T15:50:34+00:00', '2026-09-14T15:51:00+00:00', 1000),
(14, 8, 'Emarude Super', 1, '2026-09-14T13:46:17+00:00', '2026-09-14T13:46:17+00:00', 500),
(15, 8, 'Wanted Azzaro', 1, '2026-09-14T13:47:31+00:00', '2026-09-14T13:47:31+00:00', 1000),
(17, 8, 'Pink Shiffon', 1, '2026-09-14T13:51:23+00:00', '2026-09-14T13:51:23+00:00', 1000),
(18, 8, 'Valaya Perfume de Marly Super', 1, '2026-09-14T13:55:31+00:00', '2026-09-14T13:55:31+00:00', 500),
(19, 8, 'Blue Talisman Ex Nihilo', 1, '2026-09-14T13:56:35+00:00', '2026-09-14T13:56:35+00:00', 500),
(20, 8, 'Hugo Boss Bottle Bold Citrus Men', 1, '2026-09-14T13:57:23+00:00', '2026-09-14T13:57:23+00:00', 500),
(74, 8, 'Million Gold for Woman Paco Rabane', 1, '2026-09-15T12:05:30+00:00', '2026-09-16T09:31:00+00:00', 1000),
(23, 8, 'Creed Aventus', 1, '2026-09-14T14:01:18+00:00', '2026-09-14T14:01:18+00:00', 1000),
(25, 8, 'Vanilla 28', 1, '2026-09-14T14:03:33+00:00', '2026-09-14T14:03:33+00:00', 1000),
(26, 8, 'Stronger with you', 1, '2026-09-14T14:06:37+00:00', '2026-09-14T14:06:37+00:00', 1000),
(79, 8, 'Club De Nuit', 1, '2026-09-15T12:47:10+00:00', '2026-09-24T07:38:37+00:00', 1000),
(28, 8, 'Coco Vanilla', 1, '2026-09-14T14:13:01+00:00', '2026-09-14T14:13:01+00:00', 1000),
(29, 8, 'Tomford Ombre Leather', 0, '2026-09-14T14:20:15+00:00', '2026-09-14T14:20:40+00:00', 1000),
(30, 8, 'Crystal Emerald Versacea', 0, '2026-09-14T14:25:51+00:00', '2026-09-14T14:26:16+00:00', 1000),
(82, 8, 'Valentino Donna Born in Roma Extradose', 1, '2026-09-15T12:54:37+00:00', '2026-09-25T08:14:04+00:00', 1000),
(32, 8, '1 Million', 0, '2026-09-14T14:29:09+00:00', '2026-09-22T08:50:37+00:00', 1000),
(33, 8, 'Mousof', 0, '2026-09-14T14:31:42+00:00', '2026-09-14T14:32:10+00:00', 1000),
(34, 8, 'Sugar Baby', 0, '2026-09-14T14:32:51+00:00', '2026-09-14T14:34:30+00:00', 1000),
(35, 8, 'Allure Homme Sport Super Leggera', 0, '2026-09-14T14:35:18+00:00', '2026-09-14T14:35:49+00:00', 1000),
(36, 8, '212 VIP Man', 0, '2026-09-14T14:38:20+00:00', '2026-09-14T14:38:56+00:00', 1000),
(37, 8, 'Scandal Man', 0, '2026-09-14T14:39:43+00:00', '2026-09-14T14:40:31+00:00', 1000),
(38, 8, 'Ajwad Pink', 0, '2026-09-14T14:41:18+00:00', '2026-09-14T14:42:29+00:00', 1000),
(39, 8, '9 pm', 0, '2026-09-14T14:43:10+00:00', '2026-09-14T14:43:53+00:00', 1000),
(40, 8, 'Olympea', 0, '2026-09-14T14:50:16+00:00', '2026-09-14T14:53:27+00:00', 1000),
(41, 8, 'Strawberry Letter Philur-2LZ', 0, '2026-09-14T14:54:13+00:00', '2026-09-14T14:55:37+00:00', 1000),
(42, 8, 'Good Girl', 0, '2026-09-14T15:05:00+00:00', '2026-09-14T15:05:56+00:00', 1000),
(43, 8, 'Polo Blue', 0, '2026-09-14T15:08:10+00:00', '2026-09-14T15:08:37+00:00', 1000),
(44, 8, 'Barcode', 0, '2026-09-14T15:10:51+00:00', '2026-09-14T15:12:15+00:00', 1000),
(22, 8, 'Eden Juicy Apple', 5, '2026-09-14T13:59:25+00:00', '2026-09-16T09:27:15+00:00', 1000),
(21, 8, 'Strawberry', 1, '2026-09-14T13:58:23+00:00', '2026-09-25T08:15:13+00:00', 1000),
(47, 8, 'Yum me, Sunny Escadae', 0, '2026-09-14T15:17:15+00:00', '2026-09-14T15:17:44+00:00', 1000),
(48, 8, 'Invictus', 0, '2026-09-14T15:18:33+00:00', '2026-09-14T15:19:05+00:00', 1000),
(49, 8, 'Legent Mont Blank Men', 0, '2026-09-14T15:35:47+00:00', '2026-09-14T15:36:21+00:00', 1000),
(50, 8, 'Escada Ocean Lounge', 0, '2026-09-14T15:37:44+00:00', '2026-09-14T15:38:21+00:00', 1000),
(53, 8, 'Jadore Women', 0, '2026-09-14T15:52:12+00:00', '2026-09-14T15:53:00+00:00', 1000),
(54, 8, 'Midnight Fantasy', 0, '2026-09-14T15:54:07+00:00', '2026-09-14T15:54:33+00:00', 1000),
(55, 8, 'Sauvage Elixir', 0, '2026-09-14T15:55:30+00:00', '2026-09-14T15:56:03+00:00', 1000),
(56, 8, 'Eclaire Banoffi Lotfa', 0, '2026-09-14T15:56:46+00:00', '2026-09-14T15:57:08+00:00', 1000),
(57, 8, 'Rashiqa', 0, '2026-09-14T15:58:10+00:00', '2026-09-14T15:58:39+00:00', 1000),
(58, 8, 'Sweet Camilla', 0, '2026-09-14T16:00:54+00:00', '2026-09-14T16:03:06+00:00', 1000),
(59, 8, 'Berries Weekend', 0, '2026-09-14T16:06:45+00:00', '2026-09-14T16:08:27+00:00', 1000),
(60, 8, 'Blue de Channel', 0, '2026-09-14T16:12:56+00:00', '2026-09-14T16:13:22+00:00', 1000),
(61, 8, 'Scandal', 0, '2026-09-14T16:14:06+00:00', '2026-09-14T16:16:23+00:00', 1000),
(62, 8, 'Erba Pura', 0, '2026-09-14T16:17:09+00:00', '2026-09-14T16:17:49+00:00', 1000),
(63, 8, 'Coconut Passion', 0, '2026-09-14T16:20:39+00:00', '2026-09-14T16:24:04+00:00', 1000),
(64, 8, 'YSL for men', 0, '2026-09-14T16:27:07+00:00', '2026-09-14T16:27:39+00:00', 1000),
(65, 8, 'Sweet Passion', 0, '2026-09-14T16:28:31+00:00', '2026-09-14T16:29:10+00:00', 1000),
(66, 8, 'Arman Code', 0, '2026-09-14T16:31:47+00:00', '2026-09-14T16:33:11+00:00', 1000),
(67, 8, 'Lacoste White', 0, '2026-09-14T16:34:16+00:00', '2026-09-14T16:34:45+00:00', 1000),
(68, 8, 'Roberto Carvali', 0, '2026-09-14T16:35:17+00:00', '2026-09-14T16:37:58+00:00', 1000),
(69, 8, 'Issey Miyake Men', 0, '2026-09-14T16:39:03+00:00', '2026-09-14T16:39:31+00:00', 1000),
(27, 8, 'Yara Candy', 1, '2026-09-14T14:09:42+00:00', '2026-09-14T16:43:30+00:00', 1000),
(70, 8, 'CR7 Legacy', 0, '2026-09-14T16:44:49+00:00', '2026-09-14T16:45:52+00:00', 1000),
(71, 8, 'Classic Stone', 0, '2026-09-14T16:46:29+00:00', '2026-09-14T16:49:39+00:00', 1000),
(72, 8, 'My Way Sunny Vanilla', 0, '2026-09-14T16:51:24+00:00', '2026-09-14T16:52:14+00:00', 1000),
(73, 8, 'Candy Rush', 0, '2026-09-14T16:53:35+00:00', '2026-09-14T16:55:28+00:00', 1000),
(84, 8, 'My Devotion', 1, '2026-09-15T13:03:29+00:00', '2026-09-17T12:54:49+00:00', 1000),
(76, 8, 'Blue Talisman Ex Nihilo-Top', 0, '2026-09-15T12:40:57+00:00', '2026-09-15T12:41:33+00:00', 1000),
(11, 8, 'Hibiscus Mahajad-Maison Criveli', 1, '2026-09-14T13:39:42+00:00', '2026-09-15T12:30:22+00:00', 1000),
(75, 8, 'Butterfly', 0, '2026-09-15T12:30:57+00:00', '2026-09-15T12:31:29+00:00', 1000),
(77, 8, 'La Nuit Tresore', 0, '2026-09-15T12:44:30+00:00', '2026-09-15T12:44:55+00:00', 1000),
(78, 8, 'OUD Maracuja Maison Criveli', 0, '2026-09-15T12:45:34+00:00', '2026-09-15T12:46:12+00:00', 1000),
(31, 8, 'Million Gold for Man Paco Rabane', 1, '2026-09-14T14:27:55+00:00', '2026-09-16T09:38:56+00:00', 1000),
(81, 8, 'Amouage Guidance 46 Top', 0, '2026-09-15T12:51:39+00:00', '2026-09-15T12:52:30+00:00', 1000),
(3, 8, '9 pm rebel', 0, '2026-09-14T13:13:44+00:00', '2026-09-29T09:38:47+00:00', 1000),
(83, 8, 'Pure Seduction', 0, '2026-09-15T13:01:06+00:00', '2026-09-15T13:01:32+00:00', 1000),
(46, 8, 'Chance Eau Splendide Chanel', 2, '2026-09-14T15:14:15+00:00', '2026-09-16T09:35:03+00:00', 1000),
(86, 8, 'Stronger with you Powerfully', 0, '2026-09-15T13:06:58+00:00', '2026-09-15T13:07:30+00:00', 1000),
(85, 8, 'Supremacy Afnan', 0, '2026-09-15T13:05:03+00:00', '2026-09-15T13:08:45+00:00', 1000),
(87, 8, 'Imagination', 0, '2026-09-15T13:09:16+00:00', '2026-09-15T13:10:24+00:00', 1000),
(5, 8, 'Khamrah Lattaffa', 0, '2026-09-14T13:19:56+00:00', '2026-09-29T09:37:28+00:00', 1000),
(4, 8, 'Sauvage Dior', 2, '2026-09-14T13:19:15+00:00', '2026-09-24T07:38:10+00:00', 1000),
(9, 8, 'Pink Sugar', 0, '2026-09-14T13:23:20+00:00', '2026-09-25T08:17:02+00:00', 1000),
(24, 8, 'Reef 33', 7, '2026-09-14T14:02:24+00:00', '2026-09-29T09:39:26+00:00', 1000),
(80, 8, 'Yum boujee Marshimallow81 -Kayali', 0, '2026-09-15T12:48:28+00:00', '2026-09-29T09:38:16+00:00', 1000),
(88, 8, 'Purpose Amouage', 0, '2026-09-16T09:09:58+00:00', '2026-09-16T09:11:47+00:00', 1000),
(89, 8, 'Reef 31', 1, '2026-09-16T09:38:13+00:00', '2026-09-16T09:38:13+00:00', 1000),
(90, 8, '212 VIP Man', 1, '2026-09-16T09:39:50+00:00', '2026-09-16T09:39:50+00:00', 500),
(91, 8, 'Sex Gravity', 0, '2026-09-17T12:52:13+00:00', '2026-09-17T12:52:46+00:00', 1000),
(92, 8, 'YARA PINK', 1, '2026-09-19T15:10:04+00:00', '2026-09-19T15:10:04+00:00', 1000),
(93, 8, 'OUD WOOD', 2, '2026-09-19T15:18:05+00:00', '2026-09-19T15:18:05+00:00', 500),
(94, 8, 'YSL LIBRE', 1, '2026-09-19T15:19:15+00:00', '2026-09-19T15:19:15+00:00', 500),
(95, 8, 'DELINA', 1, '2026-09-19T15:20:08+00:00', '2026-09-19T15:20:08+00:00', 500),
(96, 8, 'GUCCI GUILTY', 1, '2026-09-19T15:21:06+00:00', '2026-09-19T15:21:06+00:00', 500),
(97, 8, 'LADY MILLION', 1, '2026-09-19T15:22:23+00:00', '2026-09-19T15:22:23+00:00', 500),
(98, 9, '1 Million', 1, '2026-09-22T08:55:44+00:00', '2026-09-22T08:55:44+00:00', 1000),
(99, 8, '9 pm Black', 1, '2026-09-25T12:51:11+00:00', '2026-09-25T12:51:11+00:00', 1000),
(100, 8, 'ATLAS', 7, '2026-09-30T08:18:22+00:00', '2026-09-30T08:23:23+00:00', 1000);

-- oil_fragrance_movements (205 rows)
INSERT INTO public.oil_fragrance_movements (id, branch_id, name, type, quantity, reason, performed_by, created_at, updated_at, volume) VALUES
(10, 8, 'My Way', 'stock_out', 1, 'manufacturing [1000ml]', 29, '2026-09-13T12:36:25+00:00', '2026-09-13T12:36:25+00:00', 1000),
(8, 8, 'My Way', 'stock_in', 60, 'imported [1000ml]', 29, '2026-09-12T22:20:53+00:00', '2026-09-12T22:20:53+00:00', 1000),
(9, 8, 'My Way', 'stock_in', 40, 'imported [1000ml]', 29, '2026-09-12T22:33:30+00:00', '2026-09-12T22:33:30+00:00', 1000),
(11, 8, 'ACQUA DI GIO PROFONDO ARMANI', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T13:06:56+00:00', '2026-09-14T13:06:56+00:00', 1000),
(12, 8, '9 pm rebel', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T13:13:44+00:00', '2026-09-14T13:13:44+00:00', 1000),
(13, 8, '9 pm rebel', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T13:14:51+00:00', '2026-09-14T13:14:51+00:00', 1000),
(14, 8, 'Sauvage Dior', 'stock_in', 2, 'manufacturing [1000ml]', 32, '2026-09-14T13:19:15+00:00', '2026-09-14T13:19:15+00:00', 1000),
(15, 8, 'Khamrah Lattaffa', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T13:19:56+00:00', '2026-09-14T13:19:56+00:00', 1000),
(16, 8, 'Taj Sunset', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T13:21:04+00:00', '2026-09-14T13:21:04+00:00', 1000),
(17, 8, 'My Way', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T13:22:05+00:00', '2026-09-14T13:22:05+00:00', 1000),
(18, 8, 'Baccarat Rouge', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T13:22:56+00:00', '2026-09-14T13:22:56+00:00', 1000),
(19, 8, 'Pink Sugar', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T13:23:20+00:00', '2026-09-14T13:23:20+00:00', 1000),
(20, 8, 'Dior Homme Perfume', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T13:39:00+00:00', '2026-09-14T13:39:00+00:00', 1000),
(21, 8, 'Hibiscus Mahajad-Maison Criveli', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T13:39:42+00:00', '2026-09-14T13:39:42+00:00', 1000),
(22, 8, 'Emarude Super', 'stock_in', 1, 'imported [500ml]', 32, '2026-09-14T13:40:57+00:00', '2026-09-14T13:40:57+00:00', 500),
(23, 8, 'Emarude Super', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T13:41:27+00:00', '2026-09-14T13:41:27+00:00', 1000),
(24, 8, 'Emarude Super', 'stock_in', 1, 'imported [500ml]', 32, '2026-09-14T13:43:47+00:00', '2026-09-14T13:43:47+00:00', 500),
(25, 8, 'Emarude Super', 'stock_in', 1, 'Stock in [1000ml]', 32, '2026-09-14T13:44:17+00:00', '2026-09-14T13:44:17+00:00', 1000),
(26, 8, 'Emarude Super', 'stock_in', 1, 'imported [500ml]', 32, '2026-09-14T13:46:18+00:00', '2026-09-14T13:46:18+00:00', 500),
(27, 8, 'Wanted Azzaro', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T13:47:31+00:00', '2026-09-14T13:47:31+00:00', 1000),
(28, 8, 'Now Rave', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T13:49:43+00:00', '2026-09-14T13:49:43+00:00', 1000),
(29, 8, 'Pink Shiffon', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T13:51:23+00:00', '2026-09-14T13:51:23+00:00', 1000),
(30, 8, 'Valaya Perfume de Marly Super', 'stock_in', 1, 'imported [500ml]', 32, '2026-09-14T13:55:31+00:00', '2026-09-14T13:55:31+00:00', 500),
(31, 8, 'Blue Talisman Ex Nihilo', 'stock_in', 1, 'imported [500ml]', 32, '2026-09-14T13:56:36+00:00', '2026-09-14T13:56:36+00:00', 500),
(32, 8, 'Hugo Boss Bottle Bold Citrus Men', 'stock_in', 1, 'imported [500ml]', 32, '2026-09-14T13:57:24+00:00', '2026-09-14T13:57:24+00:00', 500),
(33, 8, 'Strawberry', 'stock_in', 2, 'imported [1000ml]', 32, '2026-09-14T13:58:23+00:00', '2026-09-14T13:58:23+00:00', 1000),
(34, 8, 'Eden Juicy Apple', 'stock_in', 2, 'imported [1000ml]', 32, '2026-09-14T13:59:25+00:00', '2026-09-14T13:59:25+00:00', 1000),
(35, 8, 'Eden Juicy Apple', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T13:59:52+00:00', '2026-09-14T13:59:52+00:00', 1000),
(36, 8, 'Creed Aventus', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:01:18+00:00', '2026-09-14T14:01:18+00:00', 1000),
(37, 8, 'Reef 33', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:02:25+00:00', '2026-09-14T14:02:25+00:00', 1000),
(38, 8, 'Vanilla 28', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:03:33+00:00', '2026-09-14T14:03:33+00:00', 1000),
(39, 8, 'Stronger with you', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:06:38+00:00', '2026-09-14T14:06:38+00:00', 1000),
(40, 8, 'Yara Candy', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:09:42+00:00', '2026-09-14T14:09:42+00:00', 1000),
(41, 8, 'Now Rave', 'stock_in', 1, 'Stock in [1000ml]', 32, '2026-09-14T14:10:33+00:00', '2026-09-14T14:10:33+00:00', 1000),
(42, 8, 'Coco Vanilla', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:13:01+00:00', '2026-09-14T14:13:01+00:00', 1000),
(43, 8, 'Tomford Ombre Leather', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:20:16+00:00', '2026-09-14T14:20:16+00:00', 1000),
(44, 8, 'Tomford Ombre Leather', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T14:20:40+00:00', '2026-09-14T14:20:40+00:00', 1000),
(45, 8, 'Crystal Emerald Versacea', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:25:51+00:00', '2026-09-14T14:25:51+00:00', 1000),
(46, 8, 'Crystal Emerald Versacea', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T14:26:16+00:00', '2026-09-14T14:26:16+00:00', 1000),
(47, 8, 'Million Gold for Man Paco Rabane', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:27:55+00:00', '2026-09-14T14:27:55+00:00', 1000),
(48, 8, 'Million Gold for Man Paco Rabane', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T14:28:32+00:00', '2026-09-14T14:28:32+00:00', 1000),
(49, 8, '1 Million', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:29:09+00:00', '2026-09-14T14:29:09+00:00', 1000),
(50, 8, '1 Million', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T14:29:44+00:00', '2026-09-14T14:29:44+00:00', 1000),
(51, 8, 'Mousof', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:31:42+00:00', '2026-09-14T14:31:42+00:00', 1000),
(52, 8, 'Mousof', 'stock_out', 1, 'Used for production [1000ml]', 32, '2026-09-14T14:32:10+00:00', '2026-09-14T14:32:10+00:00', 1000),
(53, 8, 'Sugar Baby', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:32:51+00:00', '2026-09-14T14:32:51+00:00', 1000),
(54, 8, 'Sugar Baby', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T14:34:30+00:00', '2026-09-14T14:34:30+00:00', 1000),
(55, 8, 'Allure Homme Sport Super Leggera', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:35:18+00:00', '2026-09-14T14:35:18+00:00', 1000),
(56, 8, 'Allure Homme Sport Super Leggera', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T14:35:49+00:00', '2026-09-14T14:35:49+00:00', 1000),
(57, 8, '212 VIP Man', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:38:20+00:00', '2026-09-14T14:38:20+00:00', 1000),
(58, 8, '212 VIP Man', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T14:38:56+00:00', '2026-09-14T14:38:56+00:00', 1000),
(59, 8, 'Scandal Man', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:39:43+00:00', '2026-09-14T14:39:43+00:00', 1000),
(60, 8, 'Scandal Man', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T14:40:31+00:00', '2026-09-14T14:40:31+00:00', 1000),
(61, 8, 'Ajwad Pink', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:41:18+00:00', '2026-09-14T14:41:18+00:00', 1000),
(62, 8, 'Ajwad Pink', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T14:42:30+00:00', '2026-09-14T14:42:30+00:00', 1000),
(63, 8, '9 pm', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:43:10+00:00', '2026-09-14T14:43:10+00:00', 1000),
(64, 8, '9 pm', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T14:43:53+00:00', '2026-09-14T14:43:53+00:00', 1000),
(65, 8, 'Olympea', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:50:16+00:00', '2026-09-14T14:50:16+00:00', 1000),
(66, 8, 'Olympea', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T14:53:27+00:00', '2026-09-14T14:53:27+00:00', 1000),
(67, 8, 'Strawberry Letter Philur-2LZ', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T14:54:13+00:00', '2026-09-14T14:54:13+00:00', 1000),
(68, 8, 'Strawberry Letter Philur-2LZ', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T14:55:38+00:00', '2026-09-14T14:55:38+00:00', 1000),
(69, 8, 'Good Girl', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T15:05:00+00:00', '2026-09-14T15:05:00+00:00', 1000),
(70, 8, 'Good Girl', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:05:57+00:00', '2026-09-14T15:05:57+00:00', 1000),
(71, 8, 'Polo Blue', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T15:08:10+00:00', '2026-09-14T15:08:10+00:00', 1000),
(72, 8, 'Polo Blue', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:08:37+00:00', '2026-09-14T15:08:37+00:00', 1000),
(73, 8, 'Barcode', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T15:10:51+00:00', '2026-09-14T15:10:51+00:00', 1000),
(74, 8, 'Barcode', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:12:16+00:00', '2026-09-14T15:12:16+00:00', 1000),
(75, 8, 'Eclair', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T15:12:54+00:00', '2026-09-14T15:12:54+00:00', 1000),
(76, 8, 'Eclair', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:13:24+00:00', '2026-09-14T15:13:24+00:00', 1000),
(77, 8, 'Chance Eau Splendide Chanel', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T15:14:15+00:00', '2026-09-14T15:14:15+00:00', 1000),
(78, 8, 'Chance Eau Splendide Chanel', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:15:05+00:00', '2026-09-14T15:15:05+00:00', 1000),
(79, 8, 'Yum me, Sunny Escadae', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T15:17:15+00:00', '2026-09-14T15:17:15+00:00', 1000),
(80, 8, 'Yum me, Sunny Escadae', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:17:44+00:00', '2026-09-14T15:17:44+00:00', 1000),
(81, 8, 'Invictus', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T15:18:33+00:00', '2026-09-14T15:18:33+00:00', 1000),
(82, 8, 'Invictus', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:19:05+00:00', '2026-09-14T15:19:05+00:00', 1000),
(83, 8, 'Legent Mont Blank Men', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T15:35:47+00:00', '2026-09-14T15:35:47+00:00', 1000),
(84, 8, 'Legent Mont Blank Men', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:36:22+00:00', '2026-09-14T15:36:22+00:00', 1000),
(85, 8, 'Escada Ocean Lounge', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T15:37:44+00:00', '2026-09-14T15:37:44+00:00', 1000),
(86, 8, 'Escada Ocean Lounge', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:38:22+00:00', '2026-09-14T15:38:22+00:00', 1000),
(87, 8, 'Golden Dust', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T15:41:30+00:00', '2026-09-14T15:41:30+00:00', 1000),
(88, 8, 'Golden Dust', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:44:14+00:00', '2026-09-14T15:44:14+00:00', 1000),
(89, 8, 'Black Orchid', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T15:50:34+00:00', '2026-09-14T15:50:34+00:00', 1000),
(90, 8, 'Black Orchid', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:51:00+00:00', '2026-09-14T15:51:00+00:00', 1000),
(91, 8, 'Jadore Women', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T15:52:12+00:00', '2026-09-14T15:52:12+00:00', 1000),
(92, 8, 'Jadore Women', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:53:00+00:00', '2026-09-14T15:53:00+00:00', 1000),
(93, 8, 'Midnight Fantasy', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T15:54:07+00:00', '2026-09-14T15:54:07+00:00', 1000),
(94, 8, 'Midnight Fantasy', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:54:33+00:00', '2026-09-14T15:54:33+00:00', 1000),
(95, 8, 'Sauvage Elixir', 'stock_in', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:55:30+00:00', '2026-09-14T15:55:30+00:00', 1000),
(96, 8, 'Sauvage Elixir', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:56:04+00:00', '2026-09-14T15:56:04+00:00', 1000),
(97, 8, 'Eclaire Banoffi Lotfa', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T15:56:47+00:00', '2026-09-14T15:56:47+00:00', 1000),
(98, 8, 'Eclaire Banoffi Lotfa', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:57:08+00:00', '2026-09-14T15:57:08+00:00', 1000),
(99, 8, 'Rashiqa', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T15:58:11+00:00', '2026-09-14T15:58:11+00:00', 1000),
(100, 8, 'Rashiqa', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T15:58:40+00:00', '2026-09-14T15:58:40+00:00', 1000),
(101, 8, 'Sweet Camilla', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:00:54+00:00', '2026-09-14T16:00:54+00:00', 1000),
(102, 8, 'Sweet Camilla', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:03:06+00:00', '2026-09-14T16:03:06+00:00', 1000),
(103, 8, 'Berries Weekend', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:06:46+00:00', '2026-09-14T16:06:46+00:00', 1000),
(104, 8, 'Berries Weekend', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:08:28+00:00', '2026-09-14T16:08:28+00:00', 1000),
(105, 8, 'Blue de Channel', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:12:57+00:00', '2026-09-14T16:12:57+00:00', 1000),
(106, 8, 'Blue de Channel', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:13:22+00:00', '2026-09-14T16:13:22+00:00', 1000),
(107, 8, 'Scandal', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:14:07+00:00', '2026-09-14T16:14:07+00:00', 1000),
(108, 8, 'Scandal', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:14:08+00:00', '2026-09-14T16:14:08+00:00', 1000),
(109, 8, 'Scandal', 'stock_out', 2, 'manufacturing [1000ml]', 32, '2026-09-14T16:16:23+00:00', '2026-09-14T16:16:23+00:00', 1000),
(110, 8, 'Erba Pura', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:17:09+00:00', '2026-09-14T16:17:09+00:00', 1000),
(111, 8, 'Erba Pura', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:17:49+00:00', '2026-09-14T16:17:49+00:00', 1000),
(112, 8, 'Coconut Passion', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:20:39+00:00', '2026-09-14T16:20:39+00:00', 1000),
(113, 8, 'Coconut Passion', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:24:04+00:00', '2026-09-14T16:24:04+00:00', 1000),
(114, 8, 'YSL for men', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:27:07+00:00', '2026-09-14T16:27:07+00:00', 1000),
(115, 8, 'YSL for men', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:27:39+00:00', '2026-09-14T16:27:39+00:00', 1000),
(116, 8, 'Sweet Passion', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:28:31+00:00', '2026-09-14T16:28:31+00:00', 1000),
(117, 8, 'Sweet Passion', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:29:10+00:00', '2026-09-14T16:29:10+00:00', 1000),
(118, 8, 'Arman Code', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:31:48+00:00', '2026-09-14T16:31:48+00:00', 1000),
(119, 8, 'Arman Code', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:33:11+00:00', '2026-09-14T16:33:11+00:00', 1000),
(120, 8, 'Lacoste White', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:34:16+00:00', '2026-09-14T16:34:16+00:00', 1000),
(121, 8, 'Lacoste White', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:34:45+00:00', '2026-09-14T16:34:45+00:00', 1000),
(122, 8, 'Roberto Carvali', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:35:17+00:00', '2026-09-14T16:35:17+00:00', 1000),
(123, 8, 'Roberto Carvali', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:37:58+00:00', '2026-09-14T16:37:58+00:00', 1000),
(124, 8, 'Issey Miyake Men', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:39:03+00:00', '2026-09-14T16:39:03+00:00', 1000),
(125, 8, 'Issey Miyake Men', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:39:31+00:00', '2026-09-14T16:39:31+00:00', 1000),
(126, 8, 'Yara Candy', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:42:12+00:00', '2026-09-14T16:42:12+00:00', 1000),
(127, 8, 'Yara Candy', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:43:30+00:00', '2026-09-14T16:43:30+00:00', 1000),
(128, 8, 'CR7 Legacy', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:44:50+00:00', '2026-09-14T16:44:50+00:00', 1000),
(129, 8, 'CR7 Legacy', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:45:52+00:00', '2026-09-14T16:45:52+00:00', 1000),
(130, 8, 'Classic Stone', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:46:29+00:00', '2026-09-14T16:46:29+00:00', 1000),
(131, 8, 'Classic Stone', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:49:40+00:00', '2026-09-14T16:49:40+00:00', 1000),
(132, 8, 'My Way Sunny Vanilla', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:51:25+00:00', '2026-09-14T16:51:25+00:00', 1000),
(133, 8, 'My Way Sunny Vanilla', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:52:14+00:00', '2026-09-14T16:52:14+00:00', 1000),
(134, 8, 'Candy Rush', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-14T16:53:35+00:00', '2026-09-14T16:53:35+00:00', 1000),
(135, 8, 'Candy Rush', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-14T16:55:28+00:00', '2026-09-14T16:55:28+00:00', 1000),
(136, 8, 'Million Gold for Woman Paco Rabane', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-15T12:05:30+00:00', '2026-09-15T12:05:30+00:00', 1000),
(137, 8, 'Million Gold for Woman Paco Rabane', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-15T12:06:05+00:00', '2026-09-15T12:06:05+00:00', 1000),
(138, 8, 'Hibiscus Mahajad-Maison Criveli', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-15T12:08:29+00:00', '2026-09-15T12:08:29+00:00', 1000),
(139, 8, 'Hibiscus Mahajad-Maison Criveli', 'stock_out', 1, 'manufacturing [500ml]', 32, '2026-09-15T12:09:00+00:00', '2026-09-15T12:09:00+00:00', 500),
(140, 8, 'Hibiscus Mahajad-Maison Criveli', 'stock_out', 1, 'manufacturing [500ml]', 32, '2026-09-15T12:10:25+00:00', '2026-09-15T12:10:25+00:00', 500),
(141, 8, 'Hibiscus Mahajad-Maison Criveli', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-15T12:30:22+00:00', '2026-09-15T12:30:22+00:00', 1000),
(142, 8, 'Butterfly', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-15T12:30:57+00:00', '2026-09-15T12:30:57+00:00', 1000),
(143, 8, 'Butterfly', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-15T12:31:30+00:00', '2026-09-15T12:31:30+00:00', 1000),
(144, 8, 'Blue Talisman Ex Nihilo-Top', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-15T12:40:57+00:00', '2026-09-15T12:40:57+00:00', 1000),
(145, 8, 'Blue Talisman Ex Nihilo-Top', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-15T12:41:33+00:00', '2026-09-15T12:41:33+00:00', 1000),
(146, 8, 'La Nuit Tresore', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-15T12:44:30+00:00', '2026-09-15T12:44:30+00:00', 1000),
(147, 8, 'La Nuit Tresore', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-15T12:44:55+00:00', '2026-09-15T12:44:55+00:00', 1000),
(148, 8, 'OUD Maracuja Maison Criveli', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-15T12:45:35+00:00', '2026-09-15T12:45:35+00:00', 1000),
(149, 8, 'OUD Maracuja Maison Criveli', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-15T12:46:12+00:00', '2026-09-15T12:46:12+00:00', 1000),
(150, 8, 'Club De Nuit', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-15T12:47:11+00:00', '2026-09-15T12:47:11+00:00', 1000),
(151, 8, 'Club De Nuit', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-15T12:47:45+00:00', '2026-09-15T12:47:45+00:00', 1000),
(152, 8, 'Yum boujee Marshimallow81 -Kayali', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-15T12:48:28+00:00', '2026-09-15T12:48:28+00:00', 1000),
(153, 8, 'Yum boujee Marshimallow81 -Kayali', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-15T12:49:26+00:00', '2026-09-15T12:49:26+00:00', 1000),
(154, 8, 'Amouage Guidance 46 Top', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-15T12:51:39+00:00', '2026-09-15T12:51:39+00:00', 1000),
(155, 8, 'Amouage Guidance 46 Top', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-15T12:52:31+00:00', '2026-09-15T12:52:31+00:00', 1000),
(156, 8, 'Valentino Donna Born in Roma Extradose', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-15T12:54:37+00:00', '2026-09-15T12:54:37+00:00', 1000),
(157, 8, 'Valentino Donna Born in Roma Extradose', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-15T13:00:19+00:00', '2026-09-15T13:00:19+00:00', 1000);
INSERT INTO public.oil_fragrance_movements (id, branch_id, name, type, quantity, reason, performed_by, created_at, updated_at, volume) VALUES
(158, 8, 'Pure Seduction', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-15T13:01:06+00:00', '2026-09-15T13:01:06+00:00', 1000),
(159, 8, 'Pure Seduction', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-15T13:01:32+00:00', '2026-09-15T13:01:32+00:00', 1000),
(160, 8, 'My Devotion', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-15T13:03:29+00:00', '2026-09-15T13:03:29+00:00', 1000),
(161, 8, 'My Devotion', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-15T13:04:15+00:00', '2026-09-15T13:04:15+00:00', 1000),
(162, 8, 'Supremacy Afnan', 'stock_in', 3, 'imported [1000ml]', 32, '2026-09-15T13:05:03+00:00', '2026-09-15T13:05:03+00:00', 1000),
(163, 8, 'Supremacy Afnan', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-15T13:05:43+00:00', '2026-09-15T13:05:43+00:00', 1000),
(164, 8, 'Stronger with you Powerfully', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-15T13:06:58+00:00', '2026-09-15T13:06:58+00:00', 1000),
(165, 8, 'Stronger with you Powerfully', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-15T13:07:30+00:00', '2026-09-15T13:07:30+00:00', 1000),
(166, 8, 'Supremacy Afnan', 'stock_out', 2, 'manufacturing [1000ml]', 32, '2026-09-15T13:08:46+00:00', '2026-09-15T13:08:46+00:00', 1000),
(167, 8, 'Imagination', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-15T13:09:16+00:00', '2026-09-15T13:09:16+00:00', 1000),
(168, 8, 'Imagination', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-15T13:10:24+00:00', '2026-09-15T13:10:24+00:00', 1000),
(169, 8, 'Purpose Amouage', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-16T09:09:58+00:00', '2026-09-16T09:09:58+00:00', 1000),
(170, 8, 'Purpose Amouage', 'stock_out', 1, 'Used for production [1000ml]', 32, '2026-09-16T09:11:47+00:00', '2026-09-16T09:11:47+00:00', 1000),
(171, 8, 'Now Rave', 'stock_out', 1, 'Manual adjustment', 32, '2026-09-16T09:13:02+00:00', '2026-09-16T09:13:02+00:00', 1000),
(172, 8, 'Sauvage Dior', 'stock_out', 1, 'Manual adjustment', 32, '2026-09-16T09:21:44+00:00', '2026-09-16T09:21:44+00:00', 1000),
(173, 8, 'Eclair', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-16T09:23:19+00:00', '2026-09-16T09:23:19+00:00', 1000),
(174, 8, 'Eden Juicy Apple', 'stock_in', 4, 'imported [1000ml]', 32, '2026-09-16T09:27:15+00:00', '2026-09-16T09:27:15+00:00', 1000),
(175, 8, 'Million Gold for Woman Paco Rabane', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-16T09:31:00+00:00', '2026-09-16T09:31:00+00:00', 1000),
(176, 8, 'Reef 33', 'stock_in', 16, 'imported [1000ml]', 32, '2026-09-16T09:32:28+00:00', '2026-09-16T09:32:28+00:00', 1000),
(177, 8, 'My Devotion', 'stock_in', 2, 'imported [1000ml]', 32, '2026-09-16T09:33:55+00:00', '2026-09-16T09:33:55+00:00', 1000),
(178, 8, 'Chance Eau Splendide Chanel', 'stock_in', 2, 'imported [1000ml]', 32, '2026-09-16T09:35:03+00:00', '2026-09-16T09:35:03+00:00', 1000),
(179, 8, 'Valentino Donna Born in Roma Extradose', 'stock_in', 2, 'imported [1000ml]', 32, '2026-09-16T09:35:42+00:00', '2026-09-16T09:35:42+00:00', 1000),
(180, 8, 'Yum boujee Marshimallow81 -Kayali', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-16T09:36:15+00:00', '2026-09-16T09:36:15+00:00', 1000),
(181, 8, 'Reef 31', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-16T09:38:13+00:00', '2026-09-16T09:38:13+00:00', 1000),
(182, 8, 'Million Gold for Man Paco Rabane', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-16T09:38:56+00:00', '2026-09-16T09:38:56+00:00', 1000),
(183, 8, '212 VIP Man', 'stock_in', 1, 'imported [500ml]', 32, '2026-09-16T09:39:50+00:00', '2026-09-16T09:39:50+00:00', 500),
(184, 8, 'Sex Gravity', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-17T12:52:14+00:00', '2026-09-17T12:52:14+00:00', 1000),
(185, 8, 'Sex Gravity', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-17T12:52:46+00:00', '2026-09-17T12:52:46+00:00', 1000),
(186, 8, 'Reef 33', 'stock_out', 4, 'manufacturing [1000ml]', 32, '2026-09-17T12:53:39+00:00', '2026-09-17T12:53:39+00:00', 1000),
(187, 8, 'My Devotion', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-17T12:54:49+00:00', '2026-09-17T12:54:49+00:00', 1000),
(188, 8, 'YARA PINK', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-19T15:10:04+00:00', '2026-09-19T15:10:04+00:00', 1000),
(189, 8, 'OUD WOOD', 'stock_in', 2, 'imported [500ml]', 32, '2026-09-19T15:18:05+00:00', '2026-09-19T15:18:05+00:00', 500),
(190, 8, 'YSL LIBRE', 'stock_in', 1, 'imported [500ml]', 32, '2026-09-19T15:19:16+00:00', '2026-09-19T15:19:16+00:00', 500),
(191, 8, 'DELINA', 'stock_in', 1, 'imported [500ml]', 32, '2026-09-19T15:20:08+00:00', '2026-09-19T15:20:08+00:00', 500),
(192, 8, 'GUCCI GUILTY', 'stock_in', 1, 'imported [500ml]', 32, '2026-09-19T15:21:07+00:00', '2026-09-19T15:21:07+00:00', 500),
(193, 8, 'LADY MILLION', 'stock_in', 1, 'imported [500ml]', 32, '2026-09-19T15:22:24+00:00', '2026-09-19T15:22:24+00:00', 500),
(194, 8, '1 Million', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-21T08:05:12+00:00', '2026-09-21T08:05:12+00:00', 1000),
(195, 8, '1 Million', 'stock_out', 1, 'Stock transfer to Dodoma branch (TF-20260922085037-2B1E)', 32, '2026-09-22T08:50:37+00:00', '2026-09-22T08:50:37+00:00', 1000),
(196, 9, '1 Million', 'stock_in', 1, 'Received from stock transfer TF-20260922085037-2B1E (from Kinondoni branch)', 36, '2026-09-22T08:55:44+00:00', '2026-09-22T08:55:44+00:00', 1000),
(197, 8, 'Sauvage Dior', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-24T07:38:11+00:00', '2026-09-24T07:38:11+00:00', 1000),
(198, 8, 'Club De Nuit', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-24T07:38:37+00:00', '2026-09-24T07:38:37+00:00', 1000),
(199, 8, 'Reef 33', 'stock_out', 3, 'manufacturing [1000ml]', 32, '2026-09-25T08:13:09+00:00', '2026-09-25T08:13:09+00:00', 1000),
(200, 8, 'Valentino Donna Born in Roma Extradose', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-25T08:14:04+00:00', '2026-09-25T08:14:04+00:00', 1000),
(201, 8, 'Strawberry', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-25T08:15:13+00:00', '2026-09-25T08:15:13+00:00', 1000),
(202, 8, 'Pink Sugar', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-25T08:17:02+00:00', '2026-09-25T08:17:02+00:00', 1000),
(203, 8, '9 pm Black', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-25T12:51:11+00:00', '2026-09-25T12:51:11+00:00', 1000),
(204, 8, '9 pm rebel', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-25T12:51:47+00:00', '2026-09-25T12:51:47+00:00', 1000),
(205, 8, 'Eclair', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-25T12:52:24+00:00', '2026-09-25T12:52:24+00:00', 1000),
(206, 8, 'Now Rave', 'stock_in', 1, 'imported [1000ml]', 32, '2026-09-25T12:52:57+00:00', '2026-09-25T12:52:57+00:00', 1000),
(207, 8, 'Khamrah Lattaffa', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-29T09:37:28+00:00', '2026-09-29T09:37:28+00:00', 1000),
(208, 8, 'Yum boujee Marshimallow81 -Kayali', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-29T09:38:16+00:00', '2026-09-29T09:38:16+00:00', 1000),
(209, 8, '9 pm rebel', 'stock_out', 1, 'manufacturing [1000ml]', 32, '2026-09-29T09:38:48+00:00', '2026-09-29T09:38:48+00:00', 1000),
(210, 8, 'Reef 33', 'stock_out', 3, 'manufacturing [1000ml]', 32, '2026-09-29T09:39:26+00:00', '2026-09-29T09:39:26+00:00', 1000),
(211, 8, 'ATLAS', 'stock_in', 10, 'imported [1000ml]', 32, '2026-09-30T08:18:22+00:00', '2026-09-30T08:18:22+00:00', 1000),
(212, 8, 'ATLAS', 'stock_out', 3, 'Manual adjustment', 32, '2026-09-30T08:23:23+00:00', '2026-09-30T08:23:23+00:00', 1000);

-- branch_stock (7 rows)
INSERT INTO public.branch_stock (id, branch_id, product_id, quantity, buying_cost, selling_price, supplier, date_received, entered_by, created_at, updated_at, category) VALUES
(73, 10, 345, 548, 0.0, 54000.0, NULL, '2026-09-22', 37, '2026-09-21T08:11:04', '2026-09-22T07:04:31', 'Oil Fragrance'),
(75, 9, 99, 3, 0.0, 45000.0, NULL, '2026-09-22', 36, '2026-09-22T08:28:50', '2026-09-22T08:28:50', 'Oil Fragrance'),
(74, 9, 345, 60, 0.0, 54000.0, NULL, '2026-09-22', 36, '2026-09-21T08:17:44', '2026-09-22T08:29:00', 'Oil Fragrance'),
(76, 8, 299, 1, 0.0, 70000.0, NULL, '2026-09-24', 32, '2026-09-24T09:20:21', '2026-09-24T09:20:21', 'Brand Perfume'),
(77, 8, 45, 124, 0.0, 45000.0, NULL, '2026-10-01', 32, '2026-09-25T15:59:58', '2026-10-02T18:51:17', 'Oil Fragrance'),
(65, 9, 53, 2, 0.0, 45000.0, NULL, '2026-09-16', 36, '2026-09-16T15:16:41', '2026-09-16T15:16:41', 'Oil Fragrance'),
(66, 10, 53, 1, 0.0, 45000.0, NULL, '2026-09-16', 37, '2026-09-16T15:24:49', '2026-09-16T15:32:46', 'Brand Perfume');

-- branch_stock_varieties (4 rows)
INSERT INTO public.branch_stock_varieties (id, branch_id, product_id, volume, variant, quantity, created_at, updated_at, selling_price) VALUES
(4, 10, 345, 50, 'box_logo_yellow', 550, '2026-09-21T08:11:05+00:00', '2026-09-22T07:04:32+00:00', 0.0),
(5, 9, 345, 30, 'box_logo_yellow', 60, '2026-09-21T08:17:45+00:00', '2026-09-22T08:29:00+00:00', 37000.0),
(7, 8, 45, 100, 'box_logo_yellow', 71, '2026-09-25T16:00:00+00:00', '2026-09-29T09:41:32+00:00', 65000.0),
(8, 8, 45, 50, 'box_logo_yellow', 66, '2026-09-25T16:03:34+00:00', '2026-10-01T10:29:43+00:00', 45000.0);

-- stock_movements (51 rows)
INSERT INTO public.stock_movements (id, branch_id, product_id, type, quantity, unit_cost, unit_price, reference_type, reference_id, performed_by, notes, created_at, updated_at) VALUES
(31, 8, 99, 'sale', -3, NULL, 45000.0, 'sale', 62, 29, 'Sale SALE-20260913075345-EE26 (Discounted)', '2026-09-13T07:53:45', '2026-09-13T07:53:45'),
(32, 8, 99, 'sale', -1, NULL, 45000.0, 'sale', 63, 29, 'Sale SALE-20260913075519-812F', '2026-09-13T07:55:19', '2026-09-13T07:55:19'),
(33, 8, 99, 'sale', -1, NULL, 45000.0, 'sale', 64, 29, 'Sale SALE-20260913080147-0FB6', '2026-09-13T08:01:47', '2026-09-13T08:01:47'),
(34, 8, 99, 'sale', -5, NULL, 45000.0, 'sale', 65, 29, 'Sale SALE-20260913080216-1636', '2026-09-13T08:02:16', '2026-09-13T08:02:16'),
(35, 8, 99, 'sale', -6, NULL, 45000.0, 'sale', 66, 29, 'Sale SALE-20260913080308-9CFA', '2026-09-13T08:03:09', '2026-09-13T08:03:09'),
(38, 8, 99, 'sale', -1, NULL, 45000.0, NULL, NULL, 29, 'Manual stock adjustment', '2026-09-13T11:34:23', '2026-09-13T11:34:23'),
(39, 8, 99, 'sale', -4, NULL, 45000.0, NULL, NULL, 29, 'Manual stock adjustment', '2026-09-13T11:49:59', '2026-09-13T11:49:59'),
(29, 8, 99, 'entry', 40, NULL, 45000.0, NULL, NULL, 29, 'Stock entry', '2026-09-13T06:23:45', '2026-09-13T06:23:45'),
(30, 8, 99, 'entry', 11, NULL, 45000.0, NULL, NULL, 29, 'Manual stock adjustment', '2026-09-13T06:32:59', '2026-09-13T06:32:59'),
(36, 8, 99, 'sale', -1, NULL, 45000.0, NULL, NULL, 29, 'Manual stock adjustment', '2026-09-13T10:44:48', '2026-09-13T10:44:48'),
(37, 8, 99, 'entry', 1, NULL, 45000.0, NULL, NULL, 29, 'Manual stock adjustment', '2026-09-13T10:46:11', '2026-09-13T10:46:11'),
(40, 8, 99, 'sale', -1, NULL, 45000.0, 'sale', 71, 32, 'Sale SALE-20260914102316-0E70', '2026-09-14T10:23:17', '2026-09-14T10:23:17'),
(41, 8, 99, 'sale', -1, NULL, 45000.0, 'sale', 72, 32, 'Sale SALE-20260915132638-FA9C', '2026-09-15T13:26:38', '2026-09-15T13:26:38'),
(42, 8, 99, 'sale', -1, NULL, 45000.0, 'sale', 73, 23, 'Sale SALE-20260916145704-B28F', '2026-09-16T14:57:05', '2026-09-16T14:57:05'),
(43, 9, 53, 'entry', 2, NULL, 45000.0, NULL, NULL, 36, 'Stock entry', '2026-09-16T15:16:41', '2026-09-16T15:16:41'),
(44, 10, 53, 'entry', 3, NULL, 45000.0, NULL, NULL, 37, 'Stock entry', '2026-09-16T15:24:50', '2026-09-16T15:24:50'),
(45, 10, 53, 'sale', -1, NULL, 45000.0, 'sale', 74, 42, 'Sale SALE-20260916152654-FE3C', '2026-09-16T15:26:54', '2026-09-16T15:26:54'),
(46, 10, 53, 'sale', -1, NULL, 45000.0, 'sale', 75, 42, 'Sale SALE-20260916153244-CE15', '2026-09-16T15:32:45', '2026-09-16T15:32:45'),
(47, 8, 56, 'entry', 5, NULL, 35000.0, NULL, NULL, 32, 'Stock entry', '2026-09-17T11:48:54', '2026-09-17T11:48:54'),
(48, 8, 56, 'entry', 1, NULL, 35000.0, NULL, NULL, 32, 'Manual stock adjustment', '2026-09-17T12:49:14', '2026-09-17T12:49:14'),
(49, 8, 29, 'entry', 6, NULL, 7000.0, NULL, NULL, 32, 'Stock entry', '2026-09-19T14:57:40', '2026-09-19T14:57:40'),
(50, 8, 98, 'entry', 1, NULL, 35000.0, NULL, NULL, 32, 'Stock entry', '2026-09-19T15:24:46', '2026-09-19T15:24:46'),
(51, 8, 98, 'sale', -1, NULL, 35000.0, 'sale', 77, 32, 'Sale SALE-20260919152643-EF56', '2026-09-19T15:26:43', '2026-09-19T15:26:43'),
(52, 8, 345, 'entry', 200, NULL, 54000.0, NULL, NULL, 29, 'Stock entry', '2026-09-19T15:47:35', '2026-09-19T15:47:35'),
(53, 8, 345, 'transfer_out', -150, 0.0, 54000.0, NULL, NULL, 29, 'Stock transfer to Head Quarters-Mikocheni (TF-20260919170045-8E2A) â€” 50ml With Box Â· With Logo Â· Yellow', '2026-09-19T17:00:45', '2026-09-19T17:00:45'),
(54, 8, 345, 'transfer_out', -20, 0.0, 54000.0, NULL, NULL, 29, 'Stock transfer to Dodoma branch (TF-20260919170247-443D) â€” 30ml With Box Â· With Logo Â· Yellow', '2026-09-19T17:02:48', '2026-09-19T17:02:48'),
(55, 8, 345, 'entry', 200, NULL, 54000.0, NULL, NULL, 29, 'Stock entry', '2026-09-19T17:27:19', '2026-09-19T17:27:19'),
(56, 8, 345, 'entry', 100, NULL, 54000.0, NULL, NULL, 29, 'Stock entry', '2026-09-19T19:38:26', '2026-09-19T19:38:26'),
(57, 8, 345, 'entry', 50, NULL, 105000.0, NULL, NULL, 29, 'Stock entry', '2026-09-19T19:40:38', '2026-09-19T19:40:38'),
(58, 8, 345, 'entry', 50, NULL, 54000.0, NULL, NULL, 29, 'Stock entry', '2026-09-19T19:42:23', '2026-09-19T19:42:23'),
(59, 8, 345, 'transfer_out', -100, 0.0, 54000.0, NULL, NULL, 29, 'Stock transfer to Head Quarters-Mikocheni (TF-20260919194801-192A) â€” 50ml With Box Â· With Logo Â· Yellow', '2026-09-19T19:48:02', '2026-09-19T19:48:02'),
(60, 8, 345, 'entry', 20, NULL, 37000.0, NULL, NULL, 29, 'Stock entry', '2026-09-19T20:41:28', '2026-09-19T20:41:28'),
(61, 10, 345, 'transfer_in', 150, 0.0, 54000.0, NULL, NULL, 37, 'Received from stock transfer TF-20260919170045-8E2A (from Kinondoni branch) â€” 50ml With Box Â· With Logo Â· Yellow', '2026-09-21T08:11:04', '2026-09-21T08:11:04'),
(62, 9, 345, 'transfer_in', 20, 0.0, 54000.0, NULL, NULL, 36, 'Received from stock transfer TF-20260919170247-443D (from Kinondoni branch) â€” 30ml With Box Â· With Logo Â· Yellow', '2026-09-21T08:17:44', '2026-09-21T08:17:44'),
(63, 10, 345, 'transfer_in', 100, 0.0, 54000.0, NULL, NULL, 37, 'Received from stock transfer TF-20260919194801-192A (from Kinondoni branch) â€” 50ml With Box Â· With Logo Â· Yellow', '2026-09-21T08:38:35', '2026-09-21T08:38:35'),
(64, 10, 345, 'sale', -2, NULL, 54000.0, 'sale', 78, 37, 'Sale SALE-20260921084945-444C (Discounted)', '2026-09-21T08:49:45', '2026-09-21T08:49:45'),
(65, 9, 345, 'transfer_in', 20, 0.0, 54000.0, NULL, NULL, 36, 'Received from stock transfer TF-20260919170247-443D (from Kinondoni branch) â€” 30ml With Box Â· With Logo Â· Yellow', '2026-09-22T06:03:09', '2026-09-22T06:03:09'),
(66, 10, 345, 'transfer_in', 150, 0.0, 54000.0, NULL, NULL, 37, 'Received from stock transfer TF-20260919170045-8E2A (from Kinondoni branch) â€” 50ml With Box Â· With Logo Â· Yellow', '2026-09-22T06:05:34', '2026-09-22T06:05:34'),
(67, 10, 345, 'transfer_in', 150, 0.0, 54000.0, NULL, NULL, 37, 'Received from stock transfer TF-20260919170045-8E2A (from Kinondoni branch) â€” 50ml With Box Â· With Logo Â· Yellow', '2026-09-22T07:04:31', '2026-09-22T07:04:31'),
(68, 8, 345, 'transfer_in', 20, 0.0, 54000.0, NULL, NULL, 36, 'Returned by Dodoma branch â€” invalid item from stock transfer TF-20260919170247-443D â€” 30ml With Box Â· With Logo Â· Yellow', '2026-09-22T07:52:16', '2026-09-22T07:52:16'),
(69, 8, 99, 'transfer_out', -3, 0.0, 45000.0, NULL, NULL, 32, 'Stock transfer to Dodoma branch (TF-20260922082057-1D0B)', '2026-09-22T08:20:58', '2026-09-22T08:20:58'),
(70, 8, 345, 'transfer_out', -20, 0.0, 37000.0, NULL, NULL, 32, 'Stock transfer to Dodoma branch (TF-20260922082152-EE96) â€” 30ml With Box Â· With Logo Â· Yellow', '2026-09-22T08:21:53', '2026-09-22T08:21:53'),
(71, 9, 99, 'transfer_in', 3, 0.0, 45000.0, NULL, NULL, 36, 'Received from stock transfer TF-20260922082057-1D0B (from Kinondoni branch)', '2026-09-22T08:28:50', '2026-09-22T08:28:50'),
(72, 9, 345, 'transfer_in', 20, 0.0, 37000.0, NULL, NULL, 36, 'Received from stock transfer TF-20260922082152-EE96 (from Kinondoni branch) â€” 30ml With Box Â· With Logo Â· Yellow', '2026-09-22T08:29:00', '2026-09-22T08:29:00'),
(73, 8, 299, 'entry', 1, NULL, 70000.0, NULL, NULL, 32, 'Stock entry', '2026-09-24T09:20:21', '2026-09-24T09:20:21'),
(74, 8, 45, 'entry', 27, NULL, 65000.0, NULL, NULL, 32, 'Stock entry', '2026-09-25T15:59:58', '2026-09-25T15:59:58'),
(75, 8, 45, 'entry', 19, NULL, 45000.0, NULL, NULL, 32, 'Stock entry', '2026-09-25T16:03:32', '2026-09-25T16:03:32'),
(76, 8, 45, 'entry', 44, NULL, 65000.0, NULL, NULL, 32, 'Stock entry', '2026-09-29T09:41:31', '2026-09-29T09:41:31'),
(77, 8, 45, 'sale', -11, NULL, 65000.0, 'sale', 84, 32, 'Sale SALE-20260929095049-8699', '2026-09-29T09:50:50', '2026-09-29T09:50:50'),
(78, 8, 45, 'entry', 47, NULL, 45000.0, NULL, NULL, 32, 'Stock entry', '2026-10-01T10:29:42', '2026-10-01T10:29:42'),
(79, 8, 45, 'sale', -2, NULL, 45000.0, 'sale', 86, 32, 'Sale SALE-20261002185116-3BF9', '2026-10-02T18:51:17', '2026-10-02T18:51:17');

-- sales (40 rows)
INSERT INTO public.sales (id, sale_number, branch_id, cashier_id, customer_id, subtotal, discount, total, payment_status, notes, created_at, updated_at, supplier, payment_method, payment_summary, sale_type) VALUES
(18, 'SALE-000018', 8, 2, 11, 54000.0, 0.0, 54000.0, 'paid', NULL, '2026-08-24T22:31:29', '2026-08-24T22:31:38', NULL, NULL, NULL, NULL),
(19, 'SALE-000019', 8, 19, NULL, 340000.0, 0.0, 340000.0, 'paid', NULL, '2026-08-27T22:08:55', '2026-08-27T22:09:08', NULL, NULL, NULL, NULL),
(20, 'SALE-000020', 8, 19, 12, 372000.0, 0.0, 372000.0, 'paid', 'Converted from order ORD-000008', '2026-08-28T22:15:05', '2026-08-28T22:15:05', NULL, NULL, NULL, NULL),
(21, 'SALE-20260830132353-E077', 8, 19, 13, 35000.0, 0.0, 35000.0, 'paid', NULL, '2026-08-30T13:23:55', '2026-08-30T13:23:55', NULL, NULL, NULL, NULL),
(22, 'SALE-20260830170424-AACA', 8, 18, 2, 70000.0, 0.0, 70000.0, 'paid', NULL, '2026-08-30T17:04:26', '2026-08-30T17:04:26', NULL, 'multi', NULL, NULL),
(23, 'SALE-20260830171246-6D3A', 8, 18, 1, 108000.0, 0.0, 108000.0, 'paid', NULL, '2026-08-30T17:12:48', '2026-08-30T17:12:48', NULL, 'cash', 'Cash 54,000, Bank transfer 54,000', NULL),
(24, 'SALE-20260830233311-58E6', 8, 19, 12, 175000.0, 0.0, 175000.0, 'paid', 'Converted from order ORD-000010', '2026-08-30T23:33:11', '2026-08-30T23:33:11', NULL, NULL, NULL, NULL),
(25, 'SALE-20260830233327-9BD5', 8, 19, 12, 372000.0, 0.0, 372000.0, 'paid', 'Converted from order ORD-000009', '2026-08-30T23:33:27', '2026-08-30T23:33:27', NULL, NULL, NULL, NULL),
(27, 'COLTEST-6a9f2550091e5', 8, 8, NULL, 1000.0, 0.0, 1000.0, 'paid', NULL, '2026-09-07T20:57:52', '2026-09-07T20:57:52', NULL, 'cash', 'cash 1,000', NULL),
(32, 'STRIP4-TEST-6a9f2a2b09259', 8, 8, NULL, 17000.0, 0.0, 17000.0, 'paid', NULL, '2026-09-07T21:18:35', '2026-09-07T21:18:35', NULL, 'cash', 'cash 17,000', NULL),
(40, 'SIMPLER-TEST-6a9f2f9f21cb0', 8, 8, NULL, 30000.0, 0.0, 30000.0, 'paid', NULL, '2026-09-07T21:41:51', '2026-09-07T21:41:51', NULL, 'cash', 'cash 30,000', NULL),
(57, 'SALE-20260908233730-06D2', 8, 29, 11, 40000.0, 0.0, 40000.0, 'paid', NULL, '2026-09-08T23:37:30', '2026-09-08T23:37:30', NULL, 'cash', 'Cash 40,000, Bank transfer 40,000', NULL),
(58, 'SALE-20260908233949-21B3', 8, 29, NULL, 800000.0, 0.0, 800000.0, 'paid', NULL, '2026-09-08T23:39:49', '2026-09-08T23:39:49', NULL, 'cash', 'Cash 800,000', NULL),
(59, 'SALE-20260908234155-EA3C', 8, 29, 11, 40000.0, 0.0, 40000.0, 'paid', NULL, '2026-09-08T23:41:56', '2026-09-08T23:41:56', NULL, 'cash', 'Cash 40,000', NULL),
(60, 'SALE-20260909001052-5CE7', 8, 29, 15, 40000.0, 0.0, 40000.0, 'paid', NULL, '2026-09-09T00:10:53', '2026-09-09T00:10:53', NULL, 'cash', 'Cash 20,000, Bank transfer 20,000', NULL),
(61, 'SALE-20260910063324-6FCB', 8, 32, NULL, 5000.0, 0.0, 5000.0, 'paid', NULL, '2026-09-10T06:33:25', '2026-09-10T06:33:25', NULL, 'cash', 'Cash 5,000', NULL),
(62, 'SALE-20260913075345-EE26', 8, 29, NULL, 150000.0, 0.0, 150000.0, 'paid', NULL, '2026-09-13T07:53:45', '2026-09-13T07:53:45', NULL, 'cash', 'Cash 150,000', NULL),
(63, 'SALE-20260913075519-812F', 8, 29, NULL, 45000.0, 0.0, 45000.0, 'paid', NULL, '2026-09-13T07:55:19', '2026-09-13T07:55:19', NULL, 'cash', 'Cash 45,000', NULL),
(64, 'SALE-20260913080147-0FB6', 8, 29, NULL, 45000.0, 0.0, 45000.0, 'paid', NULL, '2026-09-13T08:01:47', '2026-09-13T08:01:47', NULL, 'cash', 'Cash 45,000', NULL),
(65, 'SALE-20260913080216-1636', 8, 29, NULL, 225000.0, 0.0, 225000.0, 'paid', NULL, '2026-09-13T08:02:16', '2026-09-13T08:02:16', NULL, 'cash', 'Cash 225,000', NULL),
(66, 'SALE-20260913080308-9CFA', 8, 29, NULL, 270000.0, 0.0, 270000.0, 'paid', NULL, '2026-09-13T08:03:09', '2026-09-13T08:03:09', NULL, 'cash', 'Cash 200,000, Bank transfer 70,000', NULL),
(68, 'SALE-20260913101409-4BCC', 8, 29, NULL, 20000.0, 0.0, 20000.0, 'paid', NULL, '2026-09-13T10:14:10', '2026-09-13T10:14:10', NULL, 'cash', 'Cash 20,000', NULL),
(69, 'SALE-20260913101716-9C9B', 8, 29, NULL, 60000.0, 0.0, 60000.0, 'paid', NULL, '2026-09-13T10:17:17', '2026-09-13T10:17:17', NULL, 'cash', 'Cash 30,000, Cash 30,000', NULL),
(70, 'SALE-20260913114900-8315', 8, 29, NULL, 20000.0, 0.0, 20000.0, 'paid', NULL, '2026-09-13T11:49:03', '2026-09-13T11:49:03', NULL, 'cash', 'Cash 20,000', 'wholesale'),
(71, 'SALE-20260914102316-0E70', 8, 32, NULL, 45000.0, 0.0, 45000.0, 'paid', NULL, '2026-09-14T10:23:17', '2026-09-14T10:23:17', NULL, 'cash', 'Cash 45,000', 'retail'),
(72, 'SALE-20260915132638-FA9C', 8, 32, NULL, 259000.0, 0.0, 259000.0, 'paid', NULL, '2026-09-15T13:26:40', '2026-09-15T13:26:40', NULL, 'cash', 'Cash 259,000', 'wholesale'),
(73, 'SALE-20260916145704-B28F', 8, 23, NULL, 45000.0, 0.0, 45000.0, 'paid', NULL, '2026-09-16T14:57:05', '2026-09-16T14:57:05', NULL, 'cash', 'Cash 45,000', 'retail'),
(74, 'SALE-20260916152654-FE3C', 10, 42, NULL, 45000.0, 0.0, 45000.0, 'paid', NULL, '2026-09-16T15:26:54', '2026-09-16T15:26:54', NULL, 'cash', 'Cash 45,000', 'retail'),
(75, 'SALE-20260916153244-CE15', 10, 42, NULL, 45000.0, 0.0, 45000.0, 'paid', NULL, '2026-09-16T15:32:45', '2026-09-16T15:32:45', NULL, 'cash', 'Cash 20,000, Mobile payment 25,000', 'retail'),
(76, 'SALE-20260919145439-06DE', 8, 32, NULL, 18000.0, 0.0, 18000.0, 'paid', NULL, '2026-09-19T14:54:40', '2026-09-19T14:54:40', NULL, 'cash', 'Cash 18,000', 'wholesale'),
(77, 'SALE-20260919152643-EF56', 8, 32, 16, 35000.0, 0.0, 35000.0, 'paid', NULL, '2026-09-19T15:26:43', '2026-09-19T15:26:43', NULL, 'cash', 'Cash 35,000', 'retail'),
(78, 'SALE-20260921084945-444C', 10, 37, NULL, 100000.0, 0.0, 100000.0, 'paid', NULL, '2026-09-21T08:49:45', '2026-09-21T08:49:45', NULL, 'cash', 'Cash 80,000, Bank transfer 10,000, Mobile payment 10,000', 'retail'),
(79, 'SALE-20260923090932-1DD3', 8, 32, NULL, 3000.0, 0.0, 3000.0, 'paid', NULL, '2026-09-23T09:09:33', '2026-09-23T09:09:33', NULL, 'cash', 'Cash 3,000', 'wholesale'),
(80, 'SALE-20260925162429-713A', 8, 32, NULL, 310000.0, 0.0, 310000.0, 'paid', NULL, '2026-09-25T16:24:30', '2026-09-25T16:24:30', NULL, 'cash', 'Cash 310,000', 'wholesale'),
(81, 'SALE-20260925170411-A4C8', 8, 32, NULL, 858000.0, 0.0, 858000.0, 'paid', NULL, '2026-09-25T17:04:13', '2026-09-25T17:04:13', NULL, 'cash', 'Cash 858,000', 'wholesale'),
(82, 'SALE-20260925170551-B2C8', 8, 32, NULL, 36000.0, 0.0, 36000.0, 'paid', NULL, '2026-09-25T17:05:51', '2026-09-25T17:05:51', NULL, 'cash', 'Cash 36,000', 'wholesale'),
(83, 'SALE-20260925170604-5C36', 8, 32, NULL, 36000.0, 0.0, 36000.0, 'paid', NULL, '2026-09-25T17:06:04', '2026-09-25T17:06:04', NULL, 'cash', 'Cash 36,000', 'wholesale'),
(84, 'SALE-20260929095049-8699', 8, 32, NULL, 715000.0, 0.0, 715000.0, 'paid', NULL, '2026-09-29T09:50:50', '2026-09-29T09:50:50', NULL, 'cash', 'Cash 715,000', 'retail'),
(85, 'SALE-20261001104425-B2E8', 8, 32, NULL, 664000.0, 0.0, 664000.0, 'paid', NULL, '2026-10-01T10:44:27', '2026-10-01T10:44:27', NULL, 'cash', 'Cash 664,000', 'wholesale'),
(86, 'SALE-20261002185116-3BF9', 8, 32, NULL, 90000.0, 0.0, 90000.0, 'paid', NULL, '2026-10-02T18:51:17', '2026-10-02T18:51:17', NULL, 'cash', 'Cash 90,000', 'retail');

-- sale_items (42 rows)
INSERT INTO public.sale_items (id, sale_id, product_id, quantity, unit_price, unit_cost, total, created_at, updated_at, volume, variant) VALUES
(43, 57, 140, 1, 40000.0, 0.0, 40000.0, '2026-09-08T23:37:30', '2026-09-08T23:37:30', NULL, NULL),
(44, 58, 140, 20, 40000.0, 0.0, 800000.0, '2026-09-08T23:39:49', '2026-09-08T23:39:49', NULL, NULL),
(45, 59, 140, 1, 40000.0, 0.0, 40000.0, '2026-09-08T23:41:56', '2026-09-08T23:41:56', NULL, NULL),
(46, 60, 140, 1, 40000.0, 0.0, 40000.0, '2026-09-09T00:10:53', '2026-09-09T00:10:53', NULL, NULL),
(47, 61, 140, 1, 5000.0, 0.0, 5000.0, '2026-09-10T06:33:25', '2026-09-10T06:33:25', NULL, NULL),
(48, 62, 99, 3, 50000.0, 0.0, 150000.0, '2026-09-13T07:53:45', '2026-09-13T07:53:45', NULL, NULL),
(49, 63, 99, 1, 45000.0, 0.0, 45000.0, '2026-09-13T07:55:19', '2026-09-13T07:55:19', NULL, NULL),
(50, 64, 99, 1, 45000.0, 0.0, 45000.0, '2026-09-13T08:01:47', '2026-09-13T08:01:47', NULL, NULL),
(51, 65, 99, 5, 45000.0, 0.0, 225000.0, '2026-09-13T08:02:16', '2026-09-13T08:02:16', NULL, NULL),
(52, 66, 99, 6, 45000.0, 0.0, 270000.0, '2026-09-13T08:03:09', '2026-09-13T08:03:09', NULL, NULL),
(54, 68, 322, 20, 1000.0, 0.0, 20000.0, '2026-09-13T10:14:10', '2026-09-13T10:14:10', NULL, NULL),
(55, 69, 322, 20, 3000.0, 0.0, 60000.0, '2026-09-13T10:17:17', '2026-09-13T10:17:17', NULL, NULL),
(56, 70, 322, 20, 1000.0, 0.0, 20000.0, '2026-09-13T11:49:03', '2026-09-13T11:49:03', NULL, NULL),
(57, 71, 99, 1, 45000.0, 0.0, 45000.0, '2026-09-14T10:23:17', '2026-09-14T10:23:17', NULL, NULL),
(58, 72, 99, 1, 45000.0, 0.0, 45000.0, '2026-09-15T13:26:38', '2026-09-15T13:26:38', NULL, NULL),
(59, 72, 327, 5, 12000.0, 0.0, 60000.0, '2026-09-15T13:26:39', '2026-09-15T13:26:39', NULL, NULL),
(60, 72, 328, 5, 20000.0, 0.0, 100000.0, '2026-09-15T13:26:40', '2026-09-15T13:26:40', NULL, NULL),
(61, 72, 322, 2, 27000.0, 0.0, 54000.0, '2026-09-15T13:26:40', '2026-09-15T13:26:40', NULL, NULL),
(62, 73, 99, 1, 45000.0, 0.0, 45000.0, '2026-09-16T14:57:05', '2026-09-16T14:57:05', NULL, NULL),
(63, 74, 53, 1, 45000.0, 0.0, 45000.0, '2026-09-16T15:26:54', '2026-09-16T15:26:54', NULL, NULL),
(64, 75, 53, 1, 45000.0, 0.0, 45000.0, '2026-09-16T15:32:45', '2026-09-16T15:32:45', NULL, NULL),
(65, 76, 338, 6, 3000.0, 0.0, 18000.0, '2026-09-19T14:54:40', '2026-09-19T14:54:40', NULL, NULL),
(66, 77, 98, 1, 35000.0, 0.0, 35000.0, '2026-09-19T15:26:43', '2026-09-19T15:26:43', NULL, NULL),
(67, 78, 345, 2, 50000.0, 0.0, 100000.0, '2026-09-21T08:49:45', '2026-09-21T08:49:45', NULL, NULL),
(68, 79, 322, 1, 3000.0, 0.0, 3000.0, '2026-09-23T09:09:33', '2026-09-23T09:09:33', NULL, NULL),
(69, 80, 322, 7, 30000.0, 0.0, 210000.0, '2026-09-25T16:24:30', '2026-09-25T16:24:30', NULL, NULL),
(70, 80, 328, 5, 20000.0, 0.0, 100000.0, '2026-09-25T16:24:30', '2026-09-25T16:24:30', NULL, NULL),
(71, 81, 347, 6, 3000.0, 0.0, 18000.0, '2026-09-25T17:04:12', '2026-09-25T17:04:12', NULL, NULL),
(72, 81, 338, 6, 40000.0, 0.0, 240000.0, '2026-09-25T17:04:12', '2026-09-25T17:04:12', NULL, NULL),
(73, 81, 328, 15, 40000.0, 0.0, 600000.0, '2026-09-25T17:04:13', '2026-09-25T17:04:13', NULL, NULL),
(74, 82, 347, 12, 3000.0, 0.0, 36000.0, '2026-09-25T17:05:51', '2026-09-25T17:05:51', NULL, NULL),
(75, 83, 347, 12, 3000.0, 0.0, 36000.0, '2026-09-25T17:06:04', '2026-09-25T17:06:04', NULL, NULL),
(76, 84, 45, 11, 65000.0, 0.0, 715000.0, '2026-09-29T09:50:50', '2026-09-29T09:50:50', NULL, NULL),
(77, 85, 328, 12, 3500.0, 0.0, 42000.0, '2026-10-01T10:44:26', '2026-10-01T10:44:26', NULL, NULL),
(78, 85, 347, 20, 1500.0, 0.0, 30000.0, '2026-10-01T10:44:26', '2026-10-01T10:44:26', NULL, NULL),
(79, 85, 338, 10, 2500.0, 0.0, 25000.0, '2026-10-01T10:44:26', '2026-10-01T10:44:26', NULL, NULL),
(80, 85, 327, 5, 3000.0, 0.0, 15000.0, '2026-10-01T10:44:26', '2026-10-01T10:44:26', NULL, NULL),
(81, 85, 328, 4, 3000.0, 0.0, 12000.0, '2026-10-01T10:44:26', '2026-10-01T10:44:26', NULL, NULL),
(82, 85, 322, 12, 12000.0, 0.0, 144000.0, '2026-10-01T10:44:27', '2026-10-01T10:44:27', NULL, NULL),
(83, 85, 328, 12, 15000.0, 0.0, 180000.0, '2026-10-01T10:44:27', '2026-10-01T10:44:27', NULL, NULL),
(84, 85, 322, 12, 18000.0, 0.0, 216000.0, '2026-10-01T10:44:27', '2026-10-01T10:44:27', NULL, NULL),
(85, 86, 45, 2, 45000.0, 0.0, 90000.0, '2026-10-02T18:51:17', '2026-10-02T18:51:17', NULL, NULL);

-- orders (20 rows)
INSERT INTO public.orders (id, order_number, branch_id, cashier_id, customer_id, status, total, delivery_notes, assigned_at, completed_at, cancelled_at, created_at, updated_at, payment_status, payment_method, pesapal_tracking_id, pesapal_merchant_reference, payment_confirmation_code, paid_at, assigned_to, served_at, personal_order_name) VALUES
(22, 'ORD-20260915140656-7802', 8, NULL, 13, 'served', 45000.0, 'napatikana mabibo naomba delivery na malipo', NULL, NULL, '2026-09-21T10:19:57', '2026-09-15T14:06:56', '2026-09-21T10:19:57', 'failed', 'pesapal', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'ORD-000008', 8, 19, 12, 'served', 372000.0, 'hghdffjkhmbnvfyu', '2026-08-27T22:56:33', '2026-08-28T22:15:03', NULL, NULL, '2026-08-28T22:15:03', 'unpaid', NULL, NULL, NULL, NULL, NULL, 19, NULL, NULL),
(10, 'ORD-000010', 8, 19, 12, 'served', 175000.0, 'kfhdfsfghgkhlo;liuyfthdgsfghcv', '2026-08-30T23:32:58', '2026-08-30T23:33:11', NULL, NULL, '2026-08-30T23:33:11', 'unpaid', NULL, NULL, NULL, NULL, NULL, 19, NULL, NULL),
(9, 'ORD-000009', 8, 19, 12, 'served', 372000.0, 'hghdffjkhmbnvfyu', '2026-08-28T21:43:53', '2026-08-30T23:33:26', NULL, NULL, '2026-08-30T23:33:26', 'unpaid', NULL, NULL, NULL, NULL, NULL, 19, NULL, NULL),
(12, 'ORD-20260907203417-57E5', 8, NULL, 11, 'served', 54000.0, 'hfxdfghklkhfgdxc', NULL, NULL, NULL, NULL, '2026-09-11T20:35:43', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'ORD-20260908102445-0B57', 8, NULL, 14, 'picked', 50000.0, 'Automated payment test', NULL, NULL, NULL, NULL, '2026-09-11T21:52:57', 'failed', 'pesapal', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'ORD-20260908103217-B505', 8, NULL, 11, 'picked', 50000.0, NULL, NULL, NULL, NULL, NULL, '2026-09-11T21:53:15', 'failed', 'pesapal', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'ORD-20260908103644-56B5', 8, NULL, 12, 'picked', 50000.0, 'drsfhfkuil', NULL, NULL, NULL, NULL, '2026-09-11T21:53:41', 'failed', 'pesapal', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 'ORD-20260908103904-26C7', 8, NULL, 14, 'picked', 1000.0, 'Small amount test', NULL, NULL, NULL, NULL, '2026-09-11T22:00:23', 'failed', 'pesapal', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, 'ORD-20260908110134-15B3', 8, NULL, 12, 'served', 1000.0, 'fdsdvcvsd', NULL, NULL, NULL, NULL, '2026-09-11T22:03:34', 'pending', 'pesapal', '82cc0e9e-77c3-44ef-ab9b-d9eded489073', '17', NULL, NULL, NULL, NULL, NULL),
(18, 'ORD-20260908111039-7CD1', 8, NULL, 12, 'picked', 24000.0, 'jkhlikjl', NULL, NULL, NULL, NULL, '2026-09-11T22:04:18', 'pending', 'pesapal', '17fbd2a3-55eb-4799-997a-d9ed3dcebcf9', '18', NULL, NULL, NULL, NULL, NULL),
(20, 'ORD-20260908232503-9948', 8, NULL, 11, 'served', 44999.91, 'do faster', '2026-09-12T19:01:53', '2026-09-12T19:02:12', NULL, NULL, '2026-09-12T19:02:12', 'failed', 'pesapal', NULL, NULL, NULL, NULL, 30, NULL, NULL),
(19, 'ORD-20260908111303-D76D', 8, NULL, 11, 'picked', 24000.0, 'klhuifv', '2026-09-12T19:02:30', NULL, NULL, NULL, '2026-09-12T19:02:30', 'failed', 'pesapal', NULL, NULL, NULL, NULL, 30, NULL, NULL),
(21, 'ORD-20260913062623-D420', 8, NULL, 11, 'served', 45000.0, 'quickly', '2026-09-13T10:36:41', '2026-09-13T10:36:51', NULL, '2026-09-13T06:26:23', '2026-09-13T10:36:51', 'failed', 'pesapal', NULL, NULL, NULL, NULL, 29, NULL, NULL),
(23, 'ORD-20260916162607-170D', 10, NULL, 13, 'served', 45000.0, NULL, '2026-09-16T16:36:14', '2026-09-16T16:36:33', NULL, '2026-09-16T16:26:07', '2026-09-16T16:36:33', 'failed', 'pesapal', NULL, NULL, NULL, NULL, 39, NULL, NULL),
(25, 'ORD-20260918073654-2143', 8, NULL, 11, 'served', 315000.0, 'fanya chaap nakuja kuchukua jioni', '2026-09-18T07:38:31', '2026-09-18T07:39:42', NULL, '2026-09-18T07:36:54', '2026-09-18T09:20:17', 'unpaid', 'cash', NULL, NULL, NULL, NULL, 29, '2026-09-18T09:20:17', NULL),
(28, 'ORD-20260926120327-7B01', 8, 19, 11, 'picked', 70000.0, '.nkhjhfgdzfgxfhcvk', '2026-09-26T13:19:13', NULL, NULL, '2026-09-26T12:03:27', '2026-09-26T13:19:51', 'unpaid', 'cash', NULL, NULL, NULL, NULL, 19, NULL, 'order ya mwanza'),
(26, 'ORD-20260918122049-6753', 8, NULL, 13, 'served', 45000.0, 'iwe haraka kidogo', '2026-09-21T10:09:34', '2026-09-21T10:10:59', NULL, '2026-09-18T12:20:49', '2026-09-26T14:30:36', 'unpaid', 'cash', NULL, NULL, NULL, NULL, 32, '2026-09-21T10:11:17', 'customer in morocco'),
(24, 'ORD-20260916163432-D5FA', 8, NULL, 13, 'served', 90000.0, 'nitumiwe mabibo', '2026-09-21T10:16:08', '2026-09-29T09:45:39', NULL, '2026-09-16T16:34:32', '2026-09-29T09:45:39', 'failed', 'pesapal', NULL, NULL, NULL, NULL, 32, '2026-09-29T09:45:39', NULL),
(27, 'ORD-20260925090404-2E73', 8, NULL, 13, 'served', 70000.0, 'Naiomba mapema basi', '2026-09-25T09:05:42', '2026-09-26T14:33:34', NULL, '2026-09-25T09:04:04', '2026-09-29T09:46:22', 'unpaid', 'cash', NULL, NULL, NULL, NULL, 32, '2026-09-26T14:33:34', 'mabibo mzigo jioni');

-- order_items (17 rows)
INSERT INTO public.order_items (id, order_id, product_id, quantity, unit_price, total, created_at, updated_at, volume, variant) VALUES
(19, 12, 247, 1, 54000.0, 54000.0, NULL, NULL, NULL, NULL),
(20, 13, 76, 1, 50000.0, 50000.0, NULL, NULL, NULL, NULL),
(21, 14, 76, 1, 50000.0, 50000.0, NULL, NULL, NULL, NULL),
(22, 15, 76, 1, 50000.0, 50000.0, NULL, NULL, NULL, NULL),
(23, 16, 76, 1, 1000.0, 1000.0, NULL, NULL, NULL, NULL),
(24, 17, 76, 1, 1000.0, 1000.0, NULL, NULL, NULL, NULL),
(25, 18, 76, 1, 24000.0, 24000.0, NULL, NULL, NULL, NULL),
(26, 19, 76, 1, 24000.0, 24000.0, NULL, NULL, NULL, NULL),
(27, 20, 140, 1, 44999.91, 44999.91, NULL, NULL, NULL, NULL),
(28, 21, 99, 1, 45000.0, 45000.0, '2026-09-13T06:26:25', '2026-09-13T06:26:25', NULL, NULL),
(29, 22, 99, 1, 45000.0, 45000.0, '2026-09-15T14:06:56', '2026-09-15T14:06:56', NULL, NULL),
(30, 23, 53, 1, 45000.0, 45000.0, '2026-09-16T16:26:08', '2026-09-16T16:26:08', NULL, NULL),
(31, 24, 99, 2, 45000.0, 90000.0, '2026-09-16T16:34:32', '2026-09-16T16:34:32', NULL, NULL),
(32, 25, 99, 7, 45000.0, 315000.0, '2026-09-18T07:36:54', '2026-09-18T07:36:54', NULL, NULL),
(33, 26, 99, 1, 45000.0, 45000.0, '2026-09-18T12:20:50', '2026-09-18T12:20:50', NULL, NULL),
(34, 27, 299, 1, 70000.0, 70000.0, '2026-09-25T09:04:04', '2026-09-25T09:04:04', NULL, NULL),
(35, 28, 299, 1, 70000.0, 70000.0, '2026-09-26T12:03:27', '2026-09-26T12:03:27', NULL, NULL);

-- order_notes (14 rows)
INSERT INTO public.order_notes (id, order_id, note, created_by, created_at, updated_at) VALUES
(1, 25, 'sawa nmeipokea order yako naifanyia kazi', 29, '2026-09-18T07:38:31+00:00', '2026-09-18T07:38:31+00:00'),
(2, 25, 'nmeifunga order yako naweka label sasa', 29, '2026-09-18T07:39:05+00:00', '2026-09-18T07:39:05+00:00'),
(3, 25, 'order yako imekamilika iko tayari kukabizishwa baada ya malipo', 29, '2026-09-18T07:39:42+00:00', '2026-09-18T07:39:42+00:00'),
(4, 25, 'welcome next time', 29, '2026-09-18T09:20:17+00:00', '2026-09-18T09:20:17+00:00'),
(5, 26, 'nishapark mzigo boss', 32, '2026-09-21T10:09:34+00:00', '2026-09-21T10:09:34+00:00'),
(6, 26, 'tushatuma tayari boss', 32, '2026-09-21T10:10:08+00:00', '2026-09-21T10:10:08+00:00'),
(7, 26, 'karibu sana boss', 32, '2026-09-21T10:10:59+00:00', '2026-09-21T10:10:59+00:00'),
(8, 26, 'bye', 32, '2026-09-21T10:11:17+00:00', '2026-09-21T10:11:17+00:00'),
(9, 24, 'picked', 32, '2026-09-21T10:16:08+00:00', '2026-09-21T10:16:08+00:00'),
(10, 22, 'bidhaa imetuishia boss', 32, '2026-09-21T10:19:58+00:00', '2026-09-21T10:19:58+00:00'),
(11, 27, 'okay boss,karibu nakuhudumia.', 32, '2026-09-25T09:05:42+00:00', '2026-09-25T09:05:42+00:00'),
(12, 28, 'order yako imechukuliwa naifanyia kazi', 19, '2026-09-26T13:19:13+00:00', '2026-09-26T13:19:13+00:00'),
(13, 27, 'order yako imeshatumwa boss', 32, '2026-09-26T14:33:35+00:00', '2026-09-26T14:33:35+00:00'),
(14, 24, 'karibu sana boss', 32, '2026-09-29T09:45:40+00:00', '2026-09-29T09:45:40+00:00');

-- expenses (8 rows)
INSERT INTO public.expenses (id, branch_id, user_id, category, amount, description, date, created_at, updated_at) VALUES
(20, 8, 19, 'electricity', 4000.0, 'llkhfdsfhgkio;p''[piutrdfyuio;kljhgfxc', '2026-08-27', '2026-08-27T22:37:55', '2026-08-27T22:37:55'),
(21, 8, 19, 'electricity', 4000.0, 'daily office power', '2026-09-07', '2026-09-07T03:38:24', '2026-09-07T03:38:24'),
(22, 8, 23, 'electricity', 4000.0, 'umeme kwa office', '2026-09-14', '2026-09-14T10:35:02', '2026-09-14T10:35:02'),
(23, 8, 23, 'other', 4000.0, 'food and others', '2026-09-15', '2026-09-15T13:52:18', '2026-09-15T13:52:18'),
(24, 8, 23, 'water', 10000.0, 'water used in the office', '2026-09-16', '2026-09-16T14:56:23', '2026-09-16T14:56:23'),
(25, 10, 42, 'other', 2000.0, 'Eliza kapewa 2000', '2026-09-16', '2026-09-16T15:28:59', '2026-09-16T15:28:59'),
(26, 8, 23, 'other', 2000.0, 'rehema kapewa ela', '2026-09-19', '2026-09-19T15:33:03', '2026-09-19T15:33:03'),
(27, 10, 42, 'other', 4500.0, 'water bill payment', '2026-09-29', '2026-09-29T13:00:42', '2026-09-29T13:00:42');

-- otp_records (47 rows)
INSERT INTO public.otp_records (id, user_id, email, otp, type, expires_at, used, created_at, updated_at) VALUES
(1, 1, 'admin@worldchoiceperfumes.co.tz', '123456', 'registration', '2026-08-22T05:33:02.148567', TRUE, '2026-07-23T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(2, 6, 'fatima@worldchoiceperfumes.co.tz', '654321', 'registration', '2026-08-22T05:33:02.148567', TRUE, '2026-07-28T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(3, 2, 'godwinfranklin419@gmail.com', '684946', 'registration', '2026-08-24T22:26:44', TRUE, '2026-08-24T22:16:44', '2026-08-24T22:23:55'),
(4, 6, 'godwinfranklin418@gmail.com', '966645', 'registration', '2026-08-27T23:10:30', TRUE, '2026-08-27T23:00:30', '2026-08-27T23:01:40'),
(5, 7, 'gideonmsuya146@gmail.com', '347927', 'registration', '2026-08-30T15:18:27', FALSE, '2026-08-30T15:08:27', '2026-08-30T15:08:27'),
(6, 8, 'gideonmsuya145@gmail.com', '554335', 'registration', '2026-08-30T15:33:37', FALSE, '2026-08-30T15:23:37', '2026-08-30T15:23:37'),
(7, 9, 'gideonmsuya144@gmail.com', '943052', 'registration', '2026-08-30T15:39:18', FALSE, '2026-08-30T15:29:18', '2026-08-30T15:29:18'),
(8, 1, 'gideonmsuya142@gmail.com', '857329', 'registration', '2026-08-31T07:46:12', FALSE, '2026-08-31T07:36:12', '2026-08-31T07:36:12'),
(9, 2, 'gideonmsuya141@gmail.com', '839562', 'registration', '2026-08-31T07:48:03', FALSE, '2026-08-31T07:38:03', '2026-08-31T07:38:03'),
(10, 1, 'worldchoiceperfume@gmail.com', '845351', 'registration', '2026-09-06T04:52:51', FALSE, '2026-09-06T04:42:51', '2026-09-06T04:42:51'),
(11, 2, 'admin@worldchoiceperfumes.com', '148053', 'registration', '2026-09-06T04:54:36', FALSE, '2026-09-06T04:44:36', '2026-09-06T04:44:36'),
(12, 29, 'admin@worldchoiceperfume.com', '905801', 'registration', '2026-09-06T06:13:59', FALSE, '2026-09-06T06:03:59', '2026-09-06T06:03:59'),
(13, 29, 'admin@worldchoiceperfume.com', '905801', 'registration', '2026-09-06T06:13:59', FALSE, '2026-09-06T06:03:59', '2026-09-06T06:03:59'),
(14, 30, 'godwinfranklin415@gmail.com', '101861', 'registration', '2026-09-07T03:35:29', FALSE, '2026-09-07T03:25:29', '2026-09-07T03:25:29'),
(15, 30, 'godwinfranklin415@gmail.com', '101861', 'registration', '2026-09-07T03:35:29', FALSE, '2026-09-07T03:25:29', '2026-09-07T03:25:29'),
(16, 31, 'gideonmsuya140@gmail.com', '135881', 'registration', '2026-09-07T03:58:01', FALSE, '2026-09-07T03:48:01', '2026-09-07T03:48:01'),
(17, 31, 'gideonmsuya140@gmail.com', '135881', 'registration', '2026-09-07T03:58:01', FALSE, '2026-09-07T03:48:01', '2026-09-07T03:48:01'),
(18, 32, 'gideonmsuya141@gmail.com', '785816', 'registration', '2026-09-07T04:01:34', FALSE, '2026-09-07T03:51:34', '2026-09-07T03:51:34'),
(19, 32, 'gideonmsuya141@gmail.com', '785816', 'registration', '2026-09-07T04:01:34', FALSE, '2026-09-07T03:51:35', '2026-09-07T03:51:35'),
(20, 33, 'gideonmsuya142@gmail.com', '682216', 'registration', '2026-09-07T04:06:45', FALSE, '2026-09-07T03:56:45', '2026-09-07T03:56:45'),
(21, 33, 'gideonmsuya142@gmail.com', '682216', 'registration', '2026-09-07T04:06:45', FALSE, '2026-09-07T03:56:45', '2026-09-07T03:56:45'),
(22, 34, 'seller@worldchoiceperfume.com', '837636', 'registration', '2026-09-11T20:05:45', FALSE, '2026-09-11T19:55:45', '2026-09-11T19:55:45'),
(23, 34, 'seller@worldchoiceperfume.com', '837636', 'registration', '2026-09-11T20:05:45', FALSE, '2026-09-11T19:55:45', '2026-09-11T19:55:45'),
(24, 35, 'graphics@worldchoiceperfume.com', '263871', 'registration', '2026-09-12T19:37:35', FALSE, '2026-09-12T19:27:35', '2026-09-12T19:27:35'),
(25, 35, 'graphics@worldchoiceperfume.com', '263871', 'registration', '2026-09-12T19:37:35', FALSE, '2026-09-12T19:27:36', '2026-09-12T19:27:36'),
(26, 36, 'gideonmsuya147@gmail.com', '607873', 'registration', '2026-09-13T12:51:00', FALSE, '2026-09-13T12:41:00', '2026-09-13T12:41:00'),
(27, 36, 'gideonmsuya147@gmail.com', '607873', 'registration', '2026-09-13T12:51:00', FALSE, '2026-09-13T12:41:01', '2026-09-13T12:41:01'),
(28, 37, 'gideonmsuya148@gmail.com', '282203', 'registration', '2026-09-13T13:06:36', FALSE, '2026-09-13T12:56:36', '2026-09-13T12:56:36'),
(29, 37, 'gideonmsuya148@gmail.com', '282203', 'registration', '2026-09-13T13:06:36', FALSE, '2026-09-13T12:56:36', '2026-09-13T12:56:36'),
(30, 38, 'gideonmsuya149@gmail.com', '824340', 'registration', '2026-09-13T13:13:19', FALSE, '2026-09-13T13:03:19', '2026-09-13T13:03:19'),
(31, 38, 'gideonmsuya149@gmail.com', '824340', 'registration', '2026-09-13T13:13:19', FALSE, '2026-09-13T13:03:19', '2026-09-13T13:03:19'),
(32, 39, 'gideonmsuya150@gmail.com', '748187', 'registration', '2026-09-13T13:23:01', FALSE, '2026-09-13T13:13:01', '2026-09-13T13:13:01'),
(33, 39, 'gideonmsuya150@gmail.com', '748187', 'registration', '2026-09-13T13:23:01', FALSE, '2026-09-13T13:13:02', '2026-09-13T13:13:02'),
(34, 40, 'gideonmsuya151@gmail.com', '445264', 'registration', '2026-09-13T16:00:36', FALSE, '2026-09-13T15:50:36', '2026-09-13T15:50:36'),
(35, 40, 'gideonmsuya151@gmail.com', '445264', 'registration', '2026-09-13T16:00:36', FALSE, '2026-09-13T15:50:36', '2026-09-13T15:50:36'),
(36, 1, 'gideonmsuya152@gmail.com', '619084', 'registration', '2026-09-13T16:05:20', FALSE, '2026-09-13T15:55:20', '2026-09-13T15:55:20'),
(37, 1, 'gideonmsuya152@gmail.com', '619084', 'registration', '2026-09-13T16:05:20', FALSE, '2026-09-13T15:55:20', '2026-09-13T15:55:20'),
(38, 2, 'gideonmsuya153@gmail.com', '579004', 'registration', '2026-09-13T16:06:58', FALSE, '2026-09-13T15:56:58', '2026-09-13T15:56:58'),
(39, 2, 'gideonmsuya153@gmail.com', '579004', 'registration', '2026-09-13T16:06:58', FALSE, '2026-09-13T15:56:58', '2026-09-13T15:56:58'),
(40, 43, 'gideonmsuya154@gmail.com', '757716', 'registration', '2026-09-13T16:09:34', FALSE, '2026-09-13T15:59:34', '2026-09-13T15:59:34'),
(41, 43, 'gideonmsuya154@gmail.com', '757716', 'registration', '2026-09-13T16:09:34', FALSE, '2026-09-13T15:59:34', '2026-09-13T15:59:34'),
(42, 4, 'gideonmsuya155@gmail.com', '249272', 'registration', '2026-09-13T16:10:41', FALSE, '2026-09-13T16:00:41', '2026-09-13T16:00:41'),
(43, 4, 'gideonmsuya155@gmail.com', '249272', 'registration', '2026-09-13T16:10:41', FALSE, '2026-09-13T16:00:41', '2026-09-13T16:00:41'),
(44, 5, 'gideonmsuya156@gmail.com', '692020', 'registration', '2026-09-13T16:11:41', FALSE, '2026-09-13T16:01:41', '2026-09-13T16:01:41'),
(45, 5, 'gideonmsuya156@gmail.com', '692020', 'registration', '2026-09-13T16:11:41', FALSE, '2026-09-13T16:01:41', '2026-09-13T16:01:41'),
(46, 46, 'gideonmsuya157@gmail.com', '493268', 'registration', '2026-09-13T16:13:33', FALSE, '2026-09-13T16:03:33', '2026-09-13T16:03:33'),
(47, 46, 'gideonmsuya157@gmail.com', '493268', 'registration', '2026-09-13T16:13:33', FALSE, '2026-09-13T16:03:33', '2026-09-13T16:03:33');

-- audit_logs (879 rows)
INSERT INTO public.audit_logs (id, user_id, branch_id, action, auditable_type, auditable_id, old_values, new_values, ip_address, created_at, updated_at) VALUES
(1, 1, NULL, 'user.login', 'App\\Models\\User', 1, NULL, '{"status": "active"}', '192.168.1.100', '2026-08-17T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(5, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-28T21:36:19', '2026-08-28T21:36:19'),
(6, 19, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-28T21:40:31', '2026-08-28T21:40:31'),
(7, 19, NULL, 'order_picked', NULL, NULL, NULL, NULL, NULL, '2026-08-28T21:43:54', '2026-08-28T21:43:54'),
(8, 19, NULL, 'order_ready', NULL, NULL, NULL, NULL, NULL, '2026-08-28T22:14:46', '2026-08-28T22:14:46'),
(9, 19, NULL, 'order_completed', NULL, NULL, NULL, NULL, NULL, '2026-08-28T22:15:52', '2026-08-28T22:15:52'),
(10, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T12:28:58', '2026-08-30T12:28:58'),
(11, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T12:29:50', '2026-08-30T12:29:50'),
(12, 19, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T13:04:47', '2026-08-30T13:04:47'),
(13, 19, NULL, 'sale_created', NULL, NULL, NULL, NULL, NULL, '2026-08-30T13:24:02', '2026-08-30T13:24:02'),
(14, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T13:33:53', '2026-08-30T13:33:53'),
(15, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T15:12:38', '2026-08-30T15:12:38'),
(16, 22, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T15:25:25', '2026-08-30T15:25:25'),
(17, 23, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T15:30:43', '2026-08-30T15:30:43'),
(18, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T15:53:18', '2026-08-30T15:53:18'),
(19, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T16:17:44', '2026-08-30T16:17:44'),
(20, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T16:23:04', '2026-08-30T16:23:04'),
(21, 18, NULL, 'sale_created_by_admin', NULL, NULL, NULL, NULL, NULL, '2026-08-30T17:04:33', '2026-08-30T17:04:33'),
(22, 18, NULL, 'sale_created_by_admin', NULL, NULL, NULL, NULL, NULL, '2026-08-30T17:12:55', '2026-08-30T17:12:55'),
(23, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T17:34:53', '2026-08-30T17:34:53'),
(24, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T22:30:33', '2026-08-30T22:30:33'),
(25, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T22:33:02', '2026-08-30T22:33:02'),
(26, 1, NULL, 'blocked_user_23', NULL, NULL, NULL, NULL, NULL, '2026-08-30T22:37:54', '2026-08-30T22:37:54'),
(27, 1, NULL, 'blocked_user_23', NULL, NULL, NULL, NULL, NULL, '2026-08-30T22:38:25', '2026-08-30T22:38:25'),
(28, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T22:43:44', '2026-08-30T22:43:44'),
(29, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T22:49:53', '2026-08-30T22:49:53'),
(30, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T22:57:03', '2026-08-30T22:57:03'),
(31, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T22:58:53', '2026-08-30T22:58:53'),
(32, 19, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:05:26', '2026-08-30T23:05:26'),
(33, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:07:02', '2026-08-30T23:07:02'),
(34, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:10:46', '2026-08-30T23:10:46'),
(35, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:12:54', '2026-08-30T23:12:54'),
(36, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:14:09', '2026-08-30T23:14:09'),
(37, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:14:43', '2026-08-30T23:14:43'),
(38, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:15:34', '2026-08-30T23:15:34'),
(39, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:30:55', '2026-08-30T23:30:55'),
(40, 19, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:32:04', '2026-08-30T23:32:04'),
(41, 19, NULL, 'order_picked', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:32:58', '2026-08-30T23:32:58'),
(42, 19, NULL, 'order_ready', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:33:07', '2026-08-30T23:33:07'),
(43, 19, NULL, 'order_completed', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:33:12', '2026-08-30T23:33:12'),
(44, 19, NULL, 'order_ready', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:33:19', '2026-08-30T23:33:19'),
(45, 19, NULL, 'order_completed', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:33:28', '2026-08-30T23:33:28'),
(46, 19, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:38:01', '2026-08-30T23:38:01'),
(47, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:39:07', '2026-08-30T23:39:07'),
(48, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:47:15', '2026-08-30T23:47:15'),
(49, 1, NULL, 'blocked_user_23', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:47:32', '2026-08-30T23:47:32'),
(50, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-30T23:49:24', '2026-08-30T23:49:24'),
(51, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-31T00:11:17', '2026-08-31T00:11:17'),
(52, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-31T03:53:26', '2026-08-31T03:53:26'),
(53, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-31T04:17:50', '2026-08-31T04:17:50'),
(54, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-31T04:23:37', '2026-08-31T04:23:37'),
(55, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-31T04:27:51', '2026-08-31T04:27:51'),
(56, 22, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-31T04:29:07', '2026-08-31T04:29:07'),
(57, 22, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-31T04:30:19', '2026-08-31T04:30:19'),
(58, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-31T07:43:10', '2026-08-31T07:43:10'),
(59, 23, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-31T09:09:27', '2026-08-31T09:09:27'),
(60, 22, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-31T09:14:20', '2026-08-31T09:14:20'),
(61, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-31T09:30:48', '2026-08-31T09:30:48'),
(62, 21, NULL, 'blocked_user_19', NULL, NULL, NULL, NULL, NULL, '2026-08-31T09:33:22', '2026-08-31T09:33:22'),
(63, 21, NULL, 'blocked_user_22', NULL, NULL, NULL, NULL, NULL, '2026-08-31T09:34:30', '2026-08-31T09:34:30'),
(64, 23, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-31T09:55:33', '2026-08-31T09:55:33'),
(65, 23, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-31T10:03:58', '2026-08-31T10:03:58'),
(66, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-31T10:05:05', '2026-08-31T10:05:05'),
(67, 22, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-08-31T10:06:39', '2026-08-31T10:06:39'),
(68, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-01T12:21:16', '2026-09-01T12:21:16'),
(69, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-02T10:50:06', '2026-09-02T10:50:06'),
(70, 21, NULL, 'blocked_user_20', NULL, NULL, NULL, NULL, NULL, '2026-09-02T10:50:41', '2026-09-02T10:50:41'),
(71, 23, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-02T10:52:04', '2026-09-02T10:52:04'),
(72, 22, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-02T11:00:39', '2026-09-02T11:00:39'),
(73, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T06:06:55', '2026-09-06T06:06:55'),
(74, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T06:19:17', '2026-09-06T06:19:17'),
(75, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T07:49:31', '2026-09-06T07:49:31'),
(76, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T08:08:03', '2026-09-06T08:08:03'),
(77, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T14:12:00', '2026-09-06T14:12:00'),
(78, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T15:13:26', '2026-09-06T15:13:26'),
(79, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T15:40:28', '2026-09-06T15:40:28'),
(80, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T15:58:44', '2026-09-06T15:58:44'),
(81, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T16:00:02', '2026-09-06T16:00:02'),
(82, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T16:00:32', '2026-09-06T16:00:32'),
(83, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T16:10:27', '2026-09-06T16:10:27'),
(84, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T18:33:20', '2026-09-06T18:33:20'),
(85, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T20:45:02', '2026-09-06T20:45:02'),
(86, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T20:46:13', '2026-09-06T20:46:13'),
(87, 1, NULL, 'blocked_user_10', NULL, NULL, NULL, NULL, NULL, '2026-09-06T20:49:53', '2026-09-06T20:49:53'),
(88, 1, NULL, 'blocked_user_10', NULL, NULL, NULL, NULL, NULL, '2026-09-06T20:51:45', '2026-09-06T20:51:45'),
(89, 1, NULL, 'blocked_user_10', NULL, NULL, NULL, NULL, NULL, '2026-09-06T20:53:31', '2026-09-06T20:53:31'),
(90, 19, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T21:10:54', '2026-09-06T21:10:54'),
(91, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-06T22:35:40', '2026-09-06T22:35:40'),
(92, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T02:38:22', '2026-09-07T02:38:22'),
(93, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T02:41:24', '2026-09-07T02:41:24'),
(94, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T02:50:02', '2026-09-07T02:50:02'),
(95, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T03:00:40', '2026-09-07T03:00:40'),
(96, 19, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T03:01:31', '2026-09-07T03:01:31'),
(97, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T03:05:56', '2026-09-07T03:05:56'),
(98, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T03:22:06', '2026-09-07T03:22:06'),
(99, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T03:26:39', '2026-09-07T03:26:39'),
(100, 19, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T03:37:46', '2026-09-07T03:37:46'),
(101, 19, NULL, 'expense_created', NULL, NULL, NULL, NULL, NULL, '2026-09-07T03:38:24', '2026-09-07T03:38:24'),
(102, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T03:40:53', '2026-09-07T03:40:53'),
(103, 1, NULL, 'blocked_user_12', NULL, NULL, NULL, NULL, NULL, '2026-09-07T03:45:31', '2026-09-07T03:45:31'),
(104, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T03:53:20', '2026-09-07T03:53:20'),
(105, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T03:59:21', '2026-09-07T03:59:21'),
(106, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T04:37:19', '2026-09-07T04:37:19'),
(107, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T04:42:44', '2026-09-07T04:42:44'),
(108, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T07:25:16', '2026-09-07T07:25:16'),
(109, 31, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T07:50:02', '2026-09-07T07:50:02'),
(110, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T17:54:31', '2026-09-07T17:54:31'),
(111, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T18:04:00', '2026-09-07T18:04:00'),
(112, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T20:19:41', '2026-09-07T20:19:41'),
(113, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T20:25:49', '2026-09-07T20:25:49'),
(114, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T20:39:14', '2026-09-07T20:39:14'),
(115, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T20:40:24', '2026-09-07T20:40:24'),
(116, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T21:55:33', '2026-09-07T21:55:33'),
(117, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T22:17:11', '2026-09-07T22:17:11'),
(118, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T22:51:35', '2026-09-07T22:51:35'),
(119, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T23:02:04', '2026-09-07T23:02:04'),
(120, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T23:05:03', '2026-09-07T23:05:03'),
(121, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T23:14:58', '2026-09-07T23:14:58'),
(122, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T23:21:45', '2026-09-07T23:21:45'),
(123, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T23:26:08', '2026-09-07T23:26:08'),
(124, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T23:31:51', '2026-09-07T23:31:51'),
(125, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T23:38:40', '2026-09-07T23:38:40'),
(126, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T23:44:39', '2026-09-07T23:44:39'),
(127, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T23:49:45', '2026-09-07T23:49:45'),
(128, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-07T23:56:27', '2026-09-07T23:56:27'),
(129, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-08T00:02:49', '2026-09-08T00:02:49'),
(130, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-08T00:11:46', '2026-09-08T00:11:46'),
(131, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-08T07:20:24', '2026-09-08T07:20:24'),
(132, 31, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-08T07:29:29', '2026-09-08T07:29:29'),
(133, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-08T09:08:53', '2026-09-08T09:08:53'),
(134, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-08T15:57:50', '2026-09-08T15:57:50'),
(135, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-08T23:22:27', '2026-09-08T23:22:27'),
(136, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-08T23:33:15', '2026-09-08T23:33:15'),
(137, 29, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-08T23:37:32', '2026-09-08T23:37:32'),
(138, 29, NULL, 'discount_applied_sale', 'sale', 57, '{"subtotal":40000,"paid":40000}', '{"discount_applied":true}', '10.29.121.232', '2026-09-08T23:37:32', '2026-09-08T23:37:32'),
(139, 29, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-08T23:39:51', '2026-09-08T23:39:51'),
(140, 29, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-08T23:41:57', '2026-09-08T23:41:57'),
(141, 29, NULL, 'discount_applied_sale', 'sale', 59, '{"subtotal":40000,"paid":40000}', '{"discount_applied":true}', '10.26.93.129', '2026-09-08T23:41:57', '2026-09-08T23:41:57'),
(142, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-09T00:09:23', '2026-09-09T00:09:23'),
(143, 29, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-09T00:10:54', '2026-09-09T00:10:54'),
(144, 29, NULL, 'discount_applied_sale', 'sale', 60, '{"subtotal":40000,"paid":40000}', '{"discount_applied":true}', '10.29.192.116', '2026-09-09T00:10:55', '2026-09-09T00:10:55'),
(145, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-09T00:23:46', '2026-09-09T00:23:46'),
(146, 1, NULL, 'blocked_user_31', NULL, NULL, NULL, NULL, NULL, '2026-09-09T00:29:55', '2026-09-09T00:29:55'),
(147, 1, NULL, 'blocked_user', 'users', 31, '{"status":"active"}', '{"status":"blocked"}', '10.29.192.116', '2026-09-09T00:29:55', '2026-09-09T00:29:55'),
(148, 1, NULL, 'blocked_user_30', NULL, NULL, NULL, NULL, NULL, '2026-09-09T00:32:42', '2026-09-09T00:32:42'),
(149, 1, NULL, 'blocked_user', 'users', 30, '{"status":"active"}', '{"status":"blocked"}', '10.29.121.232', '2026-09-09T00:32:42', '2026-09-09T00:32:42'),
(150, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-09T05:51:06', '2026-09-09T05:51:06'),
(151, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-10T04:07:33', '2026-09-10T04:07:33'),
(152, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-10T04:07:36', '2026-09-10T04:07:36'),
(153, 1, NULL, 'changed_user_status', 'users', 20, '{"status":"active"}', '{"status":"pending"}', '10.29.192.116', '2026-09-10T04:13:12', '2026-09-10T04:13:12');
INSERT INTO public.audit_logs (id, user_id, branch_id, action, auditable_type, auditable_id, old_values, new_values, ip_address, created_at, updated_at) VALUES
(154, 1, NULL, 'changed_user_status', 'users', 20, '{"status":"pending"}', '{"status":"active"}', '10.26.93.129', '2026-09-10T04:13:31', '2026-09-10T04:13:31'),
(155, 31, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-10T06:30:23', '2026-09-10T06:30:23'),
(156, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-10T06:32:01', '2026-09-10T06:32:01'),
(157, 32, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-10T06:33:26', '2026-09-10T06:33:26'),
(158, 32, NULL, 'discount_applied_sale', 'sale', 61, '{"subtotal":5000,"original_total":44999.91,"discounted_total":5000}', '{"discount_applied":true}', '10.29.192.116', '2026-09-10T06:33:27', '2026-09-10T06:33:27'),
(159, 32, NULL, 'stock_record_deleted', 'branch_stock', 62, '{"quantity":376,"selling_price":44999.91}', NULL, '10.29.121.232', '2026-09-10T06:37:45', '2026-09-10T06:37:45'),
(160, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-10T09:34:03', '2026-09-10T09:34:03'),
(161, 19, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-10T09:36:35', '2026-09-10T09:36:35'),
(162, 22, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-10T19:00:20', '2026-09-10T19:00:20'),
(163, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-10T19:01:31', '2026-09-10T19:01:31'),
(164, 31, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T14:57:21', '2026-09-11T14:57:21'),
(165, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T14:58:54', '2026-09-11T14:58:54'),
(166, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T15:09:27', '2026-09-11T15:09:27'),
(167, 22, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T15:21:49', '2026-09-11T15:21:49'),
(168, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T17:30:19', '2026-09-11T17:30:19'),
(169, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T18:13:17', '2026-09-11T18:13:17'),
(170, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T18:41:11', '2026-09-11T18:41:11'),
(171, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T18:53:53', '2026-09-11T18:53:53'),
(172, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T19:22:06', '2026-09-11T19:22:06'),
(173, 29, NULL, 'stock_record_deleted', 'branch_stock', 63, '{"quantity":60,"selling_price":67000}', NULL, '10.29.121.232', '2026-09-11T19:22:46', '2026-09-11T19:22:46'),
(174, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T19:29:53', '2026-09-11T19:29:53'),
(175, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T19:35:09', '2026-09-11T19:35:09'),
(176, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T19:43:32', '2026-09-11T19:43:32'),
(177, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T19:48:26', '2026-09-11T19:48:26'),
(178, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T19:50:37', '2026-09-11T19:50:37'),
(179, 34, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T19:57:05', '2026-09-11T19:57:05'),
(180, 34, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T20:12:52', '2026-09-11T20:12:52'),
(181, 19, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T20:15:30', '2026-09-11T20:15:30'),
(182, 34, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T20:26:58', '2026-09-11T20:26:58'),
(183, 34, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T20:34:33', '2026-09-11T20:34:33'),
(184, 34, NULL, 'order_status_changed', 'orders', 12, '{"status":"pending"}', '{"status":"assigned"}', '10.26.93.129', '2026-09-11T20:35:13', '2026-09-11T20:35:13'),
(185, 34, NULL, 'order_status_changed', 'orders', 12, '{"status":"assigned"}', '{"status":"ready"}', '10.26.93.129', '2026-09-11T20:35:28', '2026-09-11T20:35:28'),
(186, 34, NULL, 'order_status_changed', 'orders', 12, '{"status":"ready"}', '{"status":"completed"}', '10.29.192.116', '2026-09-11T20:35:43', '2026-09-11T20:35:43'),
(187, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T20:36:22', '2026-09-11T20:36:22'),
(188, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T20:54:35', '2026-09-11T20:54:35'),
(189, 31, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T21:32:52', '2026-09-11T21:32:52'),
(190, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T21:41:08', '2026-09-11T21:41:08'),
(191, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T21:51:09', '2026-09-11T21:51:09'),
(192, 32, NULL, 'order_status_changed', 'orders', 8, '{"status":"completed"}', '{"status":"served"}', '10.29.192.116', '2026-09-11T21:52:23', '2026-09-11T21:52:23'),
(193, 32, NULL, 'order_status_changed', 'orders', 13, '{"status":"pending"}', '{"status":"assigned"}', '10.29.192.116', '2026-09-11T21:52:57', '2026-09-11T21:52:57'),
(194, 32, NULL, 'order_status_changed', 'orders', 14, '{"status":"pending"}', '{"status":"assigned"}', '10.29.192.116', '2026-09-11T21:53:15', '2026-09-11T21:53:15'),
(195, 32, NULL, 'order_status_changed', 'orders', 15, '{"status":"pending"}', '{"status":"assigned"}', '10.26.93.129', '2026-09-11T21:53:41', '2026-09-11T21:53:41'),
(196, 31, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T21:55:14', '2026-09-11T21:55:14'),
(197, 31, NULL, 'order_status_changed', 'orders', 16, '{"status":"pending"}', '{"status":"assigned"}', '10.26.93.129', '2026-09-11T22:00:29', '2026-09-11T22:00:29'),
(198, 30, NULL, 'order_status_changed', 'orders', 17, '{"status":"pending"}', '{"status":"assigned"}', '10.26.93.129', '2026-09-11T22:01:24', '2026-09-11T22:01:24'),
(199, 31, NULL, 'order_status_changed', 'orders', 17, '{"status":"assigned"}', '{"status":"ready"}', '10.29.192.116', '2026-09-11T22:03:27', '2026-09-11T22:03:27'),
(200, 31, NULL, 'order_status_changed', 'orders', 17, '{"status":"ready"}', '{"status":"completed"}', '10.29.192.116', '2026-09-11T22:03:34', '2026-09-11T22:03:34'),
(201, 31, NULL, 'order_status_changed', 'orders', 17, '{"status":"completed"}', '{"status":"served"}', '10.29.192.116', '2026-09-11T22:03:46', '2026-09-11T22:03:46'),
(202, 31, NULL, 'order_status_changed', 'orders', 18, '{"status":"pending"}', '{"status":"assigned"}', '10.29.192.116', '2026-09-11T22:04:18', '2026-09-11T22:04:18'),
(203, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T22:07:16', '2026-09-11T22:07:16'),
(204, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T22:15:46', '2026-09-11T22:15:46'),
(205, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-11T22:27:28', '2026-09-11T22:27:28'),
(206, 31, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T02:58:57', '2026-09-12T02:58:57'),
(207, 33, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T03:14:59', '2026-09-12T03:14:59'),
(208, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T07:33:06', '2026-09-12T07:33:06'),
(209, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T07:47:13', '2026-09-12T07:47:13'),
(210, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T08:04:48', '2026-09-12T08:04:48'),
(211, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T14:21:48', '2026-09-12T14:21:48'),
(212, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T14:30:43', '2026-09-12T14:30:43'),
(213, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T14:48:26', '2026-09-12T14:48:26'),
(214, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T14:50:07', '2026-09-12T14:50:07'),
(215, 22, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T15:30:05', '2026-09-12T15:30:05'),
(216, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T15:33:57', '2026-09-12T15:33:57'),
(217, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T18:54:33', '2026-09-12T18:54:33'),
(218, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T19:01:10', '2026-09-12T19:01:10'),
(219, 30, NULL, 'order_status_changed', 'orders', 20, '{"status":"pending"}', '{"status":"assigned"}', '10.26.93.129', '2026-09-12T19:01:53', '2026-09-12T19:01:53'),
(220, 30, NULL, 'order_status_changed', 'orders', 20, '{"status":"assigned"}', '{"status":"ready"}', '10.29.121.232', '2026-09-12T19:02:03', '2026-09-12T19:02:03'),
(221, 30, NULL, 'order_status_changed', 'orders', 20, '{"status":"ready"}', '{"status":"completed"}', '10.29.121.232', '2026-09-12T19:02:12', '2026-09-12T19:02:12'),
(222, 30, NULL, 'order_status_changed', 'orders', 19, '{"status":"pending"}', '{"status":"assigned"}', '10.26.93.129', '2026-09-12T19:02:31', '2026-09-12T19:02:31'),
(223, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T19:07:04', '2026-09-12T19:07:04'),
(224, 35, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T19:28:41', '2026-09-12T19:28:41'),
(225, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T19:49:02', '2026-09-12T19:49:02'),
(226, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T21:23:14', '2026-09-12T21:23:14'),
(227, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T21:51:33', '2026-09-12T21:51:33'),
(228, 29, NULL, 'bottle_accessories_stock_deleted', 'bottle_accessories', 14, '{"type":"straws","color":"gold","quantity":20}', NULL, '10.26.93.129', '2026-09-12T21:53:21', '2026-09-12T21:53:21'),
(229, 29, NULL, 'bottle_accessories_stock_deleted', 'bottle_accessories', 13, '{"type":"bottle_tops","color":"gold","quantity":20}', NULL, '10.29.121.232', '2026-09-12T21:53:29', '2026-09-12T21:53:29'),
(230, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T22:00:17', '2026-09-12T22:00:17'),
(231, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T22:09:25', '2026-09-12T22:09:25'),
(232, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T22:19:42', '2026-09-12T22:19:42'),
(233, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T22:26:00', '2026-09-12T22:26:00'),
(234, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T22:32:57', '2026-09-12T22:32:57'),
(235, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T22:50:16', '2026-09-12T22:50:16'),
(236, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T22:59:18', '2026-09-12T22:59:18'),
(237, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-12T22:59:53', '2026-09-12T22:59:53'),
(238, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T06:22:06', '2026-09-13T06:22:06'),
(239, 29, NULL, 'stock_adjusted_manual', 'branch_stock', 64, '{"quantity":40}', '{"quantity":"51"}', '10.29.192.116', '2026-09-13T06:33:00', '2026-09-13T06:33:00'),
(240, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T07:47:41', '2026-09-13T07:47:41'),
(241, 29, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-13T07:53:46', '2026-09-13T07:53:46'),
(242, 29, NULL, 'discount_applied_sale', 'sale', 62, '{"subtotal":150000,"original_total":135000,"discounted_total":150000}', '{"discount_applied":true}', '10.26.93.129', '2026-09-13T07:53:47', '2026-09-13T07:53:47'),
(243, 29, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-13T07:55:21', '2026-09-13T07:55:21'),
(244, 29, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-13T08:01:48', '2026-09-13T08:01:48'),
(245, 29, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-13T08:02:17', '2026-09-13T08:02:17'),
(246, 29, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-13T08:03:10', '2026-09-13T08:03:10'),
(248, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T09:59:01', '2026-09-13T09:59:01'),
(249, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T10:12:05', '2026-09-13T10:12:05'),
(250, 29, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-13T10:14:12', '2026-09-13T10:14:12'),
(251, 29, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-13T10:17:18', '2026-09-13T10:17:18'),
(252, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T10:34:44', '2026-09-13T10:34:44'),
(253, 29, NULL, 'order_status_changed', 'orders', 21, '{"status":"pending"}', '{"status":"assigned"}', '10.29.192.116', '2026-09-13T10:36:41', '2026-09-13T10:36:41'),
(254, 29, NULL, 'order_status_changed', 'orders', 21, '{"status":"assigned"}', '{"status":"ready"}', '10.29.192.116', '2026-09-13T10:36:46', '2026-09-13T10:36:46'),
(255, 29, NULL, 'order_status_changed', 'orders', 21, '{"status":"ready"}', '{"status":"completed"}', '10.29.192.116', '2026-09-13T10:36:51', '2026-09-13T10:36:51'),
(256, 29, NULL, 'stock_adjusted_manual', 'branch_stock', 64, '{"quantity":35}', '{"quantity":"34"}', '10.26.93.129', '2026-09-13T10:44:48', '2026-09-13T10:44:48'),
(257, 29, NULL, 'stock_adjusted_manual', 'branch_stock', 64, '{"quantity":34}', '{"quantity":"35"}', '10.26.93.129', '2026-09-13T10:46:11', '2026-09-13T10:46:11'),
(258, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T11:33:35', '2026-09-13T11:33:35'),
(259, 29, NULL, 'stock_adjusted_manual', 'branch_stock', 64, '{"quantity":35}', '{"quantity":"34"}', '10.29.192.116', '2026-09-13T11:34:24', '2026-09-13T11:34:24'),
(260, 29, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-13T11:49:05', '2026-09-13T11:49:05'),
(261, 29, NULL, 'stock_adjusted_manual', 'branch_stock', 64, '{"quantity":34}', '{"quantity":"30"}', '10.26.93.129', '2026-09-13T11:50:00', '2026-09-13T11:50:00'),
(262, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T12:16:24', '2026-09-13T12:16:24'),
(263, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T12:18:24', '2026-09-13T12:18:24'),
(264, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T12:23:09', '2026-09-13T12:23:09'),
(265, 29, NULL, 'bottle_accessories_stock_deleted', 'bottle_accessories', 15, '{"type":"straws","color":"gold","quantity":10}', NULL, '10.29.121.232', '2026-09-13T12:27:15', '2026-09-13T12:27:15'),
(266, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T12:43:47', '2026-09-13T12:43:47'),
(267, 37, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T12:57:39', '2026-09-13T12:57:39'),
(268, 38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T13:06:31', '2026-09-13T13:06:31'),
(269, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T13:13:48', '2026-09-13T13:13:48'),
(270, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T13:20:13', '2026-09-13T13:20:13'),
(271, 35, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T14:40:49', '2026-09-13T14:40:49'),
(272, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T15:04:24', '2026-09-13T15:04:24'),
(273, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T15:11:06', '2026-09-13T15:11:06'),
(274, 38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T15:13:08', '2026-09-13T15:13:08'),
(275, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T15:15:50', '2026-09-13T15:15:50'),
(276, 22, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T15:28:43', '2026-09-13T15:28:43'),
(277, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T15:33:08', '2026-09-13T15:33:08'),
(278, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T20:06:11', '2026-09-13T20:06:11'),
(279, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T20:24:10', '2026-09-13T20:24:10'),
(280, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-13T20:42:24', '2026-09-13T20:42:24'),
(281, 38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T08:45:20', '2026-09-14T08:45:20'),
(282, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T08:47:35', '2026-09-14T08:47:35'),
(283, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T08:48:39', '2026-09-14T08:48:39'),
(284, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T08:50:35', '2026-09-14T08:50:35'),
(285, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T08:54:29', '2026-09-14T08:54:29'),
(286, 41, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T08:56:54', '2026-09-14T08:56:54'),
(287, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T08:57:44', '2026-09-14T08:57:44'),
(288, 42, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T08:59:14', '2026-09-14T08:59:14'),
(289, 37, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T09:00:51', '2026-09-14T09:00:51'),
(290, 40, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T09:02:42', '2026-09-14T09:02:42'),
(291, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T09:03:29', '2026-09-14T09:03:29'),
(292, 40, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T09:04:09', '2026-09-14T09:04:09'),
(293, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T09:05:41', '2026-09-14T09:05:41'),
(294, 41, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T09:06:42', '2026-09-14T09:06:42'),
(295, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T09:12:15', '2026-09-14T09:12:15'),
(296, 38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T09:16:09', '2026-09-14T09:16:09'),
(297, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T09:17:35', '2026-09-14T09:17:35'),
(298, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T09:34:55', '2026-09-14T09:34:55'),
(299, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T09:40:44', '2026-09-14T09:40:44'),
(300, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T09:48:45', '2026-09-14T09:48:45'),
(301, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T09:54:36', '2026-09-14T09:54:36'),
(302, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T09:59:04', '2026-09-14T09:59:04'),
(303, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T10:01:01', '2026-09-14T10:01:01'),
(304, 42, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T10:03:26', '2026-09-14T10:03:26');
INSERT INTO public.audit_logs (id, user_id, branch_id, action, auditable_type, auditable_id, old_values, new_values, ip_address, created_at, updated_at) VALUES
(305, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T10:22:27', '2026-09-14T10:22:27'),
(306, 32, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-14T10:23:18', '2026-09-14T10:23:18'),
(307, 42, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T10:24:38', '2026-09-14T10:24:38'),
(308, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T10:29:02', '2026-09-14T10:29:02'),
(309, 23, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T10:32:08', '2026-09-14T10:32:08'),
(310, 23, NULL, 'expense_created', NULL, NULL, NULL, NULL, NULL, '2026-09-14T10:35:04', '2026-09-14T10:35:04'),
(311, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T10:35:50', '2026-09-14T10:35:50'),
(312, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T10:48:28', '2026-09-14T10:48:28'),
(313, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T10:48:30', '2026-09-14T10:48:30'),
(314, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 26, '{"volume":"100ml","quantity":30}', NULL, '10.29.192.116', '2026-09-14T10:49:00', '2026-09-14T10:49:00'),
(315, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 27, '{"volume":"100ml","quantity":30}', NULL, '10.29.121.232', '2026-09-14T10:49:09', '2026-09-14T10:49:09'),
(316, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 28, '{"volume":"100ml","quantity":60}', NULL, '10.29.121.232', '2026-09-14T10:49:19', '2026-09-14T10:49:19'),
(317, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 25, '{"volume":"12ml","quantity":56}', NULL, '10.29.121.232', '2026-09-14T10:49:28', '2026-09-14T10:49:28'),
(318, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 23, '{"volume":"30ml","quantity":71}', NULL, '10.29.121.232', '2026-09-14T10:49:35', '2026-09-14T10:49:35'),
(319, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 24, '{"volume":"50ml","quantity":104}', NULL, '10.26.93.129', '2026-09-14T10:49:41', '2026-09-14T10:49:41'),
(320, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T10:51:07', '2026-09-14T10:51:07'),
(321, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T10:51:52', '2026-09-14T10:51:52'),
(322, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T10:58:00', '2026-09-14T10:58:00'),
(2, 2, NULL, 'stock.entry', 'App\\Models\\BranchStock', 1, NULL, '{"quantity": 25}', '192.168.1.101', '2026-07-23T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(3, 6, NULL, 'sale.created', 'App\\Models\\Sale', 11, NULL, '{"total": 125000}', '192.168.1.102', '2026-08-21T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(4, 3, NULL, 'cashier.approved', 'App\\Models\\User', 8, NULL, '{"status": "active"}', '192.168.2.100', '2026-08-02T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(323, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T11:06:36', '2026-09-14T11:06:36'),
(324, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T11:10:51', '2026-09-14T11:10:51'),
(325, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T11:11:27', '2026-09-14T11:11:27'),
(326, 32, NULL, 'oil_fragrance_stock_deleted', 'oil_fragrance_stock', 1, '{"name":"My Way","quantity":99}', NULL, '10.29.121.232', '2026-09-14T11:11:42', '2026-09-14T11:11:42'),
(327, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T11:50:49', '2026-09-14T11:50:49'),
(328, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T12:00:50', '2026-09-14T12:00:50'),
(329, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T12:01:18', '2026-09-14T12:01:18'),
(330, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T13:03:26', '2026-09-14T13:03:26'),
(331, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T13:26:28', '2026-09-14T13:26:28'),
(332, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-14T13:26:30', '2026-09-14T13:26:30'),
(333, 32, NULL, 'oil_fragrance_stock_deleted', 'oil_fragrance_stock', 12, '{"name":"Emarude Super","quantity":2}', NULL, '10.29.192.116', '2026-09-14T13:43:18', '2026-09-14T13:43:18'),
(334, 32, NULL, 'oil_fragrance_stock_deleted', 'oil_fragrance_stock', 13, '{"name":"Emarude Super","quantity":2}', NULL, '10.29.192.116', '2026-09-14T13:44:44', '2026-09-14T13:44:44'),
(335, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-15T11:54:16', '2026-09-15T11:54:16'),
(336, 32, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-15T13:26:45', '2026-09-15T13:26:45'),
(337, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-15T13:45:20', '2026-09-15T13:45:20'),
(338, 23, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-15T13:50:50', '2026-09-15T13:50:50'),
(339, 23, NULL, 'expense_created', NULL, NULL, NULL, NULL, NULL, '2026-09-15T13:52:19', '2026-09-15T13:52:19'),
(340, 42, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-15T13:54:51', '2026-09-15T13:54:51'),
(341, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-15T14:09:54', '2026-09-15T14:09:54'),
(342, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-15T14:11:24', '2026-09-15T14:11:24'),
(343, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-15T14:44:16', '2026-09-15T14:44:16'),
(344, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-15T14:44:18', '2026-09-15T14:44:18'),
(345, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-15T15:43:04', '2026-09-15T15:43:04'),
(346, 32, NULL, 'product_updated', 'products', 294, NULL, NULL, '10.29.206.4', '2026-09-15T15:51:26', '2026-09-15T15:51:26'),
(347, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T08:58:10', '2026-09-16T08:58:10'),
(348, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T09:42:04', '2026-09-16T09:42:04'),
(349, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T10:04:21', '2026-09-16T10:04:21'),
(350, 42, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T11:02:12', '2026-09-16T11:02:12'),
(351, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T11:08:46', '2026-09-16T11:08:46'),
(352, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T11:10:01', '2026-09-16T11:10:01'),
(353, 23, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T14:52:52', '2026-09-16T14:52:52'),
(354, 23, NULL, 'expense_created', NULL, NULL, NULL, NULL, NULL, '2026-09-16T14:56:23', '2026-09-16T14:56:23'),
(355, 23, NULL, 'sale_created', NULL, NULL, NULL, NULL, NULL, '2026-09-16T14:57:06', '2026-09-16T14:57:06'),
(356, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T15:03:31', '2026-09-16T15:03:31'),
(357, 45, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T15:08:37', '2026-09-16T15:08:37'),
(358, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T15:11:04', '2026-09-16T15:11:04'),
(359, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T15:17:48', '2026-09-16T15:17:48'),
(360, 42, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T15:19:16', '2026-09-16T15:19:16'),
(361, 37, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T15:22:34', '2026-09-16T15:22:34'),
(362, 42, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T15:26:03', '2026-09-16T15:26:03'),
(363, 42, NULL, 'sale_created', NULL, NULL, NULL, NULL, NULL, '2026-09-16T15:26:55', '2026-09-16T15:26:55'),
(364, 42, NULL, 'expense_created', NULL, NULL, NULL, NULL, NULL, '2026-09-16T15:28:59', '2026-09-16T15:28:59'),
(365, 42, NULL, 'sale_created', NULL, NULL, NULL, NULL, NULL, '2026-09-16T15:32:46', '2026-09-16T15:32:46'),
(366, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T15:37:48', '2026-09-16T15:37:48'),
(367, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T15:46:08', '2026-09-16T15:46:08'),
(368, 32, NULL, 'product_updated', 'products', 330, NULL, NULL, '10.29.206.4', '2026-09-16T15:46:56', '2026-09-16T15:46:56'),
(369, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T16:35:24', '2026-09-16T16:35:24'),
(370, 39, NULL, 'order_status_changed', 'orders', 23, '{"status":"pending"}', '{"status":"assigned"}', '10.29.206.4', '2026-09-16T16:36:14', '2026-09-16T16:36:14'),
(371, 39, NULL, 'order_status_changed', 'orders', 23, '{"status":"assigned"}', '{"status":"ready"}', '10.31.44.131', '2026-09-16T16:36:26', '2026-09-16T16:36:26'),
(372, 39, NULL, 'order_status_changed', 'orders', 23, '{"status":"ready"}', '{"status":"completed"}', '10.31.44.131', '2026-09-16T16:36:33', '2026-09-16T16:36:33'),
(373, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T16:48:12', '2026-09-16T16:48:12'),
(374, 42, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-16T16:50:14', '2026-09-16T16:50:14'),
(375, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T10:01:52', '2026-09-17T10:01:52'),
(376, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T10:14:53', '2026-09-17T10:14:53'),
(377, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T10:35:50', '2026-09-17T10:35:50'),
(378, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T10:36:44', '2026-09-17T10:36:44'),
(379, 29, NULL, 'product_updated', 'products', 331, NULL, NULL, '10.29.206.4', '2026-09-17T10:37:13', '2026-09-17T10:37:13'),
(380, 32, NULL, 'product_updated', 'products', 331, NULL, NULL, '10.29.206.4', '2026-09-17T10:38:21', '2026-09-17T10:38:21'),
(381, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T10:41:45', '2026-09-17T10:41:45'),
(382, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T11:01:14', '2026-09-17T11:01:14'),
(383, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T11:22:51', '2026-09-17T11:22:51'),
(384, 32, NULL, 'product_updated', 'products', 218, NULL, NULL, '10.25.120.81', '2026-09-17T11:23:59', '2026-09-17T11:23:59'),
(385, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T11:36:42', '2026-09-17T11:36:42'),
(386, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T12:09:31', '2026-09-17T12:09:31'),
(387, 42, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T12:11:32', '2026-09-17T12:11:32'),
(388, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T12:48:35', '2026-09-17T12:48:35'),
(389, 32, NULL, 'stock_adjusted_manual', 'branch_stock', 67, '{"quantity":5}', '{"quantity":"6"}', '10.25.120.81', '2026-09-17T12:49:14', '2026-09-17T12:49:14'),
(390, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T13:17:58', '2026-09-17T13:17:58'),
(391, 32, NULL, 'product_updated', 'products', 312, NULL, NULL, '10.31.44.131', '2026-09-17T13:21:53', '2026-09-17T13:21:53'),
(392, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T13:24:11', '2026-09-17T13:24:11'),
(393, 32, NULL, 'product_deactivated', 'products', 331, '{"is_active":true}', '{"is_active":false}', '10.31.44.131', '2026-09-17T13:24:46', '2026-09-17T13:24:46'),
(394, 32, NULL, 'product_deactivated', 'products', 330, '{"is_active":true}', '{"is_active":false}', '10.31.44.131', '2026-09-17T13:25:26', '2026-09-17T13:25:26'),
(395, 32, NULL, 'product_updated', 'products', 311, NULL, NULL, '10.25.120.81', '2026-09-17T13:25:56', '2026-09-17T13:25:56'),
(396, 32, NULL, 'product_updated', 'products', 310, NULL, NULL, '10.29.206.4', '2026-09-17T13:26:25', '2026-09-17T13:26:25'),
(397, 32, NULL, 'product_updated', 'products', 309, NULL, NULL, '10.29.206.4', '2026-09-17T13:26:58', '2026-09-17T13:26:58'),
(398, 32, NULL, 'product_updated', 'products', 308, NULL, NULL, '10.31.44.131', '2026-09-17T13:28:00', '2026-09-17T13:28:00'),
(399, 32, NULL, 'product_updated', 'products', 307, NULL, NULL, '10.31.44.131', '2026-09-17T13:28:25', '2026-09-17T13:28:25'),
(400, 32, NULL, 'product_updated', 'products', 306, NULL, NULL, '10.29.206.4', '2026-09-17T13:29:30', '2026-09-17T13:29:30'),
(401, 32, NULL, 'product_updated', 'products', 305, NULL, NULL, '10.25.120.81', '2026-09-17T13:30:03', '2026-09-17T13:30:03'),
(402, 32, NULL, 'product_deactivated', 'products', 305, '{"is_active":true}', '{"is_active":false}', '10.25.120.81', '2026-09-17T13:31:12', '2026-09-17T13:31:12'),
(403, 32, NULL, 'product_updated', 'products', 304, NULL, NULL, '10.29.206.4', '2026-09-17T13:34:56', '2026-09-17T13:34:56'),
(404, 32, NULL, 'product_updated', 'products', 303, NULL, NULL, '10.29.206.4', '2026-09-17T13:40:17', '2026-09-17T13:40:17'),
(405, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T13:46:21', '2026-09-17T13:46:21'),
(406, 32, NULL, 'product_deactivated', 'products', 303, '{"is_active":true}', '{"is_active":false}', '10.31.44.131', '2026-09-17T13:48:09', '2026-09-17T13:48:09'),
(407, 32, NULL, 'product_updated', 'products', 295, NULL, NULL, '10.29.206.4', '2026-09-17T13:49:23', '2026-09-17T13:49:23'),
(408, 32, NULL, 'product_updated', 'products', 299, NULL, NULL, '10.31.44.131', '2026-09-17T13:50:10', '2026-09-17T13:50:10'),
(409, 32, NULL, 'product_updated', 'products', 298, NULL, NULL, '10.29.206.4', '2026-09-17T13:51:02', '2026-09-17T13:51:02'),
(410, 32, NULL, 'product_updated', 'products', 297, NULL, NULL, '10.29.206.4', '2026-09-17T13:51:33', '2026-09-17T13:51:33'),
(411, 32, NULL, 'product_updated', 'products', 296, NULL, NULL, '10.25.120.81', '2026-09-17T13:52:11', '2026-09-17T13:52:11'),
(412, 32, NULL, 'product_updated', 'products', 294, NULL, NULL, '10.31.44.131', '2026-09-17T13:52:51', '2026-09-17T13:52:51'),
(413, 32, NULL, 'product_updated', 'products', 293, NULL, NULL, '10.29.206.4', '2026-09-17T13:53:33', '2026-09-17T13:53:33'),
(414, 32, NULL, 'product_deactivated', 'products', 333, '{"is_active":true}', '{"is_active":false}', '10.25.120.81', '2026-09-17T13:58:49', '2026-09-17T13:58:49'),
(415, 32, NULL, 'product_updated', 'products', 334, NULL, NULL, '10.31.44.131', '2026-09-17T14:03:59', '2026-09-17T14:03:59'),
(416, 32, NULL, 'product_deactivated', 'products', 334, '{"is_active":true}', '{"is_active":false}', '10.25.120.81', '2026-09-17T14:04:46', '2026-09-17T14:04:46'),
(417, 32, NULL, 'product_updated', 'products', 291, NULL, NULL, '10.31.44.131', '2026-09-17T14:35:08', '2026-09-17T14:35:08'),
(418, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T14:51:37', '2026-09-17T14:51:37'),
(419, 32, NULL, 'product_updated', 'products', 292, NULL, NULL, '10.31.44.131', '2026-09-17T14:52:08', '2026-09-17T14:52:08'),
(420, 32, NULL, 'product_updated', 'products', 290, NULL, NULL, '10.25.120.81', '2026-09-17T14:55:21', '2026-09-17T14:55:21'),
(421, 32, NULL, 'product_updated', 'products', 289, NULL, NULL, '10.29.206.4', '2026-09-17T14:59:10', '2026-09-17T14:59:10'),
(422, 32, NULL, 'product_updated', 'products', 288, NULL, NULL, '10.25.120.81', '2026-09-17T15:01:55', '2026-09-17T15:01:55'),
(423, 32, NULL, 'product_updated', 'products', 287, NULL, NULL, '10.25.120.81', '2026-09-17T15:04:58', '2026-09-17T15:04:58'),
(424, 32, NULL, 'product_updated', 'products', 286, NULL, NULL, '10.25.120.81', '2026-09-17T15:06:17', '2026-09-17T15:06:17'),
(425, 32, NULL, 'product_updated', 'products', 285, NULL, NULL, '10.31.44.131', '2026-09-17T15:07:23', '2026-09-17T15:07:23'),
(426, 32, NULL, 'product_updated', 'products', 284, NULL, NULL, '10.31.44.131', '2026-09-17T15:08:46', '2026-09-17T15:08:46'),
(427, 32, NULL, 'product_updated', 'products', 282, NULL, NULL, '10.29.206.4', '2026-09-17T15:14:15', '2026-09-17T15:14:15'),
(428, 32, NULL, 'product_updated', 'products', 281, NULL, NULL, '10.25.120.81', '2026-09-17T15:15:47', '2026-09-17T15:15:47'),
(429, 32, NULL, 'product_updated', 'products', 278, NULL, NULL, '10.25.120.81', '2026-09-17T15:20:27', '2026-09-17T15:20:27'),
(430, 32, NULL, 'product_updated', 'products', 277, NULL, NULL, '10.31.44.131', '2026-09-17T15:23:00', '2026-09-17T15:23:00'),
(431, 32, NULL, 'product_updated', 'products', 108, NULL, NULL, '10.29.206.4', '2026-09-17T15:29:26', '2026-09-17T15:29:26'),
(432, 32, NULL, 'product_updated', 'products', 276, NULL, NULL, '10.25.120.81', '2026-09-17T15:31:06', '2026-09-17T15:31:06'),
(433, 32, NULL, 'product_updated', 'products', 335, NULL, NULL, '10.31.44.131', '2026-09-17T15:35:56', '2026-09-17T15:35:56'),
(434, 32, NULL, 'product_updated', 'products', 279, NULL, NULL, '10.25.120.81', '2026-09-17T15:38:25', '2026-09-17T15:38:25'),
(435, 32, NULL, 'product_updated', 'products', 275, NULL, NULL, '10.25.120.81', '2026-09-17T15:44:29', '2026-09-17T15:44:29'),
(436, 32, NULL, 'product_updated', 'products', 274, NULL, NULL, '10.25.120.81', '2026-09-17T15:47:23', '2026-09-17T15:47:23'),
(437, 32, NULL, 'product_updated', 'products', 273, NULL, NULL, '10.29.206.4', '2026-09-17T15:49:51', '2026-09-17T15:49:51'),
(438, 32, NULL, 'product_updated', 'products', 272, NULL, NULL, '10.25.120.81', '2026-09-17T15:51:13', '2026-09-17T15:51:13'),
(439, 32, NULL, 'product_updated', 'products', 271, NULL, NULL, '10.29.206.4', '2026-09-17T15:54:07', '2026-09-17T15:54:07'),
(440, 32, NULL, 'product_updated', 'products', 270, NULL, NULL, '10.29.206.4', '2026-09-17T15:56:51', '2026-09-17T15:56:51'),
(441, 32, NULL, 'product_updated', 'products', 269, NULL, NULL, '10.25.120.81', '2026-09-17T15:58:27', '2026-09-17T15:58:27'),
(442, 32, NULL, 'product_updated', 'products', 268, NULL, NULL, '10.31.44.131', '2026-09-17T16:00:36', '2026-09-17T16:00:36'),
(443, 32, NULL, 'product_updated', 'products', 267, NULL, NULL, '10.29.206.4', '2026-09-17T16:02:44', '2026-09-17T16:02:44'),
(444, 32, NULL, 'product_updated', 'products', 266, NULL, NULL, '10.31.44.131', '2026-09-17T16:04:56', '2026-09-17T16:04:56'),
(445, 32, NULL, 'product_updated', 'products', 265, NULL, NULL, '10.29.206.4', '2026-09-17T16:06:20', '2026-09-17T16:06:20'),
(446, 32, NULL, 'product_updated', 'products', 264, NULL, NULL, '10.25.120.81', '2026-09-17T16:08:55', '2026-09-17T16:08:55'),
(447, 32, NULL, 'product_updated', 'products', 263, NULL, NULL, '10.25.120.81', '2026-09-17T16:10:57', '2026-09-17T16:10:57'),
(448, 32, NULL, 'product_updated', 'products', 263, NULL, NULL, '10.25.120.81', '2026-09-17T16:11:00', '2026-09-17T16:11:00'),
(449, 32, NULL, 'product_updated', 'products', 262, NULL, NULL, '10.29.206.4', '2026-09-17T16:12:14', '2026-09-17T16:12:14'),
(450, 32, NULL, 'product_updated', 'products', 261, NULL, NULL, '10.25.120.81', '2026-09-17T16:14:37', '2026-09-17T16:14:37'),
(451, 32, NULL, 'product_updated', 'products', 241, NULL, NULL, '10.29.206.4', '2026-09-17T16:19:13', '2026-09-17T16:19:13');
INSERT INTO public.audit_logs (id, user_id, branch_id, action, auditable_type, auditable_id, old_values, new_values, ip_address, created_at, updated_at) VALUES
(452, 32, NULL, 'product_updated', 'products', 259, NULL, NULL, '10.29.206.4', '2026-09-17T16:21:27', '2026-09-17T16:21:27'),
(453, 32, NULL, 'product_updated', 'products', 258, NULL, NULL, '10.29.206.4', '2026-09-17T16:22:52', '2026-09-17T16:22:52'),
(454, 32, NULL, 'product_updated', 'products', 257, NULL, NULL, '10.31.44.131', '2026-09-17T16:25:27', '2026-09-17T16:25:27'),
(455, 32, NULL, 'product_updated', 'products', 256, NULL, NULL, '10.25.120.81', '2026-09-17T16:27:47', '2026-09-17T16:27:47'),
(456, 32, NULL, 'product_updated', 'products', 255, NULL, NULL, '10.31.44.131', '2026-09-17T16:29:53', '2026-09-17T16:29:53'),
(457, 32, NULL, 'product_deactivated', 'products', 218, '{"is_active":true}', '{"is_active":false}', '10.31.44.131', '2026-09-17T16:31:37', '2026-09-17T16:31:37'),
(458, 32, NULL, 'product_updated', 'products', 254, NULL, NULL, '10.25.120.81', '2026-09-17T16:37:22', '2026-09-17T16:37:22'),
(459, 32, NULL, 'product_updated', 'products', 253, NULL, NULL, '10.29.206.4', '2026-09-17T16:39:14', '2026-09-17T16:39:14'),
(460, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-17T16:57:24', '2026-09-17T16:57:24'),
(461, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T06:11:15', '2026-09-18T06:11:15'),
(462, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T06:11:49', '2026-09-18T06:11:49'),
(463, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T06:32:10', '2026-09-18T06:32:10'),
(464, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T07:37:31', '2026-09-18T07:37:31'),
(465, 29, NULL, 'order_status_changed', 'orders', 25, '{"status":"pending"}', '{"status":"assigned"}', '10.31.44.131', '2026-09-18T07:38:31', '2026-09-18T07:38:31'),
(466, 29, NULL, 'order_status_changed', 'orders', 25, '{"status":"assigned"}', '{"status":"ready"}', '10.31.44.131', '2026-09-18T07:39:05', '2026-09-18T07:39:05'),
(467, 29, NULL, 'order_status_changed', 'orders', 25, '{"status":"ready"}', '{"status":"completed"}', '10.31.44.131', '2026-09-18T07:39:42', '2026-09-18T07:39:42'),
(468, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T08:10:33', '2026-09-18T08:10:33'),
(469, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T08:41:56', '2026-09-18T08:41:56'),
(470, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T08:52:57', '2026-09-18T08:52:57'),
(471, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T09:14:54', '2026-09-18T09:14:54'),
(472, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T09:19:36', '2026-09-18T09:19:36'),
(473, 29, NULL, 'order_status_changed', 'orders', 25, '{"status":"completed"}', '{"status":"served"}', '10.25.120.81', '2026-09-18T09:20:18', '2026-09-18T09:20:18'),
(474, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T12:13:32', '2026-09-18T12:13:32'),
(475, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T12:26:53', '2026-09-18T12:26:53'),
(476, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T12:45:31', '2026-09-18T12:45:31'),
(477, 38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T16:06:46', '2026-09-18T16:06:46'),
(478, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T16:17:07', '2026-09-18T16:17:07'),
(479, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T16:48:35', '2026-09-18T16:48:35'),
(480, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T17:08:35', '2026-09-18T17:08:35'),
(481, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T17:49:49', '2026-09-18T17:49:49'),
(482, 32, NULL, 'product_updated', 'products', 260, NULL, NULL, '10.31.44.131', '2026-09-18T17:50:38', '2026-09-18T17:50:38'),
(483, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T17:52:26', '2026-09-18T17:52:26'),
(484, 32, NULL, 'product_updated', 'products', 252, NULL, NULL, '10.31.44.131', '2026-09-18T17:53:02', '2026-09-18T17:53:02'),
(485, 32, NULL, 'product_updated', 'products', 251, NULL, NULL, '10.31.44.131', '2026-09-18T17:54:08', '2026-09-18T17:54:08'),
(486, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T17:57:01', '2026-09-18T17:57:01'),
(487, 32, NULL, 'product_updated', 'products', 250, NULL, NULL, '10.29.206.4', '2026-09-18T17:58:51', '2026-09-18T17:58:51'),
(488, 32, NULL, 'product_updated', 'products', 249, NULL, NULL, '10.25.120.81', '2026-09-18T18:00:31', '2026-09-18T18:00:31'),
(489, 32, NULL, 'product_updated', 'products', 248, NULL, NULL, '10.29.206.4', '2026-09-18T18:04:35', '2026-09-18T18:04:35'),
(490, 32, NULL, 'product_updated', 'products', 246, NULL, NULL, '10.29.206.4', '2026-09-18T18:05:40', '2026-09-18T18:05:40'),
(491, 32, NULL, 'product_updated', 'products', 245, NULL, NULL, '10.31.44.131', '2026-09-18T18:07:10', '2026-09-18T18:07:10'),
(492, 32, NULL, 'product_updated', 'products', 244, NULL, NULL, '10.31.44.131', '2026-09-18T18:08:21', '2026-09-18T18:08:21'),
(493, 32, NULL, 'product_updated', 'products', 280, NULL, NULL, '10.31.44.131', '2026-09-18T18:16:43', '2026-09-18T18:16:43'),
(494, 32, NULL, 'product_updated', 'products', 243, NULL, NULL, '10.25.120.81', '2026-09-18T18:32:01', '2026-09-18T18:32:01'),
(495, 32, NULL, 'product_updated', 'products', 242, NULL, NULL, '10.29.206.4', '2026-09-18T18:33:44', '2026-09-18T18:33:44'),
(496, 32, NULL, 'product_updated', 'products', 240, NULL, NULL, '10.25.120.81', '2026-09-18T18:35:57', '2026-09-18T18:35:57'),
(497, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T18:39:50', '2026-09-18T18:39:50'),
(498, 32, NULL, 'product_updated', 'products', 239, NULL, NULL, '10.25.120.81', '2026-09-18T18:40:40', '2026-09-18T18:40:40'),
(499, 32, NULL, 'product_updated', 'products', 238, NULL, NULL, '10.29.206.4', '2026-09-18T18:43:20', '2026-09-18T18:43:20'),
(500, 32, NULL, 'product_updated', 'products', 237, NULL, NULL, '10.29.206.4', '2026-09-18T18:45:14', '2026-09-18T18:45:14'),
(501, 32, NULL, 'product_updated', 'products', 324, NULL, NULL, '10.29.206.4', '2026-09-18T18:45:44', '2026-09-18T18:45:44'),
(502, 32, NULL, 'product_updated', 'products', 236, NULL, NULL, '10.29.206.4', '2026-09-18T18:47:58', '2026-09-18T18:47:58'),
(503, 32, NULL, 'product_updated', 'products', 235, NULL, NULL, '10.29.206.4', '2026-09-18T18:49:58', '2026-09-18T18:49:58'),
(504, 32, NULL, 'product_updated', 'products', 234, NULL, NULL, '10.29.206.4', '2026-09-18T18:53:58', '2026-09-18T18:53:58'),
(505, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-18T18:55:46', '2026-09-18T18:55:46'),
(506, 32, NULL, 'product_updated', 'products', 233, NULL, NULL, '10.25.120.81', '2026-09-18T19:02:21', '2026-09-18T19:02:21'),
(507, 32, NULL, 'product_updated', 'products', 232, NULL, NULL, '10.25.120.81', '2026-09-18T19:05:56', '2026-09-18T19:05:56'),
(508, 38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T08:16:56', '2026-09-19T08:16:56'),
(509, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T08:26:54', '2026-09-19T08:26:54'),
(510, 32, NULL, 'product_updated', 'products', 227, NULL, NULL, '10.25.162.97', '2026-09-19T08:53:32', '2026-09-19T08:53:32'),
(511, 32, NULL, 'product_updated', 'products', 228, NULL, NULL, '10.27.104.133', '2026-09-19T09:00:39', '2026-09-19T09:00:39'),
(512, 32, NULL, 'product_updated', 'products', 229, NULL, NULL, '10.25.162.97', '2026-09-19T09:02:01', '2026-09-19T09:02:01'),
(513, 32, NULL, 'product_updated', 'products', 231, NULL, NULL, '10.30.78.134', '2026-09-19T09:06:49', '2026-09-19T09:06:49'),
(514, 32, NULL, 'product_updated', 'products', 230, NULL, NULL, '10.27.104.133', '2026-09-19T09:08:53', '2026-09-19T09:08:53'),
(515, 32, NULL, 'product_updated', 'products', 226, NULL, NULL, '10.30.78.134', '2026-09-19T09:18:05', '2026-09-19T09:18:05'),
(516, 32, NULL, 'product_updated', 'products', 225, NULL, NULL, '10.27.104.133', '2026-09-19T09:20:16', '2026-09-19T09:20:16'),
(517, 32, NULL, 'product_updated', 'products', 224, NULL, NULL, '10.25.162.97', '2026-09-19T09:24:06', '2026-09-19T09:24:06'),
(518, 32, NULL, 'product_updated', 'products', 223, NULL, NULL, '10.27.104.133', '2026-09-19T09:29:58', '2026-09-19T09:29:58'),
(519, 32, NULL, 'product_updated', 'products', 222, NULL, NULL, '10.25.162.97', '2026-09-19T09:32:37', '2026-09-19T09:32:37'),
(520, 32, NULL, 'product_updated', 'products', 221, NULL, NULL, '10.27.104.133', '2026-09-19T09:43:36', '2026-09-19T09:43:36'),
(521, 32, NULL, 'product_updated', 'products', 220, NULL, NULL, '10.25.162.97', '2026-09-19T09:44:49', '2026-09-19T09:44:49'),
(522, 32, NULL, 'product_updated', 'products', 220, NULL, NULL, '10.25.162.97', '2026-09-19T09:45:25', '2026-09-19T09:45:25'),
(523, 32, NULL, 'product_updated', 'products', 219, NULL, NULL, '10.30.78.134', '2026-09-19T09:47:59', '2026-09-19T09:47:59'),
(524, 32, NULL, 'product_updated', 'products', 217, NULL, NULL, '10.27.104.133', '2026-09-19T09:49:55', '2026-09-19T09:49:55'),
(525, 32, NULL, 'product_updated', 'products', 216, NULL, NULL, '10.27.104.133', '2026-09-19T09:51:53', '2026-09-19T09:51:53'),
(526, 32, NULL, 'product_updated', 'products', 215, NULL, NULL, '10.25.162.97', '2026-09-19T09:54:31', '2026-09-19T09:54:31'),
(527, 32, NULL, 'product_updated', 'products', 214, NULL, NULL, '10.25.162.97', '2026-09-19T09:58:02', '2026-09-19T09:58:02'),
(528, 32, NULL, 'product_updated', 'products', 213, NULL, NULL, '10.25.162.97', '2026-09-19T10:00:13', '2026-09-19T10:00:13'),
(529, 32, NULL, 'product_updated', 'products', 212, NULL, NULL, '10.30.78.134', '2026-09-19T10:02:29', '2026-09-19T10:02:29'),
(530, 32, NULL, 'product_updated', 'products', 211, NULL, NULL, '10.27.104.133', '2026-09-19T10:03:46', '2026-09-19T10:03:46'),
(531, 32, NULL, 'product_updated', 'products', 210, NULL, NULL, '10.27.104.133', '2026-09-19T10:05:22', '2026-09-19T10:05:22'),
(532, 32, NULL, 'product_updated', 'products', 209, NULL, NULL, '10.27.104.133', '2026-09-19T10:07:33', '2026-09-19T10:07:33'),
(533, 32, NULL, 'product_updated', 'products', 208, NULL, NULL, '10.30.78.134', '2026-09-19T10:08:47', '2026-09-19T10:08:47'),
(534, 32, NULL, 'product_updated', 'products', 207, NULL, NULL, '10.25.162.97', '2026-09-19T10:11:00', '2026-09-19T10:11:00'),
(535, 32, NULL, 'product_updated', 'products', 206, NULL, NULL, '10.27.104.133', '2026-09-19T10:19:19', '2026-09-19T10:19:19'),
(536, 32, NULL, 'product_updated', 'products', 205, NULL, NULL, '10.27.104.133', '2026-09-19T10:22:30', '2026-09-19T10:22:30'),
(537, 32, NULL, 'product_updated', 'products', 205, NULL, NULL, '10.25.162.97', '2026-09-19T10:23:10', '2026-09-19T10:23:10'),
(538, 32, NULL, 'product_updated', 'products', 204, NULL, NULL, '10.27.104.133', '2026-09-19T10:34:35', '2026-09-19T10:34:35'),
(539, 32, NULL, 'product_updated', 'products', 203, NULL, NULL, '10.27.104.133', '2026-09-19T10:37:50', '2026-09-19T10:37:50'),
(540, 32, NULL, 'product_updated', 'products', 202, NULL, NULL, '10.25.162.97', '2026-09-19T10:39:42', '2026-09-19T10:39:42'),
(541, 32, NULL, 'product_updated', 'products', 201, NULL, NULL, '10.25.162.97', '2026-09-19T10:55:21', '2026-09-19T10:55:21'),
(542, 32, NULL, 'product_updated', 'products', 200, NULL, NULL, '10.27.104.133', '2026-09-19T10:56:38', '2026-09-19T10:56:38'),
(543, 32, NULL, 'product_updated', 'products', 199, NULL, NULL, '10.25.162.97', '2026-09-19T10:58:23', '2026-09-19T10:58:23'),
(544, 32, NULL, 'product_updated', 'products', 198, NULL, NULL, '10.30.78.134', '2026-09-19T11:02:08', '2026-09-19T11:02:08'),
(545, 32, NULL, 'product_updated', 'products', 197, NULL, NULL, '10.27.104.133', '2026-09-19T11:04:10', '2026-09-19T11:04:10'),
(546, 32, NULL, 'product_updated', 'products', 196, NULL, NULL, '10.25.162.97', '2026-09-19T11:07:59', '2026-09-19T11:07:59'),
(547, 32, NULL, 'product_updated', 'products', 195, NULL, NULL, '10.27.104.133', '2026-09-19T11:11:41', '2026-09-19T11:11:41'),
(548, 32, NULL, 'product_updated', 'products', 194, NULL, NULL, '10.27.104.133', '2026-09-19T11:12:49', '2026-09-19T11:12:49'),
(549, 32, NULL, 'product_updated', 'products', 193, NULL, NULL, '10.30.78.134', '2026-09-19T11:14:42', '2026-09-19T11:14:42'),
(550, 32, NULL, 'product_updated', 'products', 192, NULL, NULL, '10.30.78.134', '2026-09-19T11:17:09', '2026-09-19T11:17:09'),
(551, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T13:34:06', '2026-09-19T13:34:06'),
(552, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 40, '{"volume":"6ml","quantity":344}', NULL, '10.25.162.97', '2026-09-19T13:35:46', '2026-09-19T13:35:46'),
(553, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 35, '{"volume":"100ml","quantity":571}', NULL, '10.25.162.97', '2026-09-19T13:35:57', '2026-09-19T13:35:57'),
(554, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 36, '{"volume":"100ml","quantity":179}', NULL, '10.27.104.133', '2026-09-19T13:36:06', '2026-09-19T13:36:06'),
(555, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 38, '{"volume":"100ml","quantity":112}', NULL, '10.30.78.134', '2026-09-19T13:36:16', '2026-09-19T13:36:16'),
(556, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 37, '{"volume":"100ml","quantity":151}', NULL, '10.30.78.134', '2026-09-19T13:36:24', '2026-09-19T13:36:24'),
(557, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 39, '{"volume":"12ml","quantity":441}', NULL, '10.30.78.134', '2026-09-19T13:36:32', '2026-09-19T13:36:32'),
(558, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 29, '{"volume":"30ml","quantity":71}', NULL, '10.30.78.134', '2026-09-19T13:36:43', '2026-09-19T13:36:43'),
(559, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 41, '{"volume":"30ml","quantity":252}', NULL, '10.30.78.134', '2026-09-19T13:36:51', '2026-09-19T13:36:51'),
(560, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 42, '{"volume":"30ml","quantity":204}', NULL, '10.27.104.133', '2026-09-19T13:37:00', '2026-09-19T13:37:00'),
(561, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 30, '{"volume":"50ml","quantity":1008}', NULL, '10.30.78.134', '2026-09-19T13:37:08', '2026-09-19T13:37:08'),
(562, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 34, '{"volume":"50ml","quantity":50}', NULL, '10.27.104.133', '2026-09-19T13:37:19', '2026-09-19T13:37:19'),
(563, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 32, '{"volume":"50ml","quantity":207}', NULL, '10.25.162.97', '2026-09-19T13:37:26', '2026-09-19T13:37:26'),
(564, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 33, '{"volume":"50ml","quantity":170}', NULL, '10.30.78.134', '2026-09-19T13:37:35', '2026-09-19T13:37:35'),
(565, 32, NULL, 'bottle_stock_deleted', 'bottle_stock', 31, '{"volume":"50ml","quantity":74}', NULL, '10.27.104.133', '2026-09-19T13:37:45', '2026-09-19T13:37:45'),
(566, 32, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-19T14:54:42', '2026-09-19T14:54:42'),
(567, 32, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-19T15:26:44', '2026-09-19T15:26:44'),
(568, 23, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T15:31:46', '2026-09-19T15:31:46'),
(569, 23, NULL, 'expense_created', NULL, NULL, NULL, NULL, NULL, '2026-09-19T15:33:04', '2026-09-19T15:33:04'),
(570, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T15:34:40', '2026-09-19T15:34:40'),
(571, 38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T15:37:55', '2026-09-19T15:37:55'),
(572, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T15:40:41', '2026-09-19T15:40:41'),
(573, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T16:09:33', '2026-09-19T16:09:33'),
(574, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T16:58:59', '2026-09-19T16:58:59'),
(575, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T17:13:14', '2026-09-19T17:13:14'),
(576, 29, NULL, 'stock_record_deleted', 'branch_stock', 70, '{"quantity":30,"selling_price":54000}', NULL, '10.27.104.133', '2026-09-19T17:19:53', '2026-09-19T17:19:53'),
(577, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T19:36:32', '2026-09-19T19:36:32'),
(578, 29, NULL, 'stock_record_deleted', 'branch_stock', 71, '{"quantity":200,"selling_price":54000}', NULL, '10.30.78.134', '2026-09-19T19:37:02', '2026-09-19T19:37:02'),
(579, 29, NULL, 'price_change_stock_in', 'branch_stock', 72, '{"selling_price":54000}', '{"selling_price":"105000"}', '10.25.162.97', '2026-09-19T19:40:37', '2026-09-19T19:40:37'),
(580, 29, NULL, 'price_change_stock_in', 'branch_stock', 72, '{"selling_price":105000}', '{"selling_price":"54000"}', '10.25.162.97', '2026-09-19T19:42:22', '2026-09-19T19:42:22'),
(581, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T19:56:53', '2026-09-19T19:56:53'),
(582, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T20:39:48', '2026-09-19T20:39:48'),
(583, 29, NULL, 'price_change_stock_in', 'branch_stock', 72, '{"selling_price":54000}', '{"selling_price":37000}', '10.30.78.134', '2026-09-19T20:41:28', '2026-09-19T20:41:28'),
(584, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T20:49:08', '2026-09-19T20:49:08'),
(585, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T20:53:28', '2026-09-19T20:53:28'),
(586, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-19T21:04:28', '2026-09-19T21:04:28'),
(587, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-20T07:52:07', '2026-09-20T07:52:07'),
(588, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-20T08:13:05', '2026-09-20T08:13:05'),
(589, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-21T08:02:18', '2026-09-21T08:02:18'),
(590, 37, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-21T08:10:00', '2026-09-21T08:10:00'),
(591, 37, NULL, 'transfer_item_received', NULL, NULL, NULL, NULL, '10.25.162.97', '2026-09-21T08:11:06', '2026-09-21T08:11:06'),
(592, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-21T08:16:51', '2026-09-21T08:16:51'),
(593, 36, NULL, 'transfer_item_received', NULL, NULL, NULL, NULL, '10.25.162.97', '2026-09-21T08:17:46', '2026-09-21T08:17:46'),
(594, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-21T08:28:26', '2026-09-21T08:28:26'),
(595, 37, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-21T08:37:50', '2026-09-21T08:37:50'),
(596, 37, NULL, 'transfer_item_received', NULL, NULL, NULL, NULL, '10.30.78.134', '2026-09-21T08:38:36', '2026-09-21T08:38:36'),
(597, 37, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-21T08:49:46', '2026-09-21T08:49:46'),
(598, 37, NULL, 'discount_applied_sale', 'sale', 78, '{"subtotal":100000,"original_total":108000,"discounted_total":100000}', '{"discount_applied":true}', '10.25.162.97', '2026-09-21T08:49:46', '2026-09-21T08:49:46'),
(599, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-21T08:53:42', '2026-09-21T08:53:42'),
(600, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-21T10:07:26', '2026-09-21T10:07:26'),
(601, 32, NULL, 'order_status_changed', 'orders', 26, '{"status":"pending"}', '{"status":"assigned"}', '10.30.78.134', '2026-09-21T10:09:34', '2026-09-21T10:09:34');
INSERT INTO public.audit_logs (id, user_id, branch_id, action, auditable_type, auditable_id, old_values, new_values, ip_address, created_at, updated_at) VALUES
(602, 32, NULL, 'order_status_changed', 'orders', 26, '{"status":"assigned"}', '{"status":"ready"}', '10.27.104.133', '2026-09-21T10:10:09', '2026-09-21T10:10:09'),
(603, 32, NULL, 'order_status_changed', 'orders', 26, '{"status":"ready"}', '{"status":"completed"}', '10.25.162.97', '2026-09-21T10:10:59', '2026-09-21T10:10:59'),
(604, 32, NULL, 'order_status_changed', 'orders', 26, '{"status":"completed"}', '{"status":"served"}', '10.27.104.133', '2026-09-21T10:11:17', '2026-09-21T10:11:17'),
(605, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-21T10:13:36', '2026-09-21T10:13:36'),
(606, 32, NULL, 'order_status_changed', 'orders', 24, '{"status":"pending"}', '{"status":"assigned"}', '10.30.78.134', '2026-09-21T10:16:09', '2026-09-21T10:16:09'),
(607, 32, NULL, 'order_status_changed', 'orders', 22, '{"status":"pending"}', '{"status":"cancelled"}', '10.30.78.134', '2026-09-21T10:19:58', '2026-09-21T10:19:58'),
(608, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-21T11:57:29', '2026-09-21T11:57:29'),
(609, 32, NULL, 'product_updated', 'products', 191, NULL, NULL, '10.30.78.134', '2026-09-21T12:01:22', '2026-09-21T12:01:22'),
(610, 32, NULL, 'product_updated', 'products', 190, NULL, NULL, '10.30.78.134', '2026-09-21T12:06:47', '2026-09-21T12:06:47'),
(611, 32, NULL, 'product_updated', 'products', 189, NULL, NULL, '10.27.104.133', '2026-09-21T12:11:04', '2026-09-21T12:11:04'),
(612, 32, NULL, 'product_updated', 'products', 188, NULL, NULL, '10.30.78.134', '2026-09-21T12:24:23', '2026-09-21T12:24:23'),
(613, 32, NULL, 'product_updated', 'products', 187, NULL, NULL, '10.30.78.134', '2026-09-21T12:28:02', '2026-09-21T12:28:02'),
(614, 32, NULL, 'product_updated', 'products', 186, NULL, NULL, '10.30.78.134', '2026-09-21T12:29:48', '2026-09-21T12:29:48'),
(615, 32, NULL, 'product_updated', 'products', 185, NULL, NULL, '10.25.162.97', '2026-09-21T12:32:05', '2026-09-21T12:32:05'),
(616, 32, NULL, 'product_updated', 'products', 184, NULL, NULL, '10.25.162.97', '2026-09-21T12:36:56', '2026-09-21T12:36:56'),
(617, 32, NULL, 'product_updated', 'products', 183, NULL, NULL, '10.25.162.97', '2026-09-21T12:39:30', '2026-09-21T12:39:30'),
(618, 32, NULL, 'product_updated', 'products', 182, NULL, NULL, '10.27.104.133', '2026-09-21T12:41:38', '2026-09-21T12:41:38'),
(619, 32, NULL, 'product_updated', 'products', 181, NULL, NULL, '10.27.104.133', '2026-09-21T12:43:55', '2026-09-21T12:43:55'),
(620, 32, NULL, 'product_updated', 'products', 180, NULL, NULL, '10.25.162.97', '2026-09-21T12:48:51', '2026-09-21T12:48:51'),
(621, 32, NULL, 'product_updated', 'products', 179, NULL, NULL, '10.27.104.133', '2026-09-21T12:51:36', '2026-09-21T12:51:36'),
(622, 32, NULL, 'product_updated', 'products', 178, NULL, NULL, '10.27.104.133', '2026-09-21T12:52:50', '2026-09-21T12:52:50'),
(623, 32, NULL, 'product_updated', 'products', 177, NULL, NULL, '10.27.104.133', '2026-09-21T12:54:06', '2026-09-21T12:54:06'),
(624, 32, NULL, 'product_updated', 'products', 176, NULL, NULL, '10.27.104.133', '2026-09-21T12:55:33', '2026-09-21T12:55:33'),
(625, 32, NULL, 'product_updated', 'products', 175, NULL, NULL, '10.27.104.133', '2026-09-21T12:57:13', '2026-09-21T12:57:13'),
(626, 32, NULL, 'product_updated', 'products', 174, NULL, NULL, '10.30.78.134', '2026-09-21T12:58:45', '2026-09-21T12:58:45'),
(627, 32, NULL, 'product_updated', 'products', 173, NULL, NULL, '10.25.162.97', '2026-09-21T12:59:17', '2026-09-21T12:59:17'),
(628, 32, NULL, 'product_updated', 'products', 172, NULL, NULL, '10.25.162.97', '2026-09-21T13:00:36', '2026-09-21T13:00:36'),
(629, 32, NULL, 'product_updated', 'products', 171, NULL, NULL, '10.25.162.97', '2026-09-21T13:04:27', '2026-09-21T13:04:27'),
(630, 32, NULL, 'product_updated', 'products', 170, NULL, NULL, '10.30.78.134', '2026-09-21T13:06:15', '2026-09-21T13:06:15'),
(631, 32, NULL, 'product_updated', 'products', 169, NULL, NULL, '10.30.78.134', '2026-09-21T13:08:11', '2026-09-21T13:08:11'),
(632, 32, NULL, 'product_updated', 'products', 168, NULL, NULL, '10.25.162.97', '2026-09-21T13:12:49', '2026-09-21T13:12:49'),
(633, 32, NULL, 'product_updated', 'products', 167, NULL, NULL, '10.30.78.134', '2026-09-21T13:24:33', '2026-09-21T13:24:33'),
(634, 32, NULL, 'product_updated', 'products', 167, NULL, NULL, '10.30.78.134', '2026-09-21T13:24:42', '2026-09-21T13:24:42'),
(635, 32, NULL, 'product_updated', 'products', 166, NULL, NULL, '10.25.162.97', '2026-09-21T13:26:48', '2026-09-21T13:26:48'),
(636, 32, NULL, 'product_updated', 'products', 165, NULL, NULL, '10.30.78.134', '2026-09-21T13:27:58', '2026-09-21T13:27:58'),
(637, 32, NULL, 'product_updated', 'products', 164, NULL, NULL, '10.30.78.134', '2026-09-21T13:33:18', '2026-09-21T13:33:18'),
(638, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-21T13:40:42', '2026-09-21T13:40:42'),
(639, 32, NULL, 'product_updated', 'products', 162, NULL, NULL, '10.30.78.134', '2026-09-21T13:55:49', '2026-09-21T13:55:49'),
(640, 32, NULL, 'product_updated', 'products', 160, NULL, NULL, '10.25.162.97', '2026-09-21T14:02:07', '2026-09-21T14:02:07'),
(641, 32, NULL, 'product_updated', 'products', 159, NULL, NULL, '10.27.104.133', '2026-09-21T14:04:23', '2026-09-21T14:04:23'),
(642, 32, NULL, 'product_updated', 'products', 157, NULL, NULL, '10.30.78.134', '2026-09-21T14:17:00', '2026-09-21T14:17:00'),
(643, 32, NULL, 'product_updated', 'products', 156, NULL, NULL, '10.25.162.97', '2026-09-21T14:20:51', '2026-09-21T14:20:51'),
(644, 32, NULL, 'product_updated', 'products', 155, NULL, NULL, '10.30.78.134', '2026-09-21T14:25:17', '2026-09-21T14:25:17'),
(645, 32, NULL, 'product_updated', 'products', 154, NULL, NULL, '10.27.104.133', '2026-09-21T14:26:30', '2026-09-21T14:26:30'),
(646, 32, NULL, 'product_updated', 'products', 153, NULL, NULL, '10.25.162.97', '2026-09-21T14:28:25', '2026-09-21T14:28:25'),
(647, 32, NULL, 'product_updated', 'products', 152, NULL, NULL, '10.25.162.97', '2026-09-21T14:30:18', '2026-09-21T14:30:18'),
(648, 32, NULL, 'product_updated', 'products', 151, NULL, NULL, '10.30.78.134', '2026-09-21T14:40:48', '2026-09-21T14:40:48'),
(649, 32, NULL, 'product_updated', 'products', 150, NULL, NULL, '10.30.78.134', '2026-09-21T14:43:36', '2026-09-21T14:43:36'),
(650, 32, NULL, 'product_updated', 'products', 149, NULL, NULL, '10.30.78.134', '2026-09-21T14:46:30', '2026-09-21T14:46:30'),
(651, 32, NULL, 'product_updated', 'products', 148, NULL, NULL, '10.30.78.134', '2026-09-21T14:47:27', '2026-09-21T14:47:27'),
(652, 32, NULL, 'product_updated', 'products', 147, NULL, NULL, '10.30.78.134', '2026-09-21T14:48:04', '2026-09-21T14:48:04'),
(653, 32, NULL, 'product_updated', 'products', 146, NULL, NULL, '10.27.104.133', '2026-09-21T14:48:39', '2026-09-21T14:48:39'),
(654, 32, NULL, 'product_updated', 'products', 145, NULL, NULL, '10.25.162.97', '2026-09-21T14:50:34', '2026-09-21T14:50:34'),
(655, 32, NULL, 'product_updated', 'products', 144, NULL, NULL, '10.30.78.134', '2026-09-21T14:51:56', '2026-09-21T14:51:56'),
(656, 32, NULL, 'product_updated', 'products', 143, NULL, NULL, '10.25.162.97', '2026-09-21T14:54:01', '2026-09-21T14:54:01'),
(657, 32, NULL, 'product_updated', 'products', 142, NULL, NULL, '10.25.162.97', '2026-09-21T14:57:01', '2026-09-21T14:57:01'),
(658, 32, NULL, 'product_updated', 'products', 139, NULL, NULL, '10.25.162.97', '2026-09-21T14:58:20', '2026-09-21T14:58:20'),
(659, 32, NULL, 'product_updated', 'products', 141, NULL, NULL, '10.25.162.97', '2026-09-21T15:04:38', '2026-09-21T15:04:38'),
(660, 32, NULL, 'product_updated', 'products', 140, NULL, NULL, '10.25.162.97', '2026-09-21T15:10:53', '2026-09-21T15:10:53'),
(661, 32, NULL, 'product_updated', 'products', 138, NULL, NULL, '10.27.104.133', '2026-09-21T15:13:02', '2026-09-21T15:13:02'),
(662, 32, NULL, 'product_updated', 'products', 137, NULL, NULL, '10.27.104.133', '2026-09-21T15:16:49', '2026-09-21T15:16:49'),
(663, 32, NULL, 'product_updated', 'products', 136, NULL, NULL, '10.25.162.97', '2026-09-21T15:18:45', '2026-09-21T15:18:45'),
(664, 32, NULL, 'product_updated', 'products', 135, NULL, NULL, '10.30.78.134', '2026-09-21T15:20:07', '2026-09-21T15:20:07'),
(665, 32, NULL, 'product_updated', 'products', 134, NULL, NULL, '10.27.104.133', '2026-09-21T15:22:09', '2026-09-21T15:22:09'),
(666, 32, NULL, 'product_updated', 'products', 133, NULL, NULL, '10.27.104.133', '2026-09-21T15:26:32', '2026-09-21T15:26:32'),
(667, 32, NULL, 'product_updated', 'products', 132, NULL, NULL, '10.30.78.134', '2026-09-21T15:28:56', '2026-09-21T15:28:56'),
(668, 32, NULL, 'product_updated', 'products', 131, NULL, NULL, '10.25.162.97', '2026-09-21T15:32:41', '2026-09-21T15:32:41'),
(669, 32, NULL, 'product_updated', 'products', 130, NULL, NULL, '10.25.162.97', '2026-09-21T15:34:37', '2026-09-21T15:34:37'),
(670, 32, NULL, 'product_updated', 'products', 129, NULL, NULL, '10.27.104.133', '2026-09-21T15:37:26', '2026-09-21T15:37:26'),
(671, 32, NULL, 'product_updated', 'products', 128, NULL, NULL, '10.25.162.97', '2026-09-21T15:40:58', '2026-09-21T15:40:58'),
(672, 32, NULL, 'product_updated', 'products', 127, NULL, NULL, '10.27.104.133', '2026-09-21T15:49:54', '2026-09-21T15:49:54'),
(673, 32, NULL, 'product_updated', 'products', 126, NULL, NULL, '10.30.78.134', '2026-09-21T15:53:10', '2026-09-21T15:53:10'),
(674, 32, NULL, 'product_updated', 'products', 125, NULL, NULL, '10.27.104.133', '2026-09-21T15:57:29', '2026-09-21T15:57:29'),
(675, 32, NULL, 'product_updated', 'products', 124, NULL, NULL, '10.25.162.97', '2026-09-21T15:59:01', '2026-09-21T15:59:01'),
(676, 32, NULL, 'product_updated', 'products', 123, NULL, NULL, '10.30.78.134', '2026-09-21T16:01:52', '2026-09-21T16:01:52'),
(677, 32, NULL, 'product_updated', 'products', 122, NULL, NULL, '10.30.78.134', '2026-09-21T16:04:29', '2026-09-21T16:04:29'),
(678, 32, NULL, 'product_updated', 'products', 121, NULL, NULL, '10.27.104.133', '2026-09-21T16:05:56', '2026-09-21T16:05:56'),
(679, 32, NULL, 'product_updated', 'products', 120, NULL, NULL, '10.27.104.133', '2026-09-21T16:13:03', '2026-09-21T16:13:03'),
(680, 32, NULL, 'product_updated', 'products', 119, NULL, NULL, '10.25.162.97', '2026-09-21T16:14:22', '2026-09-21T16:14:22'),
(681, 32, NULL, 'product_updated', 'products', 118, NULL, NULL, '10.27.104.133', '2026-09-21T16:17:56', '2026-09-21T16:17:56'),
(682, 32, NULL, 'product_updated', 'products', 117, NULL, NULL, '10.25.162.97', '2026-09-21T16:19:16', '2026-09-21T16:19:16'),
(683, 32, NULL, 'product_updated', 'products', 117, NULL, NULL, '10.25.162.97', '2026-09-21T16:19:57', '2026-09-21T16:19:57'),
(684, 32, NULL, 'product_updated', 'products', 117, NULL, NULL, '10.27.104.133', '2026-09-21T16:20:47', '2026-09-21T16:20:47'),
(685, 32, NULL, 'product_updated', 'products', 117, NULL, NULL, '10.25.162.97', '2026-09-21T16:22:01', '2026-09-21T16:22:01'),
(686, 32, NULL, 'product_updated', 'products', 114, NULL, NULL, '10.25.162.97', '2026-09-21T16:24:19', '2026-09-21T16:24:19'),
(687, 32, NULL, 'product_updated', 'products', 117, NULL, NULL, '10.25.162.97', '2026-09-21T16:25:20', '2026-09-21T16:25:20'),
(688, 32, NULL, 'product_updated', 'products', 117, NULL, NULL, '10.30.78.134', '2026-09-21T16:27:00', '2026-09-21T16:27:00'),
(689, 32, NULL, 'product_deactivated', 'products', 117, '{"is_active":true}', '{"is_active":false}', '10.30.78.134', '2026-09-21T16:27:49', '2026-09-21T16:27:49'),
(690, 32, NULL, 'product_updated', 'products', 346, NULL, NULL, '10.30.78.134', '2026-09-21T16:31:33', '2026-09-21T16:31:33'),
(691, 32, NULL, 'product_updated', 'products', 110, NULL, NULL, '10.25.162.97', '2026-09-21T16:32:59', '2026-09-21T16:32:59'),
(692, 32, NULL, 'product_updated', 'products', 346, NULL, NULL, '10.27.104.133', '2026-09-21T16:35:07', '2026-09-21T16:35:07'),
(693, 32, NULL, 'product_updated', 'products', 346, NULL, NULL, '10.30.78.134', '2026-09-21T16:36:29', '2026-09-21T16:36:29'),
(694, 32, NULL, 'product_updated', 'products', 346, NULL, NULL, '10.27.104.133', '2026-09-21T16:37:44', '2026-09-21T16:37:44'),
(695, 32, NULL, 'product_updated', 'products', 116, NULL, NULL, '10.27.104.133', '2026-09-21T16:39:22', '2026-09-21T16:39:22'),
(696, 32, NULL, 'product_updated', 'products', 115, NULL, NULL, '10.25.162.97', '2026-09-21T16:42:31', '2026-09-21T16:42:31'),
(697, 32, NULL, 'product_deactivated', 'products', 113, '{"is_active":true}', '{"is_active":false}', '10.25.162.97', '2026-09-21T16:46:18', '2026-09-21T16:46:18'),
(698, 32, NULL, 'product_updated', 'products', 112, NULL, NULL, '10.27.104.133', '2026-09-21T16:48:16', '2026-09-21T16:48:16'),
(699, 32, NULL, 'product_updated', 'products', 111, NULL, NULL, '10.30.78.134', '2026-09-21T16:49:44', '2026-09-21T16:49:44'),
(700, 32, NULL, 'product_updated', 'products', 109, NULL, NULL, '10.30.78.134', '2026-09-21T16:52:04', '2026-09-21T16:52:04'),
(701, 32, NULL, 'product_updated', 'products', 107, NULL, NULL, '10.30.78.134', '2026-09-21T16:53:34', '2026-09-21T16:53:34'),
(702, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T02:43:22', '2026-09-22T02:43:22'),
(703, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T02:56:00', '2026-09-22T02:56:00'),
(704, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T02:56:53', '2026-09-22T02:56:53'),
(705, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T03:00:30', '2026-09-22T03:00:30'),
(706, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T06:02:42', '2026-09-22T06:02:42'),
(707, 36, NULL, 'transfer_item_received', NULL, NULL, NULL, NULL, '10.29.121.239', '2026-09-22T06:03:10', '2026-09-22T06:03:10'),
(708, 37, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T06:05:04', '2026-09-22T06:05:04'),
(709, 37, NULL, 'transfer_item_received', NULL, NULL, NULL, NULL, '10.29.121.239', '2026-09-22T06:05:35', '2026-09-22T06:05:35'),
(710, 42, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T06:35:08', '2026-09-22T06:35:08'),
(711, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T06:39:40', '2026-09-22T06:39:40'),
(712, 37, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T07:03:56', '2026-09-22T07:03:56'),
(713, 37, NULL, 'transfer_item_received', NULL, NULL, NULL, NULL, '10.30.63.139', '2026-09-22T07:04:33', '2026-09-22T07:04:33'),
(714, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T07:45:51', '2026-09-22T07:45:51'),
(715, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T07:47:35', '2026-09-22T07:47:35'),
(716, 32, NULL, 'transfer_created', NULL, NULL, NULL, NULL, '10.31.236.254', '2026-09-22T07:48:24', '2026-09-22T07:48:24'),
(717, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T07:49:37', '2026-09-22T07:49:37'),
(718, 36, NULL, 'transfer_item_received', NULL, NULL, NULL, NULL, '10.30.63.139', '2026-09-22T07:50:02', '2026-09-22T07:50:02'),
(719, 36, NULL, 'transfer_item_returned', NULL, NULL, NULL, NULL, '10.30.63.139', '2026-09-22T07:52:18', '2026-09-22T07:52:18'),
(720, 37, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T07:59:06', '2026-09-22T07:59:06'),
(721, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T08:05:10', '2026-09-22T08:05:10'),
(722, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T08:06:30', '2026-09-22T08:06:30'),
(723, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T08:15:15', '2026-09-22T08:15:15'),
(724, 32, NULL, 'transfer_created', NULL, NULL, NULL, NULL, '10.30.63.139', '2026-09-22T08:17:29', '2026-09-22T08:17:29'),
(725, 32, NULL, 'transfer_created', NULL, NULL, NULL, NULL, '10.29.121.239', '2026-09-22T08:20:59', '2026-09-22T08:20:59'),
(726, 32, NULL, 'transfer_item_resent', NULL, NULL, NULL, NULL, '10.31.236.254', '2026-09-22T08:21:54', '2026-09-22T08:21:54'),
(727, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T08:27:07', '2026-09-22T08:27:07'),
(728, 36, NULL, 'transfer_item_received', NULL, NULL, NULL, NULL, '10.31.236.254', '2026-09-22T08:28:27', '2026-09-22T08:28:27'),
(729, 36, NULL, 'transfer_item_received', NULL, NULL, NULL, NULL, '10.30.63.139', '2026-09-22T08:28:51', '2026-09-22T08:28:51'),
(730, 36, NULL, 'transfer_item_received', NULL, NULL, NULL, NULL, '10.30.63.139', '2026-09-22T08:29:02', '2026-09-22T08:29:02'),
(731, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T08:33:47', '2026-09-22T08:33:47'),
(732, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T08:34:26', '2026-09-22T08:34:26'),
(733, 32, NULL, 'transfer_created', NULL, NULL, NULL, NULL, '10.30.63.139', '2026-09-22T08:50:38', '2026-09-22T08:50:38'),
(734, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T08:53:12', '2026-09-22T08:53:12'),
(735, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T08:54:06', '2026-09-22T08:54:06'),
(736, 36, NULL, 'transfer_item_received', NULL, NULL, NULL, NULL, '10.31.236.254', '2026-09-22T08:55:45', '2026-09-22T08:55:45'),
(737, 36, NULL, 'bottle_accessories_stock_deleted', 'bottle_accessories', 22, '{"type":"straws","color":"silver","quantity":3}', NULL, '10.30.63.139', '2026-09-22T08:57:28', '2026-09-22T08:57:28'),
(738, 22, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T09:00:12', '2026-09-22T09:00:12'),
(739, 44, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T09:03:28', '2026-09-22T09:03:28'),
(740, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T09:05:15', '2026-09-22T09:05:15'),
(741, 21, NULL, 'created_user', 'users', 47, NULL, '{"status":"active"}', '10.31.236.254', '2026-09-22T09:08:12', '2026-09-22T09:08:12'),
(742, 47, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T09:08:57', '2026-09-22T09:08:57'),
(743, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T09:35:23', '2026-09-22T09:35:23'),
(744, 32, NULL, 'product_updated', 'products', 346, NULL, NULL, '10.30.63.139', '2026-09-22T09:37:11', '2026-09-22T09:37:11'),
(745, 38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T15:53:22', '2026-09-22T15:53:22'),
(746, 38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-22T15:54:30', '2026-09-22T15:54:30'),
(747, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-23T08:56:30', '2026-09-23T08:56:30'),
(748, 32, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-23T09:09:35', '2026-09-23T09:09:35'),
(749, 1, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-23T12:42:08', '2026-09-23T12:42:08'),
(750, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-23T12:42:32', '2026-09-23T12:42:32'),
(751, 32, NULL, 'transfer_created', NULL, NULL, NULL, NULL, '10.28.122.132', '2026-09-23T12:45:19', '2026-09-23T12:45:19');
INSERT INTO public.audit_logs (id, user_id, branch_id, action, auditable_type, auditable_id, old_values, new_values, ip_address, created_at, updated_at) VALUES
(752, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-23T12:47:57', '2026-09-23T12:47:57'),
(753, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-23T12:59:20', '2026-09-23T12:59:20'),
(754, 38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-23T13:16:27', '2026-09-23T13:16:27'),
(755, 38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-23T14:26:31', '2026-09-23T14:26:31'),
(756, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-23T15:30:01', '2026-09-23T15:30:01'),
(757, 32, NULL, 'transfer_created', NULL, NULL, NULL, NULL, '10.29.235.113', '2026-09-23T15:32:37', '2026-09-23T15:32:37'),
(758, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-23T15:33:16', '2026-09-23T15:33:16'),
(759, 36, NULL, 'transfer_item_received', NULL, NULL, NULL, NULL, '10.29.235.113', '2026-09-23T15:33:49', '2026-09-23T15:33:49'),
(760, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-24T06:40:26', '2026-09-24T06:40:26'),
(761, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-24T06:41:53', '2026-09-24T06:41:53'),
(762, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-24T06:41:55', '2026-09-24T06:41:55'),
(763, 36, NULL, 'transfer_item_returned', NULL, NULL, NULL, NULL, '10.31.28.2', '2026-09-24T06:43:32', '2026-09-24T06:43:32'),
(764, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-24T06:44:00', '2026-09-24T06:44:00'),
(765, 32, NULL, 'transfer_item_damage_report', NULL, NULL, NULL, NULL, '10.31.28.2', '2026-09-24T06:45:55', '2026-09-24T06:45:55'),
(766, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-24T06:54:52', '2026-09-24T06:54:52'),
(767, 32, NULL, 'product_updated', 'products', 346, NULL, NULL, '10.28.122.132', '2026-09-24T07:00:28', '2026-09-24T07:00:28'),
(768, 32, NULL, 'product_updated', 'products', 346, NULL, NULL, '10.28.122.132', '2026-09-24T07:01:44', '2026-09-24T07:01:44'),
(769, 32, NULL, 'product_updated', 'products', 346, NULL, NULL, '10.29.235.113', '2026-09-24T07:25:33', '2026-09-24T07:25:33'),
(770, 32, NULL, 'product_updated', 'products', 346, NULL, NULL, '10.31.28.2', '2026-09-24T07:27:35', '2026-09-24T07:27:35'),
(771, 32, NULL, 'product_updated', 'products', 346, NULL, NULL, '10.29.235.113', '2026-09-24T07:28:58', '2026-09-24T07:28:58'),
(772, 32, NULL, 'product_updated', 'products', 346, NULL, NULL, '10.29.235.113', '2026-09-24T07:34:02', '2026-09-24T07:34:02'),
(773, 32, NULL, 'product_deactivated', 'products', 283, '{"is_active":true}', '{"is_active":false}', '10.31.28.2', '2026-09-24T07:41:50', '2026-09-24T07:41:50'),
(774, 32, NULL, 'product_deactivated', 'products', 163, '{"is_active":true}', '{"is_active":false}', '10.29.235.113', '2026-09-24T07:44:31', '2026-09-24T07:44:31'),
(775, 32, NULL, 'product_updated', 'products', 158, NULL, NULL, '10.31.28.2', '2026-09-24T08:18:55', '2026-09-24T08:18:55'),
(776, 32, NULL, 'product_deactivated', 'products', 161, '{"is_active":true}', '{"is_active":false}', '10.29.235.113', '2026-09-24T08:20:28', '2026-09-24T08:20:28'),
(777, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-24T08:24:48', '2026-09-24T08:24:48'),
(778, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-24T08:55:28', '2026-09-24T08:55:28'),
(779, 32, NULL, 'stock_record_deleted', 'branch_stock', 72, '{"quantity":120,"selling_price":37000}', NULL, '10.29.235.113', '2026-09-24T08:56:04', '2026-09-24T08:56:04'),
(780, 32, NULL, 'stock_record_deleted', 'branch_stock', 69, '{"quantity":0,"selling_price":35000}', NULL, '10.29.235.113', '2026-09-24T08:56:13', '2026-09-24T08:56:13'),
(781, 32, NULL, 'stock_record_deleted', 'branch_stock', 68, '{"quantity":6,"selling_price":7000}', NULL, '10.29.235.113', '2026-09-24T08:56:22', '2026-09-24T08:56:22'),
(782, 32, NULL, 'stock_record_deleted', 'branch_stock', 64, '{"quantity":24,"selling_price":45000}', NULL, '10.29.235.113', '2026-09-24T08:57:02', '2026-09-24T08:57:02'),
(783, 32, NULL, 'stock_record_deleted', 'branch_stock', 67, '{"quantity":6,"selling_price":35000}', NULL, '10.29.235.113', '2026-09-24T08:57:09', '2026-09-24T08:57:09'),
(784, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-24T09:30:46', '2026-09-24T09:30:46'),
(785, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-24T14:32:54', '2026-09-24T14:32:54'),
(786, 38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-24T14:39:53', '2026-09-24T14:39:53'),
(787, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-24T15:49:53', '2026-09-24T15:49:53'),
(788, 38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-24T15:55:25', '2026-09-24T15:55:25'),
(789, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-24T16:39:40', '2026-09-24T16:39:40'),
(790, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-24T16:49:08', '2026-09-24T16:49:08'),
(791, 38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-24T17:05:03', '2026-09-24T17:05:03'),
(792, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-25T08:00:35', '2026-09-25T08:00:35'),
(793, 32, NULL, 'product_deactivated', 'products', 345, '{"is_active":true}', '{"is_active":false}', '10.31.28.2', '2026-09-25T08:02:20', '2026-09-25T08:02:20'),
(794, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-25T08:12:04', '2026-09-25T08:12:04'),
(795, 32, NULL, 'order_status_changed', 'orders', 27, '{"status":"pending"}', '{"status":"assigned"}', '10.31.28.2', '2026-09-25T09:05:42', '2026-09-25T09:05:42'),
(796, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-25T09:21:41', '2026-09-25T09:21:41'),
(797, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-25T10:53:41', '2026-09-25T10:53:41'),
(798, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-25T10:54:30', '2026-09-25T10:54:30'),
(799, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-25T12:46:42', '2026-09-25T12:46:42'),
(800, 32, NULL, 'product_updated', 'products', 56, NULL, NULL, '10.29.235.113', '2026-09-25T12:50:36', '2026-09-25T12:50:36'),
(801, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-25T15:35:23', '2026-09-25T15:35:23'),
(802, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-25T15:35:25', '2026-09-25T15:35:25'),
(803, 32, NULL, 'price_change_stock_in', 'branch_stock', 77, '{"selling_price":65000}', '{"selling_price":45000}', '10.28.122.132', '2026-09-25T16:03:32', '2026-09-25T16:03:32'),
(804, 32, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-25T16:24:33', '2026-09-25T16:24:33'),
(805, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-25T16:54:54', '2026-09-25T16:54:54'),
(806, 32, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-25T17:04:16', '2026-09-25T17:04:16'),
(807, 32, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-25T17:05:53', '2026-09-25T17:05:53'),
(808, 32, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-25T17:06:06', '2026-09-25T17:06:06'),
(809, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-26T10:41:06', '2026-09-26T10:41:06'),
(810, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-26T11:12:54', '2026-09-26T11:12:54'),
(811, 18, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-26T11:55:32', '2026-09-26T11:55:32'),
(812, 19, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-26T12:21:48', '2026-09-26T12:21:48'),
(813, 19, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-26T13:18:26', '2026-09-26T13:18:26'),
(814, 19, NULL, 'order_status_changed', 'orders', 28, '{"status":"pending"}', '{"status":"picked"}', '10.28.122.132', '2026-09-26T13:19:13', '2026-09-26T13:19:13'),
(815, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-26T13:21:36', '2026-09-26T13:21:36'),
(816, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-26T13:29:07', '2026-09-26T13:29:07'),
(817, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-26T13:39:52', '2026-09-26T13:39:52'),
(818, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-26T13:46:05', '2026-09-26T13:46:05'),
(819, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-26T13:50:05', '2026-09-26T13:50:05'),
(820, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-26T14:26:08', '2026-09-26T14:26:08'),
(821, 32, NULL, 'order_status_changed', 'orders', 27, '{"status":"picked"}', '{"status":"served"}', '10.29.235.113', '2026-09-26T14:33:35', '2026-09-26T14:33:35'),
(822, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-26T14:44:35', '2026-09-26T14:44:35'),
(823, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T08:30:34', '2026-09-28T08:30:34'),
(824, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T13:39:49', '2026-09-28T13:39:49'),
(825, 30, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T14:02:35', '2026-09-28T14:02:35'),
(826, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T14:09:05', '2026-09-28T14:09:05'),
(827, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T14:32:56', '2026-09-28T14:32:56'),
(828, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T14:57:30', '2026-09-28T14:57:30'),
(829, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T15:09:17', '2026-09-28T15:09:17'),
(830, 38, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T15:11:25', '2026-09-28T15:11:25'),
(831, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T15:51:33', '2026-09-28T15:51:33'),
(832, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T16:11:24', '2026-09-28T16:11:24'),
(833, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T17:09:08', '2026-09-28T17:09:08'),
(834, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T17:11:56', '2026-09-28T17:11:56'),
(835, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T20:32:15', '2026-09-28T20:32:15'),
(836, 29, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T21:13:14', '2026-09-28T21:13:14'),
(837, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T21:14:46', '2026-09-28T21:14:46'),
(838, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T22:15:54', '2026-09-28T22:15:54'),
(839, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-28T22:44:20', '2026-09-28T22:44:20'),
(840, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-29T08:44:51', '2026-09-29T08:44:51'),
(841, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-29T09:26:23', '2026-09-29T09:26:23'),
(842, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-29T09:36:38', '2026-09-29T09:36:38'),
(843, 32, NULL, 'price_change_stock_in', 'branch_stock', 77, '{"selling_price":45000}', '{"selling_price":65000}', '10.25.170.135', '2026-09-29T09:41:30', '2026-09-29T09:41:30'),
(844, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-29T09:42:03', '2026-09-29T09:42:03'),
(845, 32, NULL, 'order_status_changed', 'orders', 24, '{"status":"picked"}', '{"status":"served"}', '10.30.107.46', '2026-09-29T09:45:40', '2026-09-29T09:45:40'),
(846, 32, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-09-29T09:50:51', '2026-09-29T09:50:51'),
(847, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-29T11:18:51', '2026-09-29T11:18:51'),
(848, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-29T12:18:52', '2026-09-29T12:18:52'),
(849, 42, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-29T12:58:53', '2026-09-29T12:58:53'),
(850, 42, NULL, 'expense_created', NULL, NULL, NULL, NULL, NULL, '2026-09-29T13:00:42', '2026-09-29T13:00:42'),
(851, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-29T13:36:23', '2026-09-29T13:36:23'),
(852, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-29T14:12:33', '2026-09-29T14:12:33'),
(853, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-29T21:29:53', '2026-09-29T21:29:53'),
(854, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-29T21:55:51', '2026-09-29T21:55:51'),
(855, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-29T22:11:42', '2026-09-29T22:11:42'),
(856, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-30T07:43:57', '2026-09-30T07:43:57'),
(857, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-30T07:47:12', '2026-09-30T07:47:12'),
(858, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-30T16:05:39', '2026-09-30T16:05:39'),
(859, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-30T16:05:41', '2026-09-30T16:05:41'),
(860, 21, NULL, 'branch_deactivated', 'branches', 11, '{"is_active":true}', '{"is_active":false}', '10.30.107.46', '2026-09-30T16:10:05', '2026-09-30T16:10:05'),
(861, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-30T16:12:22', '2026-09-30T16:12:22'),
(862, 21, NULL, 'branch_deactivated', 'branches', 11, '{"is_active":true}', '{"is_active":false}', '10.30.107.46', '2026-09-30T16:13:19', '2026-09-30T16:13:19'),
(863, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-30T16:25:07', '2026-09-30T16:25:07'),
(864, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-30T16:47:36', '2026-09-30T16:47:36'),
(865, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-09-30T17:01:32', '2026-09-30T17:01:32'),
(866, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-10-01T09:59:10', '2026-10-01T09:59:10'),
(867, 32, NULL, 'price_change_stock_in', 'branch_stock', 77, '{"selling_price":65000}', '{"selling_price":45000}', '10.26.159.23', '2026-10-01T10:29:41', '2026-10-01T10:29:41'),
(868, 32, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-10-01T10:44:32', '2026-10-01T10:44:32'),
(869, 43, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-10-01T13:37:55', '2026-10-01T13:37:55'),
(870, 43, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-10-01T13:38:35', '2026-10-01T13:38:35'),
(871, 44, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-10-01T13:40:59', '2026-10-01T13:40:59'),
(872, 36, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-10-01T14:06:34', '2026-10-01T14:06:34'),
(873, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-10-01T14:10:30', '2026-10-01T14:10:30'),
(874, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-10-02T07:53:15', '2026-10-02T07:53:15'),
(875, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-10-02T08:29:19', '2026-10-02T08:29:19'),
(876, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-10-02T13:11:47', '2026-10-02T13:11:47'),
(877, 39, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-10-02T18:11:31', '2026-10-02T18:11:31'),
(878, 21, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-10-02T18:25:54', '2026-10-02T18:25:54'),
(879, 32, NULL, 'login', NULL, NULL, NULL, NULL, NULL, '2026-10-02T18:50:31', '2026-10-02T18:50:31'),
(880, 32, NULL, 'sale_created_by_stock_manager', NULL, NULL, NULL, NULL, NULL, '2026-10-02T18:51:18', '2026-10-02T18:51:18');

-- notifications (6 rows)
INSERT INTO public.notifications (id, user_id, type, data, read_at, created_at, updated_at) VALUES
(1, 2, 'cashier_registration', '{"message": "New cashier awaiting approval."}', NULL, '2026-08-20T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(2, 3, 'cashier_registration', '{"message": "New cashier awaiting approval."}', NULL, '2026-08-21T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(3, 2, 'new_order', '{"message": "New order ORD-000002 placed. Total: 350,000."}', NULL, '2026-08-20T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(4, 7, 'order_assigned', '{"message": "Order ORD-000003 assigned to you."}', NULL, '2026-08-21T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(5, 2, 'low_stock', '{"message": "Tom Ford Oud Wood running low (7 remaining)."}', NULL, '2026-08-19T05:23:02.148567', '2026-08-22T05:23:02.148567'),
(6, 2, 'discrepancy_alert', '{"message": "Cashier reported loss of 5,000."}', NULL, '2026-08-20T05:23:02.148567', '2026-08-22T05:23:02.148567');

-- admin_notifications (359 rows)
INSERT INTO public.admin_notifications (id, type, branch_id, user_id, title, message, data, is_read, created_at, updated_at) VALUES
(359, 'branch_deactivated', NULL, 21, 'Branch Deactivated', 'Branch Kinondoni branch was deactivated.', '{"branch_id":"11","branch_name":"Kinondoni branch"}', FALSE, '2026-09-30T16:10:05+00:00', '2026-09-30T16:10:05+00:00'),
(360, 'branch_deactivated', NULL, 21, 'Branch Deactivated', 'Branch Kinondoni branch was deactivated.', '{"branch_id":"11","branch_name":"Kinondoni branch"}', FALSE, '2026-09-30T16:13:19+00:00', '2026-09-30T16:13:19+00:00'),
(3, 'discount_used', 8, 29, 'Discount Applied', 'A discount was applied in sale SALE-20260908233730-06D2 (Stock Manager).', '{"sale_id":57,"sale_number":"SALE-20260908233730-06D2"}', TRUE, '2026-09-08T23:37:32+00:00', '2026-09-10T04:10:55+00:00'),
(4, 'discount_used', 8, 29, 'Discount Applied', 'A discount was applied in sale SALE-20260908234155-EA3C (Stock Manager).', '{"sale_id":59,"sale_number":"SALE-20260908234155-EA3C"}', TRUE, '2026-09-08T23:41:57+00:00', '2026-09-10T04:10:55+00:00'),
(5, 'discount_used', 8, 29, 'Discount Applied', 'A discount was applied in sale SALE-20260909001052-5CE7 (Stock Manager).', '{"sale_id":60,"sale_number":"SALE-20260909001052-5CE7"}', TRUE, '2026-09-09T00:10:55+00:00', '2026-09-10T04:10:56+00:00'),
(6, 'staff_blocked', NULL, 1, 'Staff Blocked', 'Staff Gideon Msuya was blocked.', '{"user_id":"31","user_name":"Gideon Msuya","status":"blocked"}', TRUE, '2026-09-09T00:29:55+00:00', '2026-09-10T04:10:56+00:00'),
(7, 'staff_blocked', NULL, 1, 'Staff Blocked', 'Staff FRANK GODWIN was blocked.', '{"user_id":"30","user_name":"FRANK GODWIN","status":"blocked"}', TRUE, '2026-09-09T00:32:42+00:00', '2026-09-10T04:10:56+00:00'),
(8, 'staff_status_changed', NULL, 1, 'Staff Status Changed', 'Staff COSMA COSMA VICTORINI status changed from active to pending.', '{"user_id":"20","user_name":"COSMA COSMA VICTORINI","before":"active","after":"pending"}', TRUE, '2026-09-10T04:13:12+00:00', '2026-09-10T04:14:44+00:00'),
(9, 'staff_status_changed', NULL, 1, 'Staff Status Changed', 'Staff COSMA COSMA VICTORINI status changed from pending to active.', '{"user_id":"20","user_name":"COSMA COSMA VICTORINI","before":"pending","after":"active"}', TRUE, '2026-09-10T04:13:31+00:00', '2026-09-10T04:14:44+00:00'),
(217, 'order_status_changed', 8, 32, 'Order Status Changed', 'Order ORD-20260918122049-6753 status changed to ready.', '{"order_id":"26","order_number":"ORD-20260918122049-6753","total":45000}', TRUE, '2026-09-21T10:10:09+00:00', '2026-09-29T14:32:41+00:00'),
(11, 'stock_deleted', 8, 32, 'Stock Record Deleted', 'Stock record deleted for product #140 (376 units @ 45,000 TZS).', '{"branch_stock_id":"62","product_id":140,"quantity":376,"selling_price":44999.91}', TRUE, '2026-09-10T06:37:45+00:00', '2026-09-10T09:34:41+00:00'),
(10, 'discount_used', 8, 32, 'Discount Applied', 'Discount applied in sale SALE-20260910063324-6FCB (Stock Manager). Club De Nuit Overdose: 45,000 -> 5,000. Total: 45,000 -> 5,000.', '{"sale_id":61,"sale_number":"SALE-20260910063324-6FCB","items":[{"product_id":"140","product_name":"Club De Nuit Overdose","quantity":1,"original_price":44999.91,"discount_price":5000}],"original_total":44999.91,"discounted_total":5000}', TRUE, '2026-09-10T06:33:27+00:00', '2026-09-10T09:34:47+00:00'),
(218, 'order_status_changed', 8, 32, 'Order Status Changed', 'Order ORD-20260918122049-6753 status changed to completed.', '{"order_id":"26","order_number":"ORD-20260918122049-6753","total":45000}', TRUE, '2026-09-21T10:10:59+00:00', '2026-09-29T14:32:42+00:00'),
(219, 'order_status_changed', 8, 32, 'Order Status Changed', 'Order ORD-20260918122049-6753 status changed to served.', '{"order_id":"26","order_number":"ORD-20260918122049-6753","total":45000}', TRUE, '2026-09-21T10:11:17+00:00', '2026-09-29T14:32:42+00:00'),
(220, 'order_status_changed', 8, 32, 'Order Status Changed', 'Order ORD-20260916163432-D5FA status changed to assigned.', '{"order_id":"24","order_number":"ORD-20260916163432-D5FA","total":90000}', TRUE, '2026-09-21T10:16:09+00:00', '2026-09-29T14:32:42+00:00'),
(221, 'order_status_changed', 8, 32, 'Order Status Changed', 'Order ORD-20260915140656-7802 status changed to cancelled.', '{"order_id":"22","order_number":"ORD-20260915140656-7802","total":45000}', TRUE, '2026-09-21T10:19:58+00:00', '2026-09-29T14:32:42+00:00'),
(232, 'product_updated', 8, 32, 'Product Updated', 'Product Second Song Angham was updated (id #181).', '{"product_id":"181","product_name":"Second Song Angham","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:43:55+00:00', '2026-09-29T14:32:43+00:00'),
(233, 'product_updated', 8, 32, 'Product Updated', 'Product Dubai night Midnight was updated (id #180).', '{"product_id":"180","product_name":"Dubai night Midnight","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:48:51+00:00', '2026-09-29T14:32:43+00:00'),
(318, 'stock_transfer', 9, 36, 'Stock item verified & received', 'Bottle Stock ''12ml â€” No details'' (qty 12) verified and added to Dodoma branch from transfer TF-20260922074823-2AAE', NULL, TRUE, '2026-09-22T07:50:02+00:00', '2026-09-29T14:32:43+00:00'),
(12, 'stock_deleted', 8, 29, 'Stock Record Deleted', 'Stock record deleted for product #210 (60 units @ 67,000 TZS).', '{"branch_stock_id":"63","product_id":210,"quantity":60,"selling_price":67000}', TRUE, '2026-09-11T19:22:46+00:00', '2026-09-13T20:43:40+00:00'),
(13, 'order_status_changed', 8, 34, 'Order Status Changed', 'Order ORD-20260907203417-57E5 status changed to assigned.', '{"order_id":"12","order_number":"ORD-20260907203417-57E5","total":54000}', TRUE, '2026-09-11T20:35:13+00:00', '2026-09-13T20:43:40+00:00'),
(14, 'order_status_changed', 8, 34, 'Order Status Changed', 'Order ORD-20260907203417-57E5 status changed to ready.', '{"order_id":"12","order_number":"ORD-20260907203417-57E5","total":54000}', TRUE, '2026-09-11T20:35:28+00:00', '2026-09-13T20:43:41+00:00'),
(15, 'order_status_changed', 8, 34, 'Order Status Changed', 'Order ORD-20260907203417-57E5 status changed to completed.', '{"order_id":"12","order_number":"ORD-20260907203417-57E5","total":54000}', TRUE, '2026-09-11T20:35:43+00:00', '2026-09-13T20:43:41+00:00'),
(16, 'order_status_changed', 8, 32, 'Order Status Changed', 'Order ORD-000008 status changed to served.', '{"order_id":"8","order_number":"ORD-000008","total":372000}', TRUE, '2026-09-11T21:52:23+00:00', '2026-09-13T20:43:41+00:00'),
(17, 'order_status_changed', 8, 32, 'Order Status Changed', 'Order ORD-20260908102445-0B57 status changed to assigned.', '{"order_id":"13","order_number":"ORD-20260908102445-0B57","total":50000}', TRUE, '2026-09-11T21:52:57+00:00', '2026-09-13T20:43:41+00:00'),
(18, 'order_status_changed', 8, 32, 'Order Status Changed', 'Order ORD-20260908103217-B505 status changed to assigned.', '{"order_id":"14","order_number":"ORD-20260908103217-B505","total":50000}', TRUE, '2026-09-11T21:53:15+00:00', '2026-09-13T20:43:41+00:00'),
(19, 'order_status_changed', 8, 32, 'Order Status Changed', 'Order ORD-20260908103644-56B5 status changed to assigned.', '{"order_id":"15","order_number":"ORD-20260908103644-56B5","total":50000}', TRUE, '2026-09-11T21:53:41+00:00', '2026-09-13T20:43:41+00:00'),
(20, 'order_status_changed', 8, 31, 'Order Status Changed', 'Order ORD-20260908103904-26C7 status changed to assigned.', '{"order_id":"16","order_number":"ORD-20260908103904-26C7","total":1000}', TRUE, '2026-09-11T22:00:29+00:00', '2026-09-13T20:43:42+00:00'),
(21, 'order_status_changed', 8, 30, 'Order Status Changed', 'Order ORD-20260908110134-15B3 status changed to assigned.', '{"order_id":"17","order_number":"ORD-20260908110134-15B3","total":1000}', TRUE, '2026-09-11T22:01:24+00:00', '2026-09-13T20:43:42+00:00'),
(22, 'order_status_changed', 8, 31, 'Order Status Changed', 'Order ORD-20260908110134-15B3 status changed to ready.', '{"order_id":"17","order_number":"ORD-20260908110134-15B3","total":1000}', TRUE, '2026-09-11T22:03:27+00:00', '2026-09-13T20:43:42+00:00'),
(361, 'price_customization', 8, 32, 'Price Changed', 'Selling price changed for product #45 during stock-in: 65,000 â†’ 45,000 TZS.', '{"branch_stock_id":77,"product_id":"45","old_price":65000,"new_price":45000}', FALSE, '2026-10-01T10:29:41+00:00', '2026-10-01T10:29:41+00:00'),
(25, 'order_status_changed', 8, 31, 'Order Status Changed', 'Order ORD-20260908111039-7CD1 status changed to assigned.', '{"order_id":"18","order_number":"ORD-20260908111039-7CD1","total":24000}', TRUE, '2026-09-11T22:04:18+00:00', '2026-09-12T15:15:02+00:00'),
(26, 'order_status_changed', 8, 30, 'Order Status Changed', 'Order ORD-20260908232503-9948 status changed to assigned.', '{"order_id":"20","order_number":"ORD-20260908232503-9948","total":44999.91}', TRUE, '2026-09-12T19:01:53+00:00', '2026-09-13T20:06:35+00:00'),
(23, 'order_status_changed', 8, 31, 'Order Status Changed', 'Order ORD-20260908110134-15B3 status changed to completed.', '{"order_id":"17","order_number":"ORD-20260908110134-15B3","total":1000}', TRUE, '2026-09-11T22:03:34+00:00', '2026-09-13T20:43:42+00:00'),
(24, 'order_status_changed', 8, 31, 'Order Status Changed', 'Order ORD-20260908110134-15B3 status changed to served.', '{"order_id":"17","order_number":"ORD-20260908110134-15B3","total":1000}', TRUE, '2026-09-11T22:03:46+00:00', '2026-09-13T20:43:42+00:00'),
(27, 'order_status_changed', 8, 30, 'Order Status Changed', 'Order ORD-20260908232503-9948 status changed to ready.', '{"order_id":"20","order_number":"ORD-20260908232503-9948","total":44999.91}', TRUE, '2026-09-12T19:02:03+00:00', '2026-09-13T20:43:43+00:00'),
(28, 'order_status_changed', 8, 30, 'Order Status Changed', 'Order ORD-20260908232503-9948 status changed to completed.', '{"order_id":"20","order_number":"ORD-20260908232503-9948","total":44999.91}', TRUE, '2026-09-12T19:02:12+00:00', '2026-09-13T20:43:43+00:00'),
(29, 'order_status_changed', 8, 30, 'Order Status Changed', 'Order ORD-20260908111303-D76D status changed to assigned.', '{"order_id":"19","order_number":"ORD-20260908111303-D76D","total":24000}', TRUE, '2026-09-12T19:02:31+00:00', '2026-09-13T20:43:43+00:00'),
(30, 'stock_deleted', 8, 29, 'Bottle Accessories Stock Deleted', 'Bottle accessories stock record deleted (straws gold, 20 units).', '{"bottle_accessories_id":"14","type":"straws","color":"gold","quantity":20}', TRUE, '2026-09-12T21:53:21+00:00', '2026-09-13T20:43:43+00:00'),
(31, 'stock_deleted', 8, 29, 'Bottle Accessories Stock Deleted', 'Bottle accessories stock record deleted (bottle_tops gold, 20 units).', '{"bottle_accessories_id":"13","type":"bottle_tops","color":"gold","quantity":20}', TRUE, '2026-09-12T21:53:29+00:00', '2026-09-13T20:43:43+00:00'),
(32, 'stock_adjusted', 8, 29, 'Stock Adjusted', 'Manual stock adjustment for product #99: 40 â†’ 51 units.', '{"branch_stock_id":"64","product_id":99,"old_qty":40,"new_qty":"51"}', TRUE, '2026-09-13T06:33:00+00:00', '2026-09-13T20:43:43+00:00'),
(33, 'discount_used', 8, 29, 'Discount Applied', 'Discount applied in sale SALE-20260913075345-EE26 (Stock Manager). Club De Nuit x3: 45,000 -> 50,000. Total: 135,000 -> 150,000.', '{"sale_id":62,"sale_number":"SALE-20260913075345-EE26","items":[{"product_id":"99","product_name":"Club De Nuit","quantity":3,"original_price":45000,"discount_price":50000}],"original_total":135000,"discounted_total":150000}', TRUE, '2026-09-13T07:53:47+00:00', '2026-09-13T20:43:44+00:00'),
(34, 'order_status_changed', 8, 29, 'Order Status Changed', 'Order ORD-20260913062623-D420 status changed to assigned.', '{"order_id":"21","order_number":"ORD-20260913062623-D420","total":45000}', TRUE, '2026-09-13T10:36:41+00:00', '2026-09-13T20:43:44+00:00'),
(35, 'order_status_changed', 8, 29, 'Order Status Changed', 'Order ORD-20260913062623-D420 status changed to ready.', '{"order_id":"21","order_number":"ORD-20260913062623-D420","total":45000}', TRUE, '2026-09-13T10:36:46+00:00', '2026-09-13T20:43:44+00:00'),
(36, 'order_status_changed', 8, 29, 'Order Status Changed', 'Order ORD-20260913062623-D420 status changed to completed.', '{"order_id":"21","order_number":"ORD-20260913062623-D420","total":45000}', TRUE, '2026-09-13T10:36:51+00:00', '2026-09-13T20:43:44+00:00'),
(37, 'stock_adjusted', 8, 29, 'Stock Adjusted', 'Manual stock adjustment for product #99: 35 â†’ 34 units.', '{"branch_stock_id":"64","product_id":99,"old_qty":35,"new_qty":"34"}', TRUE, '2026-09-13T10:44:48+00:00', '2026-09-13T20:43:44+00:00'),
(38, 'stock_adjusted', 8, 29, 'Stock Adjusted', 'Manual stock adjustment for product #99: 34 â†’ 35 units.', '{"branch_stock_id":"64","product_id":99,"old_qty":34,"new_qty":"35"}', TRUE, '2026-09-13T10:46:11+00:00', '2026-09-13T20:43:44+00:00'),
(39, 'stock_adjusted', 8, 29, 'Stock Adjusted', 'Manual stock adjustment for product #99: 35 â†’ 34 units.', '{"branch_stock_id":"64","product_id":99,"old_qty":35,"new_qty":"34"}', TRUE, '2026-09-13T11:34:24+00:00', '2026-09-13T20:43:45+00:00'),
(40, 'stock_adjusted', 8, 29, 'Stock Adjusted', 'Manual stock adjustment for product #99: 34 â†’ 30 units.', '{"branch_stock_id":"64","product_id":99,"old_qty":34,"new_qty":"30"}', TRUE, '2026-09-13T11:50:00+00:00', '2026-09-13T20:43:45+00:00'),
(41, 'stock_deleted', 8, 29, 'Bottle Accessories Stock Deleted', 'Bottle accessories stock record deleted (straws gold, 10 units).', '{"bottle_accessories_id":"15","type":"straws","color":"gold","quantity":10}', TRUE, '2026-09-13T12:27:15+00:00', '2026-09-13T20:43:45+00:00'),
(43, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 100ml, 30 units).', '{"bottle_stock_id":"27","volume":"100ml","quantity":30}', TRUE, '2026-09-14T10:49:09+00:00', '2026-09-21T09:20:23+00:00'),
(44, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 100ml, 60 units).', '{"bottle_stock_id":"28","volume":"100ml","quantity":60}', TRUE, '2026-09-14T10:49:19+00:00', '2026-09-21T09:20:24+00:00'),
(45, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 12ml, 56 units).', '{"bottle_stock_id":"25","volume":"12ml","quantity":56}', TRUE, '2026-09-14T10:49:28+00:00', '2026-09-21T09:20:24+00:00'),
(46, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 30ml, 71 units).', '{"bottle_stock_id":"23","volume":"30ml","quantity":71}', TRUE, '2026-09-14T10:49:35+00:00', '2026-09-21T09:20:24+00:00'),
(48, 'stock_deleted', 8, 32, 'Oil Fragrance Stock Deleted', 'Oil fragrance stock record deleted (name My Way, 99 units).', '{"oil_fragrance_stock_id":"1","name":"My Way","quantity":99}', TRUE, '2026-09-14T11:11:42+00:00', '2026-09-21T09:20:24+00:00'),
(49, 'stock_deleted', 8, 32, 'Oil Fragrance Stock Deleted', 'Oil fragrance stock record deleted (name Emarude Super, 2 units).', '{"oil_fragrance_stock_id":"12","name":"Emarude Super","quantity":2}', TRUE, '2026-09-14T13:43:18+00:00', '2026-09-21T09:20:25+00:00'),
(50, 'stock_deleted', 8, 32, 'Oil Fragrance Stock Deleted', 'Oil fragrance stock record deleted (name Emarude Super, 2 units).', '{"oil_fragrance_stock_id":"13","name":"Emarude Super","quantity":2}', TRUE, '2026-09-14T13:44:44+00:00', '2026-09-21T09:20:25+00:00'),
(51, 'product_updated', 8, 32, 'Product Updated', 'Product Barcode Signature was updated (id #294).', '{"product_id":"294","product_name":"Barcode Signature","validated":["name","description","brand","category","sex_category","is_active","updated_at"]}', TRUE, '2026-09-15T15:51:26+00:00', '2026-09-21T09:20:25+00:00'),
(52, 'product_updated', 8, 32, 'Product Updated', 'Product Hugo Boss Bold Citrus men was updated (id #330).', '{"product_id":"330","product_name":"Hugo Boss Bold Citrus men","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-16T15:46:56+00:00', '2026-09-21T09:20:25+00:00'),
(204, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 50ml, 207 units).', '{"bottle_stock_id":"32","volume":"50ml","quantity":207}', TRUE, '2026-09-19T13:37:26+00:00', '2026-09-21T09:20:26+00:00'),
(342, 'product_deactivated', 8, 32, 'Product Deactivated', 'Product Sydney was deactivated (id #283).', '{"product_id":"283","product_name":"Sydney"}', TRUE, '2026-09-24T07:41:50+00:00', '2026-09-29T14:32:44+00:00'),
(54, 'order_status_changed', 10, 39, 'Order Status Changed', 'Order ORD-20260916162607-170D status changed to ready.', '{"order_id":"23","order_number":"ORD-20260916162607-170D","total":45000}', TRUE, '2026-09-16T16:36:26+00:00', '2026-09-21T09:20:26+00:00'),
(55, 'order_status_changed', 10, 39, 'Order Status Changed', 'Order ORD-20260916162607-170D status changed to completed.', '{"order_id":"23","order_number":"ORD-20260916162607-170D","total":45000}', TRUE, '2026-09-16T16:36:33+00:00', '2026-09-21T09:20:26+00:00'),
(56, 'product_updated', 8, 29, 'Product Updated', 'Product Reef 31 was updated (id #331).', '{"product_id":"331","product_name":"Reef 31","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T10:37:13+00:00', '2026-09-21T09:20:26+00:00'),
(57, 'product_updated', 8, 32, 'Product Updated', 'Product Reef 31 was updated (id #331).', '{"product_id":"331","product_name":"Reef 31","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T10:38:21+00:00', '2026-09-21T09:20:26+00:00'),
(59, 'stock_adjusted', 8, 32, 'Stock Adjusted', 'Manual stock adjustment for product #56: 5 â†’ 6 units.', '{"branch_stock_id":"67","product_id":56,"old_qty":5,"new_qty":"6"}', TRUE, '2026-09-17T12:49:14+00:00', '2026-09-21T09:20:27+00:00'),
(60, 'product_updated', 8, 32, 'Product Updated', 'Product SWISS ARABIAN ESSENCE OF CASABLANCA was updated (id #312).', '{"product_id":"312","product_name":"SWISS ARABIAN ESSENCE OF CASABLANCA","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:21:53+00:00', '2026-09-21T09:20:27+00:00'),
(61, 'product_deactivated', 8, 32, 'Product Deactivated', 'Product Reef 31 was deactivated (id #331).', '{"product_id":"331","product_name":"Reef 31"}', TRUE, '2026-09-17T13:24:46+00:00', '2026-09-21T09:20:27+00:00'),
(62, 'product_deactivated', 8, 32, 'Product Deactivated', 'Product Hugo Boss Bold Citrus men was deactivated (id #330).', '{"product_id":"330","product_name":"Hugo Boss Bold Citrus men"}', TRUE, '2026-09-17T13:25:26+00:00', '2026-09-21T09:20:28+00:00'),
(63, 'product_updated', 8, 32, 'Product Updated', 'Product SWISS ARABIAN PATCHOULI 01 was updated (id #311).', '{"product_id":"311","product_name":"SWISS ARABIAN PATCHOULI 01","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:25:56+00:00', '2026-09-21T09:20:28+00:00'),
(64, 'product_updated', 8, 32, 'Product Updated', 'Product SWISS ARABIAN Incense 01 was updated (id #310).', '{"product_id":"310","product_name":"SWISS ARABIAN Incense 01","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:26:25+00:00', '2026-09-21T09:20:28+00:00'),
(65, 'product_updated', 8, 32, 'Product Updated', 'Product SWISS ARABIAN ROSE 01 was updated (id #309).', '{"product_id":"309","product_name":"SWISS ARABIAN ROSE 01","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:26:58+00:00', '2026-09-21T09:20:28+00:00'),
(66, 'product_updated', 8, 32, 'Product Updated', 'Product SWISS ARABIAN OUD 01 was updated (id #308).', '{"product_id":"308","product_name":"SWISS ARABIAN OUD 01","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:28:00+00:00', '2026-09-21T09:20:28+00:00'),
(67, 'product_updated', 8, 32, 'Product Updated', 'Product SWISS ARABIAN VANILLA 01 was updated (id #307).', '{"product_id":"307","product_name":"SWISS ARABIAN VANILLA 01","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:28:25+00:00', '2026-09-21T09:20:29+00:00'),
(68, 'product_updated', 8, 32, 'Product Updated', 'Product SWISS ARABIAN MUSK 01 was updated (id #306).', '{"product_id":"306","product_name":"SWISS ARABIAN MUSK 01","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:29:30+00:00', '2026-09-21T09:20:29+00:00'),
(69, 'product_updated', 8, 32, 'Product Updated', 'Product SWISS ARABIAN SHANGAF OUD TONKA was updated (id #305).', '{"product_id":"305","product_name":"SWISS ARABIAN SHANGAF OUD TONKA","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:30:03+00:00', '2026-09-21T09:20:29+00:00'),
(70, 'product_deactivated', 8, 32, 'Product Deactivated', 'Product SWISS ARABIAN SHANGAF OUD TONKA was deactivated (id #305).', '{"product_id":"305","product_name":"SWISS ARABIAN SHANGAF OUD TONKA"}', TRUE, '2026-09-17T13:31:12+00:00', '2026-09-21T09:20:29+00:00'),
(71, 'product_updated', 8, 32, 'Product Updated', 'Product Room Spray was updated (id #304).', '{"product_id":"304","product_name":"Room Spray","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:34:56+00:00', '2026-09-21T09:20:29+00:00'),
(72, 'product_updated', 8, 32, 'Product Updated', 'Product Diffuser was updated (id #303).', '{"product_id":"303","product_name":"Diffuser","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:40:17+00:00', '2026-09-21T09:20:30+00:00'),
(73, 'product_deactivated', 8, 32, 'Product Deactivated', 'Product Diffuser was deactivated (id #303).', '{"product_id":"303","product_name":"Diffuser"}', TRUE, '2026-09-17T13:48:09+00:00', '2026-09-21T09:20:30+00:00'),
(74, 'product_updated', 8, 32, 'Product Updated', 'Product RAMZ lattafa gold was updated (id #295).', '{"product_id":"295","product_name":"RAMZ lattafa gold","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:49:23+00:00', '2026-09-21T09:20:30+00:00'),
(75, 'product_updated', 8, 32, 'Product Updated', 'Product Zodiac Solmaris was updated (id #299).', '{"product_id":"299","product_name":"Zodiac Solmaris","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:50:10+00:00', '2026-09-21T09:20:30+00:00'),
(76, 'product_updated', 8, 32, 'Product Updated', 'Product WAYFARES INFUSION was updated (id #298).', '{"product_id":"298","product_name":"WAYFARES INFUSION","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:51:02+00:00', '2026-09-21T09:20:30+00:00'),
(77, 'product_updated', 8, 32, 'Product Updated', 'Product Khashabi was updated (id #297).', '{"product_id":"297","product_name":"Khashabi","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:51:33+00:00', '2026-09-21T09:20:31+00:00'),
(78, 'product_updated', 8, 32, 'Product Updated', 'Product ASAD Elixir was updated (id #296).', '{"product_id":"296","product_name":"ASAD Elixir","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:52:11+00:00', '2026-09-21T09:20:31+00:00'),
(205, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 50ml, 170 units).', '{"bottle_stock_id":"33","volume":"50ml","quantity":170}', TRUE, '2026-09-19T13:37:35+00:00', '2026-09-21T09:20:31+00:00'),
(343, 'product_deactivated', 8, 32, 'Product Deactivated', 'Product Enchantment was deactivated (id #163).', '{"product_id":"163","product_name":"Enchantment"}', TRUE, '2026-09-24T07:44:31+00:00', '2026-09-29T14:32:44+00:00'),
(80, 'product_updated', 8, 32, 'Product Updated', 'Product Barcode Autograph was updated (id #293).', '{"product_id":"293","product_name":"Barcode Autograph","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:53:33+00:00', '2026-09-21T09:20:31+00:00'),
(81, 'product_deactivated', 8, 32, 'Product Deactivated', 'Product Diffuser was deactivated (id #333).', '{"product_id":"333","product_name":"Diffuser"}', TRUE, '2026-09-17T13:58:49+00:00', '2026-09-21T09:20:32+00:00'),
(82, 'product_updated', 8, 32, 'Product Updated', 'Product Diffuser was updated (id #334).', '{"product_id":"334","product_name":"Diffuser","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T14:03:59+00:00', '2026-09-21T09:20:32+00:00'),
(83, 'product_deactivated', 8, 32, 'Product Deactivated', 'Product Diffuser was deactivated (id #334).', '{"product_id":"334","product_name":"Diffuser"}', TRUE, '2026-09-17T14:04:46+00:00', '2026-09-21T09:20:32+00:00'),
(84, 'product_updated', 8, 32, 'Product Updated', 'Product SHALINA ROYAL ESSENCE was updated (id #291).', '{"product_id":"291","product_name":"SHALINA ROYAL ESSENCE","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T14:35:08+00:00', '2026-09-21T09:20:32+00:00'),
(85, 'product_updated', 8, 32, 'Product Updated', 'Product RAMZ lattafa silver was updated (id #292).', '{"product_id":"292","product_name":"RAMZ lattafa silver","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T14:52:08+00:00', '2026-09-21T09:20:32+00:00'),
(87, 'product_updated', 8, 32, 'Product Updated', 'Product Art Of Nature was updated (id #289).', '{"product_id":"289","product_name":"Art Of Nature","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T14:59:10+00:00', '2026-09-21T09:20:33+00:00'),
(88, 'product_updated', 8, 32, 'Product Updated', 'Product Liquid Brun was updated (id #288).', '{"product_id":"288","product_name":"Liquid Brun","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:01:55+00:00', '2026-09-21T09:20:33+00:00'),
(89, 'product_updated', 8, 32, 'Product Updated', 'Product BADE''E AL OUD AMETHYST was updated (id #287).', '{"product_id":"287","product_name":"BADE''E AL OUD AMETHYST","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:04:58+00:00', '2026-09-21T09:20:33+00:00'),
(90, 'product_updated', 8, 32, 'Product Updated', 'Product BADE''E AL OUD for glory was updated (id #286).', '{"product_id":"286","product_name":"BADE''E AL OUD for glory","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:06:17+00:00', '2026-09-21T09:20:34+00:00'),
(91, 'product_updated', 8, 32, 'Product Updated', 'Product BADE''E AL OUD honour and glory was updated (id #285).', '{"product_id":"285","product_name":"BADE''E AL OUD honour and glory","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:07:23+00:00', '2026-09-21T09:20:34+00:00'),
(92, 'product_updated', 8, 32, 'Product Updated', 'Product BADE''E AL OUD SUBLIME was updated (id #284).', '{"product_id":"284","product_name":"BADE''E AL OUD SUBLIME","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:08:46+00:00', '2026-09-21T09:20:34+00:00'),
(93, 'product_updated', 8, 32, 'Product Updated', 'Product Delila pour Femme was updated (id #282).', '{"product_id":"282","product_name":"Delila pour Femme","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:14:15+00:00', '2026-09-21T09:20:34+00:00'),
(94, 'product_updated', 8, 32, 'Product Updated', 'Product Khamrah was updated (id #281).', '{"product_id":"281","product_name":"Khamrah","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:15:47+00:00', '2026-09-21T09:20:35+00:00'),
(95, 'product_updated', 8, 32, 'Product Updated', 'Product Creme Of Clouds was updated (id #278).', '{"product_id":"278","product_name":"Creme Of Clouds","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:20:27+00:00', '2026-09-21T09:20:35+00:00'),
(96, 'product_updated', 8, 32, 'Product Updated', 'Product Taskeen was updated (id #277).', '{"product_id":"277","product_name":"Taskeen","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:23:00+00:00', '2026-09-21T09:20:35+00:00'),
(97, 'product_updated', 8, 32, 'Product Updated', 'Product Valentino Donna Born in Roma Intense was updated (id #108).', '{"product_id":"108","product_name":"Valentino Donna Born in Roma Intense","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:29:26+00:00', '2026-09-21T09:20:35+00:00'),
(98, 'product_updated', 8, 32, 'Product Updated', 'Product Valentino Donna was updated (id #276).', '{"product_id":"276","product_name":"Valentino Donna","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:31:06+00:00', '2026-09-21T09:20:36+00:00'),
(99, 'product_updated', 8, 32, 'Product Updated', 'Product Diffuser was updated (id #335).', '{"product_id":"335","product_name":"Diffuser","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:35:56+00:00', '2026-09-21T09:20:36+00:00'),
(100, 'product_updated', 8, 32, 'Product Updated', 'Product Intense Man Essencia de flores was updated (id #279).', '{"product_id":"279","product_name":"Intense Man Essencia de flores","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:38:25+00:00', '2026-09-21T09:20:36+00:00'),
(101, 'product_updated', 8, 32, 'Product Updated', 'Product FLORENZA was updated (id #275).', '{"product_id":"275","product_name":"FLORENZA","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:44:29+00:00', '2026-09-21T09:20:36+00:00'),
(102, 'product_updated', 8, 32, 'Product Updated', 'Product Scepter Malachite was updated (id #274).', '{"product_id":"274","product_name":"Scepter Malachite","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:47:23+00:00', '2026-09-21T09:20:36+00:00'),
(103, 'product_updated', 8, 32, 'Product Updated', 'Product RA''ED LUXE was updated (id #273).', '{"product_id":"273","product_name":"RA''ED LUXE","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:49:51+00:00', '2026-09-21T09:20:37+00:00'),
(206, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 50ml, 74 units).', '{"bottle_stock_id":"31","volume":"50ml","quantity":74}', TRUE, '2026-09-19T13:37:45+00:00', '2026-09-21T09:20:37+00:00'),
(345, 'product_deactivated', 8, 32, 'Product Deactivated', 'Product Extract was deactivated (id #161).', '{"product_id":"161","product_name":"Extract"}', TRUE, '2026-09-24T08:20:28+00:00', '2026-09-29T14:32:45+00:00'),
(105, 'product_updated', 8, 32, 'Product Updated', 'Product MAWJ APPLETINI was updated (id #271).', '{"product_id":"271","product_name":"MAWJ APPLETINI","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:54:07+00:00', '2026-09-21T09:20:37+00:00'),
(106, 'product_updated', 8, 32, 'Product Updated', 'Product Qaed Al Fursan was updated (id #270).', '{"product_id":"270","product_name":"Qaed Al Fursan","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:56:51+00:00', '2026-09-21T09:20:38+00:00'),
(107, 'product_updated', 8, 32, 'Product Updated', 'Product Lail Maleki was updated (id #269).', '{"product_id":"269","product_name":"Lail Maleki","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:58:27+00:00', '2026-09-21T09:20:38+00:00'),
(109, 'product_updated', 8, 32, 'Product Updated', 'Product Meethaq was updated (id #267).', '{"product_id":"267","product_name":"Meethaq","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:02:44+00:00', '2026-09-21T09:20:38+00:00'),
(110, 'product_updated', 8, 32, 'Product Updated', 'Product Al-nashana plane was updated (id #266).', '{"product_id":"266","product_name":"Al-nashana plane","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:04:56+00:00', '2026-09-21T09:20:38+00:00'),
(111, 'product_updated', 8, 32, 'Product Updated', 'Product Al-nashana caprice was updated (id #265).', '{"product_id":"265","product_name":"Al-nashana caprice","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:06:20+00:00', '2026-09-21T09:20:39+00:00'),
(112, 'product_updated', 8, 32, 'Product Updated', 'Product Fusion intense was updated (id #264).', '{"product_id":"264","product_name":"Fusion intense","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:08:55+00:00', '2026-09-21T09:20:39+00:00'),
(113, 'product_updated', 8, 32, 'Product Updated', 'Product Legend was updated (id #263).', '{"product_id":"263","product_name":"Legend","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:10:57+00:00', '2026-09-21T09:20:39+00:00'),
(114, 'product_updated', 8, 32, 'Product Updated', 'Product Legend was updated (id #263).', '{"product_id":"263","product_name":"Legend","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:11:00+00:00', '2026-09-21T09:20:39+00:00'),
(115, 'product_updated', 8, 32, 'Product Updated', 'Product Nebras New was updated (id #262).', '{"product_id":"262","product_name":"Nebras New","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:12:14+00:00', '2026-09-21T09:20:39+00:00'),
(116, 'product_updated', 8, 32, 'Product Updated', 'Product Affection was updated (id #261).', '{"product_id":"261","product_name":"Affection","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:14:37+00:00', '2026-09-21T09:20:40+00:00'),
(117, 'product_updated', 8, 32, 'Product Updated', 'Product FATIMA zimaya was updated (id #241).', '{"product_id":"241","product_name":"FATIMA zimaya","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:19:13+00:00', '2026-09-21T09:20:40+00:00'),
(118, 'product_updated', 8, 32, 'Product Updated', 'Product Night club was updated (id #259).', '{"product_id":"259","product_name":"Night club","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:21:27+00:00', '2026-09-21T09:20:40+00:00'),
(119, 'product_updated', 8, 32, 'Product Updated', 'Product Art of Universe was updated (id #258).', '{"product_id":"258","product_name":"Art of Universe","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:22:52+00:00', '2026-09-21T09:20:40+00:00'),
(120, 'product_updated', 8, 32, 'Product Updated', 'Product Karus was updated (id #257).', '{"product_id":"257","product_name":"Karus","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:25:27+00:00', '2026-09-21T09:20:41+00:00'),
(121, 'product_updated', 8, 32, 'Product Updated', 'Product 9 pm pour femme was updated (id #256).', '{"product_id":"256","product_name":"9 pm pour femme","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:27:47+00:00', '2026-09-21T09:20:41+00:00'),
(122, 'product_updated', 8, 32, 'Product Updated', 'Product Island Hadlaj was updated (id #255).', '{"product_id":"255","product_name":"Island Hadlaj","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:29:53+00:00', '2026-09-21T09:20:41+00:00'),
(123, 'product_deactivated', 8, 32, 'Product Deactivated', 'Product Club De Nuit intense man was deactivated (id #218).', '{"product_id":"218","product_name":"Club De Nuit intense man"}', TRUE, '2026-09-17T16:31:37+00:00', '2026-09-21T09:20:41+00:00'),
(124, 'product_updated', 8, 32, 'Product Updated', 'Product FAYORA was updated (id #254).', '{"product_id":"254","product_name":"FAYORA","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:37:22+00:00', '2026-09-21T09:20:42+00:00'),
(125, 'product_updated', 8, 32, 'Product Updated', 'Product SWISS ARABIAN CASABLANCA was updated (id #253).', '{"product_id":"253","product_name":"SWISS ARABIAN CASABLANCA","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:39:14+00:00', '2026-09-21T09:20:42+00:00'),
(126, 'order_status_changed', 8, 29, 'Order Status Changed', 'Order ORD-20260918073654-2143 status changed to assigned.', '{"order_id":"25","order_number":"ORD-20260918073654-2143","total":315000}', TRUE, '2026-09-18T07:38:31+00:00', '2026-09-21T09:20:42+00:00'),
(127, 'order_status_changed', 8, 29, 'Order Status Changed', 'Order ORD-20260918073654-2143 status changed to ready.', '{"order_id":"25","order_number":"ORD-20260918073654-2143","total":315000}', TRUE, '2026-09-18T07:39:05+00:00', '2026-09-21T09:20:42+00:00'),
(128, 'order_status_changed', 8, 29, 'Order Status Changed', 'Order ORD-20260918073654-2143 status changed to completed.', '{"order_id":"25","order_number":"ORD-20260918073654-2143","total":315000}', TRUE, '2026-09-18T07:39:42+00:00', '2026-09-21T09:20:42+00:00'),
(129, 'order_status_changed', 8, 29, 'Order Status Changed', 'Order ORD-20260918073654-2143 status changed to served.', '{"order_id":"25","order_number":"ORD-20260918073654-2143","total":315000}', TRUE, '2026-09-18T09:20:18+00:00', '2026-09-21T09:20:43+00:00'),
(207, 'stock_deleted', 8, 29, 'Stock Record Deleted', 'Stock record deleted for product #345 (30 units @ 54,000 TZS).', '{"branch_stock_id":"70","product_id":345,"quantity":30,"selling_price":54000}', TRUE, '2026-09-19T17:19:53+00:00', '2026-09-21T09:20:43+00:00'),
(351, 'product_deactivated', 8, 32, 'Product Deactivated', 'Product Test perfume was deactivated (id #345).', '{"product_id":"345","product_name":"Test perfume"}', TRUE, '2026-09-25T08:02:20+00:00', '2026-09-29T14:32:45+00:00'),
(132, 'product_updated', 8, 32, 'Product Updated', 'Product Victoria Lattafa was updated (id #251).', '{"product_id":"251","product_name":"Victoria Lattafa","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T17:54:08+00:00', '2026-09-21T09:20:44+00:00'),
(133, 'product_updated', 8, 32, 'Product Updated', 'Product Eshal Vanila was updated (id #250).', '{"product_id":"250","product_name":"Eshal Vanila","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T17:58:51+00:00', '2026-09-21T09:20:44+00:00'),
(134, 'product_updated', 8, 32, 'Product Updated', 'Product Eclaire was updated (id #249).', '{"product_id":"249","product_name":"Eclaire","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T18:00:31+00:00', '2026-09-21T09:20:44+00:00'),
(135, 'product_updated', 8, 32, 'Product Updated', 'Product Atlas was updated (id #248).', '{"product_id":"248","product_name":"Atlas","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T18:04:35+00:00', '2026-09-21T09:20:44+00:00'),
(136, 'product_updated', 8, 32, 'Product Updated', 'Product MARMARA was updated (id #246).', '{"product_id":"246","product_name":"MARMARA","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T18:05:40+00:00', '2026-09-21T09:20:45+00:00'),
(137, 'product_updated', 8, 32, 'Product Updated', 'Product Taskeen Caramel cascade was updated (id #245).', '{"product_id":"245","product_name":"Taskeen Caramel cascade","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T18:07:10+00:00', '2026-09-21T09:20:45+00:00'),
(138, 'product_updated', 8, 32, 'Product Updated', 'Product Angham was updated (id #244).', '{"product_id":"244","product_name":"Angham","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T18:08:21+00:00', '2026-09-21T09:20:45+00:00'),
(139, 'product_updated', 8, 32, 'Product Updated', 'Product Faris Al Atrab was updated (id #280).', '{"product_id":"280","product_name":"Faris Al Atrab","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T18:16:43+00:00', '2026-09-21T09:20:45+00:00'),
(140, 'product_updated', 8, 32, 'Product Updated', 'Product Now Women was updated (id #243).', '{"product_id":"243","product_name":"Now Women","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T18:32:01+00:00', '2026-09-21T09:20:45+00:00'),
(141, 'product_updated', 8, 32, 'Product Updated', 'Product Marshmallow Blush was updated (id #242).', '{"product_id":"242","product_name":"Marshmallow Blush","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T18:33:44+00:00', '2026-09-21T09:20:46+00:00'),
(142, 'product_updated', 8, 32, 'Product Updated', 'Product YARA candy was updated (id #240).', '{"product_id":"240","product_name":"YARA candy","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T18:35:57+00:00', '2026-09-21T09:20:46+00:00'),
(143, 'product_updated', 8, 32, 'Product Updated', 'Product YARA elixir was updated (id #239).', '{"product_id":"239","product_name":"YARA elixir","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T18:40:40+00:00', '2026-09-21T09:20:46+00:00');
INSERT INTO public.admin_notifications (id, type, branch_id, user_id, title, message, data, is_read, created_at, updated_at) VALUES
(144, 'product_updated', 8, 32, 'Product Updated', 'Product Now Black was updated (id #238).', '{"product_id":"238","product_name":"Now Black","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T18:43:20+00:00', '2026-09-21T09:20:46+00:00'),
(145, 'product_updated', 8, 32, 'Product Updated', 'Product Now white was updated (id #237).', '{"product_id":"237","product_name":"Now white","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T18:45:14+00:00', '2026-09-21T09:20:46+00:00'),
(146, 'product_updated', 8, 32, 'Product Updated', 'Product Stronger with you was updated (id #324).', '{"product_id":"324","product_name":"Stronger with you","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","updated_at"]}', TRUE, '2026-09-18T18:45:44+00:00', '2026-09-21T09:20:47+00:00'),
(147, 'product_updated', 8, 32, 'Product Updated', 'Product Cookie Bite was updated (id #236).', '{"product_id":"236","product_name":"Cookie Bite","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T18:47:58+00:00', '2026-09-21T09:20:47+00:00'),
(148, 'product_updated', 8, 32, 'Product Updated', 'Product Candy Bite was updated (id #235).', '{"product_id":"235","product_name":"Candy Bite","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T18:49:58+00:00', '2026-09-21T09:20:47+00:00'),
(149, 'product_updated', 8, 32, 'Product Updated', 'Product Pride pour Home was updated (id #234).', '{"product_id":"234","product_name":"Pride pour Home","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T18:53:58+00:00', '2026-09-21T09:20:47+00:00'),
(150, 'product_updated', 8, 32, 'Product Updated', 'Product Pride Intense was updated (id #233).', '{"product_id":"233","product_name":"Pride Intense","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T19:02:21+00:00', '2026-09-21T09:20:48+00:00'),
(151, 'product_updated', 8, 32, 'Product Updated', 'Product Intense Noir was updated (id #232).', '{"product_id":"232","product_name":"Intense Noir","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T19:05:56+00:00', '2026-09-21T09:20:48+00:00'),
(152, 'product_updated', 8, 32, 'Product Updated', 'Product Sugar Rush was updated (id #227).', '{"product_id":"227","product_name":"Sugar Rush","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T08:53:32+00:00', '2026-09-21T09:20:48+00:00'),
(153, 'product_updated', 8, 32, 'Product Updated', 'Product Sugar Lollipop was updated (id #228).', '{"product_id":"228","product_name":"Sugar Lollipop","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:00:39+00:00', '2026-09-21T09:20:48+00:00'),
(155, 'product_updated', 8, 32, 'Product Updated', 'Product Sugar Kiss was updated (id #231).', '{"product_id":"231","product_name":"Sugar Kiss","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:06:49+00:00', '2026-09-21T09:20:49+00:00'),
(156, 'product_updated', 8, 32, 'Product Updated', 'Product Sugar punch was updated (id #230).', '{"product_id":"230","product_name":"Sugar punch","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:08:53+00:00', '2026-09-21T09:20:49+00:00'),
(157, 'product_updated', 8, 32, 'Product Updated', 'Product Khamrah Waha was updated (id #226).', '{"product_id":"226","product_name":"Khamrah Waha","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:18:05+00:00', '2026-09-21T09:20:49+00:00'),
(158, 'product_updated', 8, 32, 'Product Updated', 'Product Ombre Dor was updated (id #225).', '{"product_id":"225","product_name":"Ombre Dor","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:20:16+00:00', '2026-09-21T09:20:49+00:00'),
(159, 'product_updated', 8, 32, 'Product Updated', 'Product Queen of Roses was updated (id #224).', '{"product_id":"224","product_name":"Queen of Roses","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:24:06+00:00', '2026-09-21T09:20:50+00:00'),
(160, 'product_updated', 8, 32, 'Product Updated', 'Product Vanilla Voyage was updated (id #223).', '{"product_id":"223","product_name":"Vanilla Voyage","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:29:58+00:00', '2026-09-21T09:20:50+00:00'),
(161, 'product_updated', 8, 32, 'Product Updated', 'Product Tiramisu Zimaya was updated (id #222).', '{"product_id":"222","product_name":"Tiramisu Zimaya","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:32:37+00:00', '2026-09-21T09:20:50+00:00'),
(162, 'product_updated', 8, 32, 'Product Updated', 'Product Club De Nuit Lion heart woman was updated (id #221).', '{"product_id":"221","product_name":"Club De Nuit Lion heart woman","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:43:36+00:00', '2026-09-21T09:20:50+00:00'),
(163, 'product_updated', 8, 32, 'Product Updated', 'Product Club De Nuit lion heart man was updated (id #220).', '{"product_id":"220","product_name":"Club De Nuit lion heart man","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:44:49+00:00', '2026-09-21T09:20:50+00:00'),
(164, 'product_updated', 8, 32, 'Product Updated', 'Product Club De Nuit lion heart man was updated (id #220).', '{"product_id":"220","product_name":"Club De Nuit lion heart man","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:45:25+00:00', '2026-09-21T09:20:51+00:00'),
(165, 'product_updated', 8, 32, 'Product Updated', 'Product Club De Nuit urban man elixir was updated (id #219).', '{"product_id":"219","product_name":"Club De Nuit urban man elixir","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:47:59+00:00', '2026-09-21T09:20:51+00:00'),
(166, 'product_updated', 8, 32, 'Product Updated', 'Product Club De Nuit iconic was updated (id #217).', '{"product_id":"217","product_name":"Club De Nuit iconic","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:49:55+00:00', '2026-09-21T09:20:51+00:00'),
(167, 'product_updated', 8, 32, 'Product Updated', 'Product Club De Nuit Maleka was updated (id #216).', '{"product_id":"216","product_name":"Club De Nuit Maleka","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:51:53+00:00', '2026-09-21T09:20:51+00:00'),
(168, 'product_updated', 8, 32, 'Product Updated', 'Product Club De Nuit imperial was updated (id #215).', '{"product_id":"215","product_name":"Club De Nuit imperial","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:54:31+00:00', '2026-09-21T09:20:51+00:00'),
(169, 'product_updated', 8, 32, 'Product Updated', 'Product Marj was updated (id #214).', '{"product_id":"214","product_name":"Marj","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:58:02+00:00', '2026-09-21T09:20:52+00:00'),
(170, 'product_updated', 8, 32, 'Product Updated', 'Product HAWAS ice was updated (id #213).', '{"product_id":"213","product_name":"HAWAS ice","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:00:13+00:00', '2026-09-21T09:20:52+00:00'),
(171, 'product_updated', 8, 32, 'Product Updated', 'Product Electric Turath was updated (id #212).', '{"product_id":"212","product_name":"Electric Turath","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:02:29+00:00', '2026-09-21T09:20:52+00:00'),
(172, 'product_updated', 8, 32, 'Product Updated', 'Product 9 pm night out was updated (id #211).', '{"product_id":"211","product_name":"9 pm night out","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:03:46+00:00', '2026-09-21T09:20:52+00:00'),
(173, 'product_updated', 8, 32, 'Product Updated', 'Product 9 pm rebel was updated (id #210).', '{"product_id":"210","product_name":"9 pm rebel","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:05:22+00:00', '2026-09-21T09:20:53+00:00'),
(174, 'product_updated', 8, 32, 'Product Updated', 'Product 9 pm elixir was updated (id #209).', '{"product_id":"209","product_name":"9 pm elixir","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:07:33+00:00', '2026-09-21T09:20:53+00:00'),
(175, 'product_updated', 8, 32, 'Product Updated', 'Product Supremacy Silver was updated (id #208).', '{"product_id":"208","product_name":"Supremacy Silver","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:08:47+00:00', '2026-09-21T09:20:53+00:00'),
(176, 'product_updated', 8, 32, 'Product Updated', 'Product Supremacy in heaven was updated (id #207).', '{"product_id":"207","product_name":"Supremacy in heaven","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:11:00+00:00', '2026-09-21T09:20:53+00:00'),
(195, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 100ml, 179 units).', '{"bottle_stock_id":"36","volume":"100ml","quantity":179}', TRUE, '2026-09-19T13:36:06+00:00', '2026-09-21T09:20:57+00:00'),
(196, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 100ml, 112 units).', '{"bottle_stock_id":"38","volume":"100ml","quantity":112}', TRUE, '2026-09-19T13:36:16+00:00', '2026-09-21T09:20:57+00:00'),
(197, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 100ml, 151 units).', '{"bottle_stock_id":"37","volume":"100ml","quantity":151}', TRUE, '2026-09-19T13:36:24+00:00', '2026-09-21T09:20:57+00:00'),
(198, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 12ml, 441 units).', '{"bottle_stock_id":"39","volume":"12ml","quantity":441}', TRUE, '2026-09-19T13:36:32+00:00', '2026-09-21T09:20:58+00:00'),
(199, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 30ml, 71 units).', '{"bottle_stock_id":"29","volume":"30ml","quantity":71}', TRUE, '2026-09-19T13:36:43+00:00', '2026-09-21T09:20:58+00:00'),
(200, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 30ml, 252 units).', '{"bottle_stock_id":"41","volume":"30ml","quantity":252}', TRUE, '2026-09-19T13:36:51+00:00', '2026-09-21T09:20:58+00:00'),
(201, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 30ml, 204 units).', '{"bottle_stock_id":"42","volume":"30ml","quantity":204}', TRUE, '2026-09-19T13:37:00+00:00', '2026-09-21T09:20:58+00:00'),
(202, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 50ml, 1008 units).', '{"bottle_stock_id":"30","volume":"50ml","quantity":1008}', TRUE, '2026-09-19T13:37:08+00:00', '2026-09-21T09:20:58+00:00'),
(203, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 50ml, 50 units).', '{"bottle_stock_id":"34","volume":"50ml","quantity":50}', TRUE, '2026-09-19T13:37:19+00:00', '2026-09-21T09:20:59+00:00'),
(178, 'product_updated', 8, 32, 'Product Updated', 'Product Supremacy Collectors Edition was updated (id #205).', '{"product_id":"205","product_name":"Supremacy Collectors Edition","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:22:30+00:00', '2026-09-21T09:20:54+00:00'),
(179, 'product_updated', 8, 32, 'Product Updated', 'Product Supremacy Collectors Edition was updated (id #205).', '{"product_id":"205","product_name":"Supremacy Collectors Edition","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:23:10+00:00', '2026-09-21T09:20:54+00:00'),
(180, 'product_updated', 8, 32, 'Product Updated', 'Product Precieux was updated (id #204).', '{"product_id":"204","product_name":"Precieux","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:34:35+00:00', '2026-09-21T09:20:54+00:00'),
(181, 'product_updated', 8, 32, 'Product Updated', 'Product Kingdom was updated (id #203).', '{"product_id":"203","product_name":"Kingdom","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:37:50+00:00', '2026-09-21T09:20:54+00:00'),
(182, 'product_updated', 8, 32, 'Product Updated', 'Product Tonquin Giza Rayhan was updated (id #202).', '{"product_id":"202","product_name":"Tonquin Giza Rayhan","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:39:42+00:00', '2026-09-21T09:20:54+00:00'),
(183, 'product_updated', 8, 32, 'Product Updated', 'Product RAYHAN was updated (id #201).', '{"product_id":"201","product_name":"RAYHAN","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:55:21+00:00', '2026-09-21T09:20:55+00:00'),
(184, 'product_updated', 8, 32, 'Product Updated', 'Product Petra was updated (id #200).', '{"product_id":"200","product_name":"Petra","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:56:38+00:00', '2026-09-21T09:20:55+00:00'),
(185, 'product_updated', 8, 32, 'Product Updated', 'Product Eternal Vanille was updated (id #199).', '{"product_id":"199","product_name":"Eternal Vanille","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:58:23+00:00', '2026-09-21T09:20:55+00:00'),
(186, 'product_updated', 8, 32, 'Product Updated', 'Product Indomitable was updated (id #198).', '{"product_id":"198","product_name":"Indomitable","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T11:02:08+00:00', '2026-09-21T09:20:55+00:00'),
(187, 'product_updated', 8, 32, 'Product Updated', 'Product Giorgio Black Special edition was updated (id #197).', '{"product_id":"197","product_name":"Giorgio Black Special edition","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T11:04:10+00:00', '2026-09-21T09:20:55+00:00'),
(188, 'product_updated', 8, 32, 'Product Updated', 'Product Ely Sia Vanilla Sugar was updated (id #196).', '{"product_id":"196","product_name":"Ely Sia Vanilla Sugar","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T11:07:59+00:00', '2026-09-21T09:20:56+00:00'),
(189, 'product_updated', 8, 32, 'Product Updated', 'Product Khair Peach Delulu was updated (id #195).', '{"product_id":"195","product_name":"Khair Peach Delulu","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T11:11:41+00:00', '2026-09-21T09:20:56+00:00'),
(190, 'product_updated', 8, 32, 'Product Updated', 'Product AZM was updated (id #194).', '{"product_id":"194","product_name":"AZM","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T11:12:49+00:00', '2026-09-21T09:20:56+00:00'),
(191, 'product_updated', 8, 32, 'Product Updated', 'Product Couture Noir was updated (id #193).', '{"product_id":"193","product_name":"Couture Noir","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T11:14:42+00:00', '2026-09-21T09:20:56+00:00'),
(192, 'product_updated', 8, 32, 'Product Updated', 'Product Nebras was updated (id #192).', '{"product_id":"192","product_name":"Nebras","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T11:17:09+00:00', '2026-09-21T09:20:56+00:00'),
(193, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 6ml, 344 units).', '{"bottle_stock_id":"40","volume":"6ml","quantity":344}', TRUE, '2026-09-19T13:35:46+00:00', '2026-09-21T09:20:57+00:00'),
(194, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 100ml, 571 units).', '{"bottle_stock_id":"35","volume":"100ml","quantity":571}', TRUE, '2026-09-19T13:35:57+00:00', '2026-09-21T09:20:57+00:00'),
(42, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 100ml, 30 units).', '{"bottle_stock_id":"26","volume":"100ml","quantity":30}', TRUE, '2026-09-14T10:49:00+00:00', '2026-09-21T09:20:23+00:00'),
(47, 'stock_deleted', 8, 32, 'Bottle Stock Deleted', 'Bottle stock record deleted (volume 50ml, 104 units).', '{"bottle_stock_id":"24","volume":"50ml","quantity":104}', TRUE, '2026-09-14T10:49:41+00:00', '2026-09-21T09:20:24+00:00'),
(53, 'order_status_changed', 10, 39, 'Order Status Changed', 'Order ORD-20260916162607-170D status changed to assigned.', '{"order_id":"23","order_number":"ORD-20260916162607-170D","total":45000}', TRUE, '2026-09-16T16:36:14+00:00', '2026-09-21T09:20:26+00:00'),
(58, 'product_updated', 8, 32, 'Product Updated', 'Product Club De Nuit intense man was updated (id #218).', '{"product_id":"218","product_name":"Club De Nuit intense man","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T11:23:59+00:00', '2026-09-21T09:20:27+00:00'),
(79, 'product_updated', 8, 32, 'Product Updated', 'Product Barcode Signature was updated (id #294).', '{"product_id":"294","product_name":"Barcode Signature","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T13:52:51+00:00', '2026-09-21T09:20:31+00:00'),
(86, 'product_updated', 8, 32, 'Product Updated', 'Product Unique extremely Pista was updated (id #290).', '{"product_id":"290","product_name":"Unique extremely Pista","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T14:55:21+00:00', '2026-09-21T09:20:33+00:00'),
(104, 'product_updated', 8, 32, 'Product Updated', 'Product OPHIDIAN was updated (id #272).', '{"product_id":"272","product_name":"OPHIDIAN","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T15:51:13+00:00', '2026-09-21T09:20:37+00:00'),
(108, 'product_updated', 8, 32, 'Product Updated', 'Product Night Club Green Tweed was updated (id #268).', '{"product_id":"268","product_name":"Night Club Green Tweed","validated":["name","description","brand","category","sex_category","is_active","images","updated_at"]}', TRUE, '2026-09-17T16:00:36+00:00', '2026-09-21T09:20:38+00:00'),
(130, 'product_updated', 8, 32, 'Product Updated', 'Product Pink blush was updated (id #260).', '{"product_id":"260","product_name":"Pink blush","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T17:50:38+00:00', '2026-09-21T09:20:43+00:00'),
(131, 'product_updated', 8, 32, 'Product Updated', 'Product Shaghaf Oud Tonka was updated (id #252).', '{"product_id":"252","product_name":"Shaghaf Oud Tonka","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-18T17:53:02+00:00', '2026-09-21T09:20:43+00:00'),
(154, 'product_updated', 8, 32, 'Product Updated', 'Product Sugar Marshmallow was updated (id #229).', '{"product_id":"229","product_name":"Sugar Marshmallow","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T09:02:01+00:00', '2026-09-21T09:20:48+00:00'),
(177, 'product_updated', 8, 32, 'Product Updated', 'Product Supremacy not only intense was updated (id #206).', '{"product_id":"206","product_name":"Supremacy not only intense","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-19T10:19:19+00:00', '2026-09-21T09:20:53+00:00'),
(208, 'stock_deleted', 8, 29, 'Stock Record Deleted', 'Stock record deleted for product #345 (200 units @ 54,000 TZS).', '{"branch_stock_id":"71","product_id":345,"quantity":200,"selling_price":54000}', TRUE, '2026-09-19T19:37:02+00:00', '2026-09-21T09:20:59+00:00'),
(209, 'price_customization', 8, 29, 'Price Changed', 'Selling price changed for product #345 during stock-in: 54,000 â†’ 105,000 TZS.', '{"branch_stock_id":72,"product_id":"345","old_price":54000,"new_price":"105000"}', TRUE, '2026-09-19T19:40:37+00:00', '2026-09-21T09:20:59+00:00'),
(210, 'price_customization', 8, 29, 'Price Changed', 'Selling price changed for product #345 during stock-in: 105,000 â†’ 54,000 TZS.', '{"branch_stock_id":72,"product_id":"345","old_price":105000,"new_price":"54000"}', TRUE, '2026-09-19T19:42:22+00:00', '2026-09-21T09:20:59+00:00'),
(211, 'price_customization', 8, 29, 'Price Changed', 'Selling price changed for product #345 during stock-in: 54,000 â†’ 37,000 TZS.', '{"branch_stock_id":72,"product_id":"345","old_price":54000,"new_price":37000}', TRUE, '2026-09-19T20:41:28+00:00', '2026-09-21T09:20:59+00:00'),
(212, 'stock_transfer', 10, 37, 'Stock item verified & received', 'Product Stock ''Product #345 â€” 50ml With Box Â· With Logo Â· Yellow'' (qty 150) verified and added to Head Quarters-Mikocheni from transfer TF-20260919170045-8E2A', NULL, TRUE, '2026-09-21T08:11:06+00:00', '2026-09-21T09:21:00+00:00'),
(213, 'stock_transfer', 9, 36, 'Stock item verified & received', 'Product Stock ''Product #345 â€” 30ml With Box Â· With Logo Â· Yellow'' (qty 20) verified and added to Dodoma branch from transfer TF-20260919170247-443D', NULL, TRUE, '2026-09-21T08:17:46+00:00', '2026-09-21T09:21:00+00:00'),
(214, 'stock_transfer', 10, 37, 'Stock item verified & received', 'Product Stock ''Product #345 â€” 50ml With Box Â· With Logo Â· Yellow'' (qty 100) verified and added to Head Quarters-Mikocheni from transfer TF-20260919194801-192A', NULL, TRUE, '2026-09-21T08:38:36+00:00', '2026-09-21T09:21:00+00:00'),
(215, 'discount_used', 10, 37, 'Discount Applied', 'Discount applied in sale SALE-20260921084945-444C (Stock Manager). Test perfume x2: 54,000 -> 50,000. Total: 108,000 -> 100,000.', '{"sale_id":78,"sale_number":"SALE-20260921084945-444C","items":[{"product_id":"345","product_name":"Test perfume","quantity":2,"original_price":54000,"discount_price":50000}],"original_total":108000,"discounted_total":100000}', TRUE, '2026-09-21T08:49:46+00:00', '2026-09-21T09:21:00+00:00'),
(229, 'product_updated', 8, 32, 'Product Updated', 'Product Riwayah was updated (id #184).', '{"product_id":"184","product_name":"Riwayah","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:36:56+00:00', '2026-09-29T14:32:46+00:00'),
(230, 'product_updated', 8, 32, 'Product Updated', 'Product Vulcan Feu was updated (id #183).', '{"product_id":"183","product_name":"Vulcan Feu","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:39:30+00:00', '2026-09-29T14:32:46+00:00'),
(235, 'product_updated', 8, 32, 'Product Updated', 'Product Vintage Radio was updated (id #178).', '{"product_id":"178","product_name":"Vintage Radio","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:52:50+00:00', '2026-09-29T14:32:46+00:00'),
(236, 'product_updated', 8, 32, 'Product Updated', 'Product Aromatic Magnetic was updated (id #177).', '{"product_id":"177","product_name":"Aromatic Magnetic","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:54:06+00:00', '2026-09-29T14:32:47+00:00'),
(237, 'product_updated', 8, 32, 'Product Updated', 'Product Aromatic Forbidden fruit was updated (id #176).', '{"product_id":"176","product_name":"Aromatic Forbidden fruit","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:55:33+00:00', '2026-09-29T14:32:47+00:00'),
(238, 'product_updated', 8, 32, 'Product Updated', 'Product Aromatic FrostBite was updated (id #175).', '{"product_id":"175","product_name":"Aromatic FrostBite","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:57:13+00:00', '2026-09-29T14:32:47+00:00'),
(239, 'product_updated', 8, 32, 'Product Updated', 'Product Marwa was updated (id #174).', '{"product_id":"174","product_name":"Marwa","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:58:45+00:00', '2026-09-29T14:32:47+00:00'),
(240, 'product_updated', 8, 32, 'Product Updated', 'Product Lynked Forever was updated (id #173).', '{"product_id":"173","product_name":"Lynked Forever","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:59:17+00:00', '2026-09-29T14:32:47+00:00'),
(241, 'product_updated', 8, 32, 'Product Updated', 'Product Private Key was updated (id #172).', '{"product_id":"172","product_name":"Private Key","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T13:00:36+00:00', '2026-09-29T14:32:48+00:00'),
(242, 'product_updated', 8, 32, 'Product Updated', 'Product Yum yum was updated (id #171).', '{"product_id":"171","product_name":"Yum yum","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T13:04:27+00:00', '2026-09-29T14:32:48+00:00'),
(243, 'product_updated', 8, 32, 'Product Updated', 'Product Ravin Ginger was updated (id #170).', '{"product_id":"170","product_name":"Ravin Ginger","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T13:06:15+00:00', '2026-09-29T14:32:48+00:00'),
(244, 'product_updated', 8, 32, 'Product Updated', 'Product Milk way was updated (id #169).', '{"product_id":"169","product_name":"Milk way","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T13:08:11+00:00', '2026-09-29T14:32:48+00:00'),
(245, 'product_updated', 8, 32, 'Product Updated', 'Product Vanguard was updated (id #168).', '{"product_id":"168","product_name":"Vanguard","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T13:12:49+00:00', '2026-09-29T14:32:48+00:00'),
(246, 'product_updated', 8, 32, 'Product Updated', 'Product Thriller III was updated (id #167).', '{"product_id":"167","product_name":"Thriller III","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T13:24:33+00:00', '2026-09-29T14:32:49+00:00'),
(247, 'product_updated', 8, 32, 'Product Updated', 'Product Thriller III was updated (id #167).', '{"product_id":"167","product_name":"Thriller III","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T13:24:42+00:00', '2026-09-29T14:32:49+00:00'),
(248, 'product_updated', 8, 32, 'Product Updated', 'Product Lynked Freedom was updated (id #166).', '{"product_id":"166","product_name":"Lynked Freedom","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T13:26:48+00:00', '2026-09-29T14:32:49+00:00'),
(249, 'product_updated', 8, 32, 'Product Updated', 'Product AL-DIRGHAM was updated (id #165).', '{"product_id":"165","product_name":"AL-DIRGHAM","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T13:27:58+00:00', '2026-09-29T14:32:49+00:00'),
(250, 'product_updated', 8, 32, 'Product Updated', 'Product Cocktail was updated (id #164).', '{"product_id":"164","product_name":"Cocktail","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T13:33:18+00:00', '2026-09-29T14:32:49+00:00'),
(252, 'product_updated', 8, 32, 'Product Updated', 'Product Life Journal was updated (id #160).', '{"product_id":"160","product_name":"Life Journal","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:02:07+00:00', '2026-09-29T14:32:50+00:00'),
(253, 'product_updated', 8, 32, 'Product Updated', 'Product Oputent Dubai was updated (id #159).', '{"product_id":"159","product_name":"Oputent Dubai","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:04:23+00:00', '2026-09-29T14:32:50+00:00'),
(254, 'product_updated', 8, 32, 'Product Updated', 'Product Plum Liquor was updated (id #157).', '{"product_id":"157","product_name":"Plum Liquor","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:17:00+00:00', '2026-09-29T14:32:50+00:00'),
(255, 'product_updated', 8, 32, 'Product Updated', 'Product ANA ABIYEDH coral was updated (id #156).', '{"product_id":"156","product_name":"ANA ABIYEDH coral","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:20:51+00:00', '2026-09-29T14:32:51+00:00'),
(256, 'product_updated', 8, 32, 'Product Updated', 'Product Amber Oud Gold edition was updated (id #155).', '{"product_id":"155","product_name":"Amber Oud Gold edition","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:25:17+00:00', '2026-09-29T14:32:51+00:00'),
(317, 'stock_transfer', 8, 32, 'Stock transfer confirmed', 'Transfer TF-20260922074823-2AAE of Bottle Stock with 1 item(s) created from Kinondoni branch to Dodoma branch.', NULL, TRUE, '2026-09-22T07:48:24+00:00', '2026-09-29T14:32:51+00:00'),
(258, 'product_updated', 8, 32, 'Product Updated', 'Product Safari Breeze was updated (id #153).', '{"product_id":"153","product_name":"Safari Breeze","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:28:25+00:00', '2026-09-29T14:32:51+00:00'),
(259, 'product_updated', 8, 32, 'Product Updated', 'Product Supremacy Gala was updated (id #152).', '{"product_id":"152","product_name":"Supremacy Gala","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:30:18+00:00', '2026-09-29T14:32:52+00:00'),
(260, 'product_updated', 8, 32, 'Product Updated', 'Product Reef 33 white was updated (id #151).', '{"product_id":"151","product_name":"Reef 33 white","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:40:48+00:00', '2026-09-29T14:32:52+00:00'),
(261, 'product_updated', 8, 32, 'Product Updated', 'Product REEF Summer was updated (id #150).', '{"product_id":"150","product_name":"REEF Summer","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:43:36+00:00', '2026-09-29T14:32:52+00:00'),
(262, 'product_updated', 8, 32, 'Product Updated', 'Product REEF 33 Black was updated (id #149).', '{"product_id":"149","product_name":"REEF 33 Black","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:46:30+00:00', '2026-09-29T14:32:52+00:00'),
(263, 'product_updated', 8, 32, 'Product Updated', 'Product HAWAS London was updated (id #148).', '{"product_id":"148","product_name":"HAWAS London","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:47:27+00:00', '2026-09-29T14:32:53+00:00'),
(264, 'product_updated', 8, 32, 'Product Updated', 'Product HAWAS viper was updated (id #147).', '{"product_id":"147","product_name":"HAWAS viper","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:48:04+00:00', '2026-09-29T14:32:53+00:00'),
(265, 'product_updated', 8, 32, 'Product Updated', 'Product HAWAS Pink was updated (id #146).', '{"product_id":"146","product_name":"HAWAS Pink","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:48:39+00:00', '2026-09-29T14:32:53+00:00'),
(266, 'product_updated', 8, 32, 'Product Updated', 'Product INFINITY was updated (id #145).', '{"product_id":"145","product_name":"INFINITY","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:50:34+00:00', '2026-09-29T14:32:53+00:00'),
(267, 'product_updated', 8, 32, 'Product Updated', 'Product RAYHAN AZUL was updated (id #144).', '{"product_id":"144","product_name":"RAYHAN AZUL","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:51:56+00:00', '2026-09-29T14:32:54+00:00'),
(268, 'product_updated', 8, 32, 'Product Updated', 'Product RAYHAN AQUATICA was updated (id #143).', '{"product_id":"143","product_name":"RAYHAN AQUATICA","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:54:01+00:00', '2026-09-29T14:32:54+00:00'),
(269, 'product_updated', 8, 32, 'Product Updated', 'Product EMIR factor edition was updated (id #142).', '{"product_id":"142","product_name":"EMIR factor edition","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:57:01+00:00', '2026-09-29T14:32:54+00:00'),
(270, 'product_updated', 8, 32, 'Product Updated', 'Product SEASONS RISE was updated (id #139).', '{"product_id":"139","product_name":"SEASONS RISE","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:58:20+00:00', '2026-09-29T14:32:54+00:00'),
(271, 'product_updated', 8, 32, 'Product Updated', 'Product Club De Nuit Precieux iv was updated (id #141).', '{"product_id":"141","product_name":"Club De Nuit Precieux iv","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:04:38+00:00', '2026-09-29T14:32:54+00:00'),
(272, 'product_updated', 8, 32, 'Product Updated', 'Product Club De Nuit Overdose was updated (id #140).', '{"product_id":"140","product_name":"Club De Nuit Overdose","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:10:53+00:00', '2026-09-29T14:32:55+00:00'),
(273, 'product_updated', 8, 32, 'Product Updated', 'Product ASWAAR was updated (id #138).', '{"product_id":"138","product_name":"ASWAAR","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:13:02+00:00', '2026-09-29T14:32:55+00:00'),
(274, 'product_updated', 8, 32, 'Product Updated', 'Product XER JOFF was updated (id #137).', '{"product_id":"137","product_name":"XER JOFF","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:16:49+00:00', '2026-09-29T14:32:55+00:00'),
(275, 'product_updated', 8, 32, 'Product Updated', 'Product Burberry Her was updated (id #136).', '{"product_id":"136","product_name":"Burberry Her","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:18:45+00:00', '2026-09-29T14:32:55+00:00'),
(276, 'product_updated', 8, 32, 'Product Updated', 'Product My Burberry Black was updated (id #135).', '{"product_id":"135","product_name":"My Burberry Black","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:20:07+00:00', '2026-09-29T14:32:56+00:00'),
(277, 'product_updated', 8, 32, 'Product Updated', 'Product UNIQUE''E LUXURY CRUSH ON ME was updated (id #134).', '{"product_id":"134","product_name":"UNIQUE''E LUXURY CRUSH ON ME","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:22:09+00:00', '2026-09-29T14:32:56+00:00'),
(278, 'product_updated', 8, 32, 'Product Updated', 'Product Black Opium was updated (id #133).', '{"product_id":"133","product_name":"Black Opium","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:26:32+00:00', '2026-09-29T14:32:56+00:00'),
(279, 'product_updated', 8, 32, 'Product Updated', 'Product Stronger with you intensely was updated (id #132).', '{"product_id":"132","product_name":"Stronger with you intensely","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:28:56+00:00', '2026-09-29T14:32:57+00:00'),
(281, 'product_updated', 8, 32, 'Product Updated', 'Product MEGAMARE was updated (id #130).', '{"product_id":"130","product_name":"MEGAMARE","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:34:37+00:00', '2026-09-29T14:32:57+00:00'),
(282, 'product_updated', 8, 32, 'Product Updated', 'Product Miss Dior was updated (id #129).', '{"product_id":"129","product_name":"Miss Dior","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:37:26+00:00', '2026-09-29T14:32:57+00:00'),
(283, 'product_updated', 8, 32, 'Product Updated', 'Product Kouros was updated (id #128).', '{"product_id":"128","product_name":"Kouros","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:40:58+00:00', '2026-09-29T14:32:57+00:00'),
(284, 'product_updated', 8, 32, 'Product Updated', 'Product Angels'' Share was updated (id #127).', '{"product_id":"127","product_name":"Angels'' Share","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:49:54+00:00', '2026-09-29T14:32:58+00:00'),
(285, 'product_updated', 8, 32, 'Product Updated', 'Product Hibiscus Mahajad was updated (id #126).', '{"product_id":"126","product_name":"Hibiscus Mahajad","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:53:10+00:00', '2026-09-29T14:32:58+00:00'),
(286, 'product_updated', 8, 32, 'Product Updated', 'Product Ex Nihilo Blue Talisman was updated (id #125).', '{"product_id":"125","product_name":"Ex Nihilo Blue Talisman","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:57:29+00:00', '2026-09-29T14:32:58+00:00'),
(287, 'product_updated', 8, 32, 'Product Updated', 'Product Amouage Love hibiscus was updated (id #124).', '{"product_id":"124","product_name":"Amouage Love hibiscus","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:59:01+00:00', '2026-09-29T14:32:58+00:00'),
(288, 'product_updated', 8, 32, 'Product Updated', 'Product Amouage Guidance 46 was updated (id #123).', '{"product_id":"123","product_name":"Amouage Guidance 46","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:01:52+00:00', '2026-09-29T14:32:59+00:00'),
(289, 'product_updated', 8, 32, 'Product Updated', 'Product Louis Vuitton Afternoon swim was updated (id #122).', '{"product_id":"122","product_name":"Louis Vuitton Afternoon swim","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:04:29+00:00', '2026-09-29T14:32:59+00:00'),
(290, 'product_updated', 8, 32, 'Product Updated', 'Product Louis Vuitton Imagination was updated (id #121).', '{"product_id":"121","product_name":"Louis Vuitton Imagination","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:05:56+00:00', '2026-09-29T14:32:59+00:00'),
(291, 'product_updated', 8, 32, 'Product Updated', 'Product Hawas Glitz was updated (id #120).', '{"product_id":"120","product_name":"Hawas Glitz","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:13:03+00:00', '2026-09-29T14:32:59+00:00'),
(292, 'product_updated', 8, 32, 'Product Updated', 'Product Lady Reef was updated (id #119).', '{"product_id":"119","product_name":"Lady Reef","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:14:22+00:00', '2026-09-29T14:32:59+00:00'),
(293, 'product_updated', 8, 32, 'Product Updated', 'Product Billie Eilish was updated (id #118).', '{"product_id":"118","product_name":"Billie Eilish","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:17:56+00:00', '2026-09-29T14:33:00+00:00'),
(294, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #117).', '{"product_id":"117","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:19:16+00:00', '2026-09-29T14:33:00+00:00'),
(295, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #117).', '{"product_id":"117","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:19:57+00:00', '2026-09-29T14:33:00+00:00'),
(296, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #117).', '{"product_id":"117","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:20:47+00:00', '2026-09-29T14:33:01+00:00'),
(297, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #117).', '{"product_id":"117","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:22:01+00:00', '2026-09-29T14:33:01+00:00'),
(298, 'product_updated', 8, 32, 'Product Updated', 'Product Vanilla candy was updated (id #114).', '{"product_id":"114","product_name":"Vanilla candy","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:24:19+00:00', '2026-09-29T14:33:01+00:00'),
(299, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #117).', '{"product_id":"117","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:25:20+00:00', '2026-09-29T14:33:01+00:00'),
(300, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #117).', '{"product_id":"117","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:27:00+00:00', '2026-09-29T14:33:01+00:00'),
(301, 'product_deactivated', 8, 32, 'Product Deactivated', 'Product Dolce & Gabbana The one was deactivated (id #117).', '{"product_id":"117","product_name":"Dolce & Gabbana The one"}', TRUE, '2026-09-21T16:27:49+00:00', '2026-09-29T14:33:02+00:00'),
(302, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #346).', '{"product_id":"346","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:31:33+00:00', '2026-09-29T14:33:02+00:00'),
(303, 'product_updated', 8, 32, 'Product Updated', 'Product Sauvage Dior was updated (id #110).', '{"product_id":"110","product_name":"Sauvage Dior","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:32:59+00:00', '2026-09-29T14:33:02+00:00'),
(304, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #346).', '{"product_id":"346","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:35:07+00:00', '2026-09-29T14:33:02+00:00'),
(305, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #346).', '{"product_id":"346","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:36:29+00:00', '2026-09-29T14:33:03+00:00'),
(306, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #346).', '{"product_id":"346","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:37:44+00:00', '2026-09-29T14:33:03+00:00');
INSERT INTO public.admin_notifications (id, type, branch_id, user_id, title, message, data, is_read, created_at, updated_at) VALUES
(307, 'product_updated', 8, 32, 'Product Updated', 'Product Yum Pistachio Gelato was updated (id #116).', '{"product_id":"116","product_name":"Yum Pistachio Gelato","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:39:22+00:00', '2026-09-29T14:33:03+00:00'),
(308, 'product_updated', 8, 32, 'Product Updated', 'Product UTOPIA Vanilla Coco intense was updated (id #115).', '{"product_id":"115","product_name":"UTOPIA Vanilla Coco intense","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:42:31+00:00', '2026-09-29T14:33:03+00:00'),
(309, 'product_deactivated', 8, 32, 'Product Deactivated', 'Product Yum boujee Marshimallow 2 intense was deactivated (id #113).', '{"product_id":"113","product_name":"Yum boujee Marshimallow 2 intense"}', TRUE, '2026-09-21T16:46:18+00:00', '2026-09-29T14:33:03+00:00'),
(310, 'product_updated', 8, 32, 'Product Updated', 'Product Yum boujee Marshimallow intense was updated (id #112).', '{"product_id":"112","product_name":"Yum boujee Marshimallow intense","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:48:16+00:00', '2026-09-29T14:33:04+00:00'),
(311, 'product_updated', 8, 32, 'Product Updated', 'Product KAY ALI FREEDO MUSK SANTAL was updated (id #111).', '{"product_id":"111","product_name":"KAY ALI FREEDO MUSK SANTAL","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:49:44+00:00', '2026-09-29T14:33:04+00:00'),
(312, 'product_updated', 8, 32, 'Product Updated', 'Product Givenchy Irresistible was updated (id #109).', '{"product_id":"109","product_name":"Givenchy Irresistible","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:52:04+00:00', '2026-09-29T14:33:04+00:00'),
(313, 'product_updated', 8, 32, 'Product Updated', 'Product Imagination was updated (id #107).', '{"product_id":"107","product_name":"Imagination","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T16:53:34+00:00', '2026-09-29T14:33:04+00:00'),
(314, 'stock_transfer', 9, 36, 'Stock item verified & received', 'Product Stock ''Product #345 â€” 30ml With Box Â· With Logo Â· Yellow'' (qty 20) verified and added to Dodoma branch from transfer TF-20260919170247-443D', NULL, TRUE, '2026-09-22T06:03:10+00:00', '2026-09-29T14:33:05+00:00'),
(315, 'stock_transfer', 10, 37, 'Stock item verified & received', 'Product Stock ''Product #345 â€” 50ml With Box Â· With Logo Â· Yellow'' (qty 150) verified and added to Head Quarters-Mikocheni from transfer TF-20260919170045-8E2A', NULL, TRUE, '2026-09-22T06:05:35+00:00', '2026-09-29T14:33:05+00:00'),
(316, 'stock_transfer', 10, 37, 'Stock item verified & received', 'Product Stock ''Product #345 â€” 50ml With Box Â· With Logo Â· Yellow'' (qty 150) verified and added to Head Quarters-Mikocheni from transfer TF-20260919170045-8E2A', NULL, TRUE, '2026-09-22T07:04:33+00:00', '2026-09-29T14:33:05+00:00'),
(319, 'stock_transfer', 9, 36, 'Transfer item rejected (invalid)', 'Product Stock ''Product #345 â€” 30ml With Box Â· With Logo Â· Yellow'' (qty 20) from transfer TF-20260919170247-443D was rejected by Dodoma branch and returned to Kinondoni branch. Reason: broken bottles', NULL, TRUE, '2026-09-22T07:52:18+00:00', '2026-09-29T14:33:05+00:00'),
(321, 'stock_transfer', 8, 32, 'Stock transfer confirmed', 'Transfer TF-20260922082057-1D0B of Product Stock with 1 item(s) created from Kinondoni branch to Dodoma branch.', NULL, TRUE, '2026-09-22T08:20:59+00:00', '2026-09-29T14:33:06+00:00'),
(322, 'stock_transfer', 8, 32, 'Returned item re-sent', 'Product Stock ''Product #345 â€” 30ml With Box Â· With Logo Â· Yellow'' (qty 20) returned from Dodoma branch was re-sent in transfer TF-20260922082152-EE96.', NULL, TRUE, '2026-09-22T08:21:54+00:00', '2026-09-29T14:33:06+00:00'),
(323, 'stock_transfer', 9, 36, 'Stock item verified & received', 'Bottle Accessories ''Straws â€” Silver'' (qty 2) verified and added to Dodoma branch from transfer TF-20260922081728-4298', NULL, TRUE, '2026-09-22T08:28:27+00:00', '2026-09-29T14:33:06+00:00'),
(324, 'stock_transfer', 9, 36, 'Stock item verified & received', 'Product Stock ''Product #99'' (qty 3) verified and added to Dodoma branch from transfer TF-20260922082057-1D0B', NULL, TRUE, '2026-09-22T08:28:51+00:00', '2026-09-29T14:33:06+00:00'),
(325, 'stock_transfer', 9, 36, 'Stock item verified & received', 'Product Stock ''Product #345 â€” 30ml With Box Â· With Logo Â· Yellow'' (qty 20) verified and added to Dodoma branch from transfer TF-20260922082152-EE96', NULL, TRUE, '2026-09-22T08:29:02+00:00', '2026-09-29T14:33:07+00:00'),
(326, 'stock_transfer', 8, 32, 'Stock transfer confirmed', 'Transfer TF-20260922085037-2B1E of Oil Fragrance with 1 item(s) created from Kinondoni branch to Dodoma branch.', NULL, TRUE, '2026-09-22T08:50:38+00:00', '2026-09-29T14:33:07+00:00'),
(327, 'stock_transfer', 9, 36, 'Stock item verified & received', 'Oil Fragrance ''1 Million (1000ml)'' (qty 1) verified and added to Dodoma branch from transfer TF-20260922085037-2B1E', NULL, TRUE, '2026-09-22T08:55:45+00:00', '2026-09-29T14:33:07+00:00'),
(328, 'stock_deleted', 9, 36, 'Bottle Accessories Stock Deleted', 'Bottle accessories stock record deleted (straws silver, 3 units).', '{"bottle_accessories_id":"22","type":"straws","color":"silver","quantity":3}', TRUE, '2026-09-22T08:57:28+00:00', '2026-09-29T14:33:07+00:00'),
(329, 'staff_created', NULL, 21, 'Staff Created', 'Staff Gideon Msuya (branch_admin) registered into branch #8 by super admin.', '{"user_name":"Gideon Msuya","role":"branch_admin","branch_id":"8"}', TRUE, '2026-09-22T09:08:12+00:00', '2026-09-29T14:33:08+00:00'),
(331, 'stock_transfer', 8, 32, 'Stock transfer confirmed', 'Transfer TF-20260923124518-F1E9 of Bottle Stock with 1 item(s) created from Kinondoni branch to Dodoma branch.', NULL, TRUE, '2026-09-23T12:45:19+00:00', '2026-09-29T14:33:08+00:00'),
(332, 'stock_transfer', 8, 32, 'Stock transfer confirmed', 'Transfer TF-20260923153236-7B85 of Bottle Stock with 1 item(s) created from Kinondoni branch to Dodoma branch.', NULL, TRUE, '2026-09-23T15:32:37+00:00', '2026-09-29T14:33:08+00:00'),
(333, 'stock_transfer', 9, 36, 'Stock item verified & received', 'Bottle Stock ''12ml â€” No details'' (qty 10) verified and added to Dodoma branch from transfer TF-20260923153236-7B85', NULL, TRUE, '2026-09-23T15:33:49+00:00', '2026-09-29T14:33:08+00:00'),
(334, 'stock_transfer', 9, 36, 'Transfer item rejected (invalid)', 'Bottle Stock ''100ml â€” With Box Â· With Logo Â· Yellow'' (qty 1) from transfer TF-20260923124518-F1E9 was rejected by Dodoma branch and returned to Kinondoni branch. Reason: imevunjika', NULL, TRUE, '2026-09-24T06:43:32+00:00', '2026-09-29T14:33:09+00:00'),
(335, 'returned_stock', 8, 32, 'Lost / broken item report filed', 'Kinondoni branch reported Broken stock: 100ml â€” With Box Â· With Logo Â· Yellow (qty 1) from transfer TF-20260923124518-F1E9 â€” sent to Dodoma branch, rejected for: imevunjika. Officer: Gideon Msuya, phone 0682601154. Reason: poor packing of item', '{"transfer_id":15,"item_id":21,"damage_type":"broken","quantity":1,"officer_name":"Gideon Msuya","officer_phone":"0682601154","officer_id":null,"from_branch_id":8,"to_branch_id":9}', TRUE, '2026-09-24T06:45:55+00:00', '2026-09-29T14:33:09+00:00'),
(336, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #346).', '{"product_id":"346","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-24T07:00:28+00:00', '2026-09-29T14:33:09+00:00'),
(337, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #346).', '{"product_id":"346","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-24T07:01:44+00:00', '2026-09-29T14:33:09+00:00'),
(338, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #346).', '{"product_id":"346","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-24T07:25:33+00:00', '2026-09-29T14:33:09+00:00'),
(339, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #346).', '{"product_id":"346","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-24T07:27:35+00:00', '2026-09-29T14:33:10+00:00'),
(340, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #346).', '{"product_id":"346","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-24T07:28:58+00:00', '2026-09-29T14:33:10+00:00'),
(341, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #346).', '{"product_id":"346","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-24T07:34:02+00:00', '2026-09-29T14:33:10+00:00'),
(344, 'product_updated', 8, 32, 'Product Updated', 'Product IMPRESSION was updated (id #158).', '{"product_id":"158","product_name":"IMPRESSION","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-24T08:18:55+00:00', '2026-09-29T14:33:10+00:00'),
(346, 'stock_deleted', 8, 32, 'Stock Record Deleted', 'Stock record deleted for product #345 (120 units @ 37,000 TZS).', '{"branch_stock_id":"72","product_id":345,"quantity":120,"selling_price":37000}', TRUE, '2026-09-24T08:56:04+00:00', '2026-09-29T14:33:10+00:00'),
(347, 'stock_deleted', 8, 32, 'Stock Record Deleted', 'Stock record deleted for product #98 (0 units @ 35,000 TZS).', '{"branch_stock_id":"69","product_id":98,"quantity":0,"selling_price":35000}', TRUE, '2026-09-24T08:56:13+00:00', '2026-09-29T14:33:11+00:00'),
(348, 'stock_deleted', 8, 32, 'Stock Record Deleted', 'Stock record deleted for product #29 (6 units @ 7,000 TZS).', '{"branch_stock_id":"68","product_id":29,"quantity":6,"selling_price":7000}', TRUE, '2026-09-24T08:56:22+00:00', '2026-09-29T14:33:11+00:00'),
(349, 'stock_deleted', 8, 32, 'Stock Record Deleted', 'Stock record deleted for product #99 (24 units @ 45,000 TZS).', '{"branch_stock_id":"64","product_id":99,"quantity":24,"selling_price":45000}', TRUE, '2026-09-24T08:57:02+00:00', '2026-09-29T14:33:11+00:00'),
(216, 'order_status_changed', 8, 32, 'Order Status Changed', 'Order ORD-20260918122049-6753 status changed to assigned.', '{"order_id":"26","order_number":"ORD-20260918122049-6753","total":45000}', TRUE, '2026-09-21T10:09:34+00:00', '2026-09-29T14:32:41+00:00'),
(231, 'product_updated', 8, 32, 'Product Updated', 'Product Season Drift was updated (id #182).', '{"product_id":"182","product_name":"Season Drift","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:41:38+00:00', '2026-09-29T14:32:42+00:00'),
(222, 'product_updated', 8, 32, 'Product Updated', 'Product Freeze was updated (id #191).', '{"product_id":"191","product_name":"Freeze","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:01:22+00:00', '2026-09-29T14:32:43+00:00'),
(223, 'product_updated', 8, 32, 'Product Updated', 'Product Vanilla Addiction was updated (id #190).', '{"product_id":"190","product_name":"Vanilla Addiction","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:06:47+00:00', '2026-09-29T14:32:43+00:00'),
(224, 'product_updated', 8, 32, 'Product Updated', 'Product Raghba wood intense was updated (id #189).', '{"product_id":"189","product_name":"Raghba wood intense","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:11:04+00:00', '2026-09-29T14:32:44+00:00'),
(225, 'product_updated', 8, 32, 'Product Updated', 'Product Nebras Elixir was updated (id #188).', '{"product_id":"188","product_name":"Nebras Elixir","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:24:23+00:00', '2026-09-29T14:32:44+00:00'),
(226, 'product_updated', 8, 32, 'Product Updated', 'Product Taskeen Wowie was updated (id #187).', '{"product_id":"187","product_name":"Taskeen Wowie","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:28:02+00:00', '2026-09-29T14:32:45+00:00'),
(227, 'product_updated', 8, 32, 'Product Updated', 'Product Teriaq intense was updated (id #186).', '{"product_id":"186","product_name":"Teriaq intense","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:29:48+00:00', '2026-09-29T14:32:45+00:00'),
(228, 'product_updated', 8, 32, 'Product Updated', 'Product Kaaf was updated (id #185).', '{"product_id":"185","product_name":"Kaaf","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:32:05+00:00', '2026-09-29T14:32:45+00:00'),
(234, 'product_updated', 8, 32, 'Product Updated', 'Product Dubai night Umbra was updated (id #179).', '{"product_id":"179","product_name":"Dubai night Umbra","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T12:51:36+00:00', '2026-09-29T14:32:46+00:00'),
(251, 'product_updated', 8, 32, 'Product Updated', 'Product Ignite oud was updated (id #162).', '{"product_id":"162","product_name":"Ignite oud","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T13:55:49+00:00', '2026-09-29T14:32:50+00:00'),
(257, 'product_updated', 8, 32, 'Product Updated', 'Product CIAO citrus was updated (id #154).', '{"product_id":"154","product_name":"CIAO citrus","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T14:26:30+00:00', '2026-09-29T14:32:51+00:00'),
(280, 'product_updated', 8, 32, 'Product Updated', 'Product Tomford Ombre Leather was updated (id #131).', '{"product_id":"131","product_name":"Tomford Ombre Leather","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-21T15:32:41+00:00', '2026-09-29T14:32:57+00:00'),
(320, 'stock_transfer', 8, 32, 'Stock transfer confirmed', 'Transfer TF-20260922081728-4298 of Bottle Accessories with 1 item(s) created from Kinondoni branch to Dodoma branch.', NULL, TRUE, '2026-09-22T08:17:29+00:00', '2026-09-29T14:33:05+00:00'),
(330, 'product_updated', 8, 32, 'Product Updated', 'Product Dolce & Gabbana The one was updated (id #346).', '{"product_id":"346","product_name":"Dolce & Gabbana The one","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","images","updated_at"]}', TRUE, '2026-09-22T09:37:11+00:00', '2026-09-29T14:33:08+00:00'),
(350, 'stock_deleted', 8, 32, 'Stock Record Deleted', 'Stock record deleted for product #56 (6 units @ 35,000 TZS).', '{"branch_stock_id":"67","product_id":56,"quantity":6,"selling_price":35000}', TRUE, '2026-09-24T08:57:09+00:00', '2026-09-29T14:33:11+00:00'),
(352, 'order_status_changed', 8, 32, 'Order Status Changed', 'Order ORD-20260925090404-2E73 status changed to assigned.', '{"order_id":"27","order_number":"ORD-20260925090404-2E73","total":70000}', TRUE, '2026-09-25T09:05:42+00:00', '2026-09-29T14:33:11+00:00'),
(353, 'product_updated', 8, 32, 'Product Updated', 'Product 9 pm Black was updated (id #56).', '{"product_id":"56","product_name":"9 pm Black","validated":["name","description","brand","category","sex_category","fundamental_ingredient","is_active","updated_at"]}', TRUE, '2026-09-25T12:50:36+00:00', '2026-09-29T14:33:12+00:00'),
(354, 'price_customization', 8, 32, 'Price Changed', 'Selling price changed for product #45 during stock-in: 65,000 â†’ 45,000 TZS.', '{"branch_stock_id":77,"product_id":"45","old_price":65000,"new_price":45000}', TRUE, '2026-09-25T16:03:32+00:00', '2026-09-29T14:33:12+00:00'),
(355, 'order_status_changed', 8, 19, 'Order Status Changed', 'Order ORD-20260926120327-7B01 status changed to picked.', '{"order_id":28,"order_number":"ORD-20260926120327-7B01","total":70000,"note":"order yako imechukuliwa naifanyia kazi"}', TRUE, '2026-09-26T13:19:13+00:00', '2026-09-29T14:33:12+00:00'),
(356, 'order_status_changed', 8, 32, 'Order Status Changed', 'Order ORD-20260925090404-2E73 status changed to served.', '{"order_id":27,"order_number":"ORD-20260925090404-2E73","total":70000,"note":"order yako imeshatumwa boss"}', TRUE, '2026-09-26T14:33:35+00:00', '2026-09-29T14:33:12+00:00'),
(357, 'price_customization', 8, 32, 'Price Changed', 'Selling price changed for product #45 during stock-in: 45,000 â†’ 65,000 TZS.', '{"branch_stock_id":77,"product_id":"45","old_price":45000,"new_price":65000}', TRUE, '2026-09-29T09:41:30+00:00', '2026-09-29T14:33:13+00:00'),
(358, 'order_status_changed', 8, 32, 'Order Status Changed', 'Order ORD-20260916163432-D5FA status changed to served.', '{"order_id":24,"order_number":"ORD-20260916163432-D5FA","total":90000,"note":"karibu sana boss"}', TRUE, '2026-09-29T09:45:40+00:00', '2026-09-29T14:33:13+00:00');

-- inquiries (5 rows)
INSERT INTO public.inquiries (id, user_id, branch_id, subject, message, email, phone, attachments, is_read, status, reply_message, replied_by, replied_at, created_at, updated_at, is_featured) VALUES
(3, NULL, 8, 'pongezi', 'mnafanya kazi nzuri sana', 'gideonmsuya143@gmail.com', '0682601154', NULL, TRUE, 'pending', NULL, NULL, NULL, '2026-09-07T02:26:32+00:00', '2026-09-12T03:04:53+00:00', TRUE),
(7, NULL, 8, 'Huduma zenu nmezipenda', 'perfumes classic sana kwa bei standard', 'worldchoiceperfumes@gmail.com', '0682601154', NULL, TRUE, 'pending', NULL, NULL, NULL, '2026-09-12T07:46:50+00:00', '2026-09-12T07:47:27+00:00', TRUE),
(8, NULL, 10, 'dukuduku', 'mzigo ulifika ukiwa umepasuka', 'gideonmsuya147@gmail.com', '0682601154', NULL, TRUE, 'pending', NULL, NULL, NULL, '2026-09-13T13:18:49+00:00', '2026-09-13T13:20:33+00:00', FALSE),
(9, NULL, 10, 'dukuduku', 'perfume haikai', 'gideonmsuya000@gmail.com', '0682601154', NULL, TRUE, 'pending', NULL, NULL, NULL, '2026-09-14T09:11:35+00:00', '2026-09-14T09:13:24+00:00', TRUE),
(10, NULL, 10, 'A quick note about your website recommndations', 'Hi,

Your website worldchoiceperfume.com really stood out to me. I can see some solid opportunities to build on what you already have and take things to the next level, both in terms of ROI and overall reach.

With over 26 years of experience, V Group specializes in scaling websites the right way, ensuring they''re accessible and compliant so they reach the widest possible audience.

Would you have some time this week to connect and explore this further?

Best Regards,
Manshi Sharma', 'manshis@vgroupinc.com', 'Ojwmgopo', NULL, TRUE, 'pending', NULL, NULL, NULL, '2026-09-24T09:29:38+00:00', '2026-09-28T17:15:58+00:00', FALSE);

-- news_posts (4 rows)
INSERT INTO public.news_posts (id, title, content, branch_id, author_id, image_url, is_published, created_at, updated_at, status, rejection_reason, reviewed_by, reviewed_at) VALUES
(1, 'Imagination', 'brand new product', 8, 30, 'https://res.cloudinary.com/zcmci5mi/image/upload/v1788751910/news/imigation.png_1788751909.png', TRUE, '2026-09-07T03:31:50+00:00', '2026-09-07T03:31:50+00:00', 'approved', NULL, NULL, NULL),
(12, 'COMBO', 'SECRETO AND REEF 33', 10, 38, NULL, FALSE, '2026-09-13T15:15:05+00:00', '2026-09-13T15:15:05+00:00', 'pending', NULL, NULL, NULL),
(14, 'USHAURI', 'Usipulize perfume ovyo', 10, 38, NULL, FALSE, '2026-09-14T09:17:09+00:00', '2026-09-14T09:17:09+00:00', 'pending', NULL, NULL, NULL),
(13, 'USHAURI', 'Usipulize perfume ovyo', 10, 38, NULL, FALSE, '2026-09-14T09:17:08+00:00', '2026-09-29T12:28:31+00:00', 'rejected', 'hii haina umuhimu sana boss', 39, '2026-09-29T12:28:32+00:00');

-- stock_transfers (10 rows)
INSERT INTO public.stock_transfers (id, transfer_number, stock_type, from_branch_id, to_branch_id, status, note, officer_name, officer_phone, officer_id, created_by, received_by, received_at, created_at, updated_at) VALUES
(1, 'TF-20260919170045-8E2A', 'product', 8, 10, 'in_transit', 'iutiyrrstdyutiyou', 'JOHN MWAKYUSA', '0769010940', '42333351', 29, NULL, NULL, '2026-09-19T17:00:45+00:00', '2026-09-19T17:00:45+00:00'),
(3, 'TF-20260919194801-192A', 'product', 8, 10, 'in_transit', 'hdgxdfzc,m', 'JOHN MWAKYUSA', '0769010940', '42333351', 29, NULL, NULL, '2026-09-19T19:48:01+00:00', '2026-09-19T19:48:01+00:00'),
(10, 'TF-20260922074823-2AAE', 'bottle', 8, 9, 'received', NULL, 'Gideon Msuya', '0682601154', NULL, 32, 36, '2026-09-22T07:50:00+00:00', '2026-09-22T07:48:23+00:00', '2026-09-22T07:50:00+00:00'),
(2, 'TF-20260919170247-443D', 'product', 8, 9, 'received', 'ioyturserdytuiopyotruetsrdyu', 'JOHN MWAKYUSA', '0769010940', '42333351', 29, 36, '2026-09-22T07:52:15+00:00', '2026-09-19T17:02:47+00:00', '2026-09-22T07:52:15+00:00'),
(11, 'TF-20260922081728-4298', 'bottle_accessories', 8, 9, 'received', NULL, 'Gideon Msuya', '0682601154', NULL, 32, 36, '2026-09-22T08:28:26+00:00', '2026-09-22T08:17:28+00:00', '2026-09-22T08:28:26+00:00'),
(12, 'TF-20260922082057-1D0B', 'product', 8, 9, 'received', NULL, 'Gideon Msuya', '0682601154', NULL, 32, 36, '2026-09-22T08:28:50+00:00', '2026-09-22T08:20:57+00:00', '2026-09-22T08:28:50+00:00'),
(13, 'TF-20260922082152-EE96', 'product', 8, 9, 'received', 'Re-send of item from TF-20260919170247-443D', 'Gideon Msuya', '-', NULL, 32, 36, '2026-09-22T08:28:59+00:00', '2026-09-22T08:21:52+00:00', '2026-09-22T08:28:59+00:00'),
(14, 'TF-20260922085037-2B1E', 'oil_fragrance', 8, 9, 'received', NULL, 'Gideon Msuya', '0682601154', NULL, 32, 36, '2026-09-22T08:55:44+00:00', '2026-09-22T08:50:37+00:00', '2026-09-22T08:55:44+00:00'),
(16, 'TF-20260923153236-7B85', 'bottle', 8, 9, 'received', NULL, 'Gideon Msuya', '0682601154', NULL, 32, 36, '2026-09-23T15:33:47+00:00', '2026-09-23T15:32:36+00:00', '2026-09-23T15:33:47+00:00'),
(15, 'TF-20260923124518-F1E9', 'bottle', 8, 9, 'received', NULL, 'Gideon Msuya', '0682601154', NULL, 32, 36, '2026-09-24T06:43:30+00:00', '2026-09-23T12:45:18+00:00', '2026-09-24T06:43:30+00:00');

-- stock_transfer_items (10 rows)
INSERT INTO public.stock_transfer_items (id, transfer_id, stock_type, item_index, product_id, name, volume, variant, type, color, quantity, unit_cost, unit_price, category, supplier, status, received_by, received_at, created_at, updated_at, variety_unit_price, return_reason, return_status, loss_reason, resent_transfer_id, returned_by, returned_at, damage_type, damage_reason, damage_reported_by, damage_reported_at) VALUES
(3, 3, 'product', 1, 345, 'Test perfume', '50', 'box_logo_yellow', NULL, NULL, 100, 0.0, 54000.0, 'Oil Fragrance', NULL, 'in_transit', NULL, NULL, '2026-09-19T19:48:02+00:00', '2026-09-21T08:38:34+00:00', NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(1, 1, 'product', 1, 345, 'Test perfume', '50', 'box_logo_yellow', NULL, NULL, 150, 0.0, 54000.0, 'Oil Fragrance', NULL, 'in_transit', NULL, NULL, '2026-09-19T17:00:45+00:00', '2026-09-22T07:04:31+00:00', NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 10, 'bottle', 1, NULL, NULL, '12ml', 'plain', NULL, NULL, 12, NULL, NULL, NULL, NULL, 'received', 36, '2026-09-22T07:50:00+00:00', '2026-09-22T07:48:23+00:00', '2026-09-22T07:50:00+00:00', NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 2, 'product', 1, 345, 'Test perfume', '30', 'box_logo_yellow', NULL, NULL, 20, 0.0, 54000.0, 'Oil Fragrance', NULL, 'returned', NULL, NULL, '2026-09-19T17:02:47+00:00', '2026-09-22T08:21:53+00:00', NULL, 'broken bottles', 'resent', NULL, 13, 36, '2026-09-22T07:52:15+00:00', NULL, NULL, NULL, NULL),
(17, 11, 'bottle_accessories', 1, NULL, NULL, NULL, NULL, 'straws', 'silver', 2, NULL, NULL, NULL, NULL, 'received', 36, '2026-09-22T08:28:26+00:00', '2026-09-22T08:17:28+00:00', '2026-09-22T08:28:26+00:00', NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 12, 'product', 1, 99, 'Club De Nuit', '0', '', NULL, NULL, 3, 0.0, 45000.0, 'Oil Fragrance', NULL, 'received', 36, '2026-09-22T08:28:50+00:00', '2026-09-22T08:20:58+00:00', '2026-09-22T08:28:50+00:00', NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 13, 'product', 1, 345, 'Test perfume', '30', 'box_logo_yellow', NULL, NULL, 20, 0.0, 37000.0, 'Oil Fragrance', NULL, 'received', 36, '2026-09-22T08:28:59+00:00', '2026-09-22T08:21:53+00:00', '2026-09-22T08:28:59+00:00', 37000.0, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 14, 'oil_fragrance', 1, NULL, '1 Million', '1000', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 'received', 36, '2026-09-22T08:55:44+00:00', '2026-09-22T08:50:37+00:00', '2026-09-22T08:55:44+00:00', NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(22, 16, 'bottle', 1, NULL, NULL, '12ml', 'plain', NULL, NULL, 10, NULL, NULL, NULL, NULL, 'received', 36, '2026-09-23T15:33:47+00:00', '2026-09-23T15:32:36+00:00', '2026-09-23T15:33:47+00:00', NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 15, 'bottle', 1, NULL, NULL, '100ml', 'box_logo_yellow', NULL, NULL, 1, NULL, NULL, NULL, NULL, 'returned', NULL, NULL, '2026-09-23T12:45:18+00:00', '2026-09-24T06:45:54+00:00', NULL, 'imevunjika', 'reported', NULL, NULL, 36, '2026-09-24T06:43:30+00:00', 'broken', 'poor packing of item', 32, '2026-09-24T06:45:54+00:00');

-- info_emails (7 rows)
INSERT INTO public.info_emails (id, message_id, in_reply_to, reference_ids, from_email, from_name, to_email, cc, bcc, reply_to, subject, body_text, body_html, raw_email, headers, attachment_names, has_attachments, spf_result, dkim_result, is_read, is_starred, status, thread_key, parent_id, received_at, read_at, replied_at, created_at, updated_at) VALUES
(5, '<CAOLv=VtPE4jnCeUOuQjLqDXPjRGOKCjKN19KWBmEovn+adez1w@mail.gmail.com>', NULL, NULL, 'godwinfranklin419@gmail.com', 'FRANK GODWIN', 'info@worldchoiceperfume.com', NULL, NULL, NULL, 'Greetings', 'Hello', '<div dir="auto">Hello</div>', 'Received: from mail-pj2-x0f.google.com (2607:f8b0:4864:39::f)
        by cloudflare-email.net (cloudflare) id oaJ0nKbTOqgA
        for <info@worldchoiceperfume.com>; Mon, 28 Sep 2026 15:42:37 +0000
ARC-Seal: i=2; a=rsa-sha256; s=cf2024-1; d=cloudflare-email.net; cv=pass;
	b=RsAF+BZpXrXXv8KIwSOOUkzWEfYETpKgB9UDoxaPPIPavAIsEisH5MAcY1+6fW//k1Q7X4USx
	TwX2KmONPuHUJIXYWp/1DhTYgR/+lTzMdLxzvuk8BFZcF4Rgmg1hKrFeDmC4y72Pgf2e/S1jdZD
	7w3gx9ZSQoQ2hjFsa31x7HVH6L2OJFdnzB3c0mpAFBjtKcFk3cWbfbiprvzgBw+924m0BtHaI7w
	nIdy1vp/ndYsy2jJG7uGqWLJsmFfaczVEML92JZJr8nMeo8cOgT9K9ggWPw8jJNWUbCINoiuajJ
	Ma7jAPXW/6AqL7TP5/95iO0VhGpYR0kYzSUvJ+pSXCmw==;
ARC-Message-Signature: i=2; a=rsa-sha256; s=cf2024-1; d=cloudflare-email.net; c=relaxed/relaxed;
	h=To:Subject:Date:From:from:reply-to:cc:resent-date:resent-from:resent-to
	:resent-cc:in-reply-to:references:list-id:list-help:list-subscribe
	:list-post:list-owner:list-archive; t=1790610157; x=1791214957; bh=fP1oYezc
	jMSXWXZ39XlM98bg4KmpoDhg9rjMY1U9Dng=; b=dB/dPWZXIjKAPCviUX7TMNfpVYOOZbIP0Rg
	rchCVySaGtL/u9ZwNgMfIO3EqIpBviUJC5rRv4HmZr2aLjWM3Vv5K6AyezKbxaAqqcqHzwEznY7
	BkLGG5TAipGaZB8a9ZE44rSSTeKgSTVpleyXwdNRgJ+5swREolTkbc49mWPVytySTWvWWE2dS8k
	7YXy70iSO63L6yVw2UScj5dU/vS3C1OMGMSpezfj2YXUVnb9XAhgNXA4JQzSlu0iWgEDRIaraq6
	Uu+CAEZ0P09DdewsRZEO+durTB/PtwB3OFY52lgigyqPEjuX8BknJNP/B2VxKLoF1nLmKlN+kbg
	NiowF4A==;
ARC-Authentication-Results: i=2; mx.cloudflare.net;
	dkim=pass header.d=gmail.com header.s=20251104 header.b=KuMeFTlf;
	dmarc=pass header.from=gmail.com policy.dmarc=none;
	spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-pj2-x0f.google.com) smtp.helo=mail-pj2-x0f.google.com;
	spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:39::f as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com;
	arc=pass smtp.remote-ip="2607:f8b0:4864:39::f"
Received-SPF: pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:39::f as permitted sender)
	receiver=mx.cloudflare.net; client-ip=2607:f8b0:4864:39::f; envelope-from="godwinfranklin419@gmail.com"; helo=mail-pj2-x0f.google.com;
Authentication-Results: mx.cloudflare.net;
	dkim=pass header.d=gmail.com header.s=20251104 header.b=KuMeFTlf;
	dmarc=pass header.from=gmail.com policy.dmarc=none;
	spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-pj2-x0f.google.com) smtp.helo=mail-pj2-x0f.google.com;
	spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:39::f as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com;
	arc=pass smtp.remote-ip="2607:f8b0:4864:39::f"
X-CF-SpamH-Score: 0
Received: by mail-pj2-x0f.google.com with SMTP id d9443c01a7336-2d93ff61046so16696115ad.3
        for <info@worldchoiceperfume.com>; Mon, 28 Sep 2026 08:42:37 -0700 (PDT)
ARC-Seal: i=1; a=rsa-sha256; t=1790610157; cv=none;
        d=google.com; s=arc-20260327;
        b=m4bd2KRfPE660hx1gh0xdKfXmg/EqywZNOq2rf/VOWDELUDfCOzGaNBrbyElVSaiPj
         aZbFW/ha/pQR/DI/8dgLN4byFyM6IL8qH4wnCNs5bw5l2XLqZrg3hB3MhJ8/bLhVtn1C
         6UwSgRhavGnsefuUFxukf06VItqmvDJRnXvS9Hg++B3V3bsScbqPnwY/y+mzxuCOT3D2
         08FWFHumNQS/6l809+5SGJvl0pqI5XDWBcUgf/PuVbs4lDRa+RXMoxVhoJUt5PJEwJI+
         9jor6yJaBEzW51oBiQPSEzIwO2VKrxjCwEC+LGRBnrakra+Vn6OUnuFhRARnpDB97qPV
         KQOg==
ARC-Message-Signature: i=1; a=rsa-sha256; c=relaxed/relaxed; d=google.com; s=arc-20260327;
        h=to:subject:message-id:date:from:mime-version:dkim-signature;
        bh=fP1oYezcjMSXWXZ39XlM98bg4KmpoDhg9rjMY1U9Dng=;
        fh=nniGTCdcezPCCWQcYQFtt+TqrAdcQ+jBfMQuxKQBEts=;
        b=R6XbFCMJJr+zBf5ltAMtr8GCRLlJyl4ssJWXJ5G3A+nlWrRzKSrU5RcCFih/KnygLO
         WQYgJdXXyXXnagMFKIrbQWVQT+ETqJthvSoeFJZqizhmyslVhklSnTn8JK0WaTTIMZ3B
         Z8DM8EtCNCZO47Uk4RdWs2dQTPwp/GnPSsQdIuAfVRRBpF7cMQWC8TuiatGi5sXy+8xs
         cDI9OhwSEHUDCwndDalOzlka1ymVolWdRCJA9sMg8Vg4w4qZqVbqaOdgUlBDNEG2P8GW
         ofpLl8OD5/t+7Kap0BhjAqgwER8kD4JkD0eTDEAXdS4vIiWswyPW6DnfhBfME9fguAap
         yi4g==;
        darn=worldchoiceperfume.com
ARC-Authentication-Results: i=1; mx.google.com; arc=none
DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
        d=gmail.com; s=20251104; t=1790610157; x=1791214957; darn=worldchoiceperfume.com;
        h=content-type:to:subject:message-id:date:from:mime-version:from:to
         :cc:subject:date:message-id:reply-to:content-type;
        bh=fP1oYezcjMSXWXZ39XlM98bg4KmpoDhg9rjMY1U9Dng=;
        b=KuMeFTlfDcfd1GOmuhTgTj+tEQ1AlCFEe/wsm0w7WOcA0fG/s5uoQjrmRLlC1Y5Zmm
         r548dmGgRFTzraJXGBaQcPnSTZ5LrzIzgmZDzQcx9HtaFRaorozUauu7l8TT8dtQUmGU
         6j5soFHQL7CVhcPfoQ5BEZING+Sm/Z3Y+HR5x2qtHyVsK58VmMJC2me51xQ6eagLW2H0
         S6Rt/b6jquMYhOoCSs4qmwU/MTeeldAaZoV0PlMLLgk60EGkW1wBflctSxROIWAkwM3O
         BAQaH9eaMlMZAbU8x72B2B16RmzIuA9KksVrLwWROPz89W5Cmv9+Zzg9orGCXOWh1CEJ
         VW4g==
X-Google-DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
        d=1e100.net; s=20260707; t=1790610157; x=1791214957;
        h=content-type:to:subject:message-id:date:from:mime-version:x-gm-gg
         :x-gm-message-state:from:to:cc:subject:date:message-id:reply-to
         :content-type;
        bh=fP1oYezcjMSXWXZ39XlM98bg4KmpoDhg9rjMY1U9Dng=;
        b=KiE1Bp1Zn7YOUgn+i7aO3sU8HqFpfpos9wDBLYMXLRGsCKHjaFKNYViZ6C8NE6BNSQ
         JiXahtmWtPf+uqumMmvACQVkmzb+kpRFK58jlXlWtL5QkIpI0oltTFh7V8RYNPo93CY0
         /S88U9OgcBYaJjMgqegiCby8tGdI56FCziOZZ5sYNMfgCpoHtPZQoHtK9qD+c6+e6FY1
         CAjSlsY0RPuouREGTzy09ZLA/At3xpryomVClUYSk79cMgCvCr+kyhVpRUZs9MY2hcfw
         2qd3AjKvgnkz5bdgdyJ+CuNca3q49yH1wH/ta5Far+SSLMYt3dCE9MAttbtOhnjiMimC
         zAyA==
X-Gm-Message-State: AFq9FYKUDuTkfk5IMYw2+J0FSHcqfBizwx6X+Qx8mAEc07F6VYbnSXGl
	EQ1ZNUVK29hgS0Bv2FhR+zu4zc6axqetVUDh5SPg+9qz4nbUpkV8Ee5QyL9D3PWYG77UJ2af8ME
	vhyx2MMPi5vQg3Bg6MXsy1QsLq6EfowlWVf0u
X-Gm-Gg: AYBFou2+pzJ2DdsQ6tisKVve036GQoOXKwoxljwGLu2LtMQM19Qg9i+JCxg03OnVmPc
	/HmSXIQlnsaKCf4DlUb2Astlzh1Bf2RV93VyDvMsHNMieH3i/eEU/7aaXErYKEha/LEB8ODs18q
	QX3LqWy4AHcsCMdy+rTkk4EOekVHvp1mYN83gbr/8yRc+9A12TpTZ4G8sC9bFUcoOviIlo/EQ91
	ROs4lfznzpEaVRHWOGlkl53VPvxER6kbXXbqFsCuqZnOh1gOFj9mkXWEmzc9KMSa7hXOP3iyWQy
	ENPFYecNC4gBCLgmITbpkrEx1tod8wo+f6/rdpvs+D3Yq3eapxfU1ib1StR93l1vCVlshQ==
X-Received: by 2002:a05:701b:42d1:10b0:143:4710:a84e with SMTP id
 a92af1059eb24-146cec4be1fmr9957017c88.15.1790605045607; Mon, 28 Sep 2026
 07:17:25 -0700 (PDT)
MIME-Version: 1.0
From: FRANK GODWIN <godwinfranklin419@gmail.com>
Date: Mon, 28 Sep 2026 17:17:12 +0300
X-Gm-Features: AclHuK8WhcwxwbDewYUaf6SDSGp3IwyGxauCPODiO8qhIgoP8OpQ79fsxkxOmOE
Message-ID: <CAOLv=VtPE4jnCeUOuQjLqDXPjRGOKCjKN19KWBmEovn+adez1w@mail.gmail.com>
Subject: Greetings
To: info@worldchoiceperfume.com
Content-Type: multipart/alternative; boundary="000000000000ad3056065c8bb904"

--000000000000ad3056065c8bb904
Content-Type: text/plain; charset="UTF-8"

Hello

--000000000000ad3056065c8bb904
Content-Type: text/html; charset="UTF-8"

<div dir="auto">Hello</div>

--000000000000ad3056065c8bb904--', '{"to": "info@worldchoiceperfume.com", "date": "Mon, 28 Sep 2026 17:17:12 +0300", "from": "FRANK GODWIN <godwinfranklin419@gmail.com>", "subject": "Greetings", "x-gm-gg": "AYBFou2+pzJ2DdsQ6tisKVve036GQoOXKwoxljwGLu2LtMQM19Qg9i+JCxg03OnVmPc /HmSXIQlnsaKCf4DlUb2Astlzh1Bf2RV93VyDvMsHNMieH3i/eEU/7aaXErYKEha/LEB8ODs18q QX3LqWy4AHcsCMdy+rTkk4EOekVHvp1mYN83gbr/8yRc+9A12TpTZ4G8sC9bFUcoOviIlo/EQ91 ROs4lfznzpEaVRHWOGlkl53VPvxER6kbXXbqFsCuqZnOh1gOFj9mkXWEmzc9KMSa7hXOP3iyWQy ENPFYecNC4gBCLgmITbpkrEx1tod8wo+f6/rdpvs+D3Yq3eapxfU1ib1StR93l1vCVlshQ==", "arc-seal": "i=1; a=rsa-sha256; t=1790610157; cv=none;", "received": "by mail-pj2-x0f.google.com with SMTP id d9443c01a7336-2d93ff61046so16696115ad.3", "message-id": "<CAOLv=VtPE4jnCeUOuQjLqDXPjRGOKCjKN19KWBmEovn+adez1w@mail.gmail.com>", "x-received": "by 2002:a05:701b:42d1:10b0:143:4710:a84e with SMTP id a92af1059eb24-146cec4be1fmr9957017c88.15.1790605045607; Mon, 28 Sep 2026 07:17:25 -0700 (PDT)", "content-type": "multipart/alternative; boundary=\"000000000000ad3056065c8bb904\"", "mime-version": "1.0", "received-spf": "pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:39::f as permitted sender) receiver=mx.cloudflare.net; client-ip=2607:f8b0:4864:39::f; envelope-from=\"godwinfranklin419@gmail.com\"; helo=mail-pj2-x0f.google.com;", "x-gm-features": "AclHuK8WhcwxwbDewYUaf6SDSGp3IwyGxauCPODiO8qhIgoP8OpQ79fsxkxOmOE", "dkim-signature": "v=1; a=rsa-sha256; c=relaxed/relaxed; d=gmail.com; s=20251104; t=1790610157; x=1791214957; darn=worldchoiceperfume.com; h=content-type:to:subject:message-id:date:from:mime-version:from:to :cc:subject:date:message-id:reply-to:content-type; bh=fP1oYezcjMSXWXZ39XlM98bg4KmpoDhg9rjMY1U9Dng=; b=KuMeFTlfDcfd1GOmuhTgTj+tEQ1AlCFEe/wsm0w7WOcA0fG/s5uoQjrmRLlC1Y5Zmm r548dmGgRFTzraJXGBaQcPnSTZ5LrzIzgmZDzQcx9HtaFRaorozUauu7l8TT8dtQUmGU 6j5soFHQL7CVhcPfoQ5BEZING+Sm/Z3Y+HR5x2qtHyVsK58VmMJC2me51xQ6eagLW2H0 S6Rt/b6jquMYhOoCSs4qmwU/MTeeldAaZoV0PlMLLgk60EGkW1wBflctSxROIWAkwM3O BAQaH9eaMlMZAbU8x72B2B16RmzIuA9KksVrLwWROPz89W5Cmv9+Zzg9orGCXOWh1CEJ VW4g==", "x-cf-spamh-score": "0 for <info@worldchoiceperfume.com>; Mon, 28 Sep 2026 08:42:37 -0700 (PDT) d=google.com; s=arc-20260327; b=m4bd2KRfPE660hx1gh0xdKfXmg/EqywZNOq2rf/VOWDELUDfCOzGaNBrbyElVSaiPj aZbFW/ha/pQR/DI/8dgLN4byFyM6IL8qH4wnCNs5bw5l2XLqZrg3hB3MhJ8/bLhVtn1C 6UwSgRhavGnsefuUFxukf06VItqmvDJRnXvS9Hg++B3V3bsScbqPnwY/y+mzxuCOT3D2 08FWFHumNQS/6l809+5SGJvl0pqI5XDWBcUgf/PuVbs4lDRa+RXMoxVhoJUt5PJEwJI+ 9jor6yJaBEzW51oBiQPSEzIwO2VKrxjCwEC+LGRBnrakra+Vn6OUnuFhRARnpDB97qPV KQOg== h=to:subject:message-id:date:from:mime-version:dkim-signature; bh=fP1oYezcjMSXWXZ39XlM98bg4KmpoDhg9rjMY1U9Dng=; fh=nniGTCdcezPCCWQcYQFtt+TqrAdcQ+jBfMQuxKQBEts=; b=R6XbFCMJJr+zBf5ltAMtr8GCRLlJyl4ssJWXJ5G3A+nlWrRzKSrU5RcCFih/KnygLO WQYgJdXXyXXnagMFKIrbQWVQT+ETqJthvSoeFJZqizhmyslVhklSnTn8JK0WaTTIMZ3B Z8DM8EtCNCZO47Uk4RdWs2dQTPwp/GnPSsQdIuAfVRRBpF7cMQWC8TuiatGi5sXy+8xs cDI9OhwSEHUDCwndDalOzlka1ymVolWdRCJA9sMg8Vg4w4qZqVbqaOdgUlBDNEG2P8GW ofpLl8OD5/t+7Kap0BhjAqgwER8kD4JkD0eTDEAXdS4vIiWswyPW6DnfhBfME9fguAap yi4g==; darn=worldchoiceperfume.com", "x-gm-message-state": "AFq9FYKUDuTkfk5IMYw2+J0FSHcqfBizwx6X+Qx8mAEc07F6VYbnSXGl EQ1ZNUVK29hgS0Bv2FhR+zu4zc6axqetVUDh5SPg+9qz4nbUpkV8Ee5QyL9D3PWYG77UJ2af8ME vhyx2MMPi5vQg3Bg6MXsy1QsLq6EfowlWVf0u", "arc-message-signature": "i=1; a=rsa-sha256; c=relaxed/relaxed; d=google.com; s=arc-20260327;", "authentication-results": "mx.cloudflare.net; dkim=pass header.d=gmail.com header.s=20251104 header.b=KuMeFTlf; dmarc=pass header.from=gmail.com policy.dmarc=none; spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-pj2-x0f.google.com) smtp.helo=mail-pj2-x0f.google.com; spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:39::f as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com; arc=pass smtp.remote-ip=\"2607:f8b0:4864:39::f\"", "x-google-dkim-signature": "v=1; a=rsa-sha256; c=relaxed/relaxed; d=1e100.net; s=20260707; t=1790610157; x=1791214957; h=content-type:to:subject:message-id:date:from:mime-version:x-gm-gg :x-gm-message-state:from:to:cc:subject:date:message-id:reply-to :content-type; bh=fP1oYezcjMSXWXZ39XlM98bg4KmpoDhg9rjMY1U9Dng=; b=KiE1Bp1Zn7YOUgn+i7aO3sU8HqFpfpos9wDBLYMXLRGsCKHjaFKNYViZ6C8NE6BNSQ JiXahtmWtPf+uqumMmvACQVkmzb+kpRFK58jlXlWtL5QkIpI0oltTFh7V8RYNPo93CY0 /S88U9OgcBYaJjMgqegiCby8tGdI56FCziOZZ5sYNMfgCpoHtPZQoHtK9qD+c6+e6FY1 CAjSlsY0RPuouREGTzy09ZLA/At3xpryomVClUYSk79cMgCvCr+kyhVpRUZs9MY2hcfw 2qd3AjKvgnkz5bdgdyJ+CuNca3q49yH1wH/ta5Far+SSLMYt3dCE9MAttbtOhnjiMimC zAyA==", "arc-authentication-results": "i=1; mx.google.com; arc=none"}', NULL, FALSE, 'none', 'pass', TRUE, FALSE, 'new', '<CAOLv=VtPE4jnCeUOuQjLqDXPjRGOKCjKN19KWBmEovn+adez1w@mail.gmail.com>', NULL, '2026-09-28T14:17:12+00:00', '2026-09-28T16:12:27+00:00', NULL, '2026-09-28T14:17:12+00:00', '2026-09-28T16:12:27+00:00'),
(4, '<CAOLv=VuoxRU5h4cT=EHv0XVxM9CrMa9xiaC0FVpDH-Y7Sm5Vyg@mail.gmail.com>', '<CAOLv=VuAD0xOd6WytfO=n4SZhm-BjxkkGmyG-73yG3xRAFn2ww@mail.gmail.com>', '<CAOLv=VtPE4jnCeUOuQjLqDXPjRGOKCjKN19KWBmEovn+adez1w@mail.gmail.com> <CAOLv=VuAD0xOd6WytfO=n4SZhm-BjxkkGmyG-73yG3xRAFn2ww@mail.gmail.com>', 'godwinfranklin419@gmail.com', 'FRANK GODWIN', 'info@worldchoiceperfume.com', NULL, NULL, NULL, 'Fwd: Greetings', '---------- Forwarded message ---------
From: FRANK GODWIN <godwinfranklin419@gmail.com>
Date: Mon, 28 Sep 2026, 17:33
Subject: Fwd: Greetings
To: <info@worldchoiceperfume.com>



---------- Forwarded message ---------
From: FRANK GODWIN <godwinfranklin419@gmail.com>
Date: Mon, 28 Sep 2026, 17:17
Subject: Greetings
To: <info@worldchoiceperfume.com>


Hello', '<div dir="auto"></div><br><div class="gmail_quote gmail_quote_container"><div dir="ltr" class="gmail_attr">---------- Forwarded message ---------<br>From: <strong class="gmail_sendername" dir="auto">FRANK GODWIN</strong> <span dir="auto">&lt;<a href="mailto:godwinfranklin419@gmail.com">godwinfranklin419@gmail.com</a>&gt;</span><br>Date: Mon, 28 Sep 2026, 17:33<br>Subject: Fwd: Greetings<br>To:  &lt;<a href="mailto:info@worldchoiceperfume.com">info@worldchoiceperfume.com</a>&gt;<br></div><br><br><div dir="auto"></div><br><div class="gmail_quote"><div dir="ltr" class="gmail_attr">---------- Forwarded message ---------<br>From: <strong class="gmail_sendername" dir="auto">FRANK GODWIN</strong> <span dir="auto">&lt;<a href="mailto:godwinfranklin419@gmail.com" target="_blank" rel="noreferrer">godwinfranklin419@gmail.com</a>&gt;</span><br>Date: Mon, 28 Sep 2026, 17:17<br>Subject: Greetings<br>To:  &lt;<a href="mailto:info@worldchoiceperfume.com" target="_blank" rel="noreferrer">info@worldchoiceperfume.com</a>&gt;<br></div><br><br><div dir="auto">Hello</div>
</div>
</div>', 'Received: from mail-oi2-x0e.google.com (2607:f8b0:4864:32::e)
        by cloudflare-email.net (cloudflare) id xvJaiKgmlCOD
        for <info@worldchoiceperfume.com>; Mon, 28 Sep 2026 15:42:05 +0000
ARC-Seal: i=2; a=rsa-sha256; s=cf2024-1; d=cloudflare-email.net; cv=pass;
	b=GRVH2KdxpGzvfNkMGcqfqRpMufZcBwnO7CzBfCbzdjinuLgEpw/5Y4c0DlcDomYQ/O9Ie72WR
	4lWi0iYfyHRaK//gg+2wtFObQaqG2ZuXQwB75EFnWlja/cO1T/f+ZsrpaHGFweesoZ7u82DaZHG
	PlAoENLvuoBNRpCSiRrD6JlfP/FGXeR/dPIWJqsNyi2xd33qXNpAJqME01vWHAaEtsO85kvmi0K
	jeGwAxwTYe2s+gKS83XaJUaK7Qm8HOIDxaej2TjyoDTKcasOqeRW5j8hi7DzdlqY6m/ceCJOkVh
	551ru7LKNBseIu3o/xwtv8SmeAfYuFVlSp/30zh30/6g==;
ARC-Message-Signature: i=2; a=rsa-sha256; s=cf2024-1; d=cloudflare-email.net; c=relaxed/relaxed;
	h=To:Subject:Date:From:In-Reply-To:References:from:reply-to:cc:resent-date
	:resent-from:resent-to:resent-cc:list-id:list-help:list-subscribe
	:list-post:list-owner:list-archive; t=1790610125; x=1791214925; bh=vGgAZjJR
	9ROA7M0qdH8o5WNBp2HcimmZj2CoTGN3uPw=; b=bpeWLW+v4+Q8r3S/zTdfzvr4dbhAX8OYbfq
	aqzzHHX4uSxY0YSvGnTVnJ12oFT2tsR7p5r3RRaDbKB6k2pcWNWeeGe7GLCR6rBDVdO6wjnPaQ8
	+gXds6x2gAuWRdQqv3uHsxRfeN6/RywR3PHlx2IMz91yZxMYIAsfL6UmXfh/1zk8ii1uTMpVvpe
	gWV//qrOZ5qGxq4K5EkGMCcsuqAE2SALoLslomaF1m96859N23icVL8M8z++/Q0WChds0cWCm4e
	GeLK9sCaGmcK7383eoH8uCsz5Bj/75jrfcFzAPB2fPyW2W6t6WeUrfXyFqMldnM9bIzfclbeftf
	AcSUUKQ==;
ARC-Authentication-Results: i=2; mx.cloudflare.net;
	dkim=pass header.d=gmail.com header.s=20251104 header.b=ACehkGAf;
	dmarc=pass header.from=gmail.com policy.dmarc=none;
	spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-oi2-x0e.google.com) smtp.helo=mail-oi2-x0e.google.com;
	spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:32::e as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com;
	arc=pass smtp.remote-ip="2607:f8b0:4864:32::e"
Received-SPF: pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:32::e as permitted sender)
	receiver=mx.cloudflare.net; client-ip=2607:f8b0:4864:32::e; envelope-from="godwinfranklin419@gmail.com"; helo=mail-oi2-x0e.google.com;
Authentication-Results: mx.cloudflare.net;
	dkim=pass header.d=gmail.com header.s=20251104 header.b=ACehkGAf;
	dmarc=pass header.from=gmail.com policy.dmarc=none;
	spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-oi2-x0e.google.com) smtp.helo=mail-oi2-x0e.google.com;
	spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:32::e as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com;
	arc=pass smtp.remote-ip="2607:f8b0:4864:32::e"
X-CF-SpamH-Score: 0
Received: by mail-oi2-x0e.google.com with SMTP id 5614622812f47-4e6eede34baso916942b6e.0
        for <info@worldchoiceperfume.com>; Mon, 28 Sep 2026 08:42:05 -0700 (PDT)
ARC-Seal: i=1; a=rsa-sha256; t=1790610125; cv=none;
        d=google.com; s=arc-20260327;
        b=Xo4KN02wifMEMPD2NgYvs1UlWgVNltN6VyG3af+AwfSMOwnuv0KQg4NQCju4FccrVS
         7Mzrd6YtAii//aa7wP356lXMz8Fd9x81sBQjORxCtR7rfv2h8SfuhiXQlA94ICCk2fuu
         XVKfA9/GpzF4rZ/8R+jxILBuCiGRfk45OnrXQ7Sct0DtnDxaUmGO/4yyj+IOy1PsnR81
         UAc8hookSwZzS07SeVipenDko0YPKU8N4ngfeaeUVDR+D42XVC0kr7ubj7++UG74M8hU
         XRKtj8lXjdYmG+k42SvKCo9QKL1IMYk3jwsMgSonMJnQ3/a8LQh+T3MF8FSTP93sEqEU
         zZRg==
ARC-Message-Signature: i=1; a=rsa-sha256; c=relaxed/relaxed; d=google.com; s=arc-20260327;
        h=to:subject:message-id:date:from:in-reply-to:references:mime-version
         :dkim-signature;
        bh=vGgAZjJR9ROA7M0qdH8o5WNBp2HcimmZj2CoTGN3uPw=;
        fh=nniGTCdcezPCCWQcYQFtt+TqrAdcQ+jBfMQuxKQBEts=;
        b=YFC3KskfmMtLv1Z3WXrkBkV6MisVyrCLwIGUCHeb/z8xRCZ55LB+84KNz0dtDogHoT
         klbEZb1/xcUByNo0JhWD5nBwxl5bXmSxdnADV0vkF7gNtqrHfYi+W5h2v2f+p71w1HqZ
         BYb8c7i88XtLgCSEXG93+a2YpVBVAAby55MZ4XPVOSAO5YuyvVA59NRvlzdonyQVhmkC
         nwSaV7JQ29tUFr2npWXAZMWu5Kl2UksRbS6rHzWZtMf85271y9BODXbxV25RT6b9oCwd
         kV0cUlfT/JRcUJZE3lHftDejDqzifUp2gAeN6jqYJMLYHWtI/fIKSvtjxyd6agNALdBr
         SC0w==;
        darn=worldchoiceperfume.com
ARC-Authentication-Results: i=1; mx.google.com; arc=none
DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
        d=gmail.com; s=20251104; t=1790610125; x=1791214925; darn=worldchoiceperfume.com;
        h=content-type:to:subject:message-id:date:from:in-reply-to:references
         :mime-version:from:to:cc:subject:date:message-id:reply-to
         :content-type;
        bh=vGgAZjJR9ROA7M0qdH8o5WNBp2HcimmZj2CoTGN3uPw=;
        b=ACehkGAfug34xBlQt1W+ZiVhtNksYG8Mk/TXbygA4wQPHDFYhqiZPMaAFVNc5HozsN
         0ye9C5AGwX7BXQF3isbRsv05Mt2xVq6PcS13ZTDj0FNlLMNCMwm6tuaA7cFpYXXAEOs1
         S4Ig0e9TdVWhk47kURLMQo4RUQPAsc44TlMSPw530uxTay9G6hcXp9+06Gzv+2x2rt06
         itQZQazJ0bAK1K6D3YdrNpCo1bFYZah/drJbCnRo379EryZbISikRH41XZLRIXGwCWOK
         yIF5hCefpyD+iiT5MBwWS2YvX271a7XWDBWwaQ1FfzfLbTVB06d+JG4cRPDRVnmfD0e5
         5rqA==
X-Google-DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
        d=1e100.net; s=20260707; t=1790610125; x=1791214925;
        h=content-type:to:subject:message-id:date:from:in-reply-to:references
         :mime-version:x-gm-gg:x-gm-message-state:from:to:cc:subject:date
         :message-id:reply-to:content-type;
        bh=vGgAZjJR9ROA7M0qdH8o5WNBp2HcimmZj2CoTGN3uPw=;
        b=aFkNxFZUvVdhFCCIqvDPQcXN0J+Akfq3VHCFu0pgT/aoIYnNu+fmpb03o/813sfRkk
         HUbOHm+aELlo01zXNaEXsfdGqzQLo7CDnByLGM/CbQ6Qz12PN6Dr5bAxD2tducQWp5DH
         Fc2rqHKAvj1dTFq/YDRC09ge026JG0Q8S0jmPvlS9x6LwUUUZ2Qa7ms8s3Q/me17rKlt
         whmLFIFcsgekTJllpBiaGQNSCRV9vgKmziOG2jmUn8T3pm4B8wKCeIj7p6uJgyRbIdSL
         +NvAe+1PpnhLZQCshwtppklRHiuVyfs387DpjpuiYakbDKqpV7dJgwdgs5Bnp6ChyX6p
         1KbA==
X-Gm-Message-State: AFuF++m3dtR12VmUPAqBaORSNjD6ob6QCXLByX7zoBHLoHXFdXq9BxiM
	AX/Fl7Pr6/1orZiZz3bmx3yCBPGzN0HkyJF93+jNFdqoMM/eXRzDl7ZwOvFLjsKAntsTpc3GuyH
	hU85aWcjM6qApEw+E672/YaRcO09145M967V/
X-Gm-Gg: AYBFou3tecOoAnr6JLVvxmEkWnuKAXv/PaJPwYKQ89RrECM8wftIbfcRxqBqLbWWtaz
	ME3IxC3Kg3azrzjpcutGVqrOvdpkwdm+SJ3qmOkw3UBOPzfvMG6YP9dSNOgnxsuW0XVpCIVv215
	FrjFzAbO7IWEIVSILzL5/gV6SEGSffYOHro9nT57adhSj6UL0+Q1X2XHeZm9Dbx35pSnLbOBJeG
	08aCWogr84Pv+kAEFL4nj4/Tu5D5TwbTOHGXATQOukx/c0/x6Sr6OyGTF/6xRuMELxYJn3GNjL4
	6oWTah+XJhKcnMeKMv2UviooDfhrgWaJOfM98Ky1KRCo68aEu3bVUkgGsAE2OyjYzFop
X-Received: by 2002:a05:701b:2704:b0:148:4d6c:201e with SMTP id
 a92af1059eb24-1484d6c27d7mr7593617c88.23.1790606425503; Mon, 28 Sep 2026
 07:40:25 -0700 (PDT)
MIME-Version: 1.0
References: <CAOLv=VtPE4jnCeUOuQjLqDXPjRGOKCjKN19KWBmEovn+adez1w@mail.gmail.com>
 <CAOLv=VuAD0xOd6WytfO=n4SZhm-BjxkkGmyG-73yG3xRAFn2ww@mail.gmail.com>
In-Reply-To: <CAOLv=VuAD0xOd6WytfO=n4SZhm-BjxkkGmyG-73yG3xRAFn2ww@mail.gmail.com>
From: FRANK GODWIN <godwinfranklin419@gmail.com>
Date: Mon, 28 Sep 2026 17:40:11 +0300
X-Gm-Features: AclHuK8Ef_gUJDwAmEgoVIV3C93IFnFKwnCDPkOsJQY5B0L0atRS5jPogpP08RE
Message-ID: <CAOLv=VuoxRU5h4cT=EHv0XVxM9CrMa9xiaC0FVpDH-Y7Sm5Vyg@mail.gmail.com>
Subject: Fwd: Greetings
To: info@worldchoiceperfume.com
Content-Type: multipart/alternative; boundary="000000000000ecbb3a065c8c0b0e"

--000000000000ecbb3a065c8c0b0e
Content-Type: text/plain; charset="UTF-8"

---------- Forwarded message ---------
From: FRANK GODWIN <godwinfranklin419@gmail.com>
Date: Mon, 28 Sep 2026, 17:33
Subject: Fwd: Greetings
To: <info@worldchoiceperfume.com>



---------- Forwarded message ---------
From: FRANK GODWIN <godwinfranklin419@gmail.com>
Date: Mon, 28 Sep 2026, 17:17
Subject: Greetings
To: <info@worldchoiceperfume.com>


Hello

--000000000000ecbb3a065c8c0b0e
Content-Type: text/html; charset="UTF-8"
Content-Transfer-Encoding: quoted-printable

<div dir=3D"auto"></div><br><div class=3D"gmail_quote gmail_quote_container=
"><div dir=3D"ltr" class=3D"gmail_attr">---------- Forwarded message ------=
---<br>From: <strong class=3D"gmail_sendername" dir=3D"auto">FRANK GODWIN</=
strong> <span dir=3D"auto">&lt;<a href=3D"mailto:godwinfranklin419@gmail.co=
m">godwinfranklin419@gmail.com</a>&gt;</span><br>Date: Mon, 28 Sep 2026, 17=
:33<br>Subject: Fwd: Greetings<br>To:  &lt;<a href=3D"mailto:info@worldchoi=
ceperfume.com">info@worldchoiceperfume.com</a>&gt;<br></div><br><br><div di=
r=3D"auto"></div><br><div class=3D"gmail_quote"><div dir=3D"ltr" class=3D"g=
mail_attr">---------- Forwarded message ---------<br>From: <strong class=3D=
"gmail_sendername" dir=3D"auto">FRANK GODWIN</strong> <span dir=3D"auto">&l=
t;<a href=3D"mailto:godwinfranklin419@gmail.com" target=3D"_blank" rel=3D"n=
oreferrer">godwinfranklin419@gmail.com</a>&gt;</span><br>Date: Mon, 28 Sep =
2026, 17:17<br>Subject: Greetings<br>To:  &lt;<a href=3D"mailto:info@worldc=
hoiceperfume.com" target=3D"_blank" rel=3D"noreferrer">info@worldchoiceperf=
ume.com</a>&gt;<br></div><br><br><div dir=3D"auto">Hello</div>
</div>
</div>

--000000000000ecbb3a065c8c0b0e--', '{"to": "info@worldchoiceperfume.com", "date": "Mon, 28 Sep 2026 17:40:11 +0300", "from": "FRANK GODWIN <godwinfranklin419@gmail.com>", "subject": "Fwd: Greetings", "x-gm-gg": "AYBFou3tecOoAnr6JLVvxmEkWnuKAXv/PaJPwYKQ89RrECM8wftIbfcRxqBqLbWWtaz ME3IxC3Kg3azrzjpcutGVqrOvdpkwdm+SJ3qmOkw3UBOPzfvMG6YP9dSNOgnxsuW0XVpCIVv215 FrjFzAbO7IWEIVSILzL5/gV6SEGSffYOHro9nT57adhSj6UL0+Q1X2XHeZm9Dbx35pSnLbOBJeG 08aCWogr84Pv+kAEFL4nj4/Tu5D5TwbTOHGXATQOukx/c0/x6Sr6OyGTF/6xRuMELxYJn3GNjL4 6oWTah+XJhKcnMeKMv2UviooDfhrgWaJOfM98Ky1KRCo68aEu3bVUkgGsAE2OyjYzFop", "arc-seal": "i=1; a=rsa-sha256; t=1790610125; cv=none;", "received": "by mail-oi2-x0e.google.com with SMTP id 5614622812f47-4e6eede34baso916942b6e.0", "message-id": "<CAOLv=VuoxRU5h4cT=EHv0XVxM9CrMa9xiaC0FVpDH-Y7Sm5Vyg@mail.gmail.com>", "references": "<CAOLv=VtPE4jnCeUOuQjLqDXPjRGOKCjKN19KWBmEovn+adez1w@mail.gmail.com> <CAOLv=VuAD0xOd6WytfO=n4SZhm-BjxkkGmyG-73yG3xRAFn2ww@mail.gmail.com>", "x-received": "by 2002:a05:701b:2704:b0:148:4d6c:201e with SMTP id a92af1059eb24-1484d6c27d7mr7593617c88.23.1790606425503; Mon, 28 Sep 2026 07:40:25 -0700 (PDT)", "in-reply-to": "<CAOLv=VuAD0xOd6WytfO=n4SZhm-BjxkkGmyG-73yG3xRAFn2ww@mail.gmail.com>", "content-type": "multipart/alternative; boundary=\"000000000000ecbb3a065c8c0b0e\"", "mime-version": "1.0", "received-spf": "pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:32::e as permitted sender) receiver=mx.cloudflare.net; client-ip=2607:f8b0:4864:32::e; envelope-from=\"godwinfranklin419@gmail.com\"; helo=mail-oi2-x0e.google.com;", "x-gm-features": "AclHuK8Ef_gUJDwAmEgoVIV3C93IFnFKwnCDPkOsJQY5B0L0atRS5jPogpP08RE", "dkim-signature": "v=1; a=rsa-sha256; c=relaxed/relaxed; d=gmail.com; s=20251104; t=1790610125; x=1791214925; darn=worldchoiceperfume.com; h=content-type:to:subject:message-id:date:from:in-reply-to:references :mime-version:from:to:cc:subject:date:message-id:reply-to :content-type; bh=vGgAZjJR9ROA7M0qdH8o5WNBp2HcimmZj2CoTGN3uPw=; b=ACehkGAfug34xBlQt1W+ZiVhtNksYG8Mk/TXbygA4wQPHDFYhqiZPMaAFVNc5HozsN 0ye9C5AGwX7BXQF3isbRsv05Mt2xVq6PcS13ZTDj0FNlLMNCMwm6tuaA7cFpYXXAEOs1 S4Ig0e9TdVWhk47kURLMQo4RUQPAsc44TlMSPw530uxTay9G6hcXp9+06Gzv+2x2rt06 itQZQazJ0bAK1K6D3YdrNpCo1bFYZah/drJbCnRo379EryZbISikRH41XZLRIXGwCWOK yIF5hCefpyD+iiT5MBwWS2YvX271a7XWDBWwaQ1FfzfLbTVB06d+JG4cRPDRVnmfD0e5 5rqA==", "x-cf-spamh-score": "0 for <info@worldchoiceperfume.com>; Mon, 28 Sep 2026 08:42:05 -0700 (PDT) d=google.com; s=arc-20260327; b=Xo4KN02wifMEMPD2NgYvs1UlWgVNltN6VyG3af+AwfSMOwnuv0KQg4NQCju4FccrVS 7Mzrd6YtAii//aa7wP356lXMz8Fd9x81sBQjORxCtR7rfv2h8SfuhiXQlA94ICCk2fuu XVKfA9/GpzF4rZ/8R+jxILBuCiGRfk45OnrXQ7Sct0DtnDxaUmGO/4yyj+IOy1PsnR81 UAc8hookSwZzS07SeVipenDko0YPKU8N4ngfeaeUVDR+D42XVC0kr7ubj7++UG74M8hU XRKtj8lXjdYmG+k42SvKCo9QKL1IMYk3jwsMgSonMJnQ3/a8LQh+T3MF8FSTP93sEqEU zZRg== h=to:subject:message-id:date:from:in-reply-to:references:mime-version :dkim-signature; bh=vGgAZjJR9ROA7M0qdH8o5WNBp2HcimmZj2CoTGN3uPw=; fh=nniGTCdcezPCCWQcYQFtt+TqrAdcQ+jBfMQuxKQBEts=; b=YFC3KskfmMtLv1Z3WXrkBkV6MisVyrCLwIGUCHeb/z8xRCZ55LB+84KNz0dtDogHoT klbEZb1/xcUByNo0JhWD5nBwxl5bXmSxdnADV0vkF7gNtqrHfYi+W5h2v2f+p71w1HqZ BYb8c7i88XtLgCSEXG93+a2YpVBVAAby55MZ4XPVOSAO5YuyvVA59NRvlzdonyQVhmkC nwSaV7JQ29tUFr2npWXAZMWu5Kl2UksRbS6rHzWZtMf85271y9BODXbxV25RT6b9oCwd kV0cUlfT/JRcUJZE3lHftDejDqzifUp2gAeN6jqYJMLYHWtI/fIKSvtjxyd6agNALdBr SC0w==; darn=worldchoiceperfume.com", "x-gm-message-state": "AFuF++m3dtR12VmUPAqBaORSNjD6ob6QCXLByX7zoBHLoHXFdXq9BxiM AX/Fl7Pr6/1orZiZz3bmx3yCBPGzN0HkyJF93+jNFdqoMM/eXRzDl7ZwOvFLjsKAntsTpc3GuyH hU85aWcjM6qApEw+E672/YaRcO09145M967V/", "arc-message-signature": "i=1; a=rsa-sha256; c=relaxed/relaxed; d=google.com; s=arc-20260327;", "authentication-results": "mx.cloudflare.net; dkim=pass header.d=gmail.com header.s=20251104 header.b=ACehkGAf; dmarc=pass header.from=gmail.com policy.dmarc=none; spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-oi2-x0e.google.com) smtp.helo=mail-oi2-x0e.google.com; spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:32::e as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com; arc=pass smtp.remote-ip=\"2607:f8b0:4864:32::e\"", "x-google-dkim-signature": "v=1; a=rsa-sha256; c=relaxed/relaxed; d=1e100.net; s=20260707; t=1790610125; x=1791214925; h=content-type:to:subject:message-id:date:from:in-reply-to:references :mime-version:x-gm-gg:x-gm-message-state:from:to:cc:subject:date :message-id:reply-to:content-type; bh=vGgAZjJR9ROA7M0qdH8o5WNBp2HcimmZj2CoTGN3uPw=; b=aFkNxFZUvVdhFCCIqvDPQcXN0J+Akfq3VHCFu0pgT/aoIYnNu+fmpb03o/813sfRkk HUbOHm+aELlo01zXNaEXsfdGqzQLo7CDnByLGM/CbQ6Qz12PN6Dr5bAxD2tducQWp5DH Fc2rqHKAvj1dTFq/YDRC09ge026JG0Q8S0jmPvlS9x6LwUUUZ2Qa7ms8s3Q/me17rKlt whmLFIFcsgekTJllpBiaGQNSCRV9vgKmziOG2jmUn8T3pm4B8wKCeIj7p6uJgyRbIdSL +NvAe+1PpnhLZQCshwtppklRHiuVyfs387DpjpuiYakbDKqpV7dJgwdgs5Bnp6ChyX6p 1KbA==", "arc-authentication-results": "i=1; mx.google.com; arc=none"}', NULL, FALSE, 'none', 'pass', TRUE, FALSE, 'replied', '<CAOLv=VuAD0xOd6WytfO=n4SZhm-BjxkkGmyG-73yG3xRAFn2ww@mail.gmail.com>', NULL, '2026-09-28T14:40:11+00:00', '2026-09-29T09:43:02+00:00', '2026-09-29T09:43:02+00:00', '2026-09-28T14:40:11+00:00', '2026-09-29T09:43:02+00:00'),
(6, '<CAOLv=VuV5N_j06NybyuHGv9N2KFdXM8fakGckbpRcC_LSd0L1w@mail.gmail.com>', NULL, NULL, 'godwinfranklin419@gmail.com', 'FRANK GODWIN', 'info@worldchoiceperfume.com', NULL, NULL, NULL, 'Hello', 'Hvcxfghh', '<div dir="auto">Hvcxfghh</div>', 'Received: from mail-dl2-x10.google.com (2607:f8b0:4864:38::10)
        by cloudflare-email.net (cloudflare) id jgHFYsZlq6S4
        for <info@worldchoiceperfume.com>; Mon, 28 Sep 2026 20:40:23 +0000
ARC-Seal: i=2; a=rsa-sha256; s=cf2024-1; d=cloudflare-email.net; cv=pass;
	b=JC8EXqncW/Os5tGQ7ishx+/EHI7CcQmlcP+QqkW8F19xlI1r/rIRrUDr2UyUtzzXexJy5DwNx
	3sotAMoHIphrUBfBo46LIj55Yqlgy/G5nTcKQjs2hm4vwtqnKH0OvxRgdKSVFhrZY/vC4R5b+U1
	L5BluzJufqV3xVCS1qnecHTdvI8fogRd6eiPo+Ze+JV7zaUz2Up7ZcYizQyHp8jM1xYQs0pv9bi
	4iKKzbyUtFfx0rqPtXPclQ6gfxBRGwjvEbl1w6+xmLm0sjERUrI3MJBQMqEcPPW1ToTlIUsRiag
	TxkCuaKT6xSG4nVuvIYa5NSbTQCfaRNj6bAwk/nU8yTw==;
ARC-Message-Signature: i=2; a=rsa-sha256; s=cf2024-1; d=cloudflare-email.net; c=relaxed/relaxed;
	h=To:Subject:Date:From:from:reply-to:cc:resent-date:resent-from:resent-to
	:resent-cc:in-reply-to:references:list-id:list-help:list-subscribe
	:list-post:list-owner:list-archive; t=1790628025; x=1791232825; bh=eHd+5pIj
	RuZQSZm13HldQVc3gg66vLGKf1h1xyxLvgI=; b=aEOcFBDl/z/8I4O3rFE/LgY/FEmn1CjK1hL
	4hOEVGsb+v6KTMPRKMskFWNIFK71s6/f0hbl82tbtJY6VBNg+OA4nRvvpSdRqg4+k9h8u7sLoFQ
	kWqZm8vIVxQokZLd8KCuSGrD6GwDxT4T9jUOX7WgkopzsFcITzSP8p2NMQhyi7vPsnfwDY5ke2Y
	UZUKLlnf1EKBQYRZ1/MaiQRGzVgSsMBh7uvM3XYvETsg6crHUdIsTgXxI6VE2b3bKIW1MDRnXo9
	h3T96v6n9h4ks18XDShAetuk6oZkUaqboRbuqzPZVgZegVU644F5wD3bUMpByRNyX/dwwkG/asE
	4EoXlyw==;
ARC-Authentication-Results: i=2; mx.cloudflare.net;
	dkim=pass header.d=gmail.com header.s=20251104 header.b=ZCM5uWlY;
	dmarc=pass header.from=gmail.com policy.dmarc=none;
	spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-dl2-x10.google.com) smtp.helo=mail-dl2-x10.google.com;
	spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:38::10 as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com;
	arc=pass smtp.remote-ip="2607:f8b0:4864:38::10"
Received-SPF: pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:38::10 as permitted sender)
	receiver=mx.cloudflare.net; client-ip=2607:f8b0:4864:38::10; envelope-from="godwinfranklin419@gmail.com"; helo=mail-dl2-x10.google.com;
Authentication-Results: mx.cloudflare.net;
	dkim=pass header.d=gmail.com header.s=20251104 header.b=ZCM5uWlY;
	dmarc=pass header.from=gmail.com policy.dmarc=none;
	spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-dl2-x10.google.com) smtp.helo=mail-dl2-x10.google.com;
	spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:38::10 as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com;
	arc=pass smtp.remote-ip="2607:f8b0:4864:38::10"
Received: by mail-dl2-x10.google.com with SMTP id a92af1059eb24-142dd04edb5so5870431c88.2
        for <info@worldchoiceperfume.com>; Mon, 28 Sep 2026 13:40:23 -0700 (PDT)
ARC-Seal: i=1; a=rsa-sha256; t=1790628023; cv=none;
        d=google.com; s=arc-20260327;
        b=UNMCymnbCMlTBzw91Ypm7ynZgspoHPCp/EsJurDKZdBOTo8ZJEViqdBcDcjSS0Nz7T
         VJhqU7To7DtQO/iCG+trq+Po5NvPvnb1YmuoufMHY9Fv1l2vZJNDYU8yvb9CuG7boimC
         WB/+v7Rm7lvP/XxYT8ilZIVYjnyO92oQsrSFD5zC9BYXalQlP6A6FIH/d+1Z48AXRtWQ
         54m/5pACNDoOOAZWVOL4UA9D17zV9GJACb/q2sPSKB3GpYJcqQSg6JymBK6QlHXbhHWd
         RRk7Pea0VIRTcwWKPVmaT9H8qwpzH0ubSrLESRUTCWP8yW+4vmiIAO1Ds3v4xlinV/xS
         GbHA==
ARC-Message-Signature: i=1; a=rsa-sha256; c=relaxed/relaxed; d=google.com; s=arc-20260327;
        h=to:subject:message-id:date:from:mime-version:dkim-signature;
        bh=eHd+5pIjRuZQSZm13HldQVc3gg66vLGKf1h1xyxLvgI=;
        fh=nniGTCdcezPCCWQcYQFtt+TqrAdcQ+jBfMQuxKQBEts=;
        b=As9cqcZZYSM2GgztM3EmTh7/RFSIPb4z6whce4KS96/cSfL/8RrW8Ndo1WcpyE4tSa
         cGMX4pcoypYNepJLE57bXGRox3uhnb5A1vSGVOIWd/vbo28CcXvtQ0zO/WQRN5XZ+suh
         U6i4tGJpk7z8pUTWyLviQQszgjaMoDj1QOXOLK7pI/ohgGzV4Rbl1m7292wQk4N7kpxv
         3aJGQS6tuOjTE8c5dLK9uQhGzE3dXnWwKVeMKzTnMZRzDmTm3rE1268zl7Eu7eaFSU0I
         ef6Y6SqDiMKab18hMI5tR8/eH84TDtpswePk7HuYx632AKBQkFs7CSsZ1aoa+me39BXJ
         KRLQ==;
        darn=worldchoiceperfume.com
ARC-Authentication-Results: i=1; mx.google.com; arc=none
DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
        d=gmail.com; s=20251104; t=1790628023; x=1791232823; darn=worldchoiceperfume.com;
        h=content-type:to:subject:message-id:date:from:mime-version:from:to
         :cc:subject:date:message-id:reply-to:content-type;
        bh=eHd+5pIjRuZQSZm13HldQVc3gg66vLGKf1h1xyxLvgI=;
        b=ZCM5uWlYiN9M3TGSklKF+FYuYhtSOv5TWsjh/hMwRmPOQUtF1kv/GQoZtuXx7ZFEFP
         n3ILmVvjGQZ4yOca/kUN8+wvgH9aN9dzmEiAqii9XrcU/Tz6OuhYJS8iFbm+JHNbY1rq
         6iS23JK/mf2hcZD7ryJ3JpYEq3L4ZrNBgHPweQIVSi2cMmBPl9bRDkSLh4wh5p2ngr5w
         PRYF9YW9HHHM6KgYQ9o8ph4xFSgONsEdhIUvzi5iIcSk919rkgj5OxOgdyY/iFpHyEa/
         e7Tsq6k/g/2sEO/23y8qZsKfNJZNNeaHp00r2P81xbA2wp/0Y/Aqgal/AqKJQbHrm+nW
         QGCA==
X-Google-DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
        d=1e100.net; s=20260707; t=1790628023; x=1791232823;
        h=content-type:to:subject:message-id:date:from:mime-version:x-gm-gg
         :x-gm-message-state:from:to:cc:subject:date:message-id:reply-to
         :content-type;
        bh=eHd+5pIjRuZQSZm13HldQVc3gg66vLGKf1h1xyxLvgI=;
        b=0Fi2onAxhYUmqGm1bC846aJP/2BmElM7Q7BW9nMScM67N8UwLFbP+ijrI81cUGFm9F
         pIT6zeuXY4bmusorIpf4v7RIN9ehcfdu8qMcy/X/9gnNCDozPHVGUykc90XCv8GurzC/
         sPhXt4oC5Z3T23ZeFPBf8/Ico+Vih298rDLkpUJKPk7UpfMga9InJNOP/qJwP1lI89jC
         myw8c5nZIWSg8V9zGSg9PSia/hNSiTozpAUpQA2cUXjU+YAoY4bOJn7jlWZJ/T/539iu
         IxDc6TnvCKYiuuEv0OTQuQBNhrcPTeBaF/+E6lQzKiKhKENGVX9U309POaVew+V3+9MI
         4k0Q==
X-Gm-Message-State: AFuF++lYgaGPyPKQr4NaPhaojgTiIp0iqQZXMPItBWygL02xDCHuMPqx
	05TEh9b+SCCdkufI5PPL3brMoejklKt1qjurc3VgM3xbODwoNooDdQeRIDiapCvA98OvOhe3M79
	/pXLxqN0qW5NSnoDh74AkcSy32Ix2fc9Pazzx
X-Gm-Gg: AYBFou1ArUx154fRwmFvlLd8P4YQigUUhEG/cB2IeWghn1wBHIEOz8dQl7PtvpoYKpw
	t7UMnKabUIKVevpY8GbBhVSZ9e9/TN1HNZQiDBnjYFWCsOZZ1C7ByVj/M0zF/iOIivGDF8X73dQ
	WNNwSPs9YpY5B/eVh7+T6xHVo7XpxGFf4W37zQchBjN/nSPCZS5vKKfvrYIP1UWLqi8WeoZyDma
	gtlGg68kvOog1MoYZCjWWEGMQzjC+0BvY8vSpMxPWgxWxd3R36edMua3jvpO+3oAC8t8nX0AxAm
	TVsDm/vBSqNbMThKq11+XD65ggPr3LK2to1HahTzSLTeLJtoTMs2WwpT
X-Received: by 2002:a05:701b:4354:b0:143:6080:668f with SMTP id
 a92af1059eb24-146ce1aa9d6mr12189100c88.7.1790628021767; Mon, 28 Sep 2026
 13:40:21 -0700 (PDT)
MIME-Version: 1.0
From: FRANK GODWIN <godwinfranklin419@gmail.com>
Date: Mon, 28 Sep 2026 23:40:07 +0300
X-Gm-Features: AclHuK_kjzoTL6u7d8a8i8AtMLSy3bYSgPCGzrGz6tNup0CL9MIz-qkKuBCSJqM
Message-ID: <CAOLv=VuV5N_j06NybyuHGv9N2KFdXM8fakGckbpRcC_LSd0L1w@mail.gmail.com>
Subject: Hello
To: info@worldchoiceperfume.com
Content-Type: multipart/mixed; boundary="0000000000002c0be5065c911353"

--0000000000002c0be5065c911353
Content-Type: multipart/alternative; boundary="0000000000002c0be3065c911351"

--0000000000002c0be3065c911351
Content-Type: text/plain; charset="UTF-8"

Hvcxfghh

--0000000000002c0be3065c911351
Content-Type: text/html; charset="UTF-8"

<div dir="auto">Hvcxfghh</div>

--0000000000002c0be3065c911351--
--0000000000002c0be5065c911353
Content-Type: video/mp4; name=Chrome
Content-Disposition: attachment; filename=Chrome
Content-Transfer-Encoding: base64
Content-ID: <1a0e9bb6b927843be9a1>
X-Attachment-Id: 1a0e9bb6b927843be9a1

AAAAGGZ0eXBtcDQyAAAAAGlzb21tcDQyAAAMeGZyZWUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAARSI0m1kYXQBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwB
QCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
Wlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKA
o3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd
+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4
hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIU
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/
8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8Qpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwB
QCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
Wlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKA
o3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd
+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4
hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIU
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/
8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8Qpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwB
QCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
Wlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKA
o3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd
+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4
hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIU
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLwBQCKAo3/4hS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0t
LS0tLS0tLS7/8QpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpa
WlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpaWlpd+aIUtLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0
tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLwAANIuZYiAAEAAD/7A
d4FLALfj45slK04dTI2A90AAAAMAAAMAAHnhVoSPlSYnkWVp7ix7i1uHO+fLH/ufcDqLAcDNbAm5
KUJk+CMP0J37cbtrTkZZynbAKI3Nj54RD87BMS1i0rM1J6+hbwxzusW2OjLQdXOejlRi3yDrzSkk
Bo62jfVBNYb5fyJW0jD80F93V4ccgj2bki+5vTUCTrMws1gsx54dLdlEYQS0Z1cz22u4J6dII1qh
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

[truncated by the app at 24838303 bytes]', '{"to": "info@worldchoiceperfume.com", "date": "Mon, 28 Sep 2026 23:40:07 +0300", "from": "FRANK GODWIN <godwinfranklin419@gmail.com>", "subject": "Hello", "x-gm-gg": "AYBFou1ArUx154fRwmFvlLd8P4YQigUUhEG/cB2IeWghn1wBHIEOz8dQl7PtvpoYKpw t7UMnKabUIKVevpY8GbBhVSZ9e9/TN1HNZQiDBnjYFWCsOZZ1C7ByVj/M0zF/iOIivGDF8X73dQ WNNwSPs9YpY5B/eVh7+T6xHVo7XpxGFf4W37zQchBjN/nSPCZS5vKKfvrYIP1UWLqi8WeoZyDma gtlGg68kvOog1MoYZCjWWEGMQzjC+0BvY8vSpMxPWgxWxd3R36edMua3jvpO+3oAC8t8nX0AxAm TVsDm/vBSqNbMThKq11+XD65ggPr3LK2to1HahTzSLTeLJtoTMs2WwpT", "arc-seal": "i=1; a=rsa-sha256; t=1790628023; cv=none;", "received": "by mail-dl2-x10.google.com with SMTP id a92af1059eb24-142dd04edb5so5870431c88.2", "message-id": "<CAOLv=VuV5N_j06NybyuHGv9N2KFdXM8fakGckbpRcC_LSd0L1w@mail.gmail.com>", "x-received": "by 2002:a05:701b:4354:b0:143:6080:668f with SMTP id a92af1059eb24-146ce1aa9d6mr12189100c88.7.1790628021767; Mon, 28 Sep 2026 13:40:21 -0700 (PDT)", "content-type": "multipart/mixed; boundary=\"0000000000002c0be5065c911353\"", "mime-version": "1.0", "received-spf": "pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:38::10 as permitted sender) receiver=mx.cloudflare.net; client-ip=2607:f8b0:4864:38::10; envelope-from=\"godwinfranklin419@gmail.com\"; helo=mail-dl2-x10.google.com;", "x-gm-features": "AclHuK_kjzoTL6u7d8a8i8AtMLSy3bYSgPCGzrGz6tNup0CL9MIz-qkKuBCSJqM", "dkim-signature": "v=1; a=rsa-sha256; c=relaxed/relaxed; d=gmail.com; s=20251104; t=1790628023; x=1791232823; darn=worldchoiceperfume.com; h=content-type:to:subject:message-id:date:from:mime-version:from:to :cc:subject:date:message-id:reply-to:content-type; bh=eHd+5pIjRuZQSZm13HldQVc3gg66vLGKf1h1xyxLvgI=; b=ZCM5uWlYiN9M3TGSklKF+FYuYhtSOv5TWsjh/hMwRmPOQUtF1kv/GQoZtuXx7ZFEFP n3ILmVvjGQZ4yOca/kUN8+wvgH9aN9dzmEiAqii9XrcU/Tz6OuhYJS8iFbm+JHNbY1rq 6iS23JK/mf2hcZD7ryJ3JpYEq3L4ZrNBgHPweQIVSi2cMmBPl9bRDkSLh4wh5p2ngr5w PRYF9YW9HHHM6KgYQ9o8ph4xFSgONsEdhIUvzi5iIcSk919rkgj5OxOgdyY/iFpHyEa/ e7Tsq6k/g/2sEO/23y8qZsKfNJZNNeaHp00r2P81xbA2wp/0Y/Aqgal/AqKJQbHrm+nW QGCA==", "x-gm-message-state": "AFuF++lYgaGPyPKQr4NaPhaojgTiIp0iqQZXMPItBWygL02xDCHuMPqx 05TEh9b+SCCdkufI5PPL3brMoejklKt1qjurc3VgM3xbODwoNooDdQeRIDiapCvA98OvOhe3M79 /pXLxqN0qW5NSnoDh74AkcSy32Ix2fc9Pazzx", "arc-message-signature": "i=1; a=rsa-sha256; c=relaxed/relaxed; d=google.com; s=arc-20260327;", "authentication-results": "mx.cloudflare.net; dkim=pass header.d=gmail.com header.s=20251104 header.b=ZCM5uWlY; dmarc=pass header.from=gmail.com policy.dmarc=none; spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-dl2-x10.google.com) smtp.helo=mail-dl2-x10.google.com; spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:38::10 as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com; arc=pass smtp.remote-ip=\"2607:f8b0:4864:38::10\" for <info@worldchoiceperfume.com>; Mon, 28 Sep 2026 13:40:23 -0700 (PDT) d=google.com; s=arc-20260327; b=UNMCymnbCMlTBzw91Ypm7ynZgspoHPCp/EsJurDKZdBOTo8ZJEViqdBcDcjSS0Nz7T VJhqU7To7DtQO/iCG+trq+Po5NvPvnb1YmuoufMHY9Fv1l2vZJNDYU8yvb9CuG7boimC WB/+v7Rm7lvP/XxYT8ilZIVYjnyO92oQsrSFD5zC9BYXalQlP6A6FIH/d+1Z48AXRtWQ 54m/5pACNDoOOAZWVOL4UA9D17zV9GJACb/q2sPSKB3GpYJcqQSg6JymBK6QlHXbhHWd RRk7Pea0VIRTcwWKPVmaT9H8qwpzH0ubSrLESRUTCWP8yW+4vmiIAO1Ds3v4xlinV/xS GbHA== h=to:subject:message-id:date:from:mime-version:dkim-signature; bh=eHd+5pIjRuZQSZm13HldQVc3gg66vLGKf1h1xyxLvgI=; fh=nniGTCdcezPCCWQcYQFtt+TqrAdcQ+jBfMQuxKQBEts=; b=As9cqcZZYSM2GgztM3EmTh7/RFSIPb4z6whce4KS96/cSfL/8RrW8Ndo1WcpyE4tSa cGMX4pcoypYNepJLE57bXGRox3uhnb5A1vSGVOIWd/vbo28CcXvtQ0zO/WQRN5XZ+suh U6i4tGJpk7z8pUTWyLviQQszgjaMoDj1QOXOLK7pI/ohgGzV4Rbl1m7292wQk4N7kpxv 3aJGQS6tuOjTE8c5dLK9uQhGzE3dXnWwKVeMKzTnMZRzDmTm3rE1268zl7Eu7eaFSU0I ef6Y6SqDiMKab18hMI5tR8/eH84TDtpswePk7HuYx632AKBQkFs7CSsZ1aoa+me39BXJ KRLQ==; darn=worldchoiceperfume.com", "x-google-dkim-signature": "v=1; a=rsa-sha256; c=relaxed/relaxed; d=1e100.net; s=20260707; t=1790628023; x=1791232823; h=content-type:to:subject:message-id:date:from:mime-version:x-gm-gg :x-gm-message-state:from:to:cc:subject:date:message-id:reply-to :content-type; bh=eHd+5pIjRuZQSZm13HldQVc3gg66vLGKf1h1xyxLvgI=; b=0Fi2onAxhYUmqGm1bC846aJP/2BmElM7Q7BW9nMScM67N8UwLFbP+ijrI81cUGFm9F pIT6zeuXY4bmusorIpf4v7RIN9ehcfdu8qMcy/X/9gnNCDozPHVGUykc90XCv8GurzC/ sPhXt4oC5Z3T23ZeFPBf8/Ico+Vih298rDLkpUJKPk7UpfMga9InJNOP/qJwP1lI89jC myw8c5nZIWSg8V9zGSg9PSia/hNSiTozpAUpQA2cUXjU+YAoY4bOJn7jlWZJ/T/539iu IxDc6TnvCKYiuuEv0OTQuQBNhrcPTeBaF/+E6lQzKiKhKENGVX9U309POaVew+V3+9MI 4k0Q==", "arc-authentication-results": "i=1; mx.google.com; arc=none"}', 'Chrome', TRUE, 'none', 'pass', TRUE, FALSE, 'new', '<CAOLv=VuV5N_j06NybyuHGv9N2KFdXM8fakGckbpRcC_LSd0L1w@mail.gmail.com>', NULL, '2026-09-28T20:40:07+00:00', '2026-09-28T20:46:32+00:00', NULL, '2026-09-28T20:40:07+00:00', '2026-09-28T20:46:32+00:00'),
(3, '<CAOLv=VuAD0xOd6WytfO=n4SZhm-BjxkkGmyG-73yG3xRAFn2ww@mail.gmail.com>', '<CAOLv=VtPE4jnCeUOuQjLqDXPjRGOKCjKN19KWBmEovn+adez1w@mail.gmail.com>', '<CAOLv=VtPE4jnCeUOuQjLqDXPjRGOKCjKN19KWBmEovn+adez1w@mail.gmail.com>', 'godwinfranklin419@gmail.com', 'FRANK GODWIN', 'info@worldchoiceperfume.com', NULL, NULL, NULL, 'Fwd: Greetings', '---------- Forwarded message ---------
From: FRANK GODWIN <godwinfranklin419@gmail.com>
Date: Mon, 28 Sep 2026, 17:17
Subject: Greetings
To: <info@worldchoiceperfume.com>


Hello', '<div dir="auto"></div><br><div class="gmail_quote gmail_quote_container"><div dir="ltr" class="gmail_attr">---------- Forwarded message ---------<br>From: <strong class="gmail_sendername" dir="auto">FRANK GODWIN</strong> <span dir="auto">&lt;<a href="mailto:godwinfranklin419@gmail.com">godwinfranklin419@gmail.com</a>&gt;</span><br>Date: Mon, 28 Sep 2026, 17:17<br>Subject: Greetings<br>To:  &lt;<a href="mailto:info@worldchoiceperfume.com">info@worldchoiceperfume.com</a>&gt;<br></div><br><br><div dir="auto">Hello</div>
</div>', 'Received: from mail-pz2-x2a.google.com (2607:f8b0:4864:3b::2a)
        by cloudflare-email.net (cloudflare) id coizqSnxxAHY
        for <info@worldchoiceperfume.com>; Mon, 28 Sep 2026 15:36:16 +0000
ARC-Seal: i=2; a=rsa-sha256; s=cf2024-1; d=cloudflare-email.net; cv=pass;
	b=YCKL/Fmd6KPgI08cw8b8W4MkLRqg0ME6YoYic9i7FGzZeWOgjme/NkniZelyaYSDBz68Q/Uvu
	hJFeYk6PxE6So1BQGPuXVtn15D+G8YiyqGXj1e04Qzhd536QjjykbK9YRZYLNJSM3Bfo/+BdD0i
	28jWahRXpLSN/098VnyTTqr3dIH12mvgityVlGuz+xUxISqKq/zSc5Ks9+9MfgKKjaEUMoZDfyE
	ajy2CW+Kknta7wYVSP+G2HfAS+vn54/lyGPeszmeVSxaftpexMnIkuCjMVEp4NtINXDv26b2pxx
	XFV/OwrNbd3M0bR0sp3HKVv8X1qxlJF0w5a9CkErKonA==;
ARC-Message-Signature: i=2; a=rsa-sha256; s=cf2024-1; d=cloudflare-email.net; c=relaxed/relaxed;
	h=To:Subject:Date:From:In-Reply-To:References:from:reply-to:cc:resent-date
	:resent-from:resent-to:resent-cc:list-id:list-help:list-subscribe
	:list-post:list-owner:list-archive; t=1790609776; x=1791214576; bh=XoVSFZrF
	sXHHIw27aDkS9rIddVn3xBQ+Bol7wXv6ero=; b=NIsIln0yvM2b3JEqCLWEOsoX737eLkTFNyB
	GRd+KKkGwauIJmhbJCj7Nk/q9vDxguKkEWk44yq5JQMAjtcfqoFlXJWLnE4cUra0xh01qDcCTRN
	TKb5cq3/EgTZaaYJJEWxQtdCa16qiUN9o6piA2l+gFbg9uItWYfblUG0Ss+pnXy6dUytLkBLrdJ
	9KY16Z24oYT8HmyhCLGORSMoUML1In1XGWMLSxEaq6Af67/9QgYZwZY0WAJo6Uh9bU7dHAQIvEc
	FqOiWtyQoT3glMFQLLFHlYVv+H4DUtMSgpbvEo5PRxF3NcIGvXNY7WffPtbW9cuWfA/2jXHF2vY
	rQuBAfw==;
ARC-Authentication-Results: i=2; mx.cloudflare.net;
	dkim=pass header.d=gmail.com header.s=20251104 header.b=YdHt6pV5;
	dmarc=pass header.from=gmail.com policy.dmarc=none;
	spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-pz2-x2a.google.com) smtp.helo=mail-pz2-x2a.google.com;
	spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:3b::2a as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com;
	arc=pass smtp.remote-ip="2607:f8b0:4864:3b::2a"
Received-SPF: pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:3b::2a as permitted sender)
	receiver=mx.cloudflare.net; client-ip=2607:f8b0:4864:3b::2a; envelope-from="godwinfranklin419@gmail.com"; helo=mail-pz2-x2a.google.com;
Authentication-Results: mx.cloudflare.net;
	dkim=pass header.d=gmail.com header.s=20251104 header.b=YdHt6pV5;
	dmarc=pass header.from=gmail.com policy.dmarc=none;
	spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-pz2-x2a.google.com) smtp.helo=mail-pz2-x2a.google.com;
	spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:3b::2a as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com;
	arc=pass smtp.remote-ip="2607:f8b0:4864:3b::2a"
X-CF-SpamH-Score: 0
Received: by mail-pz2-x2a.google.com with SMTP id d2e1a72fcca58-88206fc44c6so1672007b3a.2
        for <info@worldchoiceperfume.com>; Mon, 28 Sep 2026 08:36:16 -0700 (PDT)
ARC-Seal: i=1; a=rsa-sha256; t=1790609776; cv=none;
        d=google.com; s=arc-20260327;
        b=Dk9RqKtLNC19Fd4Cmqrs1XVsJUVBSCB1dSvKt6XbrcbXoRqVLWdl/v++CjoE1dyqHR
         /aw9cet9kI6TP8R/pqmpENCqUppUauqAgRbBTTaXOVyvgLxgBVoHFNQrHqq1h98HJY7c
         6Y/mw9xWrTg1EcFSrpBzFOhaxlzjZld9zo2sA7wqTLJ7FxPFHynIQll9FBaMhmniLajS
         IGCwyQBRYI7OvTMMJmM5U+6GDZnWP3UTHqIhFjdKgagOK6f9XhqnRJAA22KtDc9AStcO
         wp1svmQ9ObmK5eixXS9z524xu2rjjqavhxFAdrN3lj3pOclGagGh0isycod1Q4IKQTh+
         HbLQ==
ARC-Message-Signature: i=1; a=rsa-sha256; c=relaxed/relaxed; d=google.com; s=arc-20260327;
        h=to:subject:message-id:date:from:in-reply-to:references:mime-version
         :dkim-signature;
        bh=XoVSFZrFsXHHIw27aDkS9rIddVn3xBQ+Bol7wXv6ero=;
        fh=nniGTCdcezPCCWQcYQFtt+TqrAdcQ+jBfMQuxKQBEts=;
        b=oALYPkK6/bAOLqMpN5/7Zk5hoBw1ylsANvvudbZhJt6N5C7m360gjNv+8G3l03+Zuz
         ALxTW1yu4cmxaYW/q8u7klTIMLTfhhgsw5aNXZH1rCytjMVENF2LqevE1zQ1LGPljskh
         oF/eyVpilr7zFtkr6YG/ldjUQ07GUTGkjKGIyqW3LeVypXi2dGfgByMIhiA1pRnYsF9F
         7IyImeH989M6fMezKjEL48pyUextT+6JzEtFuWWIOMBvz9fFraA9KFVcRVxCTNxVbRlM
         CQx9Blm1Weo3zHALb1Pi2409/lY7fKvVZuCxsx1llz+q6YTZxKli17Qd67ZRapo73XhF
         Xk9A==;
        darn=worldchoiceperfume.com
ARC-Authentication-Results: i=1; mx.google.com; arc=none
DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
        d=gmail.com; s=20251104; t=1790609776; x=1791214576; darn=worldchoiceperfume.com;
        h=content-type:to:subject:message-id:date:from:in-reply-to:references
         :mime-version:from:to:cc:subject:date:message-id:reply-to
         :content-type;
        bh=XoVSFZrFsXHHIw27aDkS9rIddVn3xBQ+Bol7wXv6ero=;
        b=YdHt6pV5DXJpcDpwiUftdHcrnKP4zYTke08HfTjVvX1W2LQ6cGloAf5WV3UiyLAxkE
         ZqNegZVLytKYvnYfjkVBdMM40AIKlpIbK8tmgJkwdpQGF1phFx2njCxa2G3XspeyMbBl
         KVdXNZWYUMXAd3z+PHqNpPuXYamilGYaW9dqG5teQwUyBtH81fHiF4awnAwjpP+ha4qm
         hX07egzTkM54J/w/J9rwd9CikzFM84D1wy61rSGjw4jn3I0sh+jw2H7i5moCfdHabOyV
         TVMc9HicrNC8KqxQTpX+q1oOKGHFMwahbA4xPMCXeFDu/RIa+75PNXbeTq887KOutltt
         A4kQ==
X-Google-DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
        d=1e100.net; s=20260707; t=1790609776; x=1791214576;
        h=content-type:to:subject:message-id:date:from:in-reply-to:references
         :mime-version:x-gm-gg:x-gm-message-state:from:to:cc:subject:date
         :message-id:reply-to:content-type;
        bh=XoVSFZrFsXHHIw27aDkS9rIddVn3xBQ+Bol7wXv6ero=;
        b=ZKkYOuKLoZ3sT7IOJqDp8ILrTgWMftOy8IUX80/i8TQpr5b766CLbZuSs3GQ1l3vKu
         D77X+bumVaz2IdoqWxG/PAb4q/KZj/7Zgp7SWwalAP20lkxrK1UbJPTe5DxtXR3nISHM
         kFs00NWZSlU7xxB14+EFjQ1hgnQNjjEjnPjJkr3IdQxjp44M8BfHMXMuJeu4v/VpciQ5
         YA3Tc5c5uH9wRvqtGFo9ZiyQXrdfPxxFbrj6EFbWYIIahrzSNTGGc3cUjhrNl6RBUe2f
         0zYALwF+1MgSJ4mloE50PEd167uCWt/T4LSpvCtLUB6ZOkqiuLoBTpRe0WPaTGRF0xL+
         QpMQ==
X-Gm-Message-State: AFuF++nJYZ2mpZBaPRlT/CfybAnnz6Ef3LuwJoq2hDLNInAzJvBtp1QY
	mi5Djda1MOUx7eCKMA2RHkgtnbatEvSSrixACVqq8KSRd8XqZRviB/HQDvyiBAqCm65pnJBkEf4
	XY5UEHy6ZmgCr+JP/dR2YpafMuslHzQVvaOow
X-Gm-Gg: AYBFou1rNgtTIGjNbO4KqmMQ/mFvPYgSBETH1nTcG1M8+JoAt9VwdNrdKJNDQXUQbDR
	3LrlKCnjk1eFNZNYt1lAA4JpVzW15K3fK9JuKuSXRihamUe97i0F5HiVavULn6kkTUHoKymjI9U
	bXFVebAzUTS+6ifK4mvsAQG5ivWcQXx0puI2l8uyLO1bDvUosi6ypB4mrGJnC4OKLCFoiddRm5S
	2ZyOFM9LK1p+FGyPh8ubDhkEEIX9rE0Y1kimPmlmWVHxsWU51SlZGGvJAzGHWWIMD2eKG1SRvTU
	wYhCoc1Z0sEbJrcxN17yZjrw3fh0R8IIU/g6n95KpSnys9pDei+ZOt1PbA==
X-Received: by 2002:a05:7022:3c16:b0:144:f47b:58b0 with SMTP id
 a92af1059eb24-146d0f3513fmr9242638c88.47.1790606032414; Mon, 28 Sep 2026
 07:33:52 -0700 (PDT)
MIME-Version: 1.0
References: <CAOLv=VtPE4jnCeUOuQjLqDXPjRGOKCjKN19KWBmEovn+adez1w@mail.gmail.com>
In-Reply-To: <CAOLv=VtPE4jnCeUOuQjLqDXPjRGOKCjKN19KWBmEovn+adez1w@mail.gmail.com>
From: FRANK GODWIN <godwinfranklin419@gmail.com>
Date: Mon, 28 Sep 2026 17:33:39 +0300
X-Gm-Features: AclHuK_BuhsbRHndH5BtrhFP37IEV1jsuX5LgtYrXyvvWLvmbUwKcZ3Czwd1blE
Message-ID: <CAOLv=VuAD0xOd6WytfO=n4SZhm-BjxkkGmyG-73yG3xRAFn2ww@mail.gmail.com>
Subject: Fwd: Greetings
To: info@worldchoiceperfume.com
Content-Type: multipart/alternative; boundary="0000000000007eaac8065c8bf42d"

--0000000000007eaac8065c8bf42d
Content-Type: text/plain; charset="UTF-8"

---------- Forwarded message ---------
From: FRANK GODWIN <godwinfranklin419@gmail.com>
Date: Mon, 28 Sep 2026, 17:17
Subject: Greetings
To: <info@worldchoiceperfume.com>


Hello

--0000000000007eaac8065c8bf42d
Content-Type: text/html; charset="UTF-8"
Content-Transfer-Encoding: quoted-printable

<div dir=3D"auto"></div><br><div class=3D"gmail_quote gmail_quote_container=
"><div dir=3D"ltr" class=3D"gmail_attr">---------- Forwarded message ------=
---<br>From: <strong class=3D"gmail_sendername" dir=3D"auto">FRANK GODWIN</=
strong> <span dir=3D"auto">&lt;<a href=3D"mailto:godwinfranklin419@gmail.co=
m">godwinfranklin419@gmail.com</a>&gt;</span><br>Date: Mon, 28 Sep 2026, 17=
:17<br>Subject: Greetings<br>To:  &lt;<a href=3D"mailto:info@worldchoiceper=
fume.com">info@worldchoiceperfume.com</a>&gt;<br></div><br><br><div dir=3D"=
auto">Hello</div>
</div>

--0000000000007eaac8065c8bf42d--', '{"to": "info@worldchoiceperfume.com", "date": "Mon, 28 Sep 2026 17:33:39 +0300", "from": "FRANK GODWIN <godwinfranklin419@gmail.com>", "subject": "Fwd: Greetings", "x-gm-gg": "AYBFou1rNgtTIGjNbO4KqmMQ/mFvPYgSBETH1nTcG1M8+JoAt9VwdNrdKJNDQXUQbDR 3LrlKCnjk1eFNZNYt1lAA4JpVzW15K3fK9JuKuSXRihamUe97i0F5HiVavULn6kkTUHoKymjI9U bXFVebAzUTS+6ifK4mvsAQG5ivWcQXx0puI2l8uyLO1bDvUosi6ypB4mrGJnC4OKLCFoiddRm5S 2ZyOFM9LK1p+FGyPh8ubDhkEEIX9rE0Y1kimPmlmWVHxsWU51SlZGGvJAzGHWWIMD2eKG1SRvTU wYhCoc1Z0sEbJrcxN17yZjrw3fh0R8IIU/g6n95KpSnys9pDei+ZOt1PbA==", "arc-seal": "i=1; a=rsa-sha256; t=1790609776; cv=none;", "received": "by mail-pz2-x2a.google.com with SMTP id d2e1a72fcca58-88206fc44c6so1672007b3a.2", "message-id": "<CAOLv=VuAD0xOd6WytfO=n4SZhm-BjxkkGmyG-73yG3xRAFn2ww@mail.gmail.com>", "references": "<CAOLv=VtPE4jnCeUOuQjLqDXPjRGOKCjKN19KWBmEovn+adez1w@mail.gmail.com>", "x-received": "by 2002:a05:7022:3c16:b0:144:f47b:58b0 with SMTP id a92af1059eb24-146d0f3513fmr9242638c88.47.1790606032414; Mon, 28 Sep 2026 07:33:52 -0700 (PDT)", "in-reply-to": "<CAOLv=VtPE4jnCeUOuQjLqDXPjRGOKCjKN19KWBmEovn+adez1w@mail.gmail.com>", "content-type": "multipart/alternative; boundary=\"0000000000007eaac8065c8bf42d\"", "mime-version": "1.0", "received-spf": "pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:3b::2a as permitted sender) receiver=mx.cloudflare.net; client-ip=2607:f8b0:4864:3b::2a; envelope-from=\"godwinfranklin419@gmail.com\"; helo=mail-pz2-x2a.google.com;", "x-gm-features": "AclHuK_BuhsbRHndH5BtrhFP37IEV1jsuX5LgtYrXyvvWLvmbUwKcZ3Czwd1blE", "dkim-signature": "v=1; a=rsa-sha256; c=relaxed/relaxed; d=gmail.com; s=20251104; t=1790609776; x=1791214576; darn=worldchoiceperfume.com; h=content-type:to:subject:message-id:date:from:in-reply-to:references :mime-version:from:to:cc:subject:date:message-id:reply-to :content-type; bh=XoVSFZrFsXHHIw27aDkS9rIddVn3xBQ+Bol7wXv6ero=; b=YdHt6pV5DXJpcDpwiUftdHcrnKP4zYTke08HfTjVvX1W2LQ6cGloAf5WV3UiyLAxkE ZqNegZVLytKYvnYfjkVBdMM40AIKlpIbK8tmgJkwdpQGF1phFx2njCxa2G3XspeyMbBl KVdXNZWYUMXAd3z+PHqNpPuXYamilGYaW9dqG5teQwUyBtH81fHiF4awnAwjpP+ha4qm hX07egzTkM54J/w/J9rwd9CikzFM84D1wy61rSGjw4jn3I0sh+jw2H7i5moCfdHabOyV TVMc9HicrNC8KqxQTpX+q1oOKGHFMwahbA4xPMCXeFDu/RIa+75PNXbeTq887KOutltt A4kQ==", "x-cf-spamh-score": "0 for <info@worldchoiceperfume.com>; Mon, 28 Sep 2026 08:36:16 -0700 (PDT) d=google.com; s=arc-20260327; b=Dk9RqKtLNC19Fd4Cmqrs1XVsJUVBSCB1dSvKt6XbrcbXoRqVLWdl/v++CjoE1dyqHR /aw9cet9kI6TP8R/pqmpENCqUppUauqAgRbBTTaXOVyvgLxgBVoHFNQrHqq1h98HJY7c 6Y/mw9xWrTg1EcFSrpBzFOhaxlzjZld9zo2sA7wqTLJ7FxPFHynIQll9FBaMhmniLajS IGCwyQBRYI7OvTMMJmM5U+6GDZnWP3UTHqIhFjdKgagOK6f9XhqnRJAA22KtDc9AStcO wp1svmQ9ObmK5eixXS9z524xu2rjjqavhxFAdrN3lj3pOclGagGh0isycod1Q4IKQTh+ HbLQ== h=to:subject:message-id:date:from:in-reply-to:references:mime-version :dkim-signature; bh=XoVSFZrFsXHHIw27aDkS9rIddVn3xBQ+Bol7wXv6ero=; fh=nniGTCdcezPCCWQcYQFtt+TqrAdcQ+jBfMQuxKQBEts=; b=oALYPkK6/bAOLqMpN5/7Zk5hoBw1ylsANvvudbZhJt6N5C7m360gjNv+8G3l03+Zuz ALxTW1yu4cmxaYW/q8u7klTIMLTfhhgsw5aNXZH1rCytjMVENF2LqevE1zQ1LGPljskh oF/eyVpilr7zFtkr6YG/ldjUQ07GUTGkjKGIyqW3LeVypXi2dGfgByMIhiA1pRnYsF9F 7IyImeH989M6fMezKjEL48pyUextT+6JzEtFuWWIOMBvz9fFraA9KFVcRVxCTNxVbRlM CQx9Blm1Weo3zHALb1Pi2409/lY7fKvVZuCxsx1llz+q6YTZxKli17Qd67ZRapo73XhF Xk9A==; darn=worldchoiceperfume.com", "x-gm-message-state": "AFuF++nJYZ2mpZBaPRlT/CfybAnnz6Ef3LuwJoq2hDLNInAzJvBtp1QY mi5Djda1MOUx7eCKMA2RHkgtnbatEvSSrixACVqq8KSRd8XqZRviB/HQDvyiBAqCm65pnJBkEf4 XY5UEHy6ZmgCr+JP/dR2YpafMuslHzQVvaOow", "arc-message-signature": "i=1; a=rsa-sha256; c=relaxed/relaxed; d=google.com; s=arc-20260327;", "authentication-results": "mx.cloudflare.net; dkim=pass header.d=gmail.com header.s=20251104 header.b=YdHt6pV5; dmarc=pass header.from=gmail.com policy.dmarc=none; spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-pz2-x2a.google.com) smtp.helo=mail-pz2-x2a.google.com; spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:3b::2a as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com; arc=pass smtp.remote-ip=\"2607:f8b0:4864:3b::2a\"", "x-google-dkim-signature": "v=1; a=rsa-sha256; c=relaxed/relaxed; d=1e100.net; s=20260707; t=1790609776; x=1791214576; h=content-type:to:subject:message-id:date:from:in-reply-to:references :mime-version:x-gm-gg:x-gm-message-state:from:to:cc:subject:date :message-id:reply-to:content-type; bh=XoVSFZrFsXHHIw27aDkS9rIddVn3xBQ+Bol7wXv6ero=; b=ZKkYOuKLoZ3sT7IOJqDp8ILrTgWMftOy8IUX80/i8TQpr5b766CLbZuSs3GQ1l3vKu D77X+bumVaz2IdoqWxG/PAb4q/KZj/7Zgp7SWwalAP20lkxrK1UbJPTe5DxtXR3nISHM kFs00NWZSlU7xxB14+EFjQ1hgnQNjjEjnPjJkr3IdQxjp44M8BfHMXMuJeu4v/VpciQ5 YA3Tc5c5uH9wRvqtGFo9ZiyQXrdfPxxFbrj6EFbWYIIahrzSNTGGc3cUjhrNl6RBUe2f 0zYALwF+1MgSJ4mloE50PEd167uCWt/T4LSpvCtLUB6ZOkqiuLoBTpRe0WPaTGRF0xL+ QpMQ==", "arc-authentication-results": "i=1; mx.google.com; arc=none"}', NULL, FALSE, 'none', 'pass', TRUE, FALSE, 'replied', '<CAOLv=VuAD0xOd6WytfO=n4SZhm-BjxkkGmyG-73yG3xRAFn2ww@mail.gmail.com>', NULL, '2026-09-28T14:33:39+00:00', '2026-09-29T13:37:27+00:00', '2026-09-29T13:37:27+00:00', '2026-09-28T14:33:39+00:00', '2026-09-29T13:37:27+00:00'),
(8, '<CAOLv=Vv=AETvbj+7aZu8_3h+4xMxnvpwLRE6a7u8FG4_My5bLA@mail.gmail.com>', NULL, NULL, 'godwinfranklin419@gmail.com', 'FRANK GODWIN', 'info@worldchoiceperfume.com', NULL, NULL, NULL, 'Hey', '', '<div dir="auto"></div>', 'Received: from mail-dl2-x10.google.com (2607:f8b0:4864:38::10)
        by cloudflare-email.net (cloudflare) id zOGWW7OMi6Ll
        for <info@worldchoiceperfume.com>; Mon, 28 Sep 2026 21:22:55 +0000
ARC-Seal: i=2; a=rsa-sha256; s=cf2024-1; d=cloudflare-email.net; cv=pass;
	b=PuWxy5qhGIDu+dY0auW+MF1mkK8Ss83vP4lEKjAKshjXPJr4V1A23nMyN3sn1pHJPNVc3z20+
	+CLE1FahG1qAktV3A73Be1V/OkLvbl/1N3T9WA4rc7MBl9T0m7beKE1biLO+X0Fpvwj6IkBfpyj
	29FMnyyJDUb/oSzI+/IFqOfRM18P74lfx6x1Nj1ALsWosSC3z6wCkWsaAf0rmnaOPhVdgdK3C/T
	W9SfpbscUSdUCoeDrgygT5irTBkE0LeIKksn6u1Bo/OWNOeGFC9y8AHhfOC7j7BM5zG5gKU+g01
	H4WqXwDkcvNVTCQWxi7ejaYDLEb/d/YVzPQqSOsirX0w==;
ARC-Message-Signature: i=2; a=rsa-sha256; s=cf2024-1; d=cloudflare-email.net; c=relaxed/relaxed;
	h=To:Subject:Date:From:from:reply-to:cc:resent-date:resent-from:resent-to
	:resent-cc:in-reply-to:references:list-id:list-help:list-subscribe
	:list-post:list-owner:list-archive; t=1790630576; x=1791235376; bh=XKLvp40l
	otSEGls3Z/2lbZcsJ5wDNDKyxMP8i9pyZ2c=; b=Ae6PNU4681SlFMDbvGUvnzASB47BaXhm38m
	P9jU2kmTiDr2/DzTece8Q80ajaSk7ulM0ffnsp1EChHeQX5MYIfQ2XyKc/9b/XbzIjEdnCI2FMR
	do8XwTyc+ZIP0986tXAVKOgdKrYVerwF+4GAeK8aR/Jbwky2Se6DUUAA6kM3xxjbu1boOV+XhkH
	9UYLPal338t1JTaqAe+tepJ9as33ZoZjAlwuCWg2LnnQWGVNBAFvRxesFjcSnOcXcdtKJjOqitj
	/9KC8pe4R8ALx2tyXt0+iDbBvfDJ7Xy7SghhTZahMW9FMVbQ9gv1oMTAH/1w5dXkfVuOfuUWeGW
	cZ8/wjg==;
ARC-Authentication-Results: i=2; mx.cloudflare.net;
	dkim=pass header.d=gmail.com header.s=20251104 header.b=RhJtSQ5t;
	dmarc=pass header.from=gmail.com policy.dmarc=none;
	spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-dl2-x10.google.com) smtp.helo=mail-dl2-x10.google.com;
	spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:38::10 as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com;
	arc=pass smtp.remote-ip="2607:f8b0:4864:38::10"
Received-SPF: pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:38::10 as permitted sender)
	receiver=mx.cloudflare.net; client-ip=2607:f8b0:4864:38::10; envelope-from="godwinfranklin419@gmail.com"; helo=mail-dl2-x10.google.com;
Authentication-Results: mx.cloudflare.net;
	dkim=pass header.d=gmail.com header.s=20251104 header.b=RhJtSQ5t;
	dmarc=pass header.from=gmail.com policy.dmarc=none;
	spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-dl2-x10.google.com) smtp.helo=mail-dl2-x10.google.com;
	spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:38::10 as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com;
	arc=pass smtp.remote-ip="2607:f8b0:4864:38::10"
X-CF-SpamH-Score: 0
Received: by mail-dl2-x10.google.com with SMTP id a92af1059eb24-142dd05d97cso4212994c88.2
        for <info@worldchoiceperfume.com>; Mon, 28 Sep 2026 14:22:55 -0700 (PDT)
ARC-Seal: i=1; a=rsa-sha256; t=1790630575; cv=none;
        d=google.com; s=arc-20260327;
        b=R3qZQYGYQ1x2qqJEidlbvur1nZCIUFa+v+7jbeSLWZU+dcOalD2YGVBgAbLrQcNFx4
         iZWAVmEBo8Ea2Z69XIuzB9DooeJiZ/IpQIs1n0j+Ht5L5aP76WAJaRwyfhVEhuE7h4g2
         1PiT4WfMjhfCrZbbvmnl1rRTL9kQyKWqyBuzAAarKdk96LdXUnNtKWAx+XrUL4X5YLJi
         O4HonTtk9xCa2iBfMhGPnABSYDvMVeeAcmhnpvjp0VCj9O5tKLcDG88mKRXcX+8hqoiE
         mUgEcXLcZJMgqN1hDt5tq3KkeINEbvTBlFHcvuAgwXdlcShdLpyCGSg/NQRBJunTsYkg
         +sxw==
ARC-Message-Signature: i=1; a=rsa-sha256; c=relaxed/relaxed; d=google.com; s=arc-20260327;
        h=to:subject:message-id:date:from:mime-version:dkim-signature;
        bh=XKLvp40lotSEGls3Z/2lbZcsJ5wDNDKyxMP8i9pyZ2c=;
        fh=nniGTCdcezPCCWQcYQFtt+TqrAdcQ+jBfMQuxKQBEts=;
        b=OLEtn2z//I6ATAs1vPBFozm2DYBe0GLMbkdV4/oIgAd7qQM6VItDl4tNorj9C7mDEb
         XjEmcllZEPWDohps0L+h1P283syycgFA7qojirmXc87Bw+vLtOglprlhQ+OMAbDi0mMb
         TkXLLjuW3kFV5LL3G+Ad88N9hjyRTsmeA5wxx5MjB8ezR3K47zXozAx4spE08yIREdbh
         5myEOgD4iTnBw9aq1fx+WUahJ+r7PHIRyYAJodL8KHhhYj46GDGLRypQML+QvEyzgrE4
         TU419OLBrBbHisUMpURIfcJyj9v/K9Gokc86tPcLUsyuwsVjs8YoFkfilYRK38zqIPSY
         jXJg==;
        darn=worldchoiceperfume.com
ARC-Authentication-Results: i=1; mx.google.com; arc=none
DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
        d=gmail.com; s=20251104; t=1790630575; x=1791235375; darn=worldchoiceperfume.com;
        h=content-type:to:subject:message-id:date:from:mime-version:from:to
         :cc:subject:date:message-id:reply-to:content-type;
        bh=XKLvp40lotSEGls3Z/2lbZcsJ5wDNDKyxMP8i9pyZ2c=;
        b=RhJtSQ5tXFjZS9F/2iazM4hb4iz3jZwGp0SyvYH5bcqQRcGFHzMWKT3YBqGORjPMec
         iJtnsJmTAP6WyTwdjlaBS+68XKW88zoE5RrTCbnR8H28SzYvArQ7hnms4BOnf8DSCqqv
         /QUNEe8Vd2kBcn6oREHqBYrLhhaSdTRtOW189snriLpz5QP+rclPoZ+YaMX5AUwUiLke
         2KS8eQ35cO5NPq1ixvoGckUe5vUQcgg6aAshLEDSRo/YwkgBpb/kUegaB5vLDi4L3XAj
         GbXBE5MwITiCCLHopP24Y3mCiSj04ShMpVsm0Frmhq52JPVw/8/jJBWirN5nRo2Fn3KN
         gKhA==
X-Google-DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
        d=1e100.net; s=20260707; t=1790630575; x=1791235375;
        h=content-type:to:subject:message-id:date:from:mime-version:x-gm-gg
         :x-gm-message-state:from:to:cc:subject:date:message-id:reply-to
         :content-type;
        bh=XKLvp40lotSEGls3Z/2lbZcsJ5wDNDKyxMP8i9pyZ2c=;
        b=w+Vi/UsWL7f3zphVUUx2/CtLyZON7gTnOfpLlQhI7oK17EkE4hNtnwYRraIq7jVkpe
         pnt4ekytq0uhH899cpj5UOc32syeXpWpMxXYhfki/hNcghHDzKXlK1Zj/VxmX/oSTkk/
         l56xLU75H6LlzniQn4yNCrO6kdRRg3SRr8EzVoA41RdewoWWc3r/3dpMqKB3kdoEq20B
         Gz6PFZOT1GfZONXFFElr4S/4ak3wuIS9Nw9nY2/1TuG+5LoxGw5TkilZOYLLB6WVkdWT
         dK74JoUxa7vIkTEntHa7tTl/SqreU4WLdRsZi9hINlyQgof469/o8C0Yx0JU00EcDHtw
         Lmlg==
X-Gm-Message-State: AFuF++lnDPL/Awol2g/32+NFGAdpkZZXG7YeL9LcVeLbZKoiSvYZ/JYk
	FqZSk0K4AIxqorzlRcFabcSo2mYavK8ao51og/D/A9aymqn6zgd1mgn76X8at4YXfIFdhXTft6T
	g/oGrr77kG4OO6hsO2jsSOZaNuXY5M8cNx5yO
X-Gm-Gg: AYBFou3pGPASR250pZcX5b81uIFwNTlonJQmrM5ZUJBjd9hH6xSt8LUYWft6UvsBfUN
	55LG4aNgv3Dhcz/vCTqZ1baXTJNS6j+dEUYKorx8HVFB8Ocl6/nxCyyM+oVwRuN/aEDTYVQU1hd
	yy9MSlAmmg8cu9LZXiD4bbqSEnRqT3n8G+Qd0S6zpg628LG+bhpzR1nUVddsC7/zRc13Cv2JbUR
	A3b7BHvZ8iiBKoTfLfJu2wUwztJOvqNeKRIjx+cW8GKLE5GRc/bxm6lNnc2HQsQHpJ98gNOU4s4
	fmIlZL6Ng8mBPJrv8yTykwhrkK9CM4NNuqkxoP43CCTaq42R5Ws674e2
X-Received: by 2002:a05:701b:2506:b0:148:c6ec:13b3 with SMTP id
 a92af1059eb24-148c6ec17bcmr7425327c88.17.1790630574666; Mon, 28 Sep 2026
 14:22:54 -0700 (PDT)
MIME-Version: 1.0
From: FRANK GODWIN <godwinfranklin419@gmail.com>
Date: Tue, 29 Sep 2026 00:22:38 +0300
X-Gm-Features: AclHuK8Xrfug2vkv-NoQgEXV-3D3Gv9ZdqBQ4cP_D3ZkyZJz5wRe9dItN_QBORo
Message-ID: <CAOLv=Vv=AETvbj+7aZu8_3h+4xMxnvpwLRE6a7u8FG4_My5bLA@mail.gmail.com>
Subject: Hey
To: info@worldchoiceperfume.com
Content-Type: multipart/mixed; boundary="000000000000545c1f065c91ab40"

--000000000000545c1f065c91ab40
Content-Type: multipart/alternative; boundary="000000000000545c1d065c91ab3e"

--000000000000545c1d065c91ab3e
Content-Type: text/plain; charset="UTF-8"



--000000000000545c1d065c91ab3e
Content-Type: text/html; charset="UTF-8"

<div dir="auto"></div>

--000000000000545c1d065c91ab3e--
--000000000000545c1f065c91ab40
Content-Type: text/plain; charset="US-ASCII"; name="om_js_content.txt"
Content-Disposition: attachment; filename="om_js_content.txt"
Content-Transfer-Encoding: base64
Content-ID: <1a0e9e5e624cb89ea061>
X-Attachment-Id: 1a0e9e5e624cb89ea061

OyhmdW5jdGlvbihvbWlkR2xvYmFsKSB7CiAgJ3VzZSBzdHJpY3QnO3ZhciBuO2Z1bmN0aW9uIGFh
KGEpe3ZhciBiPTA7cmV0dXJuIGZ1bmN0aW9uKCl7cmV0dXJuIGI8YS5sZW5ndGg/e2RvbmU6ITEs
dmFsdWU6YVtiKytdfTp7ZG9uZTohMH19fWZ1bmN0aW9uIHAoYSl7dmFyIGI9J3VuZGVmaW5lZCch
PXR5cGVvZiBTeW1ib2wmJlN5bWJvbC5pdGVyYXRvciYmYVtTeW1ib2wuaXRlcmF0b3JdO3JldHVy
biBiP2IuY2FsbChhKTp7bmV4dDphYShhKX19ZnVuY3Rpb24gcShhKXtpZighKGEgaW5zdGFuY2Vv
ZiBBcnJheSkpe2E9cChhKTtmb3IodmFyIGIsYz1bXTshKGI9YS5uZXh0KCkpLmRvbmU7KWMucHVz
aChiLnZhbHVlKTthPWN9cmV0dXJuIGF9dmFyIGJhPSdmdW5jdGlvbic9PXR5cGVvZiBPYmplY3Qu
Y3JlYXRlP09iamVjdC5jcmVhdGU6ZnVuY3Rpb24oYSl7ZnVuY3Rpb24gYigpe31iLnByb3RvdHlw
ZT1hO3JldHVybiBuZXcgYn0sY2E7CmlmKCdmdW5jdGlvbic9PXR5cGVvZiBPYmplY3Quc2V0UHJv
dG90eXBlT2YpY2E9T2JqZWN0LnNldFByb3RvdHlwZU9mO2Vsc2V7dmFyIGRhO2E6e3ZhciBlYT17
UzohMH0sZmE9e307dHJ5e2ZhLl9fcHJvdG9fXz1lYTtkYT1mYS5TO2JyZWFrIGF9Y2F0Y2goYSl7
fWRhPSExfWNhPWRhP2Z1bmN0aW9uKGEsYil7YS5fX3Byb3RvX189YjtpZihhLl9fcHJvdG9fXyE9
PWIpdGhyb3cgbmV3IFR5cGVFcnJvcihhKycgaXMgbm90IGV4dGVuc2libGUnKTtyZXR1cm4gYX06
bnVsbH12YXIgaGE9Y2E7CmZ1bmN0aW9uIHIoYSxiKXthLnByb3RvdHlwZT1iYShiLnByb3RvdHlw
ZSk7YS5wcm90b3R5cGUuY29uc3RydWN0b3I9YTtpZihoYSloYShhLGIpO2Vsc2UgZm9yKHZhciBj
IGluIGIpaWYoJ3Byb3RvdHlwZSchPWMpaWYoT2JqZWN0LmRlZmluZVByb3BlcnRpZXMpe3ZhciBk
PU9iamVjdC5nZXRPd25Qcm9wZXJ0eURlc2NyaXB0b3IoYixjKTtkJiZPYmplY3QuZGVmaW5lUHJv
cGVydHkoYSxjLGQpfWVsc2UgYVtjXT1iW2NdO2EuQ2E9Yi5wcm90b3R5cGV9dmFyIHQ9J3VuZGVm
aW5lZCchPXR5cGVvZiB3aW5kb3cmJndpbmRvdz09PXRoaXM/dGhpczondW5kZWZpbmVkJyE9dHlw
ZW9mIGdsb2JhbCYmbnVsbCE9Z2xvYmFsP2dsb2JhbDp0aGlzO2Z1bmN0aW9uIHUoYSxiKXtyZXR1
cm4gT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKGEsYil9CnZhciBpYT0nZnVu
Y3Rpb24nPT10eXBlb2YgT2JqZWN0LmFzc2lnbj9PYmplY3QuYXNzaWduOmZ1bmN0aW9uKGEsYil7
Zm9yKHZhciBjPTE7Yzxhcmd1bWVudHMubGVuZ3RoO2MrKyl7dmFyIGQ9YXJndW1lbnRzW2NdO2lm
KGQpZm9yKHZhciBlIGluIGQpdShkLGUpJiYoYVtlXT1kW2VdKX1yZXR1cm4gYX0sdz0nZnVuY3Rp
b24nPT10eXBlb2YgT2JqZWN0LmRlZmluZVByb3BlcnRpZXM/T2JqZWN0LmRlZmluZVByb3BlcnR5
OmZ1bmN0aW9uKGEsYixjKXthIT1BcnJheS5wcm90b3R5cGUmJmEhPU9iamVjdC5wcm90b3R5cGUm
JihhW2JdPWMudmFsdWUpfTsKZnVuY3Rpb24geShhLGIpe2lmKGIpe3ZhciBjPXQ7YT1hLnNwbGl0
KCcuJyk7Zm9yKHZhciBkPTA7ZDxhLmxlbmd0aC0xO2QrKyl7dmFyIGU9YVtkXTtlIGluIGN8fChj
W2VdPXt9KTtjPWNbZV19YT1hW2EubGVuZ3RoLTFdO2Q9Y1thXTtiPWIoZCk7YiE9ZCYmbnVsbCE9
YiYmdyhjLGEse2NvbmZpZ3VyYWJsZTohMCx3cml0YWJsZTohMCx2YWx1ZTpifSl9fXkoJ09iamVj
dC5hc3NpZ24nLGZ1bmN0aW9uKGEpe3JldHVybiBhfHxpYX0pO2Z1bmN0aW9uIGphKCl7amE9ZnVu
Y3Rpb24oKXt9O3QuU3ltYm9sfHwodC5TeW1ib2w9a2EpfWZ1bmN0aW9uIGxhKGEsYil7dGhpcy5h
PWE7dyh0aGlzLCdkZXNjcmlwdGlvbicse2NvbmZpZ3VyYWJsZTohMCx3cml0YWJsZTohMCx2YWx1
ZTpifSl9bGEucHJvdG90eXBlLnRvU3RyaW5nPWZ1bmN0aW9uKCl7cmV0dXJuIHRoaXMuYX07CnZh
ciBrYT1mdW5jdGlvbigpe2Z1bmN0aW9uIGEoYyl7aWYodGhpcyBpbnN0YW5jZW9mIGEpdGhyb3cg
bmV3IFR5cGVFcnJvcignU3ltYm9sIGlzIG5vdCBhIGNvbnN0cnVjdG9yJyk7cmV0dXJuIG5ldyBs
YSgnanNjb21wX3N5bWJvbF8nKyhjfHwnJykrJ18nK2IrKyxjKX12YXIgYj0wO3JldHVybiBhfSgp
O2Z1bmN0aW9uIG1hKCl7amEoKTt2YXIgYT10LlN5bWJvbC5pdGVyYXRvcjthfHwoYT10LlN5bWJv
bC5pdGVyYXRvcj10LlN5bWJvbCgnU3ltYm9sLml0ZXJhdG9yJykpOydmdW5jdGlvbichPXR5cGVv
ZiBBcnJheS5wcm90b3R5cGVbYV0mJncoQXJyYXkucHJvdG90eXBlLGEse2NvbmZpZ3VyYWJsZToh
MCx3cml0YWJsZTohMCx2YWx1ZTpmdW5jdGlvbigpe3JldHVybiBuYShhYSh0aGlzKSl9fSk7bWE9
ZnVuY3Rpb24oKXt9fWZ1bmN0aW9uIG5hKGEpe21hKCk7YT17bmV4dDphfTthW3QuU3ltYm9sLml0
ZXJhdG9yXT1mdW5jdGlvbigpe3JldHVybiB0aGlzfTtyZXR1cm4gYX0KeSgnV2Vha01hcCcsZnVu
Y3Rpb24oYSl7ZnVuY3Rpb24gYihnKXt0aGlzLmE9KGwrPU1hdGgucmFuZG9tKCkrMSkudG9TdHJp
bmcoKTtpZihnKXtnPXAoZyk7Zm9yKHZhciBoOyEoaD1nLm5leHQoKSkuZG9uZTspaD1oLnZhbHVl
LHRoaXMuc2V0KGhbMF0saFsxXSl9fWZ1bmN0aW9uIGMoKXt9ZnVuY3Rpb24gZChnKXtpZighdShn
LGYpKXt2YXIgaD1uZXcgYzt3KGcsZix7dmFsdWU6aH0pfX1mdW5jdGlvbiBlKGcpe3ZhciBoPU9i
amVjdFtnXTtoJiYoT2JqZWN0W2ddPWZ1bmN0aW9uKGspe2lmKGsgaW5zdGFuY2VvZiBjKXJldHVy
biBrO2Qoayk7cmV0dXJuIGgoayl9KX1pZihmdW5jdGlvbigpe2lmKCFhfHwhT2JqZWN0LnNlYWwp
cmV0dXJuITE7dHJ5e3ZhciBnPU9iamVjdC5zZWFsKHt9KSxoPU9iamVjdC5zZWFsKHt9KSxrPW5l
dyBhKFtbZywyXSxbaCwzXV0pO2lmKDIhPWsuZ2V0KGcpfHwzIT1rLmdldChoKSlyZXR1cm4hMTtr
LmRlbGV0ZShnKTtrLnNldChoLDQpO3JldHVybiFrLmhhcyhnKSYmCjQ9PWsuZ2V0KGgpfWNhdGNo
KG0pe3JldHVybiExfX0oKSlyZXR1cm4gYTt2YXIgZj0nJGpzY29tcF9oaWRkZW5fJytNYXRoLnJh
bmRvbSgpO2UoJ2ZyZWV6ZScpO2UoJ3ByZXZlbnRFeHRlbnNpb25zJyk7ZSgnc2VhbCcpO3ZhciBs
PTA7Yi5wcm90b3R5cGUuc2V0PWZ1bmN0aW9uKGcsaCl7ZChnKTtpZighdShnLGYpKXRocm93IEVy
cm9yKCdXZWFrTWFwIGtleSBmYWlsOiAnK2cpO2dbZl1bdGhpcy5hXT1oO3JldHVybiB0aGlzfTti
LnByb3RvdHlwZS5nZXQ9ZnVuY3Rpb24oZyl7cmV0dXJuIHUoZyxmKT9nW2ZdW3RoaXMuYV06dm9p
ZCAwfTtiLnByb3RvdHlwZS5oYXM9ZnVuY3Rpb24oZyl7cmV0dXJuIHUoZyxmKSYmdShnW2ZdLHRo
aXMuYSl9O2IucHJvdG90eXBlLmRlbGV0ZT1mdW5jdGlvbihnKXtyZXR1cm4gdShnLGYpJiZ1KGdb
Zl0sdGhpcy5hKT9kZWxldGUgZ1tmXVt0aGlzLmFdOiExfTtyZXR1cm4gYn0pOwp5KCdNYXAnLGZ1
bmN0aW9uKGEpe2Z1bmN0aW9uIGIoKXt2YXIgZz17fTtyZXR1cm4gZy5BPWcubmV4dD1nLmhlYWQ9
Z31mdW5jdGlvbiBjKGcsaCl7dmFyIGs9Zy5hO3JldHVybiBuYShmdW5jdGlvbigpe2lmKGspe2Zv
cig7ay5oZWFkIT1nLmE7KWs9ay5BO2Zvcig7ay5uZXh0IT1rLmhlYWQ7KXJldHVybiBrPWsubmV4
dCx7ZG9uZTohMSx2YWx1ZTpoKGspfTtrPW51bGx9cmV0dXJue2RvbmU6ITAsdmFsdWU6dm9pZCAw
fX0pfWZ1bmN0aW9uIGQoZyxoKXt2YXIgaz1oJiZ0eXBlb2YgaDsnb2JqZWN0Jz09a3x8J2Z1bmN0
aW9uJz09az9mLmhhcyhoKT9rPWYuZ2V0KGgpOihrPScnKyArK2wsZi5zZXQoaCxrKSk6az0ncF8n
K2g7dmFyIG09Zy5iW2tdO2lmKG0mJnUoZy5iLGspKWZvcihnPTA7ZzxtLmxlbmd0aDtnKyspe3Zh
ciB2PW1bZ107aWYoaCE9PWgmJnYua2V5IT09di5rZXl8fGg9PT12LmtleSlyZXR1cm57aWQ6ayxs
aXN0Om0saW5kZXg6ZyxzOnZ9fXJldHVybntpZDprLGxpc3Q6bSwKaW5kZXg6LTEsczp2b2lkIDB9
fWZ1bmN0aW9uIGUoZyl7dGhpcy5iPXt9O3RoaXMuYT1iKCk7dGhpcy5zaXplPTA7aWYoZyl7Zz1w
KGcpO2Zvcih2YXIgaDshKGg9Zy5uZXh0KCkpLmRvbmU7KWg9aC52YWx1ZSx0aGlzLnNldChoWzBd
LGhbMV0pfX1pZihmdW5jdGlvbigpe2lmKCFhfHwnZnVuY3Rpb24nIT10eXBlb2YgYXx8IWEucHJv
dG90eXBlLmVudHJpZXN8fCdmdW5jdGlvbichPXR5cGVvZiBPYmplY3Quc2VhbClyZXR1cm4hMTt0
cnl7dmFyIGc9T2JqZWN0LnNlYWwoe3g6NH0pLGg9bmV3IGEocChbW2csJ3MnXV0pKTtpZigncych
PWguZ2V0KGcpfHwxIT1oLnNpemV8fGguZ2V0KHt4OjR9KXx8aC5zZXQoe3g6NH0sJ3QnKSE9aHx8
MiE9aC5zaXplKXJldHVybiExO3ZhciBrPWguZW50cmllcygpLG09ay5uZXh0KCk7aWYobS5kb25l
fHxtLnZhbHVlWzBdIT1nfHwncychPW0udmFsdWVbMV0pcmV0dXJuITE7bT1rLm5leHQoKTtyZXR1
cm4gbS5kb25lfHw0IT1tLnZhbHVlWzBdLnh8fAondCchPW0udmFsdWVbMV18fCFrLm5leHQoKS5k
b25lPyExOiEwfWNhdGNoKHYpe3JldHVybiExfX0oKSlyZXR1cm4gYTttYSgpO3ZhciBmPW5ldyBX
ZWFrTWFwO2UucHJvdG90eXBlLnNldD1mdW5jdGlvbihnLGgpe2c9MD09PWc/MDpnO3ZhciBrPWQo
dGhpcyxnKTtrLmxpc3R8fChrLmxpc3Q9dGhpcy5iW2suaWRdPVtdKTtrLnM/ay5zLnZhbHVlPWg6
KGsucz17bmV4dDp0aGlzLmEsQTp0aGlzLmEuQSxoZWFkOnRoaXMuYSxrZXk6Zyx2YWx1ZTpofSxr
Lmxpc3QucHVzaChrLnMpLHRoaXMuYS5BLm5leHQ9ay5zLHRoaXMuYS5BPWsucyx0aGlzLnNpemUr
Kyk7cmV0dXJuIHRoaXN9O2UucHJvdG90eXBlLmRlbGV0ZT1mdW5jdGlvbihnKXtnPWQodGhpcyxn
KTtyZXR1cm4gZy5zJiZnLmxpc3Q/KGcubGlzdC5zcGxpY2UoZy5pbmRleCwxKSxnLmxpc3QubGVu
Z3RofHxkZWxldGUgdGhpcy5iW2cuaWRdLGcucy5BLm5leHQ9Zy5zLm5leHQsZy5zLm5leHQuQT1n
LnMuQSxnLnMuaGVhZD1udWxsLAp0aGlzLnNpemUtLSwhMCk6ITF9O2UucHJvdG90eXBlLmNsZWFy
PWZ1bmN0aW9uKCl7dGhpcy5iPXt9O3RoaXMuYT10aGlzLmEuQT1iKCk7dGhpcy5zaXplPTB9O2Uu
cHJvdG90eXBlLmhhcz1mdW5jdGlvbihnKXtyZXR1cm4hIWQodGhpcyxnKS5zfTtlLnByb3RvdHlw
ZS5nZXQ9ZnVuY3Rpb24oZyl7cmV0dXJuKGc9ZCh0aGlzLGcpLnMpJiZnLnZhbHVlfTtlLnByb3Rv
dHlwZS5lbnRyaWVzPWZ1bmN0aW9uKCl7cmV0dXJuIGModGhpcyxmdW5jdGlvbihnKXtyZXR1cm5b
Zy5rZXksZy52YWx1ZV19KX07ZS5wcm90b3R5cGUua2V5cz1mdW5jdGlvbigpe3JldHVybiBjKHRo
aXMsZnVuY3Rpb24oZyl7cmV0dXJuIGcua2V5fSl9O2UucHJvdG90eXBlLnZhbHVlcz1mdW5jdGlv
bigpe3JldHVybiBjKHRoaXMsZnVuY3Rpb24oZyl7cmV0dXJuIGcudmFsdWV9KX07ZS5wcm90b3R5
cGUuZm9yRWFjaD1mdW5jdGlvbihnLGgpe2Zvcih2YXIgaz10aGlzLmVudHJpZXMoKSxtOyEobT1r
Lm5leHQoKSkuZG9uZTspbT0KbS52YWx1ZSxnLmNhbGwoaCxtWzFdLG1bMF0sdGhpcyl9O2UucHJv
dG90eXBlW1N5bWJvbC5pdGVyYXRvcl09ZS5wcm90b3R5cGUuZW50cmllczt2YXIgbD0wO3JldHVy
biBlfSk7eSgnT2JqZWN0LnZhbHVlcycsZnVuY3Rpb24oYSl7cmV0dXJuIGE/YTpmdW5jdGlvbihi
KXt2YXIgYz1bXSxkO2ZvcihkIGluIGIpdShiLGQpJiZjLnB1c2goYltkXSk7cmV0dXJuIGN9fSk7
CnkoJ1NldCcsZnVuY3Rpb24oYSl7ZnVuY3Rpb24gYihjKXt0aGlzLmE9bmV3IE1hcDtpZihjKXtj
PXAoYyk7Zm9yKHZhciBkOyEoZD1jLm5leHQoKSkuZG9uZTspdGhpcy5hZGQoZC52YWx1ZSl9dGhp
cy5zaXplPXRoaXMuYS5zaXplfWlmKGZ1bmN0aW9uKCl7aWYoIWF8fCdmdW5jdGlvbichPXR5cGVv
ZiBhfHwhYS5wcm90b3R5cGUuZW50cmllc3x8J2Z1bmN0aW9uJyE9dHlwZW9mIE9iamVjdC5zZWFs
KXJldHVybiExO3RyeXt2YXIgYz1PYmplY3Quc2VhbCh7eDo0fSksZD1uZXcgYShwKFtjXSkpO2lm
KCFkLmhhcyhjKXx8MSE9ZC5zaXplfHxkLmFkZChjKSE9ZHx8MSE9ZC5zaXplfHxkLmFkZCh7eDo0
fSkhPWR8fDIhPWQuc2l6ZSlyZXR1cm4hMTt2YXIgZT1kLmVudHJpZXMoKSxmPWUubmV4dCgpO2lm
KGYuZG9uZXx8Zi52YWx1ZVswXSE9Y3x8Zi52YWx1ZVsxXSE9YylyZXR1cm4hMTtmPWUubmV4dCgp
O3JldHVybiBmLmRvbmV8fGYudmFsdWVbMF09PWN8fDQhPWYudmFsdWVbMF0ueHx8CmYudmFsdWVb
MV0hPWYudmFsdWVbMF0/ITE6ZS5uZXh0KCkuZG9uZX1jYXRjaChsKXtyZXR1cm4hMX19KCkpcmV0
dXJuIGE7bWEoKTtiLnByb3RvdHlwZS5hZGQ9ZnVuY3Rpb24oYyl7Yz0wPT09Yz8wOmM7dGhpcy5h
LnNldChjLGMpO3RoaXMuc2l6ZT10aGlzLmEuc2l6ZTtyZXR1cm4gdGhpc307Yi5wcm90b3R5cGUu
ZGVsZXRlPWZ1bmN0aW9uKGMpe2M9dGhpcy5hLmRlbGV0ZShjKTt0aGlzLnNpemU9dGhpcy5hLnNp
emU7cmV0dXJuIGN9O2IucHJvdG90eXBlLmNsZWFyPWZ1bmN0aW9uKCl7dGhpcy5hLmNsZWFyKCk7
dGhpcy5zaXplPTB9O2IucHJvdG90eXBlLmhhcz1mdW5jdGlvbihjKXtyZXR1cm4gdGhpcy5hLmhh
cyhjKX07Yi5wcm90b3R5cGUuZW50cmllcz1mdW5jdGlvbigpe3JldHVybiB0aGlzLmEuZW50cmll
cygpfTtiLnByb3RvdHlwZS52YWx1ZXM9ZnVuY3Rpb24oKXtyZXR1cm4gdGhpcy5hLnZhbHVlcygp
fTtiLnByb3RvdHlwZS5rZXlzPWIucHJvdG90eXBlLnZhbHVlczsKYi5wcm90b3R5cGVbU3ltYm9s
Lml0ZXJhdG9yXT1iLnByb3RvdHlwZS52YWx1ZXM7Yi5wcm90b3R5cGUuZm9yRWFjaD1mdW5jdGlv
bihjLGQpe3ZhciBlPXRoaXM7dGhpcy5hLmZvckVhY2goZnVuY3Rpb24oZil7cmV0dXJuIGMuY2Fs
bChkLGYsZixlKX0pfTtyZXR1cm4gYn0pO3koJ09iamVjdC5pcycsZnVuY3Rpb24oYSl7cmV0dXJu
IGE/YTpmdW5jdGlvbihiLGMpe3JldHVybiBiPT09Yz8wIT09Ynx8MS9iPT09MS9jOmIhPT1iJiZj
IT09Y319KTt5KCdBcnJheS5wcm90b3R5cGUuaW5jbHVkZXMnLGZ1bmN0aW9uKGEpe3JldHVybiBh
P2E6ZnVuY3Rpb24oYixjKXt2YXIgZD10aGlzO2QgaW5zdGFuY2VvZiBTdHJpbmcmJihkPVN0cmlu
ZyhkKSk7dmFyIGU9ZC5sZW5ndGg7Yz1jfHwwO2ZvcigwPmMmJihjPU1hdGgubWF4KGMrZSwwKSk7
YzxlO2MrKyl7dmFyIGY9ZFtjXTtpZihmPT09Ynx8T2JqZWN0LmlzKGYsYikpcmV0dXJuITB9cmV0
dXJuITF9fSk7CnkoJ09iamVjdC5lbnRyaWVzJyxmdW5jdGlvbihhKXtyZXR1cm4gYT9hOmZ1bmN0
aW9uKGIpe3ZhciBjPVtdLGQ7Zm9yKGQgaW4gYil1KGIsZCkmJmMucHVzaChbZCxiW2RdXSk7cmV0
dXJuIGN9fSk7CnZhciB6PXtxYTonbG9hZGVkJyx4YTonc3RhcnQnLGthOidmaXJzdFF1YXJ0aWxl
JyxzYTonbWlkcG9pbnQnLHlhOid0aGlyZFF1YXJ0aWxlJyxpYTonY29tcGxldGUnLHRhOidwYXVz
ZScsdmE6J3Jlc3VtZScsaGE6J2J1ZmZlclN0YXJ0JyxnYTonYnVmZmVyRmluaXNoJyx3YTonc2tp
cHBlZCcsQWE6J3ZvbHVtZUNoYW5nZScsdWE6J3BsYXllclN0YXRlQ2hhbmdlJyxkYTonYWRVc2Vy
SW50ZXJhY3Rpb24nfSxvYT17bmE6J2dlbmVyaWMnLHphOid2aWRlbycscmE6J21lZGlhJ30scWE9
e1I6J25hdGl2ZScsb2E6J2h0bWwnLFA6J2phdmFzY3JpcHQnfSxyYT17UjonbmF0aXZlJyxQOidq
YXZhc2NyaXB0JyxOT05FOidub25lJ30sc2E9e21hOidmdWxsJyxqYTonZG9tYWluJyxwYTonbGlt
aXRlZCd9LHRhPXtmYTonYmFja2dyb3VuZGVkJyxsYTonZm9yZWdyb3VuZGVkJ30sdWE9e2VhOidh
cHAnLEJhOid3ZWInfTtmdW5jdGlvbiBBKGEsYil7dGhpcy54PW51bGwhPWEueD9hLng6YS5sZWZ0
O3RoaXMueT1udWxsIT1hLnk/YS55OmEudG9wO3RoaXMud2lkdGg9YS53aWR0aDt0aGlzLmhlaWdo
dD1hLmhlaWdodDt0aGlzLmVuZFg9dGhpcy54K3RoaXMud2lkdGg7dGhpcy5lbmRZPXRoaXMueSt0
aGlzLmhlaWdodDt0aGlzLmFkU2Vzc2lvbklkPWEuYWRTZXNzaW9uSWR8fHZvaWQgMDt0aGlzLmlz
RnJpZW5kbHlPYnN0cnVjdGlvbkZvcj1hLmlzRnJpZW5kbHlPYnN0cnVjdGlvbkZvcnx8W107dGhp
cy5iPWEuZnJpZW5kbHlPYnN0cnVjdGlvbkNsYXNzfHx2b2lkIDA7dGhpcy5jPWEuZnJpZW5kbHlP
YnN0cnVjdGlvblB1cnBvc2V8fHZvaWQgMDt0aGlzLmY9YS5mcmllbmRseU9ic3RydWN0aW9uUmVh
c29ufHx2b2lkIDA7dGhpcy5jbGlwc1RvQm91bmRzPXZvaWQgMCE9PWEuY2xpcHNUb0JvdW5kcz8h
MD09PWEuY2xpcHNUb0JvdW5kczohMDt0aGlzLm5vdFZpc2libGVSZWFzb249YS5ub3RWaXNpYmxl
UmVhc29ufHwKdm9pZCAwO3RoaXMuY2hpbGRWaWV3cz1hLmNoaWxkVmlld3N8fFtdO3RoaXMuaXND
cmVhdGl2ZT1hLmlzQ3JlYXRpdmV8fCExO3RoaXMuYT1ifWZ1bmN0aW9uIHZhKGEpe3ZhciBiPXt9
O3JldHVybiBiLndpZHRoPWEud2lkdGgsYi5oZWlnaHQ9YS5oZWlnaHQsYn1mdW5jdGlvbiBDKGEp
e3ZhciBiPXt9O3JldHVybiBPYmplY3QuYXNzaWduKHt9LHZhKGEpLChiLng9YS54LGIueT1hLnks
YikpfWZ1bmN0aW9uIHdhKGEpe3ZhciBiPUMoYSksYz17fTtyZXR1cm4gT2JqZWN0LmFzc2lnbih7
fSxiLChjLmVuZFg9YS5lbmRYLGMuZW5kWT1hLmVuZFksYykpfWZ1bmN0aW9uIHhhKGEsYixjKXth
LngrPWI7YS55Kz1jO2EuZW5kWCs9YjthLmVuZFkrPWN9CkEucHJvdG90eXBlLko9ZnVuY3Rpb24o
YSl7aWYobnVsbD09YSlyZXR1cm4hMTthPUMoYSk7dmFyIGI9YS55LGM9YS53aWR0aCxkPWEuaGVp
Z2h0O3JldHVybiB0aGlzLng9PT1hLngmJnRoaXMueT09PWImJnRoaXMud2lkdGg9PT1jJiZ0aGlz
LmhlaWdodD09PWR9O2Z1bmN0aW9uIHlhKGEpe3JldHVybiBhLndpZHRoKmEuaGVpZ2h0fTtmdW5j
dGlvbiB6YShhLGIpe2E9QyhhKTtmb3IodmFyIGM9W10sZD1bXSxlPTA7ZTxiLmxlbmd0aDtlKysp
e3ZhciBmPUMoYltlXSk7Zj1BYShhLGYpO0JhKGMsZi54KTtCYShjLGYuZW5kWCk7QmEoZCxmLnkp
O0JhKGQsZi5lbmRZKX1jPWMuc29ydChmdW5jdGlvbihsLGcpe3JldHVybiBsLWd9KTtkPWQuc29y
dChmdW5jdGlvbihsLGcpe3JldHVybiBsLWd9KTtyZXR1cm57YmE6YyxjYTpkfX1mdW5jdGlvbiBB
YShhLGIpe3JldHVybnt4Ok1hdGgubWF4KGEueCxiLngpLHk6TWF0aC5tYXgoYS55LGIueSksZW5k
WDpNYXRoLm1pbihhLngrYS53aWR0aCxiLngrYi53aWR0aCksZW5kWTpNYXRoLm1pbihhLnkrYS5o
ZWlnaHQsYi55K2IuaGVpZ2h0KX19ZnVuY3Rpb24gQmEoYSxiKXstMT09PWEuaW5kZXhPZihiKSYm
YS5wdXNoKGIpfTtmdW5jdGlvbiBDYSgpe3RoaXMuYj10aGlzLmE9dGhpcy52PXRoaXMubD10aGlz
Lmc9dGhpcy5qPXZvaWQgMDt0aGlzLm09MDt0aGlzLmg9W107dGhpcy5vPVtdO3RoaXMudT0wO3Ro
aXMuaT1bXTt0aGlzLmM9W107dGhpcy5mPVtdfUNhLnByb3RvdHlwZS5KPWZ1bmN0aW9uKGEpe3Jl
dHVybiBudWxsPT1hPyExOkpTT04uc3RyaW5naWZ5KERhKHRoaXMpKT09PUpTT04uc3RyaW5naWZ5
KERhKGEpKX07CmZ1bmN0aW9uIERhKGEpe3ZhciBiPVtdLGM9W10sZD17dmlld3BvcnQ6YS5qLGFk
Vmlldzp7cGVyY2VudGFnZUluVmlldzphLm0scmVhc29uczphLmZ9LGRlY2xhcmVkRnJpZW5kbHlP
YnN0cnVjdGlvbnM6YS5oLmxlbmd0aH07aWYodm9pZCAwIT09YS5hKXtkLmFkVmlldy5nZW9tZXRy
eT1DKGEuYSk7ZC5hZFZpZXcuZ2VvbWV0cnkucGl4ZWxzPXlhKGEuYSk7ZC5hZFZpZXcub25TY3Jl
ZW5HZW9tZXRyeT1DKGEuYik7ZC5hZFZpZXcub25TY3JlZW5HZW9tZXRyeS5waXhlbHM9YS51O2Zv
cih2YXIgZT0wO2U8YS5jLmxlbmd0aDtlKyspYi5wdXNoKEMoYS5jW2VdKSk7Zm9yKGU9MDtlPGEu
by5sZW5ndGg7ZSsrKXt2YXIgZj1hLm9bZV0sbD1mLGc9e307bC5iJiYoZy5vYnN0cnVjdGlvbkNs
YXNzPWwuYik7bC5jJiYoZy5vYnN0cnVjdGlvblB1cnBvc2U9bC5jKTtsLmYmJihnLm9ic3RydWN0
aW9uUmVhc29uPWwuZik7Zj1BYShhLmEsZik7Yy5wdXNoKE9iamVjdC5hc3NpZ24oe30se3g6Zi54
LAp5OmYueSx3aWR0aDpmLmVuZFgtZi54LGhlaWdodDpmLmVuZFktZi55fSxnKSl9ZC5hZFZpZXcu
b25TY3JlZW5HZW9tZXRyeS5vYnN0cnVjdGlvbnM9YjtkLmFkVmlldy5vblNjcmVlbkdlb21ldHJ5
LmZyaWVuZGx5T2JzdHJ1Y3Rpb25zPWM7dm9pZCAwIT09YS5sJiZ2b2lkIDAhPT1hLnYmJihkLmFk
Vmlldy5jb250YWluZXJHZW9tZXRyeT1DKGEubCksZC5hZFZpZXcub25TY3JlZW5Db250YWluZXJH
ZW9tZXRyeT1DKGEudiksZC5hZFZpZXcubWVhc3VyaW5nRWxlbWVudD0hMCl9cmV0dXJuIGR9ZnVu
Y3Rpb24gRWEoYSxiKXtiPXZhKGIpO2Euaj17fTthLmoud2lkdGg9Yi53aWR0aDthLmouaGVpZ2h0
PWIuaGVpZ2h0O2EuZz17fTthLmcueD0wO2EuZy55PTA7YS5nLndpZHRoPWIud2lkdGg7YS5nLmhl
aWdodD1iLmhlaWdodDthLmcuZW5kWD1iLndpZHRoO2EuZy5lbmRZPWIuaGVpZ2h0fQpmdW5jdGlv
biBGYSgpe3JldHVybnt4OjAseTowLGVuZFg6MCxlbmRZOjAsd2lkdGg6MCxoZWlnaHQ6MH19ZnVu
Y3Rpb24gR2EoYSxiKXt2YXIgYz17fTtjLng9TWF0aC5tYXgoYS54LGIueCk7Yy55PU1hdGgubWF4
KGEueSxiLnkpO2MuZW5kWD1NYXRoLm1pbihhLmVuZFgsYi5lbmRYKTtjLmVuZFk9TWF0aC5taW4o
YS5lbmRZLGIuZW5kWSk7Yy53aWR0aD1NYXRoLm1heCgwLGMuZW5kWC1jLngpO2MuaGVpZ2h0PU1h
dGgubWF4KDAsYy5lbmRZLWMueSk7cmV0dXJuIGN9ZnVuY3Rpb24gSGEoYSxiKXtyZXR1cm4gYS53
aWR0aDxiLndpZHRofHxhLmhlaWdodDxiLmhlaWdodH0KZnVuY3Rpb24gSWEoYSl7aWYoLTEhPT1h
LmYuaW5kZXhPZignYmFja2dyb3VuZGVkJykpYS5tPTAsYS51PTA7ZWxzZXt2YXIgYj15YShhLmEp
O2lmKDAhPT1iKXt2YXIgYz15YShhLmIpO3ZhciBkPWEuYyxlPTA7aWYoMDxkLmxlbmd0aCl7dmFy
IGY9emEoYS5iLGQpLGw9Zi5iYTtmPWYuY2E7Zm9yKHZhciBnPTA7ZzxsLmxlbmd0aC0xO2crKylm
b3IodmFyIGg9KGxbZ10rKGxbZ10rMSkpLzIsaz1sW2crMV0tbFtnXSxtPTA7bTxmLmxlbmd0aC0x
O20rKyl7Zm9yKHZhciB2PShmW21dKyhmW21dKzEpKS8yLEI9ZlttKzFdLWZbbV0seD0hMSxGPTA7
RjxkLmxlbmd0aDtGKyspe3ZhciBLPUMoZFtGXSk7aWYoSy54PGgmJksueCtLLndpZHRoPmgmJksu
eTx2JiZLLnkrSy5oZWlnaHQ+dil7eD0hMDticmVha319eCYmKGUrPU1hdGgucm91bmQoaykqTWF0
aC5yb3VuZChCKSl9fWMtPWU7Yj1NYXRoLnJvdW5kKGMvYioxMDApO2EubT1NYXRoLm1heChiLDAp
O2EudT1NYXRoLm1heChjLDApfX19CmZ1bmN0aW9uIEphKGEsYil7aWYoMCE9PWIud2lkdGgmJjAh
PT1iLmhlaWdodCYmYS5iKXthPXdhKGEuYik7dmFyIGM9YS55LGQ9YS5lbmRYLGU9YS5lbmRZO2I9
IShiLmVuZFg8PWEueHx8Yi54Pj1kfHxiLmVuZFk8PWN8fGIueT49ZSl9ZWxzZSBiPSExO3JldHVy
biBifWZ1bmN0aW9uIEQoYSxiKXtmb3IodmFyIGM9ITEsZD0wO2Q8YS5mLmxlbmd0aDtkKyspYS5m
W2RdPT09YiYmKGM9ITApO2N8fGEuZi5wdXNoKGIpfTtmdW5jdGlvbiBLYShhLGIsYyxkLGUpe3Zh
ciBmPW5ldyBDYTtiPW5ldyBBKGIsITEpO0VhKGYsYik7TGEoYSxiLGYsZCk7aWYoIWUpcmV0dXJu
IGYuZj1bJ3VubWVhc3VyYWJsZSddLGYuaj12b2lkIDAsZi5tPTAsZi5jPVtdLGYuYSYmKGE9Zi5h
LGM9e30sYT1uZXcgQSgoYy54PTAsYy55PTAsYy53aWR0aD1hLndpZHRoLGMuaGVpZ2h0PWEuaGVp
Z2h0LGMpLGEuYSksZi5hPWEpLGYuYj1GYSgpLGY7aWYoJ2JhY2tncm91bmRlZCc9PT1jKUQoZiwn
YmFja2dyb3VuZGVkJyk7ZWxzZSBpZih2b2lkIDAhPT1mLmEpe2ZvcihhPTA7YTxmLmgubGVuZ3Ro
O2ErKylKYShmLGYuaFthXSkmJmYuby5wdXNoKGYuaFthXSk7Zm9yKGE9MDthPGYuaS5sZW5ndGg7
YSsrKXtpZihjPUphKGYsZi5pW2FdKSl7YTp7Yz1mLmlbYV07Zm9yKGQ9MDtkPGYuYy5sZW5ndGg7
ZCsrKWlmKGYuY1tkXS5KKGMpKXtjPSEwO2JyZWFrIGF9Yz0hMX1jPSFjfWMmJihEKGYsJ29ic3Ry
dWN0ZWQnKSxmLmMucHVzaChmLmlbYV0pKX1JYShmKX1lbHNlIEQoZiwKJ25vdEZvdW5kJyk7cmV0
dXJuIGZ9CmZ1bmN0aW9uIExhKGEsYixjLGQpe3ZhciBlPWIuaXNDcmVhdGl2ZT8hMDpiLmFkU2Vz
c2lvbklkPT09ZDtpZihlKXtjLmE9Yjt2YXIgZj13YShjLmEpO2E9R2EoYy5nLGYpO3ZhciBsPWMu
YTsnbm90QXR0YWNoZWQnPT09bC5ub3RWaXNpYmxlUmVhc29ufHwnbm9XaW5kb3dGb2N1cyc9PT1s
Lm5vdFZpc2libGVSZWFzb258fCdub0FkVmlldyc9PT1sLm5vdFZpc2libGVSZWFzb24/KEQoYywn
bm90Rm91bmQnKSxjLmI9bmV3IEEoRmEoKSwhMSkpOihsPWMuYSwndmlld0ludmlzaWJsZSc9PT1s
Lm5vdFZpc2libGVSZWFzb258fCd2aWV3R29uZSc9PT1sLm5vdFZpc2libGVSZWFzb258fCd2aWV3
Tm90VmlzaWJsZSc9PT1sLm5vdFZpc2libGVSZWFzb258fCd2aWV3QWxwaGFaZXJvJz09PWwubm90
VmlzaWJsZVJlYXNvbnx8J3ZpZXdIaWRkZW4nPT09bC5ub3RWaXNpYmxlUmVhc29ufHx2b2lkIDAh
PT1jLmEubm90VmlzaWJsZVJlYXNvbj8oRChjLCdoaWRkZW4nKSxjLmI9bmV3IEEoRmEoKSwKITEp
KTooSGEoYSxmKSYmRChjLCdjbGlwcGVkJyksYy5iPW5ldyBBKGEsITEpKSl9ZWxzZSBpZihmPSEw
LGIuYSYmKGY9LTEhPT1iLmlzRnJpZW5kbHlPYnN0cnVjdGlvbkZvci5pbmRleE9mKGQpPyExOiEx
PT09Yi5jbGlwc1RvQm91bmRzKSxmKXtsPWIuY2hpbGRWaWV3cztmb3IodmFyIGc9MDtnPGwubGVu
Z3RoO2crKylmPXZvaWQgMCE9PWMuYSxMYShhLG5ldyBBKGxbZ10sZiksYyxkKX1lfHx2b2lkIDA9
PT1jLmF8fChiLmE/LTEhPT1iLmlzRnJpZW5kbHlPYnN0cnVjdGlvbkZvci5pbmRleE9mKGQpP2Mu
aC5wdXNoKGIpOmMuaS5wdXNoKGIpOihlPXdhKGIpLGQ9d2EoYy5iKSxDKGMuYiksYT1jLmIsMCE9
PWEud2lkdGgmJjAhPT1hLmhlaWdodCYmYi5jbGlwc1RvQm91bmRzJiYoYj1HYShkLGUpLEhhKGIs
ZCkmJihEKGMsJ2NsaXBwZWQnKSxjLmI9bmV3IEEoYiwhMSkpKSkpfTtmdW5jdGlvbiBNYShhLGIp
e3RoaXMueT10aGlzLng9MDt0aGlzLndpZHRoPWE7dGhpcy5oZWlnaHQ9Yn07ZnVuY3Rpb24gTmEo
KXtyZXR1cm57YXBpVmVyc2lvbjonMS4wJyxhY2Nlc3NNb2RlOidsaW1pdGVkJyxlbnZpcm9ubWVu
dDonYXBwJyxvbWlkSnNJbmZvOntvbWlkSW1wbGVtZW50ZXI6J29tc2RrJyxzZXJ2aWNlVmVyc2lv
bjonMS4zLjIwLWlhYjI4MjInfX19ZnVuY3Rpb24gT2EoKXt0aGlzLmFkU2Vzc2lvbklkPW51bGw7
dGhpcy5jPU5hKCk7dGhpcy5vPW51bGw7dGhpcy5tPSdmb3JlZ3JvdW5kZWQnO3RoaXMubD10aGlz
Lmk9J25vbmUnO3RoaXMuaj10aGlzLmc9dGhpcy5mPXRoaXMuaD10aGlzLmE9dGhpcy5iPXRoaXMu
Qj10aGlzLnU9bnVsbDt0aGlzLkM9ITA7dGhpcy52PW5ldyBNYXB9dmFyIEc7ZnVuY3Rpb24gSCgp
e0d8fChHPW5ldyBPYSk7cmV0dXJuIEd9O3ZhciBQYT1ldmFsKCd0aGlzJyksST1mdW5jdGlvbigp
e2lmKCd1bmRlZmluZWQnIT09dHlwZW9mIG9taWRHbG9iYWwmJm9taWRHbG9iYWwpcmV0dXJuIG9t
aWRHbG9iYWw7aWYoJ3VuZGVmaW5lZCchPT10eXBlb2YgZ2xvYmFsJiZnbG9iYWwpcmV0dXJuIGds
b2JhbDtpZigndW5kZWZpbmVkJyE9PXR5cGVvZiB3aW5kb3cmJndpbmRvdylyZXR1cm4gd2luZG93
O2lmKCd1bmRlZmluZWQnIT09dHlwZW9mIFBhJiZQYSlyZXR1cm4gUGE7dGhyb3cgRXJyb3IoJ0Nv
dWxkIG5vdCBkZXRlcm1pbmUgZ2xvYmFsIG9iamVjdCBjb250ZXh0LicpO30oKTtmdW5jdGlvbiBR
YShhLGIpe3RoaXMuYT1hO3RoaXMuYj1ifXQuT2JqZWN0LmRlZmluZVByb3BlcnRpZXMoUWEucHJv
dG90eXBlLHtldmVudDp7Y29uZmlndXJhYmxlOiEwLGVudW1lcmFibGU6ITAsZ2V0OmZ1bmN0aW9u
KCl7cmV0dXJuIHRoaXMuYX19LG9yaWdpbjp7Y29uZmlndXJhYmxlOiEwLGVudW1lcmFibGU6ITAs
Z2V0OmZ1bmN0aW9uKCl7cmV0dXJuIHRoaXMuYn19fSk7ZnVuY3Rpb24gSihhKXtmb3IodmFyIGI9
W10sYz0wO2M8YXJndW1lbnRzLmxlbmd0aDsrK2MpYltjXT1hcmd1bWVudHNbY107UmEoZnVuY3Rp
b24oKXt0aHJvdyBuZXcgKEZ1bmN0aW9uLnByb3RvdHlwZS5iaW5kLmFwcGx5KEVycm9yLFtudWxs
LCdDb3VsZCBub3QgY29tcGxldGUgdGhlIHRlc3Qgc3VjY2Vzc2Z1bGx5IC0gJ10uY29uY2F0KHEo
YikpKSk7fSxmdW5jdGlvbigpe3JldHVybiBjb25zb2xlLmVycm9yLmFwcGx5KGNvbnNvbGUscShi
KSl9KX1mdW5jdGlvbiBTYShhKXtmb3IodmFyIGI9W10sYz0wO2M8YXJndW1lbnRzLmxlbmd0aDsr
K2MpYltjXT1hcmd1bWVudHNbY107UmEoZnVuY3Rpb24oKXt9LGZ1bmN0aW9uKCl7cmV0dXJuIGNv
bnNvbGUuZXJyb3IuYXBwbHkoY29uc29sZSxxKGIpKX0pfQpmdW5jdGlvbiBSYShhLGIpeyd1bmRl
ZmluZWQnIT09dHlwZW9mIGphc21pbmUmJmphc21pbmU/YSgpOid1bmRlZmluZWQnIT09dHlwZW9m
IGNvbnNvbGUmJmNvbnNvbGUmJmNvbnNvbGUuZXJyb3ImJmIoKX07ZnVuY3Rpb24gVGEoKXt0aGlz
LmY9W107dGhpcy5iPVtdO3RoaXMuYz1bXTt0aGlzLmc9W107dGhpcy5pPXt9O3RoaXMuYT1IKCl9
ZnVuY3Rpb24gVWEoYSl7YS5mPVtdO2EuYj1bXTthLmM9W107YS5nPVtdO2EuaT17fTtHLmFkU2Vz
c2lvbklkPW51bGw7Ry5jPU5hKCk7Ry5vPW51bGw7Ry5HPXZvaWQgMDtHLks9dm9pZCAwO0cuSD1u
dWxsO0cuST1udWxsO0cuRD1udWxsO0cubT0nZm9yZWdyb3VuZGVkJztHLmk9J25vbmUnO0cubD0n
bm9uZSc7Ry51PW51bGw7Ry5CPW51bGw7Ry5iPW51bGw7Ry5hPW51bGw7Ry5oPW51bGw7Ry5mPW51
bGw7Ry5nPW51bGw7Ry5qPW51bGw7Ry5DPSEwO0cudj1uZXcgTWFwfQpmdW5jdGlvbiBWYShhLGIp
e3ZvaWQgMCE9PWEuYSYmYS5hLmFkU2Vzc2lvbklkJiYhMSE9PVdhKGEsYikmJmEuYy5maWx0ZXIo
ZnVuY3Rpb24oYyl7cmV0dXJuIGMudHlwZT09PWIuZXZlbnQudHlwZX0pLmZvckVhY2goZnVuY3Rp
b24oYyl7cmV0dXJuIGEuaChjLkYsYi5ldmVudCl9KX1mdW5jdGlvbiBYYShhLGIpe2EuZi5wdXNo
KGIpO1ZhKGEsYil9ZnVuY3Rpb24gWWEoYSxiLGMpe3ZvaWQgMCE9PWEuYSYmYS5hLmFkU2Vzc2lv
bklkJiZhLmYuZmlsdGVyKGZ1bmN0aW9uKGQpe3JldHVybiBkLmV2ZW50LnR5cGU9PT1iJiZXYShh
LGQpfSkubWFwKGZ1bmN0aW9uKGQpe3JldHVybiBkLmV2ZW50fSkuZm9yRWFjaChjKX0KZnVuY3Rp
b24gV2EoYSxiKXt2YXIgYz1iLmV2ZW50LnR5cGUsZD0tMSE9PU9iamVjdC52YWx1ZXMoeikuaW5k
ZXhPZihjKSYmJ3ZvbHVtZUNoYW5nZSchPT1jO3JldHVybidpbXByZXNzaW9uJz09PWN8fCdsb2Fk
ZWQnPT09YyYmYS5hLmE/Yi5vcmlnaW49PT1IKCkubDpkP2Iub3JpZ2luPT09SCgpLmk6ITB9ZnVu
Y3Rpb24gWmEoYSxiLGMpeydtZWRpYSc9PT1ifHwndmlkZW8nPT09Yj8kYShhLGMpOihhLmMucHVz
aCh7dHlwZTpiLEY6Y30pLFlhKGEsYixjKSl9ZnVuY3Rpb24gJGEoYSxiKXtPYmplY3Qua2V5cyh6
KS5mb3JFYWNoKGZ1bmN0aW9uKGMpe2M9eltjXTthLmMucHVzaCh7dHlwZTpjLEY6Yn0pO1lhKGEs
YyxiKX0pfWZ1bmN0aW9uIGFiKGEsYixjLGQpe3ZhciBlPXtPOmMsTDpkLEY6Yn07YS5nLnB1c2go
ZSk7YS5iLmZvckVhY2goZnVuY3Rpb24oZil7dmFyIGw9YmIoZik7J3Nlc3Npb25TdGFydCc9PT1m
LmV2ZW50LnR5cGUmJmNiKGEsbCxlKTthLmgoYixsKX0pfQpmdW5jdGlvbiBkYihhLGIsYyl7dmFy
IGQ9TChhLCdzZXNzaW9uRXJyb3InLCduYXRpdmUnLHtlcnJvclR5cGU6YixtZXNzYWdlOmN9KTth
LmIucHVzaChkKTthLmcuZm9yRWFjaChmdW5jdGlvbihlKXthLmgoZS5GLGQuZXZlbnQpfSl9ZnVu
Y3Rpb24gZWIoYSxiKXthLmk9T2JqZWN0LmFzc2lnbihhLmksYik7Yj1hLmEuYztpZih2b2lkIDAh
PT1iKXtiPU9iamVjdC5hc3NpZ24oe30sZmIoYSxnYihhLHtjb250ZXh0OmJ9KSwhMCkse3N1cHBv
cnRzTG9hZGVkRXZlbnQ6ISFhLmEuYXx8J3ZpZGVvJz09YS5hLmJ9KTtPYmplY3QuYXNzaWduKGIs
e3BhZ2VVcmw6bnVsbCxjb250ZW50VXJsOmEuYS5vfSk7dmFyIGM9TChhLCdzZXNzaW9uU3RhcnQn
LCduYXRpdmUnLGIpO2EuYi5wdXNoKGMpO2EuZy5mb3JFYWNoKGZ1bmN0aW9uKGQpe3ZhciBlPWQu
RixmPWJiKGMpO2NiKGEsZixkKTthLmgoZSxmKX0sYSk7aGIoYSl9fQpmdW5jdGlvbiBjYihhLGIs
Yyl7Yy5PJiYoYi5kYXRhLnZlcmlmaWNhdGlvblBhcmFtZXRlcnM9YS5pW2MuT10pO2MuTCYmKGM9
YS5hLnYuZ2V0KGMuTCkpJiYoYi5kYXRhLnZlcmlmaWNhdGlvblBhcmFtZXRlcnM9Yy52ZXJpZmlj
YXRpb25QYXJhbWV0ZXJzLGIuZGF0YS5jb250ZXh0LmFjY2Vzc01vZGU9Yy5hY2Nlc3NNb2RlLCdm
dWxsJz09PWMuYWNjZXNzTW9kZSYmKGEuYS5nJiYoYi5kYXRhLmNvbnRleHQudmlkZW9FbGVtZW50
PWEuYS5nKSxhLmEuZiYmKGIuZGF0YS5jb250ZXh0LnNsb3RFbGVtZW50PWEuYS5mKSkpfWZ1bmN0
aW9uIGliKGEpe3ZhciBiPWEuZyxjPUwoYSwnc2Vzc2lvbkZpbmlzaCcsJ25hdGl2ZScpO2EuYi5w
dXNoKGMpO3ZhciBkPWEuYS5jO2QmJiduYXRpdmUnPT1kLmFkU2Vzc2lvblR5cGV8fFVhKGEpO2Iu
Zm9yRWFjaChmdW5jdGlvbihlKXtyZXR1cm4gYS5oKGUuRixjLmV2ZW50KX0pfQpUYS5wcm90b3R5
cGUuaD1mdW5jdGlvbihhLGIpe2Zvcih2YXIgYz1bXSxkPTE7ZDxhcmd1bWVudHMubGVuZ3RoOysr
ZCljW2QtMV09YXJndW1lbnRzW2RdO3RyeXthLmFwcGx5KG51bGwscShjKSl9Y2F0Y2goZSl7U2Eo
ZSl9fTtmdW5jdGlvbiBqYihhLGIpe3ZhciBjPShjPUgoKS5EKT9EYShjKTp2b2lkIDA7Yz1mYihh
LGdiKGEsYykpO1hhKGEsTChhLCdpbXByZXNzaW9uJyxiLGMpKX1mdW5jdGlvbiBrYihhLGIsYyl7
aWYoYS5hLmF8fCdkaXNwbGF5JyE9YS5hLmIpYj1MKGEsJ2xvYWRlZCcsYixmYihhLGdiKGEsdm9p
ZCAwPT09Yz9udWxsOmMpKSksWGEoYSxiKX0KZnVuY3Rpb24gbGIoYSxiLGMsZCl7J3N0YXJ0JyE9
PWImJid2b2x1bWVDaGFuZ2UnIT09Ynx8bnVsbCE9KGQmJmQuZGV2aWNlVm9sdW1lKXx8KGQuZGV2
aWNlVm9sdW1lPWEuYS51KTtpZihkJiYoJ3N0YXJ0Jz09PWJ8fCd2b2x1bWVDaGFuZ2UnPT09Yikp
e3ZhciBlPWQudmlkZW9QbGF5ZXJWb2x1bWUsZj1kLm1lZGlhUGxheWVyVm9sdW1lO251bGwhPWU/
KE9iamVjdC5hc3NpZ24oZCx7bWVkaWFQbGF5ZXJWb2x1bWU6ZX0pLGEuYS5CPWUpOm51bGwhPWYm
JihPYmplY3QuYXNzaWduKGQse3ZpZGVvUGxheWVyVm9sdW1lOmZ9KSxhLmEuQj1mKX1YYShhLEwo
YSxiLGMsZCkpfQpmdW5jdGlvbiBoYihhKXt2YXIgYj1hLmYuZmlsdGVyKGZ1bmN0aW9uKGYpe3Jl
dHVybiBPYmplY3QudmFsdWVzKHopLmluY2x1ZGVzKGYuZXZlbnQudHlwZSkmJid2aWRlbyc9PWEu
YS5iJiZmLm9yaWdpbj09PWEuYS5pfHwnbG9hZGVkJz09Zi5ldmVudC50eXBlJiYnZGlzcGxheSc9
PWEuYS5iJiZmLm9yaWdpbj09PWEuYS5sPyEwOiExfSkubWFwKGZ1bmN0aW9uKGYpe3JldHVybiBm
LmV2ZW50fSksYz1hLmEuYWRTZXNzaW9uSWR8fCcnLGQ9e307Yj1wKGIpO2Zvcih2YXIgZT1iLm5l
eHQoKTshZS5kb25lO2Q9e3c6ZC53fSxlPWIubmV4dCgpKXtkLnc9ZS52YWx1ZTtkLncuYWRTZXNz
aW9uSWR8fChkLncuYWRTZXNzaW9uSWQ9Yyk7aWYoJ2xvYWRlZCc9PWQudy50eXBlKXtpZighYS5h
LmEmJidkaXNwbGF5Jz09YS5hLmIpY29udGludWU7ZC53LmRhdGE9ZmIoYSxnYihhLGQudy5kYXRh
KSl9YS5jLmZpbHRlcihmdW5jdGlvbihmKXtyZXR1cm4gZnVuY3Rpb24obCl7cmV0dXJuIGwudHlw
ZT09PQpmLncudHlwZX19KGQpKS5mb3JFYWNoKGZ1bmN0aW9uKGYpe3JldHVybiBmdW5jdGlvbihs
KXtyZXR1cm4gbC5GKGYudyl9fShkKSl9fWZ1bmN0aW9uIG1iKGEsYixjKXthOntjPW5ldyBTZXQo
Yyk7YT1wKGEuZi5jb25jYXQoYS5iKSk7Zm9yKHZhciBkPWEubmV4dCgpOyFkLmRvbmU7ZD1hLm5l
eHQoKSlpZihkPWQudmFsdWUsYy5oYXMoZC5ldmVudC50eXBlKSYmZC5vcmlnaW4hPWIpe2I9ITA7
YnJlYWsgYX1iPSExfXJldHVybiBiPyhKKCdFdmVudCBvd25lciBjYW5ub3QgYmUgcmVnaXN0ZXJl
ZCBhZnRlciBpdHMgZXZlbnRzIGhhdmUgYWxyZWFkeSBiZWVuIHB1Ymxpc2hlZC4nKSwhMSk6ITB9
ZnVuY3Rpb24gbmIoYSxiKXttYihhLGIsT2JqZWN0LnZhbHVlcyh6KSkmJk0oYSxiKSYmKGEuYS5p
PWIpfWZ1bmN0aW9uIG9iKGEsYil7bWIoYSxiLFsnaW1wcmVzc2lvbiddKSYmcGIoYSxiKSYmKGEu
YS5sPWIpfQpmdW5jdGlvbiBwYihhLGIpe3ZhciBjPWEuYS5sO3JldHVybidub25lJyE9YyYmYyE9
Yj8oSignSW1wcmVzc2lvbiBldmVudCBpcyBvd25lZCBieSAnKyhhLmEubCsnLCBub3QgJykrKGIr
Jy4nKSksITEpOiEwfWZ1bmN0aW9uIE0oYSxiKXt2YXIgYz1hLmEuaTtyZXR1cm4nbm9uZSchPWMm
JmMhPWI/KEooJ01lZGlhIGV2ZW50cyBhcmUgb3duZWQgYnkgJysoYS5hLmkrJywgbm90ICcrYisn
LicpKSwhMSk6ITB9ZnVuY3Rpb24gZmIoYSxiLGMpe2M9dm9pZCAwPT09Yz8hMTpjO2I9T2JqZWN0
LmFzc2lnbih7fSxiKTthLmEuYiYmT2JqZWN0LmFzc2lnbihiLHttZWRpYVR5cGU6YS5hLmJ9KTth
LmEuYSYmKGN8fCdkZWZpbmVkQnlKYXZhU2NyaXB0JyE9PWEuYS5hKSYmT2JqZWN0LmFzc2lnbihi
LHtjcmVhdGl2ZVR5cGU6YS5hLmF9KTtyZXR1cm4gYn1mdW5jdGlvbiBnYihhLGIpe3JldHVybiBh
LmEuaD9PYmplY3QuYXNzaWduKHt9LGIse2ltcHJlc3Npb25UeXBlOmEuYS5ofSk6Yn0KZnVuY3Rp
b24gTChhLGIsYyxkKXtyZXR1cm4gbmV3IFFhKHthZFNlc3Npb25JZDphLmEuYWRTZXNzaW9uSWR8
fCcnLHRpbWVzdGFtcDoobmV3IERhdGUpLmdldFRpbWUoKSx0eXBlOmIsZGF0YTpkfSxjKX1mdW5j
dGlvbiBiYihhKXthPWEuZXZlbnQ7cmV0dXJue2FkU2Vzc2lvbklkOmEuYWRTZXNzaW9uSWQsdGlt
ZXN0YW1wOmEudGltZXN0YW1wLHR5cGU6YS50eXBlLGRhdGE6YS5kYXRhfX07ZnVuY3Rpb24gcWIo
YSxiLGMpeydjb250YWluZXInPT09YiYmdm9pZCAwIT09YS5hLkcmJnZvaWQgMCE9PWEuYSYmbnVs
bCE9YS5hLmFkU2Vzc2lvbklkJiYoYS5hLkg9S2EoYS5jLGEuYS5HLGEuYS5tLGEuYS5hZFNlc3Np
b25JZCwhMCkpO2I9YS5hO3ZhciBkPWIuSCxlPWIuSTtpZihkKWlmKGUpe2I9bmV3IENhO3ZhciBm
PWQuaixsPWQuYSxnPWQuYixoPWUuYTtlPWUuYjtmJiZsJiZnJiZoJiZlJiYoRWEoYixmKSxiLmw9
bmV3IEEobCwhMSksYi52PW5ldyBBKGcsITEpLGIuaT1PYmplY3QuYXNzaWduKFtdLGQuaSksYi5j
PU9iamVjdC5hc3NpZ24oW10sZC5jKSxiLmg9T2JqZWN0LmFzc2lnbihbXSxkLmgpLGIubz1PYmpl
Y3QuYXNzaWduKFtdLGQubyksYi5mPU9iamVjdC5hc3NpZ24oW10sZC5mKSxkPWIubC54LGY9Yi5s
LnksaD1uZXcgQShoLCExKSxlPW5ldyBBKGUsITEpLHhhKGgsZCxmKSx4YShlLGQsZiksYi5hPWgs
Yi5iPUdhKGUsZyksSWEoYikpfWVsc2UgYj1kO2Vsc2UgYj0KbnVsbDtnPWEuYS5EO2lmKGImJiFi
LkooZyl8fGMpZz1EYShiKSxjJiYoZy5hZFZpZXcucmVhc29ucz1nLmFkVmlldy5yZWFzb25zfHxb
Y10pLGM9YS5iLCdhdWRpbychPWMuYS5hJiZYYShjLEwoYywnZ2VvbWV0cnlDaGFuZ2UnLCduYXRp
dmUnLGcpKSxhLmEuRD1ifTtmdW5jdGlvbiBOKGEpe3JldHVybidvYmplY3QnPT09dHlwZW9mIGF9
ZnVuY3Rpb24gcmIoYSl7cmV0dXJuJ251bWJlcic9PT10eXBlb2YgYSYmIWlzTmFOKGEpJiYwPD1h
fWZ1bmN0aW9uIE8oYSl7cmV0dXJuJ3N0cmluZyc9PT10eXBlb2YgYX1mdW5jdGlvbiBQKGEsYil7
cmV0dXJuIE8oYSkmJi0xIT09T2JqZWN0LnZhbHVlcyhiKS5pbmRleE9mKGEpfWZ1bmN0aW9uIHNi
KGEpe3ZhciBiPWEmJmEudGFnTmFtZSYmJ2lmcmFtZSc9PT1hLnRhZ05hbWUudG9Mb3dlckNhc2Uo
KTt0cnl7Yj1iJiZhIGluc3RhbmNlb2YgSFRNTElGcmFtZUVsZW1lbnR9Y2F0Y2goYyl7fXJldHVy
biBifWZ1bmN0aW9uIHRiKGEpe3ZhciBiPWEmJmEudGFnTmFtZTt0cnl7Yj1iJiZhIGluc3RhbmNl
b2YgYS5vd25lckRvY3VtZW50LmRlZmF1bHRWaWV3LkhUTUxFbGVtZW50fWNhdGNoKGMpe31yZXR1
cm4gYn0KZnVuY3Rpb24gdWIoYSl7dmFyIGI9YSYmYS50YWdOYW1lJiYndmlkZW8nPT09YS50YWdO
YW1lLnRvTG93ZXJDYXNlKCk7dHJ5e2I9YiYmYSBpbnN0YW5jZW9mIGEub3duZXJEb2N1bWVudC5k
ZWZhdWx0Vmlldy5IVE1MVmlkZW9FbGVtZW50fWNhdGNoKGMpe31yZXR1cm4gYn07ZnVuY3Rpb24g
UShhLGIsYyl7dGhpcy5mPWE7dGhpcy5LPWI7dGhpcy5HPWM7dGhpcy5jPUgoKTt0aGlzLmI9bnVs
bDt0aGlzLmE9dGhpcy5nPXRoaXMudT12b2lkIDA7dGhpcy5JPSEwO3RoaXMuQj12b2lkIDA7Uih0
aGlzKX1mdW5jdGlvbiBSKGEpe2lmKCFhLmIpe3ZhciBiO2E6e2lmKChiPWEuZi5kb2N1bWVudCkm
JmIuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSYmKGI9Yi5nZXRFbGVtZW50c0J5Q2xhc3NOYW1lKCdv
bWlkLWVsZW1lbnQnKSkpe2lmKDE9PWIubGVuZ3RoKXtiPWJbMF07YnJlYWsgYX0xPGIubGVuZ3Ro
JiZhLkkmJihkYihhLkcsJ2dlbmVyaWMnLCJNb3JlIHRoYW4gb25lIGVsZW1lbnQgd2l0aCAnb21p
ZC1lbGVtZW50JyBjbGFzcyBuYW1lLiIpLGEuST0hMSl9Yj1udWxsfWlmKHViKGIpKWEuYy5nPWI7
ZWxzZSBpZih0YihiKSlhLmMuZj1iO2Vsc2UgcmV0dXJuO3ZiKGEpfX0KZnVuY3Rpb24gdmIoYSl7
YS5jLmc/KGEuYj1hLmMuZyxhLmkoKSk6YS5jLmYmJihhLmI9YS5jLmYsc2IoYS5iKT9hLmMuaiYm
YS5pKCk6YS5pKCkpfWZ1bmN0aW9uIHdiKGEpe2EuYSYmKHNiKGEuYik/YS5jLmomJihhLkQoKSx4
YihhKSk6KGEuRCgpLHhiKGEpKSl9US5wcm90b3R5cGUubT1mdW5jdGlvbigpe3RoaXMuQiYmKHRo
aXMuZi5kb2N1bWVudC5yZW1vdmVFdmVudExpc3RlbmVyKCd2aXNpYmlsaXR5Y2hhbmdlJyx0aGlz
LkIpLHRoaXMuQj12b2lkIDApfTtRLnByb3RvdHlwZS5pPWZ1bmN0aW9uKCl7fTtmdW5jdGlvbiB4
YihhKXthLnUmJihhLmMuST1hLnUscWIoYS5LLCdjcmVhdGl2ZScpKX1mdW5jdGlvbiB5YihhKXtp
ZihhLmEmJmEuYy5qKXt2YXIgYj1uZXcgQShhLmMuaiwhMSk7eGEoYixhLmEueCxhLmEueSk7Yi5j
bGlwc1RvQm91bmRzPSEwO3JldHVybiBifX07ZnVuY3Rpb24gemIoYSxiLGMpe3JldHVybiBBYihh
LCdzZXRJbnRlcnZhbCcpKGIsYyl9ZnVuY3Rpb24gQmIoYSxiKXtBYihhLCdjbGVhckludGVydmFs
JykoYil9ZnVuY3Rpb24gQ2IoYSxiKXtBYihhLCdjbGVhclRpbWVvdXQnKShiKX1mdW5jdGlvbiBB
YihhLGIpe3JldHVybiBhLmEmJmEuYVtiXT9hLmFbYl06RGIoYSxiKX0KZnVuY3Rpb24gRWIoYSxi
LGMsZCl7aWYoYS5hLmRvY3VtZW50JiZhLmEuZG9jdW1lbnQuYm9keSl7dmFyIGU9YS5hLmRvY3Vt
ZW50LmNyZWF0ZUVsZW1lbnQoJ2ltZycpO2Uud2lkdGg9MTtlLmhlaWdodD0xO2Uuc3R5bGUuZGlz
cGxheT0nbm9uZSc7ZS5zcmM9YjtjJiZlLmFkZEV2ZW50TGlzdGVuZXIoJ2xvYWQnLGZ1bmN0aW9u
KCl7cmV0dXJuIGMoKX0pO2QmJmUuYWRkRXZlbnRMaXN0ZW5lcignZXJyb3InLGZ1bmN0aW9uKCl7
cmV0dXJuIGQoKX0pO2EuYS5kb2N1bWVudC5ib2R5LmFwcGVuZENoaWxkKGUpfWVsc2UgRGIoYSwn
c2VuZFVybCcpKGIsYyxkKX1mdW5jdGlvbiBEYihhLGIpe2lmKGEuYSYmYS5hLm9taWROYXRpdmUm
JmEuYS5vbWlkTmF0aXZlW2JdKXJldHVybiBhLmEub21pZE5hdGl2ZVtiXS5iaW5kKGEuYS5vbWlk
TmF0aXZlKTt0aHJvdyBFcnJvcignTmF0aXZlIGludGVyZmFjZSBtZXRob2QgIicrYisnIiBub3Qg
Zm91bmQuJyk7fTtmdW5jdGlvbiBTKGEsYixjLGQsZSl7US5jYWxsKHRoaXMsYSxjLGUpO3RoaXMu
bD1iO3RoaXMuaD12b2lkIDA7dGhpcy5qPWR9cihTLFEpO1MucHJvdG90eXBlLm09ZnVuY3Rpb24o
KXt2b2lkIDAhPT10aGlzLmgmJihCYih0aGlzLmosdGhpcy5oKSx0aGlzLmg9dm9pZCAwKTtRLnBy
b3RvdHlwZS5tLmNhbGwodGhpcyl9O1MucHJvdG90eXBlLmk9ZnVuY3Rpb24oKXt2YXIgYT10aGlz
O1EucHJvdG90eXBlLmkuY2FsbCh0aGlzKTtudWxsPT10aGlzLmI/dGhpcy5oPXZvaWQgMDp2b2lk
IDA9PT10aGlzLmgmJih0aGlzLmg9emIodGhpcy5qLGZ1bmN0aW9uKCl7cmV0dXJuIEZiKGEpfSwy
MDApLEZiKHRoaXMpKX07ClMucHJvdG90eXBlLkQ9ZnVuY3Rpb24oKXtpZih0aGlzLmcpe3ZhciBh
PXliKHRoaXMpO2lmKGEpe3RoaXMuYS5pc0NyZWF0aXZlPSExO2EuaXNDcmVhdGl2ZT0hMDtmb3Io
dmFyIGI9ITEsYz0wO2M8dGhpcy5hLmNoaWxkVmlld3MubGVuZ3RoO2MrKylpZih0aGlzLmEuY2hp
bGRWaWV3c1tjXS5pc0NyZWF0aXZlKXt0aGlzLmEuY2hpbGRWaWV3c1tjXT1hO2I9ITA7YnJlYWt9
Ynx8dGhpcy5hLmNoaWxkVmlld3MucHVzaChhKX1lbHNlIHRoaXMuYS5pc0NyZWF0aXZlPSEwO3Ro
aXMudT1LYSh0aGlzLmwsdGhpcy5nLHRoaXMuYy5tLHRoaXMuYy5hZFNlc3Npb25JZCx0aGlzLkMo
KSl9fTtTLnByb3RvdHlwZS5DPWZ1bmN0aW9uKCl7cmV0dXJuITB9OwpmdW5jdGlvbiBGYihhKXtp
Zih2b2lkIDAhPT1hLmgpe2I6e3RyeXt2YXIgYj1hLmYudG9wO3ZhciBjPTA8PWIuaW5uZXJIZWln
aHQmJjA8PWIuaW5uZXJXaWR0aDticmVhayBifWNhdGNoKGQpe31jPSExfWM/KGM9YS5mLnRvcCxj
PW5ldyBBKG5ldyBNYShjLmlubmVyV2lkdGgsYy5pbm5lckhlaWdodCksITEpKTpjPW5ldyBBKG5l
dyBNYSgwLDApLCExKTtiPWEuYi5nZXRCb3VuZGluZ0NsaWVudFJlY3QoKTtpZihudWxsPT1iLnh8
fGlzTmFOKGIueCkpYi54PWIubGVmdDtpZihudWxsPT1iLnl8fGlzTmFOKGIueSkpYi55PWIudG9w
O2I9bmV3IEEoYiwhMSk7Yy5KKGEuZykmJmIuSihhLmEpfHwoYS5hPWIsYS5hLmNsaXBzVG9Cb3Vu
ZHM9ITAsYS5nPWMsYS5nLmNoaWxkVmlld3MucHVzaChhLmEpLHdiKGEpKX19O2Z1bmN0aW9uIFQo
YSxiLGMsZCl7US5jYWxsKHRoaXMsYSxjLGQpO3RoaXMubz10aGlzLmo9dGhpcy5sPXRoaXMuaD12
b2lkIDA7dGhpcy5IPSExO3RoaXMudj12b2lkIDB9cihULFEpO1QucHJvdG90eXBlLm09ZnVuY3Rp
b24oKXt0aGlzLmgmJnRoaXMuaC5kaXNjb25uZWN0KCk7R2IodGhpcyk7US5wcm90b3R5cGUubS5j
YWxsKHRoaXMpfTtULnByb3RvdHlwZS5pPWZ1bmN0aW9uKCl7US5wcm90b3R5cGUuaS5jYWxsKHRo
aXMpO3RoaXMuYiYmKHRoaXMuaHx8KHRoaXMuaD1IYih0aGlzKSksSWIodGhpcyksSmIodGhpcy5i
KSYmS2IodGhpcykpfTsKVC5wcm90b3R5cGUuRD1mdW5jdGlvbigpe2lmKHRoaXMuYSYmdGhpcy52
KXt2YXIgYT15Yih0aGlzKTtpZihhKXt2YXIgYj1hO3ZhciBjPXRoaXMudjt2YXIgZD1NYXRoLm1h
eChhLngsYy54KTt2YXIgZT1NYXRoLm1heChhLnksYy55KSxmPU1hdGgubWluKGEuZW5kWCxjLmVu
ZFgpO2E9TWF0aC5taW4oYS5lbmRZLGMuZW5kWSk7Zjw9ZHx8YTw9ZT9kPW51bGw6KGM9e30sZD1u
ZXcgQSgoYy54PWQsYy55PWUsYy53aWR0aD1NYXRoLmFicyhmLWQpLGMuaGVpZ2h0PU1hdGguYWJz
KGEtZSksYyksITEpKTtkfHwoZD1uZXcgQSh7eDowLHk6MCx3aWR0aDowLGhlaWdodDowfSwhMSkp
fWVsc2UgYj10aGlzLmEsZD10aGlzLnY7ZT1uZXcgQ2E7dGhpcy5nJiZFYShlLHRoaXMuZyk7ZS5h
PWI7ZS5iPWQ7SWEoZSk7dGhpcy5IPzEwMD09PWUubXx8RChlLCdjbGlwcGVkJyk6RChlLCd2aWV3
cG9ydCcpO3RoaXMudT1lfX07VC5wcm90b3R5cGUuQz1mdW5jdGlvbigpe3JldHVybiEwfTsKZnVu
Y3Rpb24gR2IoYSl7YS5sJiYoYS5sLmRpc2Nvbm5lY3QoKSxhLmw9dm9pZCAwKTthLmomJihhLmou
ZGlzY29ubmVjdCgpLGEuaj12b2lkIDApO2EubyYmKCgwLGEuZi5yZW1vdmVFdmVudExpc3RlbmVy
KSgncmVzaXplJyxhLm8pLGEubz12b2lkIDApfWZ1bmN0aW9uIEliKGEpe2EuaCYmYS5iJiYoYS5o
LnVub2JzZXJ2ZShhLmIpLGEuaC5vYnNlcnZlKGEuYikpfWZ1bmN0aW9uIEpiKGEpe2E9YS5nZXRC
b3VuZGluZ0NsaWVudFJlY3QoKTtyZXR1cm4gMD09YS53aWR0aHx8MD09YS5oZWlnaHR9CmZ1bmN0
aW9uIEhiKGEpe3JldHVybiBuZXcgYS5mLkludGVyc2VjdGlvbk9ic2VydmVyKGZ1bmN0aW9uKGIp
e3RyeXtpZihiLmxlbmd0aCl7Zm9yKHZhciBjLGQ9YlswXSxlPTE7ZTxiLmxlbmd0aDtlKyspYltl
XS50aW1lPmQudGltZSYmKGQ9YltlXSk7Yz1kO2EuZz1MYihjLnJvb3RCb3VuZHMpO2EuYT1MYihj
LmJvdW5kaW5nQ2xpZW50UmVjdCk7YS52PUxiKGMuaW50ZXJzZWN0aW9uUmVjdCk7YS5IPSEhYy5p
c0ludGVyc2VjdGluZzt3YihhKX19Y2F0Y2goZil7YS5tKCksZGIoYS5HLCdnZW5lcmljJywnUHJv
YmxlbSBoYW5kbGluZyBJbnRlcnNlY3Rpb25PYnNlcnZlciBjYWxsYmFjazogJytmLm1lc3NhZ2Up
fX0se3Jvb3Q6bnVsbCxyb290TWFyZ2luOicwcHgnLHRocmVzaG9sZDpbMCwuMSwuMiwuMywuNCwu
NSwuNiwuNywuOCwuOSwxXX0pfQpmdW5jdGlvbiBLYihhKXthLmYuUmVzaXplT2JzZXJ2ZXI/YS5s
fHwoYS5sPU1iKGEsZnVuY3Rpb24oKXtyZXR1cm4gT2IoYSl9KSxhLmwub2JzZXJ2ZShhLmIpKToo
YS5vfHwoYS5vPWZ1bmN0aW9uKCl7cmV0dXJuIE9iKGEpfSwoMCxhLmYuYWRkRXZlbnRMaXN0ZW5l
cikoJ3Jlc2l6ZScsYS5vKSksYS5qfHwoYS5qPW5ldyBNdXRhdGlvbk9ic2VydmVyKGZ1bmN0aW9u
KCl7cmV0dXJuIE9iKGEpfSksYS5qLm9ic2VydmUoYS5iLHtjaGlsZExpc3Q6ITEsYXR0cmlidXRl
czohMCxzdWJ0cmVlOiExfSkpKX1mdW5jdGlvbiBPYihhKXthLmImJiFKYihhLmIpJiYoSWIoYSks
R2IoYSkpfWZ1bmN0aW9uIE1iKGEsYil7cmV0dXJuIG5ldyBhLmYuUmVzaXplT2JzZXJ2ZXIoYil9
ZnVuY3Rpb24gTGIoYSl7aWYoYSYmbnVsbCE9PWEueCYmbnVsbCE9PWEueSYmbnVsbCE9PWEud2lk
dGgmJm51bGwhPT1hLmhlaWdodClyZXR1cm4gbmV3IEEoYSwhMSl9O2Z1bmN0aW9uIFBiKGEpe3Jl
dHVybiBhJiZOKGEpP09iamVjdC5lbnRyaWVzKGEpLnJlZHVjZShmdW5jdGlvbihiLGMpe3ZhciBk
PXAoYyk7Yz1kLm5leHQoKS52YWx1ZTtkPWQubmV4dCgpLnZhbHVlO3JldHVybiBiJiZPKGMpJiZu
dWxsIT1kJiZOKGQpJiZPKGQucmVzb3VyY2VVcmwpfSwhMCk6ITF9O2Z1bmN0aW9uIFUoYSxiLGMs
ZCl7dGhpcy5iPWE7dGhpcy5tZXRob2Q9Yjt0aGlzLnZlcnNpb249Yzt0aGlzLmE9ZH1mdW5jdGlv
biBRYihhKXtyZXR1cm4hIWEmJnZvaWQgMCE9PWEub21pZF9tZXNzYWdlX2d1aWQmJnZvaWQgMCE9
PWEub21pZF9tZXNzYWdlX21ldGhvZCYmdm9pZCAwIT09YS5vbWlkX21lc3NhZ2VfdmVyc2lvbiYm
J3N0cmluZyc9PT10eXBlb2YgYS5vbWlkX21lc3NhZ2VfZ3VpZCYmJ3N0cmluZyc9PT10eXBlb2Yg
YS5vbWlkX21lc3NhZ2VfbWV0aG9kJiYnc3RyaW5nJz09PXR5cGVvZiBhLm9taWRfbWVzc2FnZV92
ZXJzaW9uJiYodm9pZCAwPT09YS5vbWlkX21lc3NhZ2VfYXJnc3x8dm9pZCAwIT09YS5vbWlkX21l
c3NhZ2VfYXJncyl9ZnVuY3Rpb24gUmIoYSl7cmV0dXJuIG5ldyBVKGEub21pZF9tZXNzYWdlX2d1
aWQsYS5vbWlkX21lc3NhZ2VfbWV0aG9kLGEub21pZF9tZXNzYWdlX3ZlcnNpb24sYS5vbWlkX21l
c3NhZ2VfYXJncyl9CmZ1bmN0aW9uIFNiKGEpe3ZhciBiPXt9O2I9KGIub21pZF9tZXNzYWdlX2d1
aWQ9YS5iLGIub21pZF9tZXNzYWdlX21ldGhvZD1hLm1ldGhvZCxiLm9taWRfbWVzc2FnZV92ZXJz
aW9uPWEudmVyc2lvbixiKTt2b2lkIDAhPT1hLmEmJihiLm9taWRfbWVzc2FnZV9hcmdzPWEuYSk7
cmV0dXJuIGJ9O2Z1bmN0aW9uIFRiKGEpe3RoaXMuYz1hfTtmdW5jdGlvbiBWKGEpe3RoaXMuYz1h
O3RoaXMuaGFuZGxlRXhwb3J0ZWRNZXNzYWdlPVYucHJvdG90eXBlLmYuYmluZCh0aGlzKX1yKFYs
VGIpO1YucHJvdG90eXBlLmI9ZnVuY3Rpb24oYSxiKXtiPXZvaWQgMD09PWI/dGhpcy5jOmI7aWYo
IWIpdGhyb3cgRXJyb3IoJ01lc3NhZ2UgZGVzdGluYXRpb24gbXVzdCBiZSBkZWZpbmVkIGF0IGNv
bnN0cnVjdGlvbiB0aW1lIG9yIHdoZW4gc2VuZGluZyB0aGUgbWVzc2FnZS4nKTtiLmhhbmRsZUV4
cG9ydGVkTWVzc2FnZShTYihhKSx0aGlzKX07Vi5wcm90b3R5cGUuZj1mdW5jdGlvbihhLGIpe1Fi
KGEpJiZ0aGlzLmEmJnRoaXMuYShSYihhKSxiKX07ZnVuY3Rpb24gVWIoYSxiKXt0aGlzLmM9Yj12
b2lkIDA9PT1iP0k6Yjt2YXIgYz10aGlzO2EuYWRkRXZlbnRMaXN0ZW5lcignbWVzc2FnZScsZnVu
Y3Rpb24oZCl7aWYoJ29iamVjdCc9PT10eXBlb2YgZC5kYXRhKXt2YXIgZT1kLmRhdGE7UWIoZSkm
JmQuc291cmNlJiZjLmEmJmMuYShSYihlKSxkLnNvdXJjZSl9fSl9cihVYixUYik7VWIucHJvdG90
eXBlLmI9ZnVuY3Rpb24oYSxiKXtiPXZvaWQgMD09PWI/dGhpcy5jOmI7aWYoIWIpdGhyb3cgRXJy
b3IoJ01lc3NhZ2UgZGVzdGluYXRpb24gbXVzdCBiZSBkZWZpbmVkIGF0IGNvbnN0cnVjdGlvbiB0
aW1lIG9yIHdoZW4gc2VuZGluZyB0aGUgbWVzc2FnZS4nKTtiLnBvc3RNZXNzYWdlKFNiKGEpLCcq
Jyl9O2Z1bmN0aW9uIFZiKCl7cmV0dXJuJ3h4eHh4eHh4LXh4eHgtNHh4eC15eHh4LXh4eHh4eHh4
eHh4eCcucmVwbGFjZSgvW3h5XS9nLGZ1bmN0aW9uKGEpe3ZhciBiPTE2Kk1hdGgucmFuZG9tKCl8
MDtyZXR1cm4neSc9PT1hPyhiJjN8OCkudG9TdHJpbmcoMTYpOmIudG9TdHJpbmcoMTYpfSl9O2Z1
bmN0aW9uIFdiKGEpe2lmKCFhLmF8fCFhLmEuZG9jdW1lbnQpdGhyb3cgRXJyb3IoJ09NSUQgU2Vy
dmljZSBTY3JpcHQgaXMgbm90IHJ1bm5pbmcgd2l0aGluIGEgd2luZG93LicpO3ZhciBiPWEuYjth
LmI9W107Yi5mb3JFYWNoKGZ1bmN0aW9uKGMpe3RyeXt2YXIgZD1hLmMuQz8nbGltaXRlZCc6J2Z1
bGwnLGU9UChjLmFjY2Vzc01vZGUsc2EpP2MuYWNjZXNzTW9kZTpudWxsO3ZhciBmPWU/J2Z1bGwn
PT1lJiYnbGltaXRlZCc9PWQ/ZDonZG9tYWluJz09ZT8nbGltaXRlZCc6ZTpkO2MuYWNjZXNzTW9k
ZT1mO2E6e3ZhciBsPWMucmVzb3VyY2VVcmwsZz1hLmEubG9jYXRpb24ub3JpZ2luO3RyeXt2YXIg
aD1uZXcgVVJMKGwsZyk7YnJlYWsgYX1jYXRjaChGKXt9dHJ5e2g9bmV3IFVSTChsKTticmVhayBh
fWNhdGNoKEYpe31oPW51bGx9aWYoZD1oKXt2YXIgaz1WYigpLG09YS5hLmRvY3VtZW50LHY9bS5j
cmVhdGVFbGVtZW50KCdpZnJhbWUnKTt2LmlkPSdvbWlkLXZlcmlmaWNhdGlvbi1zY3JpcHQtZnJh
bWUtJysKazt2LnN0eWxlLmRpc3BsYXk9J25vbmUnO1snZnVsbCcsJ2xpbWl0ZWQnXS5pbmNsdWRl
cyhmKT92LnNyY2RvYz0iPGh0bWw+PGhlYWQ+XG48c2NyaXB0IHR5cGU9XCJ0ZXh0L2phdmFzY3Jp
cHRcIj53aW5kb3dbJ29taWRWZXJpZmljYXRpb25Qcm9wZXJ0aWVzJ10gPSB7XG4nc2VydmljZVdp
bmRvdyc6IHdpbmRvdy5wYXJlbnQsXG4naW5qZWN0aW9uU291cmNlJzogJ2FwcCcsXG4naW5qZWN0
aW9uSWQnOiAnIisoaysnXCcsXG59O1x4M2Mvc2NyaXB0PlxuPHNjcmlwdCB0eXBlPSJ0ZXh0L2ph
dmFzY3JpcHQiIHNyYz0iJykrZC5ocmVmKyciPlx4M2Mvc2NyaXB0PlxuPC9oZWFkPjxib2R5Pjwv
Ym9keT48L2h0bWw+JzonZG9tYWluJz09ZiYmKHYuc3JjPVhiKGEsayxkKS5ocmVmKTtbJ2RvbWFp
bicsJ2xpbWl0ZWQnXS5pbmNsdWRlcyhmKSYmKHYuc2FuZGJveD0nYWxsb3ctc2NyaXB0cycpO20u
Ym9keS5hcHBlbmRDaGlsZCh2KTt2YXIgQj1jLnZlbmRvcktleSx4PWMudmVyaWZpY2F0aW9uUGFy
YW1ldGVyczsKQj12b2lkIDA9PT1CPycnOkI7eD12b2lkIDA9PT14PycnOng7QiYmJ3N0cmluZyc9
PT10eXBlb2YgQiYmJychPT1CJiZ4JiYnc3RyaW5nJz09PXR5cGVvZiB4JiYnJyE9PXgmJihhLmYu
aVtCXT14KTthLmMudi5zZXQoayxjKX19Y2F0Y2goRil7U2EoJ09NSUQgdmVyaWZpY2F0aW9uIHNj
cmlwdCAnK2MucmVzb3VyY2VVcmwrJyBmYWlsZWQgdG8gbG9hZDogJytGKX19KX0KZnVuY3Rpb24g
WGIoYSxiLGMpe3ZhciBkPScvLndlbGwta25vd24vb21pZC9vbWxvYWRlci12MS5odG1sIyc7KG5l
dyBNYXAoW1sndmVyaWZpY2F0aW9uU2NyaXB0VXJsJyxjLmhyZWZdLFsnaW5qZWN0aW9uSWQnLGJd
XSkpLmZvckVhY2goZnVuY3Rpb24oZSxmKXtkKz1lbmNvZGVVUklDb21wb25lbnQoZikrJz0nK2Vu
Y29kZVVSSUNvbXBvbmVudChlKSsnJid9KTtiPW51bGw7dHJ5e2I9bmV3IFVSTChkLGEuYS5wYXJl
bnQubG9jYXRpb24ub3JpZ2luKX1jYXRjaChlKXt0aHJvdyBFcnJvcignT01JRCBTZXJ2aWNlIFNj
cmlwdCBjYW5ub3QgYWNjZXNzIHRoZSBwYXJlbnQgd2luZG93LicpO31yZXR1cm4gYn07ZnVuY3Rp
b24gWWIoKXt2YXIgYT1aYixiPSRiLGM9dGhpczt0aGlzLmM9Vzt0aGlzLmI9YTt0aGlzLmE9SCgp
O3RoaXMuZz1iO3RoaXMuZj0hMTt0aGlzLnJlZ2lzdGVyU2Vzc2lvbk9ic2VydmVyKGZ1bmN0aW9u
KGQpe3JldHVybiBhYyhjLGQpfSl9bj1ZYi5wcm90b3R5cGU7bi5yZWdpc3RlclNlc3Npb25PYnNl
cnZlcj1mdW5jdGlvbihhKXthYih0aGlzLmMsYSl9O24uc2V0U2xvdEVsZW1lbnQ9ZnVuY3Rpb24o
YSl7dGIoYSk/KHRoaXMuYS5mPWEsdGhpcy5iJiZ2Yih0aGlzLmIpKTpKKCdzZXRTbG90RWxlbWVu
dCBjYWxsZWQgd2l0aCBhIG5vbi1IVE1MRWxlbWVudC4gIEl0IHdpbGwgYmUgaWdub3JlZC4nKX07
bi5zZXRFbGVtZW50Qm91bmRzPWZ1bmN0aW9uKGEpe3RoaXMuYS5qPWE7dGhpcy5iJiZ2Yih0aGlz
LmIpO3RoaXMuYiYmd2IodGhpcy5iKX07bi5lcnJvcj1mdW5jdGlvbihhLGIpe2RiKHRoaXMuYyxh
LGIpfTsKbi5yZWdpc3RlckFkRXZlbnRzPWZ1bmN0aW9uKCl7b2IodGhpcy5jLCdqYXZhc2NyaXB0
Jyl9O24ucmVnaXN0ZXJNZWRpYUV2ZW50cz1mdW5jdGlvbigpe25iKHRoaXMuYywnamF2YXNjcmlw
dCcpfTtmdW5jdGlvbiBZKGEsYixjKXsnaW1wcmVzc2lvbic9PWI/cGIoYS5jLCdqYXZhc2NyaXB0
JykmJihqYihhLmMsJ2phdmFzY3JpcHQnKSxhLmImJlIoYS5iKSk6KCdsb2FkZWQnPT1iPyhjPXZv
aWQgMD09PWM/bnVsbDpjLE0oYS5jLCdqYXZhc2NyaXB0JykmJmtiKGEuYywnamF2YXNjcmlwdCcs
YykpOk0oYS5jLCdqYXZhc2NyaXB0JykmJmxiKGEuYyxiLCdqYXZhc2NyaXB0JyxjKSxbJ2xvYWRl
ZCcsJ3N0YXJ0J10uaW5jbHVkZXMoYikmJmEuYiYmUihhLmIpKX1uLmluamVjdFZlcmlmaWNhdGlv
blNjcmlwdFJlc291cmNlcz1mdW5jdGlvbihhKXt2YXIgYj10aGlzLmc7Yi5iLnB1c2guYXBwbHko
Yi5iLHEoYSkpO2lmKHRoaXMuZil0cnl7V2IodGhpcy5nKX1jYXRjaChjKXtKKGMubWVzc2FnZSl9
fTsKbi5zZXRDcmVhdGl2ZVR5cGU9ZnVuY3Rpb24oYSxiKXtiPXZvaWQgMD09PWI/bnVsbDpiO2lm
KCF0aGlzLmEuYnx8dGhpcy5hLmEpdGhpcy5hLmE9YSwndmlkZW8nPT1hfHwnYXVkaW8nPT1hP3Ro
aXMuYS5iPSd2aWRlbyc6J2h0bWxEaXNwbGF5Jz09YXx8J25hdGl2ZURpc3BsYXknPT1hP3RoaXMu
YS5iPSdkaXNwbGF5JzonZGVmaW5lZEJ5SmF2YVNjcmlwdCc9PWEmJmImJih0aGlzLmEuYj0nbm9u
ZSc9PWI/J2Rpc3BsYXknOid2aWRlbycpfTtuLnNldEltcHJlc3Npb25UeXBlPWZ1bmN0aW9uKGEp
e2lmKCF0aGlzLmEuYnx8dGhpcy5hLmEpdGhpcy5hLmg9YX07CmZ1bmN0aW9uIGFjKGEsYil7aWYo
J3Nlc3Npb25TdGFydCc9PT1iLnR5cGUpe2EuZj0hMDt0cnl7V2IoYS5nKX1jYXRjaChjKXtKKGMu
bWVzc2FnZSl9fSdzZXNzaW9uRmluaXNoJz09PWIudHlwZSYmKGEuZj0hMSwoYj1IKCkuYykmJidu
YXRpdmUnPT1iLmFkU2Vzc2lvblR5cGV8fGEucmVnaXN0ZXJTZXNzaW9uT2JzZXJ2ZXIoZnVuY3Rp
b24oYyl7cmV0dXJuIGFjKGEsYyl9KSl9bi5zZXRDbGllbnRJbmZvPWZ1bmN0aW9uKGEsYixjKXt2
YXIgZD10aGlzLmEuY3x8e307ZC5vbWlkSnNJbmZvPU9iamVjdC5hc3NpZ24oe30sZC5vbWlkSnNJ
bmZvLHtzZXNzaW9uQ2xpZW50VmVyc2lvbjphLHBhcnRuZXJOYW1lOmIscGFydG5lclZlcnNpb246
Y30pO3RoaXMuYS5jPWQ7cmV0dXJuIHRoaXMuYS5jLm9taWRKc0luZm8uc2VydmljZVZlcnNpb259
O2Z1bmN0aW9uIGJjKGEpe3JldHVybi9cZCtcLlxkK1wuXGQrKC0uKik/Ly50ZXN0KGEpfWZ1bmN0
aW9uIGNjKGEpe2E9YS5zcGxpdCgnLScpWzBdLnNwbGl0KCcuJyk7Zm9yKHZhciBiPVsnMScsJzAn
LCczJ10sYz0wOzM+YztjKyspe3ZhciBkPXBhcnNlSW50KGFbY10sMTApLGU9cGFyc2VJbnQoYltj
XSwxMCk7aWYoZD5lKWJyZWFrO2Vsc2UgaWYoZDxlKXJldHVybiExfXJldHVybiEwfTtmdW5jdGlv
biBkYyhhLGIpe3JldHVybiBiYyhhKSYmY2MoYSk/Yj9iOltdOmImJidzdHJpbmcnPT09dHlwZW9m
IGI/SlNPTi5wYXJzZShiKTpbXX07ZnVuY3Rpb24gZWMoKXt2YXIgYT1mYzt2YXIgYj12b2lkIDA9
PT1iP29taWRHbG9iYWw6Yjt0aGlzLmE9YTt0aGlzLmY9Yjt0aGlzLmI9bmV3IFY7dGhpcy5mLm9t
aWQ9dGhpcy5mLm9taWR8fHt9O3RoaXMuZi5vbWlkLnYxX1Nlc3Npb25TZXJ2aWNlQ29tbXVuaWNh
dGlvbj10aGlzLmI7dGhpcy5jPWImJmIuYWRkRXZlbnRMaXN0ZW5lciYmYi5wb3N0TWVzc2FnZT9u
ZXcgVWIoYik6bnVsbDt0aGlzLmc9bnVsbDt0aGlzLmIuYT10aGlzLmguYmluZCh0aGlzKTt0aGlz
LmMmJih0aGlzLmMuYT10aGlzLmkuYmluZCh0aGlzKSl9ZWMucHJvdG90eXBlLmg9ZnVuY3Rpb24o
YSxiKXtnYyh0aGlzLGEsYix0aGlzLmIpfTsKZWMucHJvdG90eXBlLmk9ZnVuY3Rpb24oYSxiKXt0
aGlzLmd8fCh0aGlzLmc9Yik7dGhpcy5nIT1iP0ooJ1RoZSBzb3VyY2Ugd2luZG93IG9mIHNlc3Np
b24gY2xpZW50IHBvc3QgbWVzc2FnZXMgY2Fubm90IGJlIGNoYW5nZWQgZnJvbSB0aGUgc291cmNl
IG9mIHRoZSBmaXJzdCBtZXNzYWdlLicpOmdjKHRoaXMsYSxiLHRoaXMuYyl9OwpmdW5jdGlvbiBn
YyhhLGIsYyxkKXtmdW5jdGlvbiBlKGgpe2Zvcih2YXIgaz1bXSxtPTA7bTxhcmd1bWVudHMubGVu
Z3RoOysrbSlrW21dPWFyZ3VtZW50c1ttXTtrPW5ldyBVKGYsJ3Jlc3BvbnNlJyxnLGJjKGcpJiZj
YyhnKT9rOkpTT04uc3RyaW5naWZ5KGspKTtkLmIoayxjKX12YXIgZj1iLmIsbD1iLm1ldGhvZCxn
PWIudmVyc2lvbjtiPWRjKGcsYi5hKTt0cnl7aGMoYSxsLGUsYil9Y2F0Y2goaCl7ZC5iKG5ldyBV
KGYsJ2Vycm9yJyxnLCdcbiAgICAgICAgbmFtZTogJytoLm5hbWUrJ1xuICAgICAgICBtZXNzYWdl
OiAnK2gubWVzc2FnZSsnXG4gICAgICAgIGZpbGVuYW1lOiAnK2guZmlsZW5hbWUrJ1xuICAgICAg
ICBsaW5lTnVtYmVyOiAnK2gubGluZU51bWJlcisnXG4gICAgICAgIGNvbHVtbk51bWJlcjogJyto
LmNvbHVtbk51bWJlcisnXG4gICAgICAgIHN0YWNrOiAnK2guc3RhY2srJ1xuICAgICAgICB0b1N0
cmluZygpOiAnK2gudG9TdHJpbmcoKSksYyl9fQpmdW5jdGlvbiBoYyhhLGIsYyxkKXtzd2l0Y2go
Yil7Y2FzZSAnU2Vzc2lvblNlcnZpY2UucmVnaXN0ZXJBZEV2ZW50cyc6YS5hLnJlZ2lzdGVyQWRF
dmVudHMoKTticmVhaztjYXNlICdTZXNzaW9uU2VydmljZS5yZWdpc3Rlck1lZGlhRXZlbnRzJzph
LmEucmVnaXN0ZXJNZWRpYUV2ZW50cygpO2JyZWFrO2Nhc2UgJ1Nlc3Npb25TZXJ2aWNlLnJlZ2lz
dGVyU2Vzc2lvbk9ic2VydmVyJzphLmEucmVnaXN0ZXJTZXNzaW9uT2JzZXJ2ZXIoYyk7YnJlYWs7
Y2FzZSAnU2Vzc2lvblNlcnZpY2Uuc2V0U2xvdEVsZW1lbnQnOmM9cChkKS5uZXh0KCkudmFsdWU7
YS5hLnNldFNsb3RFbGVtZW50KGMpO2JyZWFrO2Nhc2UgJ1Nlc3Npb25TZXJ2aWNlLnNldFZpZGVv
RWxlbWVudCc6Yz1wKGQpLm5leHQoKS52YWx1ZTthPWEuYTt1YihjKT8oYS5hLmc9YyxhLmImJnZi
KGEuYikpOkooJ3NldFZpZGVvRWxlbWVudCBjYWxsZWQgd2l0aCBhIG5vbi1IVE1MVmlkZW9FbGVt
ZW50LiBJdCB3aWxsIGJlIGlnbm9yZWQuJyk7CmJyZWFrO2Nhc2UgJ1Nlc3Npb25TZXJ2aWNlLnNl
dEVsZW1lbnRCb3VuZHMnOmM9cChkKS5uZXh0KCkudmFsdWU7YS5hLnNldEVsZW1lbnRCb3VuZHMo
Yyk7YnJlYWs7Y2FzZSAnU2Vzc2lvblNlcnZpY2Uuc3RhcnRTZXNzaW9uJzpKKCdTZXNzaW9uIHN0
YXJ0IGZyb20gSlMgaXMgbm90IHN1cHBvcnRlZCBpbiBtb2JpbGUgYXBwLicpO2JyZWFrO2Nhc2Ug
J1Nlc3Npb25TZXJ2aWNlLmZpbmlzaFNlc3Npb24nOkooJ1Nlc3Npb24gZmluaXNoIGZyb20gSlMg
aXMgbm90IHN1cHBvcnRlZCBpbiBtb2JpbGUgYXBwLicpO2JyZWFrO2Nhc2UgJ1Nlc3Npb25TZXJ2
aWNlLmltcHJlc3Npb25PY2N1cnJlZCc6WShhLmEsJ2ltcHJlc3Npb24nKTticmVhaztjYXNlICdT
ZXNzaW9uU2VydmljZS5sb2FkZWQnOihjPXAoZCkubmV4dCgpLnZhbHVlKT8oYj17c2tpcHBhYmxl
OmMuaXNTa2lwcGFibGUsYXV0b1BsYXk6Yy5pc0F1dG9QbGF5LHBvc2l0aW9uOmMucG9zaXRpb259
LGMuaXNTa2lwcGFibGUmJgooYi5za2lwT2Zmc2V0PWMuc2tpcE9mZnNldCksWShhLmEsJ2xvYWRl
ZCcsYikpOlkoYS5hLCdsb2FkZWQnKTticmVhaztjYXNlICdTZXNzaW9uU2VydmljZS5zdGFydCc6
Yj1wKGQpO2M9Yi5uZXh0KCkudmFsdWU7Yj1iLm5leHQoKS52YWx1ZTtZKGEuYSwnc3RhcnQnLHtk
dXJhdGlvbjpjLG1lZGlhUGxheWVyVm9sdW1lOmJ9KTticmVhaztjYXNlICdTZXNzaW9uU2Vydmlj
ZS5maXJzdFF1YXJ0aWxlJzpZKGEuYSwnZmlyc3RRdWFydGlsZScpO2JyZWFrO2Nhc2UgJ1Nlc3Np
b25TZXJ2aWNlLm1pZHBvaW50JzpZKGEuYSwnbWlkcG9pbnQnKTticmVhaztjYXNlICdTZXNzaW9u
U2VydmljZS50aGlyZFF1YXJ0aWxlJzpZKGEuYSwndGhpcmRRdWFydGlsZScpO2JyZWFrO2Nhc2Ug
J1Nlc3Npb25TZXJ2aWNlLmNvbXBsZXRlJzpZKGEuYSwnY29tcGxldGUnKTticmVhaztjYXNlICdT
ZXNzaW9uU2VydmljZS5wYXVzZSc6WShhLmEsJ3BhdXNlJyk7YnJlYWs7Y2FzZSAnU2Vzc2lvblNl
cnZpY2UucmVzdW1lJzpZKGEuYSwKJ3Jlc3VtZScpO2JyZWFrO2Nhc2UgJ1Nlc3Npb25TZXJ2aWNl
LmJ1ZmZlclN0YXJ0JzpZKGEuYSwnYnVmZmVyU3RhcnQnKTticmVhaztjYXNlICdTZXNzaW9uU2Vy
dmljZS5idWZmZXJGaW5pc2gnOlkoYS5hLCdidWZmZXJGaW5pc2gnKTticmVhaztjYXNlICdTZXNz
aW9uU2VydmljZS5za2lwcGVkJzpZKGEuYSwnc2tpcHBlZCcpO2JyZWFrO2Nhc2UgJ1Nlc3Npb25T
ZXJ2aWNlLnZvbHVtZUNoYW5nZSc6Yz17bWVkaWFQbGF5ZXJWb2x1bWU6cChkKS5uZXh0KCkudmFs
dWV9O1koYS5hLCd2b2x1bWVDaGFuZ2UnLGMpO2JyZWFrO2Nhc2UgJ1Nlc3Npb25TZXJ2aWNlLnBs
YXllclN0YXRlQ2hhbmdlJzpjPXtzdGF0ZTpwKGQpLm5leHQoKS52YWx1ZX07WShhLmEsJ3BsYXll
clN0YXRlQ2hhbmdlJyxjKTticmVhaztjYXNlICdTZXNzaW9uU2VydmljZS5hZFVzZXJJbnRlcmFj
dGlvbic6Yz17aW50ZXJhY3Rpb25UeXBlOnAoZCkubmV4dCgpLnZhbHVlfTtZKGEuYSwnYWRVc2Vy
SW50ZXJhY3Rpb24nLApjKTticmVhaztjYXNlICdTZXNzaW9uU2VydmljZS5zZXRDbGllbnRJbmZv
Jzp2YXIgZT1wKGQpO2I9ZS5uZXh0KCkudmFsdWU7ZD1lLm5leHQoKS52YWx1ZTtlPWUubmV4dCgp
LnZhbHVlO2E9YS5hLnNldENsaWVudEluZm8oYixkLGUpO2MoYSk7YnJlYWs7Y2FzZSAnU2Vzc2lv
blNlcnZpY2UuaW5qZWN0VmVyaWZpY2F0aW9uU2NyaXB0UmVzb3VyY2VzJzpjPXAoZCkubmV4dCgp
LnZhbHVlO2EuYS5pbmplY3RWZXJpZmljYXRpb25TY3JpcHRSZXNvdXJjZXMoYyk7YnJlYWs7Y2Fz
ZSAnU2Vzc2lvblNlcnZpY2Uuc2V0Q3JlYXRpdmVUeXBlJzpjPXAoZCkubmV4dCgpLnZhbHVlO2Eu
YS5zZXRDcmVhdGl2ZVR5cGUoYyk7YnJlYWs7Y2FzZSAnU2Vzc2lvblNlcnZpY2Uuc2V0SW1wcmVz
c2lvblR5cGUnOmM9cChkKS5uZXh0KCkudmFsdWU7YS5hLnNldEltcHJlc3Npb25UeXBlKGMpO2Jy
ZWFrO2Nhc2UgJ1Nlc3Npb25TZXJ2aWNlLnNldENvbnRlbnRVcmwnOmM9cChkKS5uZXh0KCkudmFs
dWU7CmEuYS5hLm89YzticmVhaztjYXNlICdTZXNzaW9uU2VydmljZS5zZXNzaW9uRXJyb3InOmI9
cChkKSxjPWIubmV4dCgpLnZhbHVlLGI9Yi5uZXh0KCkudmFsdWUsYS5hLmVycm9yKGMsYil9fTtm
dW5jdGlvbiBaKCl7dmFyIGE9VyxiPWljLGM9amMsZD1aYjt0aGlzLmY9ZmM7dGhpcy5hPWE7dGhp
cy5jPWI7dGhpcy5oPWM7dGhpcy5nPWQ7dGhpcy5iPUgoKX1uPVoucHJvdG90eXBlOwpuLlQ9ZnVu
Y3Rpb24oYSl7aWYoISghKGEmJk4oYSkmJlAoYS5pbXByZXNzaW9uT3duZXIscmEpKXx8J3ZpZGVv
RXZlbnRzT3duZXInaW4gYSYmbnVsbCE9YS52aWRlb0V2ZW50c093bmVyJiYhUChhLnZpZGVvRXZl
bnRzT3duZXIscmEpfHwnbWVkaWFFdmVudHNPd25lcidpbiBhJiZudWxsIT1hLm1lZGlhRXZlbnRz
T3duZXImJiFQKGEubWVkaWFFdmVudHNPd25lcixyYSkpKXtpZihhLmNyZWF0aXZlVHlwZSYmYS5p
bXByZXNzaW9uVHlwZSl7dmFyIGI9YS5tZWRpYUV2ZW50c093bmVyO251bGw9PXRoaXMuYi5hJiZ0
aGlzLmYuc2V0Q3JlYXRpdmVUeXBlKGEuY3JlYXRpdmVUeXBlLGIpO251bGw9PXRoaXMuYi5oJiYo
dGhpcy5iLmg9YS5pbXByZXNzaW9uVHlwZSk7bmIodGhpcy5hLGIpfWVsc2UgYj1hLnZpZGVvRXZl
bnRzT3duZXIsdGhpcy5iLmI9bnVsbD09Ynx8J25vbmUnPT09Yj8nZGlzcGxheSc6J3ZpZGVvJyx0
aGlzLmIuYT1udWxsLHRoaXMuYi5oPW51bGwsbmIodGhpcy5hLGIpOwpvYih0aGlzLmEsYS5pbXBy
ZXNzaW9uT3duZXIpO2EmJm51bGwhPWEuaXNvbGF0ZVZlcmlmaWNhdGlvblNjcmlwdHMmJidib29s
ZWFuJz09PXR5cGVvZiBhLmlzb2xhdGVWZXJpZmljYXRpb25TY3JpcHRzJiYodGhpcy5iLkM9YS5p
c29sYXRlVmVyaWZpY2F0aW9uU2NyaXB0cyl9fTsKbi5XPWZ1bmN0aW9uKGEsYixjLGQpe3ZhciBl
O2lmKE4oYikpe2lmKGU9UChiLmVudmlyb25tZW50LHVhKSYmUChiLmFkU2Vzc2lvblR5cGUscWEp
KWU9Yi5vbWlkTmF0aXZlSW5mbyxlPU4oZSk/TyhlLnBhcnRuZXJOYW1lKSYmTyhlLnBhcnRuZXJW
ZXJzaW9uKTohMTtlJiYoZT1iLmFwcCxlPU4oZSk/TyhlLmxpYnJhcnlWZXJzaW9uKSYmTyhlLmFw
cElkKTohMSl9ZWxzZSBlPSExO2UmJihQYihkKSYmKHRoaXMuYi52PW5ldyBNYXAoT2JqZWN0LmVu
dHJpZXMoZCkpKSxkPXRoaXMuZixjPXZvaWQgMD09PWM/bnVsbDpjLG51bGw9PWEmJihhPVZiKCkp
LGIuY2FuTWVhc3VyZVZpc2liaWxpdHk9ZC5iLkMoKSxkLmEuYWRTZXNzaW9uSWQ9YSxhPWQuYSxl
PWIsdm9pZCAwIT09ZS5jb250ZW50VXJsJiYoYS5vPWUuY29udGVudFVybCxlLmNvbnRlbnRVcmw9
dm9pZCAwKSxlPWEuY3x8e30sYi5vbWlkSnNJbmZvPU9iamVjdC5hc3NpZ24oe30sZS5vbWlkSnNJ
bmZvfHx7fSxiLm9taWRKc0luZm98fAp7fSksZT1iPU9iamVjdC5hc3NpZ24oe30sZSxiKSxhLkN8
fChudWxsIT1hLmc/KGUudmlkZW9FbGVtZW50PWEuZyxlLmFjY2Vzc01vZGU9J2Z1bGwnKTpudWxs
IT1hLmYmJihlLnNsb3RFbGVtZW50PWEuZixlLmFjY2Vzc01vZGU9J2Z1bGwnKSksYS5jPWIsZWIo
ZC5jLGMpLGQuYiYmUihkLmIpKX07bi5VPWZ1bmN0aW9uKCl7dmFyIGE9dGhpcy5mO2liKGEuYyk7
YS5iLm0oKX07bi4kPWZ1bmN0aW9uKGEpe04oYSkmJnJiKGEueCkmJnJiKGEueSkmJnJiKGEud2lk
dGgpJiZyYihhLmhlaWdodCkmJih0aGlzLmIuRz1hLHFiKHRoaXMuYywnY29udGFpbmVyJykpfTtu
LmFhPWZ1bmN0aW9uKGEpe1AoYSx0YSkmJih0aGlzLmIubT1hLCdiYWNrZ3JvdW5kZWQnPT09YT9x
Yih0aGlzLmMsJ2NvbnRhaW5lcicsJ2JhY2tncm91bmRlZCcpOnFiKHRoaXMuYywnY29udGFpbmVy
JykpfTtuLlg9ZnVuY3Rpb24oYSl7J2ltcHJlc3Npb24nPT09YSYmKHRoaXMuTSgpLHRoaXMuZyYm
Uih0aGlzLmcpKX07Cm4uTT1mdW5jdGlvbigpe3BiKHRoaXMuYSwnbmF0aXZlJykmJmpiKHRoaXMu
YSwnbmF0aXZlJyl9O24uVj1mdW5jdGlvbihhKXthPXZvaWQgMD09PWE/bnVsbDphO00odGhpcy5h
LCduYXRpdmUnKSYma2IodGhpcy5hLCduYXRpdmUnLGEpfTtuLmVycm9yPWZ1bmN0aW9uKGEsYil7
UChhLG9hKSYmZGIodGhpcy5hLGEsYil9O24uWT1mdW5jdGlvbihhLGIpe3RoaXMuTihhLGIpfTtu
Lk49ZnVuY3Rpb24oYSxiKXtNKHRoaXMuYSwnbmF0aXZlJykmJlAoYSx6KSYmKHZvaWQgMD09PWJ8
fE4oYikpJiYoJ2xvYWRlZCc9PWE/a2IodGhpcy5hLCduYXRpdmUnLGIpOmxiKHRoaXMuYSxhLCdu
YXRpdmUnLGIpKX07Cm4uWj1mdW5jdGlvbihhKXtpZignbm9uZSchPT10aGlzLmEuYS5pJiYnbnVt
YmVyJz09PXR5cGVvZiBhJiYhaXNOYU4oYSkpe3RoaXMuYi51PWE7YT10aGlzLmg7dmFyIGI9YS5h
LkI7bnVsbCE9YiYmbGIoYS5iLCd2b2x1bWVDaGFuZ2UnLCduYXRpdmUnLHttZWRpYVBsYXllclZv
bHVtZTpiLGRldmljZVZvbHVtZTphLmEudX0pfX07Wi5wcm90b3R5cGUuc3RhcnRTZXNzaW9uPVou
cHJvdG90eXBlLlc7Wi5wcm90b3R5cGUuZXJyb3I9Wi5wcm90b3R5cGUuZXJyb3I7Wi5wcm90b3R5
cGUuZmluaXNoU2Vzc2lvbj1aLnByb3RvdHlwZS5VO1oucHJvdG90eXBlLnB1Ymxpc2hBZEV2ZW50
PVoucHJvdG90eXBlLlg7Wi5wcm90b3R5cGUucHVibGlzaEltcHJlc3Npb25FdmVudD1aLnByb3Rv
dHlwZS5NO1oucHJvdG90eXBlLnB1Ymxpc2hWaWRlb0V2ZW50PVoucHJvdG90eXBlLlk7Wi5wcm90
b3R5cGUucHVibGlzaE1lZGlhRXZlbnQ9Wi5wcm90b3R5cGUuTjsKWi5wcm90b3R5cGUucHVibGlz
aExvYWRlZEV2ZW50PVoucHJvdG90eXBlLlY7Wi5wcm90b3R5cGUuc2V0TmF0aXZlVmlld0hpZXJh
cmNoeT1aLnByb3RvdHlwZS4kO1oucHJvdG90eXBlLnNldFN0YXRlPVoucHJvdG90eXBlLmFhO1ou
cHJvdG90eXBlLnNldERldmljZVZvbHVtZT1aLnByb3RvdHlwZS5aO1oucHJvdG90eXBlLmluaXQ9
Wi5wcm90b3R5cGUuVDtmdW5jdGlvbiBrYygpe3ZhciBhPVcsYj1sYzt2YXIgYz12b2lkIDA9PT1j
P0k6Yzt0aGlzLmc9YTt0aGlzLmE9Yjt0aGlzLmg9e307dGhpcy5mPXt9O3RoaXMuYz1uZXcgVjtj
Lm9taWQ9Yy5vbWlkfHx7fTtjLm9taWQudjFfVmVyaWZpY2F0aW9uU2VydmljZUNvbW11bmljYXRp
b249dGhpcy5jO3RoaXMuYj1udWxsO2MmJmMuYWRkRXZlbnRMaXN0ZW5lciYmYy5wb3N0TWVzc2Fn
ZSYmKHRoaXMuYj1uZXcgVWIoYykpO3RoaXMuYy5hPXRoaXMuaS5iaW5kKHRoaXMpO3RoaXMuYiYm
KHRoaXMuYi5hPXRoaXMuai5iaW5kKHRoaXMpKX1mdW5jdGlvbiBtYyhhLGIsYyxkKXtFYihhLmEs
YixjLGQpfWZ1bmN0aW9uIG5jKGEsYixjLGQpe0RiKGEuYSwnZG93bmxvYWRKYXZhU2NyaXB0UmVz
b3VyY2UnKShiLGMsZCl9a2MucHJvdG90eXBlLmo9ZnVuY3Rpb24oYSxiKXt0aGlzLmImJm9jKHRo
aXMsYSxiLHRoaXMuYil9O2tjLnByb3RvdHlwZS5pPWZ1bmN0aW9uKGEsYil7b2ModGhpcyxhLGIs
dGhpcy5jKX07CmZ1bmN0aW9uIG9jKGEsYixjLGQpe2Z1bmN0aW9uIGUoRSl7Zm9yKHZhciBYPVtd
LHBhPTA7cGE8YXJndW1lbnRzLmxlbmd0aDsrK3BhKVhbcGFdPWFyZ3VtZW50c1twYV07WD1uZXcg
VShmLCdyZXNwb25zZScsZyxiYyhnKSYmY2MoZyk/WDpKU09OLnN0cmluZ2lmeShYKSk7ZC5iKFgs
Yyl9dmFyIGY9Yi5iLGw9Yi5tZXRob2QsZz1iLnZlcnNpb247Yj1kYyhnLGIuYSk7dHJ5e3N3aXRj
aChsKXtjYXNlICdWZXJpZmljYXRpb25TZXJ2aWNlLmFkZEV2ZW50TGlzdGVuZXInOnZhciBoPXAo
YikubmV4dCgpLnZhbHVlO1phKGEuZyxoLGUpO2JyZWFrO2Nhc2UgJ1ZlcmlmaWNhdGlvblNlcnZp
Y2UuYWRkU2Vzc2lvbkxpc3RlbmVyJzp2YXIgaz1wKGIpLG09ay5uZXh0KCkudmFsdWUsdj1rLm5l
eHQoKS52YWx1ZTthYihhLmcsZSxtLHYpO2JyZWFrO2Nhc2UgJ1ZlcmlmaWNhdGlvblNlcnZpY2Uu
c2VuZFVybCc6dmFyIEI9cChiKS5uZXh0KCkudmFsdWU7bWMoYSxCLGZ1bmN0aW9uKCl7cmV0dXJu
IGUoITApfSwKZnVuY3Rpb24oKXtyZXR1cm4gZSghMSl9KTticmVhaztjYXNlICdWZXJpZmljYXRp
b25TZXJ2aWNlLnNldFRpbWVvdXQnOnZhciB4PXAoYiksRj14Lm5leHQoKS52YWx1ZSxLPXgubmV4
dCgpLnZhbHVlO2EuaFtGXT1BYihhLmEsJ3NldFRpbWVvdXQnKShlLEspO2JyZWFrO2Nhc2UgJ1Zl
cmlmaWNhdGlvblNlcnZpY2UuY2xlYXJUaW1lb3V0Jzp2YXIgdGM9cChiKS5uZXh0KCkudmFsdWU7
Q2IoYS5hLGEuaFt0Y10pO2JyZWFrO2Nhc2UgJ1ZlcmlmaWNhdGlvblNlcnZpY2Uuc2V0SW50ZXJ2
YWwnOnZhciBOYj1wKGIpLHVjPU5iLm5leHQoKS52YWx1ZSx2Yz1OYi5uZXh0KCkudmFsdWU7YS5m
W3VjXT16YihhLmEsZSx2Yyk7YnJlYWs7Y2FzZSAnVmVyaWZpY2F0aW9uU2VydmljZS5jbGVhcklu
dGVydmFsJzp2YXIgd2M9cChiKS5uZXh0KCkudmFsdWU7QmIoYS5hLGEuZlt3Y10pO2JyZWFrO2Nh
c2UgJ1ZlcmlmaWNhdGlvblNlcnZpY2UuaW5qZWN0SmF2YVNjcmlwdFJlc291cmNlJzp2YXIgeGM9
CnAoYikubmV4dCgpLnZhbHVlO25jKGEseGMsZnVuY3Rpb24oRSl7cmV0dXJuIGUoITAsRSl9LGZ1
bmN0aW9uKCl7cmV0dXJuIGUoITEpfSk7YnJlYWs7Y2FzZSAnVmVyaWZpY2F0aW9uU2VydmljZS5n
ZXRWZXJzaW9uJzpwKGIpLm5leHQoKTt2YXIgeWM9SCgpLmMub21pZEpzSW5mbztlKHljLnNlcnZp
Y2VWZXJzaW9uKX19Y2F0Y2goRSl7ZC5iKG5ldyBVKGYsJ2Vycm9yJyxnLCdcbiAgICAgICAgICAg
ICAgbmFtZTogJytFLm5hbWUrJ1xuICAgICAgICAgICAgICBtZXNzYWdlOiAnK0UubWVzc2FnZSsn
XG4gICAgICAgICAgICAgIGZpbGVuYW1lOiAnK0UuZmlsZW5hbWUrJ1xuICAgICAgICAgICAgICBs
aW5lTnVtYmVyOiAnK0UubGluZU51bWJlcisnXG4gICAgICAgICAgICAgIGNvbHVtbk51bWJlcjog
JytFLmNvbHVtbk51bWJlcisnXG4gICAgICAgICAgICAgIHN0YWNrOiAnK0Uuc3RhY2srJ1xuICAg
ICAgICAgICAgICB0b1N0cmluZygpOiAnK0UudG9TdHJpbmcoKSsnXG4gICAgICAgICAgJyksCmMp
fX07ZnVuY3Rpb24gcGMoKXt2YXIgYT1JLmRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2lmcmFtZScp
O2EuaWQ9J29taWRfdjFfcHJlc2VudCc7YS5uYW1lPSdvbWlkX3YxX3ByZXNlbnQnO2Euc3R5bGUu
ZGlzcGxheT0nbm9uZSc7SS5kb2N1bWVudC5ib2R5LmFwcGVuZENoaWxkKGEpfWZ1bmN0aW9uIHFj
KCl7dmFyIGE9bmV3IE11dGF0aW9uT2JzZXJ2ZXIoZnVuY3Rpb24oYil7Yi5mb3JFYWNoKGZ1bmN0
aW9uKGMpeydCT0RZJz09PWMuYWRkZWROb2Rlc1swXS5ub2RlTmFtZSYmKHBjKCksYS5kaXNjb25u
ZWN0KCkpfSl9KTthLm9ic2VydmUoSS5kb2N1bWVudC5kb2N1bWVudEVsZW1lbnQse2NoaWxkTGlz
dDohMH0pfTt2YXIgVz1uZXcgVGEsbGM9bmV3IGZ1bmN0aW9uKCl7dmFyIGE7dGhpcy5hPWE9dm9p
ZCAwPT09YT9vbWlkR2xvYmFsOmF9O25ldyBrYzt2YXIgcmM9bmV3IGZ1bmN0aW9uKCl7fSxzYz1u
ZXcgZnVuY3Rpb24oKXt9LGljPW5ldyBmdW5jdGlvbigpe3RoaXMuYj1XO3RoaXMuYz1zYzt0aGlz
LmE9SCgpfSx6YztJP3pjPUkuSW50ZXJzZWN0aW9uT2JzZXJ2ZXImJihJLk11dGF0aW9uT2JzZXJ2
ZXJ8fEkuUmVzaXplT2JzZXJ2ZXIpP25ldyBUKEkscmMsaWMsVyk6bmV3IFMoSSxzYyxpYyxsYyxX
KTp6Yz1udWxsO3ZhciBaYj16YywkYj1uZXcgZnVuY3Rpb24oKXt2YXIgYT1XO3ZhciBiPXZvaWQg
MD09PWI/STpiO3RoaXMuZj1hO3RoaXMuYT1iO3RoaXMuYz1IKCk7dGhpcy5iPVtdfSxmYz1uZXcg
WWIsamM9bmV3IGZ1bmN0aW9uKCl7dmFyIGE9VyxiPUgoKTt0aGlzLmI9YTt0aGlzLmE9Yn07SS5v
bWlkQnJpZGdlPW5ldyBaO25ldyBlYzsKaWYoSS5mcmFtZXMmJkkuZG9jdW1lbnQmJiEoJ29taWRf
djFfcHJlc2VudCdpbiBJLmZyYW1lcykpe3ZhciBBYztpZihBYz1udWxsPT1JLmRvY3VtZW50LmJv
ZHkpQWM9J011dGF0aW9uT2JzZXJ2ZXInaW4gSTtBYz9xYygpOkkuZG9jdW1lbnQuYm9keT9wYygp
OkkuZG9jdW1lbnQud3JpdGUoJzxpZnJhbWUgc3R5bGU9ImRpc3BsYXk6bm9uZSIgaWQ9Im9taWRf
djFfcHJlc2VudCIgbmFtZT0ib21pZF92MV9wcmVzZW50Ij48L2lmcmFtZT4nKX07Cn0pLmNhbGwo
dGhpcywgdGhpcyk7Cgo=
--000000000000545c1f065c91ab40--', '{"to": "info@worldchoiceperfume.com", "date": "Tue, 29 Sep 2026 00:22:38 +0300", "from": "FRANK GODWIN <godwinfranklin419@gmail.com>", "subject": "Hey", "x-gm-gg": "AYBFou3pGPASR250pZcX5b81uIFwNTlonJQmrM5ZUJBjd9hH6xSt8LUYWft6UvsBfUN 55LG4aNgv3Dhcz/vCTqZ1baXTJNS6j+dEUYKorx8HVFB8Ocl6/nxCyyM+oVwRuN/aEDTYVQU1hd yy9MSlAmmg8cu9LZXiD4bbqSEnRqT3n8G+Qd0S6zpg628LG+bhpzR1nUVddsC7/zRc13Cv2JbUR A3b7BHvZ8iiBKoTfLfJu2wUwztJOvqNeKRIjx+cW8GKLE5GRc/bxm6lNnc2HQsQHpJ98gNOU4s4 fmIlZL6Ng8mBPJrv8yTykwhrkK9CM4NNuqkxoP43CCTaq42R5Ws674e2", "arc-seal": "i=1; a=rsa-sha256; t=1790630575; cv=none;", "received": "by mail-dl2-x10.google.com with SMTP id a92af1059eb24-142dd05d97cso4212994c88.2", "message-id": "<CAOLv=Vv=AETvbj+7aZu8_3h+4xMxnvpwLRE6a7u8FG4_My5bLA@mail.gmail.com>", "x-received": "by 2002:a05:701b:2506:b0:148:c6ec:13b3 with SMTP id a92af1059eb24-148c6ec17bcmr7425327c88.17.1790630574666; Mon, 28 Sep 2026 14:22:54 -0700 (PDT)", "content-type": "multipart/mixed; boundary=\"000000000000545c1f065c91ab40\"", "mime-version": "1.0", "received-spf": "pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:38::10 as permitted sender) receiver=mx.cloudflare.net; client-ip=2607:f8b0:4864:38::10; envelope-from=\"godwinfranklin419@gmail.com\"; helo=mail-dl2-x10.google.com;", "x-gm-features": "AclHuK8Xrfug2vkv-NoQgEXV-3D3Gv9ZdqBQ4cP_D3ZkyZJz5wRe9dItN_QBORo", "dkim-signature": "v=1; a=rsa-sha256; c=relaxed/relaxed; d=gmail.com; s=20251104; t=1790630575; x=1791235375; darn=worldchoiceperfume.com; h=content-type:to:subject:message-id:date:from:mime-version:from:to :cc:subject:date:message-id:reply-to:content-type; bh=XKLvp40lotSEGls3Z/2lbZcsJ5wDNDKyxMP8i9pyZ2c=; b=RhJtSQ5tXFjZS9F/2iazM4hb4iz3jZwGp0SyvYH5bcqQRcGFHzMWKT3YBqGORjPMec iJtnsJmTAP6WyTwdjlaBS+68XKW88zoE5RrTCbnR8H28SzYvArQ7hnms4BOnf8DSCqqv /QUNEe8Vd2kBcn6oREHqBYrLhhaSdTRtOW189snriLpz5QP+rclPoZ+YaMX5AUwUiLke 2KS8eQ35cO5NPq1ixvoGckUe5vUQcgg6aAshLEDSRo/YwkgBpb/kUegaB5vLDi4L3XAj GbXBE5MwITiCCLHopP24Y3mCiSj04ShMpVsm0Frmhq52JPVw/8/jJBWirN5nRo2Fn3KN gKhA==", "x-cf-spamh-score": "0 for <info@worldchoiceperfume.com>; Mon, 28 Sep 2026 14:22:55 -0700 (PDT) d=google.com; s=arc-20260327; b=R3qZQYGYQ1x2qqJEidlbvur1nZCIUFa+v+7jbeSLWZU+dcOalD2YGVBgAbLrQcNFx4 iZWAVmEBo8Ea2Z69XIuzB9DooeJiZ/IpQIs1n0j+Ht5L5aP76WAJaRwyfhVEhuE7h4g2 1PiT4WfMjhfCrZbbvmnl1rRTL9kQyKWqyBuzAAarKdk96LdXUnNtKWAx+XrUL4X5YLJi O4HonTtk9xCa2iBfMhGPnABSYDvMVeeAcmhnpvjp0VCj9O5tKLcDG88mKRXcX+8hqoiE mUgEcXLcZJMgqN1hDt5tq3KkeINEbvTBlFHcvuAgwXdlcShdLpyCGSg/NQRBJunTsYkg +sxw== h=to:subject:message-id:date:from:mime-version:dkim-signature; bh=XKLvp40lotSEGls3Z/2lbZcsJ5wDNDKyxMP8i9pyZ2c=; fh=nniGTCdcezPCCWQcYQFtt+TqrAdcQ+jBfMQuxKQBEts=; b=OLEtn2z//I6ATAs1vPBFozm2DYBe0GLMbkdV4/oIgAd7qQM6VItDl4tNorj9C7mDEb XjEmcllZEPWDohps0L+h1P283syycgFA7qojirmXc87Bw+vLtOglprlhQ+OMAbDi0mMb TkXLLjuW3kFV5LL3G+Ad88N9hjyRTsmeA5wxx5MjB8ezR3K47zXozAx4spE08yIREdbh 5myEOgD4iTnBw9aq1fx+WUahJ+r7PHIRyYAJodL8KHhhYj46GDGLRypQML+QvEyzgrE4 TU419OLBrBbHisUMpURIfcJyj9v/K9Gokc86tPcLUsyuwsVjs8YoFkfilYRK38zqIPSY jXJg==; darn=worldchoiceperfume.com", "x-gm-message-state": "AFuF++lnDPL/Awol2g/32+NFGAdpkZZXG7YeL9LcVeLbZKoiSvYZ/JYk FqZSk0K4AIxqorzlRcFabcSo2mYavK8ao51og/D/A9aymqn6zgd1mgn76X8at4YXfIFdhXTft6T g/oGrr77kG4OO6hsO2jsSOZaNuXY5M8cNx5yO", "arc-message-signature": "i=1; a=rsa-sha256; c=relaxed/relaxed; d=google.com; s=arc-20260327;", "authentication-results": "mx.cloudflare.net; dkim=pass header.d=gmail.com header.s=20251104 header.b=RhJtSQ5t; dmarc=pass header.from=gmail.com policy.dmarc=none; spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-dl2-x10.google.com) smtp.helo=mail-dl2-x10.google.com; spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:38::10 as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com; arc=pass smtp.remote-ip=\"2607:f8b0:4864:38::10\"", "x-google-dkim-signature": "v=1; a=rsa-sha256; c=relaxed/relaxed; d=1e100.net; s=20260707; t=1790630575; x=1791235375; h=content-type:to:subject:message-id:date:from:mime-version:x-gm-gg :x-gm-message-state:from:to:cc:subject:date:message-id:reply-to :content-type; bh=XKLvp40lotSEGls3Z/2lbZcsJ5wDNDKyxMP8i9pyZ2c=; b=w+Vi/UsWL7f3zphVUUx2/CtLyZON7gTnOfpLlQhI7oK17EkE4hNtnwYRraIq7jVkpe pnt4ekytq0uhH899cpj5UOc32syeXpWpMxXYhfki/hNcghHDzKXlK1Zj/VxmX/oSTkk/ l56xLU75H6LlzniQn4yNCrO6kdRRg3SRr8EzVoA41RdewoWWc3r/3dpMqKB3kdoEq20B Gz6PFZOT1GfZONXFFElr4S/4ak3wuIS9Nw9nY2/1TuG+5LoxGw5TkilZOYLLB6WVkdWT dK74JoUxa7vIkTEntHa7tTl/SqreU4WLdRsZi9hINlyQgof469/o8C0Yx0JU00EcDHtw Lmlg==", "arc-authentication-results": "i=1; mx.google.com; arc=none"}', 'om_js_content.txt', TRUE, 'none', 'pass', TRUE, FALSE, 'replied', '<CAOLv=Vv=AETvbj+7aZu8_3h+4xMxnvpwLRE6a7u8FG4_My5bLA@mail.gmail.com>', NULL, '2026-09-28T21:22:38+00:00', '2026-09-29T08:45:29+00:00', '2026-09-29T08:45:29+00:00', '2026-09-28T21:22:38+00:00', '2026-09-29T08:45:29+00:00'),
(14, '<CAOLv=Vsm_70EnfFgg+kE2gW1P=0XsXptv0agyPYhDaShG=j=UA@mail.gmail.com>', NULL, NULL, 'godwinfranklin419@gmail.com', 'FRANK GODWIN', 'info@worldchoiceperfume.com', NULL, NULL, NULL, '', 'Hello', '<div dir="auto">Hello<div dir="auto"><br></div></div>', 'Received: from mail-dl2-x0f.google.com (2607:f8b0:4864:38::f)
        by cloudflare-email.net (cloudflare) id Hipi9r6eypxE
        for <info@worldchoiceperfume.com>; Tue, 29 Sep 2026 21:12:16 +0000
ARC-Seal: i=2; a=rsa-sha256; s=cf2024-1; d=cloudflare-email.net; cv=pass;
	b=eFK6FXmE7w4JYXGfTTAU45urvx8WB6RlSzoLClP92s+07shGtl7DT2jjxP95d9z96//peD8Yr
	fXFAni1NzTjlGdwC73Wl811a/OTE5jWi3fIDecn5T7C2mIykoNs7qt3stku4fUPiBXveRKZ8dvX
	ijNbx6XXgcgHfHjSSIXlkvCcm7zhST5dJpCEo4nNF2Wc/OhbTh5nrw0QV87IJvRm61D3RhyXC2L
	IrcMs+cjDhiNF876SWbgjedTtZHL/6LcajtapIuGj7gWjJE3+VeiRKObO9EluR224taGYmO6nAN
	43zPc1kxLJondF6hAISt4WY/ka+aboinKB714eYb3iHQ==;
ARC-Message-Signature: i=2; a=rsa-sha256; s=cf2024-1; d=cloudflare-email.net; c=relaxed/relaxed;
	h=To:Subject:Date:From:from:reply-to:cc:resent-date:resent-from:resent-to
	:resent-cc:in-reply-to:references:list-id:list-help:list-subscribe
	:list-post:list-owner:list-archive; t=1790716337; x=1791321137; bh=BwK/SkuO
	D2doQejDhu7Z1jJvOAmEbuw7Lo53z4JS8Jw=; b=erdEL+64al4Z2eFVDXPPdNvd6qriYruPpUn
	+AIfr5D8d9WxSClHx+rNHxeCHgYwTNE7GGCOdaaXXzsq52CmAwheOml5k8gboIkrwSERGqP++YB
	5AiKA7DMAX2/S9+9zvitPqd3lnRDoO9Vm4nVUuxfIWV1Nsh+X+oM2yf+2KQUeqev/XsVWsqawB1
	1oTr5MerI8mO/BeIO0tBuQXF/njjIV7B6apnk0vPUCstk8F4UOSMMUkFC5jfhPBDyPYCcwT4r9h
	x7F5rpffG2zFDITxqCncPPSnIBb+acWXdg1bf1qPv+Dc2+8jA98thPUepWATlPx2Uu/tdy1Ztfz
	okQUpTg==;
ARC-Authentication-Results: i=2; mx.cloudflare.net;
	dkim=pass header.d=gmail.com header.s=20251104 header.b=JoihKc0n;
	dmarc=pass header.from=gmail.com policy.dmarc=none;
	spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-dl2-x0f.google.com) smtp.helo=mail-dl2-x0f.google.com;
	spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:38::f as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com;
	arc=pass smtp.remote-ip="2607:f8b0:4864:38::f"
Received-SPF: pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:38::f as permitted sender)
	receiver=mx.cloudflare.net; client-ip=2607:f8b0:4864:38::f; envelope-from="godwinfranklin419@gmail.com"; helo=mail-dl2-x0f.google.com;
Authentication-Results: mx.cloudflare.net;
	dkim=pass header.d=gmail.com header.s=20251104 header.b=JoihKc0n;
	dmarc=pass header.from=gmail.com policy.dmarc=none;
	spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-dl2-x0f.google.com) smtp.helo=mail-dl2-x0f.google.com;
	spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:38::f as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com;
	arc=pass smtp.remote-ip="2607:f8b0:4864:38::f"
X-CF-SpamH-Score: 1
Received: by mail-dl2-x0f.google.com with SMTP id a92af1059eb24-1438f55921fso1384308c88.1
        for <info@worldchoiceperfume.com>; Tue, 29 Sep 2026 14:12:16 -0700 (PDT)
ARC-Seal: i=1; a=rsa-sha256; t=1790716336; cv=none;
        d=google.com; s=arc-20260327;
        b=WEtSeNtuHJXppXDFkTXthAjQ8YRyVJqRFnIF7vdKQwTZC7Gs9gTrtRmsx4CvDSHrwA
         znk+UqIY4gToknJiOIgdVAtLSpeFuBXB3YYnvScuMI1aYPq9ObTtxZqYCSTvUh6JMeaS
         gKY4D2AkPLeVBymPgQD66i67ZGLrWuu1nEKEgf6xGWaxmheuhBujrNyezsZZ7OAfo5PP
         FWs7fLyVWJ6YYW5v31sgtPSWn0A2bQAMrVzGstJs0ejYmFp+x1DkhYo+jHpeIKUbsWzo
         H81vNJNKmN03d+advR22Z/qxvxHjgLwHyDv8vW71ZephhVoYtYrhnuN/NA0R6maWKwxq
         aapw==
ARC-Message-Signature: i=1; a=rsa-sha256; c=relaxed/relaxed; d=google.com; s=arc-20260327;
        h=to:subject:message-id:date:from:mime-version:dkim-signature;
        bh=BwK/SkuOD2doQejDhu7Z1jJvOAmEbuw7Lo53z4JS8Jw=;
        fh=nniGTCdcezPCCWQcYQFtt+TqrAdcQ+jBfMQuxKQBEts=;
        b=VB8pq8k9G5pxTx2n1eRku/ClDOguSPm2Y6QQOD3AUGsZqi1agiwUZAI4IuqwQSgd7Q
         tm4qhf3GjW7HvWfdaXsVpJ+R1hC5Zv22brjW0XHYR8v//3OTKdGmiXMj+wGy6GMEXFlv
         fJq1SZtfVU6NtD4mJj8pvlFsRvbhMW0RXB/VHGolmKmKocejKelLo9MvDKz/BmRpzsni
         M6GDSXdV2QRH8TnWgsNJSHH/xQ/zwDxOloeAXkSPASi0HWWdcnb2H+TJSurP6wi2rOB7
         rTH5ccx7LSzCRODE2aYMBRiFwjxLdE0/NMZMCu/cCIlr8cm+TuezR+wgYNQtkTIvpZIZ
         Qztw==;
        darn=worldchoiceperfume.com
ARC-Authentication-Results: i=1; mx.google.com; arc=none
DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
        d=gmail.com; s=20251104; t=1790716336; x=1791321136; darn=worldchoiceperfume.com;
        h=content-type:to:subject:message-id:date:from:mime-version:from:to
         :cc:subject:date:message-id:reply-to:content-type;
        bh=BwK/SkuOD2doQejDhu7Z1jJvOAmEbuw7Lo53z4JS8Jw=;
        b=JoihKc0nYWSQ9KppvGBELNR129TgcgREl1i9cuzufVy8ck7+nZP50EQ++Gfg4sU4sr
         QJIYmeZUU651V/bXpcfpOpIsSDZtzdjazcVxTlwQSbrvPb2FQdZ6iQFzXt0BcgKP5XPF
         OIpJ5aCzlTXUt1tXb3gohdddQgAZX3CPFkJIaU/2Ti1F8FSfrxlbAU/iiUwwYj4HQ4Kx
         LEIUlk9pwbU5Gzjf0VsJYlpUPxTThqYyfSTw7yCtltS9ECUMx8rePKgSbEro5HlnQG/R
         y8frfG0904LfGoLqOaa5oAx35DB9msip5fII7zfY/ar8OM+XU/hktYmI4Ji28HzxYdnB
         ES9g==
X-Google-DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
        d=1e100.net; s=20260707; t=1790716336; x=1791321136;
        h=content-type:to:subject:message-id:date:from:mime-version:x-gm-gg
         :x-gm-message-state:from:to:cc:subject:date:message-id:reply-to
         :content-type;
        bh=BwK/SkuOD2doQejDhu7Z1jJvOAmEbuw7Lo53z4JS8Jw=;
        b=QSR7RDwHa9FkmX4cnRM8ueUm8wAOVXR2yVyhn5/VgxD2Mq4mnBEDKWe+JYT4355+pw
         aExdrDlcPMjOVYiIMNNnffuGi5gQOT9mLfRsZSYZUUe1vIiidb2Xd/EvHah4GxkryAHS
         OPCG+ZUiJQ0OTv14gXRr5foKvVAGsIQcVTTtTaHrf4RkWggbsghtxnLveUMWPHOQW7Gk
         RANSxYKb0KXbN93B7tEqmL9SSiCONcZHxC2u3P8ZPV+aWhLeT23xDpLCA4AmHRetAW9L
         aY/sR8uapkK9bEkB8Cu6gYIfK94GA3+1NpobDk2xKtOOpocmwzvStFzPzCw+yh0QOwo5
         IQjQ==
X-Gm-Message-State: AFuF++n9tnOvwP9spzSYiJxZL1s5QEX3ZBt7ACn+c0NEt6UKHchSMo/g
	AHGzEqJ5P4q4Y9qRUDhG4raeB90lWST/w9S1dCaw2nm/7zxeNJTJkQBUmxe1/NRnDm9Jr4clxDN
	oDwONDR8EP3qDVzQsIG+hjMrgZEbUF4qaEN/1
X-Gm-Gg: AYBFou0ZYx4FLLKB0S4wJ6+T9KbZsN69RR+9+Ra1PQ9OZQVpsv/46a7I+X1a/C8GlKK
	5qv4zzBB1iYLlIFDXQEFoepLuupeOMPRRIi0s+Hj54u4IyVrki0hqZvWjU9TnwlNn8hOlmgr0o7
	aznvwHWfxog9UUoZR/gk5g5GxAZRr5bkc8feUKd8eDpdbOf60OZQRTwsubb3sNa/XemNSM/S3/w
	pUaJc5fWrBhyqgR6M1nJrBfjBLPRFVJO+exjWgxNo3RFDRyN3ep33SiWv5IqYNnjhTZrWXmJRvi
	5IRXLK3RJJT0pYuNUpV9FU9eEXWQGyQjNKNxpgq4amhKdwdI7mqThm3P
X-Received: by 2002:a05:701b:280a:b0:141:51fa:e609 with SMTP id
 a92af1059eb24-14c99ae7a1bmr537809c88.0.1790716336332; Tue, 29 Sep 2026
 14:12:16 -0700 (PDT)
MIME-Version: 1.0
From: FRANK GODWIN <godwinfranklin419@gmail.com>
Date: Wed, 30 Sep 2026 00:12:03 +0300
X-Gm-Features: AclHuK9YXi6u2p0D6vqy0MmJl6PEsqf8Yo14vKb9WQjTgImApwJwl-9S7ES07Gs
Message-ID: <CAOLv=Vsm_70EnfFgg+kE2gW1P=0XsXptv0agyPYhDaShG=j=UA@mail.gmail.com>
Subject: 
To: info@worldchoiceperfume.com
Content-Type: multipart/alternative; boundary="0000000000001ee6b8065ca5a3a6"

--0000000000001ee6b8065ca5a3a6
Content-Type: text/plain; charset="UTF-8"

Hello

--0000000000001ee6b8065ca5a3a6
Content-Type: text/html; charset="UTF-8"

<div dir="auto">Hello<div dir="auto"><br></div></div>

--0000000000001ee6b8065ca5a3a6--', '{"to": "info@worldchoiceperfume.com", "date": "Wed, 30 Sep 2026 00:12:03 +0300", "from": "FRANK GODWIN <godwinfranklin419@gmail.com>", "subject": "", "x-gm-gg": "AYBFou0ZYx4FLLKB0S4wJ6+T9KbZsN69RR+9+Ra1PQ9OZQVpsv/46a7I+X1a/C8GlKK 5qv4zzBB1iYLlIFDXQEFoepLuupeOMPRRIi0s+Hj54u4IyVrki0hqZvWjU9TnwlNn8hOlmgr0o7 aznvwHWfxog9UUoZR/gk5g5GxAZRr5bkc8feUKd8eDpdbOf60OZQRTwsubb3sNa/XemNSM/S3/w pUaJc5fWrBhyqgR6M1nJrBfjBLPRFVJO+exjWgxNo3RFDRyN3ep33SiWv5IqYNnjhTZrWXmJRvi 5IRXLK3RJJT0pYuNUpV9FU9eEXWQGyQjNKNxpgq4amhKdwdI7mqThm3P", "arc-seal": "i=1; a=rsa-sha256; t=1790716336; cv=none;", "received": "by mail-dl2-x0f.google.com with SMTP id a92af1059eb24-1438f55921fso1384308c88.1", "message-id": "<CAOLv=Vsm_70EnfFgg+kE2gW1P=0XsXptv0agyPYhDaShG=j=UA@mail.gmail.com>", "x-received": "by 2002:a05:701b:280a:b0:141:51fa:e609 with SMTP id a92af1059eb24-14c99ae7a1bmr537809c88.0.1790716336332; Tue, 29 Sep 2026 14:12:16 -0700 (PDT)", "content-type": "multipart/alternative; boundary=\"0000000000001ee6b8065ca5a3a6\"", "mime-version": "1.0", "received-spf": "pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:38::f as permitted sender) receiver=mx.cloudflare.net; client-ip=2607:f8b0:4864:38::f; envelope-from=\"godwinfranklin419@gmail.com\"; helo=mail-dl2-x0f.google.com;", "x-gm-features": "AclHuK9YXi6u2p0D6vqy0MmJl6PEsqf8Yo14vKb9WQjTgImApwJwl-9S7ES07Gs", "dkim-signature": "v=1; a=rsa-sha256; c=relaxed/relaxed; d=gmail.com; s=20251104; t=1790716336; x=1791321136; darn=worldchoiceperfume.com; h=content-type:to:subject:message-id:date:from:mime-version:from:to :cc:subject:date:message-id:reply-to:content-type; bh=BwK/SkuOD2doQejDhu7Z1jJvOAmEbuw7Lo53z4JS8Jw=; b=JoihKc0nYWSQ9KppvGBELNR129TgcgREl1i9cuzufVy8ck7+nZP50EQ++Gfg4sU4sr QJIYmeZUU651V/bXpcfpOpIsSDZtzdjazcVxTlwQSbrvPb2FQdZ6iQFzXt0BcgKP5XPF OIpJ5aCzlTXUt1tXb3gohdddQgAZX3CPFkJIaU/2Ti1F8FSfrxlbAU/iiUwwYj4HQ4Kx LEIUlk9pwbU5Gzjf0VsJYlpUPxTThqYyfSTw7yCtltS9ECUMx8rePKgSbEro5HlnQG/R y8frfG0904LfGoLqOaa5oAx35DB9msip5fII7zfY/ar8OM+XU/hktYmI4Ji28HzxYdnB ES9g==", "x-cf-spamh-score": "1 for <info@worldchoiceperfume.com>; Tue, 29 Sep 2026 14:12:16 -0700 (PDT) d=google.com; s=arc-20260327; b=WEtSeNtuHJXppXDFkTXthAjQ8YRyVJqRFnIF7vdKQwTZC7Gs9gTrtRmsx4CvDSHrwA znk+UqIY4gToknJiOIgdVAtLSpeFuBXB3YYnvScuMI1aYPq9ObTtxZqYCSTvUh6JMeaS gKY4D2AkPLeVBymPgQD66i67ZGLrWuu1nEKEgf6xGWaxmheuhBujrNyezsZZ7OAfo5PP FWs7fLyVWJ6YYW5v31sgtPSWn0A2bQAMrVzGstJs0ejYmFp+x1DkhYo+jHpeIKUbsWzo H81vNJNKmN03d+advR22Z/qxvxHjgLwHyDv8vW71ZephhVoYtYrhnuN/NA0R6maWKwxq aapw== h=to:subject:message-id:date:from:mime-version:dkim-signature; bh=BwK/SkuOD2doQejDhu7Z1jJvOAmEbuw7Lo53z4JS8Jw=; fh=nniGTCdcezPCCWQcYQFtt+TqrAdcQ+jBfMQuxKQBEts=; b=VB8pq8k9G5pxTx2n1eRku/ClDOguSPm2Y6QQOD3AUGsZqi1agiwUZAI4IuqwQSgd7Q tm4qhf3GjW7HvWfdaXsVpJ+R1hC5Zv22brjW0XHYR8v//3OTKdGmiXMj+wGy6GMEXFlv fJq1SZtfVU6NtD4mJj8pvlFsRvbhMW0RXB/VHGolmKmKocejKelLo9MvDKz/BmRpzsni M6GDSXdV2QRH8TnWgsNJSHH/xQ/zwDxOloeAXkSPASi0HWWdcnb2H+TJSurP6wi2rOB7 rTH5ccx7LSzCRODE2aYMBRiFwjxLdE0/NMZMCu/cCIlr8cm+TuezR+wgYNQtkTIvpZIZ Qztw==; darn=worldchoiceperfume.com", "x-gm-message-state": "AFuF++n9tnOvwP9spzSYiJxZL1s5QEX3ZBt7ACn+c0NEt6UKHchSMo/g AHGzEqJ5P4q4Y9qRUDhG4raeB90lWST/w9S1dCaw2nm/7zxeNJTJkQBUmxe1/NRnDm9Jr4clxDN oDwONDR8EP3qDVzQsIG+hjMrgZEbUF4qaEN/1", "arc-message-signature": "i=1; a=rsa-sha256; c=relaxed/relaxed; d=google.com; s=arc-20260327;", "authentication-results": "mx.cloudflare.net; dkim=pass header.d=gmail.com header.s=20251104 header.b=JoihKc0n; dmarc=pass header.from=gmail.com policy.dmarc=none; spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-dl2-x0f.google.com) smtp.helo=mail-dl2-x0f.google.com; spf=pass (mx.cloudflare.net: domain of godwinfranklin419@gmail.com designates 2607:f8b0:4864:38::f as permitted sender) smtp.mailfrom=godwinfranklin419@gmail.com; arc=pass smtp.remote-ip=\"2607:f8b0:4864:38::f\"", "x-google-dkim-signature": "v=1; a=rsa-sha256; c=relaxed/relaxed; d=1e100.net; s=20260707; t=1790716336; x=1791321136; h=content-type:to:subject:message-id:date:from:mime-version:x-gm-gg :x-gm-message-state:from:to:cc:subject:date:message-id:reply-to :content-type; bh=BwK/SkuOD2doQejDhu7Z1jJvOAmEbuw7Lo53z4JS8Jw=; b=QSR7RDwHa9FkmX4cnRM8ueUm8wAOVXR2yVyhn5/VgxD2Mq4mnBEDKWe+JYT4355+pw aExdrDlcPMjOVYiIMNNnffuGi5gQOT9mLfRsZSYZUUe1vIiidb2Xd/EvHah4GxkryAHS OPCG+ZUiJQ0OTv14gXRr5foKvVAGsIQcVTTtTaHrf4RkWggbsghtxnLveUMWPHOQW7Gk RANSxYKb0KXbN93B7tEqmL9SSiCONcZHxC2u3P8ZPV+aWhLeT23xDpLCA4AmHRetAW9L aY/sR8uapkK9bEkB8Cu6gYIfK94GA3+1NpobDk2xKtOOpocmwzvStFzPzCw+yh0QOwo5 IQjQ==", "arc-authentication-results": "i=1; mx.google.com; arc=none"}', NULL, FALSE, 'none', 'pass', TRUE, FALSE, 'replied', '<CAOLv=Vsm_70EnfFgg+kE2gW1P=0XsXptv0agyPYhDaShG=j=UA@mail.gmail.com>', NULL, '2026-09-29T21:12:03+00:00', '2026-09-29T21:31:57+00:00', '2026-09-29T21:31:57+00:00', '2026-09-29T21:12:03+00:00', '2026-09-29T21:31:57+00:00'),
(15, '<CAFywjrqYGinWPECWDtND6VEbT_969ANYnfoABe9DErhu0TGOTw@mail.gmail.com>', NULL, NULL, 'gideonmsuya146@gmail.com', 'Gideon Msuya', 'info@worldchoiceperfume.com', NULL, NULL, NULL, '', 'Hello world choice', '<div dir="auto">Hello world choice</div>', 'Received: from mail-dy2-x18.google.com (2607:f8b0:4864:36::18)
        by cloudflare-email.net (cloudflare) id 6e9UW51TVCyE
        for <info@worldchoiceperfume.com>; Fri, 02 Oct 2026 18:18:02 +0000
ARC-Seal: i=2; a=rsa-sha256; s=cf2024-1; d=cloudflare-email.net; cv=pass;
	b=N0Dt9s//BHFjeTwjEqECksXq8LIyp2wzWP4tyo+PqCdr1XK7a2UDZekNnfSKUwoeziJiMPMap
	KMUefn2Fcbs7el4qY9jIyEGhMxCaCFDla4RIzU913t3cj7sRuxR8g+Xp2ZAJjuJcBD6FruI1pBt
	YDTPKyINoyNAxju81M691acP8gcTfE5Qn3JTSKzQEFww/T7TRSiLDGZaENE+YYhSCVOiC2XETW0
	Lyw1W7ZFntOou/KKpJcDURvKAA+AlcU92as5AsvFSdyckgy+Sk1ffzPZC2135htECTrooKgOgj8
	Gx4eThtYhYvYnkIGavi9Fje2PJyq5Rb3w2+76AZDeWxw==;
ARC-Message-Signature: i=2; a=rsa-sha256; s=cf2024-1; d=cloudflare-email.net; c=relaxed/relaxed;
	h=To:Subject:Date:From:from:reply-to:cc:resent-date:resent-from:resent-to
	:resent-cc:in-reply-to:references:list-id:list-help:list-subscribe
	:list-post:list-owner:list-archive; t=1790965082; x=1791569882; bh=vEtbyrqc
	YS1jlhOX7peoRy488tm8YOpNchHOxPXh9C0=; b=BBr2nMDGeFrEDVbYCouLnoSWlnY2vhfZAK6
	pt4DNQDLWQvdpL66o51v2Z5Q2MtdHh05j+89Ja4FMcXgQtRkk3kNxTnEHopY3ksfj3V0tZGvAZw
	zOBxlzIuw38VTLbk9Bye6uYeT2xupC2byiikgMnGzV4iLgBVb0M7DQtqINR3IGM3tNRBYGiNkG2
	p97LMgVCwOp2rDwWw80HsIyq5gI1HuKMcCffXhb5J6t90c7bS2Gslt0qO8wfmC6usTxSTwAFClW
	0mXWLc7TzOZiYnU9MY6yOv0ep79U4Drpe7a4ze+0SD1IVANomFyK4B46+W4CGILleAc5ZGVWQJX
	/d/FRKA==;
ARC-Authentication-Results: i=2; mx.cloudflare.net;
	dkim=pass header.d=gmail.com header.s=20251104 header.b=HnGrztXY;
	dmarc=pass header.from=gmail.com policy.dmarc=none;
	spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-dy2-x18.google.com) smtp.helo=mail-dy2-x18.google.com;
	spf=pass (mx.cloudflare.net: domain of gideonmsuya146@gmail.com designates 2607:f8b0:4864:36::18 as permitted sender) smtp.mailfrom=gideonmsuya146@gmail.com;
	arc=pass smtp.remote-ip="2607:f8b0:4864:36::18"
Received-SPF: pass (mx.cloudflare.net: domain of gideonmsuya146@gmail.com designates 2607:f8b0:4864:36::18 as permitted sender)
	receiver=mx.cloudflare.net; client-ip=2607:f8b0:4864:36::18; envelope-from="gideonmsuya146@gmail.com"; helo=mail-dy2-x18.google.com;
Authentication-Results: mx.cloudflare.net;
	dkim=pass header.d=gmail.com header.s=20251104 header.b=HnGrztXY;
	dmarc=pass header.from=gmail.com policy.dmarc=none;
	spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-dy2-x18.google.com) smtp.helo=mail-dy2-x18.google.com;
	spf=pass (mx.cloudflare.net: domain of gideonmsuya146@gmail.com designates 2607:f8b0:4864:36::18 as permitted sender) smtp.mailfrom=gideonmsuya146@gmail.com;
	arc=pass smtp.remote-ip="2607:f8b0:4864:36::18"
X-CF-SpamH-Score: 1
Received: by mail-dy2-x18.google.com with SMTP id 5a478bee46e88-344447f9c3dso4793256eec.0
        for <info@worldchoiceperfume.com>; Fri, 02 Oct 2026 11:18:02 -0700 (PDT)
ARC-Seal: i=1; a=rsa-sha256; t=1790965082; cv=none;
        d=google.com; s=arc-20260327;
        b=GLOk0krzmOsf9MUtR6O9PI203lx4JYcG2n75h9OetC/M4V94Y5OkNp2wrKkmuFyYar
         ZiRLtRnu48exAN6SWAeqbEe4X57NJPFQJS5E31mUxRI34Ar0gA2R9EWO1O6reEpjYtXA
         skSVmNewvZZsGdElOzBZ5pNKE2e+pBS5prNDrouyQg19ysTw7m+jcVxLsM7rDs4+paBP
         faXsavAoSd5+Q4/8Kkwjc5taY6+733Wioj4LTJIUsStTGlkrEHIHYhTM0J7XsAWYZKft
         tijP8xlGRiN7HMkfh4boWvzsPFCe44BBh+Y6h9J+lOfI2MG42Gd8wR9ThLX0eSkvOQs1
         nwAw==
ARC-Message-Signature: i=1; a=rsa-sha256; c=relaxed/relaxed; d=google.com; s=arc-20260327;
        h=to:subject:message-id:date:from:mime-version:dkim-signature;
        bh=vEtbyrqcYS1jlhOX7peoRy488tm8YOpNchHOxPXh9C0=;
        fh=nniGTCdcezPCCWQcYQFtt+TqrAdcQ+jBfMQuxKQBEts=;
        b=Lyjvuwrv7rG9LTZTPOa1V+Y+YIhSx1YP5cw7Rr9sedBOED5DTsl/DF3GDbLj8MaXI5
         3T1O6vEUStFGH+VaKi8rlvVcvSn/6+9owPZvyd8z15DZJsXn/o4LNVMVr+i4+Hdy6FZg
         7SwJwSTr6OAAKdg9NeeYcH+tWhfi8hLXKnhbGvZCmfQMWTXzedi4ofvTUCr3fvK5sgfr
         srM6s7GunZMI5HpmAnYIZ0HU5Vr1MVfRksJY/PTlb2ppu2uJPpoNwwLQS2NJTcqm8gAw
         eBG2xMG7+HVGaZ8hAJign3FMQqXQaJtE0ruajFE8hvK87u1m1k0as/soqjlL+AcqtvNv
         mpkQ==;
        darn=worldchoiceperfume.com
ARC-Authentication-Results: i=1; mx.google.com; arc=none
DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
        d=gmail.com; s=20251104; t=1790965082; x=1791569882; darn=worldchoiceperfume.com;
        h=content-type:to:subject:message-id:date:from:mime-version:from:to
         :cc:subject:date:message-id:reply-to:content-type;
        bh=vEtbyrqcYS1jlhOX7peoRy488tm8YOpNchHOxPXh9C0=;
        b=HnGrztXY1j0Rikaq20jGfJq3u03eQYt5YUkFAW9ShyOpuQKvOfOEGAD4t6wDnbcUTx
         wr1GqAv0jnaJKLBSSL8YZdOjVJPi7PzE0z+m4hG9eEtUTOFQCU/00dyN1lHxTQ8PhLxV
         ibZ0k0jKcwgsMKct4ht5dou1AY8EkUMU2hrDLTZHCJ0sKooFCFASzOM5djVhcA0zkaB6
         WJ12YbaqsCZJRuI4t6j2seH8XTjuZLq3b+fhQZtf5f4bbW1d5SbxFTQRCvhJusTRkc5K
         M0SFnlmhVfOYe9aW6Z9sv5V4tAw3UkGMNN2aR20M2+wt7YaeZArrdXPAqg2GC4nGNwkc
         QJQQ==
X-Google-DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
        d=1e100.net; s=20260707; t=1790965082; x=1791569882;
        h=content-type:to:subject:message-id:date:from:mime-version:x-gm-gg
         :x-gm-message-state:from:to:cc:subject:date:message-id:reply-to
         :content-type;
        bh=vEtbyrqcYS1jlhOX7peoRy488tm8YOpNchHOxPXh9C0=;
        b=SdnhPbAuBZSwyUDPgjTYckQnP05Zz5rDrRLvcELkL4yCPBVUXDXvmyUUAdRMgqsHbO
         c7WRS2pY/PNiz/J3hwv7svJGKh9O3Ig+qVMRfpFvthLgw3xO6OudhIrgVfWhc89gNBKq
         D0Pkt+dZ7mgl41v/n+D1uPXjyBG+UrwhDboIXjnrH5DEPSMlSsYWqsQYMpDqvVYTuJgO
         JdoXnJFaMx841gPi+Xo4KqJf5gUi7BKEQHGhOxLjipSvWFpLuMqm6hMl9NbRgo6rs+Dx
         DEHuaqgFvcjH2QjcIL0rj+czlfnd2vMS8ASHCjYuPNuHByR9ulJiYNzYV1YWadlVLkUF
         3Nig==
X-Gm-Message-State: AFuF++n1E1WdBHhit8mfuDPDtSiQpK7XTooHwgxBOFwAqgZHbKBRNSDb
	Ftzfs9PD70ofHke6c1VRODxbLfn/gw2afEgswGgqwEf/hyNc/j4MKHufjuN0pc3RFHtMHBvkV0M
	qrEwB/zrZ8Qz2rIbcCplgdUfWiXClFlYCugYI5x0=
X-Gm-Gg: AYBFou3P2jIDCmXYqretBAIvm5DFM0tpVHtgaD7V8qlGXEn4QUWw5JwInvxq7JDCBFo
	fjuwJxaIv8qSIClIEknXjmFPoxixPQ9J0D6M1Ki20bxuVDR8aAMJ8yY260RB2g9OntGqon55a/J
	p1kzEXgs30QefWchfZSYt6IYRO4XbcSZuPTZKSNwkWR76Xswv3/8t3pcOyPInfQmhItszl2edGU
	tDqjf20F+5z99o14iiOp2rLn/r1iimxO0npnlNfh88H2T8gtB4q7S3lkOzN2a5bRcLeyRdnWI2K
	rnBHRyDIcm5J30eg7TsB47iz8i4pgCBhofxWlTb6bR01pqXIkoX+uPBXmmChnqWy54I=
X-Received: by 2002:a05:7301:787:b0:33c:1f44:81c1 with SMTP id
 5a478bee46e88-34f219a55ffmr3451098eec.37.1790965082033; Fri, 02 Oct 2026
 11:18:02 -0700 (PDT)
MIME-Version: 1.0
From: Gideon Msuya <gideonmsuya146@gmail.com>
Date: Fri, 2 Oct 2026 21:17:50 +0300
X-Gm-Features: AclHuK9RWlaeLr-y-IQiWMQNOulPYeLIZq_UtXIHEGut7q2ROlYowUWJJhD5ILs
Message-ID: <CAFywjrqYGinWPECWDtND6VEbT_969ANYnfoABe9DErhu0TGOTw@mail.gmail.com>
Subject: 
To: info@worldchoiceperfume.com
Content-Type: multipart/alternative; boundary="000000000000851217065cdf8dbb"

--000000000000851217065cdf8dbb
Content-Type: text/plain; charset="UTF-8"

Hello world choice

--000000000000851217065cdf8dbb
Content-Type: text/html; charset="UTF-8"

<div dir="auto">Hello world choice</div>

--000000000000851217065cdf8dbb--', '{"to": "info@worldchoiceperfume.com", "date": "Fri, 2 Oct 2026 21:17:50 +0300", "from": "Gideon Msuya <gideonmsuya146@gmail.com>", "subject": "", "x-gm-gg": "AYBFou3P2jIDCmXYqretBAIvm5DFM0tpVHtgaD7V8qlGXEn4QUWw5JwInvxq7JDCBFo fjuwJxaIv8qSIClIEknXjmFPoxixPQ9J0D6M1Ki20bxuVDR8aAMJ8yY260RB2g9OntGqon55a/J p1kzEXgs30QefWchfZSYt6IYRO4XbcSZuPTZKSNwkWR76Xswv3/8t3pcOyPInfQmhItszl2edGU tDqjf20F+5z99o14iiOp2rLn/r1iimxO0npnlNfh88H2T8gtB4q7S3lkOzN2a5bRcLeyRdnWI2K rnBHRyDIcm5J30eg7TsB47iz8i4pgCBhofxWlTb6bR01pqXIkoX+uPBXmmChnqWy54I=", "arc-seal": "i=1; a=rsa-sha256; t=1790965082; cv=none;", "received": "by mail-dy2-x18.google.com with SMTP id 5a478bee46e88-344447f9c3dso4793256eec.0", "message-id": "<CAFywjrqYGinWPECWDtND6VEbT_969ANYnfoABe9DErhu0TGOTw@mail.gmail.com>", "x-received": "by 2002:a05:7301:787:b0:33c:1f44:81c1 with SMTP id 5a478bee46e88-34f219a55ffmr3451098eec.37.1790965082033; Fri, 02 Oct 2026 11:18:02 -0700 (PDT)", "content-type": "multipart/alternative; boundary=\"000000000000851217065cdf8dbb\"", "mime-version": "1.0", "received-spf": "pass (mx.cloudflare.net: domain of gideonmsuya146@gmail.com designates 2607:f8b0:4864:36::18 as permitted sender) receiver=mx.cloudflare.net; client-ip=2607:f8b0:4864:36::18; envelope-from=\"gideonmsuya146@gmail.com\"; helo=mail-dy2-x18.google.com;", "x-gm-features": "AclHuK9RWlaeLr-y-IQiWMQNOulPYeLIZq_UtXIHEGut7q2ROlYowUWJJhD5ILs", "dkim-signature": "v=1; a=rsa-sha256; c=relaxed/relaxed; d=gmail.com; s=20251104; t=1790965082; x=1791569882; darn=worldchoiceperfume.com; h=content-type:to:subject:message-id:date:from:mime-version:from:to :cc:subject:date:message-id:reply-to:content-type; bh=vEtbyrqcYS1jlhOX7peoRy488tm8YOpNchHOxPXh9C0=; b=HnGrztXY1j0Rikaq20jGfJq3u03eQYt5YUkFAW9ShyOpuQKvOfOEGAD4t6wDnbcUTx wr1GqAv0jnaJKLBSSL8YZdOjVJPi7PzE0z+m4hG9eEtUTOFQCU/00dyN1lHxTQ8PhLxV ibZ0k0jKcwgsMKct4ht5dou1AY8EkUMU2hrDLTZHCJ0sKooFCFASzOM5djVhcA0zkaB6 WJ12YbaqsCZJRuI4t6j2seH8XTjuZLq3b+fhQZtf5f4bbW1d5SbxFTQRCvhJusTRkc5K M0SFnlmhVfOYe9aW6Z9sv5V4tAw3UkGMNN2aR20M2+wt7YaeZArrdXPAqg2GC4nGNwkc QJQQ==", "x-cf-spamh-score": "1 for <info@worldchoiceperfume.com>; Fri, 02 Oct 2026 11:18:02 -0700 (PDT) d=google.com; s=arc-20260327; b=GLOk0krzmOsf9MUtR6O9PI203lx4JYcG2n75h9OetC/M4V94Y5OkNp2wrKkmuFyYar ZiRLtRnu48exAN6SWAeqbEe4X57NJPFQJS5E31mUxRI34Ar0gA2R9EWO1O6reEpjYtXA skSVmNewvZZsGdElOzBZ5pNKE2e+pBS5prNDrouyQg19ysTw7m+jcVxLsM7rDs4+paBP faXsavAoSd5+Q4/8Kkwjc5taY6+733Wioj4LTJIUsStTGlkrEHIHYhTM0J7XsAWYZKft tijP8xlGRiN7HMkfh4boWvzsPFCe44BBh+Y6h9J+lOfI2MG42Gd8wR9ThLX0eSkvOQs1 nwAw== h=to:subject:message-id:date:from:mime-version:dkim-signature; bh=vEtbyrqcYS1jlhOX7peoRy488tm8YOpNchHOxPXh9C0=; fh=nniGTCdcezPCCWQcYQFtt+TqrAdcQ+jBfMQuxKQBEts=; b=Lyjvuwrv7rG9LTZTPOa1V+Y+YIhSx1YP5cw7Rr9sedBOED5DTsl/DF3GDbLj8MaXI5 3T1O6vEUStFGH+VaKi8rlvVcvSn/6+9owPZvyd8z15DZJsXn/o4LNVMVr+i4+Hdy6FZg 7SwJwSTr6OAAKdg9NeeYcH+tWhfi8hLXKnhbGvZCmfQMWTXzedi4ofvTUCr3fvK5sgfr srM6s7GunZMI5HpmAnYIZ0HU5Vr1MVfRksJY/PTlb2ppu2uJPpoNwwLQS2NJTcqm8gAw eBG2xMG7+HVGaZ8hAJign3FMQqXQaJtE0ruajFE8hvK87u1m1k0as/soqjlL+AcqtvNv mpkQ==; darn=worldchoiceperfume.com", "x-gm-message-state": "AFuF++n1E1WdBHhit8mfuDPDtSiQpK7XTooHwgxBOFwAqgZHbKBRNSDb Ftzfs9PD70ofHke6c1VRODxbLfn/gw2afEgswGgqwEf/hyNc/j4MKHufjuN0pc3RFHtMHBvkV0M qrEwB/zrZ8Qz2rIbcCplgdUfWiXClFlYCugYI5x0=", "arc-message-signature": "i=1; a=rsa-sha256; c=relaxed/relaxed; d=google.com; s=arc-20260327;", "authentication-results": "mx.cloudflare.net; dkim=pass header.d=gmail.com header.s=20251104 header.b=HnGrztXY; dmarc=pass header.from=gmail.com policy.dmarc=none; spf=none (mx.cloudflare.net: no SPF records found for postmaster@mail-dy2-x18.google.com) smtp.helo=mail-dy2-x18.google.com; spf=pass (mx.cloudflare.net: domain of gideonmsuya146@gmail.com designates 2607:f8b0:4864:36::18 as permitted sender) smtp.mailfrom=gideonmsuya146@gmail.com; arc=pass smtp.remote-ip=\"2607:f8b0:4864:36::18\"", "x-google-dkim-signature": "v=1; a=rsa-sha256; c=relaxed/relaxed; d=1e100.net; s=20260707; t=1790965082; x=1791569882; h=content-type:to:subject:message-id:date:from:mime-version:x-gm-gg :x-gm-message-state:from:to:cc:subject:date:message-id:reply-to :content-type; bh=vEtbyrqcYS1jlhOX7peoRy488tm8YOpNchHOxPXh9C0=; b=SdnhPbAuBZSwyUDPgjTYckQnP05Zz5rDrRLvcELkL4yCPBVUXDXvmyUUAdRMgqsHbO c7WRS2pY/PNiz/J3hwv7svJGKh9O3Ig+qVMRfpFvthLgw3xO6OudhIrgVfWhc89gNBKq D0Pkt+dZ7mgl41v/n+D1uPXjyBG+UrwhDboIXjnrH5DEPSMlSsYWqsQYMpDqvVYTuJgO JdoXnJFaMx841gPi+Xo4KqJf5gUi7BKEQHGhOxLjipSvWFpLuMqm6hMl9NbRgo6rs+Dx DEHuaqgFvcjH2QjcIL0rj+czlfnd2vMS8ASHCjYuPNuHByR9ulJiYNzYV1YWadlVLkUF 3Nig==", "arc-authentication-results": "i=1; mx.google.com; arc=none"}', NULL, FALSE, 'none', 'pass', TRUE, FALSE, 'new', '<CAFywjrqYGinWPECWDtND6VEbT_969ANYnfoABe9DErhu0TGOTw@mail.gmail.com>', NULL, '2026-10-02T18:17:50+00:00', '2026-10-02T18:19:32+00:00', NULL, '2026-10-02T18:17:50+00:00', '2026-10-02T18:19:32+00:00');

UPDATE public.info_emails SET parent_id = 3 WHERE id = 4;

-- info_email_replies (6 rows)
INSERT INTO public.info_email_replies (id, info_email_id, from_email, to_email, subject, body, message_id, in_reply_to, status, error, sent_by, sent_by_name, sent_at, created_at, attachment_names) VALUES
(2, 8, 'info@worldchoiceperfume.com', 'godwinfranklin419@gmail.com', 'Re: Hey', 'hi we received your email and we are working on it very hard', NULL, '<CAOLv=Vv=AETvbj+7aZu8_3h+4xMxnvpwLRE6a7u8FG4_My5bLA@mail.gmail.com>', 'failed', 'Connection could not be established with host "ssl://smtp.resend.com:465": stream_socket_client(): Unable to connect to ssl://smtp.resend.com:465 (Connection timed out)', 39, 'Gideon Msuya', NULL, '2026-09-28T22:18:17+00:00', NULL),
(7, 8, 'info@worldchoiceperfume.com', 'godwinfranklin419@gmail.com', 'Re: Hey', 'hello', NULL, '<CAOLv=Vv=AETvbj+7aZu8_3h+4xMxnvpwLRE6a7u8FG4_My5bLA@mail.gmail.com>', 'failed', 'Connection could not be established with host "ssl://smtp.resend.com:465": stream_socket_client(): Unable to connect to ssl://smtp.resend.com:465 (Connection timed out)', 39, 'Gideon Msuya', NULL, '2026-09-28T22:45:46+00:00', NULL),
(8, 8, 'info@worldchoiceperfume.com', 'godwinfranklin419@gmail.com', 'Re: Hey', 'HELLO', '<ea86a1e9da2ad839561467d782bad359@worldchoiceperfume.com>', '<CAOLv=Vv=AETvbj+7aZu8_3h+4xMxnvpwLRE6a7u8FG4_My5bLA@mail.gmail.com>', 'sent', NULL, 39, 'Gideon Msuya', '2026-09-29T08:45:29+00:00', '2026-09-29T08:45:29+00:00', NULL),
(9, 4, 'info@worldchoiceperfume.com', 'godwinfranklin419@gmail.com', 'Re: Fwd: Greetings', 'read it', '<7d406e5180197f82ad4193e33ab1b85f@worldchoiceperfume.com>', '<CAOLv=VuoxRU5h4cT=EHv0XVxM9CrMa9xiaC0FVpDH-Y7Sm5Vyg@mail.gmail.com>', 'sent', NULL, 39, 'Gideon Msuya', '2026-09-29T09:43:02+00:00', '2026-09-29T09:43:02+00:00', NULL),
(10, 3, 'info@worldchoiceperfume.com', 'godwinfranklin419@gmail.com', 'Re: Fwd: Greetings', 'hello', '<289f0888c7d331356b18afb3336ee1cf@worldchoiceperfume.com>', '<CAOLv=VuAD0xOd6WytfO=n4SZhm-BjxkkGmyG-73yG3xRAFn2ww@mail.gmail.com>', 'sent', NULL, 39, 'Gideon Msuya', '2026-09-29T13:37:27+00:00', '2026-09-29T13:37:27+00:00', NULL),
(11, 14, 'info@worldchoiceperfume.com', 'godwinfranklin419@gmail.com', 'Re: Your message', 'iul76iyutrfdgfhkl;iouilyktg', '<292718103a2a311ad8e86789e693cff1@worldchoiceperfume.com>', '<CAOLv=Vsm_70EnfFgg+kE2gW1P=0XsXptv0agyPYhDaShG=j=UA@mail.gmail.com>', 'sent', NULL, 39, 'Gideon Msuya', '2026-09-29T21:31:57+00:00', '2026-09-29T21:31:57+00:00', NULL);

-- info_email_attachments (2 rows)
INSERT INTO public.info_email_attachments (id, info_email_id, file_name, mime_type, size_bytes, storage_path, created_at) VALUES
(1, 6, 'Chrome', 'video/mp4', 18145671, 'mail-6/0-Chrome', '2026-09-28T20:40:41+00:00'),
(4, 8, 'om_js_content.txt', 'text/plain', 39741, 'mail-8/0-om_js_content.txt', '2026-09-28T21:23:00+00:00');

-- ----------------------------------------------------------------------------
-- Reset identity sequences so the next insert gets a free id.
-- ----------------------------------------------------------------------------
SELECT setval(pg_get_serial_sequence('public.branches', 'id'), COALESCE((SELECT MAX(id) FROM public.branches), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.users', 'id'), COALESCE((SELECT MAX(id) FROM public.users), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.jobs', 'id'), COALESCE((SELECT MAX(id) FROM public.jobs), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.failed_jobs', 'id'), COALESCE((SELECT MAX(id) FROM public.failed_jobs), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.products', 'id'), COALESCE((SELECT MAX(id) FROM public.products), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.product_images', 'id'), COALESCE((SELECT MAX(id) FROM public.product_images), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.brands', 'id'), COALESCE((SELECT MAX(id) FROM public.brands), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.customers', 'id'), COALESCE((SELECT MAX(id) FROM public.customers), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.bottle_stock', 'id'), COALESCE((SELECT MAX(id) FROM public.bottle_stock), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.bottle_stock_movements', 'id'), COALESCE((SELECT MAX(id) FROM public.bottle_stock_movements), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.bottle_accessories', 'id'), COALESCE((SELECT MAX(id) FROM public.bottle_accessories), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.bottle_accessories_movements', 'id'), COALESCE((SELECT MAX(id) FROM public.bottle_accessories_movements), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.oil_fragrance_stock', 'id'), COALESCE((SELECT MAX(id) FROM public.oil_fragrance_stock), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.oil_fragrance_movements', 'id'), COALESCE((SELECT MAX(id) FROM public.oil_fragrance_movements), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.branch_stock', 'id'), COALESCE((SELECT MAX(id) FROM public.branch_stock), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.branch_stock_varieties', 'id'), COALESCE((SELECT MAX(id) FROM public.branch_stock_varieties), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.stock_movements', 'id'), COALESCE((SELECT MAX(id) FROM public.stock_movements), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.sales', 'id'), COALESCE((SELECT MAX(id) FROM public.sales), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.sale_items', 'id'), COALESCE((SELECT MAX(id) FROM public.sale_items), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.orders', 'id'), COALESCE((SELECT MAX(id) FROM public.orders), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.order_items', 'id'), COALESCE((SELECT MAX(id) FROM public.order_items), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.order_notes', 'id'), COALESCE((SELECT MAX(id) FROM public.order_notes), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.expenses', 'id'), COALESCE((SELECT MAX(id) FROM public.expenses), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.cashier_accounts', 'id'), COALESCE((SELECT MAX(id) FROM public.cashier_accounts), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.discrepancies', 'id'), COALESCE((SELECT MAX(id) FROM public.discrepancies), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.otp_records', 'id'), COALESCE((SELECT MAX(id) FROM public.otp_records), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.audit_logs', 'id'), COALESCE((SELECT MAX(id) FROM public.audit_logs), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.notifications', 'id'), COALESCE((SELECT MAX(id) FROM public.notifications), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.admin_notifications', 'id'), COALESCE((SELECT MAX(id) FROM public.admin_notifications), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.inquiries', 'id'), COALESCE((SELECT MAX(id) FROM public.inquiries), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.news_posts', 'id'), COALESCE((SELECT MAX(id) FROM public.news_posts), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.stock_transfers', 'id'), COALESCE((SELECT MAX(id) FROM public.stock_transfers), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.stock_transfer_items', 'id'), COALESCE((SELECT MAX(id) FROM public.stock_transfer_items), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.info_emails', 'id'), COALESCE((SELECT MAX(id) FROM public.info_emails), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.info_email_replies', 'id'), COALESCE((SELECT MAX(id) FROM public.info_email_replies), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.info_email_attachments', 'id'), COALESCE((SELECT MAX(id) FROM public.info_email_attachments), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.migrations', 'id'), COALESCE((SELECT MAX(id) FROM public.migrations), 0) + 1, false);

COMMIT;

NOTIFY pgrst, 'reload schema';
