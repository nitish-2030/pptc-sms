<?php
// ============================================================
// print_report.php — Student Data Visual Report + Print
// Charts: fee status pie, course-wise bar, active/inactive
// Filters: course, status, fee_status — then print result
// ============================================================
require_once 'config/db.php';
require_once 'config/courses_helper.php';
$pageTitle    = 'Student Report';
$baseUrl      = '';
$course_codes = get_course_codes($conn);

// ---- Filters ----
$f_course = trim($_GET['course']     ?? '');
$f_status = $_GET['status']          ?? '';
$f_fee    = trim($_GET['fee_status'] ?? '');

$where  = []; $types = ''; $vals = [];
if ($f_course !== '') { $where[] = 's.course=?';     $types .= 's'; $vals[] = $f_course; }
if (in_array($f_status,['0','1'])) { $where[] = 's.is_active=?'; $types .= 'i'; $vals[] = (int)$f_status; }
if (in_array($f_fee,['Paid','Partial','Unpaid'])) { $where[] = 'f.status=?'; $types .= 's'; $vals[] = $f_fee; }
$wsql = $where ? 'WHERE '.implode(' AND ',$where) : '';

$sql = "SELECT s.*, f.total_fee, f.paid_amount, f.due_amount, f.status AS fee_status
        FROM students s LEFT JOIN fees f ON s.id=f.student_id $wsql ORDER BY s.name ASC";
$stmt = mysqli_prepare($conn, $sql);
if ($types) mysqli_stmt_bind_param($stmt, $types, ...$vals);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$students = [];
while ($row = mysqli_fetch_assoc($res)) $students[] = $row;
$total = count($students);

// ---- Chart data ----
$course_counts = []; $fee_counts = ['Paid'=>0,'Partial'=>0,'Unpaid'=>0]; $active_counts = [1=>0,0=>0];
$total_collected = 0; $total_due = 0;
foreach ($students as $s) {
    $course_counts[$s['course']] = ($course_counts[$s['course']] ?? 0) + 1;
    $fs = $s['fee_status'] ?? 'Unpaid';
    if (isset($fee_counts[$fs])) $fee_counts[$fs]++;
    $active_counts[(int)$s['is_active']]++;
    $total_collected += (float)($s['paid_amount'] ?? 0);
    $total_due       += (float)($s['due_amount']  ?? 0);
}
arsort($course_counts);
$max_course = max(array_values($course_counts) ?: [1]);

include 'includes/header.php';
?>

