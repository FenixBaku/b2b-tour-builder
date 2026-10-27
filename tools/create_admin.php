<?php
/**
 * İlk admin istifadəçisini yaradır və ya mövcud istifadəçini admin edib parolunu yeniləyir (yalnız CLI).
 *
 * İstifadə (cPanel → Terminal və ya SSH):
 *   php tools/create_admin.php admin@example.com
 * Parol interaktiv soruşulur (ekranda görünmür və tarixçəyə yazılmır).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/config.php';

$email = trim($argv[1] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "İstifadə: php tools/create_admin.php admin@example.com\n");
    exit(1);
}

function read_secret($prompt) {
    fwrite(STDOUT, $prompt);
    if (DIRECTORY_SEPARATOR === '/') {
        system('stty -echo');
        $value = trim((string) fgets(STDIN));
        system('stty echo');
        fwrite(STDOUT, "\n");
        return $value;
    }
    return trim((string) fgets(STDIN));
}

$password = read_secret('Parol (ən azı 8 simvol): ');
$password2 = read_secret('Parolu təkrarlayın: ');
if (strlen($password) < 8 || $password !== $password2) {
    fwrite(STDERR, "Parol ən azı 8 simvol olmalı və təkrarla eyni olmalıdır.\n");
    exit(1);
}

$pdo = dbConnect();
if (!$pdo) {
    fwrite(STDERR, "DB bağlantısı alınmadı (config.local.php-ni yoxlayın).\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
$id = $stmt->fetchColumn();
if ($id) {
    $pdo->prepare('UPDATE users SET password_hash = ?, admin_status = 1 WHERE id = ?')->execute([$hash, $id]);
    echo "Mövcud istifadəçi admin edildi və parolu yeniləndi: {$email}\n";
} else {
    $pdo->prepare("INSERT INTO users (email, password_hash, name, admin_status) VALUES (?, ?, 'Admin', 1)")->execute([$email, $hash]);
    echo "Admin yaradıldı: {$email}\n";
}
