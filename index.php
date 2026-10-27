<?php
require_once __DIR__ . '/auth/_auth_helper.php';
auth_start_session();
if (auth_user_logged_in()) {
    header('Location: /bb/tour-builder.php');
} else {
    header('Location: /bb/auth/login.php');
}
exit;
