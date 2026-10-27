-- Hotel name / Stars regiona görə (baku, gabala, shahdag, sheki)
SET NAMES utf8mb4;

-- tour_hotels üçün region sütunu
ALTER TABLE `tour_hotels` ADD COLUMN `region` varchar(50) NOT NULL DEFAULT 'baku' AFTER `name`;

-- Köhnə unique constraint
ALTER TABLE `tour_hotels` DROP INDEX `name`;
ALTER TABLE `tour_hotels` ADD UNIQUE KEY `region_name` (`region`, `name`(100));
ALTER TABLE `tour_hotels` ADD KEY `region` (`region`);

-- Region dəyərlərini hotel adından təyin et
UPDATE `tour_hotels` SET `region` = 'gabala' WHERE `name` LIKE '% - Gabala' OR `name` LIKE '% - gabala';
UPDATE `tour_hotels` SET `region` = 'shahdag' WHERE `name` LIKE '% - Shahdag' OR `name` LIKE '% - shahdag';
