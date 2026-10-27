-- Meals üçün sezon bazlı fiyat cədvəli (Excel strukturuna uyğun: Season 01 Jan–31 Dec 2026 + iki sütun fiyat)
CREATE TABLE IF NOT EXISTS `tour_meal_prices` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `meal_option_id` int(11) UNSIGNED NOT NULL,
  `season_start` date NOT NULL,
  `season_end` date NOT NULL,
  `price` DECIMAL(10,2) NULL DEFAULT NULL,
  `price2` DECIMAL(10,2) NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `meal_option_id` (`meal_option_id`),
  KEY `season` (`season_start`, `season_end`),
  CONSTRAINT `fk_meal_prices_option` FOREIGN KEY (`meal_option_id`) REFERENCES `tour_meal_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2026 sezonu üçün nümunə fiyatlar (Excel cədvəlinə uyğun)
INSERT INTO `tour_meal_prices` (`meal_option_id`, `season_start`, `season_end`, `price`, `price2`) VALUES
(1, '2026-01-01', '2026-12-31', 12.00, 12.00),
(2, '2026-01-01', '2026-12-31', 10.00, 10.00),
(3, '2026-01-01', '2026-12-31', 1.00, 1.00),
(4, '2026-01-01', '2026-12-31', 11.00, 11.00),
(5, '2026-01-01', '2026-12-31', 14.00, 14.00),
(6, '2026-01-01', '2026-12-31', 9.00, 9.00),
(7, '2026-01-01', '2026-12-31', 15.00, 15.00),
(8, '2026-01-01', '2026-12-31', 12.00, 12.00),
(9, '2026-01-01', '2026-12-31', 11.00, 11.00);
