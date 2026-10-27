<?php
/**
 * Excel → MySQL (PHP / PhpSpreadsheet) — cPanel-də exec() olmadan.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/excel_parser_activities.php';
require_once __DIR__ . '/excel_parser_transfers.php';
require_once __DIR__ . '/excel_parser_hotels.php';
require_once __DIR__ . '/cities.php';

function excel_import_vals_equal($a, $b) {
    if ($a === null && $b === null) {
        return true;
    }
    if ($a === null || $b === null) {
        return false;
    }
    if (is_float($a) || is_float($b) || is_int($a) || is_int($b)) {
        try {
            return abs((float) $a - (float) $b) < 0.001;
        } catch (Throwable $e) {
            return trim((string) $a) === trim((string) $b);
        }
    }
    return trim((string) $a) === trim((string) $b);
}

function excel_import_native_table_exists(PDO $pdo, $name) {
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$name]);
    return (bool) $stmt->fetch();
}

/**
 * SQL faylını ";" ilə bölüb icra edir; "--" şərh sətirləri atılır (əvvəllər şərhlə başlayan əmr tam atlanırdı).
 * Miqrasiyalar təkrar işlədilə bilən yazılır, ona görə xətalar (mövcud indeks, olmayan köhnə cədvəl) dayandırmır.
 * @return string[] xəta mesajları
 */
