CREATE DATABASE IF NOT EXISTS `priest_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `priest_db`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('administrator','manager','tailor','customer') NOT NULL DEFAULT 'manager',
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` VARCHAR(30) NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `gender` ENUM('Male','Female','Other') DEFAULT NULL,
  `phone` VARCHAR(40) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `address` TEXT,
  `notes` TEXT,
  `photo_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customers_customer_id` (`customer_id`),
  UNIQUE KEY `uq_customers_user_id` (`user_id`),
  KEY `idx_customer_name` (`full_name`),
  KEY `idx_customer_phone` (`phone`),
  CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `measurements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NOT NULL,
  `measurement_date` DATE NOT NULL,
  `clothing_type` VARCHAR(80) NOT NULL,
  `unit` ENUM('cm','inch') NOT NULL DEFAULT 'cm',
  `notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_measurements_customer` (`customer_id`),
  CONSTRAINT `fk_measurements_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `measurement_details` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `measurement_id` INT UNSIGNED NOT NULL,
  `field_name` VARCHAR(80) NOT NULL,
  `value` DECIMAL(8,2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_measurement_details_measurement` (`measurement_id`),
  CONSTRAINT `fk_meas_detail_measurement` FOREIGN KEY (`measurement_id`) REFERENCES `measurements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `designs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `design_code` VARCHAR(40) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `category` ENUM('Men\'s wear','Women\'s wear','Children\'s wear','Traditional wear','Suits','Dresses','Shirts','Trousers','Skirts','Wedding outfits','Uniforms','Other custom designs') NOT NULL,
  `estimated_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_design_code` (`design_code`),
  KEY `idx_designs_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(40) NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `design_id` INT UNSIGNED DEFAULT NULL,
  `fabric_info` VARCHAR(200) DEFAULT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `order_date` DATE NOT NULL,
  `expected_completion_date` DATE DEFAULT NULL,
  `status` ENUM('Pending','Cutting','Sewing','Fitting','Alteration','Completed','Ready for collection','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `special_instructions` TEXT,
  `assigned_tailor` VARCHAR(120) DEFAULT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_order_number` (`order_number`),
  KEY `idx_orders_customer` (`customer_id`),
  KEY `idx_orders_design` (`design_id`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_orders_design` FOREIGN KEY (`design_id`) REFERENCES `designs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `description` VARCHAR(200) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED DEFAULT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_date` DATE NOT NULL,
  `payment_method` ENUM('Cash','Mobile Money','Bank Transfer','Card','Other') NOT NULL,
  `reference_number` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` INT UNSIGNED DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_customer` (`customer_id`),
  KEY `idx_payments_order` (`order_id`),
  KEY `idx_payments_status` (`status`),
  CONSTRAINT `fk_payments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_payments_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `expenses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `expense_date` DATE NOT NULL,
  `description` VARCHAR(200) NOT NULL,
  `category` VARCHAR(80) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `deliveries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `delivery_date` DATE NOT NULL,
  `status` ENUM('Ready for collection','Delivered','Undelivered') NOT NULL DEFAULT 'Ready for collection',
  `recipient_name` VARCHAR(120) DEFAULT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_deliveries_order` (`order_id`),
  CONSTRAINT `fk_deliveries_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('administrator','manager','tailor','customer') NOT NULL DEFAULT 'manager',
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employees_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `status`)
VALUES
('System Administrator', 'admin@priest.local', '$2y$10$cUYyW8kn3ZHwW64pU1GrGOX5j82mle1YS.KqI8tVXa382q1E.d016', 'administrator', 'active'),
('Customer Test User', 'customer@priest.local', '$2y$10$R0QdO961Q6oBmRrfLyx6s.u3PWq8p.d2qRgiV.8uNUenYKiwToMtG', 'customer', 'active')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`), `password_hash` = VALUES(`password_hash`), `role` = VALUES(`role`), `status` = VALUES(`status`);

INSERT INTO `business_settings` (`business_name`, `business_address`, `contact_phone`, `contact_email`, `currency`, `measurement_unit`, `receipt_info`)
VALUES
('PRIEST Atelier', 'Accra, Ghana', '+233 00 000 0000', 'support@priest.local', 'GHS', 'cm', 'Thank you for choosing PRIEST Atelier.')
ON DUPLICATE KEY UPDATE `business_name` = VALUES(`business_name`), `business_address` = VALUES(`business_address`), `contact_phone` = VALUES(`contact_phone`), `contact_email` = VALUES(`contact_email`), `currency` = VALUES(`currency`), `measurement_unit` = VALUES(`measurement_unit`), `receipt_info` = VALUES(`receipt_info`);