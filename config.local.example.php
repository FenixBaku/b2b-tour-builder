<?php
/**
 * Server konfiqurasiyası — bu faylı config.local.php adı ilə kopyalayın və doldurun.
 * config.local.php git-ə düşmür (.gitignore). Parolları heç vaxt repoya yazmayın.
 */

// Verilənlər bazası (cPanel → MySQL Databases)
define('DB_HOST', 'localhost');
define('DB_NAME', 'cpaneluser_b2b');
define('DB_USER', 'cpaneluser_b2b');
define('DB_PASS', 'change-me');

// SMTP — qeydiyyat OTP və bildiriş emailləri (cPanel → Email Accounts)
define('SMTP_HOST', 'mail.example.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'noreply@example.com');
define('SMTP_PASS', 'change-me');
define('SMTP_FROM', 'noreply@example.com');
define('SMTP_FROM_NAME', 'B2B Tour Builder');
