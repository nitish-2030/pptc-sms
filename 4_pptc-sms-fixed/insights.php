<?php
// ============================================================
// insights.php — Financial Insights Dashboard (Feature 3)
// 5 Sections: Overview | Trend | Risk | Admin | Smart Insights
// ============================================================
require_once 'config/db.php';
require_once 'config/activity_helper.php';
$pageTitle = 'Insights Dashboard';
$baseUrl   = '';

// Track insights page visits in activity log
log_activity($conn, 'admin', 'Viewed Insights Dashboard', 'Financial insights page opened');

// ════════════════════════════════════════════════════════════
// 1. FINANCIAL OVERVIEW
// ════════════════════════════════════════════════════════════
$ov = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total,
            SUM(is_active) AS active,
            SUM(1-is_active) AS inactive
     FROM students"
));
$fv = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(total_fee),0)   AS tf,
            COALESCE(SUM(paid_amount),0) AS tp,
            COALESCE(SUM(due_amount),0)  AS td,
            COUNT(CASE WHEN status='Paid'    THEN 1 END) AS paid_c,
            COUNT(CASE WHEN status='Partial' THEN 1 END) AS part_c,
            COUNT(CASE WHEN status='Unpaid'  THEN 1 END) AS unp_c,
            COUNT(*) AS fee_students
     FROM fees"
));
$efficiency = $fv['tf'] > 0 ? round(($fv['tp'] / $fv['tf']) * 100, 1) : 0;
$avg_fee     = $fv['fee_students'] > 0 ? round($fv['tf'] / $fv['fee_students']) : 0;
$avg_paid    = $fv['fee_students'] > 0 ? round($fv['tp'] / $fv['fee_students']) : 0;

// ════════════════════════════════════════════════════════════
// 2. MONTHLY TREND (last 7 months)
// ════════════════════════════════════════════════════════════
$monthly_res = mysqli_query($conn,
    "SELECT DATE_FORMAT(payment_date,'%Y-%m') AS month,
            DATE_FORMAT(payment_date,'%b %Y')  AS label,
            COUNT(*)                            AS txn_count,
            SUM(amount)                         AS collected
     FROM fee_payments
     WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 MONTH)
     GROUP BY DATE_FORMAT(payment_date,'%Y-%m')
     ORDER BY month ASC"
);
$months_data = [];
while ($r = mysqli_fetch_assoc($monthly_res)) $months_data[] = $r;

// Fill missing months so chart always shows 7 points
$filled = [];
for ($i = 6; $i >= 0; $i--) {
    $key   = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $found = array_filter($months_data, fn($m) => $m['month'] === $key);
    $found = array_values($found);
    $filled[] = [
        'month'      => $key,
        'label'      => $label,
        'collected'  => (float)($found[0]['collected']  ?? 0),
        'txn_count'  => (int)($found[0]['txn_count'] ?? 0),
    ];
}

// This month vs last month
$this_month = $filled[6]['collected'];
$last_month = $filled[5]['collected'];
$trend_pct  = $last_month > 0 ? round((($this_month - $last_month) / $last_month) * 100, 1) : ($this_month > 0 ? 100 : 0);
$trend_dir  = $trend_pct >= 0 ? 'up' : 'down';

// Course-wise breakdown
$course_res = mysqli_query($conn,
    "SELECT s.course, COUNT(*) AS cnt,
            COALESCE(SUM(f.total_fee),0)  AS tf,
            COALESCE(SUM(f.paid_amount),0) AS tp,
            COALESCE(SUM(f.due_amount),0)  AS td
     FROM students s LEFT JOIN fees f ON s.id=f.student_id
     WHERE s.is_active=1
     GROUP BY s.course ORDER BY tp DESC LIMIT 8"
);
$course_data = [];
while ($r = mysqli_fetch_assoc($course_res)) $course_data[] = $r;

