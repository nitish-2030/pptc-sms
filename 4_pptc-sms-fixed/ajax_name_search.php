<?php
// ============================================================
// ajax_name_search.php — Live search by name OR roll no
// Returns JSON array of matching students (max 8)
// Used by fees.php, update.php, delete.php search boxes
// ============================================================
header('Content-Type: application/json; charset=utf-8');
require_once 'config/db.php';

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

// Match against both name (LIKE) and roll_no (LIKE)
$like = '%' . $q . '%';
$stmt = mysqli_prepare($conn,
    "SELECT roll_no, name, course, is_active
     FROM students
     WHERE (name LIKE ? OR roll_no LIKE ?)
     ORDER BY is_active DESC, name ASC
     LIMIT 8"
);
mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
mysqli_stmt_execute($stmt);
$res     = mysqli_stmt_get_result($stmt);
$results = [];
while ($row = mysqli_fetch_assoc($res)) {
    $results[] = [
        'roll_no'   => $row['roll_no'],
        'name'      => $row['name'],
        'course'    => $row['course'],
        'is_active' => (int)$row['is_active'],
    ];
}

echo json_encode($results);
mysqli_stmt_close($stmt);
mysqli_close($conn);
