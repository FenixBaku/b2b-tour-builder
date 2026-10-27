<?php
require_once __DIR__ . '/../lib/cities.php';

/**
 * Tour Builder məlumat funksiyaları – bütün məlumat veritabanından (transfers, tour_hotels, və s.)
 * Vehicles və qiymətlər transfers cədvəlindən: sedan_4_local, sedan_4_english, vito_8_local, ...
 */

function fetchSimpleList(PDO $pdo, $table, $column) {
    $stmt = $pdo->query("SELECT `$column` FROM `$table` ORDER BY sort_order ASC, id ASC");
    return $stmt ? array_column($stmt->fetchAll(PDO::FETCH_ASSOC), $column) : [];
}

/** Water paketlərini Excel-dən import olunan Daily Water qiyməti ilə qaytarır. */
function fetchWaterPackages(PDO $pdo) {
    $dailyTwoBottlePrice = 0.0;
    try {
        $stmt = $pdo->query("SELECT adult_price FROM tour_azerbaijan_activities_2026 WHERE LOWER(TRIM(name)) = 'daily water' ORDER BY id ASC LIMIT 1");
        if ($stmt) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $dailyTwoBottlePrice = (float)($row['adult_price'] ?? 0);
        }
    } catch (Exception $e) { }

    $unitBottlePrice = $dailyTwoBottlePrice > 0 ? $dailyTwoBottlePrice / 2 : 0.0;
    $out = [];
    try {
        $stmt = $pdo->query("SELECT name FROM tour_water_packages ORDER BY sort_order ASC, id ASC");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($rows as $row) {
            $name = trim((string)($row['name'] ?? ''));
            if ($name === '') continue;
            $bottleCount = 0;
            if (preg_match('/^(\d+)\s*x/i', $name, $m)) $bottleCount = (int)$m[1];
            $out[] = [
                'value' => $name,
                'label' => $name,
                'price' => $bottleCount > 0 ? $unitBottlePrice * $bottleCount : 0.0,
            ];
        }
    } catch (Exception $e) { }
    return $out;
}

function fetchGuidesWithPrice(PDO $pdo) {
    try {
        $stmt = $pdo->query("SELECT name, price FROM tour_guides ORDER BY sort_order ASC, id ASC");
        if (!$stmt) return fetchSimpleList($pdo, 'tour_guides', 'name');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $name = $r['name'] ?? '';
            if ($name === '') continue;
            $price = isset($r['price']) && $r['price'] !== null && $r['price'] !== '' ? (float)$r['price'] : null;
            $out[] = [
                'value' => $name,
                'label' => $name,
                'price' => $price,
            ];
        }
        return $out;
    } catch (Exception $e) {
        // Əgər price sütunu yoxdursa və ya başqa xəta olursa, sadə siyahıya qayıt
        return fetchSimpleList($pdo, 'tour_guides', 'name');
    }
}

function fetchMealsByCity(PDO $pdo, $checkin = null) {
    $baseFrom = " FROM tour_meal_options m";
    $baseOrder = " ORDER BY m.city, m.meal_type, m.sort_order ASC, m.id ASC";
    $stmt = null;
    $hasPriceCol = true;
    if ($checkin && preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkin)) {
        try {
            // Hər yemək üçün tək sezon qiyməti (təkrarlanan sətir olsa da yemək bir dəfə görünsün)
            $joinSql = "SELECT m.id, m.city, m.meal_type, m.name, m.price, p.price AS season_price, p.price2 AS season_price2"
                . $baseFrom . " LEFT JOIN tour_meal_prices p ON p.id = ("
                . "SELECT p2.id FROM tour_meal_prices p2 WHERE p2.meal_option_id = m.id AND ? BETWEEN p2.season_start AND p2.season_end ORDER BY p2.id DESC LIMIT 1"
                . ")" . $baseOrder;
            $stmt = $pdo->prepare($joinSql);
            $stmt->execute([$checkin]);
        } catch (Exception $e) {
            $stmt = $pdo->query("SELECT m.id, m.city, m.meal_type, m.name, m.price" . $baseFrom . $baseOrder);
        }
    } else {
        try {
            $stmt = $pdo->query("SELECT m.id, m.city, m.meal_type, m.name, m.price" . $baseFrom . $baseOrder);
        } catch (Exception $e) {
            $stmt = $pdo->query("SELECT m.id, m.city, m.meal_type, m.name" . $baseFrom . $baseOrder);
            $hasPriceCol = false;
        }
    }
    if (!$stmt) return [];
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $city = city_code($r['city']) ?: CITY_DEFAULT_CODE;
        if (!isset($out[$city])) $out[$city] = ['breakfast' => [], 'lunch' => [], 'dinner' => []];
        $name = $r['name'];
        $price = null;
        $price2 = null;
        if (isset($r['season_price']) && $r['season_price'] !== null && $r['season_price'] !== '') $price = (float) $r['season_price'];
        if (isset($r['season_price2']) && $r['season_price2'] !== null && $r['season_price2'] !== '') $price2 = (float) $r['season_price2'];
        if ($price === null && $hasPriceCol && isset($r['price']) && $r['price'] !== null && $r['price'] !== '') $price = (float) $r['price'];
        $out[$city][$r['meal_type']][] = ['value' => $name, 'label' => $name, 'price' => $price, 'price2' => $price2];
    }
    return $out;
}

