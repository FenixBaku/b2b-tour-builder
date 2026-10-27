-- =============================================================================
-- users cədvəlinə admin_status əlavə et
-- admin_status = 1 olanlar admin panelə daxil ola bilər
-- Bir istifadəçiyə admin vermək: UPDATE users SET admin_status = 1 WHERE email = 'your@email.com';
-- =============================================================================
SET NAMES utf8mb4;

-- Əgər sütun artıq varsa, bu sətir xəta verə bilər – o zaman onu keçin
ALTER TABLE `users` ADD COLUMN `admin_status` TINYINT(1) NOT NULL DEFAULT 0 AFTER `name`;
