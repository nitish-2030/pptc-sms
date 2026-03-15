<?php
// ============================================================
// update.php — Update Student Form (v3 — all profile fields)
// ============================================================
require_once 'config/db.php';
require_once 'config/courses_helper.php';
require_once 'config/photo_helper.php';
require_once 'config/csrf_helper.php';
$pageTitle       = 'Update Student';
$baseUrl         = '';
$grouped_courses = get_all_courses($conn);

$student = null;
$success = $error = '';

if (isset($_GET['status'])) {
    $success = $_GET['status'] === 'success' ? 'Student updated successfully!' : '';
    $error   = $_GET['status'] === 'error'   ? htmlspecialchars($_GET['msg'] ?? 'Error.') : '';
}

$roll_no = trim($_GET['roll_no'] ?? '');
$fee_data = ['total_fee' => 0];

if ($roll_no !== '') {
    $stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE roll_no = ?");
    mysqli_stmt_bind_param($stmt, 's', $roll_no);
    mysqli_stmt_execute($stmt);
    $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$student) $error = 'No student found with Roll No: ' . htmlspecialchars($roll_no);

    if ($student) {
        $f = mysqli_prepare($conn, "SELECT total_fee FROM fees WHERE student_id = ?");
        mysqli_stmt_bind_param($f, 'i', $student['id']);
        mysqli_stmt_execute($f);
        $fd = mysqli_fetch_assoc(mysqli_stmt_get_result($f));
        if ($fd) $fee_data = $fd;
    }
}

include 'includes/header.php';

// Helper: selected option
function sel($a, $b) { return $a === $b ? 'selected' : ''; }
function chk($a, $b) { return $a == $b ? 'checked' : ''; }
?>

