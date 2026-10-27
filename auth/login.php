<?php
require_once __DIR__ . '/_auth_helper.php';
auth_start_session();

$error = '';
$redirect = auth_safe_redirect($_GET['redirect'] ?? '', '');
$registered = isset($_GET['registered']) && $_GET['registered'] === '1';
$already_registered = isset($_GET['msg']) && $_GET['msg'] === 'already_registered';

if (auth_user_logged_in()) {
    header('Location: ' . ($redirect ?: '/bb/tour-builder.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        require_once __DIR__ . '/../config.php';
        $pdo = dbConnect();
        if (!$pdo) {
            $error = 'Database error.';
        } else {
            $lockSeconds = auth_login_lock_seconds($pdo, $email);
            if ($lockSeconds > 0) {
                $error = 'Too many failed attempts. Please try again in ' . ceil($lockSeconds / 60) . ' minute(s).';
            } else {
                $stmt = $pdo->prepare("SELECT id, email, name, password_hash, COALESCE(admin_status, 0) AS admin_status FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$user || !password_verify($password, $user['password_hash'])) {
                    auth_login_record_failure($pdo, $email);
                    $error = 'Incorrect email or password.';
                } else {
                    auth_login_clear_failures($pdo, $email);
                    auth_login_user($user);
                    header('Location: ' . ($redirect ?: '/bb/tour-builder.php'));
                    exit;
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
    <title>Login | Destinations Azerbaijan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Montserrat', sans-serif; }
        .auth-card { box-shadow: 0 25px 50px -12px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="auth-card bg-white rounded-2xl p-8 border border-slate-100" role="main">
            <h1 class="text-2xl font-bold text-slate-800 text-center mb-2">Login</h1>
            <p class="text-slate-500 text-sm text-center mb-6">Sign in to Destinations Azerbaijan B2B Tour Builder</p>
            <?php if ($registered): ?>
                <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm" role="status">Registration completed successfully. Please log in.</div>
            <?php endif; ?>
            <?php if ($already_registered): ?>
                <div class="mb-4 p-3 rounded-lg bg-blue-50 text-blue-700 text-sm" role="status">An account with this email already exists. Please log in.</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" action="" class="space-y-4" novalidate>
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input id="email" type="email" name="email" required autocomplete="email"
                        class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        placeholder="email@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                        class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        placeholder="••••••••" />
                </div>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>" />
                <button type="submit" class="w-full py-3 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold transition">Sign in</button>
            </form>
            <p class="mt-6 text-center text-sm text-slate-500">
                <a href="/bb/tour-builder.php" class="text-slate-500 hover:text-slate-700">← Tour Builder</a>
            </p>
        </div>
    </div>
</body>
</html>
