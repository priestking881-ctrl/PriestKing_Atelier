-- Ensure the customer role exists on the users table.
ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('administrator','manager','tailor','customer') NOT NULL DEFAULT 'manager';

-- Ensure customers.user_id exists and is linked to users.id.
SELECT COUNT(*) INTO @customers_user_id_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'customers'
  AND COLUMN_NAME = 'user_id';

SET @customers_user_id_sql = IF(@customers_user_id_exists = 0,
  'ALTER TABLE `customers` ADD COLUMN `user_id` INT UNSIGNED DEFAULT NULL AFTER `customer_id`',
  'SELECT 1');
PREPARE customers_user_id_stmt FROM @customers_user_id_sql;
EXECUTE customers_user_id_stmt;
DEALLOCATE PREPARE customers_user_id_stmt;

SELECT COUNT(*) INTO @customers_user_id_index_exists
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'customers'
  AND INDEX_NAME = 'uq_customers_user_id';

SET @customers_user_id_index_sql = IF(@customers_user_id_index_exists = 0,
  'ALTER TABLE `customers` ADD UNIQUE KEY `uq_customers_user_id` (`user_id`)',
  'SELECT 1');
PREPARE customers_user_id_index_stmt FROM @customers_user_id_index_sql;
EXECUTE customers_user_id_index_stmt;
DEALLOCATE PREPARE customers_user_id_index_stmt;

SELECT COUNT(*) INTO @customers_user_fk_exists
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND CONSTRAINT_NAME = 'fk_customers_user';

SET @customers_user_fk_sql = IF(@customers_user_fk_exists = 0,
  'ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL',
  'SELECT 1');
PREPARE customers_user_fk_stmt FROM @customers_user_fk_sql;
EXECUTE customers_user_fk_stmt;
DEALLOCATE PREPARE customers_user_fk_stmt;

-- Ensure the payment approval fields exist.
SELECT COUNT(*) INTO @payments_status_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'payments'
  AND COLUMN_NAME = 'status';

SET @payments_status_sql = IF(@payments_status_exists = 0,
  'ALTER TABLE `payments` ADD COLUMN `status` ENUM(\'pending\',\'approved\',\'rejected\') NOT NULL DEFAULT \'pending\' AFTER `notes`',
  'SELECT 1');
PREPARE payments_status_stmt FROM @payments_status_sql;
EXECUTE payments_status_stmt;
DEALLOCATE PREPARE payments_status_stmt;

SELECT COUNT(*) INTO @payments_approved_by_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'payments'
  AND COLUMN_NAME = 'approved_by';

SET @payments_approved_by_sql = IF(@payments_approved_by_exists = 0,
  'ALTER TABLE `payments` ADD COLUMN `approved_by` INT UNSIGNED DEFAULT NULL AFTER `status`',
  'SELECT 1');
PREPARE payments_approved_by_stmt FROM @payments_approved_by_sql;
EXECUTE payments_approved_by_stmt;
DEALLOCATE PREPARE payments_approved_by_stmt;

SELECT COUNT(*) INTO @payments_approved_at_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'payments'
  AND COLUMN_NAME = 'approved_at';

SET @payments_approved_at_sql = IF(@payments_approved_at_exists = 0,
  'ALTER TABLE `payments` ADD COLUMN `approved_at` TIMESTAMP NULL DEFAULT NULL AFTER `approved_by`',
  'SELECT 1');
PREPARE payments_approved_at_stmt FROM @payments_approved_at_sql;
EXECUTE payments_approved_at_stmt;
DEALLOCATE PREPARE payments_approved_at_stmt;

SELECT COUNT(*) INTO @payments_status_index_exists
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'payments'
  AND INDEX_NAME = 'idx_payments_status';

SET @payments_status_index_sql = IF(@payments_status_index_exists = 0,
  'ALTER TABLE `payments` ADD KEY `idx_payments_status` (`status`)',
  'SELECT 1');
PREPARE payments_status_index_stmt FROM @payments_status_index_sql;
EXECUTE payments_status_index_stmt;
DEALLOCATE PREPARE payments_status_index_stmt;

SELECT COUNT(*) INTO @payments_approved_fk_exists
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND CONSTRAINT_NAME = 'fk_payments_approved_by';

SET @payments_approved_fk_sql = IF(@payments_approved_fk_exists = 0,
  'ALTER TABLE `payments` ADD CONSTRAINT `fk_payments_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL',
  'SELECT 1');
PREPARE payments_approved_fk_stmt FROM @payments_approved_fk_sql;
EXECUTE payments_approved_fk_stmt;
DEALLOCATE PREPARE payments_approved_fk_stmt;
