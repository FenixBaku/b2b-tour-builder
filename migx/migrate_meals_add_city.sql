-- Mövcud tour_meal_options cədvəlinə city sütunu əlavə etmək üçün
-- Əgər schema.sql-i sıfırdan işə salmısınızsa, bu fayla ehtiyac yoxdur
SET NAMES utf8mb4;
USE `bb_tour_builder`;

-- 1. city sütununu əlavə et (əgər artıq varsa, bu sətir xəta verə bilər - nəzərə almayın)
ALTER TABLE `tour_meal_options` ADD COLUMN `city` varchar(50) NOT NULL DEFAULT 'baku' AFTER `id`;

-- 2. Köhnə UNIQUE açarı sil
ALTER TABLE `tour_meal_options` DROP INDEX `meal_type_name`;

-- 3. Yeni UNIQUE açarı əlavə et (şəhərə görə)
ALTER TABLE `tour_meal_options` ADD UNIQUE KEY `city_meal_type_name` (`city`,`meal_type`,`name`);