<div class="container">
    <h1 class="page-title">&#128202; Student Report &amp; Print</h1>

    <!-- ── Filter Bar ── -->
    <form method="GET" action="print_report.php" class="card" style="margin-bottom:1.5rem;padding:1.25rem 1.5rem;">
        <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;">
            <div style="flex:1;min-width:160px;">
                <label style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#4a2020;display:block;margin-bottom:.4rem;">Course</label>
                <select name="course" class="form-control" style="font-size:.85rem;padding:.45rem .75rem;">
                    <option value="">All Courses</option>
                    <?php foreach($course_codes as $code=>$label): ?>
                    <option value="<?= htmlspecialchars($code) ?>" <?= $f_course===$code?'selected':'' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#4a2020;display:block;margin-bottom:.4rem;">Status</label>
                <select name="status" class="form-control" style="font-size:.85rem;padding:.45rem .75rem;">
                    <option value="">All</option>
                    <option value="1" <?= $f_status==='1'?'selected':'' ?>>Active</option>
                    <option value="0" <?= $f_status==='0'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <div>
                <label style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#4a2020;display:block;margin-bottom:.4rem;">Fee Status</label>
                <select name="fee_status" class="form-control" style="font-size:.85rem;padding:.45rem .75rem;">
                    <option value="">All</option>
                    <option value="Paid"    <?= $f_fee==='Paid'   ?'selected':'' ?>>Paid</option>
                    <option value="Partial" <?= $f_fee==='Partial'?'selected':'' ?>>Partial</option>
                    <option value="Unpaid"  <?= $f_fee==='Unpaid' ?'selected':'' ?>>Unpaid</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">&#128269; Apply Filter</button>
            <a href="print_report.php" class="btn btn-outline btn-sm">&#10005; Clear</a>
            <button type="button" onclick="window.print()" class="btn btn-gold btn-sm">&#128424; Print This Report</button>
        </div>
        <p style="margin-top:.75rem;font-size:.78rem;color:var(--text-light);">
            Showing <strong style="color:var(--crimson);"><?= $total ?></strong> students
            <?= $f_course ? " &bull; Course: <strong>$f_course</strong>" : '' ?>
            <?= $f_status !== '' ? " &bull; ".($f_status==='1'?'Active':'Inactive') : '' ?>
            <?= $f_fee ? " &bull; Fee: <strong>$f_fee</strong>" : '' ?>
        </p>
    </form>

    <!-- ── CHARTS SECTION ── -->
    <?php if ($total > 0): ?>
    <div class="rp-charts-grid">

        <!-- Fee Status Donut -->
        <div class="rp-chart-card">
            <div class="rp-chart-title">&#127775; Fee Status Distribution</div>
            <div class="rp-donut-wrap">
                <canvas id="feeDonut" width="180" height="180"></canvas>
                <div class="rp-donut-center">
                    <span class="rp-donut-num"><?= $total ?></span>
                    <span class="rp-donut-sub">Students</span>
                </div>
            </div>
            <div class="rp-legend">
                <div class="rp-legend-item"><span style="background:#16a34a;"></span>Paid (<?= $fee_counts['Paid'] ?>)</div>
                <div class="rp-legend-item"><span style="background:#f59e0b;"></span>Partial (<?= $fee_counts['Partial'] ?>)</div>
                <div class="rp-legend-item"><span style="background:#8B0000;"></span>Unpaid (<?= $fee_counts['Unpaid'] ?>)</div>
            </div>
        </div>

        <!-- Active/Inactive Donut -->
        <div class="rp-chart-card">
            <div class="rp-chart-title">&#128100; Active vs Inactive</div>
            <div class="rp-donut-wrap">
                <canvas id="activeDonut" width="180" height="180"></canvas>
                <div class="rp-donut-center">
                    <span class="rp-donut-num"><?= $active_counts[1] ?></span>
                    <span class="rp-donut-sub">Active</span>
                </div>
            </div>
            <div class="rp-legend">
                <div class="rp-legend-item"><span style="background:#16a34a;"></span>Active (<?= $active_counts[1] ?>)</div>
                <div class="rp-legend-item"><span style="background:#aaa;"></span>Inactive (<?= $active_counts[0] ?>)</div>
            </div>
        </div>

        <!-- Fee Collection Summary -->
        <div class="rp-chart-card">
            <div class="rp-chart-title">&#128176; Fee Collection</div>
            <?php
            $total_fee_sum = $total_collected + $total_due;
            $coll_pct = $total_fee_sum > 0 ? round(($total_collected / $total_fee_sum)*100) : 0;
            ?>
            <div style="padding:.5rem 0;">
                <div style="text-align:center;margin-bottom:1rem;">
                    <div style="font-size:1.8rem;font-weight:900;color:#16a34a;">&#8377;<?= number_format($total_collected) ?></div>
                    <div style="font-size:.75rem;color:#888;text-transform:uppercase;letter-spacing:.08em;">Collected</div>
                </div>
                <div style="margin-bottom:.9rem;">
                    <div style="display:flex;justify-content:space-between;font-size:.72rem;color:#888;margin-bottom:.3rem;">
                        <span>Collection Rate</span><span style="font-weight:700;color:#16a34a;"><?= $coll_pct ?>%</span>
                    </div>
                    <div style="height:10px;background:#eee;border-radius:20px;overflow:hidden;">
                        <div style="width:<?= $coll_pct ?>%;height:100%;background:linear-gradient(90deg,#16a34a,#4ade80);border-radius:20px;"></div>
                    </div>
                </div>
                <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                    <div style="flex:1;text-align:center;padding:.5rem;background:#eafbea;border-radius:8px;">
                        <div style="font-size:.95rem;font-weight:800;color:#16a34a;">&#8377;<?= number_format($total_collected) ?></div>
                        <div style="font-size:.65rem;color:#888;text-transform:uppercase;">Paid</div>
                    </div>
                    <div style="flex:1;text-align:center;padding:.5rem;background:#fdecea;border-radius:8px;">
                        <div style="font-size:.95rem;font-weight:800;color:#8B0000;">&#8377;<?= number_format($total_due) ?></div>
                        <div style="font-size:.65rem;color:#888;text-transform:uppercase;">Due</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Course-wise Bar Chart -->
    <?php if (count($course_counts) > 1): ?>
    <div class="card" style="margin-bottom:1.5rem;padding:1.25rem 1.5rem;">
        <div class="rp-chart-title" style="margin-bottom:1rem;">&#127979; Course-wise Enrollment</div>
        <div class="rp-bar-chart">
            <?php foreach ($course_counts as $course => $cnt): $bar_w = round(($cnt/$max_course)*100); ?>
            <div class="rp-bar-row">
                <span class="rp-bar-label"><?= htmlspecialchars($course) ?></span>
                <div class="rp-bar-wrap">
                    <div class="rp-bar-fill" style="width:<?= $bar_w ?>%;"><?= $cnt ?></div>
                </div>
                <span class="rp-bar-count"><?= $cnt ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- ── STUDENT TABLE ── -->
    <div class="card" style="padding:0;overflow:hidden;">
        <div style="padding:1rem 1.5rem;border-bottom:1px solid #f5ebe0;display:flex;justify-content:space-between;align-items:center;">
            <div style="font-family:'Cinzel',serif;font-size:.85rem;font-weight:700;color:var(--crimson-dk);">&#128203; Student List (<?= $total ?>)</div>
            <button onclick="window.print()" class="btn btn-primary btn-sm">&#128424; Print</button>
        </div>
        <div style="overflow-x:auto;">
        <table class="styled-table" style="font-size:.83rem;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Roll No</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Admission</th>
                    <th>Total Fee</th>
                    <th>Paid</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th>Fee</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($students): ?>
                <?php foreach ($students as $i => $s):
                    $fs  = $s['fee_status'] ?? 'Unpaid';
                    $fc  = match($fs){ 'Paid'=>'#eafbea,#1a7a1a','Partial'=>'#fff8e1,#a07800',default=>'#fdecea,#8B0000' };
                    [$fbg,$ftx] = explode(',', $fc);
                ?>
                <tr>
                    <td style="color:#aaa;font-size:.75rem;"><?= $i+1 ?></td>
                    <td><strong style="color:var(--crimson-dk);"><?= htmlspecialchars($s['roll_no']) ?></strong></td>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><span style="background:#f5ebe0;color:var(--crimson-dk);padding:.12rem .5rem;border-radius:12px;font-size:.72rem;font-weight:700;"><?= htmlspecialchars($s['course']) ?></span></td>
                    <td style="color:#888;font-size:.78rem;"><?= date('d M Y', strtotime($s['admission_date'])) ?></td>
                    <td>&#8377;<?= number_format((float)($s['total_fee']??0)) ?></td>
                    <td style="color:#1a7a1a;font-weight:700;">&#8377;<?= number_format((float)($s['paid_amount']??0)) ?></td>
                    <td style="color:<?= (float)($s['due_amount']??0)>0?'var(--crimson)':'#1a7a1a' ?>;font-weight:700;">&#8377;<?= number_format((float)($s['due_amount']??0)) ?></td>
                    <td>
                        <span style="padding:.12rem .5rem;border-radius:12px;font-size:.7rem;font-weight:700;background:<?= $s['is_active']?'#eafbea':'#f0f0f0' ?>;color:<?= $s['is_active']?'#1a7a1a':'#888' ?>;">
                            <?= $s['is_active']?'Active':'Inactive' ?>
                        </span>
                    </td>
                    <td><span style="padding:.12rem .5rem;border-radius:12px;font-size:.7rem;font-weight:700;background:<?= $fbg ?>;color:<?= $ftx ?>;"><?= $fs ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="10" style="text-align:center;padding:2rem;color:#aaa;">No students match your filters.</td></tr>
                <?php endif; ?>
            </tbody>
            <?php if ($students): ?>
            <tfoot>
                <tr style="background:#f5ebe0;font-weight:800;">
                    <td colspan="5" style="padding:.7rem 1rem;font-family:'Cinzel',serif;font-size:.75rem;color:var(--crimson-dk);">TOTALS (<?= $total ?> students)</td>
                    <td>&#8377;<?= number_format($total_collected + $total_due) ?></td>
                    <td style="color:#1a7a1a;">&#8377;<?= number_format($total_collected) ?></td>
                    <td style="color:var(--crimson);">&#8377;<?= number_format($total_due) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
        </div>
    </div>
