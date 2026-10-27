-- =============================================================================
-- İstifadəçi sifarişləri – Tour Builder-dan saxlanılan turlar
-- =============================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `user_orders` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL,
  `name` varchar(200) DEFAULT NULL COMMENT 'Sifariş adı / qeyd',
  `order_data` longtext NOT NULL COMMENT 'JSON: tour builder form state',
  `total_amount` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_orders_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
