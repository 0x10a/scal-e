-- =============================================================
--  Scal-e CDP — Seed Data
--  Run on a fresh database AFTER applying schema.sql:
--    mysql -u root -p scal_e_db < database/seeds/seed.sql
-- =============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- -------------------------------------------------------------
--  Customers
-- -------------------------------------------------------------
INSERT INTO `customers` (`id`, `email`, `name`, `created_at`, `updated_at`) VALUES
(1, 'alice@example.com',   'Alice Martin',   '2026-01-15 10:00:00', '2026-01-15 10:00:00'),
(2, 'bob@example.com',     'Bob Johnson',    '2026-02-01 09:30:00', '2026-02-01 09:30:00'),
(3, 'charlie@example.com', 'Charlie Brown',  '2026-02-10 14:20:00', '2026-02-10 14:20:00'),
(4, 'diana@example.com',   'Diana Prince',   '2026-03-05 11:00:00', '2026-03-05 11:00:00'),
(5, 'eve@example.com',     'Eve Adams',      '2026-04-01 16:45:00', '2026-04-01 16:45:00');

-- -------------------------------------------------------------
--  Events
-- -------------------------------------------------------------
INSERT INTO `events` (`id`, `customer_id`, `event_type`, `event_hash`, `occurred_at`, `created_at`) VALUES
-- Alice: two high-value purchases + a page view
(1,  1, 'purchase',  'aaaaaa0000000000000000000000000000000001', '2026-01-20 10:00:00', NOW()),
(2,  1, 'purchase',  'aaaaaa0000000000000000000000000000000002', '2026-02-05 14:30:00', NOW()),
(3,  1, 'page_view', 'aaaaaa0000000000000000000000000000000003', '2026-02-10 09:00:00', NOW()),
-- Bob: one low-value purchase + signup
(4,  2, 'purchase',  'bbbbbb0000000000000000000000000000000001', '2026-02-15 11:00:00', NOW()),
(5,  2, 'signup',    'bbbbbb0000000000000000000000000000000002', '2026-02-01 09:30:00', NOW()),
-- Charlie: one high-value + one low-value purchase
(6,  3, 'purchase',  'cccccc0000000000000000000000000000000001', '2026-02-12 16:00:00', NOW()),
(7,  3, 'purchase',  'cccccc0000000000000000000000000000000002', '2026-03-01 10:00:00', NOW()),
-- Diana: signup only
(8,  4, 'signup',    'dddddd0000000000000000000000000000000001', '2026-03-05 11:00:00', NOW()),
-- Eve: newsletter subscription
(9,  5, 'subscribe', 'eeeeee0000000000000000000000000000000001', '2026-04-01 16:45:00', NOW());

-- -------------------------------------------------------------
--  Event Properties
--  property_numeric is populated for numeric values (amount)
--  to enable indexed range queries in the segmentation engine.
-- -------------------------------------------------------------
INSERT INTO `event_properties` (`event_id`, `property_key`, `property_value`, `property_numeric`) VALUES
-- Event 1: Alice — purchase Shoes 150
(1, 'amount',  '150',   150.0000),
(1, 'product', 'Shoes', NULL),

-- Event 2: Alice — purchase Jacket 200
(2, 'amount',  '200',    200.0000),
(2, 'product', 'Jacket', NULL),

-- Event 3: Alice — page view
(3, 'page', '/products', NULL),

-- Event 4: Bob — purchase T-Shirt 80
(4, 'amount',  '80',       80.0000),
(4, 'product', 'T-Shirt',  NULL),

-- Event 5: Bob — signup via email
(5, 'source', 'email', NULL),

-- Event 6: Charlie — purchase Coat 320
(6, 'amount',  '320',  320.0000),
(6, 'product', 'Coat', NULL),

-- Event 7: Charlie — purchase Socks 45
(7, 'amount',  '45',    45.0000),
(7, 'product', 'Socks', NULL),

-- Event 8: Diana — signup via google
(8, 'source', 'google', NULL),

-- Event 9: Eve — newsletter subscribe
(9, 'list', 'weekly-digest', NULL);

SET foreign_key_checks = 1;

-- =============================================================
--  Verification queries
-- =============================================================
-- SELECT COUNT(*) FROM customers;     -- expects 5
-- SELECT COUNT(*) FROM events;        -- expects 9
-- SELECT COUNT(*) FROM event_properties; -- expects 15
--
-- Segment query: customers with a purchase > 100
-- SELECT DISTINCT c.id, c.email, c.name FROM customers c
-- WHERE EXISTS (
--     SELECT 1 FROM events e
--     JOIN event_properties ep ON ep.event_id = e.id AND ep.property_key = 'amount'
--     WHERE e.customer_id = c.id AND e.event_type = 'purchase' AND ep.property_numeric > 100
-- );
-- Expects: Alice (id=1), Charlie (id=3)
