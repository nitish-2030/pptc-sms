<?php
// ============================================================
// dashboard.php — Main Dashboard (v5 — Production UI)
// ============================================================
require_once 'config/db.php';
$pageTitle = 'Dashboard';
$baseUrl   = '';

$total    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM students"))['c'];
$active   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM students WHERE is_active=1"))['c'];
$inactive = $total - $active;

$fee = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(total_fee),0) tf, COALESCE(SUM(paid_amount),0) tp, COALESCE(SUM(due_amount),0) td,
            COUNT(CASE WHEN status='Unpaid'  THEN 1 END) cu,
            COUNT(CASE WHEN status='Partial' THEN 1 END) cp,
            COUNT(CASE WHEN status='Paid'    THEN 1 END) cd
     FROM fees"
));
$pct = $fee['tf'] > 0 ? round(($fee['tp'] / $fee['tf']) * 100, 1) : 0;
$bar_color = $pct >= 75 ? '#16a34a' : ($pct >= 40 ? '#d97706' : '#dc2626');

$top_res = mysqli_query($conn,
    "SELECT s.name, s.roll_no, s.course, f.due_amount
     FROM students s JOIN fees f ON s.id = f.student_id
     WHERE f.due_amount > 0 AND s.is_active = 1
     ORDER BY f.due_amount DESC LIMIT 5"
);
$top_due = [];
while ($row = mysqli_fetch_assoc($top_res)) $top_due[] = $row;
$max_due = $top_due ? max(array_column($top_due, 'due_amount')) : 1;

include 'includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════
   DASHBOARD v5 — Professional Production UI
   ═══════════════════════════════════════════ */

