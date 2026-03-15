<?php
// ============================================================
// ajax_filter.php — AJAX filter endpoint (v4 — new table design)
// ============================================================
header('Content-Type: application/json; charset=utf-8');
require_once 'config/db.php';
require_once 'config/courses_helper.php';

$all_codes = array_keys(get_course_codes($conn));

$filter_course  = trim($_GET['course']      ?? '');
$filter_status  = $_GET['status']           ?? '';
$filter_date    = trim($_GET['adm_date']    ?? '');
$search_name    = trim($_GET['search_name'] ?? '');
$fee_filter     = trim($_GET['fee_filter']  ?? '');

if (!in_array($filter_course, $all_codes))           $filter_course = '';
if (!in_array($filter_status, ['','0','1']))          $filter_status = '';
if (!in_array($fee_filter,    ['','due','paid']))     $fee_filter    = '';

$where  = [];
$types  = '';
$values = [];

if ($filter_course !== '') { $where[] = 's.course = ?';         $types .= 's'; $values[] = $filter_course; }
if ($filter_status !== '') { $where[] = 's.is_active = ?';      $types .= 'i'; $values[] = (int)$filter_status; }
if ($filter_date   !== '' && strtotime($filter_date))
                           { $where[] = 's.admission_date = ?'; $types .= 's'; $values[] = $filter_date; }
if ($search_name   !== '') { $where[] = '(s.name LIKE ? OR s.roll_no LIKE ?)';
                             $types .= 'ss'; $like = "%$search_name%"; $values[] = $like; $values[] = $like; }
if ($fee_filter === 'due') { $where[] = 'f.due_amount > 0'; }
if ($fee_filter === 'paid'){ $where[] = '(f.due_amount = 0 OR f.due_amount IS NULL)'; }

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT s.*, f.due_amount FROM students s LEFT JOIN fees f ON s.id = f.student_id $where_sql ORDER BY s.name ASC";

$stmt = mysqli_prepare($conn, $sql);
if ($types) mysqli_stmt_bind_param($stmt, $types, ...$values);
mysqli_stmt_execute($stmt);
$result   = mysqli_stmt_get_result($stmt);
$students = [];
while ($row = mysqli_fetch_assoc($result)) $students[] = $row;
$total = count($students);

ob_start();
if ($students) {
    foreach ($students as $i => $s) {
        $due       = (float)($s['due_amount'] ?? 0);
        $initial   = htmlspecialchars(strtoupper(mb_substr($s['name'], 0, 1)), ENT_QUOTES);
        $name      = htmlspecialchars($s['name'],    ENT_QUOTES);
        $roll_html = htmlspecialchars($s['roll_no'],  ENT_QUOTES);
        $course    = htmlspecialchars($s['course'],   ENT_QUOTES);
        $roll_enc  = htmlspecialchars(urlencode($s['roll_no']), ENT_QUOTES);
        $adm       = date('d M Y', strtotime($s['admission_date']));
        $due_cls   = $due > 0 ? 'va-due--red' : 'va-due--green';
        $due_fmt   = '&#8377;' . number_format($due);
        $st_cls    = $s['is_active'] ? 'va-status--active' : 'va-status--inactive';
        $st_txt    = $s['is_active'] ? 'Active' : 'Inactive';
        $del_btn   = $s['is_active']
            ? "<a href='delete.php?roll_no={$roll_enc}' class='va-btn va-btn--del confirm-delete' title='Deactivate'>&#10005;</a>"
            : '';
        echo "<tr class='va-row'>
            <td class='va-td-num'>" . ($i+1) . "</td>
            <td class='va-td-student'>
                <div class='va-avatar'>{$initial}</div>
                <div class='va-student-info'>
                    <span class='va-name'>{$name}</span>
                    <span class='va-roll'>{$roll_html}</span>
                </div>
            </td>
            <td><span class='va-course'>{$course}</span></td>
            <td><span class='va-due {$due_cls}'>{$due_fmt}</span></td>
            <td class='va-td-date va-hide-sm'>{$adm}</td>
            <td><span class='va-status {$st_cls}'>{$st_txt}</span></td>
            <td class='va-td-actions'>
                <a href='view.php?roll_no={$roll_enc}'   class='va-btn va-btn--view' title='View'>&#128065;</a>
                <a href='update.php?roll_no={$roll_enc}' class='va-btn va-btn--edit' title='Edit'>&#9998;</a>
                <a href='fees.php?roll_no={$roll_enc}'   class='va-btn va-btn--fee'  title='Pay'>&#8377;</a>
                {$del_btn}
            </td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='7' class='va-empty'><div>&#127891;</div><p>No students match your filters.</p></td></tr>";
}
$html = ob_get_clean();

echo json_encode(['html' => $html, 'total' => $total]);
mysqli_stmt_close($stmt);
mysqli_close($conn);