// ════════════════════════════════════════════════════════════
// 3. RISK INTELLIGENCE ENGINE
// ════════════════════════════════════════════════════════════
// Risk score per student:
//   due > 5000   → +30
//   due > 10000  → +20 more (total +50)
//   warning_sent → +20
//   partial payments > 2 → +15
//   is_active=0  → not included (already out)
$risk_res = mysqli_query($conn,
    "SELECT s.id, s.name, s.roll_no, s.course, s.email,
            s.warning_sent_at,
            f.total_fee, f.paid_amount, f.due_amount, f.status AS fee_status,
            (SELECT COUNT(*) FROM fee_payments fp WHERE fp.student_id = s.id) AS pay_count
     FROM students s
     LEFT JOIN fees f ON s.id = f.student_id
     WHERE s.is_active = 1"
);
$risk_students = [];
while ($r = mysqli_fetch_assoc($risk_res)) {
    $score = 0;
    $factors = [];
    $due = (float)($r['due_amount'] ?? 0);

    if ($due > 10000)      { $score += 50; $factors[] = 'Due > ₹10K'; }
    elseif ($due > 5000)   { $score += 30; $factors[] = 'Due > ₹5K'; }
    if (!empty($r['warning_sent_at'])) { $score += 20; $factors[] = 'Warning sent'; }
    if ($r['fee_status'] === 'Partial' && $r['pay_count'] >= 2) { $score += 15; $factors[] = 'Repeated partial'; }
    if ($r['fee_status'] === 'Unpaid' && $r['pay_count'] == 0)  { $score += 10; $factors[] = 'Never paid'; }
    if ($due > 0 && ($r['total_fee'] ?? 0) > 0) {
        $due_ratio = $due / $r['total_fee'];
        if ($due_ratio > 0.75) { $score += 15; $factors[] = '>75% unpaid'; }
    }

    $level = $score >= 61 ? 'High' : ($score >= 31 ? 'Medium' : 'Low');
    $r['risk_score']   = $score;
    $r['risk_level']   = $level;
    $r['risk_factors'] = $factors;
    $risk_students[] = $r;
}
usort($risk_students, fn($a,$b) => $b['risk_score'] - $a['risk_score']);
$high_risk    = array_filter($risk_students, fn($s) => $s['risk_level'] === 'High');
$medium_risk  = array_filter($risk_students, fn($s) => $s['risk_level'] === 'Medium');
$low_risk     = array_filter($risk_students, fn($s) => $s['risk_level'] === 'Low');
$top5_risk    = array_slice($risk_students, 0, 5);

// ════════════════════════════════════════════════════════════
// 4. ADMIN PERFORMANCE
// ════════════════════════════════════════════════════════════
$admin_week = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total_actions,
            COUNT(CASE WHEN category='admin' THEN 1 END) AS admin_actions,
            COUNT(CASE WHEN category='email' THEN 1 END) AS email_actions,
            COUNT(CASE WHEN category='student' THEN 1 END) AS student_actions
     FROM activity_logs
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
));
$admin_logins = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM activity_logs WHERE action LIKE '%login%' AND category='admin'"
));
$emails_sent = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM email_logs WHERE status='sent'"
));
$emails_failed = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM email_logs WHERE status='failed'"
));
$emails_noemail = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM email_logs WHERE status='no_email'"
));
// Most common action this week
$top_action_res = mysqli_query($conn,
    "SELECT action, COUNT(*) AS c FROM activity_logs
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY action ORDER BY c DESC LIMIT 1"
);
$top_action = mysqli_fetch_assoc($top_action_res);
// Daily login pattern last 7 days
$login_trend_res = mysqli_query($conn,
    "SELECT DATE_FORMAT(created_at,'%a') AS day_name,
            DATE(created_at) AS day_date,
            COUNT(*) AS c
     FROM activity_logs
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(created_at)
     ORDER BY day_date ASC"
);
$login_trend = [];
while ($r = mysqli_fetch_assoc($login_trend_res)) $login_trend[] = $r;

// Warning stats
$warnings_sent_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM students WHERE warning_sent_at IS NOT NULL"
));

// ════════════════════════════════════════════════════════════
// 5. AUTO SMART INSIGHTS — Dynamic text conclusions
// ════════════════════════════════════════════════════════════
$smart = [];

// Efficiency
if ($efficiency >= 85)
    $smart[] = ['🏆','green', "Excellent collection efficiency of <strong>{$efficiency}%</strong>. The college is maintaining outstanding financial health. Keep up the momentum!"];
elseif ($efficiency >= 65)
    $smart[] = ['📈','blue',  "Collection efficiency stands at <strong>{$efficiency}%</strong>. Good progress, but there is room for improvement. Target is 85%+."];