<div class="container-sm">
    <h1 class="page-title">✏️ Update Student</h1>

    <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error">❌ <?= $error ?></div><?php endif; ?>

    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="font-family:'Cinzel',serif;font-size:0.95rem;color:var(--crimson-dk);margin-bottom:0.5rem;">Search Student</h3>
        <p style="font-size:0.8rem;color:var(--text-light);margin-bottom:1rem;">Type a <strong>name</strong> or <strong>roll number</strong> — suggestions appear instantly.</p>
        <div class="smart-search-wrap">
            <form id="smartSearchForm" method="GET" action="update.php">
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
    <form id="studentForm" action="process_update.php" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id"            value="<?= $student['id'] ?>">
        <input type="hidden" name="roll_no"       value="<?= htmlspecialchars($student['roll_no']) ?>">
        <input type="hidden" name="current_photo" value="<?= htmlspecialchars($student['photo'] ?? '') ?>">

        <!-- ── SECTION 1: Photo & Identity ── -->
        <div class="card" style="margin-bottom:1.5rem;">
            <h3 class="section-heading">📷 Photo & Identity</h3>

            <div class="form-group">
                <label>Profile Photo <span class="opt">(leave blank to keep current)</span></label>
                <div class="photo-upload-wrap">
                    <div class="photo-preview" id="previewBox">
                        <?php $existing = $student['photo'] ?? '';
                        if ($existing && file_exists(UPLOAD_DIR . $existing)): ?>
                            <img src="<?= htmlspecialchars(UPLOAD_URL . $existing) ?>" alt="Current photo" id="previewImg">
                        <?php else: ?>
                            <span id="previewLetter"><?= strtoupper(substr($student['name'],0,1)) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="photo-upload-info">
                        <label for="photo">📷 Change Photo</label>
                        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
                        <span class="photo-hint">JPG, PNG or WEBP · Max 2 MB</span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Roll Number (Read-only)</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($student['roll_no']) ?>"
                       disabled style="background:#f5f0f0;cursor:not-allowed;">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required maxlength="100"
                           value="<?= htmlspecialchars($student['name']) ?>">
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" class="form-control">
                        <option value="">-- Select --</option>
                        <option value="Male"   <?= sel($student['gender']??'','Male')   ?>>Male</option>
                        <option value="Female" <?= sel($student['gender']??'','Female') ?>>Female</option>
                        <option value="Other"  <?= sel($student['gender']??'','Other')  ?>>Other</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" class="form-control"
                           value="<?= htmlspecialchars($student['dob'] ?? '') ?>"
                           max="<?= date('Y-m-d', strtotime('-14 years')) ?>">
                </div>
                <div class="form-group">
                    <label for="blood_group">Blood Group</label>
                    <select id="blood_group" name="blood_group" class="form-control">
                        <option value="">-- Select --</option>
                        <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg): ?>
                        <option value="<?= $bg ?>" <?= sel($student['blood_group']??'',$bg) ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" class="form-control">
                        <?php foreach(['General','OBC','SC','ST','EWS'] as $cat): ?>
                        <option value="<?= $cat ?>" <?= sel($student['category']??'General',$cat) ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- ── SECTION 2: Contact Info ── -->
        <div class="card" style="margin-bottom:1.5rem;">
            <h3 class="section-heading">📞 Contact Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control"
                           value="<?= htmlspecialchars($student['phone'] ?? '') ?>"
                           placeholder="e.g. 9876543210" maxlength="15">
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($student['email'] ?? '') ?>"
                           placeholder="student@email.com" maxlength="120">
                </div>
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" class="form-control"
                          rows="2" maxlength="300"><?= htmlspecialchars($student['address'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- ── SECTION 3: Guardian ── -->
        <div class="card" style="margin-bottom:1.5rem;">
            <h3 class="section-heading">👨‍👩‍👧 Guardian / Parent Details</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="guardian_name">Guardian Name</label>
                    <input type="text" id="guardian_name" name="guardian_name" class="form-control"
                           value="<?= htmlspecialchars($student['guardian_name'] ?? '') ?>"
                           placeholder="Father / Mother / Guardian name" maxlength="100">
                </div>
                <div class="form-group">
                    <label for="guardian_phone">Guardian Phone</label>
                    <input type="tel" id="guardian_phone" name="guardian_phone" class="form-control"
                           value="<?= htmlspecialchars($student['guardian_phone'] ?? '') ?>"
                           placeholder="e.g. 9876543210" maxlength="15">
                </div>
            </div>
        </div>

        <!-- ── SECTION 4: Academic ── -->
        <div class="card" style="margin-bottom:1.5rem;">
            <h3 class="section-heading">🎓 Academic Details</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="course">Course *</label>
                    <select id="course" name="course" class="form-control" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($grouped_courses as $cat => $list): ?>
                        <optgroup label="— <?= $cat ?> Programmes —">
                            <?php foreach ($list as $c): ?>
                            <option value="<?= htmlspecialchars($c['code']) ?>"
                                <?= ($student['course'] === $c['code']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="admission_date">Admission Date *</label>
                    <input type="date" id="admission_date" name="admission_date" class="form-control"
                           required value="<?= $student['admission_date'] ?>">
                </div>
                <div class="form-group">
                    <label for="total_fee">Total Course Fee (₹)</label>
                    <input type="number" id="total_fee" name="total_fee" class="form-control"
                           value="<?= htmlspecialchars($fee_data['total_fee']) ?>" min="0" step="0.01">
                </div>
                <div class="form-group">
                    <label for="is_active">Status</label>
                    <select id="is_active" name="is_active" class="form-control">
                        <option value="1" <?= $student['is_active']==1 ? 'selected':'' ?>>Active</option>
                        <option value="0" <?= $student['is_active']==0 ? 'selected':'' ?>>Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">💾 Update Student</button>
            <a href="view.php?roll_no=<?= urlencode($student['roll_no']) ?>" class="btn btn-gold">👁️ View Profile</a>
            <a href="dashboard.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
    <?php endif; ?>
</div>

<style>
.section-heading { font-family:'Cinzel',serif; font-size:0.95rem; color:var(--crimson-dk); margin-bottom:1.25rem; padding-bottom:0.5rem; border-bottom:2px solid #f5ebe0; }
.opt { color:var(--text-light); font-weight:400; font-size:0.82rem; }
textarea.form-control { resize:vertical; min-height:60px; }
</style>

<?php include 'includes/footer.php'; ?>
