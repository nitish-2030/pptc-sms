<?php
// ============================================================
// delete.php — Soft Delete with full details + photo
// ============================================================
require_once 'config/db.php';
require_once 'config/csrf_helper.php';
require_once 'config/photo_helper.php';
$pageTitle = 'Deactivate Student';
$baseUrl   = '';

$student = null;
$success = $error = '';

if (isset($_GET['status'])) {
    $success = $_GET['status'] === 'success' ? 'Student deactivated successfully.' : '';
    $error   = $_GET['status'] === 'error'   ? htmlspecialchars($_GET['msg'] ?? 'Error.') : '';
}

$roll_no = trim($_GET['roll_no'] ?? '');
if ($roll_no !== '') {
    $stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE roll_no = ?");
    mysqli_stmt_bind_param($stmt, 's', $roll_no);
    mysqli_stmt_execute($stmt);
    $res     = mysqli_stmt_get_result($stmt);
    $student = mysqli_fetch_assoc($res);
    if (!$student) $error = 'No student found with Roll No: ' . htmlspecialchars($roll_no);
}

// Resolve course full title
$course_title = '';
if ($student) {
    $cr = @mysqli_query($conn, "SELECT full_title FROM courses WHERE code='" . mysqli_real_escape_string($conn, $student['course']) . "' LIMIT 1");
    if ($cr && $row = mysqli_fetch_assoc($cr)) {
        $course_title = $row['full_title'];
    }
}

include 'includes/header.php';
?>

<div class="container-sm">
    <h1 class="page-title">🗑️ Deactivate Student</h1>

    <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error">❌ <?= $error ?></div><?php endif; ?>

    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="font-family:'Cinzel',serif;font-size:0.95rem;color:var(--crimson-dk);margin-bottom:0.5rem;">Search Student</h3>
        <p style="font-size:0.8rem;color:var(--text-light);margin-bottom:1rem;">Type a <strong>name</strong> or <strong>roll number</strong> — suggestions appear instantly.</p>
        <div class="smart-search-wrap">
            <form id="smartSearchForm" method="GET" action="delete.php">
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


    <?php if ($student): ?>
    <div class="card">

        <!-- Warning notice -->
        <div class="alert alert-info" style="margin-bottom:1.5rem;">
            ℹ️ Review <strong>all details carefully</strong> before deactivating.
            The record will <strong>NOT</strong> be permanently deleted — only marked Inactive.
        </div>

        <!-- Full detail card with photo -->
        <div class="detail-card">
            <div class="detail-card-header">
                <?= render_avatar($student['photo'] ?? '', $student['name'], 'lg') ?>
                <div class="detail-name"><?= htmlspecialchars($student['name']) ?></div>
                <p style="color:rgba(255,255,255,0.65);font-size:0.85rem;margin-top:0.2rem;">
                    <?= htmlspecialchars($student['roll_no']) ?>
                </p>
                <span class="badge <?= $student['is_active'] ? 'badge-active' : 'badge-inactive' ?>"
                      style="margin-top:0.5rem;">
                    Currently: <?= $student['is_active'] ? 'Active' : 'Already Inactive' ?>
                </span>
            </div>

            <!-- ALL details so admin can confirm correct student -->
            <div class="detail-body">
                <div class="detail-row">
                    <span class="detail-key">Student ID</span>
                    <span class="detail-val">#<?= $student['id'] ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Roll Number</span>
                    <span class="detail-val"><strong><?= htmlspecialchars($student['roll_no']) ?></strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Full Name</span>
                    <span class="detail-val"><?= htmlspecialchars($student['name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Course</span>
                    <span class="detail-val">
                        <span class="course-badge"><?= htmlspecialchars($student['course']) ?></span>
                        <?php if ($course_title): ?>
                        <br><span style="font-size:0.75rem;color:var(--text-light);margin-top:0.3rem;display:inline-block;">
                            <?= htmlspecialchars($course_title) ?>
                        </span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Admission Date</span>
                    <span class="detail-val"><?= date('d F Y', strtotime($student['admission_date'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Registered On</span>
                    <span class="detail-val"><?= date('d M Y, h:i A', strtotime($student['created_at'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Current Status</span>
                    <span class="detail-val">
                        <span class="badge <?= $student['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $student['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <?php if ($student['is_active']): ?>
        <form action="process_delete.php" method="POST" style="margin-top:1.5rem;">
            <?= csrf_field() ?>
            <input type="hidden" name="id"      value="<?= $student['id'] ?>">
            <input type="hidden" name="roll_no" value="<?= htmlspecialchars($student['roll_no']) ?>">
            <div class="btn-group">
                <button type="submit" class="btn btn-danger confirm-delete">⚠️ Yes, Deactivate</button>
                <a href="view.php?roll_no=<?= urlencode($student['roll_no']) ?>" class="btn btn-gold">👁️ Full Profile</a>
                <a href="dashboard.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
        <?php else: ?>
        <div style="margin-top:1rem;">
            <div class="alert alert-info">ℹ️ This student is already inactive. No action needed.</div>
            <div class="btn-group">
                <a href="update.php?roll_no=<?= urlencode($student['roll_no']) ?>" class="btn btn-primary">✏️ Reactivate via Edit</a>
                <a href="dashboard.php" class="btn btn-outline">← Dashboard</a>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
