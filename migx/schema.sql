

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Veritabanı
CREATE DATABASE IF NOT EXISTS `bb_tour_builder` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bb_tour_builder`;

-- ------------------------------------------------------------
-- Ölkələr (Milliyət / Nationality)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `tour_countries`;
CREATE TABLE `tour_countries` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Viza növləri
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `tour_visa_types`;
CREATE TABLE `tour_visa_types` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Nəqliyyat növləri (Vehicles)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `tour_vehicles`;
CREATE TABLE `tour_vehicles` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Su paketləri (Water)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `tour_water_packages`;
CREATE TABLE `tour_water_packages` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Bələdçi seçimləri (Guides)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `tour_guides`;
CREATE TABLE `tour_guides` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Otellər (Hotels)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `tour_hotels`;
CREATE TABLE `tour_hotels` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `region` varchar(50) NOT NULL DEFAULT 'baku',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `region_name` (`region`, `name`(100)),
  KEY `region` (`region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Turlar / Aktivitetlər (Tours)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `tour_activities`;
CREATE TABLE `tour_activities` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Girişlər / Biletlər (Entrances)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `tour_entrances`;
CREATE TABLE `tour_entrances` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Yemək seçimləri (Meals: breakfast, lunch, dinner) - şəhərə görə
-- city: baku, qabala, shahdag, sheki
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `tour_meal_options`;
CREATE TABLE `tour_meal_options` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `city` varchar(50) NOT NULL DEFAULT 'baku',
  `meal_type` enum('breakfast','lunch','dinner') NOT NULL,
  `name` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `city_meal_type_name` (`city`,`meal_type`,`name`),
  KEY `city` (`city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Transfer nöqtələri (Transfers Excel: A = başlanğıc, B = varış)
-- A, B, SERVICE regiona bağlıdır (baku, gabala, shahdag, shamakhi, sheki)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `tour_transfer_point_a`;
CREATE TABLE `tour_transfer_point_a` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `region` varchar(50) NOT NULL DEFAULT 'baku',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `region_name` (`region`, `name`(150)),
  KEY `region` (`region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tour_transfer_point_b`;
CREATE TABLE `tour_transfer_point_b` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `region` varchar(50) NOT NULL DEFAULT 'baku',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `region_name` (`region`, `name`(150)),
  KEY `region` (`region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transfer Service növləri (1 way Transfer, 2 ways Transfer, Day Trip, Private Tour, Overnight və s.)
DROP TABLE IF EXISTS `tour_transfer_services`;
CREATE TABLE `tour_transfer_services` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `region` varchar(50) NOT NULL DEFAULT 'baku',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `region_name` (`region`, `name`(150)),
  KEY `region` (`region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Nümunə məlumatlar (isteğe bağlı - silə bilərsiniz)
-- ============================================================

INSERT INTO `tour_countries` (`name`, `sort_order`) VALUES
('India', 1),
('Pakistan', 2),
('United Arab Emirates', 3),
('Saudi Arabia', 4),
('United Kingdom', 5);

INSERT INTO `tour_visa_types` (`name`, `sort_order`) VALUES
('Not Required', 1),
('Standard (3-5 Days)', 2),
('Urgent (3 Hours)', 3);

INSERT INTO `tour_vehicles` (`name`, `sort_order`) VALUES
('Sedan (Up to 3 pax)', 1),
('Vito (Up to 6 pax)', 2),
('Sprinter (Up to 15 pax)', 3);

INSERT INTO `tour_water_packages` (`name`, `sort_order`) VALUES
('1x 300ml daily', 1),
('2x 300ml daily', 2),
('None', 3);

INSERT INTO `tour_guides` (`name`, `sort_order`) VALUES
('English Speaking Guide', 1),
('Arabic Speaking Guide', 2),
('Russian Speaking Guide', 3),
('English Speaking Driver only', 4);

INSERT INTO `tour_hotels` (`name`, `sort_order`) VALUES
('JW Marriott Baku', 1),
('Hilton Baku', 2),
('Four Seasons', 3),
('Holiday Inn Baku', 4);

INSERT INTO `tour_activities` (`name`, `sort_order`) VALUES
('Airport Transfer', 1),
('Baku City Tour', 2),
('Old City Walk', 3);

INSERT INTO `tour_entrances` (`name`, `sort_order`) VALUES
('No Entrance Needed', 1),
('Flame Towers', 2),
('Maiden Tower', 3);

INSERT INTO `tour_meal_options` (`city`, `meal_type`, `name`, `sort_order`) VALUES
('baku', 'breakfast', 'Hotel Buffet Breakfast', 1),
('baku', 'breakfast', 'Set Menu Breakfast', 2),
('baku', 'breakfast', 'No Breakfast', 3),
('baku', 'lunch', 'Local Restaurant Lunch', 1),
('baku', 'lunch', 'Hotel Lunch', 2),
('baku', 'lunch', 'Lunch Box', 3),
('baku', 'dinner', 'Local Restaurant Dinner', 1),
('baku', 'dinner', 'Hotel Dinner', 2),
('baku', 'dinner', 'Dinner Cruise', 3);