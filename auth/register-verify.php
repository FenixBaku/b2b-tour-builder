<?php
require_once __DIR__ . '/_auth_helper.php';
require_once __DIR__ . '/_mail_helper.php';
auth_start_session();

$error = '';
$resend_ok = false;
$redirect = auth_safe_redirect($_GET['redirect'] ?? '', '');
$pending = $_SESSION['register_pending'] ?? null;

const OTP_TTL_MINUTES = 10;
const OTP_MAX_ATTEMPTS = 5;
const OTP_RESEND_COOLDOWN = 60;

if (auth_user_logged_in() && !auth_is_admin()) {
    header('Location: ' . ($redirect ?: '/bb/tour-builder.php'));
    exit;
}

if (!$pending || empty($pending['email'])) {
    header('Location: /bb/auth/register.php' . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
    exit;
}

// Eyni email ilə hesab artıq varsa – qeydiyyat icazə vermə, loginə yönləndir
require_once __DIR__ . '/../config.php';
$pdo = dbConnect();
if ($pdo) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$pending['email']]);
    if ($stmt->fetch()) {
        unset($_SESSION['register_pending']);
        header('Location: /bb/auth/login.php?msg=already_registered' . ($redirect ? '&redirect=' . urlencode($redirect) : ''));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend') {
    $wait = OTP_RESEND_COOLDOWN - (time() - (int)($_SESSION['otp_last_sent'] ?? 0));
    if ($wait > 0) {
        $error = 'Please wait ' . $wait . ' seconds before requesting a new code.';
    } elseif ($pdo) {
        $otp = (string)random_int(100000, 999999);
        // Yalnız ən son kod etibarlıdır
        $pdo->prepare("DELETE FROM otp_codes WHERE email = ?")->execute([$pending['email']]);
        $stmt = $pdo->prepare("INSERT INTO otp_codes (email, code) VALUES (?, ?)");
        $stmt->execute([$pending['email'], $otp]);
        send_otp_email($pending['email'], $otp);
        $_SESSION['otp_last_sent'] = time();
        $_SESSION['otp_attempts'] = 0;
        $resend_ok = true;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = isset($_POST['otp']) ? trim(preg_replace('/\D/', '', $_POST['otp'])) : '';

    if ((int)($_SESSION['otp_attempts'] ?? 0) >= OTP_MAX_ATTEMPTS) {
        $error = 'Too many incorrect attempts. Please request a new code.';
    } elseif (strlen($otp) < 6) {
        $error = 'Please enter the 6-digit code.';
    } else {
        if (!$pdo) {
            $error = 'Database error.';
        } else {
            // Yalnız ən son göndərilən, müddəti bitməmiş kod qəbul olunur
            $stmt = $pdo->prepare("SELECT code, created_at > (NOW() - INTERVAL " . OTP_TTL_MINUTES . " MINUTE) AS fresh FROM otp_codes WHERE email = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$pending['email']]);
            $latest = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$latest || !hash_equals((string)$latest['code'], $otp)) {
                $_SESSION['otp_attempts'] = (int)($_SESSION['otp_attempts'] ?? 0) + 1;
                $left = OTP_MAX_ATTEMPTS - $_SESSION['otp_attempts'];
                $error = $left > 0
                    ? 'Incorrect code. ' . $left . ' attempt(s) left.'
                    : 'Too many incorrect attempts. Please request a new code.';
            } elseif (!(int)$latest['fresh']) {
                $error = 'This code has expired. Please request a new one.';
            } else {
                unset($_SESSION['otp_attempts'], $_SESSION['otp_last_sent']);
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?)");
                    $stmt->execute([$pending['email'], $pending['password_hash'], $pending['name'] ?: null]);
                    unset($_SESSION['register_pending']);
                    $pdo->prepare("DELETE FROM otp_codes WHERE email = ?")->execute([$pending['email']]);
                    $loc = '/bb/auth/login.php?registered=1';
                    if (!empty($pending['redirect'])) $loc .= '&redirect=' . urlencode($pending['redirect']);
                    header('Location: ' . $loc);
                    exit;
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) $error = 'This email is already registered.';
                    else $error = 'An error occurred. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Email verification | B2B Tour Builder</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Montserrat', sans-serif; }
        .auth-card { box-shadow: 0 25px 50px -12px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="auth-card bg-white rounded-2xl p-8 border border-slate-100">
            <h1 class="text-2xl font-bold text-slate-800 text-center mb-2">Email verification</h1>
            <p class="text-slate-500 text-sm text-center mb-6">
                Enter the 6-digit code that was sent to <strong><?= htmlspecialchars($pending['email']) ?></strong>.
            </p>
            <?php if ($resend_ok): ?>
                <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm">A new code has been sent to your email.</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" action="" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">OTP code</label>
                    <input type="text" name="otp" required autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                        class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-center text-2xl tracking-[0.5em] font-mono"
                        placeholder="000000" autofocus />
                </div>
                <button type="submit" class="w-full py-3 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold transition">
                    Verify
                </button>
            </form>
            <form method="post" class="mt-6 text-center">
                <input type="hidden" name="action" value="resend" />
                <button type="submit" class="text-sm text-blue-600 hover:underline font-medium">Didn't receive a code? Resend (no limit)</button>
            </form>
            <p class="mt-2 text-center text-sm">
                <a href="login.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>" class="text-slate-500 hover:text-slate-700">← Login</a>
            </p>
        </div>
    </div>
</body>
</html>
