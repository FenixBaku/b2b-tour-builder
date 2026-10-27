-- Transfer Service növləri (Transfers Excel: 1 way Transfer, 2 ways Transfer, Day Trip, Private Tour, Overnight və s.)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `tour_transfer_services` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
