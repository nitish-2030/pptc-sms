<?php
// ============================================================
// view_all.php — All Students (v4 — compact professional UI)
// ============================================================
require_once 'config/db.php';
require_once 'config/courses_helper.php';
$pageTitle    = 'All Students';
$baseUrl      = '';
$course_codes = get_course_codes($conn);

$result   = mysqli_query($conn, "SELECT s.*, f.due_amount, f.status AS fee_status FROM students s LEFT JOIN fees f ON s.id = f.student_id ORDER BY s.name ASC");
$students = [];
while ($row = mysqli_fetch_assoc($result)) { $students[] = $row; }
$total_found = count($students);

include 'includes/header.php';
?>

<div class="container">

    <div class="va-topbar">
        <div>
            <h1 class="va-title">All Students</h1>
            <p class="va-subtitle">
                Showing <span id="resultCount"><?= $total_found ?></span>
                student<?= $total_found !== 1 ? 's' : '' ?> &mdash; sorted A&ndash;Z
            </p>
        </div>
        <a href="insert.php" class="btn btn-primary btn-sm">&#65291; Add Student</a>
    </div>

    <!-- Filter Bar -->
    <div class="va-filter-bar">
        <form id="filterForm" style="display:contents;">
            <div class="va-filter-field">
                <span class="va-filter-icon">&#128269;</span>
                <input type="text" id="filterNameInput" name="search_name"
                       class="va-filter-input" placeholder="Search name or roll no&hellip;" autocomplete="off">
            </div>
            <select name="course" class="va-filter-select">
                <option value="">All Courses</option>
                <?php foreach ($course_codes as $code => $label): ?>
                <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="va-filter-select">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
            <select name="fee_filter" class="va-filter-select">
                <option value="">All Fees</option>
                <option value="due">Has Due</option>
                <option value="paid">Fully Paid</option>
            </select>
            <input type="date" name="adm_date" class="va-filter-select">
            <button type="button" id="clearFilters" class="va-clear-btn" title="Clear">&#10005; Clear</button>
        </form>
    </div>

    <!-- Table -->
    <div class="va-table-wrap" id="tableWrap">
        <table class="va-table">
            <thead>
                <tr>
                    <th class="va-th-num">#</th>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Due</th>
                    <th class="va-hide-sm">Admitted</th>
                    <th>Status</th>
                    <th class="va-th-actions">Actions</th>
                </tr>
            </thead>
            <tbody id="studentsTableBody">
                <?php if ($students): ?>
                <?php foreach ($students as $i => $s):
                    $due      = (float)($s['due_amount'] ?? 0);
                    $initial  = strtoupper(mb_substr($s['name'], 0, 1));
                    $roll_enc = urlencode($s['roll_no']);
                ?>
                <tr class="va-row">
                    <td class="va-td-num"><?= $i + 1 ?></td>
                    <td class="va-td-student">
                        <div class="va-avatar"><?= $initial ?></div>
                        <div class="va-student-info">
                            <span class="va-name"><?= htmlspecialchars($s['name']) ?></span>
                            <span class="va-roll"><?= htmlspecialchars($s['roll_no']) ?></span>
                        </div>
                    </td>
                    <td><span class="va-course"><?= htmlspecialchars($s['course']) ?></span></td>
                    <td>
                        <span class="va-due <?= $due > 0 ? 'va-due--red' : 'va-due--green' ?>">
                            &#8377;<?= number_format($due) ?>
                        </span>
                    </td>
                    <td class="va-td-date va-hide-sm"><?= date('d M Y', strtotime($s['admission_date'])) ?></td>
                    <td>
                        <span class="va-status <?= $s['is_active'] ? 'va-status--active' : 'va-status--inactive' ?>">
                            <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="va-td-actions">
                        <a href="view.php?roll_no=<?= $roll_enc ?>"   class="va-btn va-btn--view"   title="View Profile">&#128065;</a>
                        <a href="update.php?roll_no=<?= $roll_enc ?>" class="va-btn va-btn--edit"   title="Edit">&#9998;</a>
                        <a href="fees.php?roll_no=<?= $roll_enc ?>"   class="va-btn va-btn--fee"    title="Pay Fees">&#8377;</a>
                        <?php if ($s['is_active']): ?>
                        <a href="delete.php?roll_no=<?= $roll_enc ?>" class="va-btn va-btn--del confirm-delete" title="Deactivate">&#10005;</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="7" class="va-empty">
                    <div>&#127891;</div>
                    <p>No students found. <a href="insert.php">Add one?</a></p>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.va-topbar{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:1.25rem;gap:1rem;}