function fetchHotelPrices(PDO $pdo, $roomType = 'Double Room (2P)', $seasonType = 'peak') {
    $out = [];
    try {
        $stmt = $pdo->prepare(
            "SELECT hotel_name, sheet_name, room_type, price, col_extra " .
            "FROM hotel_listing_full " .
            "WHERE price IS NOT NULL AND price > 0 AND hotel_name != 'Extras' " .
            "ORDER BY sheet_name ASC, row_num ASC, id ASC"
        );
        $stmt->execute();
        $currentHotel = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sheet = $row['sheet_name'] ?? '';
            $name = trim((string)($row['hotel_name'] ?? ''));
            $room = trim((string)($row['room_type'] ?? ''));
            if ($name !== '') $currentHotel[$sheet] = $name;
            $h = $currentHotel[$sheet] ?? '';
            if ($h === '' || $h === 'Extras') continue;
            if ($room !== $roomType) continue;
            $price = null;
            if ($seasonType === 'peak' || $seasonType === 'standard') {
                $price = isset($row['price']) ? (float)$row['price'] : null;
            } else {
                if (!empty($row['col_extra'])) {
                    $extra = @json_decode($row['col_extra'], true);
                    if (is_array($extra) && isset($extra['Season2']) && is_numeric($extra['Season2'])) {
                        $price = (float)$extra['Season2'];
                    }
                }
                if ($price === null) $price = isset($row['price']) ? (float)$row['price'] : null;
            }
            if ($price !== null && $price > 0 && !isset($out[$h])) $out[$h] = $price;
        }
        if (empty($out)) {
            $stmt = $pdo->prepare("SELECT hotel_name, price FROM tour_hotel_prices WHERE room_type = ? AND season_type = ? AND price IS NOT NULL AND price > 0 ORDER BY hotel_name");
            if ($stmt && $stmt->execute([$roomType, $seasonType])) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $name = trim((string)($row['hotel_name'] ?? ''));
                    if ($name !== '' && !isset($out[$name])) $out[$name] = (float)$row['price'];
                }
            }
        }
    } catch (Exception $e) {
        try {
            $stmt = $pdo->prepare("SELECT hotel_name, price FROM tour_hotel_prices WHERE room_type = ? AND season_type = ? AND price IS NOT NULL AND price > 0");
            $stmt->execute([$roomType, $seasonType]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $name = trim((string)($row['hotel_name'] ?? ''));
                if ($name !== '' && !isset($out[$name])) $out[$name] = (float)$row['price'];
            }
        } catch (Exception $e2) { }
    }
    return $out;
}

