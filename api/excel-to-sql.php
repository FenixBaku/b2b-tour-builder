<?php
/**
 * Excel Upload → SQL Generate API
 * POST (multipart/form-data): type=activities|hotels|transfers, file=excel
 * Çıxış: JSON { ok, sql, filename, error }
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth/_auth_helper.php';
auth_require_admin_api();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Yalnız POST qəbul olunur']);
    exit;
}

$type = isset($_POST['type']) ? trim($_POST['type']) : '';
$validTypes = ['activities', 'hotels', 'transfers'];
if (!in_array($type, $validTypes, true)) {
    echo json_encode(['ok' => false, 'error' => 'Keçərsiz tip. activities, hotels və ya transfers seçin.']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'Excel faylı yüklənmədi və ya xəta var.']);
    exit;
}

$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['xlsx', 'xls'], true)) {
    echo json_encode(['ok' => false, 'error' => 'Yalnız .xlsx və .xls faylları qəbul olunur.']);
    exit;
}

$root = dirname(__DIR__);
$tempDir = $root . DIRECTORY_SEPARATOR . 'temp';
if (!is_dir($tempDir)) {
    @mkdir($tempDir, 0755, true);
}
$tempFile = $tempDir . DIRECTORY_SEPARATOR . 'excel_upload_' . uniqid() . '.' . $ext;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $tempFile)) {
    echo json_encode(['ok' => false, 'error' => 'Müvəqqəti fayl saxlanılmadı.']);
    exit;
}

$python = 'python';
if (defined('PHP_OS_FAMILY') && PHP_OS_FAMILY === 'Windows') {
    $python = 'python';
}

$outputDir = $tempDir . DIRECTORY_SEPARATOR . 'sql_' . uniqid();
@mkdir($outputDir, 0755, true);

$scripts = [
    'activities' => ['excel_to_sql.py', $tempFile, $outputDir . DIRECTORY_SEPARATOR . 'activities.sql', 'activities.sql'],
    'hotels' => ['hotels_excel_to_sql.py', $tempFile, $outputDir, null],
    'transfers' => ['transfers_to_json.py', $tempFile, $outputDir . DIRECTORY_SEPARATOR . 'transfers.json', $outputDir],
];

$cmd = '';
$sqlFiles = [];
$filename = '';

switch ($type) {
    case 'activities':
        $cmd = sprintf(
            '%s %s %s %s 2>&1',
            escapeshellarg($python),
            escapeshellarg($root . DIRECTORY_SEPARATOR . $scripts['activities'][0]),
            escapeshellarg($scripts['activities'][1]),
            escapeshellarg($scripts['activities'][2])
        );
        $sqlFiles = [$scripts['activities'][2]];
        $filename = 'tour_azerbaijan_activities_2026.sql';
        break;
    case 'hotels':
        $cmd = sprintf(
            '%s %s %s %s 2>&1',
            escapeshellarg($python),
            escapeshellarg($root . DIRECTORY_SEPARATOR . $scripts['hotels'][0]),
            escapeshellarg($tempFile),
            escapeshellarg($outputDir)
        );
        $sqlFiles = [
            $outputDir . DIRECTORY_SEPARATOR . '005_seed_hotels.sql',
            $outputDir . DIRECTORY_SEPARATOR . '012_seed_hotel_prices.sql',
        ];
        $filename = 'hotels_combined.sql';
        break;
    case 'transfers':
        $cmd = sprintf(
            '%s %s %s %s %s 2>&1',
            escapeshellarg($python),
            escapeshellarg($root . DIRECTORY_SEPARATOR . 'transfers_to_json.py'),
            escapeshellarg($tempFile),
            escapeshellarg($outputDir . DIRECTORY_SEPARATOR . 'transfers.json'),
            escapeshellarg($outputDir)
        );
        $sqlFiles = [$outputDir . DIRECTORY_SEPARATOR . '007_seed_transfer_points_by_region.sql'];
        $filename = 'tour_transfer_points.sql';
        break;
}

$cwd = $root;
$output = [];
exec($cmd, $output, $exitCode);

// Əlavə olaraq Python scriptləri print edə bilər - onları göstərmək üçün log kimi saxlayırıq
$log = implode("\n", $output);

$sql = '';
foreach ($sqlFiles as $f) {
    if (is_file($f)) {
        $content = file_get_contents($f);
        if ($content !== false) {
            $sql .= $content . "\n\n";
        }
    }
}

// Təmizlik
@unlink($tempFile);
foreach (glob($outputDir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
    @unlink($f);
}
@rmdir($outputDir);

if ($sql === '' && $exitCode !== 0) {
    echo json_encode([
        'ok' => false,
        'error' => 'SQL generasiya edilə bilmədi. Python xətası: ' . ($log ?: 'bilinmir'),
    ]);
    exit;
}

if ($sql === '') {
    echo json_encode(['ok' => false, 'error' => 'Heç bir SQL məlumatı alınmadı. Excel formatını yoxlayın.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'sql' => $sql,
    'filename' => $filename,
    'log' => $log ?: null,
]);
