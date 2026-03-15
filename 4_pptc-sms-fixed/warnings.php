<?php
// ============================================================
// warnings.php — Fee Warning System (v4)
// Lists students with due > WARNING_THRESHOLD (₹15,000)
// Admin can send warning email per student
// ============================================================
require_once 'config/db.php';
require_once 'config/email_helper.php';
require_once 'config/activity_helper.php';
$pageTitle = 'Fee Warnings';
$baseUrl   = '';

$msg_success = $msg_error = '';

// ── Handle warning send (POST) ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_warning'])) {
    require_once 'config/csrf_helper.php';
    csrf_validate();

    $warn_student_id = (int)($_POST['student_id'] ?? 0);
    if ($warn_student_id > 0) {
        // Fetch student + due
        $ws = mysqli_prepare($conn,
            "SELECT s.*, f.due_amount FROM students s
             LEFT JOIN fees f ON s.id = f.student_id
             WHERE s.id = ? AND s.is_active = 1"
        );
        mysqli_stmt_bind_param($ws, 'i', $warn_student_id);
        mysqli_stmt_execute($ws);
        $ws_row = mysqli_fetch_assoc(mysqli_stmt_get_result($ws));

        if ($ws_row) {
            $email_result = send_email($conn, 'warning', $ws_row, [
                'due_amount' => $ws_row['due_amount']
            ]);

            // Update warning_sent_at
            @mysqli_query($conn,
                "UPDATE students SET warning_sent_at = NOW() WHERE id = $warn_student_id"
            );

            log_activity($conn, 'student',
                "Warning sent to {$ws_row['name']} — due ₹" . number_format($ws_row['due_amount']),
                "Email: {$email_result['message']}",
                $warn_student_id, $ws_row['name']
            );
            log_activity($conn, 'email',
                "Warning email {$email_result['message']}",
                "Trigger: warning | Due: ₹" . number_format($ws_row['due_amount']),
                $warn_student_id, $ws_row['name']
            );

            if ($email_result['success']) {
                $msg_success = "Warning sent to <strong>{$ws_row['name']}</strong> successfully.";
            } else {
                // On localhost (XAMPP), mail() doesn't work — warning is still saved in DB
                $msg_success = "Warning logged for <strong>{$ws_row['name']}</strong>. (Email not available on localhost — warning saved in activity log.)";
            }
        }
    }
}

// ── Fetch all students with due > threshold ──────────────────
$threshold = WARNING_THRESHOLD;
$res = mysqli_query($conn,
    "SELECT s.id, s.name, s.roll_no, s.course, s.email, s.phone,
            s.warning_sent_at, f.total_fee, f.paid_amount, f.due_amount, f.status AS fee_status
     FROM students s
     JOIN fees f ON s.id = f.student_id
     WHERE f.due_amount > $threshold AND s.is_active = 1
     ORDER BY f.due_amount DESC"
);
$warned = [];
while ($row = mysqli_fetch_assoc($res)) $warned[] = $row;
$total_warned = count($warned);
$total_due_sum = array_sum(array_column($warned, 'due_amount'));

// Stats for top bar
$all_due_res = mysqli_query($conn, "SELECT COUNT(*) AS c, SUM(due_amount) AS s FROM fees WHERE due_amount > 0");
$all_due = mysqli_fetch_assoc($all_due_res);

include 'includes/header.php';
require_once 'config/csrf_helper.php';
?>

<!-- Pay Modal -->
<div class="pay-modal-overlay" id="payModalOverlay">
    <div class="pay-modal">
        <div class="pay-modal-header">
            <span class="pay-modal-title">&#x1F4B3; Collect Fee Payment</span>
            <button class="pay-modal-close" onclick="closePayModal()">&#x2715;</button>
        </div>
        <div class="pay-modal-info">
            <div><strong id="modalStudentName">&#8212;</strong></div>
            <div class="pay-modal-meta" id="modalStudentMeta"></div>
            <div style="margin-top:.5rem;font-size:.85rem;">Due: <strong id="modalDueAmount" style="color:var(--crimson);"></strong></div>
        </div>
        <p style="font-size:.8rem;color:var(--text-light);margin-bottom:1rem;">You will be taken to the fee collection page for this student.</p>
        <div style="display:flex;gap:.6rem;">
            <a id="modalPayLink" href="#" class="btn btn-primary" style="flex:1;justify-content:center;">Proceed to Pay</a>
            <button onclick="closePayModal()" class="btn btn-outline" style="flex:1;justify-content:center;">Cancel</button>
        </div>
    </div>
</div>