else
    $smart[] = ['⚠️','red',   "Collection efficiency is critically low at <strong>{$efficiency}%</strong>. Immediate intervention needed. Consider sending bulk fee reminders."];

// Monthly trend
if ($trend_pct > 0)
    $smart[] = ['📊','green', "Fee collection <strong>increased by {$trend_pct}%</strong> this month compared to last month. A positive growth signal!"];
elseif ($trend_pct < 0)
    $smart[] = ['📉','red',   "Collection <strong>dropped by " . abs($trend_pct) . "%</strong> compared to last month. Review payment activity and follow up on pending students."];
else
    $smart[] = ['➡️','gray',  "Collection remained <strong>flat</strong> compared to last month. Push for fee drives to drive growth."];

// High risk
$hr_count = count($high_risk);
if ($hr_count === 0)
    $smart[] = ['✅','green', "No high-risk students detected. All students are within acceptable fee payment thresholds."];
elseif ($hr_count <= 3)
    $smart[] = ['⚠️','amber', "<strong>{$hr_count} high-risk student" . ($hr_count>1?'s':'') . "</strong> detected. These students have critical dues and require immediate attention."];
else
    $smart[] = ['🔴','red',   "<strong>{$hr_count} students are classified as HIGH RISK</strong>. Bulk warning emails should be sent immediately to prevent further escalation."];

// Email coverage
$no_email_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM students WHERE email IS NULL OR email=''"));
$no_email_c   = (int)$no_email_res['c'];
if ($no_email_c > 0)
    $smart[] = ['📧','amber', "<strong>{$no_email_c} student" . ($no_email_c>1?'s are':'is') . "</strong> missing email addresses. Automated notifications cannot reach them. Update their profiles."];

// Paid students
if ((int)$fv['paid_c'] > 0) {
    $paid_pct = round(($fv['paid_c'] / max($ov['total'],1)) * 100);
    $smart[] = ['💰','green', "<strong>{$fv['paid_c']} student" . ($fv['paid_c']>1?'s have':'has') . " cleared 100% of their fees</strong> ({$paid_pct}% of total). Recognize and encourage others."];
}

// Admin activity
$total_w = (int)($admin_week['total_actions'] ?? 0);
if ($total_w > 0)
    $smart[] = ['🔐','purple', "Admin performed <strong>{$total_w} operational actions</strong> this week. Most frequent: <em>" . htmlspecialchars($top_action['action'] ?? 'N/A') . "</em>."];

// Unpaid students
$never_paid = (int)$fv['unp_c'];
if ($never_paid > 0)
    $smart[] = ['❗','red', "<strong>{$never_paid} enrolled student" . ($never_paid>1?'s have':'has') . " never made a single payment</strong>. These accounts need urgent follow-up."];

include 'includes/header.php';

// Chart data as JSON
$chart_labels    = json_encode(array_column($filled, 'label'));
$chart_collected = json_encode(array_column($filled, 'collected'));
$chart_txns      = json_encode(array_column($filled, 'txn_count'));
$course_labels   = json_encode(array_column($course_data, 'course'));
$course_paid     = json_encode(array_map(fn($c)=>(float)$c['tp'], $course_data));
$course_due      = json_encode(array_map(fn($c)=>(float)$c['td'], $course_data));
?>

