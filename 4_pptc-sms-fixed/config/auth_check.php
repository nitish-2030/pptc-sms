<?php
// ============================================================
// config/auth_check.php — Auth + CSRF session init
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout — 2 hours
$timeout = 2 * 60 * 60;

if (
    !isset($_SESSION['pptc_admin_logged_in']) ||
    $_SESSION['pptc_admin_logged_in'] !== true ||
    (time() - ($_SESSION['pptc_login_time'] ?? 0)) > $timeout
) {
    session_destroy();
    header('Location: ' . (isset($baseUrl) ? $baseUrl : '') . 'login.php?reason=auth');
    exit;
}

// Refresh activity time
$_SESSION['pptc_login_time'] = time();

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
