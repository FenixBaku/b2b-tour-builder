-- Yeməklər şəhərə görə: Qebele, Shahdag, Sheki
-- Hər şəhərdə yalnız öz yemək və əlavə xidmətləri göstərilir
-- Excel / cədvəllərdən uyğunlaşdırılıb
SET NAMES utf8mb4;

USE `bb_tour_builder`;

-- Köhnə şəhər məlumatlarını təmizlə (qabala, shahdag, sheki)
DELETE FROM `tour_meal_options` WHERE `city` IN ('qabala', 'shahdag', 'sheki', 'QBL', 'SHD', 'SHK');

-- ===================== QEBELE (Qəbələ) =====================
INSERT INTO `tour_meal_options` (`city`, `meal_type`, `name`, `price`, `sort_order`) VALUES
-- Meal Transfer - Lunch
('qabala', 'lunch', 'MIX (2 V + 2 NV Main Course)', 11.00, 1),
('qabala', 'lunch', 'MIX (2 V + 1 NV Main Course)', 10.00, 2),
('qabala', 'lunch', 'MIX (1 V + 1 NV Main Course)', 9.00, 3),
('qabala', 'lunch', 'VEG (4 Main Course)', 11.00, 4),
('qabala', 'lunch', 'VEG (3 Main Course)', 10.00, 5),
('qabala', 'lunch', 'VEG (2 Main Course)', 9.00, 6),
('qabala', 'lunch', 'JAIN (4V Main Course)', 12.00, 7),
('qabala', 'lunch', 'JAIN (3V Main Course)', 11.00, 8),
('qabala', 'lunch', 'JAIN (2V Main Course)', 10.00, 9),
('qabala', 'lunch', 'Local Lunch (2V + 2 NV Main Course)', 12.00, 10),
-- Meal Transfer - Dinner
('qabala', 'dinner', '2 V + 2 NV starters (2hrs), 2 V + 2 NV Main Course (1 hr)', 14.00, 1),
('qabala', 'dinner', 'MIX (2 V + 2 NV Main Course)', 11.00, 2),
('qabala', 'dinner', 'MIX (2 V + 1 NV Main Course)', 10.00, 3),
('qabala', 'dinner', 'MIX (1 V + 1 NV Main Course)', 9.00, 4),
('qabala', 'dinner', 'VEG (4 Main Course)', 11.00, 5),
('qabala', 'dinner', 'VEG (3 Main Course)', 10.00, 6),
('qabala', 'dinner', 'VEG (2 Main Course)', 9.00, 7),
('qabala', 'dinner', 'JAIN (4V Main Course)', 12.00, 8),
('qabala', 'dinner', 'JAIN (3V Main Course)', 11.00, 9),
('qabala', 'dinner', 'JAIN (2V Main Course)', 10.00, 10),
('qabala', 'dinner', 'Local Dinner (2V + 2 NV Main Course)', 12.00, 11),
-- Meal Transfer - Gala Dinner
('qabala', 'dinner', '2 V + 2 NV starters (2hrs), 2 V + 2 NV Main Course (1 hr) [Gala]', NULL, 12),
('qabala', 'dinner', '4 V starters (2hrs), 4 V Main Course (1 hr) [Gala]', NULL, 13),
('qabala', 'dinner', '2 Jain starters (2hrs), 2 Jain Main Course (1 hr) [Gala]', NULL, 14),
-- Extras
('qabala', 'dinner', 'Cake', NULL, 15),
('qabala', 'dinner', 'Snacks', NULL, 16),
('qabala', 'dinner', 'Bouquet', NULL, 17),
('qabala', 'dinner', 'Drinks', NULL, 18),
('qabala', 'dinner', 'Vehicle Fines', NULL, 19),
('qabala', 'dinner', 'Vehicle Cleaning Charges', NULL, 20),
('qabala', 'dinner', 'Hrs for Vehicle Useage', NULL, 21),
('qabala', 'dinner', 'Hrs for Guide Useage', NULL, 22);