<div class="container">
    <h1 class="page-title">&#x26A0;&#xFE0F; Fee Warning System</h1>

    <?php if ($msg_success): ?>
    <div class="alert alert-success" style="max-width:700px;margin:0 auto 1.5rem;">✅ <?= $msg_success ?></div>
    <?php endif; ?>
    <?php if ($msg_error): ?>
    <div class="alert alert-error" style="max-width:700px;margin:0 auto 1.5rem;">❌ <?= $msg_error ?></div>
    <?php endif; ?>

    <!-- ── Stats Row ── -->
    <div class="warn-stats">
        <div class="warn-stat warn-stat--red">
            <span class="warn-stat-icon">&#x26A0;&#xFE0F;</span>
            <div>
                <span class="warn-stat-num"><?= $total_warned ?></span>
                <span class="warn-stat-lbl">At-Risk Students<br><small>Due &gt; &#x20B9;<?= number_format($threshold) ?></small></span>
            </div>
        </div>
        <div class="warn-stat warn-stat--amber">
            <span class="warn-stat-icon">&#x1F4B8;</span>
            <div>
                <span class="warn-stat-num">&#x20B9;<?= number_format($total_due_sum) ?></span>
                <span class="warn-stat-lbl">Total Overdue<br><small>From listed students</small></span>
            </div>
        </div>
        <div class="warn-stat warn-stat--blue">
            <span class="warn-stat-icon">&#x1F464;</span>
            <div>
                <span class="warn-stat-num"><?= $all_due['c'] ?></span>
                <span class="warn-stat-lbl">Students with Any Due<br><small>Across all students</small></span>
            </div>
        </div>
        <div class="warn-stat warn-stat--gray">
            <span class="warn-stat-icon">&#x1F4CA;</span>
            <div>
                <span class="warn-stat-num">&#x20B9;<?= number_format($all_due['s']) ?></span>
                <span class="warn-stat-lbl">Total System Due<br><small>All students combined</small></span>
            </div>
        </div>
    </div>

    <!-- ── Threshold info (softer) ── -->
    <div class="warn-banner">
        <span class="warn-banner-icon">&#x26A0;&#xFE0F;</span>
        <span>Warning threshold is <strong>&#x20B9;<?= number_format($threshold) ?></strong>. Students exceeding this due amount are listed below. Click <strong>Send Warning</strong> to email them directly.</span>
    </div>

    <!-- ── Student Warning Table ── -->
    <?php if ($warned): ?>
    <div class="va-table-wrap">
        <table class="va-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Total Fee</th>
                    <th>Paid</th>
                    <th style="color:#fca5a5;">Due Amount</th>
                    <th>Email Status</th>
                    <th>Last Warning</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($warned as $i => $s):
                    $due_pct = $s['total_fee'] > 0 ? round(($s['due_amount']/$s['total_fee'])*100) : 0;
                    $has_email = !empty($s['email']);
                    $warn_sent = !empty($s['warning_sent_at']);
                    $roll_enc  = urlencode($s['roll_no']);
                ?>
                <tr class="va-row">
                    <td class="va-td-num"><?= $i+1 ?></td>
                    <td class="va-td-student">
                        <div class="va-avatar" style="background:linear-gradient(135deg,var(--crimson-dk),var(--crimson));">
                            <?= strtoupper(mb_substr($s['name'],0,1)) ?>
                        </div>
                        <div class="va-student-info">
                            <span class="va-name" style="font-weight:700;"><?= htmlspecialchars($s['name']) ?></span>
                            <span class="va-roll" style="font-size:.68rem;color:var(--text-light);"><?= htmlspecialchars($s['roll_no']) ?></span>
                        </div>
                    </td>
                    <td><span class="va-course"><?= htmlspecialchars($s['course']) ?></span></td>
                    <td style="font-size:.83rem;">₹<?= number_format($s['total_fee']) ?></td>
                    <td style="font-size:.83rem;color:#1a7a1a;font-weight:700;">₹<?= number_format($s['paid_amount']) ?></td>
                    <td>
                        <div style="font-weight:800;color:var(--danger);font-size:.9rem;">&#x20B9;<?= number_format($s['due_amount']) ?></div>
                        <div style="font-size:.63rem;color:#aaa;"><?= $due_pct ?>% of total</div>
                        <div class="due-bar-track">
                            <?php
                            $bar_c = $due_pct >= 75 ? '#dc2626' : ($due_pct >= 50 ? '#d97706' : '#16a34a');
                            ?>
                            <div style="width:<?= $due_pct ?>%;height:100%;background:<?= $bar_c ?>;border-radius:10px;"></div>
                        </div>
                    </td>
                    <td>
                        <?php if ($has_email): ?>
                        <span style="background:#eafbea;color:#1a7a1a;padding:.15rem .55rem;border-radius:12px;font-size:.7rem;font-weight:700;">✓ <?= htmlspecialchars($s['email']) ?></span>
                        <?php else: ?>
                        <span style="background:#fdecea;color:#c0392b;padding:.15rem .55rem;border-radius:12px;font-size:.7rem;font-weight:700;">✗ Not Registered</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.75rem;color:#888;">
                        <?php if ($warn_sent): ?>
                        <span style="color:#b45309;font-weight:700;">⚠ <?= date('d M Y', strtotime($s['warning_sent_at'])) ?></span>
                        <?php else: ?>
                        <span style="color:#ccc;">Never</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                        <div class="warn-actions">
                            <form method="POST" action="warnings.php" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                <button type="submit" name="send_warning" value="1"
                                        class="warn-send-btn"
                                        <?= !$has_email ? 'disabled title="No email registered"' : 'title="Send warning email"' ?>>
                                    &#x26A0; Warning
                                </button>
                            </form>
                            <a href="#" class="va-btn va-btn--fee" title="Collect Fee" onclick="openWarnPayModal(event,'<?= htmlspecialchars(addslashes($s['name'])) ?>','<?= $s['roll_no'] ?>','<?= $s['course'] ?>','&#x20B9;<?= number_format($s['due_amount']) ?>','fees.php?roll_no=<?= $roll_enc ?>')">&#x20B9;</a>
                            <a href="view.php?roll_no=<?= $roll_enc ?>"  class="va-btn va-btn--view" title="View Profile">&#x1F441;</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#fff8e1;">
                    <td colspan="5" style="padding:.65rem 1rem;font-size:.8rem;font-weight:800;color:#92400e;">
                        TOTAL (<?= $total_warned ?> at-risk students)
                    </td>
                    <td style="padding:.65rem 1rem;font-weight:800;color:#c0392b;font-size:.95rem;">
                        ₹<?= number_format($total_due_sum) ?>
                    </td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php else: ?>
    <div style="text-align:center;padding:4rem 1rem;background:#fff;border-radius:12px;border:1px solid #f0e8e0;">
        <div style="font-size:3rem;margin-bottom:1rem;">🎉</div>
        <h3 style="color:#1a7a1a;font-family:'Cinzel',serif;margin-bottom:.5rem;">All Clear!</h3>
        <p style="color:#888;font-size:.9rem;">No students have a due amount exceeding ₹<?= number_format($threshold) ?>.</p>
    </div>
    <?php endif; ?>

