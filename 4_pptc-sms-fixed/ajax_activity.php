<?php
// ============================================================
// ajax_activity.php — AJAX endpoint for activity feed
// Returns JSON array of logs, filtered by category
// ============================================================
header('Content-Type: application/json; charset=utf-8');
require_once 'config/auth_check.php';
require_once 'config/db.php';

$category = trim($_GET['category'] ?? 'all');
$limit    = min((int)($_GET['limit'] ?? 30), 100);
$offset   = max((int)($_GET['offset'] ?? 0), 0);

$allowed = ['all','student','admin','email','system'];
if (!in_array($category, $allowed)) $category = 'all';

$where = $category !== 'all' ? "WHERE category = '" . mysqli_real_escape_string($conn, $category) . "'" : '';

$rows = [];
$res  = mysqli_query($conn,
    "SELECT * FROM activity_logs $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset"
);
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $rows[] = [
            'id'           => (int)$r['id'],
            'category'     => $r['category'],
            'action'       => $r['action'],
            'detail'       => $r['detail'],
            'student_name' => $r['student_name'],
            'student_id'   => $r['student_id'],
            'icon'         => $r['icon'],
            'color'        => $r['color'],
            'time_raw'     => $r['created_at'],
            'time_fmt'     => date('d M Y, h:i A', strtotime($r['created_at'])),
            'time_ago'     => time_ago($r['created_at']),
        ];
    }
}

// Count per category
$counts = [];
$cr = mysqli_query($conn, "SELECT category, COUNT(*) AS c FROM activity_logs GROUP BY category");
if ($cr) while ($r = mysqli_fetch_assoc($cr)) $counts[$r['category']] = (int)$r['c'];
$counts['all'] = array_sum($counts);

echo json_encode(['logs' => $rows, 'counts' => $counts]);

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)        return 'just now';
    if ($diff < 3600)      return floor($diff/60) . 'm ago';
    if ($diff < 86400)     return floor($diff/3600) . 'h ago';
    if ($diff < 604800)    return floor($diff/86400) . 'd ago';
    return date('d M', strtotime($datetime));
}
