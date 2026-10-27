<?php
/**
 * Lokal test istifadəçisi yaradır / parolunu yeniləyir (yalnız CLI və lokal DB üçün).
 * İstifadə: php tests/seed_test_user.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/config.php';

if (empty($is_localhost)) {
    fwrite(STDERR, "Yalnız lokal mühitdə işləyir.\n");
    exit(1);
}

$pdo = dbConnect();
if (!$pdo) {
    fwrite(STDERR, "DB bağlantısı alınmadı.\n");
    exit(1);
}

$email = 'test-admin@bb.local';
$password = bin2hex(random_bytes(6));
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
$id = $stmt->fetchColumn();
if ($id) {
    $pdo->prepare('UPDATE users SET password_hash = ?, admin_status = 1 WHERE id = ?')->execute([$hash, $id]);
} else {
    $pdo->prepare("INSERT INTO users (email, password_hash, name, company, admin_status) VALUES (?, ?, 'Test Admin', 'Local', 1)")
        ->execute([$email, $hash]);
}

// Giriş məlumatı lokal fayla yazılır (.gitignore-dadır, repoya düşmür)
$credFile = __DIR__ . '/test_user.local.txt';
file_put_contents($credFile, "URL: http://localhost/bb/auth/login.php\nEmail: {$email}\nPassword: {$password}\n");
echo "Test admin hazırdır. Giriş məlumatı: {$credFile}\n";
