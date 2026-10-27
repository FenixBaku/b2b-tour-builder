<?php
/**
 * Konfiqurasiya — B2B Tour Builder
 *
 * Serverə xas dəyərlər (DB, SMTP parolu) config.local.php-də saxlanılır və git-ə düşmür.
 * Yeni serverdə: config.local.example.php → config.local.php kopyalayın və doldurun.
 * config.local.php yoxdursa, aşağıdakı default-lar (lokal XAMPP) işləyir.
 */

// CLI (testlər, skriptlər) SERVER_NAME göndərmir — lokal sayılır.
$serverName = $_SERVER['SERVER_NAME'] ?? (PHP_SAPI === 'cli' ? 'localhost' : '');
$is_localhost = ($serverName === 'localhost' || $serverName === '127.0.0.1');

if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

// Verilənlər bazası (default: lokal XAMPP)
defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_NAME') || define('DB_NAME', 'bb_tour_builder');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '');
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

// SMTP (qeydiyyat OTP və bildiriş emailləri). Boşdursa email göndərilmir.
defined('SMTP_HOST') || define('SMTP_HOST', '');
defined('SMTP_PORT') || define('SMTP_PORT', 465);
defined('SMTP_USER') || define('SMTP_USER', '');
defined('SMTP_PASS') || define('SMTP_PASS', '');
defined('SMTP_FROM') || define('SMTP_FROM', SMTP_USER);
defined('SMTP_FROM_NAME') || define('SMTP_FROM_NAME', 'B2B Tour Builder');

/**
 * Köhnə Python Excel import skripti üçün python.exe yolu (default PHP import istifadə olunur).
 * Boşdursa lib/excel_import.php avtomatik tapmağa çalışır.
 */
defined('PYTHON_BIN') || define('PYTHON_BIN', '');

/**
 * Excel import: default PHP (PhpSpreadsheet) — duty və şəhər kodları burada dəstəklənir.
 * Köhnə Python skripti üçün config.local.php-də: define('EXCEL_IMPORT_ENGINE', 'python');
 */

function dbConnect() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null; // Return null so we can check it
    }
}
