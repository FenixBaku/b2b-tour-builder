-- Destinations Azerbaijan Transfers Excel-dan transfer fiyatları
-- Hər A, B, Service, Vehicle kombinasiyası üçün qiymət

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `tour_transfer_prices` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `region` varchar(50) NOT NULL DEFAULT 'baku',
  `point_a` varchar(255) NOT NULL,
  `point_b` varchar(255) NOT NULL,
  `service` varchar(255) NOT NULL,
  `vehicle` varchar(120) NOT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `route_vehicle` (`region`, `point_a`(100), `point_b`(100), `service`(100), `vehicle`(80)),
  KEY `region` (`region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;