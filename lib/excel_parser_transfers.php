<?php
require_once __DIR__ . '/excel_xlsx.php';

const EXCEL_PARSER_TRANSFERS_OUTPUT_COLUMNS = [
    'sedan_4_local', 'sedan_4_english', 'vito_8_local', 'vito_8_english',
    'sprinter_18', 'isuzu_27', 'merc_403_45', 'trivago_54', 'neoplan_48',
    'guides_a', 'guides_o',
];

function excel_parser_transfers_map_vehicle($vehicleName) {
    $v = strtolower((string) $vehicleName);
    $map = [
        '/sedan.*4.*local speaking|sedan.*local/' => 'sedan_4_local',
        '/sedan.*4.*english|sedan.*english.*non fluent/' => 'sedan_4_english',
        '/vito.*8.*local/' => 'vito_8_local',
        '/vito.*8.*english|vito.*english.*non fluent/' => 'vito_8_english',
        '/sprinter.*18/' => 'sprinter_18',
        '/isuzu.*27/' => 'isuzu_27',
        '/403.*45|mercedes 403/' => 'merc_403_45',
        '/trivago.*54/' => 'trivago_54',
        '/neoplan.*48/' => 'neoplan_48',
        '/guides\s*\(a\)/' => 'guides_a',
        '/guides\s*\(o\)/' => 'guides_o',
    ];
    foreach ($map as $pattern => $col) {
        if (preg_match($pattern, $v)) {
            return $col;
        }
    }
    return null;
}

function excel_parser_transfers_normalize_service($service) {
    $s = excel_xlsx_cell_str($service);
    if ($s === '') {
        return 'Transfer';
    }
    $low = strtolower(str_replace(' ', '', $s));
    if (in_array($low, ['privatetransfer', 'privatetour', 'privatetransfer\t'], true)) {
        return 'Private Transfer';
    }
    if ($low === '1waytransfer') {
        return '1 way Transfer';
    }
    if ($low === '2waystransfer') {
        return '2 ways Transfer';
    }
    return $s;
}

function excel_parser_transfers_parse_duty($val) {
    if ($val === null || excel_xlsx_cell_str($val) === '') {
        return null;
    }
    $n = (int) floor((float) $val);
    return $n > 0 ? $n : null;
}

function excel_parser_transfers_parse_number($val) {
    if ($val === null || excel_xlsx_cell_str($val) === '') {
        return null;
    }
    if (!is_numeric($val)) {
        return null;
    }
    return (float) $val;
}

function excel_parser_transfers_header_matches($sheet) {
    $labels = [];
    for ($col = 1; $col <= 9; $col++) {
        $labels[] = strtolower(excel_xlsx_cell_str(excel_xlsx_cell_val($sheet, 1, $col)));
    }
    if ($labels === []) {
        return false;
    }
    $text = implode(' ', $labels);
    $hasDuty = strpos($labels[0], 'duty') !== false || strpos($labels[0], 'daily') !== false || strpos($labels[0], 'code') !== false;
    $hasService = false;
    foreach ($labels as $lb) {
        if (strpos($lb, 'service') !== false) {
            $hasService = true;
            break;
        }
    }
    if ($hasDuty && $hasService && count($labels) > 2 && $labels[1] === 'a' && $labels[2] === 'b') {
        return true;
    }
    if ($hasDuty && $hasService && count($labels) > 4) {
        if (strpos($labels[4], 'schedule') !== false || strpos($text, 'schedule') !== false) {
            return true;
        }
    }
    return false;
}

function excel_parser_transfers_detect_meta_columns($sheet) {
    $labels = [];
    for ($col = 1; $col <= 12; $col++) {
        $labels[] = strtolower(excel_xlsx_cell_str(excel_xlsx_cell_val($sheet, 1, $col)));
    }
    if (count($labels) > 2 && $labels[1] === 'a' && $labels[2] === 'b') {
        return [
            'duty' => 0, 'a' => 1, 'b' => 2, 'service' => 3,
            'distance' => 4, 'start_time' => 5, 'duration' => 6, 'day_schedule' => 7,
        ];
    }
    $schedCol = 4;
    foreach ($labels as $i => $lb) {
        if (strpos($lb, 'schedule') !== false) {
            $schedCol = $i;
            break;
        }
    }
    return [
        'duty' => 0, 'a' => 1, 'b' => 2, 'service' => 3,
        'distance' => null, 'start_time' => null, 'duration' => null,
        'day_schedule' => $schedCol,
    ];
}