/** hotel_listing_full-dan otel + otaq tipi → qiymət map: [hotel][roomType] = price */
function fetchHotelRoomPrices(PDO $pdo, $seasonType = 'peak') {
    $out = [];
    try {
        $stmt = $pdo->prepare(
            "SELECT hotel_name, sheet_name, room_type, price, col_extra " .
            "FROM hotel_listing_full " .
            "WHERE price IS NOT NULL AND price > 0 AND hotel_name != 'Extras' AND room_type IS NOT NULL AND TRIM(room_type) != '' " .
            "ORDER BY sheet_name ASC, row_num ASC, id ASC"
        );
        $stmt->execute();
        $currentHotel = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sheet = $row['sheet_name'] ?? '';
            $name = trim((string)($row['hotel_name'] ?? ''));
            $room = trim((string)($row['room_type'] ?? ''));
            if ($name !== '') $currentHotel[$sheet] = $name;
            $h = $currentHotel[$sheet] ?? '';
            if ($h === '' || $h === 'Extras') continue;
            $price = null;
            if ($seasonType === 'peak' || $seasonType === 'standard') {
                $price = isset($row['price']) ? (float)$row['price'] : null;
            } else {
                if (!empty($row['col_extra'])) {
                    $extra = @json_decode($row['col_extra'], true);
                    if (is_array($extra) && isset($extra['Season2']) && is_numeric($extra['Season2'])) {
                        $price = (float)$extra['Season2'];
                    }
                }
                if ($price === null) $price = isset($row['price']) ? (float)$row['price'] : null;
            }
            if ($price !== null && $price > 0) {
                if (!isset($out[$h])) $out[$h] = [];
                if (!isset($out[$h][$room])) $out[$h][$room] = $price;
            }
        }
    } catch (Exception $e) { }
    return $out;
}

/** hotel_listing_full Extras bölməsi: [otelAdı] => [{value, label, price}, ...] */
function fetchHotelExtras(PDO $pdo, $seasonType = 'peak') {
    $out = [];
    try {
        $stmt = $pdo->query(
            "SELECT sheet_name, hotel_name, room_type, price, col_extra, row_num " .
            "FROM hotel_listing_full " .
            "WHERE room_type IS NOT NULL AND TRIM(room_type) != '' " .
            "ORDER BY sheet_name ASC, row_num ASC, id ASC"
        );
        if (!$stmt) return $out;

        $sheetHotels = [];
        $inExtras = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sheet = $row['sheet_name'] ?? '';
            $name = trim((string)($row['hotel_name'] ?? ''));
            $room = trim((string)($row['room_type'] ?? ''));

            if ($name !== '' && strcasecmp($name, 'Extras') !== 0) {
                if (!isset($sheetHotels[$sheet])) $sheetHotels[$sheet] = [];
                if (!in_array($name, $sheetHotels[$sheet], true)) $sheetHotels[$sheet][] = $name;
                $inExtras[$sheet] = false;
                continue;
            }

            if (strcasecmp($name, 'Extras') === 0) {
                $inExtras[$sheet] = true;
            } elseif (empty($inExtras[$sheet])) {
                continue;
            }

            if (empty($inExtras[$sheet])) continue;

            $price = null;
            if ($seasonType === 'peak' || $seasonType === 'standard') {
                $price = isset($row['price']) ? (float)$row['price'] : null;
            } else {
                if (!empty($row['col_extra'])) {
                    $extra = @json_decode($row['col_extra'], true);
                    if (is_array($extra) && isset($extra['Season2']) && is_numeric($extra['Season2'])) {
                        $price = (float)$extra['Season2'];
                    }
                }
                if ($price === null) $price = isset($row['price']) ? (float)$row['price'] : null;
            }
            if ($price === null || $price <= 0) continue;

            $hotels = $sheetHotels[$sheet] ?? [];
            if (empty($hotels)) continue;

            $item = [
                'value' => $room,
                'label' => $room,
                'price' => $price,
            ];
            foreach ($hotels as $hotel) {
                if (!isset($out[$hotel])) $out[$hotel] = [];
                $exists = false;
                foreach ($out[$hotel] as $ex) {
                    if (($ex['value'] ?? '') === $room) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) $out[$hotel][] = $item;
            }
        }
    } catch (Exception $e) { }
    return $out;
}

