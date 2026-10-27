-- Destinations Azerbaijan Transfers PVT 2026 - Excel-dakı bütün sütunları DB-yə əlavə etmək
-- Excel: A=Duty Code, B=Airport/Hotel, C=City, D=Service, E=Distance, F=Start Time,
--        G=Duration(Min), H=Day Schedule, I=Pick-up From/To, J+=Vehicle columns (price)

SET NAMES utf8mb4;

-- Yeni sütunlar - Excel-dan birbaşa mapping
ALTER TABLE `tour_transfer_prices`
  ADD COLUMN `duty_code` varchar(120) DEFAULT NULL AFTER `region`,
  ADD COLUMN `location_type` varchar(50) DEFAULT NULL COMMENT 'Airport / Hotel (Excel B)' AFTER `duty_code`,
  ADD COLUMN `route_description` varchar(500) DEFAULT NULL COMMENT 'Pick-up From / To (Excel I)' AFTER `point_b`,
  ADD COLUMN `distance_km` decimal(10,2) DEFAULT NULL AFTER `service`,
  ADD COLUMN `start_time` varchar(20) DEFAULT NULL AFTER `distance_km`,
  ADD COLUMN `duration_min` int(11) DEFAULT NULL AFTER `start_time`,
  ADD COLUMN `day_schedule` varchar(255) DEFAULT NULL AFTER `duration_min`,
  ADD COLUMN `valid_from` date DEFAULT NULL COMMENT '1 Jan 2026' AFTER `day_schedule`,
  ADD COLUMN `valid_to` date DEFAULT NULL COMMENT '31 Dec 2026' AFTER `valid_from`;
-- (Əgər point_a/point_b NULL olacaqsa, UNIQUE key-i dəyişmək lazımdır)
