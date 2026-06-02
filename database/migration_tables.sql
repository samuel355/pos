-- Table management for POS
-- Run once: mysql -u root POS < database/migration_tables.sql

CREATE TABLE IF NOT EXISTS `restaurant_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 4,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed default tables if empty
INSERT INTO `restaurant_tables` (`name`, `capacity`, `status`)
SELECT CONCAT('Table ', n), 4, 'Active'
FROM (
  SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
  UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
  UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
  UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
) AS nums
WHERE NOT EXISTS (SELECT 1 FROM `restaurant_tables` LIMIT 1);

-- Link sales to tables (nullable for walk-in orders)
SET @col_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'sales'
    AND COLUMN_NAME = 'table_id'
);

SET @sql = IF(
  @col_exists = 0,
  'ALTER TABLE `sales` ADD COLUMN `table_id` int(11) DEFAULT NULL AFTER `user_id`, ADD KEY `table_id` (`table_id`), ADD CONSTRAINT `sales_table_fk` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