function fetchHotelsByRegion(PDO $pdo) {
    $out = [];
    try {
        $stmt = $pdo->query(
            "SELECT hotel_name, COALESCE(region, 'baku') AS region, col_extra " .
            "FROM hotel_listing_full " .
            "WHERE hotel_name IS NOT NULL AND TRIM(hotel_name) != '' AND hotel_name != 'Extras' " .
            "ORDER BY region ASC, hotel_name ASC, id ASC"
        );
        if ($stmt) {
            $seen = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $r = city_code($row['region']) ?: CITY_DEFAULT_CODE;
                $name = trim((string)($row['hotel_name'] ?? ''));
                if ($name === '') continue;
                $key = $r . '|' . $name;
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                if (!isset($out[$r])) $out[$r] = [];
                $star = null;
                if (!empty($row['col_extra'])) {
                    $extra = @json_decode($row['col_extra'], true);
                    if (is_array($extra) && isset($extra['Star'])) $star = (int)$extra['Star'];
                }
                $label = $star ? $name . ' ★' . $star : $name;
                $out[$r][] = ['value' => $name, 'label' => $label];
            }
        }
    } catch (Exception $e) {
        try {
            $stmt = $pdo->query("SELECT name, COALESCE(region, 'baku') AS region FROM tour_hotels ORDER BY region ASC, sort_order ASC, id ASC");
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $r = city_code($row['region']) ?: CITY_DEFAULT_CODE;
                    if (!isset($out[$r])) $out[$r] = [];
                    $out[$r][] = $row['name'];
                }
            }
        } catch (Exception $e2) { }
    }
    return $out;
}

function fetchTransfersByRegion(PDO $pdo) {
    $out = [];
    $tables = ['transferA' => 'tour_transfer_point_a', 'transferB' => 'tour_transfer_point_b', 'transferServices' => 'tour_transfer_services'];
    foreach ($tables as $key => $table) {
        try {
            $stmt = $pdo->query("SELECT name, COALESCE(region, 'baku') AS region FROM `$table` ORDER BY region ASC, sort_order ASC, id ASC");
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $r = city_code($row['region']) ?: CITY_DEFAULT_CODE;
                    if (!isset($out[$r])) $out[$r] = ['transferA' => [], 'transferB' => [], 'transferServices' => []];
                    $out[$r][$key][] = $row['name'];
                }
            }
        } catch (Exception $e) {
            $stmt = $pdo->query("SELECT name FROM `$table` ORDER BY sort_order ASC, id ASC");
            if ($stmt) {
                if (!isset($out[CITY_DEFAULT_CODE])) $out[CITY_DEFAULT_CODE] = ['transferA' => [], 'transferB' => [], 'transferServices' => []];
                $out[CITY_DEFAULT_CODE][$key] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
            }
        }
    }
    return $out;
}

/** transfers cədvəlindəki vehicle/guide qiymət sütunları - veritabanından */
const TRANSFER_VEHICLE_COLUMNS = [
    'sedan_4_local' => 'Sedan 4 (Local)',
    'sedan_4_english' => 'Sedan 4 (English)',
    'vito_8_local' => 'Vito 8 (Local)',
    'vito_8_english' => 'Vito 8 (English)',
    'sprinter_18' => 'Sprinter 18',
    'isuzu_27' => 'Isuzu 27',
    'merc_403_45' => 'Mercedes 403 (45 pax)',
    'trivago_54' => 'Trivago 54',
    'neoplan_48' => 'Neoplan 48',
    'guides_a' => 'Guide (A)',
    'guides_o' => 'Guide (O)',
];

function getTransferVehicleColumnsFromTable(PDO $pdo) {
    $out = [];
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM transfers");
        if (!$stmt) return TRANSFER_VEHICLE_COLUMNS;
        $existing = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $existing[$row['Field']] = true;
        foreach (TRANSFER_VEHICLE_COLUMNS as $col => $label) {
            if (isset($existing[$col])) $out[$col] = $label;
        }
    } catch (Exception $e) { }
    return !empty($out) ? $out : TRANSFER_VEHICLE_COLUMNS;
}

function fetchTransfersFromTransfersTable(PDO $pdo) {
    $colMap = getTransferVehicleColumnsFromTable($pdo);
    $result = ['transferA' => [], 'transferB' => [], 'transferServices' => [], 'transferVehicles' => [], 'transferVehiclePrices' => []];
    try {
        $stmt = $pdo->query("SELECT DISTINCT a FROM transfers WHERE a IS NOT NULL AND TRIM(a) != '' ORDER BY a");
        if ($stmt) $result['transferA'] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'a');
        $stmt = $pdo->query("SELECT DISTINCT b FROM transfers WHERE b IS NOT NULL AND TRIM(b) != '' ORDER BY b");
        if ($stmt) $result['transferB'] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'b');
        $stmt = $pdo->query("SELECT DISTINCT service FROM transfers WHERE service IS NOT NULL AND TRIM(service) != '' ORDER BY service");
        if ($stmt) $result['transferServices'] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'service');
        $result['transferVehicles'] = array_values($colMap);
        $result['transferVehiclePrices'] = fetchTransferVehiclePricesFromTransfers($pdo, $colMap);
    } catch (Exception $e) { }
    return $result;
}

