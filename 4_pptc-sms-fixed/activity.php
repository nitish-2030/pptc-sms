<?php
// ============================================================
// activity.php — Live Activity System (Feature 2)
// 4 tabs: Student | Admin | Email | System
// Social feed style, AJAX auto-refresh every 5s
// ============================================================
require_once 'config/db.php';
$pageTitle = 'Live Activity';
$baseUrl   = '';

// Quick stats
$stats = [];
$sr = mysqli_query($conn, "SELECT category, COUNT(*) AS c FROM activity_logs GROUP BY category");
if ($sr) while ($r = mysqli_fetch_assoc($sr)) $stats[$r['category']] = (int)$r['c'];
$stats['all'] = array_sum($stats);

// Latest entry time
$latest_res = mysqli_query($conn, "SELECT created_at FROM activity_logs ORDER BY created_at DESC LIMIT 1");
$latest_row = $latest_res ? mysqli_fetch_assoc($latest_res) : null;
$latest_time = $latest_row ? date('d M Y, h:i A', strtotime($latest_row['created_at'])) : 'No activity yet';

include 'includes/header.php';
?>

<div class="container act-page">

    <!-- ── Top bar ── -->
    <div class="act-topbar">
        <div>
            <h1 class="act-title">Live Activity Stream</h1>
            <p class="act-subtitle">
                <span class="act-live-dot"></span>
                Auto-refreshing every 5 seconds &nbsp;&bull;&nbsp; Last update: <span id="lastUpdate"><?= $latest_time ?></span>
            </p>
        </div>
        <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;">
            <button id="pauseBtn" class="act-ctrl-btn act-ctrl-btn--pause">⏸ Pause</button>
            <button id="refreshBtn" class="act-ctrl-btn">↺ Refresh</button>
            <a href="dashboard.php" class="act-ctrl-btn">← Dashboard</a>
        </div>
    </div>

    <!-- ── Summary cards ── -->
    <div class="act-summary-grid">
        <div class="act-sum-card act-sum-card--all" data-tab="all">
            <div class="act-sum-icon">📊</div>
            <div>
                <div class="act-sum-num" id="cnt-all"><?= $stats['all'] ?? 0 ?></div>
                <div class="act-sum-lbl">Total Events</div>
            </div>
        </div>
        <div class="act-sum-card act-sum-card--student" data-tab="student">
            <div class="act-sum-icon">📚</div>
            <div>
                <div class="act-sum-num" id="cnt-student"><?= $stats['student'] ?? 0 ?></div>
                <div class="act-sum-lbl">Student Events</div>
            </div>
        </div>
        <div class="act-sum-card act-sum-card--admin" data-tab="admin">
            <div class="act-sum-icon">🔐</div>
            <div>
                <div class="act-sum-num" id="cnt-admin"><?= $stats['admin'] ?? 0 ?></div>
                <div class="act-sum-lbl">Admin Events</div>
            </div>
        </div>
        <div class="act-sum-card act-sum-card--email" data-tab="email">
            <div class="act-sum-icon">📧</div>
            <div>
                <div class="act-sum-num" id="cnt-email"><?= $stats['email'] ?? 0 ?></div>
                <div class="act-sum-lbl">Email Events</div>
            </div>
        </div>
        <div class="act-sum-card act-sum-card--system" data-tab="system">
            <div class="act-sum-icon">⚙️</div>
            <div>
                <div class="act-sum-num" id="cnt-system"><?= $stats['system'] ?? 0 ?></div>
                <div class="act-sum-lbl">System Events</div>
            </div>
        </div>
    </div>

    <!-- ── Tab bar ── -->
    <div class="act-tabs">
        <button class="act-tab act-tab--active" data-cat="all">
            <span>All</span>
            <span class="act-tab-badge" id="badge-all"><?= $stats['all'] ?? 0 ?></span>
        </button>
        <button class="act-tab act-tab--student" data-cat="student">
            <span>👥 Students</span>
            <span class="act-tab-badge act-tab-badge--student" id="badge-student"><?= $stats['student'] ?? 0 ?></span>
        </button>
        <button class="act-tab act-tab--admin" data-cat="admin">
            <span>🔐 Admin</span>
            <span class="act-tab-badge act-tab-badge--admin" id="badge-admin"><?= $stats['admin'] ?? 0 ?></span>
        </button>
        <button class="act-tab act-tab--email" data-cat="email">
            <span>📧 Emails</span>
            <span class="act-tab-badge act-tab-badge--email" id="badge-email"><?= $stats['email'] ?? 0 ?></span>
        </button>
        <button class="act-tab act-tab--system" data-cat="system">
            <span>⚙️ System</span>
            <span class="act-tab-badge act-tab-badge--system" id="badge-system"><?= $stats['system'] ?? 0 ?></span>
        </button>
    </div>

    <!-- ── Feed ── -->
    <div class="act-feed-wrap">
        <!-- Loading shimmer -->
        <div id="feedLoader" class="act-feed-loader">
            <?php for($i=0;$i<5;$i++): ?>
            <div class="act-shimmer-row">
                <div class="act-shimmer act-shimmer-avatar"></div>
                <div style="flex:1;">
                    <div class="act-shimmer act-shimmer-line" style="width:60%;"></div>
                    <div class="act-shimmer act-shimmer-line" style="width:40%;margin-top:6px;height:10px;"></div>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Actual feed -->
        <div id="actFeed" style="display:none;"></div>

        <!-- Empty state -->
        <div id="actEmpty" style="display:none;" class="act-empty">
            <div class="act-empty-icon">📭</div>
            <p>No activity in this category yet.</p>
            <span>Events will appear here as actions happen in the system.</span>
        </div>
    </div>

