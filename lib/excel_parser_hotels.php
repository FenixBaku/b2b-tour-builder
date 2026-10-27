<?php
require_once __DIR__ . '/excel_xlsx.php';
require_once __DIR__ . '/cities.php';

/** Vərəq adı → şəhər kodu ("LakeSide Hotel - Gabala" → QBL; kəsik "- Gaba" da tanınır). */
function excel_parser_hotels_sheet_to_region($sheetName) {
    return city_code_from_text((string) $sheetName) ?: CITY_DEFAULT_CODE;
}

function excel_parser_hotels_find_column($headerRow, $keywords) {
    foreach ($headerRow as $i => $cell) {
        if ($cell === null || $cell === '') {
            continue;
        }
        $val = strtolower(trim((string) $cell));
        foreach ($keywords as $kw) {
            if (strpos($val, $kw) !== false) {
                return $i;
            }
        }
    }
    return null;
}

function excel_parser_hotels_find_hotel_column($headerRow) {
    foreach ($headerRow as $i => $cell) {
        if ($cell === null) {
            continue;
        }
        $val = strtolower(trim((string) $cell));
        if (in_array($val, ['name', 'hotel', 'otel'], true)) {
            return $i;
        }
    }
    foreach ($headerRow as $i => $cell) {
        if ($cell === null) {
            continue;
        }
        $val = strtolower(trim((string) $cell));
        if (strpos($val, 'name') !== false || strpos($val, 'hotel') !== false) {
            return $i;
        }
    }
    return null;
}

function excel_parser_hotels_find_price_columns($headerRow) {
    $out = [];
    foreach ($headerRow as $i => $cell) {
        if ($cell === null) {
            continue;
        }
        $val = strtolower(trim((string) $cell));
        if (strpos($val, 'peak') !== false) {
            $out[] = [$i, 'peak'];
        } elseif (strpos($val, 'low') !== false) {
            $out[] = [$i, 'low'];
        } elseif (preg_match('/^season\s*_?\s*2$/', $val)) {
            $out[] = [$i, 'low'];
        } elseif ($val === 'price' || $val === 'rate') {
            $out[] = [$i, 'standard'];
        }
    }
    return $out ?: null;
}

function excel_parser_hotels_parse_price($val) {
    if ($val === null) {
        return null;
    }
    $s = str_replace(',', '.', trim((string) $val));
    if (preg_match('/[\d.]+/', $s, $m)) {
        return (float) $m[0];
    }
    return null;
}

function excel_parser_hotels_parse_num_listing($val) {
    if ($val === null) {
        return null;
    }
    $s = str_replace(',', '.', trim((string) $val));
    if (!preg_match_all('/\d+\.?\d*/', $s, $nums) || $nums[0] === []) {
        return null;
    }
    if (count($nums[0]) >= 3 && max(array_map('strlen', $nums[0])) >= 4) {
        return null;
    }
    $f = (float) $nums[0][0];
    if ($f >= 2020 && $f <= 2030) {
        return null;
    }
    if ($f >= 1 && $f <= 10000) {
        return $f;
    }
    return null;
}

function excel_parser_hotels_is_valid_name($name) {
    if (!$name || strlen($name) < 2) {
        return false;
    }
    $n = trim($name);
    if (strlen($n) > 120) {
        return false;
    }
    $lower = strtolower($n);
    if (preg_match('/^\d+$/', str_replace([' ', '.'], '', $n))) {
        return false;
    }
    if (in_array($lower, ['bb', 'hb', 'fb', 'ai', 'extras', 'room', 'suite'], true)) {
        return false;
    }
    foreach (['2025', '2026', '01 jan', '01 june', '31 dec', ' - 31'] as $x) {
        if (strpos($lower, $x) !== false) {
            return false;
        }
    }
    if (preg_match('/room\s*\(\d+p\)/', $lower) || strpos($lower, ' (2p)') !== false || strpos($lower, ' (1p)') !== false) {
        return false;
    }
    return (bool) preg_match('/[a-zA-Z]/u', $n);
}

/**
 * @return array{0: string[], 1: array}
 */
function excel_parser_hotels_collect_hotels_and_prices($path) {
    $wb = excel_xlsx_load($path);
    $hotels = [];
    $prices = [];

    foreach ($wb->getAllSheets() as $sheet) {
        $rows = excel_xlsx_all_rows($sheet);
        if ($rows === []) {
            continue;
        }
        $region = excel_parser_hotels_sheet_to_region($sheet->getTitle());
        $header = $rows[0];
        $hotelCol = excel_parser_hotels_find_hotel_column($header);
        $roomCol = excel_parser_hotels_find_column($header, ['room', 'otaq', 'room type']);
        $priceCols = excel_parser_hotels_find_price_columns($header);

        for ($i = 6; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (!$row) {
                continue;
            }
            $hotelVal = ($hotelCol !== null && isset($row[$hotelCol])) ? $row[$hotelCol] : $sheet->getTitle();
            $hotelStr = $hotelVal ? trim((string) $hotelVal) : '';
            if ($hotelStr === '' && $hotelCol === null) {
                $hotelStr = trim($sheet->getTitle());
            }
            if (!$hotelStr || !excel_parser_hotels_is_valid_name($hotelStr)) {
                continue;
            }
            $hotels[$hotelStr] = true;
            $roomType = '';
            if ($roomCol !== null && isset($row[$roomCol]) && $row[$roomCol]) {
                $roomType = trim((string) $row[$roomCol]);
            }
            if ($priceCols) {
                foreach ($priceCols as $pair) {
                    $colIdx = $pair[0];
                    $seasonKey = $pair[1];
                    if (isset($row[$colIdx])) {
                        $p = excel_parser_hotels_parse_price($row[$colIdx]);
                        if ($p !== null && $p > 0) {
                            $prices[] = [$hotelStr, $region, $roomType ?: 'Standard', $seasonKey, $p];
                        }
                    }
                }
            }
        }
    }
    $hotelList = array_keys($hotels);
    sort($hotelList);
    return [$hotelList, $prices];
}

/**
 * @return array<int, array{name:string, headers:array, rows:array}>
 */
function excel_parser_hotels_collect_all_sheets($path) {
    $wb = excel_xlsx_load($path);
    $allSheets = [];
    foreach ($wb->getAllSheets() as $sheet) {
        $rows = excel_xlsx_all_rows($sheet);
        if ($rows === []) {
            $allSheets[] = ['name' => $sheet->getTitle(), 'headers' => [], 'rows' => []];
            continue;
        }
        $headerRow = $rows[0];
        $headers = [];
        $seen = [];
        foreach ($headerRow as $i => $cell) {
            $h = $cell ? preg_replace('/[^a-zA-Z0-9_]/', '_', trim((string) $cell)) : ('col_' . $i);
            $h = preg_replace('/_+/', '_', trim($h, '_')) ?: 'col';
            if ($h !== '' && $h[0] >= '0' && $h[0] <= '9') {
                $h = 'col_' . $h;
            }
            $h = substr($h, 0, 64) ?: 'col';
            if (isset($seen[$h])) {
                $seen[$h]++;
                $h .= '_' . $seen[$h];
            } else {
                $seen[$h] = 0;
            }
            $headers[] = $h;
        }
        $dataRows = array_slice($rows, 1);
        $allSheets[] = ['name' => $sheet->getTitle(), 'headers' => $headerRow, 'rows' => $dataRows];
    }
    return $allSheets;
}
