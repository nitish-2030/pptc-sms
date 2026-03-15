<?php
// ============================================================
// fee_report.php — Fee Due Report & CSV Export
// NEW FEATURE: Full fee status report with filters + CSV export
// ============================================================
require_once 'config/db.php';
require_once 'config/courses_helper.php';
$pageTitle = 'Fee Report';
$baseUrl   = '';

$course_codes = get_course_codes($conn);

// Filters
$filter_status = trim($_GET['fee_status'] ?? '');
$filter_course = trim($_GET['course']     ?? '');
$export_csv    = isset($_GET['export']) && $_GET['export'] === 'csv';

// Build query
$wheres = [];
$params = [];
$types  = '';

if (in_array($filter_status, ['Unpaid', 'Partial', 'Paid'])) {
    $wheres[] = "f.status = ?";
    $types   .= 's';
    $params[] = $filter_status;
}

if ($filter_course !== '') {
    $wheres[] = "s.course = ?";
    $types   .= 's';
    $params[] = $filter_course;
}

$where_sql = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';

$sql = "SELECT s.roll_no, s.name, s.course, s.is_active,
               f.total_fee, f.paid_amount, f.due_amount, f.status
        FROM students s
        LEFT JOIN fees f ON s.id = f.student_id
        $where_sql
        ORDER BY f.due_amount DESC, s.name ASC";

$stmt = mysqli_prepare($conn, $sql);
if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result   = mysqli_stmt_get_result($stmt);
$students = [];
while ($row = mysqli_fetch_assoc($result)) { $students[] = $row; }

// Totals
$total_fee_sum  = array_sum(array_column($students, 'total_fee'));
$total_paid_sum = array_sum(array_column($students, 'paid_amount'));
$total_due_sum  = array_sum(array_column($students, 'due_amount'));

// ---- CSV Export ----
if ($export_csv) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="fee_report_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
    fputcsv($out, ['Roll No', 'Student Name', 'Course', 'Status', 'Total Fee (₹)', 'Paid (₹)', 'Due (₹)', 'Fee Status']);
    foreach ($students as $s) {
        fputcsv($out, [
            $s['roll_no'],
            $s['name'],
            $s['course'],
            $s['is_active'] ? 'Active' : 'Inactive',
            $s['total_fee']   ?? 0,
            $s['paid_amount'] ?? 0,
            $s['due_amount']  ?? 0,
            $s['status']      ?? 'No Record',
        ]);
    }
    fputcsv($out, []);
    fputcsv($out, ['', '', '', 'TOTAL', $total_fee_sum, $total_paid_sum, $total_due_sum, '']);
    fclose($out);
    exit;
}

