<?php
/**
 * Auth helper – session, giriş yoxlaması, admin yoxlaması
 */
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config.php';
}

function auth_start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

/** Uğurlu girişdən sonra: session id yenilənir (session fixation-a qarşı). */
function auth_login_user(array $user) {
    auth_start_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'] ?? $user['email'];
    $_SESSION['admin_status'] = (int)($user['admin_status'] ?? 0);
    $_SESSION['auth_checked_at'] = time();
}

/**
 * Yalnız saytın daxili ünvanına yönləndirmə (open redirect-ə qarşı).
 * "/bb/..." kimi nisbi yollar qəbul olunur; "//evil.com", "https://..." rədd edilir.
 */
function auth_safe_redirect($url, $default = '/bb/tour-builder.php') {
    $url = trim((string)$url);
    if ($url === '' || $url[0] !== '/' || (isset($url[1]) && ($url[1] === '/' || $url[1] === '\\')) || preg_match('/[\r\n]/', $url)) {
        return $default;
    }
    return $url;
}

/**
 * Giriş cəhdləri limiti: eyni email və ya IP üçün 15 dəqiqədə 5 uğursuz cəhd.
 * @return int bloklanmanın qalan saniyəsi (0 = icazə var)
 */
function auth_login_lock_seconds(PDO $pdo, $email) {
    auth_login_attempts_table($pdo);
    $stmt = $pdo->prepare(
        // Vaxt fərqi SQL-də hesablanır (PHP və MySQL saat qurşaqları fərqli ola bilər)
        "SELECT COUNT(*) AS fails, TIMESTAMPDIFF(SECOND, MAX(created_at), NOW()) AS since_last FROM login_attempts "
        . "WHERE (email = ? OR ip = ?) AND created_at > (NOW() - INTERVAL 15 MINUTE)"
    );
    $stmt->execute([strtolower(trim((string)$email)), $_SERVER['REMOTE_ADDR'] ?? '']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ((int)($row['fails'] ?? 0) < 5) return 0;
    return max(1, 15 * 60 - (int)$row['since_last']);
}

function auth_login_record_failure(PDO $pdo, $email) {
    auth_login_attempts_table($pdo);
    $pdo->prepare("INSERT INTO login_attempts (email, ip) VALUES (?, ?)")
        ->execute([strtolower(trim((string)$email)), $_SERVER['REMOTE_ADDR'] ?? '']);
}

function auth_login_clear_failures(PDO $pdo, $email) {
    auth_login_attempts_table($pdo);
    $pdo->prepare("DELETE FROM login_attempts WHERE email = ? OR created_at < (NOW() - INTERVAL 1 DAY)")
        ->execute([strtolower(trim((string)$email))]);
}

function auth_login_attempts_table(PDO $pdo) {
    static $done = false;
    if ($done) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(180) NOT NULL DEFAULT '',
        ip VARCHAR(45) NOT NULL DEFAULT '',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY email_time (email, created_at),
        KEY ip_time (ip, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $done = true;
}

function auth_user_logged_in() {
    auth_start_session();
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) return false;
    // Rol/hesab dəyişiklikləri (silinmə, admin statusunun alınması) 5 dəqiqə ərzində aktiv sessiyaya da tətbiq olunur
    if (time() - (int)($_SESSION['auth_checked_at'] ?? 0) > 300) {
        $pdo = function_exists('dbConnect') ? dbConnect() : null;
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT COALESCE(admin_status, 0) FROM users WHERE id = ?");
            $stmt->execute([(int)$_SESSION['user_id']]);
            $status = $stmt->fetchColumn();
            if ($status === false) {
                $_SESSION = [];
                return false;
            }
            $_SESSION['admin_status'] = (int)$status;
            $_SESSION['auth_checked_at'] = time();
        }
    }
    return true;
}

function auth_is_admin() {
    auth_start_session();
    return isset($_SESSION['admin_status']) && (int)$_SESSION['admin_status'] === 1;
}

/**
 * Admin assistant rolu: yalnız məhdud admin icazələri (Orders, Invoices & Debts, Users).
 * admin_status = 2 kimi istifadə edirik.
 */
function auth_is_admin_assistant() {
    auth_start_session();
    return isset($_SESSION['admin_status']) && (int)$_SESSION['admin_status'] === 2;
}

/**
 * Hem tam admin (1), həm də admin assistant (2) üçün ümumi yoxlama.
 */
function auth_is_admin_or_assistant() {
    auth_start_session();
    if (!isset($_SESSION['admin_status'])) return false;
    $st = (int)$_SESSION['admin_status'];
    return $st === 1 || $st === 2;
}

function auth_get_user() {
    auth_start_session();
    if (!auth_user_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'admin_status' => isset($_SESSION['admin_status']) ? (int)$_SESSION['admin_status'] : 0,
    ];
}

function auth_require_login($redirect_to = null) {
    if (!auth_user_logged_in()) {
        $url = $redirect_to ?: ('/bb/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
        header('Location: ' . $url);
        exit;
    }
}

function auth_require_login_api() {
    auth_start_session();
    if (!auth_user_logged_in()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Giriş tələb olunur']);
        exit;
    }
}

function auth_require_admin($redirect_to = null) {
    auth_require_login($redirect_to ?: '/bb/auth/login.php');
    if (!auth_is_admin()) {
        header('Location: ' . ($redirect_to ?: '/bb/tour-builder.php'));
        exit;
    }
}

/**
 * Admin və ya admin assistant üçün (panelə giriş, oxuma icazələri və müəyyən məhdud əməliyyatlar üçün).
 */
function auth_require_admin_or_assistant($redirect_to = null) {
    auth_require_login($redirect_to ?: '/bb/auth/login.php');
    if (!auth_is_admin_or_assistant()) {
        header('Location: ' . ($redirect_to ?: '/bb/tour-builder.php'));
        exit;
    }
}

function auth_require_admin_api() {
    auth_start_session();
    if (!auth_user_logged_in()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Giriş tələb olunur']);
        exit;
    }
    if (!auth_is_admin()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Admin icazəsi tələb olunur']);
        exit;
    }
}

/**
 * Admin və ya admin assistant üçün API yoxlaması.
 * Tam konfiqurasiya dəyişiklikləri üçün yenə də auth_require_admin_api istifadə olunmalıdır.
 */
function auth_require_admin_or_assistant_api() {
    auth_start_session();
    if (!auth_user_logged_in()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Giriş tələb olunur']);
        exit;
    }
    if (!auth_is_admin_or_assistant()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Admin və ya admin assistant icazəsi tələb olunur']);
        exit;
    }
}
