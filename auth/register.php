<?php
require_once __DIR__ . '/_auth_helper.php';
require_once __DIR__ . '/_mail_helper.php';
auth_start_session();

$only_admin_can_register = true;

$error = '';
$redirect = auth_safe_redirect($_GET['redirect'] ?? ($_POST['redirect'] ?? ''), '');

if ($only_admin_can_register && !auth_is_admin()) {
    header('Location: /bb/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password2 = isset($_POST['password2']) ? $_POST['password2'] : '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } else {
        require_once __DIR__ . '/../config.php';
        $pdo = dbConnect();
        if (!$pdo) {
            $error = 'Database error.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'This email is already registered.';
                } else {
                    $otp = (string)random_int(100000, 999999);
                    $pdo->prepare("DELETE FROM otp_codes WHERE email = ?")->execute([$email]);
                    $stmt = $pdo->prepare("INSERT INTO otp_codes (email, code) VALUES (?, ?)");
                    $stmt->execute([$email, $otp]);
                    $sent = send_otp_email($email, $otp);
                    $_SESSION['otp_last_sent'] = time();
                    $_SESSION['otp_attempts'] = 0;
                    $_SESSION['register_pending'] = [
                        'email' => $email,
                        'name' => $name,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'redirect' => $redirect,
                    ];
                    $loc = '/bb/auth/register-verify.php';
                    if ($redirect) $loc .= '?redirect=' . urlencode($redirect);
                    header('Location: ' . $loc);
                    exit;
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) $error = 'This email is already registered.';
                else $error = 'An error occurred. Please try again.';
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
    <title>Register | B2B Tour Builder</title>
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
            <h1 class="text-2xl font-bold text-slate-800 text-center mb-2">Register</h1>
            <p class="text-slate-500 text-sm text-center mb-6">Create a new account</p>
            <?php if ($error): ?>
                <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" action="" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
                    <input type="email" name="email" required autocomplete="email"
                        class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        placeholder="email@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                    <input type="text" name="name" autocomplete="name"
                        class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        placeholder="Your name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password *</label>
                    <input type="password" name="password" required autocomplete="new-password" minlength="6"
                        class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        placeholder="At least 6 characters" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Repeat password</label>
                    <input type="password" name="password2" required autocomplete="new-password"
                        class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        placeholder="Repeat the password" />
                </div>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>" />
                <button type="submit" class="w-full py-3 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold transition">
                    Register
                </button>
            </form>
            <p class="mt-6 text-center text-sm text-slate-500">
                Already have an account? <a href="login.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>" class="text-blue-600 hover:underline font-medium">Login</a>
            </p>
            <p class="mt-2 text-center text-sm">
                <a href="/bb/tour-builder.php" class="text-slate-500 hover:text-slate-700">← Tour Builder</a>
            </p>
        </div>
    </div>
</body>
</html>
