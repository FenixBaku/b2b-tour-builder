<?php
/**
 * Təkrar işlədilə bilən miqrasiyaları icra edir (CREATE TABLE IF NOT EXISTS, şəhər kodları).
 * CLI: php migrations/run_migrations.php
 * Brauzer: /bb/migrations/run_migrations.php (yalnız admin)
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/excel_import_native.php';

if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/../auth/_auth_helper.php';
    auth_require_admin_api();
    header('Content-Type: text/plain; charset=utf-8');
}

$pdo = dbConnect();
if (!$pdo) {
    http_response_code(500);
    echo "DB bağlantısı alınmadı\n";
    exit(1);
}

foreach (['020_excel_import_schema.sql', '021_city_codes.sql', '022_meal_prices_unique.sql'] as $file) {
    $errors = excel_import_native_run_sql_file($pdo, __DIR__ . '/' . $file);
    echo "OK: {$file}\n";
    foreach ($errors as $err) {
        // Təkrar işlədilmədə gözlənilən xətalar (məs: "Duplicate key name", "doesn't exist") məlumat üçündür
        echo "   qeyd: {$err}\n";
    }
}
