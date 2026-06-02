-- Reservation & serving fields for restaurant tables
-- Run: mysql -u root POS < database/migration_table_serve_reserve.sql

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'restaurant_tables' AND COLUMN_NAME = 'reserved_by');
SET @sql = IF(@col = 0, 'ALTER TABLE `restaurant_tables` ADD COLUMN `reserved_by` varchar(100) DEFAULT NULL AFTER `status`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'restaurant_tables' AND COLUMN_NAME = 'reserved_at');
SET @sql = IF(@col = 0, 'ALTER TABLE `restaurant_tables` ADD COLUMN `reserved_at` timestamp NULL DEFAULT NULL AFTER `reserved_by`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'restaurant_tables' AND COLUMN_NAME = 'serving_user_id');
SET @sql = IF(@col = 0, 'ALTER TABLE `restaurant_tables` ADD COLUMN `serving_user_id` int(11) DEFAULT NULL AFTER `reserved_at`, ADD KEY `serving_user_id` (`serving_user_id`)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'restaurant_tables' AND COLUMN_NAME = 'serve_status');
SET @sql = IF(@col = 0, "ALTER TABLE `restaurant_tables` ADD COLUMN `serve_status` enum('none','serving','ready') NOT NULL DEFAULT 'none' AFTER `serving_user_id`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'restaurant_tables' AND CONSTRAINT_NAME = 'restaurant_tables_serving_user_fk');
SET @sql = IF(@fk = 0, 'ALTER TABLE `restaurant_tables` ADD CONSTRAINT `restaurant_tables_serving_user_fk` FOREIGN KEY (`serving_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
