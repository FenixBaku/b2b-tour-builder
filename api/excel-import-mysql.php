<?php
/**
 * Excel → MySQL import API
 * POST multipart: files[] və ya action=scan
 */
@ini_set('display_errors', '0');
@set_time_limit(600);
@ini_set('max_execution_time', '600');
ob_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit;
}

function excel_import_json_response($data, $httpCode = 200) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../auth/_auth_helper.php';
require_once __DIR__ . '/../lib/excel_import.php';

auth_require_admin_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    excel_import_json_response(['ok' => false, 'error' => 'Yalnız POST'], 405);
}

$action = isset($_POST['action']) ? trim($_POST['action']) : 'upload';
$requestedType = isset($_POST['type']) ? trim($_POST['type']) : '';
$validTypes = ['activities', 'hotels', 'transfers', 'meals'];
if ($requestedType !== '' && !in_array($requestedType, $validTypes, true)) {
    excel_import_json_response(['ok' => false, 'error' => 'Keçərsiz import tipi'], 422);
}
$paths = [];

if ($action === 'scan') {
    try {
        excel_import_json_response(excel_import_scan_project());
    } catch (Throwable $e) {
        excel_import_json_response(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}

if ($action === 'list') {
    $files = excel_import_find_xlsx();
    $list = [];
    foreach ($files as $p) {
        $list[] = [
            'path' => $p,
            'name' => basename($p),
            'type' => excel_import_detect_type(basename($p)),
            'size' => filesize($p),
            'mtime' => filemtime($p),
        ];
    }
    excel_import_json_response(['ok' => true, 'files' => $list]);
}

$uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'xlsx_uploads';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

if (!empty($_FILES['files'])) {
    $names = $_FILES['files']['name'];
    $tmps = $_FILES['files']['tmp_name'];
    $errs = $_FILES['files']['error'];
    if (!is_array($names)) {
        $names = [$names];
        $tmps = [$tmps];
        $errs = [$errs];
    }
    foreach ($names as $i => $name) {
        if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') {
            continue;
        }
        $safe = preg_replace('/[^a-zA-Z0-9._\- ]/', '_', basename($name));
        // Type is included in the stored name so both PHP and Python engines use
        // the section selected in admin instead of relying on the user's filename.
        $typePrefix = $requestedType !== '' ? $requestedType . '_' : '';
        $dest = $uploadDir . DIRECTORY_SEPARATOR . date('Ymd_His') . '_' . $typePrefix . $safe;
        if (move_uploaded_file($tmps[$i], $dest)) {
            $paths[] = $dest;
        }
    }
}

if ($paths === []) {
    excel_import_json_response(['ok' => false, 'error' => 'Yüklənmiş .xlsx faylı yoxdur']);
}

try {
    // Transfer: "faylda olmayan duty-ləri sil" — DB qiymət cədvəli ilə tam eyni olsun
    $options = ['remove_missing_duties' => ($requestedType === 'transfers' && !empty($_POST['remove_missing_duties']))];
    excel_import_json_response(excel_import_to_mysql($paths, $options));
} catch (Throwable $e) {
    excel_import_json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
