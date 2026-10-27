<?php

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/api/tour_config_functions.php';
require dirname(__DIR__) . '/lib/excel_import.php';
require dirname(__DIR__) . '/lib/excel_import_native.php';

function failTransferDutyTest(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}" . PHP_EOL);
    exit(1);
}

if (!excel_xlsx_autoload()) {
    failTransferDutyTest('vendor/ is missing — run composer install');
}

// 1) Parser: duty kodu açardır, eyni marşrut fərqli duty ilə saxlanılır.
$skipped = [];
$rows = excel_parser_transfers_collect_wide_rows(__DIR__ . '/fixtures/transfer_duty_cases.xlsx', $skipped);
$byDuty = [];
foreach ($rows as $row) {
    $byDuty[$row['duty_code']] = $row;
}
if (array_keys($byDuty) !== [10, 11, 12, 13]) {
    failTransferDutyTest('expected duties 10,11,12,13, got ' . implode(',', array_keys($byDuty)));
}
if ($byDuty[10]['prices']['sedan_4_local'] !== 41.0 || $byDuty[11]['prices']['sedan_4_local'] !== 29.0) {
    failTransferDutyTest('two duties on the same route must keep their own prices');
}
if ($skipped['no_duty'] !== 1 || $skipped['duplicate_duty'] !== 1) {
    failTransferDutyTest('expected 1 row without duty and 1 duplicate duty, got ' . json_encode($skipped));
}

// 2) Import: duty kodu üzrə yeniləmə; yoxlamadan sonra rollback (real data dəyişmir).
$pdo = dbConnect();
if (!$pdo) {
    failTransferDutyTest('database connection failed');
}
$countBefore = (int) $pdo->query('SELECT COUNT(*) FROM transfers')->fetchColumn();
$pdo->beginTransaction();
try {
    $stats = ['inserted' => 0, 'updated' => 0, 'unchanged' => 0, 'total' => 0];
    excel_import_native_transfers($pdo, __DIR__ . '/fixtures/transfer_template.xlsx', $stats);
    $duty3 = fetchTransferDutyByCode($pdo, 3);
    $countAfter = (int) $pdo->query('SELECT COUNT(*) FROM transfers')->fetchColumn();
} finally {
    $pdo->rollBack();
}
if ($stats['total'] !== 3) {
    failTransferDutyTest('template should import 3 duties, got ' . $stats['total']);
}
if (!$duty3 || $duty3['b'] !== 'Gobustan' || ($duty3['prices']['Sedan 4 (Local)'] ?? null) !== 53.0) {
    failTransferDutyTest('duty 3 must be updated by its code from the template');
}
if ($countAfter - $countBefore !== $stats['inserted']) {
    failTransferDutyTest('import must only add rows for new duty codes');
}

// 3) Config: hər duty öz qiymət massivi ilə, transferVehicles sırasında.
$config = getTourConfig($pdo);
$duties = $config['transferDuties'] ?? [];
if (count($duties) !== $countBefore) {
    failTransferDutyTest('transferDuties must list every duty code');
}
$first = $duties[0];
if (count($first['prices']) !== count($config['transferVehicles'])) {
    failTransferDutyTest('duty prices must align with transferVehicles');
}

echo 'PASS: transfers are imported, listed and priced by duty code' . PHP_EOL;
