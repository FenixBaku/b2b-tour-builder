-- A, B, SERVICE regiona bağlı olmalıdır
SET NAMES utf8mb4;

-- tour_transfer_point_a üçün region sütunu
ALTER TABLE `tour_transfer_point_a` ADD COLUMN `region` varchar(50) NOT NULL DEFAULT 'baku' AFTER `name`;
ALTER TABLE `tour_transfer_point_a` DROP INDEX `name`;
ALTER TABLE `tour_transfer_point_a` ADD UNIQUE KEY `region_name` (`region`, `name`(150));
ALTER TABLE `tour_transfer_point_a` ADD KEY `region` (`region`);

-- tour_transfer_point_b üçün region sütunu
ALTER TABLE `tour_transfer_point_b` ADD COLUMN `region` varchar(50) NOT NULL DEFAULT 'baku' AFTER `name`;
ALTER TABLE `tour_transfer_point_b` DROP INDEX `name`;
ALTER TABLE `tour_transfer_point_b` ADD UNIQUE KEY `region_name` (`region`, `name`(150));
ALTER TABLE `tour_transfer_point_b` ADD KEY `region` (`region`);

-- tour_transfer_services üçün region sütunu
ALTER TABLE `tour_transfer_services` ADD COLUMN `region` varchar(50) NOT NULL DEFAULT 'baku' AFTER `name`;
ALTER TABLE `tour_transfer_services` DROP INDEX `name`;
ALTER TABLE `tour_transfer_services` ADD UNIQUE KEY `region_name` (`region`, `name`(150));
ALTER TABLE `tour_transfer_services` ADD KEY `region` (`region`);