</div>

<style>
.warn-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin-bottom:1.5rem;}
.warn-stat{background:#fff;border-radius:12px;padding:1rem 1.15rem;border:1px solid rgba(139,0,0,.06);box-shadow:0 2px 8px rgba(0,0,0,.04);display:flex;align-items:center;gap:.85rem;transition:box-shadow .2s;}
.warn-stat:hover{box-shadow:0 4px 16px rgba(0,0,0,.08);}
.warn-stat-icon{font-size:1.3rem;flex-shrink:0;}
.warn-stat-num{display:block;font-size:1.6rem;font-weight:900;line-height:1;font-family:'Cinzel',serif;margin-bottom:.15rem;}
.warn-stat-lbl{font-size:.7rem;color:var(--text-light);font-weight:600;line-height:1.5;}
.warn-stat-lbl small{display:block;font-size:.62rem;color:#bbb;margin-top:.1rem;}
.warn-stat--red  {border-left:4px solid #dc2626;}
.warn-stat--red  .warn-stat-num{color:#dc2626;}
.warn-stat--amber{border-left:4px solid #d97706;}
.warn-stat--amber .warn-stat-num{color:#d97706;}
.warn-stat--blue {border-left:4px solid #2563eb;}
.warn-stat--blue .warn-stat-num{color:#2563eb;}
.warn-stat--gray {border-left:4px solid #9ca3af;}
.warn-stat--gray .warn-stat-num{color:#4b5563;}
/* At-risk is most prominent */
.warn-stat--red .warn-stat-num{font-size:2rem!important;}
</style>

<script>
function openWarnPayModal(e,name,roll,course,due,payUrl){
    e.preventDefault();
    document.getElementById('modalStudentName').textContent=name;
    document.getElementById('modalStudentMeta').textContent=roll+' • '+course;
    document.getElementById('modalDueAmount').textContent=due;
    document.getElementById('modalPayLink').href=payUrl;
    document.getElementById('payModalOverlay').classList.add('open');
}
function closePayModal(){
    document.getElementById('payModalOverlay').classList.remove('open');
}
document.addEventListener('DOMContentLoaded',function(){
    var overlay=document.getElementById('payModalOverlay');
    if(overlay){
        overlay.addEventListener('click',function(e){if(e.target===this)closePayModal();});
    }
});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closePayModal();});
</script>

<?php include 'includes/footer.php'; ?>
