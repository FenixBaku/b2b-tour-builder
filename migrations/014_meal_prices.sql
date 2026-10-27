-- Meals seçeneklərinə fiyat sütunu
ALTER TABLE `tour_meal_options`
  ADD COLUMN `price` DECIMAL(10,2) NULL DEFAULT NULL AFTER `name`;