function excel_parser_transfers_find_vehicle_header_row($sheet) {
    $bestRow = 4;
    $bestCount = 0;
    $maxCol = min(
        \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn() ?: 'A'),
        40
    );
    for ($r = 1; $r <= 8; $r++) {
        $count = 0;
        for ($col = 1; $col <= $maxCol; $col++) {
            $val = excel_xlsx_cell_val($sheet, $r, $col);
            if ($val && excel_parser_transfers_map_vehicle((string) $val)) {
                $count++;
            }
        }
        if ($count > $bestCount) {
            $bestCount = $count;
            $bestRow = $r;
        }
    }
    return $bestCount >= 2 ? $bestRow : 4;
}

function excel_parser_transfers_get_vehicle_columns($sheet, $headerRow) {
    $vehicleCols = [];
    $maxCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn() ?: 'A');
    for ($col = 1; $col <= $maxCol; $col++) {
        $val = excel_xlsx_cell_val($sheet, $headerRow, $col);
        if ($val && trim((string) $val) !== '') {
            $outCol = excel_parser_transfers_map_vehicle((string) $val);
            if ($outCol) {
                $vehicleCols[] = [$col - 1, $outCol];
            }
        }
    }
    return $vehicleCols;
}

function excel_parser_transfers_find_data_start_row($sheet, $vehicleHeaderRow) {
    for ($r = $vehicleHeaderRow + 1; $r <= min((int) $sheet->getHighestDataRow(), $vehicleHeaderRow + 5); $r++) {
        $a = excel_xlsx_cell_str(excel_xlsx_cell_val($sheet, $r, 2));
        $b = excel_xlsx_cell_str(excel_xlsx_cell_val($sheet, $r, 3));
        $svc = excel_xlsx_cell_str(excel_xlsx_cell_val($sheet, $r, 4));
        $duty = excel_parser_transfers_parse_duty(excel_xlsx_cell_val($sheet, $r, 1));
        if ($duty || $a !== '' || $b !== '' || $svc !== '') {
            return $r;
        }
    }
    return $vehicleHeaderRow + 2;
}

function excel_parser_transfers_pick_sheets($sheetNames, $path) {
    // Every valid sheet in the uploaded workbook is part of the incremental
    // import. A newly added sheet must not be silently ignored.
    return array_values($sheetNames);
}

function excel_parser_transfers_row_get($row, $idx) {
    if ($idx === null || $idx >= count($row)) {
        return null;
    }
    return $row[$idx];
}

function excel_parser_transfers_map_meta_row($row, $cols) {
    $duty = excel_parser_transfers_parse_duty(excel_parser_transfers_row_get($row, $cols['duty']));
    $pointA = excel_xlsx_cell_str(excel_parser_transfers_row_get($row, $cols['a'])) ?: null;
    $pointB = excel_xlsx_cell_str(excel_parser_transfers_row_get($row, $cols['b'])) ?: null;
    $service = excel_parser_transfers_normalize_service(excel_parser_transfers_row_get($row, $cols['service']));
    $distance = excel_parser_transfers_row_get($row, $cols['distance'] ?? null);
    $startTime = excel_parser_transfers_row_get($row, $cols['start_time'] ?? null);
    $duration = excel_parser_transfers_row_get($row, $cols['duration'] ?? null);
    $daySchedule = excel_parser_transfers_row_get($row, $cols['day_schedule'] ?? null);

    if ($distance !== null && excel_xlsx_cell_str($distance) === '') {
        $distance = null;
    }
    if ($startTime !== null && excel_xlsx_cell_str($startTime) === '') {
        $startTime = null;
    }
    if ($duration !== null && excel_xlsx_cell_str($duration) === '') {
        $duration = null;
    }
    if ($daySchedule !== null && excel_xlsx_cell_str($daySchedule) === '') {
        $daySchedule = null;
    }

    return [
        'duty_code' => $duty,
        'a' => $pointA,
        'b' => $pointB,
        'service' => $service,
        'distance' => $distance,
        'start_time' => $startTime,
        'duration' => $duration,
        'day_schedule' => $daySchedule,
    ];
}

