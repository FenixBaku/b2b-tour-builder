<?php
require_once __DIR__ . '/excel_xlsx.php';
require_once __DIR__ . '/cities.php';

/** Excel-dəki şəhər xanası → şəhər kodu (BAK, QBL, ...). */
function excel_parser_activities_normalize_city($v) {
    if ($v === null || trim((string) $v) === '') {
        return CITY_DEFAULT_CODE;
    }
    return city_code($v) ?: (city_code_from_text($v) ?: CITY_DEFAULT_CODE);
}

/** Vərəq adı → şəhər kodu (məs: "Gabala Activities" → QBL). */
function excel_parser_activities_sheet_to_city($sheetName) {
    return city_code_from_text((string) $sheetName) ?: CITY_DEFAULT_CODE;
}

/**
 * Excel başlıqlarından sütun indeksləri (0-based).
 * Standart Activities faylı: I=Adult, J=Child (8, 9); köhnə səhv xəritə season=8 idi.
 *
 * @return array{adult:int, child:int, season:?int}
 */
function excel_parser_activities_detect_columns($sheet) {
    $map = ['adult' => 8, 'child' => 9, 'season' => null];
    $maxCol = min(
        30,
        max(
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn() ?: 'A'),
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn() ?: 'A')
        )
    );
    for ($r = 1; $r <= 6; $r++) {
        for ($c = 1; $c <= $maxCol; $c++) {
            $label = strtolower(excel_xlsx_cell_str(excel_xlsx_cell_val($sheet, $r, $c)));
            if ($label === '') {
                continue;
            }
            if (preg_match('/child|6\s*[-–_]\s*12/u', $label)) {
                $map['child'] = $c - 1;
            } elseif (preg_match('/\badult\b/u', $label)) {
                $map['adult'] = $c - 1;
            } elseif (preg_match('/\bseason\b/u', $label) && !preg_match('/price|peak|low/u', $label)) {
                $map['season'] = $c - 1;
            }
        }
    }
    if ($map['season'] !== null && ($map['season'] === $map['adult'] || $map['season'] === $map['child'])) {
        $map['season'] = null;
    }
    return $map;
}

function excel_parser_activities_parse_price($v) {
    if ($v === null || $v === '') {
        return null;
    }
    if (is_int($v) || is_float($v)) {
        return (float) $v;
    }
    $s = trim(str_replace(',', '.', (string) $v));
    if ($s === '') {
        return null;
    }
    if (is_numeric($s)) {
        return (float) $s;
    }
    if (preg_match('/[\d.]+/', $s, $m)) {
        return (float) $m[0];
    }
    return null;
}

/**
 * @param array<int, mixed> $row
 * @param array{adult:int, child:int, season:?int} $cols
 * @return array<string, mixed>
 */
function excel_parser_activities_map_row($row, $fallbackCity, array $cols) {
    $name = trim((string) ($row[0] ?? ''));
    $cityVal = $row[1] ?? null;
    $city = ($cityVal !== null && trim((string) $cityVal) !== '')
        ? excel_parser_activities_normalize_city($cityVal)
        : $fallbackCity;
    $service = trim((string) ($row[2] ?? '')) ?: $name;
    $desc = $row[3] ?? null;
    $openT = $row[4] ?? null;
    $closeT = $row[5] ?? null;
    $dur = $row[6] ?? null;
    if ($dur !== null && is_numeric($dur)) {
        $dur = (int) $dur;
    } else {
        $dur = null;
    }
    $slots = $row[7] ?? null;

    $adult = excel_parser_activities_parse_price($row[$cols['adult']] ?? null);
    $child = excel_parser_activities_parse_price($row[$cols['child']] ?? null);
    if ($child === null && $adult !== null) {
        $child = $adult;
    }

    $season = null;
    if ($cols['season'] !== null && array_key_exists($cols['season'], $row)) {
        $sv = $row[$cols['season']];
        if ($sv !== null && trim((string) $sv) !== '' && !is_numeric($sv)) {
            $season = trim((string) $sv);
        }
    }

    return [
        'name' => $name,
        'city' => $city,
        'service' => $service,
        'description' => $desc,
        'open_time' => $openT,
        'close_time' => $closeT,
        'duration_mins' => $dur,
        'slots' => $slots,
        'season' => $season,
        'adult_price' => $adult,
        'child_price_6_12' => $child,
    ];
}

/**
 * @return array<int, array<string, mixed>>
 */
function excel_parser_activities_collect_rows($path) {
    $wb = excel_xlsx_load($path);
    $out = [];
    foreach ($wb->getAllSheets() as $sheet) {
        $cols = excel_parser_activities_detect_columns($sheet);
        $rows = excel_xlsx_all_rows($sheet);
        $fallbackCity = excel_parser_activities_sheet_to_city($sheet->getTitle());
        for ($i = 4; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (!$row || !array_filter($row, function ($v) {
                return $v !== null && $v !== '';
            })) {
                continue;
            }
            $mapped = excel_parser_activities_map_row($row, $fallbackCity, $cols);
            if ($mapped['name'] === '') {
                continue;
            }
            $out[] = $mapped;
        }
    }
    return $out;
}
