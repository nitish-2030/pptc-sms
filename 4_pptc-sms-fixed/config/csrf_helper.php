<?php
// ============================================================
// config/csrf_helper.php — CSRF Token Helpers
// ============================================================

/**
 * Generate CSRF token and store in session.
 */
function csrf_generate(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from POST. Dies on failure.
 */
function csrf_validate(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:2rem;color:#8B0000;">❌ Invalid CSRF token. Please go back and try again.</div>');
    }
    // Rotate token after use
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Output a hidden CSRF field for use in forms.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_generate()) . '">';
}