function excel_parser_transfers_row_has_price($row, $vehicleCols) {
    foreach ($vehicleCols as $pair) {
        $colIdx = $pair[0];
        if ($colIdx < count($row)) {
            $p = excel_parser_transfers_parse_number($row[$colIdx]);
            if ($p !== null && $p >= 0) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Duty kodu transferin unikal açarıdır (A|B|Service təkrarlana bilər).
 * Duty Code sütunu boş olan sətirlər (və ya köhnə, kodsuz vərəqlər) atlanır.
 * Eyni duty kodu bir neçə vərəqdə varsa, ilk vərəq (ən yeni) üstündür.
 *
 * @param array|null $skipped atlanan sətirlərin sayı: ['no_duty' => n, 'duplicate_duty' => n]
 * @return array<int, array>
 */
function excel_parser_transfers_collect_wide_rows($path, &$skipped = null) {
    $wb = excel_xlsx_load($path);
    $allRows = [];
    $seenDuties = [];
    $skipped = ['no_duty' => 0, 'duplicate_duty' => 0];
    $sheetNames = [];
    foreach ($wb->getAllSheets() as $s) {
        $sheetNames[] = $s->getTitle();
    }
    $sheets = excel_parser_transfers_pick_sheets($sheetNames, $path);

    foreach ($wb->getAllSheets() as $sheet) {
        if (!in_array($sheet->getTitle(), $sheets, true)) {
            continue;
        }
        if (!excel_parser_transfers_header_matches($sheet)) {
            continue;
        }
        $metaCols = excel_parser_transfers_detect_meta_columns($sheet);
        $vehicleHeaderRow = excel_parser_transfers_find_vehicle_header_row($sheet);
        $vehicleCols = excel_parser_transfers_get_vehicle_columns($sheet, $vehicleHeaderRow);
        if ($vehicleCols === []) {
            continue;
        }
        $dataStart = excel_parser_transfers_find_data_start_row($sheet, $vehicleHeaderRow);
        $rows = excel_xlsx_all_rows($sheet);
        for ($excelRowNum = $dataStart; $excelRowNum <= count($rows); $excelRowNum++) {
            $row = $rows[$excelRowNum - 1] ?? null;
            if (!$row) {
                continue;
            }
            $meta = excel_parser_transfers_map_meta_row($row, $metaCols);
            if (!($meta['a'] || $meta['b'] || $meta['service'])) {
                continue;
            }
            $duty = $meta['duty_code'];
            $prices = array_fill_keys(EXCEL_PARSER_TRANSFERS_OUTPUT_COLUMNS, null);
            foreach ($vehicleCols as $pair) {
                $colIdx = $pair[0];
                $outCol = $pair[1];
                if ($colIdx < count($row)) {
                    $p = excel_parser_transfers_parse_number($row[$colIdx]);
                    if ($p !== null && $p >= 0) {
                        $prices[$outCol] = $p;
                    }
                }
            }
            if (!excel_parser_transfers_row_has_price($row, $vehicleCols)) {
                continue;
            }
            if ($duty === null) {
                $skipped['no_duty']++;
                continue;
            }
            if (isset($seenDuties[$duty])) {
                $skipped['duplicate_duty']++;
                continue;
            }
            $seenDuties[$duty] = true;
            $allRows[] = [
                'duty_code' => $duty,
                'a' => $meta['a'] ?: 'Hotel',
                'b' => $meta['b'] ?: 'Transfer',
                'service' => $meta['service'] ?: 'Transfer',
                'distance' => $meta['distance'],
                'start_time' => $meta['start_time'],
                'duration' => $meta['duration'],
                'day_schedule' => $meta['day_schedule'],
                'prices' => $prices,
            ];
        }
    }
    return $allRows;
}
