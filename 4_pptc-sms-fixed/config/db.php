<?php
// ============================================
// config/db.php — Database Connection
// ============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // Change to your MySQL username
define('DB_PASS', '');             // Change to your MySQL password
define('DB_NAME', 'student_db');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die('<div class="alert alert-error">Database connection failed: ' . mysqli_connect_error() . '</div>');
}

mysqli_set_charset($conn, 'utf8mb4');
