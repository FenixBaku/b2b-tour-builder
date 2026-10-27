-- Şəhərə görə aktivitələri filtrləmək üçün city sütunu əlavə et
-- tour_azerbaijan_activities_2026 cədvəli üçün
-- Əgər city sütunu yoxdursa: ALTER TABLE əlavə edin; varsa bu faylı oxuyun və skip edin.

ALTER TABLE `tour_azerbaijan_activities_2026` 
ADD COLUMN `city` VARCHAR(50) NOT NULL DEFAULT 'baku';
