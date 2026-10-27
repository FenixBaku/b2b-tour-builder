<?php
/**
 * XLSX oxuma (PhpSpreadsheet) — exec/Python olmadan cPanel üçün.
 */
function excel_xlsx_autoload() {
    static $loaded = false;
    if ($loaded) {
        return true;
    }
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        return false;
    }
    require_once $autoload;
    $loaded = true;
    return true;
}

/**
 * @return \PhpOffice\PhpSpreadsheet\Spreadsheet|null
 */
function excel_xlsx_load($path) {
    if (!excel_xlsx_autoload()) {
        throw new RuntimeException('vendor/autoload.php yoxdur. Layihədə composer install işlədin.');
    }
    if (!is_file($path)) {
        throw new RuntimeException('Excel tapılmadı: ' . $path);
    }
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('PHP ext-zip lazımdır (cPanel PHP Extensions-dan aktiv edin).');
    }
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(true);
    return $reader->load($path);
}

/** 1-based sütun/sətir */
function excel_xlsx_cell_val($sheet, $row, $col) {
    $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $cell = $sheet->getCell($letter . $row);
    $v = $cell->getValue();
    if ($v === null || $v === '') {
        $v = $cell->getCalculatedValue();
    }
    if ($v === null || $v === '') {
        return null;
    }
    return $v;
}

/** Bütün sətirlər (1-based Excel sətir nömrəsi ilə values_only massiv) */
function excel_xlsx_all_rows($sheet) {
    $highestRow = (int) $sheet->getHighestDataRow();
    $highestCol = max(
        \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn() ?: 'A'),
        \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn() ?: 'A')
    );
    $rows = [];
    for ($r = 1; $r <= $highestRow; $r++) {
        $row = [];
        for ($c = 1; $c <= $highestCol; $c++) {
            $row[] = excel_xlsx_cell_val($sheet, $r, $c);
        }
        $rows[] = $row;
    }
    return $rows;
}

function excel_xlsx_cell_str($val) {
    if ($val === null) {
        return '';
    }
    if (is_float($val) && $val == (int) $val) {
        return (string) (int) $val;
    }
    $s = str_replace(["\t", "\r"], [' ', ' '], (string) $val);
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
}
