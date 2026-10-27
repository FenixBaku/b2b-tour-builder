/* tour_meal_prices: eyni yemək + sezon üçün təkrarlanan sətirləri sil (ən sonuncu saxlanılır) və təkrarlanmanın qarşısını al.
   Səbəb: migx/meals.sql qiymət bloku hər generasiyada yenidən əlavə olunurdu, builder-də hər yemək 3-4 dəfə görünürdü. */
DELETE p FROM `tour_meal_prices` p
JOIN `tour_meal_prices` newer
  ON newer.meal_option_id = p.meal_option_id
 AND newer.season_start = p.season_start
 AND newer.season_end = p.season_end
 AND newer.id > p.id;

ALTER TABLE `tour_meal_prices` ADD UNIQUE KEY `uniq_option_season` (`meal_option_id`, `season_start`, `season_end`);
