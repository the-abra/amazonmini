-- ==============================================================================
-- Modular PHP E-Commerce & Cargo Tracking Platform
-- Database Schema for MySQL 5.7+ / MariaDB 10.4+ / InfinityFree Hosting
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `stripe_events`;
DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `shipment_events`;
DROP TABLE IF EXISTS `shipments`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `wishlist_items`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `addresses`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('customer', 'admin', 'courier') NOT NULL DEFAULT 'customer',
    `phone` VARCHAR(30) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Addresses Table (Multi-address book per user)
CREATE TABLE `addresses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(50) NOT NULL DEFAULT 'Home',
    `recipient_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `address_line` TEXT NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `postal_code` VARCHAR(20) NOT NULL,
    `country` VARCHAR(50) NOT NULL DEFAULT 'United States',
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_address_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Categories Table
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `icon_name` VARCHAR(50) NOT NULL DEFAULT 'folder'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Products Table
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `sku` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) NOT NULL UNIQUE,
    `description` TEXT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `image_url` TEXT NOT NULL,
    `specs_json` TEXT NULL,
    `rating_avg` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    `review_count` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_products_listing` (`is_active`, `category_id`),
    CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Reviews Table
CREATE TABLE `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `rating` TINYINT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Cart Items Table
CREATE TABLE `cart_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `user_product_unique` (`user_id`, `product_id`),
    CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Wishlist Items Table
CREATE TABLE `wishlist_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_wishlist_unique` (`user_id`, `product_id`),
    CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Orders Table
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `address_snapshot_json` TEXT NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    `shipping_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    `stripe_session_id` VARCHAR(255) NULL,
    `stripe_payment_intent` VARCHAR(255) NULL,
    `order_status` ENUM('placed', 'packed', 'shipped', 'delivered', 'cancelled', 'returned') NOT NULL DEFAULT 'placed',
    `cancel_reason` VARCHAR(255) NULL,
    `cancelled_at` DATETIME NULL,
    `refund_id` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_orders_user` (`user_id`),
    INDEX `idx_orders_status` (`order_status`, `payment_status`),
    INDEX `idx_orders_session` (`stripe_session_id`),
    CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Order Items Table
CREATE TABLE `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name` VARCHAR(200) NOT NULL,
    `sku` VARCHAR(50) NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `quantity` INT NOT NULL,
    `line_total` DECIMAL(10,2) NOT NULL,
    CONSTRAINT `fk_order_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Shipments Table (Logistics Core)
CREATE TABLE `shipments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL UNIQUE,
    `tracking_number` VARCHAR(60) NOT NULL UNIQUE,
    `carrier_name` VARCHAR(100) NOT NULL DEFAULT 'Amazon-Logistics-Express',
    `assigned_courier_id` INT NULL,
    `current_status` ENUM('awaiting_pickup', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'delivery_failed', 'returned_to_sender', 'cancelled') NOT NULL DEFAULT 'awaiting_pickup',
    `estimated_delivery` DATE NULL,
    `delivery_attempts` TINYINT NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_shipment_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_shipment_courier` FOREIGN KEY (`assigned_courier_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Shipment Events Table (Timeline Audit History)
CREATE TABLE `shipment_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `shipment_id` INT NOT NULL,
    `status_step` ENUM('awaiting_pickup', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'delivery_failed', 'returned_to_sender', 'cancelled') NOT NULL,
    `location` VARCHAR(150) NOT NULL,
    `reason_code` VARCHAR(40) NULL,
    `notes` TEXT NOT NULL,
    `updated_by_user_id` INT NULL,
    `event_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_event_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_event_user` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Stock Movements (inventory audit ledger)
CREATE TABLE `stock_movements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `order_id` INT NULL,
    `delta` INT NOT NULL,
    `reason` VARCHAR(60) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_stock_product` (`product_id`),
    CONSTRAINT `fk_stock_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Stripe Webhook Events (idempotency + audit log)
CREATE TABLE `stripe_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` VARCHAR(100) NOT NULL UNIQUE,
    `event_type` VARCHAR(100) NOT NULL,
    `order_id` INT NULL,
    `source` ENUM('stripe', 'simulated') NOT NULL DEFAULT 'stripe',
    `outcome` VARCHAR(255) NOT NULL DEFAULT 'received',
    `payload` MEDIUMTEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Login Attempts (brute-force throttling)
CREATE TABLE `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(150) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_login_lookup` (`email`, `ip_address`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- CTF Scoreboard Tables
-- Tracks participant progress and flag submissions

CREATE TABLE IF NOT EXISTS `ctf_participants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `display_name` VARCHAR(150) NOT NULL,
    `session_token` VARCHAR(64) NOT NULL UNIQUE,
    `ip_address` VARCHAR(45),
    `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_session` (`session_token`),
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ctf_flags` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `flag_key` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Internal identifier',
    `flag_value` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Actual flag string',
    `vulnerability_name` VARCHAR(100) NOT NULL,
    `difficulty` ENUM('easy', 'medium', 'hard', 'critical') NOT NULL,
    `points` INT UNSIGNED NOT NULL,
    `hint` TEXT,
    `category` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ctf_submissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `participant_id` INT UNSIGNED NOT NULL,
    `flag_id` INT UNSIGNED NOT NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ip_address` VARCHAR(45),
    FOREIGN KEY (`participant_id`) REFERENCES `ctf_participants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`flag_id`) REFERENCES `ctf_flags`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_submission` (`participant_id`, `flag_id`),
    INDEX `idx_participant` (`participant_id`),
    INDEX `idx_submitted` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert CTF Flags
INSERT INTO `ctf_flags` (`flag_key`, `flag_value`, `vulnerability_name`, `difficulty`, `points`, `hint`, `category`) VALUES
('login_sqli', 'CTF{l0g1n_byp4ss_sql1_m4g1c}', 'SQL Injection - Authentication Bypass', 'critical', 500, 'Check for hidden POST parameters in the login form. Try fuzzing with common debug/test parameter names.', 'Injection'),
('tracking_sqli', 'CTF{tr4ck1ng_1nj3ct10n_m4st3r}', 'SQL Injection - Data Extraction', 'hard', 300, 'The tracking code parameter is vulnerable. Try UNION-based SQL injection.', 'Injection'),
('xss_review', 'CTF{xss_r3v13w_pwn3d_2024}', 'Stored XSS in Product Reviews', 'medium', 200, 'Product reviews are not properly escaped. Submit a review with HTML/JavaScript.', 'XSS'),
('idor_user', 'CTF{1d0r_us3r_3num3r4t10n_FTW}', 'IDOR - User Enumeration', 'medium', 200, 'Look for hidden API endpoints. Check /api/* routes and try incrementing IDs.', 'Broken Access Control'),
('lfi_logs', 'CTF{p4th_tr4v3rs4l_f1l3_r34d_pwn}', 'Path Traversal / Local File Inclusion', 'hard', 350, 'Find the logs viewing endpoint. Use directory traversal (../) to read config files.', 'Path Traversal');
