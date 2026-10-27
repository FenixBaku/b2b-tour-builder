-- Excel import üçün cədvəl və indekslər (yukle.php / import_excel_to_mysql.py)
SET NAMES utf8mb4;

-- Mövcud transfers cədvəli duty_code PRIMARY KEY ilə uyğundur; yalnız yoxdursa yaradılır
CREATE TABLE IF NOT EXISTS `transfers` (
  `duty_code` int(11) NOT NULL,
  `a` varchar(255) DEFAULT NULL,
  `b` varchar(255) DEFAULT NULL,
  `service` varchar(255) DEFAULT NULL,
  `distance` decimal(10,2) DEFAULT NULL,
  `start_time` varchar(50) DEFAULT NULL,
  `duration_mins` int(11) DEFAULT NULL,
  `day_schedule` text DEFAULT NULL,
  `sedan_4_local` decimal(12,2) DEFAULT NULL,
  `sedan_4_english` decimal(12,2) DEFAULT NULL,
  `vito_8_local` decimal(12,2) DEFAULT NULL,
  `vito_8_english` decimal(12,2) DEFAULT NULL,
  `sprinter_18` decimal(12,2) DEFAULT NULL,
  `isuzu_27` decimal(12,2) DEFAULT NULL,
  `merc_403_45` decimal(12,2) DEFAULT NULL,
  `trivago_54` decimal(12,2) DEFAULT NULL,
  `neoplan_48` decimal(12,2) DEFAULT NULL,
  `guides_a` decimal(12,2) DEFAULT NULL,
  `guides_o` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`duty_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hotel_listing_full` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sheet_name` varchar(100) NOT NULL,
  `region` varchar(50) NOT NULL DEFAULT 'baku',
  `row_num` int(11) NOT NULL DEFAULT 0,
  `hotel_name` varchar(255) DEFAULT NULL,
  `room_type` varchar(255) DEFAULT NULL,
  `season_type` varchar(50) DEFAULT 'peak',
  `price` decimal(12,2) DEFAULT NULL,
  `col_extra` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sheet_region` (`sheet_name`, `region`),
  KEY `hotel_name` (`hotel_name`(100)),
  UNIQUE KEY `uq_sheet_row` (`sheet_name`(80), `region`, `row_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
