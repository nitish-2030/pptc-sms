<?php
// ============================================================
// fees.php — Student Fee Collection & Status (v2 — Secure)
// FIXED: Auth check, CSRF token, hide form when fully paid,
//        server-side overpayment guard, payment history shown
// ============================================================
require_once 'config/db.php';
require_once 'config/photo_helper.php';
require_once 'config/csrf_helper.php';
$pageTitle = 'Pay Fees';
$baseUrl   = '';

$roll_no     = trim($_GET['roll_no'] ?? '');
$student     = null;
$fee_summary = null;
$payments    = [];

if ($roll_no !== '') {
    $stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE roll_no = ?");
    mysqli_stmt_bind_param($stmt, 's', $roll_no);
    mysqli_stmt_execute($stmt);
    $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($student) {
        // Fee summary (use prepared statement)
        $f_stmt = mysqli_prepare($conn, "SELECT * FROM fees WHERE student_id = ?");
        mysqli_stmt_bind_param($f_stmt, 'i', $student['id']);
        mysqli_stmt_execute($f_stmt);
        $fee_summary = mysqli_fetch_assoc(mysqli_stmt_get_result($f_stmt));

        if (!$fee_summary) {
            $fee_summary = ['total_fee' => 0, 'paid_amount' => 0, 'due_amount' => 0, 'status' => 'Unpaid'];
        }

        // Payment history
        $p_stmt = mysqli_prepare($conn, "SELECT * FROM fee_payments WHERE student_id = ? ORDER BY id DESC");
        mysqli_stmt_bind_param($p_stmt, 'i', $student['id']);
        mysqli_stmt_execute($p_stmt);
        $p_res = mysqli_stmt_get_result($p_stmt);
        while ($p = mysqli_fetch_assoc($p_res)) {
            $payments[] = $p;
        }
    }
}

include 'includes/header.php';
?>

<div class="container-sm">
    <h1 class="page-title">💰 Collect Fees</h1>

    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="font-family:'Cinzel',serif;font-size:0.95rem;color:var(--crimson-dk);margin-bottom:0.5rem;">Search Student</h3>
        <p style="font-size:0.8rem;color:var(--text-light);margin-bottom:1rem;">Type a <strong>name</strong> or <strong>roll number</strong> — suggestions appear instantly.</p>
        <div class="smart-search-wrap">
            <form id="smartSearchForm" method="GET" action="fees.php">
                <div class="search-box">
                    <input type="text" id="smartSearchInput" name="roll_no" class="form-control"
                           placeholder="Search by name or roll number..."
                           value="<?= htmlspecialchars($roll_no) ?>" autocomplete="off" required>
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
            <div id="searchDropdown" class="search-dropdown"></div>
        </div>
    </div>

