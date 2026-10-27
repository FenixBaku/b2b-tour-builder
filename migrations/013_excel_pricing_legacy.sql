-- =============================================================================
-- LEGACY: MariaDB 10.4 (ADD COLUMN IF NOT EXISTS yoxdursa) - Tək-tək run edin
-- =============================================================================
-- Bu faylı 013_excel_pricing_structure.sql xəta verdikdə istifadə edin.
-- Hər blokdan YALNIZ BİR DƏFƏ run edin. "Duplicate column" = həmin sütun artıq var.
-- =============================================================================

SET NAMES utf8mb4;

-- 1. tour_transfer_prices cədvəli (əgər yoxdursa)
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

-- 2. Transfer Excel sütunları - hər birini ayrıca run edin (yalnız bir dəfə)
-- ALTER TABLE `tour_transfer_prices` ADD COLUMN `duty_code` varchar(120) DEFAULT NULL AFTER `region`;
-- ALTER TABLE `tour_transfer_prices` ADD COLUMN `location_type` varchar(50) DEFAULT NULL AFTER `duty_code`;
-- ALTER TABLE `tour_transfer_prices` ADD COLUMN `route_description` varchar(500) DEFAULT NULL AFTER `point_b`;
-- ALTER TABLE `tour_transfer_prices` ADD COLUMN `distance_km` decimal(10,2) DEFAULT NULL AFTER `service`;
-- ALTER TABLE `tour_transfer_prices` ADD COLUMN `start_time` varchar(20) DEFAULT NULL AFTER `distance_km`;
-- ALTER TABLE `tour_transfer_prices` ADD COLUMN `duration_min` int(11) DEFAULT NULL AFTER `start_time`;
-- ALTER TABLE `tour_transfer_prices` ADD COLUMN `day_schedule` text DEFAULT NULL AFTER `duration_min`;
-- ALTER TABLE `tour_transfer_prices` ADD COLUMN `valid_from` date DEFAULT NULL AFTER `day_schedule`;
-- ALTER TABLE `tour_transfer_prices` ADD COLUMN `valid_to` date DEFAULT NULL AFTER `valid_from`;

-- 3. Hotel prices əlavə sütunlar
-- ALTER TABLE `tour_hotel_prices` ADD COLUMN `valid_from` date DEFAULT NULL AFTER `price`;
-- ALTER TABLE `tour_hotel_prices` ADD COLUMN `valid_to` date DEFAULT NULL AFTER `valid_from`;
-- ALTER TABLE `tour_hotel_prices` ADD COLUMN `meal_plan` varchar(20) DEFAULT NULL AFTER `valid_to`;

-- 4. Activities əlavə sütunlar
-- ALTER TABLE `tour_azerbaijan_activities_2026` ADD COLUMN `valid_from` date DEFAULT NULL AFTER `child_price_6_12`;
-- ALTER TABLE `tour_azerbaijan_activities_2026` ADD COLUMN `valid_to` date DEFAULT NULL;