/* Page header */
.db-page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.4rem;flex-wrap:wrap;gap:.75rem;}
.db-page-title{font-size:1.25rem;font-weight:800;color:var(--crimson-dk);margin:0;letter-spacing:-.015em;}
.db-page-sub{font-size:.7rem;color:var(--text-light);margin:.2rem 0 0;font-weight:600;letter-spacing:.02em;}
[data-theme="dark"] .db-page-title{color:#f5c07a;}
.db-cta-row{display:flex;gap:.5rem;}

/* Actions bar — grouped, minimal */
.db-actions-bar{
    background:#fff;
    border-radius:10px;
    box-shadow:0 1px 8px rgba(0,0,0,.055);
    padding:.8rem 1.1rem;
    margin-bottom:1.2rem;
    display:flex;
    gap:1.25rem;
    flex-wrap:wrap;
    align-items:center;
}
[data-theme="dark"] .db-actions-bar{background:#1e1e28;box-shadow:0 2px 16px rgba(0,0,0,.38);}
.db-action-group{display:flex;align-items:center;gap:.35rem;flex-wrap:wrap;}
.db-group-sep{width:1px;height:24px;background:#e8d8d0;flex-shrink:0;margin:0 .1rem;}
[data-theme="dark"] .db-group-sep{background:rgba(255,255,255,.1);}
.db-group-lbl{font-size:.58rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:var(--text-light);padding-right:.45rem;white-space:nowrap;}

/* Chips */
.db-chip{
    display:inline-flex;align-items:center;gap:.3rem;
    padding:.3rem .7rem;border-radius:6px;
    font-size:.74rem;font-weight:700;
    text-decoration:none;
    background:#f5f0eb;color:var(--crimson-dk);
    border:1px solid rgba(139,0,0,.1);
    transition:background .15s,color .15s,transform .15s,box-shadow .15s;
    position:relative;overflow:hidden;
    white-space:nowrap;
}
.db-chip:hover{background:var(--crimson);color:#fff;border-color:var(--crimson);transform:translateY(-1px);box-shadow:0 3px 10px rgba(139,0,0,.2);}
.db-chip--red{background:#fff1f1;color:#dc2626;border-color:rgba(220,38,38,.15);}
.db-chip--red:hover{background:#dc2626;color:#fff;border-color:#dc2626;}
.db-chip--blue{background:#eff6ff;color:#1d4ed8;border-color:rgba(29,78,216,.12);}
.db-chip--blue:hover{background:#1d4ed8;color:#fff;border-color:#1d4ed8;}
.db-chip--green{background:#f0fdf4;color:#16a34a;border-color:rgba(22,163,74,.12);}
.db-chip--green:hover{background:#16a34a;color:#fff;border-color:#16a34a;}
.db-chip--amber{background:#fffbeb;color:#b45309;border-color:rgba(180,83,9,.12);}
.db-chip--amber:hover{background:#d97706;color:#fff;border-color:#d97706;}
[data-theme="dark"] .db-chip{background:#252530;color:#ccc0b0;border-color:rgba(255,255,255,.08);}
[data-theme="dark"] .db-chip:hover{background:var(--crimson);color:#fff;border-color:var(--crimson);}

/* Main 2-col grid */
.db-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.1rem;}
@media(max-width:680px){.db-grid{grid-template-columns:1fr;}}

/* Panels */
.db-panel{
    background:#fff;
    border-radius:12px;
    box-shadow:0 2px 14px rgba(0,0,0,.07);
    padding:1.15rem 1.35rem;
    transition:box-shadow .22s;
}
.db-panel:hover{box-shadow:0 4px 22px rgba(0,0,0,.11);}
[data-theme="dark"] .db-panel{background:#1e1e28;box-shadow:0 4px 24px rgba(0,0,0,.42);}
.db-panel-mt{margin-top:1.1rem;}

.db-panel-head{
    font-size:.68rem;font-weight:800;
    color:var(--crimson-dk);
    text-transform:uppercase;letter-spacing:.1em;
    padding-bottom:.55rem;
    border-bottom:1.5px solid #f0e5e0;
    margin-bottom:.85rem;
}
[data-theme="dark"] .db-panel-head{color:var(--gold-lt);border-color:rgba(255,255,255,.07);}
.db-panel-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:.7rem;}
.db-link-sm{font-size:.7rem;color:var(--crimson);font-weight:700;text-decoration:none;transition:color .15s;}
.db-link-sm:hover{color:var(--crimson-dk);}
[data-theme="dark"] .db-link-sm{color:var(--gold-lt);}

/* Stat numbers */
.db-stat-row{display:flex;align-items:stretch;}
.db-stat{flex:1;text-align:center;padding:.65rem 0;}
.db-stat-num{display:block;font-size:2.1rem;font-weight:900;line-height:1;color:var(--text-dark);letter-spacing:-.02em;}
[data-theme="dark"] .db-stat-num{color:#f5e8d8;}
.db-stat-lbl{display:block;font-size:.6rem;text-transform:uppercase;letter-spacing:.09em;color:var(--text-light);margin-top:.25rem;font-weight:700;}
.db-stat-divider{width:1px;background:#ede0e0;margin:.4rem 0;flex-shrink:0;}
[data-theme="dark"] .db-stat-divider{background:rgba(255,255,255,.07);}

/* Search — instant, no button */
.db-search-hint{font-size:.67rem;color:var(--text-light);margin:.3rem 0 .45rem;letter-spacing:.01em;}
.db-search-wrap{position:relative;}
.db-search-ico{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);font-size:.85rem;pointer-events:none;color:var(--text-light);}
.db-search-inp{
    width:100%;padding:.55rem .85rem .55rem 2.1rem;
    border:1.5px solid #ddd0cc;border-radius:8px;
    font-size:.85rem;color:var(--text-dark);background:#fafafa;
    outline:none;transition:border-color .2s,box-shadow .2s;
}
.db-search-inp:focus{border-color:var(--crimson);box-shadow:0 0 0 3px rgba(139,0,0,.08);background:#fff;}
[data-theme="dark"] .db-search-inp{background:#252530;border-color:rgba(255,255,255,.1);color:#f0eae0;}
[data-theme="dark"] .db-search-inp:focus{background:#2d2d3a;}
#dashSearchResult .db-search-result{
    margin-top:.5rem;padding:.7rem .9rem;
    background:#fdf8f5;border-radius:8px;
    border:1px solid #f0e0d8;
    animation:slideDown .2s cubic-bezier(.4,0,.2,1);
}
[data-theme="dark"] #dashSearchResult .db-search-result{background:#252530;border-color:rgba(255,255,255,.07);}
@keyframes slideDown{from{opacity:0;transform:translateY(-6px);}to{opacity:1;transform:translateY(0);}}

/* Fee trio */
.db-fee-trio{display:flex;background:#faf6f0;border-radius:8px;overflow:hidden;margin-bottom:.75rem;}
[data-theme="dark"] .db-fee-trio{background:#252530;}
.db-fee-item{flex:1;text-align:center;padding:.75rem .35rem;border-right:1px solid #f0e5dc;}
.db-fee-item:last-child{border-right:none;}
[data-theme="dark"] .db-fee-item{border-color:rgba(255,255,255,.06);}
.db-fee-num{display:block;font-size:.97rem;font-weight:800;color:var(--text-dark);line-height:1.25;}
[data-theme="dark"] .db-fee-num{color:#f5e8d8;}
.db-fee-lbl{display:block;font-size:.58rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-light);margin-top:.15rem;font-weight:700;}

/* Progress */
.db-progress-meta{display:flex;justify-content:space-between;font-size:.68rem;color:var(--text-light);margin-bottom:.28rem;font-weight:600;}
.db-progress-track{height:6px;background:#ece0dc;border-radius:20px;overflow:hidden;}
[data-theme="dark"] .db-progress-track{background:rgba(255,255,255,.1);}
.db-progress-fill{height:100%;border-radius:20px;transition:width 1.1s cubic-bezier(.4,0,.2,1);}

/* Pills */
.db-pills{display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.65rem;}
.db-pill{padding:.18rem .6rem;border-radius:20px;font-size:.67rem;font-weight:700;}
.db-pill--red{background:#fff1f1;color:#dc2626;}
.db-pill--amber{background:#fffbeb;color:#b45309;}
.db-pill--green{background:#f0fdf4;color:#16a34a;}

/* Dues list */
.db-due-list{display:flex;flex-direction:column;margin-top:.65rem;}
.db-due-row{
    display:flex;align-items:center;gap:.7rem;
    padding:.5rem .3rem;border-bottom:1px solid #f5ece8;
    border-radius:6px;
    transition:background .15s;
}
[data-theme="dark"] .db-due-row{border-color:rgba(255,255,255,.05);}
.db-due-row:last-child{border-bottom:none;}
.db-due-row:hover{background:#fdf7f5;}
[data-theme="dark"] .db-due-row:hover{background:rgba(255,255,255,.03);}
.db-due-rank{
    width:20px;height:20px;border-radius:50%;
    background:var(--crimson-dk);color:var(--gold-lt);
    font-size:.62rem;font-weight:800;
    display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.db-due-info{flex:1;min-width:0;}
.db-due-top{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:.18rem;}
.db-due-name{font-weight:700;font-size:.8rem;color:var(--text-dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:155px;}
[data-theme="dark"] .db-due-name{color:#f5e8d8;}
.db-due-amount{font-weight:800;font-size:.8rem;flex-shrink:0;margin-left:.4rem;}
.db-due-bar-track{height:3px;background:#f0e8e4;border-radius:10px;overflow:hidden;margin-bottom:.15rem;}
[data-theme="dark"] .db-due-bar-track{background:rgba(255,255,255,.07);}
.db-due-meta{font-size:.63rem;color:var(--text-light);}
.db-pay-btn{
    padding:.2rem .6rem;background:var(--crimson);color:#fff;
    border-radius:5px;font-size:.68rem;font-weight:800;
    text-decoration:none;white-space:nowrap;flex-shrink:0;
    transition:all .15s;position:relative;overflow:hidden;
}
.db-pay-btn:hover{background:var(--crimson-dk);color:#fff;transform:scale(1.06);}

/* Activity feed */
.db-feed-item{
    display:flex;gap:.65rem;align-items:flex-start;
    padding:.5rem 0;border-bottom:1px solid #f5ece8;
    animation:feedIn .28s ease;
}
[data-theme="dark"] .db-feed-item{border-color:rgba(255,255,255,.05);}
.db-feed-item:last-child{border-bottom:none;}
.db-feed-icon{
    width:30px;height:30px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    font-size:.85rem;flex-shrink:0;
}
.db-feed-action{font-size:.76rem;font-weight:700;color:var(--text-dark);line-height:1.35;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
[data-theme="dark"] .db-feed-action{color:#e8ddd0;}
.db-feed-meta{font-size:.64rem;color:#bbb;margin-top:.07rem;}

/* Shimmer loading */
.db-shimmer-row{display:flex;gap:.65rem;align-items:center;padding:.5rem 0;border-bottom:1px solid #f5ece8;}
.db-shimmer-block{
    border-radius:6px;
    background:linear-gradient(90deg,#f5ece8 25%,#fdf7f5 50%,#f5ece8 75%);
    background-size:200% 100%;
    animation:shimmerAnim 1.6s infinite;
}
[data-theme="dark"] .db-shimmer-block{
    background:linear-gradient(90deg,rgba(255,255,255,.06) 25%,rgba(255,255,255,.1) 50%,rgba(255,255,255,.06) 75%);
    background-size:200% 100%;
}
@keyframes shimmerAnim{0%{background-position:200% 0;}100%{background-position:-200% 0;}}

/* Live dot */
.db-live-dot{
    display:inline-block;width:7px;height:7px;
    border-radius:50%;background:#22c55e;margin-right:.35rem;
    animation:liveDot 2s infinite;
}
@keyframes liveDot{0%{box-shadow:0 0 0 0 rgba(34,197,94,.5);}70%{box-shadow:0 0 0 5px rgba(34,197,94,0);}100%{box-shadow:0 0 0 0 rgba(34,197,94,0);}}
@keyframes feedIn{from{opacity:0;transform:translateY(-5px);}to{opacity:1;transform:translateY(0);}}

/* Ripple */
.ripple{position:relative;overflow:hidden;}
.ripple-wave{
    position:absolute;border-radius:50%;pointer-events:none;
    background:rgba(255,255,255,.32);
    transform:scale(0);
    animation:rippleAnim .55s ease-out forwards;
}
@keyframes rippleAnim{to{transform:scale(4);opacity:0;}}

/* Page entry stagger */
.db-page-header{animation:entryUp .3s ease both;}
.db-actions-bar{animation:entryUp .3s .06s ease both;}
.db-grid{animation:entryUp .3s .12s ease both;}
.db-panel-mt{animation:entryUp .3s .18s ease both;}
@keyframes entryUp{from{opacity:0;transform:translateY(9px);}to{opacity:1;transform:translateY(0);}}
</style>

<div class="container">

    <!-- Page header with date + CTAs -->
    <div class="db-page-header">
        <div>
            <h1 class="db-page-title">&#128101; Dashboard</h1>
            <p class="db-page-sub"><?= date('l, d F Y') ?></p>
        </div>
        <div class="db-cta-row">
            <a href="insert.php" class="btn btn-primary btn-sm ripple">&#10133; Add Student</a>
            <a href="fees.php"   class="btn btn-gold btn-sm ripple">&#8377; Collect Fee</a>
        </div>
    </div>

    <!-- Grouped quick actions — reduced, no duplicates -->
    <div class="db-actions-bar">
        <div class="db-action-group">
            <span class="db-group-lbl">Students</span>
            <a href="view_all.php"   class="db-chip">&#128203; All Students</a>
            <a href="update.php"     class="db-chip">&#9998; Update</a>
            <a href="delete.php"     class="db-chip db-chip--red">&#128465; Deactivate</a>
        </div>
        <div class="db-group-sep"></div>
        <div class="db-action-group">
            <span class="db-group-lbl">Reports</span>
            <a href="fee_report.php"     class="db-chip">&#128202; Fee Report</a>
            <a href="student_report.php" class="db-chip">&#128101; Student Report</a>
            <a href="print_report.php"   class="db-chip">&#128424; Print</a>
        </div>
        <div class="db-group-sep"></div>
        <div class="db-action-group">
            <span class="db-group-lbl">Analytics</span>
            <a href="activity.php" class="db-chip db-chip--blue">&#9889; Activity</a>
            <a href="insights.php" class="db-chip db-chip--green">&#128200; Insights</a>
            <a href="warnings.php" class="db-chip db-chip--amber">&#9888; Warnings</a>
        </div>
    </div>

    <!-- Two-column panels -->
    <div class="db-grid">

        <!-- LEFT: Student Overview + Instant Search -->
        <div class="db-panel">
            <div class="db-panel-head">&#128101; Student Overview</div>
            <div class="db-stat-row">
                <div class="db-stat">
                    <span class="db-stat-num"><?= $total ?></span>
                    <span class="db-stat-lbl">Total</span>
                </div>
                <div class="db-stat-divider"></div>
                <div class="db-stat">
                    <span class="db-stat-num" style="color:#16a34a;"><?= $active ?></span>
                    <span class="db-stat-lbl">Active</span>
                </div>
                <div class="db-stat-divider"></div>
                <div class="db-stat">
                    <span class="db-stat-num" style="color:#dc2626;"><?= $inactive ?></span>
                    <span class="db-stat-lbl">Inactive</span>
                </div>
            </div>

            <div class="db-panel-head" style="margin-top:1.1rem;">&#128269; Quick Search</div>
            <p class="db-search-hint">Type a roll number — results appear instantly</p>
            <div class="db-search-wrap">
                <span class="db-search-ico">&#128269;</span>
                <input type="text" id="dashRollInput" class="db-search-inp"
                       placeholder="e.g. BCA2024001" autocomplete="off">
            </div>
            <div id="dashSearchResult"></div>
        </div>

        <!-- RIGHT: Fee Collection -->
        <div class="db-panel">
            <div class="db-panel-head">&#128176; Fee Collection</div>
            <div class="db-fee-trio">
                <div class="db-fee-item">
                    <span class="db-fee-num">&#8377;<?= number_format($fee['tf']) ?></span>
                    <span class="db-fee-lbl">Total Billed</span>
                </div>
                <div class="db-fee-item">
                    <span class="db-fee-num" style="color:#16a34a;">&#8377;<?= number_format($fee['tp']) ?></span>
                    <span class="db-fee-lbl">Collected</span>
                </div>
                <div class="db-fee-item">
                    <span class="db-fee-num" style="color:#dc2626;">&#8377;<?= number_format($fee['td']) ?></span>
                    <span class="db-fee-lbl">Pending</span>
                </div>
            </div>

            <div class="db-progress-meta">
                <span>Collection Rate</span>
                <span style="font-weight:800;color:<?= $bar_color ?>;"><?= $pct ?>%</span>
            </div>
            <div class="db-progress-track">
                <div class="db-progress-fill" style="width:<?= $pct ?>%;background:<?= $bar_color ?>;"></div>
            </div>

            <div class="db-pills">
                <span class="db-pill db-pill--red">&#128308; <?= $fee['cu'] ?> Unpaid</span>
                <span class="db-pill db-pill--amber">&#128992; <?= $fee['cp'] ?> Partial</span>
                <span class="db-pill db-pill--green">&#128994; <?= $fee['cd'] ?> Paid</span>
            </div>
        </div>
    </div>

    <!-- Highest Pending Dues -->
    <?php if ($top_due): ?>
    <div class="db-panel db-panel-mt">
        <div class="db-panel-row">
            <div class="db-panel-head" style="margin-bottom:0;">&#128680; Highest Pending Dues</div>
            <a href="fee_report.php?fee_status=Unpaid" class="db-link-sm">View all &rarr;</a>
        </div>
        <div class="db-due-list">
            <?php foreach ($top_due as $i => $s):
                $bar_w = round(($s['due_amount'] / $max_due) * 100);
                // Color based on proportion of max due
                $due_ratio = $max_due > 0 ? ($s['due_amount'] / $max_due) * 100 : 0;
                $c = $due_ratio >= 75 ? '#dc2626' : ($due_ratio >= 40 ? '#d97706' : '#16a34a');
                $c_light = $due_ratio >= 75 ? '#fef2f2' : ($due_ratio >= 40 ? '#fffbeb' : '#f0fdf4');
            ?>
            <div class="db-due-row">
                <span class="db-due-rank"><?= $i+1 ?></span>
                <div class="db-due-info">
                    <div class="db-due-top">
                        <span class="db-due-name"><?= htmlspecialchars($s['name']) ?></span>
                        <span class="db-due-amount" style="color:<?= $c ?>;">&#8377;<?= number_format($s['due_amount']) ?></span>
                    </div>
                    <div class="db-due-bar-track" style="background:<?= $c_light ?>;">
                        <div style="width:<?= $bar_w ?>%;height:100%;background:<?= $c ?>;border-radius:10px;opacity:.75;"></div>
                    </div>
                    <span class="db-due-meta"><?= htmlspecialchars($s['roll_no']) ?> &bull; <?= htmlspecialchars($s['course']) ?></span>
                </div>
                <a href="fees.php?roll_no=<?= urlencode($s['roll_no']) ?>" class="db-pay-btn ripple">Pay</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Live Activity -->
    <div class="db-panel db-panel-mt">
        <div class="db-panel-row">
            <div class="db-panel-head" style="margin-bottom:0;">
                <span class="db-live-dot"></span>Live Activity
            </div>
            <a href="activity.php" class="db-link-sm">View all &rarr;</a>
        </div>
        <div id="dashActivityFeed">
            <?php for($i=0;$i<4;$i++): ?>
            <div class="db-shimmer-row">
                <div class="db-shimmer-block" style="width:30px;height:30px;border-radius:50%;flex-shrink:0;"></div>
                <div style="flex:1;">
                    <div class="db-shimmer-block" style="height:10px;width:62%;margin-bottom:5px;"></div>
                    <div class="db-shimmer-block" style="height:8px;width:38%;"></div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>

</div>

<script>
// ── Instant search — no Go button ──
(function(){
    const input = document.getElementById('dashRollInput');
    const result = document.getElementById('dashSearchResult');
    if(!input) return;
    function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    let timer;
    input.addEventListener('input', function(){
        clearTimeout(timer);
        const q = this.value.trim();
        if(!q){ result.innerHTML=''; return; }
        timer = setTimeout(()=>{
            fetch('ajax_search.php?roll_no='+encodeURIComponent(q))
            .then(r=>r.json())
            .then(d=>{
                if(d.found){
                    result.innerHTML=`<div class="db-search-result">
                        <div style="font-weight:700;font-size:.83rem;color:var(--crimson-dk);">${esc(d.name)}</div>
                        <div style="font-size:.68rem;color:var(--text-light);margin:.1rem 0 .5rem;">${esc(d.roll_no)} &bull; ${esc(d.course)}</div>
                        <div style="display:flex;gap:.4rem;">
                            <a href="view.php?roll_no=${encodeURIComponent(d.roll_no)}" style="padding:.2rem .6rem;background:var(--crimson);color:#fff;border-radius:5px;font-size:.68rem;font-weight:700;text-decoration:none;">&#128065; View</a>
                            <a href="fees.php?roll_no=${encodeURIComponent(d.roll_no)}" style="padding:.2rem .6rem;background:#16a34a;color:#fff;border-radius:5px;font-size:.68rem;font-weight:700;text-decoration:none;">&#8377; Fee</a>
                        </div>
                    </div>`;
                } else {
                    result.innerHTML=`<div style="margin-top:.4rem;font-size:.7rem;color:#aaa;padding:.3rem 0;">No student found for "<strong>${esc(q)}</strong>"</div>`;
                }
            }).catch(()=>{});
        }, 300);
    });
})();

// ── Activity feed ──
(function(){
    function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
    const cc={student:'#3b82f6',admin:'#8b5cf6',email:'#10b981',system:'#9ca3af'};
    const cb={student:'#dbeafe',admin:'#ede9fe',email:'#d1fae5',system:'#f3f4f6'};
    function load(){
        fetch('ajax_activity.php?category=all&limit=6')
        .then(r=>r.json())
        .then(data=>{
            const logs=data.logs||[];
            const el=document.getElementById('dashActivityFeed');
            if(!el) return;
            if(!logs.length){ el.innerHTML='<div style="padding:1rem;text-align:center;font-size:.73rem;color:#ccc;">No activity yet.</div>'; return; }
            el.innerHTML=logs.map(l=>`
            <div class="db-feed-item">
                <div class="db-feed-icon" style="background:${cb[l.category]||'#f3f4f6'}">${esc(l.icon)}</div>
                <div style="flex:1;min-width:0;">
                    <div class="db-feed-action">${esc(l.action)}</div>
                    <div class="db-feed-meta">${esc(l.time_ago)} &bull; <span style="color:${cc[l.category]||'#aaa'};font-weight:700;">${esc(l.category)}</span></div>
                </div>
            </div>`).join('');
        }).catch(()=>{});
    }
    load(); setInterval(load,8000);
})();

// ── Ripple effect on .ripple elements ──
document.addEventListener('click',function(e){
    const el=e.target.closest('.ripple');
    if(!el) return;
    const r=el.getBoundingClientRect();
    const w=document.createElement('span');
    w.className='ripple-wave';
    const sz=Math.max(r.width,r.height)*2;
    w.style.cssText=`width:${sz}px;height:${sz}px;top:${e.clientY-r.top-sz/2}px;left:${e.clientX-r.left-sz/2}px`;
    el.appendChild(w);
    w.addEventListener('animationend',()=>w.remove());
});
</script>

<?php include 'includes/footer.php'; ?>
