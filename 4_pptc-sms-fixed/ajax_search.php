<?php
// ============================================================
// ajax_search.php — AJAX endpoint: search student by Roll No
// Returns JSON. Used by dashboard.php search box.
// ============================================================
header('Content-Type: application/json; charset=utf-8');
require_once 'config/db.php';

$roll_no = trim($_GET['roll_no'] ?? '');

if ($roll_no === '') {
    echo json_encode(['found' => false, 'error' => 'No roll number provided.']);
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE roll_no = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $roll_no);
mysqli_stmt_execute($stmt);
$result  = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if ($student) {
    // Format date for display
    $student['admission_date_fmt'] = date('d M Y', strtotime($student['admission_date']));
    echo json_encode(['found' => true, 'student' => $student]);
} else {
    echo json_encode(['found' => false]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
