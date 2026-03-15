<?php
// ============================================================
// view.php — Full Student Profile (v3 — all fields)
// ============================================================
require_once 'config/db.php';
require_once 'config/photo_helper.php';
$pageTitle = 'Student Profile';
$baseUrl   = '';

$roll_no = trim($_GET['roll_no'] ?? '');
$student = null;

if ($roll_no !== '') {
    $stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE roll_no = ?");
    mysqli_stmt_bind_param($stmt, 's', $roll_no);
    mysqli_stmt_execute($stmt);
    $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$fee_summary = null;
$payments    = [];
$course_title = '';

if ($student) {
    $f = mysqli_prepare($conn, "SELECT * FROM fees WHERE student_id = ?");
    mysqli_stmt_bind_param($f, 'i', $student['id']);
    mysqli_stmt_execute($f);
    $fee_summary = mysqli_fetch_assoc(mysqli_stmt_get_result($f));

    $p = mysqli_prepare($conn, "SELECT * FROM fee_payments WHERE student_id = ? ORDER BY id DESC");
    mysqli_stmt_bind_param($p, 'i', $student['id']);
    mysqli_stmt_execute($p);
    $pr = mysqli_stmt_get_result($p);
    while ($row = mysqli_fetch_assoc($pr)) $payments[] = $row;

    $cr = mysqli_prepare($conn, "SELECT full_title FROM courses WHERE code = ? LIMIT 1");
    mysqli_stmt_bind_param($cr, 's', $student['course']);
    mysqli_stmt_execute($cr);
    $crow = mysqli_fetch_assoc(mysqli_stmt_get_result($cr));
    if ($crow) $course_title = $crow['full_title'];
}

// Calculate age from dob
function calc_age($dob) {
    if (!$dob) return null;
    $d = new DateTime($dob);
    return $d->diff(new DateTime())->y;
}

include 'includes/header.php';
?>

<div class="container-sm">
    <h1 class="page-title">👤 Student Profile</h1>

    <?php if (!$student): ?>
    <div class="alert alert-error">❌ Student not found.</div>
    <div class="btn-group" style="justify-content:center;">
        <a href="dashboard.php" class="btn btn-primary">← Dashboard</a>
    </div>

    <?php else: ?>

    <!-- ── Profile Card ── -->
    <div class="detail-card">
        <div class="detail-card-header">
            <?= render_avatar($student['photo'] ?? '', $student['name'], 'lg') ?>
            <div class="detail-name"><?= htmlspecialchars($student['name']) ?></div>
            <p style="color:rgba(255,255,255,0.65);font-size:0.85rem;margin-top:0.2rem;">
                <?= htmlspecialchars($student['roll_no']) ?>
                <?php if (!empty($student['gender'])): ?>
                 · <?= htmlspecialchars($student['gender']) ?>
                <?php endif; ?>
            </p>
            <?php if (!empty($student['category'])): ?>
            <span style="background:rgba(201,168,76,0.2);color:var(--gold-lt);padding:0.15rem 0.7rem;border-radius:12px;font-size:0.75rem;margin-top:0.4rem;display:inline-block;">
                <?= htmlspecialchars($student['category']) ?>
            </span>
            <?php endif; ?>
            <span class="badge <?= $student['is_active'] ? 'badge-active' : 'badge-inactive' ?>" style="margin-top:0.6rem;">
                <?= $student['is_active'] ? '● Active' : '● Inactive' ?>
            </span>
        </div>

        <div class="detail-body">

            <!-- ACADEMIC -->
            <div class="profile-section-label">🎓 Academic</div>
            <div class="detail-row">
                <span class="detail-key">Roll Number</span>
                <span class="detail-val"><strong><?= htmlspecialchars($student['roll_no']) ?></strong></span>
            </div>
            <div class="detail-row">
                <span class="detail-key">Course</span>
                <span class="detail-val">
                    <span class="course-badge"><?= htmlspecialchars($student['course']) ?></span>
                    <?php if ($course_title): ?>
                    <span style="font-size:0.78rem;color:var(--text-light);margin-left:0.5rem;"><?= htmlspecialchars($course_title) ?></span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-key">Admission Date</span>
                <span class="detail-val"><?= date('d F Y', strtotime($student['admission_date'])) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-key">Status</span>
                <span class="detail-val">
                    <span class="badge <?= $student['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                        <?= $student['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </span>
            </div>

            <!-- PERSONAL -->
            <div class="profile-section-label" style="margin-top:1rem;">👤 Personal</div>
            <?php if (!empty($student['dob'])): ?>
            <div class="detail-row">
                <span class="detail-key">Date of Birth</span>
                <span class="detail-val">
                    <?= date('d F Y', strtotime($student['dob'])) ?>
                    <?php $age = calc_age($student['dob']); if ($age): ?>
                    <span style="color:var(--text-light);font-size:0.82rem;"> (<?= $age ?> yrs)</span>
                    <?php endif; ?>
                </span>
            </div>
            <?php endif; ?>
            <?php if (!empty($student['gender'])): ?>
            <div class="detail-row">
                <span class="detail-key">Gender</span>
                <span class="detail-val"><?= htmlspecialchars($student['gender']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($student['blood_group'])): ?>
            <div class="detail-row">
                <span class="detail-key">Blood Group</span>
                <span class="detail-val">
                    <span style="background:#fdecea;color:var(--crimson);padding:0.1rem 0.5rem;border-radius:8px;font-weight:700;">
                        <?= htmlspecialchars($student['blood_group']) ?>
                    </span>
                </span>
            </div>
            <?php endif; ?>
            <?php if (!empty($student['category'])): ?>
            <div class="detail-row">
                <span class="detail-key">Category</span>
                <span class="detail-val"><?= htmlspecialchars($student['category']) ?></span>
            </div>
            <?php endif; ?>

            <!-- CONTACT -->
            <div class="profile-section-label" style="margin-top:1rem;">📞 Contact</div>
            <?php if (!empty($student['phone'])): ?>
            <div class="detail-row">
                <span class="detail-key">Phone</span>
                <span class="detail-val">
                    <a href="tel:<?= htmlspecialchars($student['phone']) ?>" style="color:var(--crimson-dk);font-weight:600;">
                        📞 <?= htmlspecialchars($student['phone']) ?>
                    </a>
                </span>
            </div>
            <?php endif; ?>
            <?php if (!empty($student['email'])): ?>
            <div class="detail-row">
                <span class="detail-key">Email</span>
                <span class="detail-val">
                    <a href="mailto:<?= htmlspecialchars($student['email']) ?>" style="color:var(--crimson-dk);">
                        ✉️ <?= htmlspecialchars($student['email']) ?>
                    </a>
                </span>
            </div>
            <?php endif; ?>
            <?php if (!empty($student['address'])): ?>
            <div class="detail-row">
                <span class="detail-key">Address</span>
                <span class="detail-val" style="max-width:320px;"><?= nl2br(htmlspecialchars($student['address'])) ?></span>
            </div>
            <?php endif; ?>

            <!-- GUARDIAN -->
            <?php if (!empty($student['guardian_name']) || !empty($student['guardian_phone'])): ?>
            <div class="profile-section-label" style="margin-top:1rem;">👨‍👩‍👧 Guardian</div>
            <?php if (!empty($student['guardian_name'])): ?>
            <div class="detail-row">
                <span class="detail-key">Guardian Name</span>
                <span class="detail-val"><?= htmlspecialchars($student['guardian_name']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($student['guardian_phone'])): ?>
            <div class="detail-row">
                <span class="detail-key">Guardian Phone</span>
                <span class="detail-val">
                    <a href="tel:<?= htmlspecialchars($student['guardian_phone']) ?>" style="color:var(--crimson-dk);font-weight:600;">
                        📞 <?= htmlspecialchars($student['guardian_phone']) ?>
                    </a>
                </span>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <!-- META -->
            <div class="profile-section-label" style="margin-top:1rem;">🗂 System</div>
            <div class="detail-row">
                <span class="detail-key">Student ID</span>
                <span class="detail-val">#<?= $student['id'] ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-key">Registered On</span>
                <span class="detail-val"><?= date('d M Y, h:i A', strtotime($student['created_at'])) ?></span>
            </div>
        </div>
    </div>

    <!-- ── Fee Summary ── -->
    <div class="card" style="margin-top:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h3 style="font-family:'Cinzel',serif;font-size:1.1rem;color:var(--crimson-dk);margin:0;">💰 Fee Summary</h3>
            <a href="fees.php?roll_no=<?= urlencode($student['roll_no']) ?>" class="btn btn-primary btn-sm">💳 Pay Fees</a>
        </div>

        <?php if ($fee_summary): ?>
        <?php
        $status      = $fee_summary['status'] ?? 'Unpaid';
        $status_col  = match($status) { 'Paid' => '#1a7a1a', 'Partial' => '#a07800', default => 'var(--crimson)' };
        $status_bg   = match($status) { 'Paid' => '#eafbea', 'Partial' => '#fff8e1',  default => '#fdecea' };
        $pct         = $fee_summary['total_fee'] > 0
                         ? round(($fee_summary['paid_amount'] / $fee_summary['total_fee']) * 100)
                         : 0;
        ?>
        <div class="dashboard-grid" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:1rem;margin-bottom:1rem;">
            <div style="padding:0.8rem;background:#fafafa;border-radius:8px;text-align:center;">
                <small style="color:#666;">Total Fee</small><br>
                <strong style="font-size:1.1rem;">₹<?= number_format($fee_summary['total_fee']) ?></strong>
            </div>
            <div style="padding:0.8rem;background:#eafbea;border-radius:8px;text-align:center;">
                <small style="color:#1a7a1a;">Paid</small><br>
                <strong style="font-size:1.1rem;color:#1a7a1a;">₹<?= number_format($fee_summary['paid_amount']) ?></strong>
            </div>
            <div style="padding:0.8rem;background:#fdecea;border-radius:8px;text-align:center;">
                <small style="color:var(--crimson);">Due</small><br>
                <strong style="font-size:1.1rem;color:var(--crimson);">₹<?= number_format($fee_summary['due_amount']) ?></strong>
            </div>
        </div>
        <!-- Progress bar -->
        <div style="margin-bottom:0.75rem;">
            <div style="display:flex;justify-content:space-between;font-size:0.78rem;color:var(--text-light);margin-bottom:0.3rem;">
                <span>Collection Progress</span>
                <span><?= $pct ?>% paid</span>
            </div>
            <div style="background:#eee;border-radius:20px;height:10px;overflow:hidden;">
                <div style="width:<?= $pct ?>%;background:<?= $pct>=100 ? '#1a7a1a' : 'var(--crimson)' ?>;height:100%;border-radius:20px;transition:width 0.5s;"></div>
            </div>
        </div>
        <div style="text-align:center;">
            <span style="background:<?= $status_bg ?>;color:<?= $status_col ?>;padding:0.25rem 0.9rem;border-radius:20px;font-weight:700;font-size:0.82rem;">
                <?= $status ?>
            </span>
        </div>
        <?php else: ?>
        <p style="color:var(--text-light);font-style:italic;">No fee record found.</p>
        <?php endif; ?>

        <?php if ($payments): ?>
        <h4 style="font-size:0.9rem;margin:1rem 0 0.5rem;color:var(--text-mid);">Payment History</h4>
        <table class="styled-table">
            <thead><tr><th>Date</th><th>Receipt</th><th>Mode</th><th>Amount</th><th>Print</th></tr></thead>
            <tbody>
                <?php foreach ($payments as $pay): ?>
                <tr>
                    <td><?= date('d M Y', strtotime($pay['payment_date'])) ?></td>
                    <td><strong><?= htmlspecialchars($pay['receipt_no']) ?></strong></td>
                    <td><?= htmlspecialchars($pay['payment_mode']) ?></td>
                    <td style="font-weight:700;color:#1a7a1a;">₹<?= number_format($pay['amount']) ?></td>
                    <td><a href="receipt.php?receipt=<?= urlencode($pay['receipt_no']) ?>" target="_blank" class="btn btn-gold btn-sm">🖨️</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Actions -->
    <div class="btn-group" style="justify-content:center;margin-top:1.5rem;">
        <a href="update.php?roll_no=<?= urlencode($student['roll_no']) ?>" class="btn btn-primary">✏️ Edit</a>
        <?php if ($student['is_active']): ?>
        <a href="delete.php?roll_no=<?= urlencode($student['roll_no']) ?>" class="btn btn-danger">🗑️ Deactivate</a>
        <?php endif; ?>
        <a href="view_all.php"  class="btn btn-gold">📋 All Students</a>
        <a href="dashboard.php" class="btn btn-outline">← Dashboard</a>
    </div>

    <?php endif; ?>
</div>

<style>
.profile-section-label {
    font-family: 'Cinzel', serif;
    font-size: 0.72rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--crimson-dk);
    padding: 0.4rem 0;
    border-bottom: 1px solid #f0e8e8;
    margin-bottom: 0.5rem;
}
</style>

<?php include 'includes/footer.php'; ?>
