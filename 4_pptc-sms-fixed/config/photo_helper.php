<?php
// ============================================================
// config/photo_helper.php
// Shared helpers: upload photo, render avatar HTML
// ============================================================

define('UPLOAD_DIR',  __DIR__ . '/../assets/uploads/students/');
define('UPLOAD_URL',  'assets/uploads/students/');
define('MAX_SIZE',    2 * 1024 * 1024); // 2 MB
define('ALLOWED_EXT', ['jpg','jpeg','png','webp']);

/**
 * Handle photo upload. Returns filename on success or '' on skip/fail.
 * $error_ref is passed by reference to collect error message.
 */
function handle_photo_upload(array $file, string &$error_ref): string {
    // No file chosen — skip silently
    if (!isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE || $file['tmp_name'] === '') {
        return '';
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_ref = 'Upload error code: ' . $file['error'];
        return '';
    }

    // Size check
    if ($file['size'] > MAX_SIZE) {
        $error_ref = 'Photo must be under 2 MB.';
        return '';
    }

    // Extension check
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXT)) {
        $error_ref = 'Only JPG, PNG or WEBP photos allowed.';
        return '';
    }

    // MIME check (basic)
    $mime = mime_content_type($file['tmp_name']);
    if (!str_starts_with($mime, 'image/')) {
        $error_ref = 'Uploaded file is not a valid image.';
        return '';
    }

    // Create upload dir if missing
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Unique filename
    $filename = uniqid('stu_', true) . '.' . $ext;
    $dest     = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $error_ref = 'Could not save photo. Check folder permissions.';
        return '';
    }

    return $filename;
}

/**
 * Delete old photo file from disk safely.
 */
function delete_photo(string $filename): void {
    if ($filename && $filename !== '') {
        $path = UPLOAD_DIR . basename($filename);
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}

/**
 * Render avatar HTML:
 *   - If photo exists → <img> circle
 *   - Else          → letter circle div
 *
 * $size: 'sm' (36px) | 'md' (80px) | 'lg' (100px)
 * $base: base URL prefix ('' for same-dir, '../' for subdir)
 */
function render_avatar(string $photo, string $name, string $size = 'md', string $base = ''): string {
    $letter = strtoupper(mb_substr($name, 0, 1));

    if ($photo && file_exists(UPLOAD_DIR . $photo)) {
        $src = htmlspecialchars($base . UPLOAD_URL . $photo, ENT_QUOTES);
        $alt = htmlspecialchars($name, ENT_QUOTES);
        $cls = match($size) {
            'lg'    => 'avatar-photo-lg',
            'sm'    => 'row-avatar',
            default => 'avatar-photo',
        };
        return "<img src=\"{$src}\" alt=\"{$alt}\" class=\"{$cls}\">";
    }

    // Letter fallback
    if ($size === 'sm') {
        return "<span class=\"row-avatar-letter\">{$letter}</span>";
    }

    // md / lg — the existing .detail-avatar style, enlarged for lg
    $extra = $size === 'lg' ? 'width:100px;height:100px;font-size:2.5rem;' : '';
    return "<div class=\"detail-avatar\" style=\"{$extra}\">{$letter}</div>";
}
