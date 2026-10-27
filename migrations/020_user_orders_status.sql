-- =============================================================================
-- user_orders cədvəlinə status sütunu – admin təsdiqi üçün
-- 0 = gözləmədə, 1 = təsdiqlənib, 2 = rədd edilib
-- =============================================================================
SET NAMES utf8mb4;

ALTER TABLE `user_orders`
  ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=pending,1=approved,2=rejected' AFTER `total_amount`;