.va-title{font-family:'Cinzel',serif;font-size:1.45rem;font-weight:700;color:var(--crimson-dk);line-height:1.1;}
.va-subtitle{font-size:0.8rem;color:var(--text-light);margin-top:0.25rem;}
.va-subtitle span{font-weight:700;color:var(--crimson);}
.va-filter-bar{display:flex;flex-wrap:wrap;gap:0.55rem;align-items:center;background:#fff;border:1px solid rgba(139,0,0,0.09);border-radius:10px;padding:0.7rem 1rem;margin-bottom:1rem;box-shadow:0 1px 6px rgba(139,0,0,0.05);}
.va-filter-field{position:relative;flex:1;min-width:170px;}
.va-filter-icon{position:absolute;left:0.65rem;top:50%;transform:translateY(-50%);font-size:0.8rem;pointer-events:none;opacity:0.45;}
.va-filter-input,.va-filter-select{padding:0.42rem 0.7rem;border:1.5px solid #e0cece;border-radius:7px;font-family:'Nunito',sans-serif;font-size:0.83rem;color:var(--text-dark);background:#fafafa;outline:none;transition:border-color .2s,box-shadow .2s;}
.va-filter-input{width:100%;padding-left:2rem;}
.va-filter-input:focus,.va-filter-select:focus{border-color:var(--crimson);box-shadow:0 0 0 3px rgba(139,0,0,0.07);background:#fff;}
.va-clear-btn{padding:0.42rem 0.8rem;font-size:0.78rem;font-weight:700;color:var(--text-light);background:transparent;border:1.5px solid #ddd;border-radius:7px;cursor:pointer;font-family:'Nunito',sans-serif;transition:all .18s;white-space:nowrap;}
.va-clear-btn:hover{border-color:var(--crimson);color:var(--crimson);background:#fdecea;}
.va-table-wrap{background:#fff;border-radius:12px;border:1px solid rgba(139,0,0,0.08);box-shadow:0 2px 14px rgba(139,0,0,0.06);overflow:hidden;overflow-x:auto;transition:opacity .25s;}
.va-table-wrap.loading{opacity:.4;pointer-events:none;}
.va-table{width:100%;border-collapse:collapse;font-size:0.875rem;}
.va-table thead tr{background:linear-gradient(135deg,var(--crimson-dk),var(--crimson));}
.va-table th{padding:0.65rem 1rem;text-align:left;font-family:'Cinzel',serif;font-size:0.7rem;font-weight:600;color:rgba(255,255,255,0.9);letter-spacing:0.07em;text-transform:uppercase;white-space:nowrap;border:none;}
.va-th-num,.va-td-num{width:40px;text-align:center;}
.va-th-actions{text-align:right;}
.va-row{border-bottom:1px solid #f8f0e8;transition:background .15s;}
.va-row:last-child{border-bottom:none;}
.va-row:hover{background:#fdf9f4;}
.va-table td{padding:0.6rem 1rem;vertical-align:middle;color:var(--text-dark);}
.va-td-num{font-size:0.72rem;color:var(--text-light);text-align:center;}
.va-td-student{display:flex;align-items:center;gap:0.6rem;}
.va-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--crimson-dk),var(--crimson));color:var(--gold-lt);font-family:'Cinzel',serif;font-size:0.78rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:2px solid rgba(201,168,76,0.35);}
.va-student-info{display:flex;flex-direction:column;gap:0.08rem;}
.va-name{font-weight:700;font-size:0.87rem;color:var(--text-dark);line-height:1.2;}
.va-roll{font-size:0.7rem;color:var(--text-light);font-weight:600;letter-spacing:0.03em;}
.va-course{display:inline-block;padding:0.15rem 0.55rem;background:#f5ebe0;color:var(--crimson-dk);border-radius:20px;font-size:0.7rem;font-weight:700;letter-spacing:0.03em;white-space:nowrap;}
.va-due{font-weight:700;font-size:0.83rem;}
.va-due--red{color:var(--crimson);}
.va-due--green{color:#1a7a1a;}
.va-td-date{font-size:0.78rem;color:var(--text-light);white-space:nowrap;}
.va-status{display:inline-block;padding:0.15rem 0.6rem;border-radius:20px;font-size:0.7rem;font-weight:700;letter-spacing:0.03em;}
.va-status--active{background:#eafbea;color:#1a7a1a;}
.va-status--inactive{background:#f0f0f0;color:#888;}
.va-td-actions{text-align:right;white-space:nowrap;}
.va-btn{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;font-size:0.78rem;text-decoration:none;border:none;cursor:pointer;transition:all .17s;margin-left:3px;line-height:1;}
.va-btn--view{background:#fff8e8;color:var(--gold-dk);border:1px solid rgba(201,168,76,0.35);}
.va-btn--view:hover{background:var(--gold);color:var(--crimson-dk);}
.va-btn--edit{background:#eef2ff;color:#4455cc;border:1px solid rgba(68,85,204,0.25);}
.va-btn--edit:hover{background:#4455cc;color:#fff;}
.va-btn--fee{background:#eafbea;color:#1a7a1a;border:1px solid rgba(26,122,26,0.25);font-weight:800;font-size:0.85rem;}
.va-btn--fee:hover{background:#1a7a1a;color:#fff;}
.va-btn--del{background:#fdecea;color:var(--crimson);border:1px solid rgba(139,0,0,0.2);font-weight:700;}
.va-btn--del:hover{background:var(--crimson);color:#fff;}
.va-empty{text-align:center;padding:3rem 1rem;color:var(--text-light);}
.va-empty div{font-size:2rem;margin-bottom:0.5rem;}
.va-empty p{font-weight:600;font-size:0.88rem;}
.va-empty a{color:var(--crimson);text-decoration:underline;}
@media(max-width:768px){.va-hide-sm{display:none;}}
</style>

<?php include 'includes/footer.php'; ?>
