-- =============================================================================
-- Excel Pricing Structure - Bütün 3 Excel fayl üçün DB strukturunu hazırlayır
-- =============================================================================
-- 1. Destinations Azerbaijan Transfers PVT 2026_31.12.2025.xlsx  -> tour_transfer_prices
-- 2. Destinations Azerbaijan Activities 2026_updated 31.12.2025.xlsx -> tour_azerbaijan_activities_2026
-- 3. Sembark Hotel Listing _Revise 10.12.2025 (1).xlsx -> tour_hotel_prices
--
-- Bu fayl run olunduqdan SONRA insert/mapping skriptlərini işlədə bilərsiniz.
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. TOUR_TRANSFER_PRICES - Transfer Excel üçün
--    (bb_tour_builder.sql dump-da bu cədvəl YOXDUR - yaradılmalıdır)
-- -----------------------------------------------------------------------------

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

-- Transfer Excel əlavə sütunları (A=Duty Code, B=Airport/Hotel, C=City, D=Service, E=Distance, ...)
-- MariaDB 10.5.2+ və MySQL 8.0.12+ ADD COLUMN IF NOT EXISTS dəstəkləyir.
-- Əgər "Duplicate column" xətası alarsanız, hər ALTER-i tək-tək comment-dən çıxarıb run edin.
ALTER TABLE `tour_transfer_prices`
  ADD COLUMN IF NOT EXISTS `duty_code` varchar(120) DEFAULT NULL AFTER `region`,
  ADD COLUMN IF NOT EXISTS `location_type` varchar(50) DEFAULT NULL COMMENT 'Airport/Hotel (Excel B)' AFTER `duty_code`,
  ADD COLUMN IF NOT EXISTS `route_description` varchar(500) DEFAULT NULL COMMENT 'Pick-up From/To (Excel I)' AFTER `point_b`,
  ADD COLUMN IF NOT EXISTS `distance_km` decimal(10,2) DEFAULT NULL AFTER `service`,
  ADD COLUMN IF NOT EXISTS `start_time` varchar(20) DEFAULT NULL AFTER `distance_km`,
  ADD COLUMN IF NOT EXISTS `duration_min` int(11) DEFAULT NULL AFTER `start_time`,
  ADD COLUMN IF NOT EXISTS `day_schedule` text DEFAULT NULL COMMENT 'Excel H - uzun mətn ola bilər' AFTER `duration_min`,
  ADD COLUMN IF NOT EXISTS `valid_from` date DEFAULT NULL COMMENT '1 Jan 2026' AFTER `day_schedule`,
  ADD COLUMN IF NOT EXISTS `valid_to` date DEFAULT NULL COMMENT '31 Dec 2026' AFTER `valid_from`;

-- -----------------------------------------------------------------------------
-- 2. TOUR_HOTEL_PRICES - Hotel Excel üçün əlavə sütunlar
--    (Sembark Hotel Listing: Peak/Low Season, otaq tipləri, tarix aralığı)
-- -----------------------------------------------------------------------------

-- Hotel Excel-də validity tarixləri varsa (məs: 1 Jan 2026 - 31 Dec 2026)
ALTER TABLE `tour_hotel_prices`
  ADD COLUMN IF NOT EXISTS `valid_from` date DEFAULT NULL COMMENT 'Excel: 1 Jan 2026' AFTER `price`,
  ADD COLUMN IF NOT EXISTS `valid_to` date DEFAULT NULL COMMENT 'Excel: 31 Dec 2026' AFTER `valid_from`,
  ADD COLUMN IF NOT EXISTS `meal_plan` varchar(20) DEFAULT NULL COMMENT 'BB/HB/FB/AI' AFTER `valid_to`;

-- Əgər IF NOT EXISTS işləmirsə, aşağıdakıları tək-tək run edin (yalnız bir dəfə):
/*
ALTER TABLE `tour_hotel_prices` ADD COLUMN `valid_from` date DEFAULT NULL AFTER `price`;
ALTER TABLE `tour_hotel_prices` ADD COLUMN `valid_to` date DEFAULT NULL AFTER `valid_from`;
ALTER TABLE `tour_hotel_prices` ADD COLUMN `meal_plan` varchar(20) DEFAULT NULL AFTER `valid_to`;
*/

-- -----------------------------------------------------------------------------
-- 3. TOUR_AZERBAIJAN_ACTIVITIES_2026 - Activities Excel üçün
--    (name, service, adult_price, child_price_6_12 artıq var)
--    Season sütunu bəzən qiymət kimi istifadə olunur - schema uyğundur.
--    Əgər Excel-də validity tarixi varsa əlavə edə bilərsiniz:
-- -----------------------------------------------------------------------------

ALTER TABLE `tour_azerbaijan_activities_2026`
  ADD COLUMN IF NOT EXISTS `valid_from` date DEFAULT NULL AFTER `child_price_6_12`,
  ADD COLUMN IF NOT EXISTS `valid_to` date DEFAULT NULL AFTER `valid_from`;

/*
ALTER TABLE `tour_azerbaijan_activities_2026` ADD COLUMN `valid_from` date DEFAULT NULL;
ALTER TABLE `tour_azerbaijan_activities_2026` ADD COLUMN `valid_to` date DEFAULT NULL;
*/

-- -----------------------------------------------------------------------------
-- MariaDB 10.4 üçün: IF NOT EXISTS işləmirsə, yuxarıdakı ALTER-ləri comment-dən 
-- çıxarıb tək-tək run edin. "Duplicate column" = sütun artıq var, növbətinə keçin.
-- -----------------------------------------------------------------------------

-- DONE. Pricing struktur hazırdır.
-- Növbəti addımlar:
--   1. excel_transfer_to_sql.py  -> tour_transfer_prices_2026.sql (və ya part_01..22)
--   2. excel_to_sql.py           -> tour_azerbaijan_activities_2026
--   3. hotels_excel_to_sql.py    -> tour_hotel_prices
-- =============================================================================