-- ===================== SHAHDAG =====================
INSERT INTO `tour_meal_options` (`city`, `meal_type`, `name`, `price`, `sort_order`) VALUES
-- Meal Transfer - Lunch
('shahdag', 'lunch', 'MIX (2 V + 2 NV Main Course)', 11.00, 1),
('shahdag', 'lunch', 'MIX (2 V + 1 NV Main Course)', 10.00, 2),
('shahdag', 'lunch', 'MIX (1 V + 1 NV Main Course)', 9.00, 3),
('shahdag', 'lunch', 'VEG (4 Main Course)', 11.00, 4),
('shahdag', 'lunch', 'VEG (3 Main Course)', 10.00, 5),
('shahdag', 'lunch', 'VEG (2 Main Course)', 9.00, 6),
('shahdag', 'lunch', 'JAIN (4V Main Course)', 12.00, 7),
('shahdag', 'lunch', 'JAIN (3V Main Course)', 11.00, 8),
('shahdag', 'lunch', 'JAIN (2V Main Course)', 10.00, 9),
('shahdag', 'lunch', 'Local Lunch (2V + 2 NV Main Course)', 12.00, 10),
-- Meal Transfer - Hi Tea (lunch)
('shahdag', 'lunch', 'Local (Local Tea + Local Sweets / Cookies)', NULL, 11),
('shahdag', 'lunch', 'Indian (Masala Tea / Coffee + Cookies)', 6.00, 12),
('shahdag', 'lunch', 'Indian (Masala Tea / Coffee + Cookies + 1 V + 1 NV Starter)', 8.00, 13),
('shahdag', 'lunch', 'Indian (Masala Tea / Coffee + Cookies + 2 V + 2 NV Starter)', 9.00, 14),
('shahdag', 'lunch', 'Indian (Masala Tea / Coffee + Cookies + 2 V Starter)', 8.00, 15),
('shahdag', 'lunch', 'Indian (Masala Tea / Coffee + Cookies + 4 V Starter)', 9.00, 16),
('shahdag', 'lunch', 'Indian (Masala Tea / Coffee + Cookies + 2 JAIN Starter)', 8.00, 17),
-- Meal Transfer - Dinner
('shahdag', 'dinner', '2 V + 2 NV starters (2hrs), 2 V + 2 NV Main Course (1 hr)', 14.00, 1),
('shahdag', 'dinner', 'MIX (2 V + 2 NV Main Course)', 11.00, 2),
('shahdag', 'dinner', 'MIX (2 V + 1 NV Main Course)', 10.00, 3),
('shahdag', 'dinner', 'MIX (1 V + 1 NV Main Course)', 9.00, 4),
('shahdag', 'dinner', 'VEG (4 Main Course)', 11.00, 5),
('shahdag', 'dinner', 'VEG (3 Main Course)', 10.00, 6),
('shahdag', 'dinner', 'VEG (2 Main Course)', 9.00, 7),
('shahdag', 'dinner', 'JAIN (4V Main Course)', 12.00, 8),
('shahdag', 'dinner', 'JAIN (3V Main Course)', 11.00, 9),
('shahdag', 'dinner', 'JAIN (2V Main Course)', 10.00, 10),
('shahdag', 'dinner', 'Local Dinner (2V + 2 NV Main Course)', 12.00, 11),
-- Meal Transfer - Gala Dinner
('shahdag', 'dinner', '2 V + 2 NV starters (2hrs), 2 V + 2 NV Main Course (1 hr) [Gala]', NULL, 12),
('shahdag', 'dinner', '4 V starters (2hrs), 4 V Main Course (1 hr) [Gala]', NULL, 13),
('shahdag', 'dinner', '2 Jain starters (2hrs), 2 Jain Main Course (1 hr) [Gala]', NULL, 14),
-- Extras
('shahdag', 'dinner', 'Cake', NULL, 15),
('shahdag', 'dinner', 'Snacks', NULL, 16),
('shahdag', 'dinner', 'Bouquet', NULL, 17),
('shahdag', 'dinner', 'Drinks', NULL, 18),
('shahdag', 'dinner', 'Vehicle Fines', NULL, 19),
('shahdag', 'dinner', 'Vehicle Cleaning Charges', NULL, 20),
('shahdag', 'dinner', 'Hrs for Vehicle Useage', NULL, 21),
('shahdag', 'dinner', 'Hrs for Guide Useage', NULL, 22);