<div class="container ins-page">

    <!-- ══ HEADER BAR ══ -->
    <div class="ins-topbar">
        <div>
            <h1 class="ins-title">📊 Insights Dashboard</h1>
            <p class="ins-subtitle">Live analytics · <?= date('d M Y, h:i A') ?></p>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;">
            <a href="print_report.php" class="act-ctrl-btn">🖨 Print</a>
            <a href="warnings.php" class="act-ctrl-btn" style="color:#92400e;border-color:#fcd34d;">⚠ Warnings</a>
        </div>
    </div>

    <!-- ══ ROW 1: 6 stat cards ══ -->
    <div class="ins-overview-grid">
        <div class="ins-card ins-card--crimson">
            <div class="ins-card-icon">🎓</div>
            <div class="ins-card-body">
                <div class="ins-card-num"><?= number_format($ov['total']) ?></div>
                <div class="ins-card-lbl">Total Students</div>
                <div class="ins-card-sub"><?= $ov['active'] ?> active · <?= $ov['inactive'] ?> inactive</div>
            </div>
        </div>
        <div class="ins-card ins-card--green">
            <div class="ins-card-icon">💰</div>
            <div class="ins-card-body">
                <div class="ins-card-num">₹<?= number_format($fv['tp']) ?></div>
                <div class="ins-card-lbl">Collected</div>
                <div class="ins-card-sub">avg ₹<?= number_format($avg_paid) ?>/student</div>
            </div>
        </div>
        <div class="ins-card ins-card--amber">
            <div class="ins-card-icon">⏳</div>
            <div class="ins-card-body">
                <div class="ins-card-num">₹<?= number_format($fv['td']) ?></div>
                <div class="ins-card-lbl">Pending</div>
                <div class="ins-card-sub"><?= $fv['unp_c'] ?> unpaid · <?= $fv['part_c'] ?> partial</div>
            </div>
        </div>
        <div class="ins-card ins-card--blue">
            <div class="ins-card-icon">📈</div>
            <div class="ins-card-body">
                <div class="ins-card-num"><?= $efficiency ?>%</div>
                <div class="ins-card-lbl">Efficiency</div>
                <div class="ins-eff-bar"><div class="ins-eff-fill" style="width:<?= $efficiency ?>%;background:<?= $efficiency>=75?'#22c55e':($efficiency>=50?'#f59e0b':'#ef4444') ?>;"></div></div>
            </div>
        </div>
        <div class="ins-card ins-card--red">
            <div class="ins-card-icon">🔴</div>
            <div class="ins-card-body">
                <div class="ins-card-num"><?= count($high_risk) ?></div>
                <div class="ins-card-lbl">High Risk</div>
                <div class="ins-card-sub"><?= count($medium_risk) ?> med · <?= count($low_risk) ?> low</div>
            </div>
        </div>
        <div class="ins-card ins-card--teal">
            <div class="ins-card-icon">✅</div>
            <div class="ins-card-body">
                <div class="ins-card-num"><?= $fv['paid_c'] ?></div>
                <div class="ins-card-lbl">Fully Paid</div>
                <div class="ins-card-sub">₹<?= number_format($fv['tf']) ?> total billed</div>
            </div>
        </div>
    </div>

    <!-- ══ ROW 2: Trend chart + Course chart side by side ══ -->
    <div class="ins-row2">
        <div class="ins-panel">
            <div class="ins-panel-head">
                <span>02 — Monthly Trend</span>
                <span class="ins-trend-badge ins-trend-badge--<?= $trend_dir ?>">
                    <?= $trend_dir==='up'?'▲':'▼' ?> <?= abs($trend_pct) ?>% vs last month
                </span>
            </div>
            <canvas id="trendChart" height="130"></canvas>
        </div>
        <div class="ins-panel">
            <div class="ins-panel-head">Course-wise Collection &amp; Pending</div>
            <canvas id="courseChart" height="130"></canvas>
        </div>
    </div>

    <!-- ══ ROW 3: Risk donut + Top 5 risk table + Admin stats ══ -->
    <div class="ins-row3">

        <!-- Risk donut -->
        <div class="ins-panel" style="display:flex;flex-direction:column;align-items:center;justify-content:center;">
            <div class="ins-panel-head" style="width:100%;text-align:left;">03 — Risk</div>
            <div class="ins-risk-donut-wrap">
                <canvas id="riskDonut" width="120" height="120"></canvas>
                <div class="ins-risk-center">
                    <div class="ins-risk-num"><?= count($risk_students) ?></div>
                    <div class="ins-risk-sub">Students</div>
                </div>
            </div>
            <div class="ins-risk-legend" style="width:100%;">
                <div class="ins-risk-leg-item"><span class="ins-risk-dot" style="background:#dc2626;"></span>High <strong><?= count($high_risk) ?></strong></div>
                <div class="ins-risk-leg-item"><span class="ins-risk-dot" style="background:#f59e0b;"></span>Medium <strong><?= count($medium_risk) ?></strong></div>
                <div class="ins-risk-leg-item"><span class="ins-risk-dot" style="background:#22c55e;"></span>Low <strong><?= count($low_risk) ?></strong></div>
            </div>
        </div>

        <!-- Top 5 risk table -->
        <div class="ins-panel">
            <div class="ins-panel-head">Top At-Risk Students</div>
            <table class="ins-table">
                <thead><tr><th>Student</th><th>Due</th><th>Score</th><th>Level</th></tr></thead>
                <tbody>
                <?php foreach ($top5_risk as $s):
                    $lvl_color = match($s['risk_level']) { 'High'=>'#dc2626,#fdecea','Medium'=>'#d97706,#fef3c7',default=>'#16a34a,#dcfce7' };
                    [$lc,$lb]  = explode(',',$lvl_color);
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700;font-size:.82rem;"><?= htmlspecialchars($s['name']) ?></div>
                        <div style="font-size:.68rem;color:var(--text-light);"><?= htmlspecialchars($s['roll_no']) ?> · <?= htmlspecialchars($s['course']) ?></div>
                    </td>
                    <td style="font-weight:800;color:#dc2626;white-space:nowrap;">₹<?= number_format($s['due_amount']) ?></td>
                    <td style="font-weight:800;color:<?= $lc ?>;font-size:.85rem;"><?= $s['risk_score'] ?></td>
                    <td><span style="background:<?= $lb ?>;color:<?= $lc ?>;padding:.15rem .5rem;border-radius:10px;font-size:.68rem;font-weight:800;"><?= $s['risk_level'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($top5_risk)): ?>
                <tr><td colspan="4" style="text-align:center;padding:1rem;color:#ccc;">🎉 No at-risk students</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Admin stats -->
        <div class="ins-panel">
            <div class="ins-panel-head">04 — Admin &amp; Emails</div>
            <div class="ins-admin-stats">
                <div class="ins-admin-stat"><span class="ins-admin-num"><?= $admin_week['total_actions']??0 ?></span><span class="ins-admin-lbl">Actions/Week</span></div>
                <div class="ins-admin-stat"><span class="ins-admin-num" style="color:#7c3aed;"><?= $admin_week['admin_actions']??0 ?></span><span class="ins-admin-lbl">Admin</span></div>
                <div class="ins-admin-stat"><span class="ins-admin-num" style="color:#059669;"><?= $emails_sent['c'] ?></span><span class="ins-admin-lbl">Email Sent</span></div>
                <div class="ins-admin-stat"><span class="ins-admin-num" style="color:#dc2626;"><?= $emails_failed['c'] ?></span><span class="ins-admin-lbl">Failed</span></div>
            </div>
            <canvas id="adminChart" height="80" style="margin-top:.5rem;"></canvas>
            <div style="margin-top:.6rem;padding:.5rem;background:#fff8e1;border-radius:8px;border:1px solid #fcd34d;display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:.75rem;font-weight:700;color:#92400e;">⚠ Warnings Sent</span>
                <span style="font-size:1.2rem;font-weight:900;color:#b45309;"><?= $warnings_sent_count['c'] ?></span>
            </div>
        </div>
    </div>

    <!-- ══ ROW 4: Smart Insights ══ -->
    <div class="ins-sec-label" style="margin-top:1rem;">05 — Auto Smart Insights</div>
    <div class="ins-smart-grid">
        <?php foreach ($smart as [$icon,$color,$text]):
            $colors=['green'=>['#d1fae5','#059669','#065f46'],'red'=>['#fdecea','#dc2626','#7f1d1d'],'blue'=>['#dbeafe','#1d4ed8','#1e3a8a'],'amber'=>['#fef3c7','#d97706','#78350f'],'purple'=>['#ede9fe','#7c3aed','#4c1d95'],'gray'=>['#f3f4f6','#6b7280','#1f2937'],'teal'=>['#d1fae5','#0d9488','#134e4a']];
            [$bg,$ic,$tc]=$colors[$color]??$colors['gray'];
        ?>
        <div class="ins-smart-card" style="border-left:3px solid <?= $ic ?>;">
            <div class="ins-smart-icon" style="background:<?= $bg ?>;color:<?= $ic ?>;"><?= $icon ?></div>
            <p style="color:<?= $tc ?>;font-size:.78rem;"><?= $text ?></p>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- ── Styles ── -->