/** UI-də seçilən A/B/Service ilə DB açarları arasında alias (məs: Hotel→Baku, Airport→Hotel). */
function addTransferRouteAliases(array $prices) {
    foreach ($prices as $key => $vehiclePrices) {
        $parts = explode('|', $key, 3);
        if (count($parts) < 3) continue;
        $a = $parts[0];
        $b = $parts[1];
        $s = $parts[2];

        if ($a === 'Baku' && stripos($s, 'Day Trip') !== false) {
            $aliasKey = 'Hotel|' . $b . '|' . $s;
            if (!isset($prices[$aliasKey])) $prices[$aliasKey] = $vehiclePrices;
        }
        if ($a === 'Baku' && stripos($s, 'Overnight') !== false) {
            $aliasKey = 'Hotel|' . $b . '|' . $s;
            if (!isset($prices[$aliasKey])) $prices[$aliasKey] = $vehiclePrices;
        }
        // Airport-origin prices must stay attached to the Airport route.  Creating
        // a Hotel-origin alias here made a transfer selected on another day use
        // the airport transfer's price (for example Hotel -> Hotel, 1 way).
    }
    return $prices;
}

function fetchTransferVehiclePricesFromTransfers(PDO $pdo, $colMap = null) {
    $colMap = $colMap ?: getTransferVehicleColumnsFromTable($pdo);
    $prices = [];
    try {
        $cols = array_keys($colMap);
        $safeCols = array_map(function ($c) { return '`' . str_replace('`', '``', $c) . '`'; }, array_merge(['a', 'b', 'service'], $cols));
        $sql = "SELECT " . implode(', ', $safeCols) . " FROM transfers WHERE a IS NOT NULL AND b IS NOT NULL AND service IS NOT NULL";
        $stmt = $pdo->query($sql);
        if (!$stmt) return $prices;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $a = trim((string)($row['a'] ?? ''));
            $b = trim((string)($row['b'] ?? ''));
            $s = trim((string)($row['service'] ?? ''));
            if ($a === '' || $b === '' || $s === '') continue;
            $key = $a . '|' . $b . '|' . $s;
            if (!isset($prices[$key])) $prices[$key] = [];
            foreach ($cols as $col) {
                if (!isset($row[$col]) || $row[$col] === null || $row[$col] === '') continue;
                $val = (float) $row[$col];
                if ($val < 0) continue;
                $name = $colMap[$col];
                if (!isset($prices[$key][$name]) || $val < $prices[$key][$name]) $prices[$key][$name] = $val;
            }
        }
        $prices = addTransferRouteAliases($prices);
    } catch (Exception $e) { }
    return $prices;
}

/** Day Schedule mətnindən duty seçimi üçün qısa başlıq (bir sətir). */
function transferDutyTitle($schedule, $maxLen = 110) {
    $text = (string) $schedule;
    if ($text === '') return '';
    if (!mb_check_encoding($text, 'UTF-8')) $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    $text = ltrim($text, "-• \t");
    if (mb_strlen($text, 'UTF-8') > $maxLen) {
        $text = rtrim(mb_substr($text, 0, $maxLen - 1, 'UTF-8')) . '…';
    }
    return $text;
}

/** transfers sətrini duty obyektinə çevirir: kod, marşrut, başlıq və maşın adına görə qiymətlər. */
function transferDutyFromRow(array $row, array $colMap) {
    $prices = [];
    foreach ($colMap as $col => $label) {
        if (!isset($row[$col]) || $row[$col] === null || $row[$col] === '') continue;
        $val = (float) $row[$col];
        if ($val < 0) continue;
        $prices[$label] = $val;
    }
    return [
        'code' => (int) $row['duty_code'],
        'a' => trim((string) ($row['a'] ?? '')),
        'b' => trim((string) ($row['b'] ?? '')),
        'service' => trim((string) ($row['service'] ?? '')),
        'title' => transferDutyTitle($row['day_schedule'] ?? ''),
        'prices' => $prices,
    ];
}