<style>
.smart-search-wrap { position: relative; }
.smart-search-wrap .search-box { margin-bottom: 0; }
.search-dropdown {
    position: absolute; top: 100%; left: 0; right: 0; z-index: 200;
    background: #fff; border: 1.5px solid #e8d8c0; border-top: none;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    max-height: 320px; overflow-y: auto;
    display: none;
}
.search-dropdown.open { display: block; }
.sd-item {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.6rem 1rem; cursor: pointer;
    border-bottom: 1px solid #f5ede0; transition: background 0.15s;
}
.sd-item:last-child { border-bottom: none; }
.sd-item:hover, .sd-item.active { background: #fdf6e3; }
.sd-avatar {
    width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
    background: var(--crimson); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Cinzel', serif; font-size: 0.85rem; font-weight: 700;
}
.sd-name  { font-weight: 700; font-size: 0.88rem; color: var(--text-dark); }
.sd-meta  { font-size: 0.75rem; color: var(--text-light); }
.sd-badge { font-size: 0.7rem; background: #f5ebe0; color: var(--crimson-dk); padding: 0.1rem 0.45rem; border-radius: 8px; margin-left: 4px; }
.sd-inactive { opacity: 0.5; }
.sd-no-result { padding: 0.75rem 1rem; color: var(--text-light); font-size: 0.85rem; font-style: italic; }
</style>

<script>
(function() {
    const input    = document.getElementById('smartSearchInput');
    const dropdown = document.getElementById('searchDropdown');
    const form     = document.getElementById('smartSearchForm');
    if (!input || !dropdown || !form) return;

    let debounce, currentFocus = -1, lastResults = [];

    input.addEventListener('input', function() {
        const q = this.value.trim();
        clearTimeout(debounce);
        currentFocus = -1;
        if (q.length < 2) { dropdown.classList.remove('open'); dropdown.innerHTML = ''; return; }
        debounce = setTimeout(() => fetchResults(q), 200);
    });

    function fetchResults(q) {
        fetch('ajax_name_search.php?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                lastResults = data;
                renderDropdown(data);
            })
            .catch(() => { dropdown.classList.remove('open'); });
    }

    function renderDropdown(data) {
        dropdown.innerHTML = '';
        if (!data.length) {
            dropdown.innerHTML = '<div class="sd-no-result">No students found — try a different name or roll no.</div>';
            dropdown.classList.add('open');
            return;
        }
        data.forEach((s, i) => {
            const div = document.createElement('div');
            div.className = 'sd-item' + (s.is_active ? '' : ' sd-inactive');
            div.dataset.roll = s.roll_no;
            div.innerHTML =
                '<div class="sd-avatar">' + escH(s.name.charAt(0).toUpperCase()) + '</div>' +
                '<div>' +
                    '<div class="sd-name">' + escH(s.name) + (s.is_active ? '' : ' <span style="color:#999;font-size:0.72rem;">(Inactive)</span>') + '</div>' +
                    '<div class="sd-meta">' + escH(s.roll_no) + '<span class="sd-badge">' + escH(s.course) + '</span></div>' +
                '</div>';
            div.addEventListener('mousedown', function(e) {
                e.preventDefault();
                selectStudent(s.roll_no);
            });
            dropdown.appendChild(div);
        });
        dropdown.classList.add('open');
    }

    function selectStudent(roll_no) {
        input.value = roll_no;
        dropdown.classList.remove('open');
        form.submit();
    }

    // Keyboard navigation
    input.addEventListener('keydown', function(e) {
        const items = dropdown.querySelectorAll('.sd-item');
        if (!items.length) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); currentFocus = Math.min(currentFocus + 1, items.length - 1); setActive(items); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); currentFocus = Math.max(currentFocus - 1, 0); setActive(items); }
        else if (e.key === 'Enter' && currentFocus >= 0) { e.preventDefault(); selectStudent(items[currentFocus].dataset.roll); }
        else if (e.key === 'Escape') { dropdown.classList.remove('open'); currentFocus = -1; }
    });

    function setActive(items) {
        items.forEach((el, i) => el.classList.toggle('active', i === currentFocus));
        if (currentFocus >= 0) items[currentFocus].scrollIntoView({ block: 'nearest' });
    }

    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!form.contains(e.target)) dropdown.classList.remove('open');
    });

    input.addEventListener('focus', function() {
        if (lastResults.length) dropdown.classList.add('open');
    });

    function escH(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>


    <?php if ($roll_no && !$student): ?>
        <div class="alert alert-error">❌ Student not found with Roll No: <strong><?= htmlspecialchars($roll_no) ?></strong></div>
    <?php endif; ?>

    <?php if ($student): ?>
    <div class="card">
        <!-- Student Header -->
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid #eee;">
            <?= render_avatar($student['photo'] ?? '', $student['name'], 'md') ?>
            <div>
                <h2 style="font-size:1.2rem;color:var(--crimson-dk);margin-bottom:0.2rem;"><?= htmlspecialchars($student['name']) ?></h2>
                <div style="font-size:0.85rem;color:var(--text-light);">
                    <?= htmlspecialchars($student['roll_no']) ?> &bull; <span class="course-badge"><?= htmlspecialchars($student['course']) ?></span>
                </div>
            </div>
        </div>

        <!-- Fee Stats Grid -->
        <div class="dashboard-grid" style="grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem;">
            <div style="text-align:center;padding:1rem;background:#fafafa;border-radius:8px;">
                <div style="font-size:0.8rem;color:#666;text-transform:uppercase;">Total Fee</div>
                <div style="font-size:1.4rem;font-weight:700;color:var(--text-dark);">₹<?= number_format($fee_summary['total_fee']) ?></div>
            </div>
            <div style="text-align:center;padding:1rem;background:#eafbea;border-radius:8px;">
                <div style="font-size:0.8rem;color:#1a7a1a;text-transform:uppercase;">Paid</div>
                <div style="font-size:1.4rem;font-weight:700;color:#1a7a1a;">₹<?= number_format($fee_summary['paid_amount']) ?></div>
            </div>
            <div style="text-align:center;padding:1rem;background:#fdecea;border-radius:8px;">
                <div style="font-size:0.8rem;color:var(--crimson);text-transform:uppercase;">Due</div>
                <div style="font-size:1.4rem;font-weight:700;color:var(--crimson);">₹<?= number_format($fee_summary['due_amount']) ?></div>
            </div>
        </div>

        <!-- Status badge -->
        <?php
        $status = $fee_summary['status'] ?? 'Unpaid';
        $status_color = match($status) { 'Paid' => '#1a7a1a', 'Partial' => '#a07800', default => 'var(--crimson)' };
        $status_bg    = match($status) { 'Paid' => '#eafbea', 'Partial' => '#fff8e1', default => '#fdecea' };
        ?>
        <div style="text-align:center;margin-bottom:1.5rem;">
            <span style="background:<?= $status_bg ?>;color:<?= $status_color ?>;padding:0.3rem 1rem;border-radius:20px;font-weight:700;font-size:0.85rem;">
                Fee Status: <?= htmlspecialchars($status) ?>
            </span>
        </div>

        <!-- Payment Form — only show if there is outstanding due -->
        <?php if ((float)$fee_summary['due_amount'] > 0): ?>
        <h3 style="font-family:'Cinzel',serif;font-size:1rem;color:var(--crimson-dk);margin-bottom:1rem;">Make a Payment</h3>
        <form action="process_fee.php" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
            <input type="hidden" name="roll_no"    value="<?= htmlspecialchars($student['roll_no']) ?>">
            <input type="hidden" name="max_due"    value="<?= $fee_summary['due_amount'] ?>">

            <div class="form-row">
                <div class="form-group">
                    <label>Amount (₹) — Max: ₹<?= number_format($fee_summary['due_amount']) ?></label>
                    <input type="number" name="amount" class="form-control" required
                           min="1"
                           max="<?= $fee_summary['due_amount'] ?>"
                           step="0.01"
                           placeholder="Enter amount">
                </div>
                <div class="form-group">
                    <label>Payment Mode</label>
                    <select name="mode" class="form-control">
                        <option>Cash</option>
                        <option>UPI</option>
                        <option>Card</option>
                        <option>Bank Transfer</option>
                        <option>Cheque</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">💳 Submit Payment</button>
        </form>
        <?php else: ?>
        <div class="alert alert-success" style="text-align:center;font-weight:700;">✅ All fees have been paid! No dues pending.</div>
        <?php endif; ?>
    </div>

    <!-- Payment History -->
    <?php if ($payments): ?>
    <div class="card" style="margin-top:1.5rem;">
        <h3 style="font-family:'Cinzel',serif;font-size:1rem;color:var(--crimson-dk);margin-bottom:1rem;">📜 Payment History</h3>
        <div class="table-wrapper">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Receipt No</th>
                    <th>Mode</th>
                    <th>Amount</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $i => $pay): ?>
                <tr>
                    <td style="color:var(--text-light);font-size:0.82rem;"><?= $i + 1 ?></td>
                    <td><?= date('d M Y', strtotime($pay['payment_date'])) ?></td>
                    <td><strong><?= htmlspecialchars($pay['receipt_no']) ?></strong></td>
                    <td><?= htmlspecialchars($pay['payment_mode']) ?></td>
                    <td style="font-weight:700;color:#1a7a1a;">₹<?= number_format($pay['amount'], 2) ?></td>
                    <td>
                        <a href="receipt.php?receipt=<?= urlencode($pay['receipt_no']) ?>" target="_blank" class="btn btn-gold btn-sm">🖨️ Print</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="btn-group" style="margin-top:1rem;">
        <a href="view.php?roll_no=<?= urlencode($student['roll_no']) ?>" class="btn btn-gold">👁️ Full Profile</a>
        <a href="view_all.php" class="btn btn-outline">← All Students</a>
    </div>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