include 'includes/header.php';
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
    <h1 class="page-title">&#x1F4CA; Fee Report</h1>

    <!-- Summary Cards with icons -->
    <div class="fr-summary-grid">
        <div class="fr-stat-card">
            <div class="fr-stat-icon fr-stat-icon--blue">&#x1F4B0;</div>
            <div class="fr-stat-body">
                <span class="fr-stat-num">&#x20B9;<?= number_format($total_fee_sum) ?></span>
                <span class="fr-stat-lbl">Total Fees</span>
            </div>
        </div>
        <div class="fr-stat-card">
            <div class="fr-stat-icon fr-stat-icon--green">&#x2705;</div>
            <div class="fr-stat-body">
                <span class="fr-stat-num" style="color:var(--success);">&#x20B9;<?= number_format($total_paid_sum) ?></span>
                <span class="fr-stat-lbl">Collected</span>
            </div>
        </div>
        <div class="fr-stat-card">
            <div class="fr-stat-icon fr-stat-icon--red">&#x1F514;</div>
            <div class="fr-stat-body">
                <span class="fr-stat-num" style="color:var(--danger);">&#x20B9;<?= number_format($total_due_sum) ?></span>
                <span class="fr-stat-lbl">Total Due</span>
            </div>
        </div>
        <div class="fr-stat-card">
            <div class="fr-stat-icon fr-stat-icon--purple">&#x1F464;</div>
            <div class="fr-stat-body">
                <span class="fr-stat-num"><?= count($students) ?></span>
                <span class="fr-stat-lbl">Students Shown</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="fr-filter-bar no-print">
        <form method="GET" action="fee_report.php" style="display:contents;" id="filterForm">
            <div class="form-group">
                <label>Fee Status</label>
                <select name="fee_status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="Unpaid"  <?= $filter_status==='Unpaid'  ? 'selected':'' ?>>&#x1F534; Unpaid</option>
                    <option value="Partial" <?= $filter_status==='Partial' ? 'selected':'' ?>>&#x1F7E1; Partial</option>
                    <option value="Paid"    <?= $filter_status==='Paid'    ? 'selected':'' ?>>&#x1F7E2; Paid</option>
                </select>
            </div>
            <div class="form-group">
                <label>Course</label>
                <select name="course" class="form-control">
                    <option value="">All Courses</option>
                    <?php foreach ($course_codes as $code => $label): ?>
                    <option value="<?= htmlspecialchars($code) ?>" <?= $filter_course===$code ? 'selected':'' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fr-filter-actions">
                <button type="submit" class="btn btn-primary btn-sm" id="filterBtn">&#x1F50D; Filter</button>
                <a href="fee_report.php" class="btn btn-outline btn-sm">&#x2715; Clear</a>
            </div>
        </form>
    </div>

    <!-- Table toolbar: search + export aligned -->
    <div class="fr-table-toolbar no-print">
        <div class="fr-search-wrap">
            <span class="fr-search-icon">&#x1F50D;</span>
            <input type="text" class="fr-search-input" id="frTableSearch" placeholder="Search name or roll no&#x2026;" autocomplete="off">
        </div>
        <div class="fr-table-toolbar-right">
            <span id="frRowCount" style="font-size:.72rem;color:var(--text-light);font-weight:600;"><?= count($students) ?> records</span>
            <?php $export_params = http_build_query(['fee_status'=>$filter_status,'course'=>$filter_course,'export'=>'csv']); ?>
            <a href="fee_report.php?<?= $export_params ?>" class="btn btn-export">&#x2B07;&#xFE0F; Export CSV</a>
        </div>
    </div>

    <!-- Report Table -->
    <div class="table-wrapper" id="frTableWrap">
        <table class="styled-table" id="frTable">
            <thead class="fr-table-head fr-sticky-head">
                <tr>
                    <th>#</th>
                    <th class="sortable" data-col="0">Roll No <span class="sort-icon">&#x21C5;</span></th>
                    <th class="sortable" data-col="1">Name <span class="sort-icon">&#x21C5;</span></th>
                    <th class="sortable" data-col="2">Course <span class="sort-icon">&#x21C5;</span></th>
                    <th>Total Fee</th>
                    <th>Paid</th>
                    <th class="sortable" data-col="5">Due <span class="sort-icon">&#x21C5;</span></th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="frTableBody">
                <?php if ($students): ?>
                <?php foreach ($students as $i => $s):
                    $fstatus = $s['status'] ?? 'No Record';
                    $due     = (float)($s['due_amount'] ?? 0);
                    $badgeClass = match($fstatus) {
                        'Paid'    => 'badge-paid',
                        'Partial' => 'badge-partial',
                        'Unpaid'  => 'badge-unpaid',
                        default   => ''
                    };
                ?>
                <tr data-roll="<?= htmlspecialchars(strtolower($s['roll_no'])) ?>" data-name="<?= htmlspecialchars(strtolower($s['name'])) ?>">
                    <td style="color:var(--text-light);font-size:0.82rem;"><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($s['roll_no']) ?></strong></td>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><span class="course-badge"><?= htmlspecialchars($s['course']) ?></span></td>
                    <td style="font-weight:600;">&#x20B9;<?= number_format((float)($s['total_fee'] ?? 0)) ?></td>
                    <td style="color:var(--success);font-weight:600;">&#x20B9;<?= number_format((float)($s['paid_amount'] ?? 0)) ?></td>
                    <td style="color:var(--danger);font-weight:700;" data-due="<?= $due ?>">&#x20B9;<?= number_format($due) ?></td>
                    <td>
                        <span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($fstatus) ?></span>
                    </td>
                    <td>
                        <a href="#" class="fr-pay-btn"
                           onclick="openPayModal(event,'<?= htmlspecialchars(addslashes($s['name'])) ?>','<?= htmlspecialchars(addslashes($s['roll_no'])) ?>','<?= htmlspecialchars($s['course']) ?>','&#x20B9;<?= number_format($due) ?>','fees.php?roll_no=<?= urlencode($s['roll_no']) ?>')">
                            &#x1F4B3; Pay
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr id="noDataRow">
                    <td colspan="9" style="padding:0;">
                        <div class="empty-state">
                            <span class="empty-state-icon">&#x1F4B0;</span>
                            <div class="empty-state-title">No Records Found</div>
                            <div class="empty-state-desc">Try adjusting your filters or clearing them to see all students.</div>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
            <?php if ($students): ?>
            <tfoot>
                <tr style="background:#fdf6e3;font-weight:700;">
                    <td colspan="4" style="text-align:right;padding-right:1rem;font-size:.82rem;color:var(--text-mid);">TOTALS:</td>
                    <td style="font-weight:800;">&#x20B9;<?= number_format($total_fee_sum) ?></td>
                    <td style="color:var(--success);font-weight:800;">&#x20B9;<?= number_format($total_paid_sum) ?></td>
                    <td style="color:var(--danger);font-weight:800;">&#x20B9;<?= number_format($total_due_sum) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
        <!-- Search empty state -->
        <div id="frSearchEmpty" style="display:none;">
            <div class="empty-state">
                <span class="empty-state-icon">&#x1F50D;</span>
                <div class="empty-state-title">No Matching Students</div>
                <div class="empty-state-desc">No students match your search. Try a different name or roll number.</div>
            </div>
        </div>
    </div>

    <div style="margin-top:1rem;" class="no-print">
        <a href="dashboard.php" class="btn btn-outline">&larr; Dashboard</a>
    </div>