</div>

<style>
/* Charts grid */
.rp-charts-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.25rem;margin-bottom:1.5rem;}
.rp-chart-card{background:#fff;border-radius:12px;border:1px solid rgba(139,0,0,.08);box-shadow:0 2px 12px rgba(139,0,0,.06);padding:1.25rem;text-align:center;}
.rp-chart-title{font-family:'Cinzel',serif;font-size:.75rem;font-weight:700;color:var(--crimson-dk);text-transform:uppercase;letter-spacing:.08em;margin-bottom:.85rem;}
/* Donut */
.rp-donut-wrap{position:relative;display:inline-block;margin-bottom:.75rem;}
.rp-donut-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;}
.rp-donut-num{display:block;font-size:1.5rem;font-weight:900;color:var(--text-dark);line-height:1;}
.rp-donut-sub{display:block;font-size:.65rem;color:#888;text-transform:uppercase;letter-spacing:.08em;}
/* Legend */
.rp-legend{display:flex;flex-wrap:wrap;gap:.4rem;justify-content:center;}
.rp-legend-item{display:flex;align-items:center;gap:.3rem;font-size:.72rem;color:#666;font-weight:600;}
.rp-legend-item span{display:inline-block;width:10px;height:10px;border-radius:50%;}
/* Bar chart */
.rp-bar-chart{display:flex;flex-direction:column;gap:.55rem;}
.rp-bar-row{display:flex;align-items:center;gap:.75rem;}
.rp-bar-label{font-size:.72rem;font-weight:700;color:var(--text-mid);width:80px;text-align:right;flex-shrink:0;}
.rp-bar-wrap{flex:1;background:#f5ebe0;border-radius:20px;height:22px;overflow:hidden;}
.rp-bar-fill{height:100%;background:linear-gradient(90deg,var(--crimson-dk),var(--crimson));border-radius:20px;display:flex;align-items:center;padding-left:.5rem;font-size:.7rem;font-weight:800;color:#fff;min-width:22px;transition:width .5s;}
.rp-bar-count{font-size:.75rem;font-weight:800;color:var(--crimson);width:24px;flex-shrink:0;}

/* Print styles */
@media print {
    *{transition:none!important;animation:none!important;}
    body{background:#fff!important;color:#000!important;}
    /* Hide all non-table UI */
    .site-header,.sms-footer,.r-actions,button,a.btn,
    form.card,.rp-charts-grid,.no-print,.dm-toggle{display:none!important;}
    .main-content{padding:0!important;}
    .container{max-width:100%!important;width:100%!important;padding:0!important;margin:0!important;}
    /* Remove decorative styling */
    .card{box-shadow:none!important;border-radius:0!important;border:none!important;}
    /* Ensure table is full width and not in scrollable div */
    div[style*="overflow-x"]{overflow:visible!important;}
    table{width:100%!important;page-break-inside:auto;font-size:9pt!important;}
    thead tr{
        background:#2d2d2d!important;
        -webkit-print-color-adjust:exact;print-color-adjust:exact;
    }
    th{
        color:#fff!important;font-size:7.5pt!important;
        padding:5px 7px!important;
        -webkit-print-color-adjust:exact;print-color-adjust:exact;
    }
    td{padding:5px 7px!important;font-size:9pt!important;border-bottom:1px solid #ddd!important;}
    tr{page-break-inside:avoid!important;page-break-after:auto;}
    thead{display:table-header-group!important;}
    tfoot{display:table-footer-group!important;}
}
</style>

<script>
// ── Simple canvas donut chart (no library needed) ──
function drawDonut(id, values, colors, total) {
    const canvas = document.getElementById(id);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const cx = canvas.width/2, cy = canvas.height/2, r = 70, ir = 48;
    let start = -Math.PI/2;
    const sum = values.reduce((a,b)=>a+b,0) || 1;
    ctx.clearRect(0,0,canvas.width,canvas.height);
    values.forEach((v,i) => {
        const sweep = (v/sum) * Math.PI * 2;
        ctx.beginPath();
        ctx.moveTo(cx,cy);
        ctx.arc(cx,cy,r,start,start+sweep);
        ctx.closePath();
        ctx.fillStyle = colors[i];
        ctx.fill();
        start += sweep;
    });
    // Inner hole
    ctx.beginPath();
    ctx.arc(cx,cy,ir,0,Math.PI*2);
    ctx.fillStyle = '#fff';
    ctx.fill();
}

drawDonut('feeDonut',
    [<?= $fee_counts['Paid'] ?>,<?= $fee_counts['Partial'] ?>,<?= $fee_counts['Unpaid'] ?>],
    ['#16a34a','#f59e0b','#8B0000'],
    <?= $total ?>
);
drawDonut('activeDonut',
    [<?= $active_counts[1] ?>,<?= $active_counts[0] ?>],
    ['#16a34a','#aaaaaa'],
    <?= $total ?>
);
</script>

<?php include 'includes/footer.php'; ?>
