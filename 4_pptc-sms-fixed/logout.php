<?php
// ============================================================
// logout.php — Admin Logout (v4 — with activity log)
// ============================================================
require_once 'config/auth_check.php';
require_once 'config/db.php';
require_once 'config/activity_helper.php';

log_activity($conn, 'admin', 'Admin logged out', '');

session_destroy();
header('Location: login.php');
exit;