<style>
.ins-page { padding-bottom: 1.5rem; }

/* Topbar */
.ins-topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:.9rem; flex-wrap:wrap; gap:.5rem; }
.ins-title   { font-family:'Cinzel',serif; font-size:1.1rem; font-weight:700; color:var(--crimson-dk); }
.ins-subtitle { font-size:.7rem; color:var(--text-light); margin-top:.15rem; }

/* Section label */
.ins-sec-label { font-family:'Cinzel',serif; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.15em; color:var(--crimson); padding:.3rem 0; border-bottom:2px solid #f5ebe0; margin-bottom:.65rem; margin-top:.9rem; }

/* ── ROW 1: 6 stat cards ── */
.ins-overview-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:.6rem; margin-bottom:.75rem; }
@media(max-width:1100px){ .ins-overview-grid { grid-template-columns:repeat(3,1fr); } }
@media(max-width:600px){  .ins-overview-grid { grid-template-columns:repeat(2,1fr); } }
.ins-card { background:#fff; border-radius:10px; padding:.75rem; border:1px solid rgba(139,0,0,.07); box-shadow:0 2px 8px rgba(0,0,0,.05); display:flex; align-items:center; gap:.6rem; transition:transform .2s,box-shadow .2s; }
.ins-card:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(0,0,0,.1); }
.ins-card-icon { font-size:1.1rem; flex-shrink:0; width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; }
.ins-card-num  { font-size:1.1rem; font-weight:900; font-family:'Cinzel',serif; line-height:1.1; }
.ins-card-lbl  { font-size:.6rem; text-transform:uppercase; letter-spacing:.06em; font-weight:700; color:var(--text-light); margin-top:.1rem; }
.ins-card-sub  { font-size:.58rem; color:#aaa; margin-top:.1rem; }
.ins-card--crimson .ins-card-num { color:var(--crimson-dk); } .ins-card--crimson .ins-card-icon { background:#fdecea; }
.ins-card--green  .ins-card-num { color:#059669; }           .ins-card--green  .ins-card-icon { background:#d1fae5; }
.ins-card--amber  .ins-card-num { color:#d97706; }           .ins-card--amber  .ins-card-icon { background:#fef3c7; }
.ins-card--blue   .ins-card-num { color:#1d4ed8; }           .ins-card--blue   .ins-card-icon { background:#dbeafe; }
.ins-card--red    .ins-card-num { color:#dc2626; }           .ins-card--red    .ins-card-icon { background:#fee2e2; }
.ins-card--teal   .ins-card-num { color:#0d9488; }           .ins-card--teal   .ins-card-icon { background:#ccfbf1; }
.ins-eff-bar  { height:4px; background:#e5e7eb; border-radius:10px; overflow:hidden; margin-top:.3rem; }
.ins-eff-fill { height:100%; border-radius:10px; }

/* ── Panels ── */
.ins-panel { background:#fff; border-radius:10px; border:1px solid rgba(139,0,0,.07); box-shadow:0 2px 8px rgba(0,0,0,.05); padding:.85rem 1rem; }
.ins-panel-head { font-family:'Cinzel',serif; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.09em; color:var(--crimson-dk); padding-bottom:.45rem; border-bottom:1px solid #f5ebe0; margin-bottom:.65rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.3rem; }
.ins-trend-badge { padding:.15rem .55rem; border-radius:20px; font-size:.65rem; font-weight:800; }
.ins-trend-badge--up   { background:#d1fae5; color:#059669; }
.ins-trend-badge--down { background:#fee2e2; color:#dc2626; }

/* ── ROW 2: Trend + Course side by side ── */
.ins-row2 { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:.75rem; }
@media(max-width:768px){ .ins-row2 { grid-template-columns:1fr; } }

/* ── ROW 3: Risk donut + Table + Admin ── */
.ins-row3 { display:grid; grid-template-columns:160px 1fr 220px; gap:.75rem; margin-bottom:.75rem; }
@media(max-width:900px){ .ins-row3 { grid-template-columns:1fr 1fr; } }
@media(max-width:600px){ .ins-row3 { grid-template-columns:1fr; } }

/* Risk donut */
.ins-risk-donut-wrap { position:relative; display:flex; justify-content:center; margin:.5rem 0 .5rem; }
.ins-risk-center { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center; pointer-events:none; }
.ins-risk-num { font-size:1.2rem; font-weight:900; font-family:'Cinzel',serif; color:var(--text-dark); line-height:1; }
.ins-risk-sub { font-size:.58rem; color:var(--text-light); text-transform:uppercase; }
.ins-risk-legend { display:flex; flex-direction:column; gap:.3rem; }
.ins-risk-leg-item { display:flex; align-items:center; gap:.4rem; font-size:.7rem; font-weight:600; }
.ins-risk-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }

/* Table */
.ins-table { width:100%; border-collapse:collapse; font-size:.78rem; }
.ins-table th { padding:.4rem .4rem; text-align:left; font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-light); border-bottom:2px solid #f5ebe0; }
.ins-table td { padding:.45rem .4rem; border-bottom:1px solid #f8f0e8; vertical-align:middle; }
.ins-table tr:hover { background:#fdf9f6; }
.ins-table tr:last-child td { border-bottom:none; }

/* Admin stats */
.ins-admin-stats { display:grid; grid-template-columns:1fr 1fr; gap:.5rem; margin-bottom:.5rem; }
.ins-admin-stat { text-align:center; padding:.45rem; background:#faf7f5; border-radius:7px; }
.ins-admin-num  { display:block; font-size:1.2rem; font-weight:900; font-family:'Cinzel',serif; color:var(--crimson-dk); line-height:1; }
.ins-admin-lbl  { display:block; font-size:.58rem; text-transform:uppercase; letter-spacing:.06em; color:var(--text-light); margin-top:.15rem; font-weight:700; }

/* Smart insights */
.ins-smart-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:.65rem; margin-bottom:1rem; }
.ins-smart-card { background:#fff; border-radius:9px; border:1px solid rgba(139,0,0,.07); box-shadow:0 1px 6px rgba(0,0,0,.04); padding:.75rem .85rem .75rem 1rem; display:flex; gap:.65rem; align-items:flex-start; }
.ins-smart-icon { width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0; }
.ins-smart-card p { font-size:.75rem; line-height:1.55; font-weight:600; margin:0; }

/* Dark mode */
[data-theme="dark"] .ins-card,.ins-panel,.ins-smart-card { }
[data-theme="dark"] .ins-card     { background:#1e1e28; border-color:rgba(255,255,255,.06); }
[data-theme="dark"] .ins-panel    { background:#1e1e28; border-color:rgba(255,255,255,.06); }
[data-theme="dark"] .ins-smart-card { background:#1e1e28; border-color:rgba(255,255,255,.06); }
[data-theme="dark"] .ins-table td { border-color:rgba(255,255,255,.05); }
[data-theme="dark"] .ins-table tr:hover { background:rgba(255,255,255,.03); }
[data-theme="dark"] .ins-admin-stat { background:#2a2a36; }
[data-theme="dark"] .ins-sec-label { border-color:rgba(255,255,255,.08); }
</style>

<!-- ── Chart.js via CDN ── -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
(function(){
    const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';
    const gridColor  = () => isDark() ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const labelColor = () => isDark() ? '#a08878' : '#8B6060';
    const cardBg     = () => isDark() ? '#1e1e28' : '#ffffff';

    const labels    = <?= $chart_labels ?>;
    const collected = <?= $chart_collected ?>;
    const txns      = <?= $chart_txns ?>;
    const cLabels   = <?= $course_labels ?>;
    const cPaid     = <?= $course_paid ?>;
    const cDue      = <?= $course_due ?>;

    // ── 1. Monthly Trend Line Chart ──
    const tCtx = document.getElementById('trendChart');
    if (tCtx) {
        new Chart(tCtx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Collected (₹)',
                    data: collected,
                    borderColor: '#8B0000',
                    backgroundColor: 'rgba(139,0,0,0.08)',
                    borderWidth: 2.5,
                    pointRadius: 5,
                    pointBackgroundColor: '#8B0000',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    fill: true,
                    tension: 0.4,
                }, {
                    label: 'Transactions',
                    data: txns,
                    borderColor: '#C9A84C',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [5,4],
                    pointRadius: 4,
                    pointBackgroundColor: '#C9A84C',
                    fill: false,
                    tension: 0.4,
                    yAxisID: 'y2',
                }]
            },
            options: {
                responsive:true,
                interaction:{mode:'index',intersect:false},
                plugins:{
                    legend:{labels:{font:{family:'Nunito',size:11},color:labelColor(),usePointStyle:true}},
                    tooltip:{callbacks:{label:ctx=>ctx.datasetIndex===0?'₹'+ctx.raw.toLocaleString():ctx.raw+' txns'}}
                },
                scales:{
                    x:{grid:{color:gridColor()},ticks:{color:labelColor(),font:{size:10}}},
                    y:{grid:{color:gridColor()},ticks:{color:labelColor(),font:{size:10},callback:v=>'₹'+v.toLocaleString()}},
                    y2:{position:'right',grid:{display:false},ticks:{color:'#C9A84C',font:{size:10}}},
                }
            }
        });
    }

    // ── 2. Course Bar Chart ──
    const cCtx = document.getElementById('courseChart');
    if (cCtx && cLabels.length) {
        new Chart(cCtx, {
            type: 'bar',
            data: {
                labels: cLabels,
                datasets: [
                    { label:'Collected', data:cPaid, backgroundColor:'rgba(5,150,105,0.75)', borderRadius:6, borderSkipped:false },
                    { label:'Pending',   data:cDue,  backgroundColor:'rgba(139,0,0,0.55)',  borderRadius:6, borderSkipped:false },
                ]
            },
            options: {
                responsive:true,
                plugins:{legend:{labels:{font:{family:'Nunito',size:11},color:labelColor(),usePointStyle:true}}},
                scales:{
                    x:{stacked:false,grid:{display:false},ticks:{color:labelColor(),font:{size:10}}},
                    y:{grid:{color:gridColor()},ticks:{color:labelColor(),font:{size:10},callback:v=>'₹'+v.toLocaleString()}},
                }
            }
        });
    }

    // ── 3. Risk Donut (pure canvas) ──
    const rCtx = document.getElementById('riskDonut');
    if (rCtx) {
        const vals   = [<?= count($high_risk) ?>, <?= count($medium_risk) ?>, <?= count($low_risk) ?>];
        const colors = ['#dc2626','#f59e0b','#22c55e'];
        const ctx2   = rCtx.getContext('2d');
        const cx = 60, cy = 60, r = 52, ir = 36;
        let start = -Math.PI/2;
        const sum = vals.reduce((a,b)=>a+b,0) || 1;
        vals.forEach((v,i)=>{
            const sweep = (v/sum)*Math.PI*2;
            ctx2.beginPath();
            ctx2.moveTo(cx,cy);
            ctx2.arc(cx,cy,r,start,start+sweep);
            ctx2.closePath();
            ctx2.fillStyle = colors[i];
            ctx2.fill();
            start += sweep;
        });
        ctx2.beginPath();
        ctx2.arc(cx,cy,ir,0,Math.PI*2);
        ctx2.fillStyle = cardBg();
        ctx2.fill();
    }

    // ── 4. Admin activity bar ──
    const aCtx = document.getElementById('adminChart');
    if (aCtx) {
        const aData = <?= json_encode(array_map(fn($d)=>['day'=>$d['day_name'],'c'=>$d['c']], $login_trend)) ?>;
        new Chart(aCtx, {
            type:'bar',
            data:{
                labels: aData.map(d=>d.day),
                datasets:[{ label:'Actions', data:aData.map(d=>d.c),
                    backgroundColor:'rgba(124,58,237,0.65)', borderRadius:6, borderSkipped:false }]
            },
            options:{
                responsive:true,
                plugins:{legend:{display:false}},
                scales:{
                    x:{grid:{display:false},ticks:{color:labelColor(),font:{size:10}}},
                    y:{grid:{color:gridColor()},ticks:{color:labelColor(),font:{size:10},stepSize:1}},
                }
            }
        });
    }

    // Re-draw on dark mode toggle
    document.getElementById('dmToggle')?.addEventListener('click', ()=>{
        setTimeout(()=>{
            const riskCanvas = document.getElementById('riskDonut');
            if (riskCanvas) {
                const c = riskCanvas.getContext('2d');
                const vals=[<?= count($high_risk) ?>,<?= count($medium_risk) ?>,<?= count($low_risk) ?>];
                const cols=['#dc2626','#f59e0b','#22c55e'];
                const sum=vals.reduce((a,b)=>a+b,0)||1;
                let s=-Math.PI/2;
                c.clearRect(0,0,120,120);
                vals.forEach((v,i)=>{const sw=(v/sum)*Math.PI*2;c.beginPath();c.moveTo(60,60);c.arc(60,60,52,s,s+sw);c.closePath();c.fillStyle=cols[i];c.fill();s+=sw;});
                c.beginPath();c.arc(60,60,36,0,Math.PI*2);c.fillStyle=cardBg();c.fill();
            }
        }, 100);
    });
})();
</script>

<?php include 'includes/footer.php'; ?>