function excel_import_native_run_sql_file(PDO $pdo, $sqlPath) {
    $errors = [];
    if (!is_file($sqlPath)) {
        return ['Fayl tapılmadı: ' . basename($sqlPath)];
    }
    $sql = preg_replace('/^\s*--.*$/m', '', file_get_contents($sqlPath));
    foreach (explode(';', $sql) as $stmt) {
        $s = trim($stmt);
        if ($s === '' || preg_match('#^/\*.*\*/$#s', $s)) {
            continue;
        }
        try {
            $pdo->exec($s);
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
    return $errors;
}

function excel_import_native_ensure_schema(PDO $pdo) {
    $dir = dirname(__DIR__) . '/migrations/';
    excel_import_native_run_sql_file($pdo, $dir . '020_excel_import_schema.sql');
    // Import şəhər kodları yazır; köhnə qeydlər də kodlara çevrilməlidir ki, dublikat yaranmasın
    excel_import_native_run_sql_file($pdo, $dir . '021_city_codes.sql');
}

function excel_import_native_activities(PDO $pdo, $path, array &$stats) {
    $rows = excel_parser_activities_collect_rows($path);
    foreach ($rows as $row) {
        $name = $row['name'] ?? '';
        if ($name === '') {
            $stats['skipped']++;
            continue;
        }
        $city = $row['city'] ?? 'baku';
        $service = $row['service'] ?? $name;
        $desc = $row['description'] ?? null;
        $openT = $row['open_time'] ?? null;
        $closeT = $row['close_time'] ?? null;
        $dur = $row['duration_mins'] ?? null;
        $slots = $row['slots'] ?? null;
        $season = $row['season'] ?? null;
        $adult = $row['adult_price'] ?? null;
        $child = $row['child_price_6_12'] ?? null;

        $stmt = $pdo->prepare(
            'SELECT id, description, open_time, close_time, duration_mins, slots, season, '
            . 'adult_price, child_price_6_12 FROM tour_azerbaijan_activities_2026 '
            . 'WHERE name=? AND city=? AND service=? LIMIT 1'
        );
        $stmt->execute([$name, $city, $service]);
        $ex = $stmt->fetch(PDO::FETCH_NUM);
        if ($ex) {
            $rid = $ex[0];
            $old = array_slice($ex, 1);
            $new = [$desc, $openT, $closeT, $dur, $slots, $season, $adult, $child];
            $same = true;
            for ($i = 0; $i < count($old); $i++) {
                if (!excel_import_vals_equal($old[$i], $new[$i])) {
                    $same = false;
                    break;
                }
            }
            if ($same) {
                $stats['unchanged']++;
            } else {
                $pdo->prepare(
                    'UPDATE tour_azerbaijan_activities_2026 SET description=?, open_time=?, '
                    . 'close_time=?, duration_mins=?, slots=?, season=?, adult_price=?, '
                    . 'child_price_6_12=? WHERE id=?'
                )->execute([$desc, $openT, $closeT, $dur, $slots, $season, $adult, $child, $rid]);
                $stats['updated']++;
            }
        } else {
            $pdo->prepare(
                'INSERT INTO tour_azerbaijan_activities_2026 '
                . '(name, city, service, description, open_time, close_time, duration_mins, '
                . 'slots, season, adult_price, child_price_6_12) VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([$name, $city, $service, $desc, $openT, $closeT, $dur, $slots, $season, $adult, $child]);
            $stats['inserted']++;
        }
    }
    $stats['total'] = count($rows);
}

/**
 * @param bool $removeMissing true olduqda faylda olmayan duty kodları silinir (DB = fayl).
 */
function excel_import_native_transfers(PDO $pdo, $path, array &$stats, $removeMissing = false) {
    if (!excel_import_native_table_exists($pdo, 'transfers')) {
        throw new RuntimeException('transfers cədvəli yoxdur. migrations/020_excel_import_schema.sql işlədin.');
    }
    $skipped = [];
    $rows = excel_parser_transfers_collect_wide_rows($path, $skipped);
    if ($rows === [] && ($skipped['no_duty'] ?? 0) > 0) {
        throw new RuntimeException('Transfer faylında Duty Code sütunu boşdur. Hər sətrin A sütununda duty kodu olmalıdır.');
    }
    $stats['skipped_no_duty'] = $skipped['no_duty'] ?? 0;
    $stats['skipped_duplicate_duty'] = $skipped['duplicate_duty'] ?? 0;
    $priceCols = EXCEL_PARSER_TRANSFERS_OUTPUT_COLUMNS;
    $allCols = array_merge(
        ['duty_code', 'a', 'b', 'service', 'distance', 'start_time', 'duration_mins', 'day_schedule'],
        $priceCols
    );
    $selectCols = implode(', ', array_slice($allCols, 1));
    $findStmt = $pdo->prepare("SELECT {$selectCols} FROM transfers WHERE duty_code=? LIMIT 1");
    $sets = implode(', ', array_map(function ($c) {
        return $c . '=?';
    }, array_slice($allCols, 1)));
    $updateStmt = $pdo->prepare("UPDATE transfers SET {$sets} WHERE duty_code=?");
    $insertStmt = $pdo->prepare('INSERT INTO transfers (' . implode(', ', $allCols) . ') VALUES ('
        . implode(',', array_fill(0, count($allCols), '?')) . ')');
    foreach ($rows as $r) {
        $duty = (int) $r['duty_code'];
        $dist = $r['distance'];
        if ($dist !== null) {
            $dist = trim((string) $dist) ?: null;
        }
        $dur = $r['duration'];
        if ($dur !== null) {
            try {
                $dur = (string) (int) floor((float) $dur);
            } catch (Throwable $e) {
                $dur = $dur ? (string) $dur : null;
            }
        }
        $startT = $r['start_time'];
        if ($startT !== null) {
            $startT = (string) $startT;
        }
        $vals = [
            $duty, $r['a'], $r['b'], $r['service'], $dist, $startT, $dur,
            $r['day_schedule'] ?? null,
        ];
        foreach ($priceCols as $c) {
            $vals[] = $r['prices'][$c] ?? null;
        }

        // Duty kodu açardır: eyni kod varsa yenilənir, yoxdursa əlavə olunur.
        $findStmt->execute([$duty]);
        $ex = $findStmt->fetch(PDO::FETCH_NUM);
        $findStmt->closeCursor();
        if ($ex) {
            $same = true;
            for ($i = 0; $i < count($ex); $i++) {
                if (!excel_import_vals_equal($ex[$i], $vals[$i + 1])) {
                    $same = false;
                    break;
                }
            }
            if ($same) {
                $stats['unchanged']++;
            } else {
                $updateStmt->execute(array_merge(array_slice($vals, 1), [$duty]));
                $stats['updated']++;
            }
        } else {
            $insertStmt->execute($vals);
            $stats['inserted']++;
        }
    }

    if ($removeMissing && $rows !== []) {
        $codes = array_map(function ($r) { return (int) $r['duty_code']; }, $rows);
        $del = $pdo->prepare('DELETE FROM transfers WHERE duty_code NOT IN (' . implode(',', array_fill(0, count($codes), '?')) . ')');
        $del->execute($codes);
        $stats['deleted'] = $del->rowCount();
    }

    $stats['total'] = count($rows);
}

function excel_import_native_meal_type($value) {
    $raw = trim((string) $value);
    $v = function_exists('mb_strtolower') ? mb_strtolower($raw, 'UTF-8') : strtolower(strtr($raw, ['Ş' => 'ş', 'Ə' => 'ə', 'İ' => 'i', 'Ö' => 'ö', 'Ü' => 'ü', 'Ç' => 'ç']));
    if (strpos($v, 'breakfast') !== false || strpos($v, 'səhər') !== false || strpos($v, 'seher') !== false) return 'breakfast';
    if (strpos($v, 'lunch') !== false || strpos($v, 'nahar') !== false || strpos($v, 'hi tea') !== false) return 'lunch';
    if (strpos($v, 'dinner') !== false || strpos($v, 'şam') !== false || strpos($v, 'sam') !== false || strpos($v, 'gala') !== false) return 'dinner';
    return in_array($v, ['breakfast', 'lunch', 'dinner'], true) ? $v : '';
}

function excel_import_native_meals(PDO $pdo, $path, array &$stats) {
    if (!excel_import_native_table_exists($pdo, 'tour_meal_options')) {
        throw new RuntimeException('tour_meal_options cədvəli yoxdur');
    }
    $book = excel_xlsx_load($path);
    foreach ($book->getWorksheetIterator() as $sheet) {
        $rows = excel_xlsx_all_rows($sheet);
        if (!$rows) continue;
        $sheetName = strtolower($sheet->getTitle());
        $defaultCity = city_code_from_text($sheet->getTitle()) ?: CITY_DEFAULT_CODE;
        $headers = array_map(function ($v) { return strtolower(trim((string) $v)); }, $rows[0]);
        $findCol = function (array $names) use ($headers) {
            foreach ($headers as $i => $h) foreach ($names as $n) if ($h === $n || strpos($h, $n) !== false) return $i;
            return null;
        };
        $cityCol = $findCol(['city', 'şəhər', 'seher']);
        $typeCol = $findCol(['meal type', 'meal_type', 'type', 'yemək növü']);
        $nameCol = $findCol(['name', 'meal', 'service', 'seçim', 'menu']);
        $priceCol = $findCol(['adult', 'price', 'qiymət']);
        if ($nameCol === null) $nameCol = count($headers) > 1 ? 1 : 0;
        $currentType = excel_import_native_meal_type($sheetName);
        foreach (array_slice($rows, 1) as $row) {
            $rawType = $typeCol !== null ? ($row[$typeCol] ?? '') : ($row[0] ?? '');
            $detectedType = excel_import_native_meal_type($rawType);
            if ($detectedType !== '') $currentType = $detectedType;
            $name = trim((string) ($row[$nameCol] ?? ''));
            if ($name === '' || excel_import_native_meal_type($name) !== '') continue;
            if ($currentType === '') { $stats['skipped']++; continue; }
            $rawCity = trim((string) ($cityCol !== null ? ($row[$cityCol] ?? '') : ''));
            $city = $rawCity !== '' ? (city_code($rawCity) ?: $defaultCity) : $defaultCity;
            $priceRaw = $priceCol !== null ? ($row[$priceCol] ?? null) : null;
            $price = is_numeric($priceRaw) ? (float) $priceRaw : null;
            $stmt = $pdo->prepare('SELECT id, price FROM tour_meal_options WHERE city=? AND meal_type=? AND name=? LIMIT 1');
            $stmt->execute([$city, $currentType, $name]);
            $existing = $stmt->fetch(PDO::FETCH_NUM);
            if ($existing) {
                if (excel_import_vals_equal($existing[1], $price)) $stats['unchanged']++;
                else {
                    $pdo->prepare('UPDATE tour_meal_options SET price=? WHERE id=?')->execute([$price, $existing[0]]);
                    $stats['updated']++;
                }
            } else {
                $sortStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM tour_meal_options WHERE city=? AND meal_type=?');
                $sortStmt->execute([$city, $currentType]);
                $sort = (int) $sortStmt->fetchColumn();
                $pdo->prepare('INSERT INTO tour_meal_options (city, meal_type, name, price, sort_order) VALUES (?,?,?,?,?)')
                    ->execute([$city, $currentType, $name, $price, $sort]);
                $stats['inserted']++;
            }
            $stats['total']++;
        }
    }
}

function excel_import_native_hotels(PDO $pdo, $path, array &$stats) {
    list($hotels, $prices) = excel_parser_hotels_collect_hotels_and_prices($path);
    $hasTourHotels = excel_import_native_table_exists($pdo, 'tour_hotels');
    $hasTourPrices = excel_import_native_table_exists($pdo, 'tour_hotel_prices');
    $hasListing = excel_import_native_table_exists($pdo, 'hotel_listing_full');

    foreach ($hotels as $i => $name) {
        if (!$hasTourHotels) {
            break;
        }
        $region = city_code_from_text($name) ?: CITY_DEFAULT_CODE;
        $stmt = $pdo->prepare('INSERT IGNORE INTO tour_hotels (name, region, sort_order) VALUES (?, ?, ?)');
        $stmt->execute([$name, $region, $i + 1]);
        if ($stmt->rowCount()) {
            $stats['hotels_inserted']++;
        } else {
            $stats['hotels_unchanged']++;
        }
    }

    foreach ($prices as $p) {
        if (!$hasTourPrices) {
            break;
        }
        list($h, $region, $room, $season, $price) = $p;
        $stmt = $pdo->prepare(
            'SELECT id, price FROM tour_hotel_prices WHERE hotel_name=? AND region=? '
            . 'AND room_type=? AND season_type=?'
        );
        $stmt->execute([$h, $region, $room, $season]);
        $ex = $stmt->fetch(PDO::FETCH_NUM);
        if ($ex) {
            if (excel_import_vals_equal($ex[1], $price)) {
                $stats['prices_unchanged']++;
            } else {
                $pdo->prepare('UPDATE tour_hotel_prices SET price=? WHERE id=?')->execute([$price, $ex[0]]);
                $stats['prices_updated']++;
            }
        } else {
            $pdo->prepare(
                'INSERT INTO tour_hotel_prices (hotel_name, region, room_type, season_type, price) VALUES (?,?,?,?,?)'
            )->execute([$h, $region, $room, $season, $price]);
            $stats['prices_inserted']++;
        }
    }

    if (!$hasListing) {
        $stats['note'] = 'hotel_listing_full cədvəli yoxdur; yalnız tour_hotels import edildi';
        $stats['total_hotels'] = $hasTourHotels ? count($hotels) : 0;
        $stats['total_prices'] = $hasTourPrices ? count($prices) : 0;
        return;
    }

    $sheets = excel_parser_hotels_collect_all_sheets($path);
    foreach ($sheets as $sheet) {
        $name = $sheet['name'];
        $headers = $sheet['headers'];
        $dataRows = $sheet['rows'];
        $region = excel_parser_hotels_sheet_to_region($name);
        $hotelCol = $roomCol = null;
        $priceCols = [];
        foreach ($headers as $i => $h) {
            if ($h === null || $h === '') {
                continue;
            }
            $hl = strtolower(trim((string) $h));
            if (strpos($hl, 'hotel') !== false || $hl === 'name' || strpos($hl, 'otel') !== false) {
                $hotelCol = $i;
            }
            if (strpos($hl, 'room') !== false || strpos($hl, 'otaq') !== false) {
                $roomCol = $i;
            }
            if (strpos($hl, 'peak') !== false) {
                $priceCols[] = [$i, 'peak'];
            } elseif (strpos($hl, 'low') !== false) {
                $priceCols[] = [$i, 'low'];
            } elseif (preg_match('/^season\s*_?\s*2$/', $hl)) {
                $priceCols[] = [$i, 'low'];
            } elseif ($hl === 'price' || $hl === 'rate') {
                $priceCols[] = [$i, 'standard'];
            }
        }

        foreach ($dataRows as $rowIdx => $row) {
            $rowNum = $rowIdx + 2;
            $hotelName = '';
            if ($hotelCol !== null && isset($row[$hotelCol]) && $row[$hotelCol]) {
                $hotelName = trim((string) $row[$hotelCol]);
            }
            $roomType = '';
            if ($roomCol !== null && isset($row[$roomCol]) && $row[$roomCol]) {
                $roomType = trim((string) $row[$roomCol]);
            }
            $priceVal = null;
            $seasonType = 'peak';
            foreach ($priceCols as $pair) {
                $pc = $pair[0];
                $st = $pair[1];
                if (isset($row[$pc])) {
                    $p = excel_parser_hotels_parse_num_listing($row[$pc]);
                    if ($p !== null && $p > 0) {
                        $priceVal = $p;
                        $seasonType = $st;
                        break;
                    }
                }
            }
            $extra = [];
            foreach ($headers as $i => $h) {
                if (!isset($row[$i]) || $row[$i] === null || trim((string) $row[$i]) === '') {
                    continue;
                }
                if (($hotelCol === null || $i !== $hotelCol) && ($roomCol === null || $i !== $roomCol)) {
                    $isPriceCol = false;
                    foreach ($priceCols as $pair) {
                        if ($pair[0] === $i) {
                            $isPriceCol = true;
                            break;
                        }
                    }
                    if (!$isPriceCol) {
                        $extra[(string) $h] = $row[$i];
                    }
                }
            }
            $extraJson = $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null;

            if ($hotelName && $roomType) {
                $stmt = $pdo->prepare(
                    'SELECT id, hotel_name, room_type, season_type, price, col_extra '
                    . 'FROM hotel_listing_full WHERE hotel_name=? AND room_type=? '
                    . 'AND region=? AND season_type=? LIMIT 1'
                );
                $stmt->execute([$hotelName, $roomType, $region, $seasonType]);
            } else {
                $stmt = $pdo->prepare(
                    'SELECT id, hotel_name, room_type, season_type, price, col_extra '
                    . 'FROM hotel_listing_full WHERE sheet_name=? AND region=? AND row_num=? LIMIT 1'
                );
                $stmt->execute([$name, $region, $rowNum]);
            }
            $ex = $stmt->fetch(PDO::FETCH_NUM);
            if ($ex) {
                $old = [$ex[1], $ex[2], $ex[3], $ex[4], $ex[5]];
                $new = [$hotelName ?: null, $roomType ?: null, $seasonType, $priceVal, $extraJson];
                $same = true;
                for ($i = 0; $i < count($old); $i++) {
                    if (!excel_import_vals_equal($old[$i], $new[$i])) {
                        $same = false;
                        break;
                    }
                }
                if ($same) {
                    $stats['listing_unchanged']++;
                } else {
                    $pdo->prepare(
                        'UPDATE hotel_listing_full SET hotel_name=?, room_type=?, season_type=?, '
                        . 'price=?, col_extra=? WHERE id=?'
                    )->execute([$hotelName ?: null, $roomType ?: null, $seasonType, $priceVal, $extraJson, $ex[0]]);
                    $stats['listing_updated']++;
                }
            } else {
                $pdo->prepare(
                    'INSERT INTO hotel_listing_full '
                    . '(sheet_name, region, row_num, hotel_name, room_type, season_type, price, col_extra) '
                    . 'VALUES (?,?,?,?,?,?,?,?)'
                )->execute([$name, $region, $rowNum, $hotelName ?: null, $roomType ?: null, $seasonType, $priceVal, $extraJson]);
                $stats['listing_inserted']++;
            }
        }
    }
    $stats['total_hotels'] = count($hotels);
    $stats['total_prices'] = count($prices);
}

function excel_import_native_import_file(PDO $pdo, $path, array $options = []) {
    $kind = excel_import_detect_type(basename($path));
    $result = ['file' => $path, 'type' => $kind, 'ok' => false, 'error' => null, 'stats' => []];
    if ($kind === 'unknown') {
        $result['error'] = 'Fayl növü tanınmadı (adında activity, transfer, hotel və ya meal olmalıdır)';
        return $result;
    }
    if (!is_file($path)) {
        $result['error'] = 'Fayl tapılmadı';
        return $result;
    }
    try {
        if ($kind === 'activities') {
            $stats = ['inserted' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'total' => 0];
            excel_import_native_activities($pdo, $path, $stats);
            $result['stats'] = $stats;
        } elseif ($kind === 'transfers') {
            $stats = ['inserted' => 0, 'updated' => 0, 'unchanged' => 0, 'total' => 0];
            excel_import_native_transfers($pdo, $path, $stats, !empty($options['remove_missing_duties']));
            $result['stats'] = $stats;
        } elseif ($kind === 'hotels') {
            $stats = [
                'hotels_inserted' => 0, 'hotels_unchanged' => 0,
                'prices_inserted' => 0, 'prices_updated' => 0, 'prices_unchanged' => 0,
                'listing_inserted' => 0, 'listing_updated' => 0, 'listing_unchanged' => 0,
            ];
            excel_import_native_hotels($pdo, $path, $stats);
            $result['stats'] = $stats;
        } elseif ($kind === 'meals') {
            $stats = ['inserted' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'total' => 0];
            excel_import_native_meals($pdo, $path, $stats);
            $result['stats'] = $stats;
        }
        $result['ok'] = true;
    } catch (Throwable $e) {
        $result['error'] = $e->getMessage();
    }
    return $result;
}

/**
 * @param string[] $paths
 * @return array{ok:bool, results?:array, error?:string, log?:string, engine?:string}
 */
function excel_import_native_to_mysql(array $paths, array $options = []) {
    if (!excel_xlsx_autoload()) {
        return [
            'ok' => false,
            'error' => 'vendor/ qovluğu yoxdur. cPanel Terminal: cd public_html/bb && composer install',
            'engine' => 'php',
        ];
    }

    $valid = [];
    foreach ($paths as $p) {
        $p = realpath($p);
        if ($p && is_file($p) && preg_match('/\.xlsx$/i', $p)) {
            $valid[] = $p;
        }
    }
    if ($valid === []) {
        return ['ok' => false, 'error' => 'Keçərli .xlsx faylı yoxdur', 'engine' => 'php'];
    }

    $pdo = dbConnect();
    if (!$pdo) {
        return ['ok' => false, 'error' => 'Veritabanı bağlantısı uğursuz (config.php)', 'engine' => 'php'];
    }

    try {
        excel_import_native_ensure_schema($pdo);
        $results = [];
        foreach ($valid as $path) {
            $pdo->beginTransaction();
            $one = excel_import_native_import_file($pdo, $path, $options);
            if (!empty($one['ok'])) $pdo->commit();
            else $pdo->rollBack();
            $results[] = $one;
        }
        $ok = true;
        foreach ($results as $r) {
            if (empty($r['ok'])) {
                $ok = false;
            }
        }
        return [
            'ok' => $ok,
            'results' => $results,
            'files_found' => count($valid),
            'engine' => 'php',
            'log' => 'PHP import (cPanel / exec olmadan)',
        ];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage(), 'engine' => 'php'];
    }
}
