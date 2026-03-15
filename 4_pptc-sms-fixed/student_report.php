<?php
// ============================================================
// student_report.php — Visual Charts + Printable Student Report (v5 UI)
// ============================================================
require_once 'config/db.php';
require_once 'config/courses_helper.php';
$pageTitle = 'Student Report';
$baseUrl   = '';

$course_codes = get_course_codes($conn);

$f_course  = trim($_GET['course']  ?? '');
$f_status  = $_GET['status']       ?? '';
$f_gender  = trim($_GET['gender']  ?? '');
$f_cat     = trim($_GET['category'] ?? '');

$where  = ['1=1'];
$types  = '';
$vals   = [];
if ($f_course) { $where[] = 's.course = ?';    $types .= 's'; $vals[] = $f_course; }
if ($f_status !== '') { $where[] = 's.is_active = ?'; $types .= 'i'; $vals[] = (int)$f_status; }
if ($f_gender) { $where[] = 's.gender = ?';    $types .= 's'; $vals[] = $f_gender; }
if ($f_cat)    { $where[] = 's.category = ?';  $types .= 's'; $vals[] = $f_cat; }

$where_sql = implode(' AND ', $where);
$sql = "SELECT s.*, f.total_fee, f.paid_amount, f.due_amount, f.status AS fee_status
        FROM students s LEFT JOIN fees f ON s.id = f.student_id
        WHERE $where_sql ORDER BY s.name ASC";

$stmt = mysqli_prepare($conn, $sql);
if ($types) mysqli_stmt_bind_param($stmt, $types, ...$vals);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$students = [];
while ($row = mysqli_fetch_assoc($res)) $students[] = $row;
$total = count($students);

$course_dist = [];
foreach ($students as $s) {
    $c = $s['course'] ?? 'Unknown';
    $course_dist[$c] = ($course_dist[$c] ?? 0) + 1;
}
arsort($course_dist);

$fee_dist = ['Paid' => 0, 'Partial' => 0, 'Unpaid' => 0];
foreach ($students as $s) {
    $fs = $s['fee_status'] ?? 'Unpaid';
    if (isset($fee_dist[$fs])) $fee_dist[$fs]++;
}

$gender_dist = ['Male' => 0, 'Female' => 0, 'Other' => 0, 'N/A' => 0];
foreach ($students as $s) {
    $g = $s['gender'] ?? '';
    $key = in_array($g, ['Male','Female','Other']) ? $g : 'N/A';
    $gender_dist[$key]++;
}

$total_fee  = array_sum(array_column($students, 'total_fee'));
$total_paid = array_sum(array_column($students, 'paid_amount'));
$total_due  = array_sum(array_column($students, 'due_amount'));

include 'includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════
   STUDENT REPORT v5 — Production UI
   ═══════════════════════════════════════════════ */