</div>

<style>
/* ── Page ── */
.act-page { padding-bottom: 3rem; }

/* ── Topbar ── */
.act-topbar { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem; }
.act-title  { font-family:'Cinzel',serif; font-size:1.4rem; font-weight:700; color:var(--crimson-dk); }
.act-subtitle { font-size:.8rem; color:var(--text-light); margin-top:.3rem; display:flex; align-items:center; gap:.4rem; }
.act-live-dot { width:8px; height:8px; border-radius:50%; background:#22c55e; display:inline-block; box-shadow:0 0 0 0 rgba(34,197,94,.4); animation:livePulse 2s infinite; }
@keyframes livePulse { 0%{box-shadow:0 0 0 0 rgba(34,197,94,.5);} 70%{box-shadow:0 0 0 8px rgba(34,197,94,0);} 100%{box-shadow:0 0 0 0 rgba(34,197,94,0);} }

/* ── Control buttons ── */
.act-ctrl-btn { padding:.42rem .9rem; border-radius:8px; font-family:'Nunito',sans-serif; font-size:.8rem; font-weight:700; cursor:pointer; border:1.5px solid #e0cece; background:#fff; color:var(--text-mid); text-decoration:none; transition:all .18s; display:inline-flex; align-items:center; gap:.3rem; }
.act-ctrl-btn:hover { border-color:var(--crimson); color:var(--crimson); background:#fdecea; }
.act-ctrl-btn--pause { background:#fff8e1; color:#92400e; border-color:#fcd34d; }
.act-ctrl-btn--pause.paused { background:#fdecea; color:var(--crimson); border-color:var(--crimson); }

/* ── Summary cards ── */
.act-summary-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:.85rem; margin-bottom:1.25rem; }
.act-sum-card { background:#fff; border-radius:12px; padding:1rem 1.1rem; display:flex; align-items:center; gap:.85rem; border:1.5px solid transparent; box-shadow:0 2px 10px rgba(0,0,0,.05); cursor:pointer; transition:all .2s; }
.act-sum-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.1); }
.act-sum-icon { font-size:1.5rem; flex-shrink:0; }
.act-sum-num { font-size:1.6rem; font-weight:900; font-family:'Cinzel',serif; line-height:1; }
.act-sum-lbl { font-size:.68rem; text-transform:uppercase; letter-spacing:.07em; color:var(--text-light); font-weight:700; margin-top:.15rem; }
.act-sum-card--all     { border-color:rgba(139,0,0,.12); } .act-sum-card--all .act-sum-num     { color:var(--crimson); }
.act-sum-card--student { border-color:rgba(29,78,216,.12);} .act-sum-card--student .act-sum-num { color:#1d4ed8; }
.act-sum-card--admin   { border-color:rgba(124,58,237,.12);}.act-sum-card--admin .act-sum-num   { color:#7c3aed; }
.act-sum-card--email   { border-color:rgba(5,150,105,.12); }.act-sum-card--email .act-sum-num   { color:#059669; }
.act-sum-card--system  { border-color:rgba(75,85,99,.12);  }.act-sum-card--system .act-sum-num  { color:#4b5563; }
.act-sum-card.active   { background:var(--cream); box-shadow:0 6px 20px rgba(0,0,0,.1); }

/* ── Tabs ── */
.act-tabs { display:flex; gap:.4rem; flex-wrap:wrap; margin-bottom:1rem; background:#fff; border-radius:12px; padding:.5rem; border:1px solid rgba(139,0,0,.07); box-shadow:0 1px 6px rgba(0,0,0,.04); }
.act-tab { flex:1; min-width:90px; padding:.5rem .75rem; border-radius:8px; border:none; background:transparent; font-family:'Nunito',sans-serif; font-size:.82rem; font-weight:700; cursor:pointer; color:var(--text-light); display:flex; align-items:center; justify-content:center; gap:.4rem; transition:all .2s; white-space:nowrap; }
.act-tab:hover { background:#f5f0f0; color:var(--text-dark); }
.act-tab--active { background:linear-gradient(135deg,var(--crimson-dk),var(--crimson)); color:#fff !important; box-shadow:0 4px 14px rgba(139,0,0,.3); }
.act-tab-badge { padding:.1rem .45rem; border-radius:20px; font-size:.65rem; font-weight:800; background:rgba(139,0,0,.1); color:var(--crimson-dk); min-width:20px; text-align:center; }
.act-tab--active .act-tab-badge { background:rgba(255,255,255,.25); color:#fff; }
.act-tab-badge--student { background:rgba(29,78,216,.1); color:#1d4ed8; }
.act-tab-badge--admin   { background:rgba(124,58,237,.1); color:#7c3aed; }
.act-tab-badge--email   { background:rgba(5,150,105,.1);  color:#059669; }
.act-tab-badge--system  { background:rgba(75,85,99,.1);   color:#4b5563; }

/* ── Feed wrapper ── */
.act-feed-wrap { background:#fff; border-radius:14px; border:1px solid rgba(139,0,0,.07); box-shadow:0 2px 14px rgba(0,0,0,.05); overflow:hidden; min-height:300px; }

/* ── Feed items ── */
.act-item { display:flex; gap:1rem; padding:.9rem 1.25rem; border-bottom:1px solid #f8f0e8; transition:background .15s; position:relative; animation:feedIn .3s ease; }
@keyframes feedIn { from{opacity:0;transform:translateY(-8px);} to{opacity:1;transform:translateY(0);} }
.act-item:last-child { border-bottom:none; }
.act-item:hover { background:#fdf9f6; }
.act-item.act-item--new { background:#fffbeb; }

/* Avatar circle */
.act-avatar { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; position:relative; }
.act-avatar--student { background:linear-gradient(135deg,#dbeafe,#bfdbfe); }
.act-avatar--admin   { background:linear-gradient(135deg,#ede9fe,#ddd6fe); }
.act-avatar--email   { background:linear-gradient(135deg,#d1fae5,#a7f3d0); }
.act-avatar--system  { background:linear-gradient(135deg,#f3f4f6,#e5e7eb); }

/* Category dot */
.act-cat-dot { position:absolute; bottom:-1px; right:-1px; width:14px; height:14px; border-radius:50%; border:2px solid #fff; font-size:.45rem; display:flex; align-items:center; justify-content:center; }
.act-cat-dot--student { background:#3b82f6; }
.act-cat-dot--admin   { background:#8b5cf6; }
.act-cat-dot--email   { background:#10b981; }
.act-cat-dot--system  { background:#6b7280; }

/* Content */
.act-content { flex:1; min-width:0; }
.act-action-text { font-size:.875rem; font-weight:700; color:var(--text-dark); line-height:1.4; margin-bottom:.2rem; }
.act-action-text .act-highlight { color:var(--crimson-dk); font-weight:800; }
.act-detail { font-size:.75rem; color:var(--text-light); line-height:1.5; }
.act-meta { display:flex; align-items:center; gap:.6rem; margin-top:.3rem; flex-wrap:wrap; }
.act-time { font-size:.68rem; color:#bbb; font-weight:600; }
.act-cat-pill { padding:.1rem .5rem; border-radius:10px; font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
.act-cat-pill--student { background:#dbeafe; color:#1d4ed8; }
.act-cat-pill--admin   { background:#ede9fe; color:#7c3aed; }
.act-cat-pill--email   { background:#d1fae5; color:#059669; }
.act-cat-pill--system  { background:#f3f4f6; color:#6b7280; }

/* Color bar on left */
.act-item::before { content:''; position:absolute; left:0; top:0; bottom:0; width:3px; border-radius:0 3px 3px 0; }
.act-item[data-color="green"]::before  { background:#22c55e; }
.act-item[data-color="red"]::before    { background:#ef4444; }
.act-item[data-color="blue"]::before   { background:#3b82f6; }
.act-item[data-color="purple"]::before { background:#8b5cf6; }
.act-item[data-color="teal"]::before   { background:#14b8a6; }
.act-item[data-color="amber"]::before  { background:#f59e0b; }
.act-item[data-color="gray"]::before   { background:#9ca3af; }

/* Shimmer */
.act-feed-loader { padding:.75rem 1.25rem; }
.act-shimmer-row { display:flex; gap:1rem; align-items:center; padding:.75rem 0; border-bottom:1px solid #f8f0e8; }
.act-shimmer { background:linear-gradient(90deg,#f5f0f0 25%,#ede8e8 50%,#f5f0f0 75%); background-size:200% 100%; animation:shimmer 1.5s infinite; border-radius:6px; }
@keyframes shimmer { 0%{background-position:200% 0;} 100%{background-position:-200% 0;} }
.act-shimmer-avatar { width:40px; height:40px; border-radius:50%; flex-shrink:0; }
.act-shimmer-line { height:14px; border-radius:6px; }

/* Empty state */
.act-empty { text-align:center; padding:4rem 1.5rem; }
.act-empty-icon { font-size:3rem; margin-bottom:1rem; opacity:.4; }
.act-empty p { font-weight:700; color:var(--text-mid); margin-bottom:.3rem; }
.act-empty span { font-size:.82rem; color:var(--text-light); }

/* New activity toast */
.act-toast { position:fixed; top:80px; right:1.5rem; background:#1a1a2e; color:#fff; padding:.65rem 1.1rem; border-radius:10px; font-size:.8rem; font-weight:700; z-index:999; box-shadow:0 8px 24px rgba(0,0,0,.3); transform:translateX(200%); transition:transform .3s cubic-bezier(.34,1.56,.64,1); display:flex; align-items:center; gap:.5rem; }
.act-toast.show { transform:translateX(0); }

@media(max-width:640px) {
    .act-summary-grid { grid-template-columns:1fr 1fr; }
    .act-tabs { gap:.25rem; }
    .act-tab  { font-size:.72rem; padding:.45rem .5rem; min-width:70px; }
}
</style>

<!-- New activity toast -->
<div class="act-toast" id="actToast">🔔 <span id="actToastMsg">New activity</span></div>

<script>
(function(){
    let currentCat   = 'all';
    let paused       = false;
    let lastId       = 0;
    let refreshTimer = null;
    let isFirstLoad  = true;

    const feed     = document.getElementById('actFeed');
    const loader   = document.getElementById('feedLoader');
    const empty    = document.getElementById('actEmpty');
    const pauseBtn = document.getElementById('pauseBtn');
    const toast    = document.getElementById('actToast');
    const toastMsg = document.getElementById('actToastMsg');

    // ── Color map ──
    const catColors = {
        student: '#3b82f6', admin: '#8b5cf6', email: '#10b981', system: '#6b7280'
    };

    // ── Escape HTML ──
    function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    // ── Render a single feed item ──
    function renderItem(log, isNew = false) {
        const catPill  = `<span class="act-cat-pill act-cat-pill--${esc(log.category)}">${esc(log.category)}</span>`;
        const catDot   = `<span class="act-cat-dot act-cat-dot--${esc(log.category)}"></span>`;
        const newClass = isNew ? 'act-item--new' : '';

        // Bold the student name if present
        let actionHtml = esc(log.action);
        if (log.student_name) {
            actionHtml = actionHtml.replace(
                esc(log.student_name),
                `<span class="act-highlight">${esc(log.student_name)}</span>`
            );
        }

        return `
        <div class="act-item ${newClass}" data-id="${log.id}" data-color="${esc(log.color)}">
            <div class="act-avatar act-avatar--${esc(log.category)}">
                <span>${esc(log.icon)}</span>
                ${catDot}
            </div>
            <div class="act-content">
                <div class="act-action-text">${actionHtml}</div>
                ${log.detail ? `<div class="act-detail">${esc(log.detail)}</div>` : ''}
                <div class="act-meta">
                    ${catPill}
                    <span class="act-time" title="${esc(log.time_fmt)}">${esc(log.time_ago)}</span>
                    ${log.student_name && log.student_id
                        ? `<a href="view.php?roll_no=" style="font-size:.68rem;color:var(--crimson);font-weight:700;text-decoration:none;" onclick="event.preventDefault()">@${esc(log.student_name)}</a>`
                        : ''}
                </div>
            </div>
        </div>`;
    }

    // ── Render empty / group header ──
    function renderGrouped(logs) {
        if (!logs.length) return '';
        let html = '';
        let lastDate = '';
        logs.forEach(log => {
            const d = log.time_fmt.split(',')[0];
            if (d !== lastDate) {
                html += `<div style="padding:.5rem 1.25rem;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#ccc;background:#fafafa;border-bottom:1px solid #f5ede0;">${d}</div>`;
                lastDate = d;
            }
            html += renderItem(log);
        });
        return html;
    }

    // ── Fetch and render ──
    function fetchFeed(showLoader = false) {
        if (paused) return;
        if (showLoader) {
            loader.style.display = 'block';
            feed.style.display   = 'none';
            empty.style.display  = 'none';
        }

        fetch(`ajax_activity.php?category=${encodeURIComponent(currentCat)}&limit=40`)
            .then(r => r.json())
            .then(data => {
                // Update counts
                const c = data.counts || {};
                ['all','student','admin','email','system'].forEach(cat => {
                    const el = document.getElementById('cnt-'+cat);
                    if (el) el.textContent = c[cat] || 0;
                    const badge = document.getElementById('badge-'+cat);
                    if (badge) badge.textContent = c[cat] || 0;
                });

                const logs = data.logs || [];
                loader.style.display = 'none';

                if (!logs.length) {
                    feed.style.display  = 'none';
                    empty.style.display = 'block';
                    return;
                }

                empty.style.display = 'none';
                feed.style.display  = 'block';

                // Check for new items
                const newMaxId = logs.length ? logs[0].id : 0;
                if (!isFirstLoad && newMaxId > lastId && lastId > 0) {
                    const newCount = logs.filter(l => l.id > lastId).length;
                    showToast(`${newCount} new event${newCount > 1 ? 's' : ''}`);
                    // Prepend new items with animation
                    const newHtml = logs.filter(l => l.id > lastId).map(l => renderItem(l, true)).join('');
                    feed.innerHTML = newHtml + feed.innerHTML;
                    // Remove extras beyond 40
                    const items = feed.querySelectorAll('.act-item');
                    if (items.length > 40) {
                        for (let i = 40; i < items.length; i++) items[i].remove();
                    }
                    document.getElementById('lastUpdate').textContent = logs[0].time_fmt;
                } else {
                    feed.innerHTML = renderGrouped(logs);
                    if (logs.length) document.getElementById('lastUpdate').textContent = logs[0].time_fmt;
                }

                lastId     = newMaxId;
                isFirstLoad = false;
            })
            .catch(() => {
                loader.style.display = 'none';
                if (!feed.innerHTML) {
                    feed.style.display  = 'block';
                    feed.innerHTML = '<div style="padding:2rem;text-align:center;color:#ccc;font-size:.85rem;">Could not load activity. Check connection.</div>';
                }
            });
    }

    // ── Toast ──
    function showToast(msg) {
        toastMsg.textContent = msg;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // ── Start auto-refresh ──
    function startRefresh() {
        clearInterval(refreshTimer);
        refreshTimer = setInterval(() => fetchFeed(false), 5000);
    }

    // ── Tab switching ──
    document.querySelectorAll('.act-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.act-tab').forEach(t => t.classList.remove('act-tab--active'));
            this.classList.add('act-tab--active');
            currentCat  = this.dataset.cat;
            isFirstLoad = true;
            lastId      = 0;
            fetchFeed(true);
        });
    });

    // ── Summary card click ──
    document.querySelectorAll('.act-sum-card').forEach(card => {
        card.addEventListener('click', function() {
            const tab = this.dataset.tab;
            document.querySelectorAll('.act-tab').forEach(t => {
                t.classList.toggle('act-tab--active', t.dataset.cat === tab);
            });
            document.querySelectorAll('.act-sum-card').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            currentCat  = tab;
            isFirstLoad = true;
            lastId      = 0;
            fetchFeed(true);
        });
    });

    // ── Pause / Resume ──
    pauseBtn.addEventListener('click', function() {
        paused = !paused;
        this.textContent = paused ? '▶ Resume' : '⏸ Pause';
        this.classList.toggle('paused', paused);
        if (!paused) { fetchFeed(false); startRefresh(); }
        else clearInterval(refreshTimer);
    });

    // ── Manual refresh ──
    document.getElementById('refreshBtn').addEventListener('click', () => {
        isFirstLoad = true;
        lastId      = 0;
        fetchFeed(true);
    });

    // ── Init ──
    fetchFeed(true);
    startRefresh();

})();
</script>

<?php include 'includes/footer.php'; ?>