function transferDutySelectSql(array $colMap) {
    $cols = array_map(function ($c) { return '`' . str_replace('`', '``', $c) . '`'; }, array_keys($colMap));
    return "SELECT duty_code, a, b, service, day_schedule, " . implode(', ', $cols) . " FROM transfers";
}

/**
 * Bütün transferlər duty kodu üzrə (hər duty unikaldır; A|B|Service təkrarlana bilər).
 * Səhifə ölçüsü üçün qiymətlər massiv kimidir: indekslər transferVehicles sırasına uyğundur (null = qiymət yoxdur).
 */
function fetchTransferDuties(PDO $pdo, $colMap = null) {
    $colMap = $colMap ?: getTransferVehicleColumnsFromTable($pdo);
    $labels = array_values($colMap);
    $out = [];
    try {
        $stmt = $pdo->query(transferDutySelectSql($colMap) . " ORDER BY duty_code ASC");
        if (!$stmt) return $out;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $duty = transferDutyFromRow($row, $colMap);
            $list = [];
            foreach ($labels as $label) {
                $list[] = $duty['prices'][$label] ?? null;
            }
            $duty['prices'] = $list;
            $out[] = $duty;
        }
    } catch (Exception $e) { }
    return $out;
}

/** Tək duty (PDF və server tərəfi üçün); day_schedule tam mətn kimi qaytarılır. */
function fetchTransferDutyByCode(PDO $pdo, $code) {
    $code = (int) $code;
    if ($code <= 0) return null;
    $colMap = getTransferVehicleColumnsFromTable($pdo);
    try {
        $stmt = $pdo->prepare(transferDutySelectSql($colMap) . " WHERE duty_code = ? LIMIT 1");
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $duty = transferDutyFromRow($row, $colMap);
        $duty['schedule'] = trim((string) ($row['day_schedule'] ?? ''));
        return $duty;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Aktivitetlər şəhər kodu üzrə: [BAK => [{value: "ad|xidmət", label, price}], ...]
 * Hər şəhərdə eyni ad|xidmət yalnız bir dəfə (ilk qiymət) götürülür — builder və server qiymət hesabı eyni siyahını istifadə edir.
 */
function fetchActivitiesByCity(PDO $pdo) {
    $out = [];
    $seen = [];
    try {
        $stmt = $pdo->query("SELECT name, service, city, adult_price FROM tour_azerbaijan_activities_2026 WHERE (name IS NOT NULL AND name != '') OR (service IS NOT NULL AND service != '') ORDER BY name ASC, service ASC");
        while ($stmt && ($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            $name = trim((string)($row['name'] ?? ''));
            $service = trim((string)($row['service'] ?? ''));
            if ($name === '' && $service === '') continue;
            $city = city_code($row['city'] ?? '') ?: CITY_DEFAULT_CODE;
            $value = $name . '|' . $service;
            if (isset($seen[$city][$value])) continue;
            $seen[$city][$value] = true;
            $label = $name !== '' && $service !== '' ? $name . ' - ' . $service : ($name ?: $service);
            $price = isset($row['adult_price']) && is_numeric($row['adult_price']) ? (float)$row['adult_price'] : null;
            $out[$city][] = ['value' => $value, 'label' => $label, 'price' => $price];
        }
    } catch (Exception $e) { }
    return $out;
}

/** Seçimdə göstəriləcək şəhər kodları (reyestr sırası ilə): məlumatı olan şəhərlər, həmişə BAK daxil. */
function fetchActiveCityCodes(PDO $pdo, array $codes) {
    $have = array_fill_keys($codes, true);
    $have[CITY_DEFAULT_CODE] = true;
    try {
        $stmt = $pdo->query("SELECT DISTINCT city FROM tour_azerbaijan_activities_2026 WHERE city IS NOT NULL AND city != ''");
        while ($stmt && ($c = $stmt->fetchColumn()) !== false) {
            $code = city_code($c);
            if ($code !== '') $have[$code] = true;
        }
    } catch (Exception $e) { }
    return array_values(array_filter(array_keys(CITY_REGISTRY), function ($code) use ($have) {
        return isset($have[$code]);
    }));
}

/** Bütün tour konfiqurasiyasını veritabanından qaytarır. Vehicles və qiymətlər transfers cədvəlindən. */
function getTourConfig(PDO $pdo, $checkin = null) {
    $mealsByCity = fetchMealsByCity($pdo, $checkin);
    $transfersByRegion = fetchTransfersByRegion($pdo);
    $hotelsByRegion = fetchHotelsByRegion($pdo);
    $fromTransfersTable = fetchTransfersFromTransfersTable($pdo);
    $hotelPrices = fetchHotelPrices($pdo);
    $hotelRoomPrices = fetchHotelRoomPrices($pdo);
    $hotelExtras = fetchHotelExtras($pdo);

    if (!empty($fromTransfersTable['transferA']) || !empty($fromTransfersTable['transferB']) || !empty($fromTransfersTable['transferServices'])) {
        $bakuFromTransfers = [
            'transferA' => $fromTransfersTable['transferA'] ?: ($transfersByRegion[CITY_DEFAULT_CODE]['transferA'] ?? []),
            'transferB' => $fromTransfersTable['transferB'] ?: ($transfersByRegion[CITY_DEFAULT_CODE]['transferB'] ?? []),
            'transferServices' => $fromTransfersTable['transferServices'] ?: ($transfersByRegion[CITY_DEFAULT_CODE]['transferServices'] ?? []),
        ];
        $transfersByRegion[CITY_DEFAULT_CODE] = array_merge($transfersByRegion[CITY_DEFAULT_CODE] ?? ['transferA' => [], 'transferB' => [], 'transferServices' => []], $bakuFromTransfers);
    }
    $bakuTransfers = $transfersByRegion[CITY_DEFAULT_CODE] ?? ['transferA' => [], 'transferB' => [], 'transferServices' => []];
    $bakuHotels = $hotelsByRegion[CITY_DEFAULT_CODE] ?? [];

    // Extra services with price
    $extraServices = [];
    try {
        $stmt = $pdo->query("SELECT id, name, price FROM tour_extra_services ORDER BY sort_order ASC, id ASC");
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $extraServices[] = [
                    'value' => $row['name'],
                    'label' => $row['name'],
                    'price' => isset($row['price']) ? (float)$row['price'] : null,
                ];
            }
        }
    } catch (Exception $e) {
        $extraServices = fetchSimpleList($pdo, 'tour_extra_services', 'name');
    }

    return [
        'countries' => fetchSimpleList($pdo, 'tour_countries', 'name'),
        'visa' => fetchSimpleList($pdo, 'tour_visa_types', 'name'),
        'vehicles' => !empty($fromTransfersTable['transferVehicles']) ? $fromTransfersTable['transferVehicles'] : fetchSimpleList($pdo, 'tour_vehicles', 'name'),
        'transferVehicles' => !empty($fromTransfersTable['transferVehicles']) ? $fromTransfersTable['transferVehicles'] : fetchSimpleList($pdo, 'tour_vehicles', 'name'),
        'transferVehiclePrices' => $fromTransfersTable['transferVehiclePrices'] ?? [],
        // prices massivi transferVehicles sırası ilədir
        'transferDuties' => fetchTransferDuties($pdo),
        'hotelPrices' => $hotelPrices,
        'hotelRoomPrices' => $hotelRoomPrices,
        'hotelExtras' => $hotelExtras,
        'water' => fetchWaterPackages($pdo),
        'guides' => fetchGuidesWithPrice($pdo),
        'hotels' => $bakuHotels,
        'hotelsByRegion' => $hotelsByRegion,
        'tours' => fetchSimpleList($pdo, 'tour_activities', 'name'),
        'entrances' => fetchSimpleList($pdo, 'tour_entrances', 'name'),
        // New: extra services list for whole-trip extras (with price)
        'extraServices' => $extraServices,
        'transferA' => $bakuTransfers['transferA'] ?? [],
        'transferB' => $bakuTransfers['transferB'] ?? [],
        'transferServices' => $bakuTransfers['transferServices'] ?? [],
        'transfersByRegion' => $transfersByRegion,
        'meals' => $mealsByCity[CITY_DEFAULT_CODE] ?? ['breakfast' => [], 'lunch' => [], 'dinner' => []],
        'mealsByCity' => $mealsByCity,
        // Məlumatı (otel, yemək, aktivitet) olan şəhər kodları + kod reyestri
        'cities' => fetchActiveCityCodes($pdo, array_merge(array_keys($mealsByCity), array_keys($hotelsByRegion))),
        'cityRegistry' => city_registry_for_js(),
    ];
}