/* Page header */
.sr-page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.3rem;flex-wrap:wrap;gap:.75rem;}
.sr-page-title{font-size:1.2rem;font-weight:800;color:var(--crimson-dk);margin:0;letter-spacing:-.01em;}
.sr-page-sub{font-size:.68rem;color:var(--text-light);margin:.15rem 0 0;font-weight:600;}
[data-theme="dark"] .sr-page-title{color:#f5c07a;}

/* Filter bar — clean pill design */
.sr-filter-bar{
    background:#fff;border-radius:12px;
    box-shadow:0 2px 14px rgba(0,0,0,.065);
    padding:1rem 1.25rem;
    display:flex;gap:.65rem;flex-wrap:wrap;align-items:flex-end;
    margin-bottom:1.2rem;
}
[data-theme="dark"] .sr-filter-bar{background:#1e1e28;box-shadow:0 4px 24px rgba(0,0,0,.42);}
.sr-filter-group{display:flex;flex-direction:column;gap:.25rem;min-width:145px;flex:1;}
.sr-filter-lbl{font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.09em;color:var(--text-light);}
[data-theme="dark"] .sr-filter-lbl{color:#9a8a7a;}
.sr-filter-sel{
    padding:.52rem .85rem;
    border:1.5px solid #ddd0ca;border-radius:8px;
    font-size:.83rem;color:var(--text-dark);background:#fdfcfb;
    outline:none;cursor:pointer;
    transition:border-color .2s,box-shadow .2s;
    font-family:inherit;
}
.sr-filter-sel:focus{border-color:var(--crimson);box-shadow:0 0 0 3px rgba(139,0,0,.09);}
[data-theme="dark"] .sr-filter-sel{background:#252530;border-color:rgba(255,255,255,.1);color:#f0eae0;}
.sr-filter-actions{display:flex;gap:.5rem;align-items:flex-end;padding-top:.01rem;}
.sr-clear-btn{
    display:inline-flex;align-items:center;gap:.3rem;
    padding:.5rem .8rem;border-radius:8px;
    font-size:.76rem;font-weight:700;
    color:var(--text-light);text-decoration:none;
    border:1.5px solid #e0d8d0;background:#fff;
    transition:all .15s;
}
.sr-clear-btn:hover{background:#f5ece8;color:var(--crimson);border-color:rgba(139,0,0,.2);}
[data-theme="dark"] .sr-clear-btn{background:#252530;border-color:rgba(255,255,255,.1);color:#9a8a7a;}

/* Stat cards — consistent, balanced */
.sr-stats-grid{
    display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;
    margin-bottom:1.2rem;
}
@media(max-width:700px){.sr-stats-grid{grid-template-columns:repeat(2,1fr);}}
.sr-stat-card{
    background:#fff;border-radius:12px;
    box-shadow:0 2px 14px rgba(0,0,0,.065);
    padding:1rem 1.15rem;
    position:relative;overflow:hidden;
    transition:box-shadow .2s,transform .2s;
}
.sr-stat-card::before{
    content:'';position:absolute;top:0;left:0;right:0;
    height:3px;border-radius:12px 12px 0 0;
}
.sr-stat-card:hover{box-shadow:0 6px 22px rgba(0,0,0,.11);transform:translateY(-2px);}
[data-theme="dark"] .sr-stat-card{background:#1e1e28;box-shadow:0 4px 24px rgba(0,0,0,.42);}
.sr-stat-card--blue::before{background:#3b82f6;}
.sr-stat-card--green::before{background:#16a34a;}
.sr-stat-card--red::before{background:#dc2626;}
.sr-stat-card--gold::before{background:var(--gold);}
.sr-stat-icon{font-size:1.35rem;margin-bottom:.4rem;display:block;line-height:1;}
.sr-stat-num{
    display:block;font-size:1.45rem;font-weight:900;line-height:1.1;
    margin-bottom:.2rem;letter-spacing:-.02em;
}
.sr-stat-card--blue .sr-stat-num{color:#1d4ed8;}
.sr-stat-card--green .sr-stat-num{color:#16a34a;}
.sr-stat-card--red .sr-stat-num{color:#dc2626;}
.sr-stat-card--gold .sr-stat-num{color:#b45309;}
[data-theme="dark"] .sr-stat-card--blue .sr-stat-num{color:#60a5fa;}
[data-theme="dark"] .sr-stat-card--green .sr-stat-num{color:#4ade80;}
[data-theme="dark"] .sr-stat-card--red .sr-stat-num{color:#f87171;}
[data-theme="dark"] .sr-stat-card--gold .sr-stat-num{color:var(--gold-lt);}
.sr-stat-lbl{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-light);}

/* Charts row — balanced 3 col */
.sr-charts-row{
    display:grid;grid-template-columns:1.3fr 1fr 1fr;gap:1rem;
    margin-bottom:1.2rem;
}
@media(max-width:900px){.sr-charts-row{grid-template-columns:1fr;}}
.sr-chart-card{
    background:#fff;border-radius:12px;
    box-shadow:0 2px 14px rgba(0,0,0,.065);
    padding:1.1rem 1.2rem;
}
[data-theme="dark"] .sr-chart-card{background:#1e1e28;box-shadow:0 4px 24px rgba(0,0,0,.42);}
.sr-chart-title{
    font-size:.68rem;font-weight:800;color:var(--crimson-dk);
    text-transform:uppercase;letter-spacing:.09em;
    padding-bottom:.5rem;border-bottom:1.5px solid #f0e5e0;
    margin-bottom:.9rem;
}
[data-theme="dark"] .sr-chart-title{color:var(--gold-lt);border-color:rgba(255,255,255,.07);}

/* Bar chart */
.sr-bar-list{display:flex;flex-direction:column;gap:.5rem;}
.sr-bar-row{display:flex;align-items:center;gap:.6rem;}
.sr-bar-lbl{font-size:.65rem;font-weight:700;color:var(--text-mid);width:70px;flex-shrink:0;text-align:right;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
[data-theme="dark"] .sr-bar-lbl{color:#c0a898;}
.sr-bar-track{flex:1;height:12px;background:#f5ebe0;border-radius:20px;overflow:hidden;}
[data-theme="dark"] .sr-bar-track{background:rgba(255,255,255,.07);}
.sr-bar-fill{height:100%;border-radius:20px;transition:width .9s cubic-bezier(.4,0,.2,1);}
.sr-bar-val{font-size:.68rem;font-weight:800;color:var(--text-dark);width:18px;text-align:right;flex-shrink:0;}
[data-theme="dark"] .sr-bar-val{color:#e8ddd0;}

/* Donut */
.sr-donut-wrap{position:relative;width:120px;height:120px;margin:0 auto .85rem;}
.sr-donut{width:100%;height:100%;border-radius:50%;}
.sr-donut-hole{
    position:absolute;inset:20px;border-radius:50%;
    background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;
    box-shadow:inset 0 2px 8px rgba(0,0,0,.05);
}
[data-theme="dark"] .sr-donut-hole{background:#1e1e28;}
.sr-donut-hole span{font-size:1.35rem;font-weight:900;color:var(--text-dark);line-height:1;}
[data-theme="dark"] .sr-donut-hole span{color:#f5e8d8;}
.sr-donut-hole small{font-size:.55rem;color:var(--text-light);text-transform:uppercase;letter-spacing:.08em;}
.sr-legend{display:flex;flex-direction:column;gap:.35rem;}
.sr-legend-item{display:flex;align-items:center;gap:.5rem;font-size:.73rem;color:var(--text-mid);font-weight:600;}
[data-theme="dark"] .sr-legend-item{color:#c0a898;}
.sr-legend-dot{width:10px;height:10px;border-radius:3px;flex-shrink:0;}

/* Gender bars */
.sr-gender-list{display:flex;flex-direction:column;gap:.65rem;}
.sr-gender-row{display:flex;align-items:center;gap:.6rem;}
.sr-gender-lbl{font-size:.65rem;font-weight:700;color:var(--text-mid);width:50px;flex-shrink:0;text-align:right;}
[data-theme="dark"] .sr-gender-lbl{color:#c0a898;}
.sr-gender-track{flex:1;height:11px;background:#f0f0f0;border-radius:20px;overflow:hidden;}
[data-theme="dark"] .sr-gender-track{background:rgba(255,255,255,.07);}
.sr-gender-val{font-size:.65rem;font-weight:800;flex-shrink:0;width:65px;text-align:right;}

/* Table controls */
.sr-table-controls{
    display:flex;justify-content:space-between;align-items:center;
    margin-bottom:.75rem;flex-wrap:wrap;gap:.6rem;
}
.sr-table-search-wrap{position:relative;}
.sr-table-search-ico{position:absolute;left:.7rem;top:50%;transform:translateY(-50%);font-size:.8rem;color:var(--text-light);pointer-events:none;}
.sr-table-search{
    padding:.48rem .85rem .48rem 2rem;
    border:1.5px solid #ddd0ca;border-radius:8px;
    font-size:.8rem;color:var(--text-dark);background:#fdfcfb;
    outline:none;transition:border-color .2s,box-shadow .2s;
    width:220px;
}
.sr-table-search:focus{border-color:var(--crimson);box-shadow:0 0 0 3px rgba(139,0,0,.09);}
[data-theme="dark"] .sr-table-search{background:#252530;border-color:rgba(255,255,255,.1);color:#f0eae0;}
.sr-table-count{font-size:.7rem;color:var(--text-light);font-weight:600;}

/* Table */
.sr-table-wrap{
    background:#fff;border-radius:12px;
    box-shadow:0 2px 14px rgba(0,0,0,.065);
    overflow:hidden;
}
[data-theme="dark"] .sr-table-wrap{background:#1e1e28;box-shadow:0 4px 24px rgba(0,0,0,.42);}
.sr-table{width:100%;border-collapse:collapse;font-size:.83rem;}
.sr-table thead tr{background:linear-gradient(135deg,var(--crimson-dk),var(--crimson));}
.sr-table th{
    padding:.8rem 1rem;text-align:left;
    font-size:.63rem;font-weight:800;color:#fff;
    text-transform:uppercase;letter-spacing:.09em;
    white-space:nowrap;
}
.sr-table td{
    padding:.85rem 1rem;
    border-bottom:1px solid #f5ece8;
    color:var(--text-dark);vertical-align:middle;
}
[data-theme="dark"] .sr-table td{border-color:rgba(255,255,255,.05);color:#e8ddd0;}
.sr-table tbody tr{transition:background .15s;}
.sr-table tbody tr:hover td{background:#fdf8f5;}
[data-theme="dark"] .sr-table tbody tr:hover td{background:rgba(255,255,255,.04);}
.sr-table tbody tr:last-child td{border-bottom:none;}

/* Avatar */
.sr-avatar{
    width:32px;height:32px;border-radius:50%;
    background:linear-gradient(135deg,var(--crimson-dk),var(--crimson));
    color:var(--gold-lt);font-size:.8rem;font-weight:800;
    display:inline-flex;align-items:center;justify-content:center;
    flex-shrink:0;border:2px solid rgba(201,168,76,.4);
}
.sr-student-cell{display:flex;align-items:center;gap:.65rem;}
.sr-student-name{font-weight:700;font-size:.82rem;color:var(--text-dark);display:block;line-height:1.2;}
[data-theme="dark"] .sr-student-name{color:#f5e8d8;}
.sr-student-roll{font-size:.63rem;color:var(--text-light);}

/* Badges */
.sr-course-badge{
    display:inline-block;padding:.18rem .6rem;border-radius:20px;
    font-size:.68rem;font-weight:800;
    background:linear-gradient(135deg,var(--crimson-dk),var(--crimson));
    color:var(--gold-lt);
}
.sr-fee-badge{font-size:.72rem;font-weight:800;}
.sr-due-pos{color:#dc2626;}
.sr-due-zero{color:#16a34a;}

/* Action buttons */
.sr-action-btn{
    display:inline-flex;align-items:center;justify-content:center;
    width:30px;height:30px;border-radius:7px;font-size:.9rem;
    text-decoration:none;transition:all .15s;border:1px solid;
    margin-right:.25rem;
}
.sr-action-btn--view{background:#eff6ff;color:#1d4ed8;border-color:rgba(29,78,216,.2);}
.sr-action-btn--view:hover{background:#1d4ed8;color:#fff;border-color:#1d4ed8;}
.sr-action-btn--fee{background:#f0fdf4;color:#16a34a;border-color:rgba(22,163,74,.2);}
.sr-action-btn--fee{font-size:.75rem;font-weight:800;}
.sr-action-btn--fee:hover{background:#16a34a;color:#fff;border-color:#16a34a;}
.sr-action-btn--edit{background:#fdf6e3;color:var(--gold-dk);border-color:rgba(160,120,48,.2);}
.sr-action-btn--edit:hover{background:var(--gold);color:var(--crimson-dk);border-color:var(--gold);}

/* Totals row */
.sr-totals-row td{background:#fdf6e3;font-weight:800;border-top:2px solid #e8d8b0 !important;}
[data-theme="dark"] .sr-totals-row td{background:rgba(201,168,76,.08);border-color:rgba(201,168,76,.2) !important;}

/* Empty state */
.sr-empty{text-align:center;padding:3rem 1rem;}
.sr-empty-icon{font-size:2.5rem;margin-bottom:.75rem;display:block;opacity:.4;}
.sr-empty-text{font-size:.85rem;color:var(--text-light);font-weight:600;}

/* Pagination */
.sr-pagination{
    display:flex;justify-content:space-between;align-items:center;
    padding:.85rem 1.1rem;border-top:1px solid #f0e5e0;
    flex-wrap:wrap;gap:.5rem;
}
[data-theme="dark"] .sr-pagination{border-color:rgba(255,255,255,.07);}
.sr-page-info{font-size:.7rem;color:var(--text-light);font-weight:600;}
.sr-page-btns{display:flex;gap:.35rem;}
.sr-page-btn{
    display:inline-flex;align-items:center;justify-content:center;
    min-width:30px;height:30px;padding:0 .5rem;
    border-radius:6px;font-size:.73rem;font-weight:700;
    border:1.5px solid #e0d0ca;background:#fff;color:var(--text-mid);
    cursor:pointer;transition:all .15s;
}
.sr-page-btn:hover{background:var(--crimson);color:#fff;border-color:var(--crimson);}
.sr-page-btn.active{background:var(--crimson);color:#fff;border-color:var(--crimson);}
.sr-page-btn:disabled{opacity:.4;cursor:not-allowed;}
[data-theme="dark"] .sr-page-btn{background:#252530;border-color:rgba(255,255,255,.1);color:#c0a898;}

/* Print */
.print-only{display:none;}
@media print{
    *{transition:none!important;animation:none!important;}
    body{background:#fff!important;color:#000!important;font-size:10pt!important;}
    .main-content{padding:0!important;}
    .container{max-width:100%!important;width:100%!important;padding:0!important;margin:0!important;}
    .site-header,.sms-footer,.no-print,
    .sr-filter-bar,.sr-table-controls,.sr-stats-grid,.sr-charts-row,
    .sr-page-header,.sr-pagination,
    .btn,.dm-toggle{display:none!important;}
    .print-only{display:block!important;}
    /* Table full width, no scroll wrapper */
    .sr-table-wrap{
        box-shadow:none!important;border-radius:0!important;
        border:none!important;overflow:visible!important;
        width:100%!important;
    }
    .sr-table{width:100%!important;font-size:9pt!important;}
    .sr-table thead tr{
        background:#2d2d2d!important;
        -webkit-print-color-adjust:exact;print-color-adjust:exact;
    }
    .sr-table th{
        color:#fff!important;font-size:7.5pt!important;
        padding:5px 7px!important;
        -webkit-print-color-adjust:exact;print-color-adjust:exact;
    }
    .sr-table td{padding:5px 7px!important;font-size:9pt!important;border-bottom:1px solid #ddd!important;}
    .sr-avatar{
        background:#2d2d2d!important;color:#E8C76A!important;
        -webkit-print-color-adjust:exact;print-color-adjust:exact;
    }
    .sr-course-badge{
        background:#2d2d2d!important;color:#fff!important;
        -webkit-print-color-adjust:exact;print-color-adjust:exact;
    }
    tr{page-break-inside:avoid!important;}
    thead{display:table-header-group!important;}
    tfoot{display:table-footer-group!important;}
    .print-header{margin-bottom:.75rem;border-bottom:2px solid #333;padding-bottom:.5rem;}
}

/* Page entry */
.sr-page-header,.sr-filter-bar,.sr-stats-grid,.sr-charts-row,.sr-table-wrap{
    animation:srIn .28s ease both;
}
.sr-filter-bar{animation-delay:.04s;}
.sr-stats-grid{animation-delay:.08s;}
.sr-charts-row{animation-delay:.12s;}
.sr-table-controls,.sr-table-wrap{animation-delay:.16s;}
@keyframes srIn{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
</style>

<div class="container">

    <!-- Page header -->
    <div class="sr-page-header no-print">
        <div>
            <h1 class="sr-page-title">&#128202; Student Report</h1>
            <p class="sr-page-sub">Analytics &amp; printable student data &mdash; <?= $total ?> record<?= $total!==1?'s':'' ?> shown</p>
        </div>
        <button type="button" onclick="window.print()" class="btn btn-gold btn-sm no-print">&#128424; Print Report</button>
    </div>

    <!-- Filter bar -->
    <form method="GET" action="student_report.php">
        <div class="sr-filter-bar no-print">
            <div class="sr-filter-group">
                <span class="sr-filter-lbl">Course</span>
                <select name="course" class="sr-filter-sel">
                    <option value="">All Courses</option>
                    <?php foreach ($course_codes as $code => $label): ?>
                    <option value="<?= htmlspecialchars($code) ?>" <?= $f_course===$code?'selected':'' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sr-filter-group">
                <span class="sr-filter-lbl">Status</span>
                <select name="status" class="sr-filter-sel">
                    <option value="">All Status</option>
                    <option value="1" <?= $f_status==='1'?'selected':'' ?>>Active</option>
                    <option value="0" <?= $f_status==='0'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <div class="sr-filter-group">
                <span class="sr-filter-lbl">Gender</span>
                <select name="gender" class="sr-filter-sel">
                    <option value="">All Genders</option>
                    <option value="Male"   <?= $f_gender==='Male'  ?'selected':'' ?>>Male</option>
                    <option value="Female" <?= $f_gender==='Female'?'selected':'' ?>>Female</option>
                </select>
            </div>
            <div class="sr-filter-group">
                <span class="sr-filter-lbl">Category</span>
                <select name="category" class="sr-filter-sel">
                    <option value="">All Categories</option>
                    <?php foreach (['General','OBC','SC','ST','EWS'] as $cat): ?>
                    <option value="<?= $cat ?>" <?= $f_cat===$cat?'selected':'' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sr-filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">&#128269; Apply</button>
                <a href="student_report.php" class="sr-clear-btn">&#10005; Clear</a>
            </div>
        </div>
    </form>

    <!-- Stat cards -->
    <div class="sr-stats-grid no-print">
        <div class="sr-stat-card sr-stat-card--blue">
            <span class="sr-stat-icon">&#128101;</span>
            <span class="sr-stat-num"><?= $total ?></span>
            <span class="sr-stat-lbl">Students Found</span>
        </div>
        <div class="sr-stat-card sr-stat-card--green">
            <span class="sr-stat-icon">&#128176;</span>
            <span class="sr-stat-num">&#8377;<?= number_format($total_paid) ?></span>
            <span class="sr-stat-lbl">Total Collected</span>
        </div>
        <div class="sr-stat-card sr-stat-card--red">
            <span class="sr-stat-icon">&#9888;</span>
            <span class="sr-stat-num">&#8377;<?= number_format($total_due) ?></span>
            <span class="sr-stat-lbl">Total Due</span>
        </div>
        <div class="sr-stat-card sr-stat-card--gold">
            <span class="sr-stat-icon">&#128200;</span>
            <span class="sr-stat-num"><?= $total_fee > 0 ? round(($total_paid/$total_fee)*100,1) : 0 ?>%</span>
            <span class="sr-stat-lbl">Collection Rate</span>
        </div>
    </div>

    <!-- Charts -->
    <div class="sr-charts-row no-print">

        <!-- Course bar chart -->
        <div class="sr-chart-card">
            <div class="sr-chart-title">&#127979; Students by Course</div>
            <div class="sr-bar-list">
                <?php
                $max = max(array_values($course_dist) ?: [1]);
                $bar_colors = ['#8B0000','#B22222','#C0392B','#E74C3C','#922B21','#7B241C','#641E16','#5B2333'];
                $ci = 0;
                foreach ($course_dist as $course => $cnt):
                    $w = round(($cnt / $max) * 100);
                    $bc = $bar_colors[$ci % count($bar_colors)];
                    $ci++;
                ?>
                <div class="sr-bar-row">
                    <span class="sr-bar-lbl" title="<?= htmlspecialchars($course) ?>"><?= htmlspecialchars($course) ?></span>
                    <div class="sr-bar-track">
                        <div class="sr-bar-fill" style="width:<?= $w ?>%;background:<?= $bc ?>;"></div>
                    </div>
                    <span class="sr-bar-val"><?= $cnt ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Fee donut -->
        <div class="sr-chart-card">
            <div class="sr-chart-title">&#128176; Fee Distribution</div>
            <?php
            $total_with_fee = array_sum($fee_dist) ?: 1;
            $paid_pct    = round($fee_dist['Paid']    / $total_with_fee * 100);
            $partial_pct = round($fee_dist['Partial'] / $total_with_fee * 100);
            $p1 = $paid_pct;
            $p2 = $p1 + $partial_pct;
            ?>
            <div class="sr-donut-wrap">
                <div class="sr-donut" style="background:conic-gradient(#16a34a 0% <?= $p1 ?>%,#f59e0b <?= $p1 ?>% <?= $p2 ?>%,#dc2626 <?= $p2 ?>% 100%);"></div>
                <div class="sr-donut-hole">
                    <span><?= $total_with_fee ?></span>
                    <small>students</small>
                </div>
            </div>
            <div class="sr-legend">
                <div class="sr-legend-item"><span class="sr-legend-dot" style="background:#16a34a;"></span>Paid (<?= $fee_dist['Paid'] ?>)</div>
                <div class="sr-legend-item"><span class="sr-legend-dot" style="background:#f59e0b;"></span>Partial (<?= $fee_dist['Partial'] ?>)</div>
                <div class="sr-legend-item"><span class="sr-legend-dot" style="background:#dc2626;"></span>Unpaid (<?= $fee_dist['Unpaid'] ?>)</div>
            </div>
        </div>

        <!-- Gender chart -->
        <div class="sr-chart-card">
            <div class="sr-chart-title">&#9898; Gender Distribution</div>
            <?php
            $g_total = array_sum($gender_dist) ?: 1;
            $g_colors = ['Male'=>'#1e40af','Female'=>'#be185d','Other'=>'#7c3aed','N/A'=>'#6b7280'];
            ?>
            <div class="sr-gender-list">
                <?php foreach ($gender_dist as $g => $cnt):
                    if ($cnt === 0) continue;
                    $gpct = round($cnt / $g_total * 100);
                ?>
                <div class="sr-gender-row">
                    <span class="sr-gender-lbl"><?= $g ?></span>
                    <div class="sr-gender-track">
                        <div style="width:<?= $gpct ?>%;height:100%;background:<?= $g_colors[$g] ?>;border-radius:20px;transition:width .9s;"></div>
                    </div>
                    <span class="sr-gender-val" style="color:<?= $g_colors[$g] ?>;"><?= $cnt ?> (<?= $gpct ?>%)</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($total_fee > 0): $cpct = min(100, round($total_paid/$total_fee*100,1)); ?>
            <div style="margin-top:1.1rem;padding-top:.9rem;border-top:1px solid #f0e5e0;">

                <div style="font-size:.63rem;font-weight:800;color:var(--text-light);margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.08em;">Collection Rate</div>
                <div style="height:8px;background:#f0ece8;border-radius:20px;overflow:hidden;">
                    <div style="width:<?= $cpct ?>%;height:100%;background:linear-gradient(90deg,#16a34a,#22c55e);border-radius:20px;transition:width .9s;"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:.63rem;color:var(--text-light);margin-top:.25rem;">
                    <span>&#8377;<?= number_format($total_paid) ?> collected</span>
                    <span style="font-weight:800;color:#16a34a;"><?= $cpct ?>%</span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Print header -->
    <div class="print-only" style="margin-bottom:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;justify-content:center;margin-bottom:.75rem;">
            <img src="assets/img/pptc_logo.png" style="width:52px;height:52px;object-fit:contain;">
            <div>
                <div style="font-family:serif;font-size:1.15rem;font-weight:700;color:#5C0000;">Pentium Point Group of Institutions</div>
                <div style="font-size:.72rem;color:#666;">Station Road, Rewa, M.P. &bull; 07662-438035</div>
            </div>
        </div>
        <div style="text-align:center;background:#5C0000;color:#fff;padding:.4rem;font-weight:700;font-size:.82rem;border-radius:4px;">
            STUDENT REPORT <?php if ($f_course || $f_status !== '' || $f_gender || $f_cat): ?> &mdash; <?= implode(', ', array_filter([$f_course, $f_status!==''?($f_status?'Active':'Inactive'):'', $f_gender, $f_cat])) ?><?php endif; ?>
        </div>
        <div style="text-align:right;font-size:.68rem;color:#999;margin-top:.35rem;">Generated: <?= date('d F Y, h:i A') ?> &bull; Total: <?= $total ?> students</div>
    </div>

    <!-- Table with search + pagination -->
    <div class="no-print sr-table-controls">
        <div class="sr-table-search-wrap">
            <span class="sr-table-search-ico">&#128269;</span>
            <input type="text" id="srTableSearch" class="sr-table-search" placeholder="Search in results...">
        </div>
        <span class="sr-table-count" id="srTableCount">Showing <strong><?= $total ?></strong> students</span>
    </div>

    <div class="sr-table-wrap">
        <table class="sr-table" id="srTable">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Gender</th>
                    <th>Category</th>
                    <th>Phone</th>
                    <th>Fee Status</th>
                    <th>Due Amount</th>
                    <th class="no-print" style="width:100px;">Actions</th>
                </tr>
            </thead>
            <tbody id="srTableBody">
                <?php if ($students): ?>
                <?php foreach ($students as $i => $s):
                    $due = (float)($s['due_amount'] ?? 0);
                    $fs  = $s['fee_status'] ?? 'Unpaid';
                    $fc  = match($fs) { 'Paid' => '#16a34a', 'Partial' => '#b45309', default => '#dc2626' };
                    $roll_enc = urlencode($s['roll_no']);
                ?>
                <tr class="sr-table-row" data-search="<?= strtolower(htmlspecialchars($s['name'].' '.$s['roll_no'].' '.$s['course'].' '.($s['gender']??'').' '.($s['category']??''))) ?>">
                    <td style="font-size:.72rem;color:var(--text-light);font-weight:700;"><?= $i+1 ?></td>
                    <td>
                        <div class="sr-student-cell">
                            <div class="sr-avatar"><?= strtoupper(mb_substr($s['name'],0,1)) ?></div>
                            <div>
                                <span class="sr-student-name"><?= htmlspecialchars($s['name']) ?></span>
                                <span class="sr-student-roll"><?= htmlspecialchars($s['roll_no']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td><span class="sr-course-badge"><?= htmlspecialchars($s['course']) ?></span></td>
                    <td style="font-size:.78rem;"><?= htmlspecialchars($s['gender'] ?? '—') ?></td>
                    <td style="font-size:.78rem;"><?= htmlspecialchars($s['category'] ?? '—') ?></td>
                    <td style="font-size:.75rem;color:var(--text-mid);"><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                    <td><span class="sr-fee-badge" style="color:<?= $fc ?>;"><?= $fs ?></span></td>
                    <td><span class="sr-fee-badge <?= $due>0?'sr-due-pos':'sr-due-zero' ?>">&#8377;<?= number_format($due) ?></span></td>
                    <td class="no-print">
                        <a href="view.php?roll_no=<?= $roll_enc ?>"   class="sr-action-btn sr-action-btn--view" title="View Profile">&#128065;</a>
                        <a href="fees.php?roll_no=<?= $roll_enc ?>"   class="sr-action-btn sr-action-btn--fee"  title="Collect Fee">&#8377;</a>
                        <a href="update.php?roll_no=<?= $roll_enc ?>" class="sr-action-btn sr-action-btn--edit" title="Edit">&#9998;</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr class="sr-totals-row">
                    <td colspan="7" style="text-align:right;font-size:.75rem;color:var(--text-light);padding:.75rem 1rem;">TOTALS</td>
                    <td style="padding:.75rem 1rem;color:#dc2626;font-size:.85rem;">&#8377;<?= number_format($total_due) ?></td>
                    <td class="no-print"></td>
                </tr>
                <?php else: ?>
                <tr><td colspan="9"><div class="sr-empty">
                    <span class="sr-empty-icon">&#127891;</span>
                    <p class="sr-empty-text">No students match the selected filters.</p>
                </div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- Pagination -->
        <div class="sr-pagination no-print" id="srPagination"></div>
    </div>

    <!-- Print footer -->
    <div class="print-only" style="margin-top:2rem;padding-top:.85rem;border-top:1px dashed #ccc;display:flex;justify-content:space-between;font-size:.67rem;color:#999;">
        <span>Pentium Point Group of Institutions &bull; Rewa, M.P.</span>
        <span>System generated report.</span>
        <span>Page 1</span>
    </div>
</div>

<script>
(function(){
    const ROWS_PER_PAGE = 15;
    const tbody = document.getElementById('srTableBody');
    const searchInput = document.getElementById('srTableSearch');
    const countEl = document.getElementById('srTableCount');
    const paginationEl = document.getElementById('srPagination');
    if(!tbody) return;

    let allRows = Array.from(tbody.querySelectorAll('tr.sr-table-row'));
    let filteredRows = [...allRows];
    let currentPage = 1;

    function updateCount(shown, total){
        if(countEl) countEl.innerHTML = `Showing <strong>${shown}</strong> of <strong>${total}</strong> students`;
    }

    function renderPagination(total){
        if(!paginationEl) return;
        const pages = Math.ceil(total / ROWS_PER_PAGE);
        if(pages <= 1){ paginationEl.innerHTML=''; return; }
        const start = (currentPage-1)*ROWS_PER_PAGE+1;
        const end   = Math.min(currentPage*ROWS_PER_PAGE, total);
        let btns = `<span class="sr-page-info">Showing ${start}–${end} of ${total}</span><div class="sr-page-btns">`;
        btns += `<button class="sr-page-btn" onclick="changePage(${currentPage-1})" ${currentPage===1?'disabled':''}>&#8592;</button>`;
        for(let p=1;p<=pages;p++){
            if(p===1||p===pages||Math.abs(p-currentPage)<=1){
                btns += `<button class="sr-page-btn ${p===currentPage?'active':''}" onclick="changePage(${p})">${p}</button>`;
            } else if(Math.abs(p-currentPage)===2){
                btns += `<span class="sr-page-btn" style="cursor:default;opacity:.5;">&hellip;</span>`;
            }
        }
        btns += `<button class="sr-page-btn" onclick="changePage(${currentPage+1})" ${currentPage===pages?'disabled':''}>&#8594;</button>`;
        btns += `</div>`;
        paginationEl.innerHTML = btns;
    }

    window.changePage = function(p){
        const pages = Math.ceil(filteredRows.length / ROWS_PER_PAGE);
        if(p<1||p>pages) return;
        currentPage = p;
        renderPage();
    };

    function renderPage(){
        allRows.forEach(r=>r.style.display='none');
        const start = (currentPage-1)*ROWS_PER_PAGE;
        const end   = start+ROWS_PER_PAGE;
        filteredRows.slice(start,end).forEach(r=>r.style.display='');
        updateCount(filteredRows.length, allRows.length);
        renderPagination(filteredRows.length);
    }

    // Table search
    if(searchInput){
        searchInput.addEventListener('input',function(){
            const q = this.value.trim().toLowerCase();
            filteredRows = q ? allRows.filter(r=>r.dataset.search.includes(q)) : [...allRows];
            currentPage = 1;
            renderPage();
        });
    }

    renderPage();
})();
</script>

<?php include 'includes/footer.php'; ?>
