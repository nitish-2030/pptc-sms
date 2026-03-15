<?php
// ============================================================
// process_delete.php — Soft Delete Handler (v4 — with email + activity)
// ============================================================
require_once 'config/db.php';
require_once 'config/csrf_helper.php';
require_once 'config/email_helper.php';
require_once 'config/activity_helper.php';

csrf_validate();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: delete.php'); exit;
}

$id      = (int)($_POST['id']      ?? 0);
$roll_no = trim($_POST['roll_no']  ?? '');

if (!$id) {
    header('Location: delete.php?status=error&msg=Invalid+student+ID.'); exit;
}

// Fetch student before deactivating (need email + name)
$s_stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ?");
mysqli_stmt_bind_param($s_stmt, 'i', $id);
mysqli_stmt_execute($s_stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($s_stmt));

// Soft delete
$stmt = mysqli_prepare($conn, "UPDATE students SET is_active = 0 WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    // ── Feature 1 & 2: Email + Activity ──
    if ($student) {
        $email_result = send_email($conn, 'deactivated', $student, []);
        log_activity($conn, 'student',
            "{$student['name']} was deactivated",
            "Roll: {$student['roll_no']}",
            $id, $student['name']
        );
        log_activity($conn, 'email',
            "Deactivation email {$email_result['message']}",
            "Trigger: deactivated | To: ".($student['email']??'no email'),
            $id, $student['name']
        );
    }
    header('Location: delete.php?roll_no=' . urlencode($roll_no) . '&status=success');
} else {
    header('Location: delete.php?roll_no=' . urlencode($roll_no) . '&status=error&msg=' . urlencode(mysqli_stmt_error($stmt)));
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
