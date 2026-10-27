-- =============================================================================
-- OTP kodları – qeydiyyat zamanı email təsdiqi
-- Limit yoxdur – istənilən sayda OTP göndərilə bilər
-- =============================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `otp_codes` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(180) NOT NULL,
  `code` varchar(12) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