-- ===================== SHEKI =====================
INSERT INTO `tour_meal_options` (`city`, `meal_type`, `name`, `price`, `sort_order`) VALUES
-- Meal Transfer - Lunch
('sheki', 'lunch', 'MIX (2 V + 2 NV Main Course)', 11.00, 1),
('sheki', 'lunch', 'MIX (2 V + 1 NV Main Course)', 10.00, 2),
('sheki', 'lunch', 'MIX (1 V + 1 NV Main Course)', 9.00, 3),
('sheki', 'lunch', 'VEG (4 Main Course)', 11.00, 4),
('sheki', 'lunch', 'VEG (3 Main Course)', 10.00, 5),
('sheki', 'lunch', 'VEG (2 Main Course)', 9.00, 6),
('sheki', 'lunch', 'JAIN (4V Main Course)', 12.00, 7),
('sheki', 'lunch', 'JAIN (3V Main Course)', 11.00, 8),
('sheki', 'lunch', 'JAIN (2V Main Course)', 10.00, 9),
('sheki', 'lunch', 'Local Lunch (2V + 2 NV Main Course)', 12.00, 10),
-- Meal Transfer - Dinner
('sheki', 'dinner', '2 V + 2 NV starters (2hrs), 2 V + 2 NV Main Course (1 hr)', 14.00, 1),
('sheki', 'dinner', 'MIX (2 V + 2 NV Main Course)', 11.00, 2),
('sheki', 'dinner', 'MIX (2 V + 1 NV Main Course)', 10.00, 3),
('sheki', 'dinner', 'MIX (1 V + 1 NV Main Course)', 9.00, 4),
('sheki', 'dinner', 'VEG (4 Main Course)', 11.00, 5),
('sheki', 'dinner', 'VEG (3 Main Course)', 10.00, 6),
('sheki', 'dinner', 'VEG (2 Main Course)', 9.00, 7),
('sheki', 'dinner', 'JAIN (4V Main Course)', 12.00, 8),
('sheki', 'dinner', 'JAIN (3V Main Course)', 11.00, 9),
('sheki', 'dinner', 'JAIN (2V Main Course)', 10.00, 10),
('sheki', 'dinner', 'Local Dinner (2V + 2 NV Main Course)', 12.00, 11),
-- Meal Transfer - Gala Dinner
('sheki', 'dinner', '2 V + 2 NV starters (2hrs), 2 V + 2 NV Main Course (1 hr) [Gala]', NULL, 12),
('sheki', 'dinner', '4 V starters (2hrs), 4 V Main Course (1 hr) [Gala]', NULL, 13),
('sheki', 'dinner', '2 Jain starters (2hrs), 2 Jain Main Course (1 hr) [Gala]', NULL, 14),
-- Extras
('sheki', 'dinner', 'Cake', NULL, 15),
('sheki', 'dinner', 'Snacks', NULL, 16),
('sheki', 'dinner', 'Bouquet', NULL, 17),
('sheki', 'dinner', 'Drinks', NULL, 18),
('sheki', 'dinner', 'Vehicle Fines', NULL, 19),
('sheki', 'dinner', 'Vehicle Cleaning Charges', NULL, 20),
('sheki', 'dinner', 'Hrs for Vehicle Useage', NULL, 21),
('sheki', 'dinner', 'Hrs for Guide Useage', NULL, 22);
INSERT INTO `tour_meal_options` (`city`, `meal_type`, `name`, `price`, `sort_order`) VALUES
('baku', 'lunch', 'Indian (Masala Tea / Coffee + Cookies + 1 V + 1 NV Starter)', 8.00, 1),
('baku', 'lunch', 'Indian (Masala Tea / Coffee + Cookies + 2 JAIN Starter)', 8.00, 2),
('baku', 'lunch', 'Local ( Local Tea + Local Sweets / Cookies)', 5.00, 3),
('baku', 'lunch', 'Local Lunch (2V + 2 NV Main Course)', 12.00, 4),
('baku', 'lunch', 'Lunch at Hotel', 1.00, 5),
('baku', 'lunch', 'MIX (1 V + 1 NV Main Course)', 9.00, 6),
('baku', 'lunch', 'MIX (2 V + 1 NV Main Course)', 10.00, 7),
('baku', 'lunch', 'MIX (2 V + 2 NV Main Course)', 11.00, 8),
('baku', 'lunch', 'VEG (2 Main Course)', 9.00, 9),
('baku', 'lunch', 'VEG (4 Main Course)', 11.00, 10),
('baku', 'lunch', 'JAIN (2V Main Course)', 10.00, 11),
('baku', 'lunch', 'JAIN (4V Main Course)', 12.00, 12),
('baku', 'dinner', 'Romantic', 60.00, 1),
('baku', 'dinner', 'Premium', 90.00, 2),
('baku', 'dinner', 'Luxury', 100.00, 3),
('baku', 'dinner', 'Luxury (with Golden Ring)', 350.00, 4),
('baku', 'dinner', '2 V + 2 NV starters (2hrs), 2 V + 2 NV Main Course (1 hr)', 14.00, 5),
('baku', 'dinner', 'Dinner at Hotel', 1.00, 6),
('baku', 'dinner', 'MIX (2 V + 2 NV Main Course)', 11.00, 7),
('baku', 'dinner', 'Local Dinner (2V + 2 NV Main Course)', 12.00, 8),
('baku', 'dinner', 'VEG (4 Main Course)', 11.00, 9)
;
-- Sezon fiyatları (2026) - tour_meal_prices
INSERT INTO `tour_meal_prices` (`meal_option_id`, `season_start`, `season_end`, `price`, `price2`)
SELECT id, '2026-01-01', '2026-12-31', 15.00, 15.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='2 Jain starters (2hrs), 2 Jain Main Course (1 hr)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 14.00, 14.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='2 V + 2 NV starters (2hrs), 2 V + 2 NV Main Course (1 hr)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 14.00, 14.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='4 V starters (2hrs), 4 V Main Course (1 hr)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 1.00, 1.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='Dinner at Hotel'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 10.00, 10.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='JAIN (2V Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 11.00, 11.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='JAIN (3V Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 12.00, 12.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='JAIN (4V Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 12.00, 12.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='Local Dinner (2V + 2 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 100.00, 0.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='Luxury'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 350.00, 0.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='Luxury (with Golden Ring)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 9.00, 9.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='MIX (1 V + 1 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 10.00, 10.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='MIX (2 V + 1 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 11.00, 11.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='MIX (2 V + 2 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 90.00, 0.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='Premium'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 60.00, 0.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='Romantic'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 9.00, 9.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='VEG (2 Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 10.00, 10.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='VEG (3 Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 11.00, 11.00 FROM tour_meal_options WHERE city='baku' AND meal_type='dinner' AND name='VEG (4 Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 8.00, 8.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='Indian (Masala Tea / Coffee + Cookies + 1 V + 1 NV Starter)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 8.00, 8.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='Indian (Masala Tea / Coffee + Cookies + 2 JAIN Starter)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 9.00, 9.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='Indian (Masala Tea / Coffee + Cookies + 2 V + 2 NV Starter)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 8.00, 8.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='Indian (Masala Tea / Coffee + Cookies + 2 V Starter)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 9.00, 9.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='Indian (Masala Tea / Coffee + Cookies + 4 V Starter)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 6.00, 6.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='Indian (Masala Tea / Coffee + Cookies)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 10.00, 10.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='JAIN (2V Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 11.00, 11.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='JAIN (3V Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 12.00, 12.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='JAIN (4V Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 5.00, 5.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='Local ( Local Tea + Local Sweets / Cookies)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 12.00, 12.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='Local Lunch (2V + 2 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 1.00, 1.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='Lunch at Hotel'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 9.00, 9.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='MIX (1 V + 1 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 10.00, 10.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='MIX (2 V + 1 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 11.00, 11.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='MIX (2 V + 2 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 9.00, 9.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='VEG (2 Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 10.00, 10.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='VEG (3 Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 11.00, 11.00 FROM tour_meal_options WHERE city='baku' AND meal_type='lunch' AND name='VEG (4 Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 15.00, 15.00 FROM tour_meal_options WHERE city='qabala' AND meal_type='dinner' AND name='2 Jain starters (2hrs), 2 Jain Main Course (1 hr)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 14.00, 14.00 FROM tour_meal_options WHERE city='qabala' AND meal_type='dinner' AND name='2 V + 2 NV starters (2hrs), 2 V + 2 NV Main Course (1 hr)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 14.00, 14.00 FROM tour_meal_options WHERE city='qabala' AND meal_type='dinner' AND name='4 V starters (2hrs), 4 V Main Course (1 hr)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 10.00, 10.00 FROM tour_meal_options WHERE city='qabala' AND meal_type='dinner' AND name='JAIN (2V Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 11.00, 11.00 FROM tour_meal_options WHERE city='qabala' AND meal_type='dinner' AND name='JAIN (3V Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 12.00, 12.00 FROM tour_meal_options WHERE city='qabala' AND meal_type='dinner' AND name='JAIN (4V Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 12.00, 12.00 FROM tour_meal_options WHERE city='qabala' AND meal_type='dinner' AND name='Local Dinner (2V + 2 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 9.00, 9.00 FROM tour_meal_options WHERE city='qabala' AND meal_type='dinner' AND name='MIX (1 V + 1 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 10.00, 10.00 FROM tour_meal_options WHERE city='qabala' AND meal_type='dinner' AND name='MIX (2 V + 1 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 11.00, 11.00 FROM tour_meal_options WHERE city='qabala' AND meal_type='dinner' AND name='MIX (2 V + 2 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 9.00, 9.00 FROM tour_meal_options WHERE city='qabala' AND meal_type='dinner' AND name='VEG (2 Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 10.00, 10.00 FROM tour_meal_options WHERE city='qabala' AND meal_type='dinner' AND name='VEG (3 Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 11.00, 11.00 FROM tour_meal_options WHERE city='qabala' AND meal_type='dinner' AND name='VEG (4 Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 8.00, 8.00 FROM tour_meal_options WHERE city='shahdag' AND meal_type='lunch' AND name='Indian (Masala Tea / Coffee + Cookies + 1 V + 1 NV Starter)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 8.00, 8.00 FROM tour_meal_options WHERE city='shahdag' AND meal_type='lunch' AND name='Indian (Masala Tea / Coffee + Cookies + 2 JAIN Starter)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 9.00, 9.00 FROM tour_meal_options WHERE city='shahdag' AND meal_type='lunch' AND name='Indian (Masala Tea / Coffee + Cookies + 2 V + 2 NV Starter)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 8.00, 8.00 FROM tour_meal_options WHERE city='shahdag' AND meal_type='lunch' AND name='Indian (Masala Tea / Coffee + Cookies + 2 V Starter)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 9.00, 9.00 FROM tour_meal_options WHERE city='shahdag' AND meal_type='lunch' AND name='Indian (Masala Tea / Coffee + Cookies + 4 V Starter)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 6.00, 6.00 FROM tour_meal_options WHERE city='shahdag' AND meal_type='lunch' AND name='Indian (Masala Tea / Coffee + Cookies)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 5.00, 5.00 FROM tour_meal_options WHERE city='shahdag' AND meal_type='lunch' AND name='Local ( Local Tea + Local Sweets / Cookies)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 15.00, 15.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='dinner' AND name='2 Jain starters (2hrs), 2 Jain Main Course (1 hr)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 14.00, 14.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='dinner' AND name='2 V + 2 NV starters (2hrs), 2 V + 2 NV Main Course (1 hr)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 14.00, 14.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='dinner' AND name='4 V starters (2hrs), 4 V Main Course (1 hr)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 10.00, 10.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='dinner' AND name='JAIN (2V Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 11.00, 11.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='dinner' AND name='JAIN (3V Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 12.00, 12.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='dinner' AND name='JAIN (4V Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 12.00, 12.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='dinner' AND name='Local Dinner (2V + 2 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 9.00, 9.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='dinner' AND name='MIX (1 V + 1 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 10.00, 10.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='dinner' AND name='MIX (2 V + 1 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 11.00, 11.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='dinner' AND name='MIX (2 V + 2 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 9.00, 9.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='dinner' AND name='VEG (2 Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 10.00, 10.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='dinner' AND name='VEG (3 Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 11.00, 11.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='dinner' AND name='VEG (4 Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 10.00, 10.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='lunch' AND name='JAIN (2V Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 11.00, 11.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='lunch' AND name='JAIN (3V Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 12.00, 12.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='lunch' AND name='JAIN (4V Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 12.00, 12.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='lunch' AND name='Local Lunch (2V + 2 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 9.00, 9.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='lunch' AND name='MIX (1 V + 1 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 10.00, 10.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='lunch' AND name='MIX (2 V + 1 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 11.00, 11.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='lunch' AND name='MIX (2 V + 2 NV Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 9.00, 9.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='lunch' AND name='VEG (2 Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 10.00, 10.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='lunch' AND name='VEG (3 Main Course)'
UNION ALL SELECT id, '2026-01-01', '2026-12-31', 11.00, 11.00 FROM tour_meal_options WHERE city='sheki' AND meal_type='lunch' AND name='VEG (4 Main Course)' ON DUPLICATE KEY UPDATE `price` = VALUES(`price`), `price2` = VALUES(`price2`);

-- Şəhər adlarını kodlara çevir (lib/cities.php, migrations/021_city_codes.sql)
UPDATE `tour_meal_options` SET `city` = CASE LOWER(`city`) WHEN 'baku' THEN 'BAK' WHEN 'qabala' THEN 'QBL' WHEN 'gabala' THEN 'QBL' WHEN 'shahdag' THEN 'SHD' WHEN 'sheki' THEN 'SHK' ELSE `city` END;