</div>

<script>
(function(){
    const inp = document.getElementById('frTableSearch');
    const tbody = document.getElementById('frTableBody');
    const countEl = document.getElementById('frRowCount');
    const emptyEl = document.getElementById('frSearchEmpty');
    if(!inp||!tbody) return;
    inp.addEventListener('input', function(){
        const q = this.value.trim().toLowerCase();
        const rows = tbody.querySelectorAll('tr[data-roll]');
        let visible = 0;
        rows.forEach(r => {
            const show = !q || (r.dataset.roll||'').includes(q) || (r.dataset.name||'').includes(q);
            r.style.display = show ? '' : 'none';
            if(show) visible++;
        });
        if(countEl) countEl.textContent = visible + ' records';
        if(emptyEl) emptyEl.style.display = (visible===0 && rows.length>0) ? 'block' : 'none';
    });
})();

(function(){
    const thead = document.querySelector('#frTable thead');
    const tbody = document.getElementById('frTableBody');
    if(!thead||!tbody) return;
    let sortCol=-1, sortDir=1;
    thead.querySelectorAll('th.sortable').forEach(th => {
        th.style.cursor='pointer';
        th.addEventListener('click', function(){
            const col = parseInt(this.dataset.col);
            sortDir = (sortCol===col) ? sortDir*-1 : 1;
            sortCol = col;
            thead.querySelectorAll('th.sortable').forEach(t => {
                t.classList.remove('sort-asc','sort-desc');
                const ic=t.querySelector('.sort-icon'); if(ic) ic.textContent='\u21C5';
            });
            this.classList.add(sortDir===1?'sort-asc':'sort-desc');
            const ic=this.querySelector('.sort-icon'); if(ic) ic.textContent=sortDir===1?'\u2191':'\u2193';
            const rows=Array.from(tbody.querySelectorAll('tr[data-roll]'));
            const map={0:1,1:2,2:3,5:6};
            rows.sort((a,b)=>{
                const tdI=map[col]??col;
                const ac=a.querySelectorAll('td')[tdI], bc=b.querySelectorAll('td')[tdI];
                if(!ac||!bc) return 0;
                if(col===5){ return (parseFloat(ac.dataset.due||0)-parseFloat(bc.dataset.due||0))*sortDir; }
                return ac.textContent.trim().toLowerCase()<bc.textContent.trim().toLowerCase()?-sortDir:sortDir;
            });
            rows.forEach(r=>tbody.appendChild(r));
        });
    });
})();

function openPayModal(e,name,roll,course,due,payUrl){
    e.preventDefault();
    document.getElementById('modalStudentName').textContent=name;
    document.getElementById('modalStudentMeta').textContent=roll+' \u2022 '+course;
    document.getElementById('modalDueAmount').textContent=due;
    document.getElementById('modalPayLink').href=payUrl;
    document.getElementById('payModalOverlay').classList.add('open');
}
function closePayModal(){
    document.getElementById('payModalOverlay').classList.remove('open');
}
document.getElementById('payModalOverlay').addEventListener('click',function(e){if(e.target===this)closePayModal();});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closePayModal();});

(function(){
    const form=document.getElementById('filterForm');
    const btn=document.getElementById('filterBtn');
    if(!form||!btn) return;
    form.addEventListener('submit',function(){ btn.classList.add('btn-loading'); });
})();
</script>

<?php include 'includes/footer.php'; ?>
