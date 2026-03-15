<?php
// ============================================================
// includes/header.php — v6 Modern SaaS Header
// ============================================================
require_once __DIR__ . '/../config/auth_check.php';
$pageTitle = isset($pageTitle) ? $pageTitle . ' | PPTC SMS' : 'PPTC Student Management';
$base      = $baseUrl ?? '';
$cur_page  = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="<?= $base ?>assets/img/pptc_logo.png" type="image/png">
    <style>
    /* ===================================================
       HEADER v6 — Modern SaaS Admin Nav
       =================================================== */
    .site-header {
        background: #fff;
        height: 60px;
        padding: 0 24px;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 1px 0 rgba(0,0,0,.07), 0 2px 10px rgba(0,0,0,.04);
        border-bottom: 1px solid rgba(0,0,0,.06);
        transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    }
    [data-theme="dark"] .site-header {
        background: #12121a;
        border-bottom-color: rgba(255,255,255,.07);
        box-shadow: 0 1px 0 rgba(255,255,255,.04), 0 4px 20px rgba(0,0,0,.5);
    }
    .header-inner {
        max-width: 1300px;
        margin: 0 auto;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0;
        padding: 0;
    }

    /* Logo */
    .header-logo { display:flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0; }
    .logo-img { width:34px;height:34px;object-fit:contain;filter:none;animation:none; }
    .header-title { display:flex;flex-direction:column; }
    .college-name { font-family:'Cinzel',serif;font-size:.8rem;font-weight:700;color:var(--crimson-dk);letter-spacing:.02em;line-height:1.2; }
    [data-theme="dark"] .college-name { color:#e8c76a; }
    .college-sub { font-size:.58rem;color:#9ca3af;letter-spacing:.05em;text-transform:uppercase;margin-top:1px; }
    [data-theme="dark"] .college-sub { color:#4b5563; }

    /* Center nav */
    .header-nav { display:flex;align-items:center;gap:2px;flex:1;justify-content:center;padding:0 20px; }

    .nav-item { position:relative; }
    .nav-link {
        display:inline-flex;align-items:center;gap:5px;
        padding:6px 11px;border-radius:7px;
        font-family:'Nunito',sans-serif;font-size:.8rem;font-weight:700;
        color:#374151;text-decoration:none;border:none;background:transparent;
        cursor:pointer;white-space:nowrap;
        transition:background .14s,color .14s;
    }
    [data-theme="dark"] .nav-link { color:#c9d1d9; }
    .nav-link:hover { background:#f3f4f6;color:var(--crimson); }
    [data-theme="dark"] .nav-link:hover { background:rgba(255,255,255,.07);color:#e8c76a; }
    .nav-link.active { background:#fef2f2;color:var(--crimson); }
    [data-theme="dark"] .nav-link.active { background:rgba(139,0,0,.2);color:#fca5a5; }

    .nav-chevron { font-size:.52rem;opacity:.45;transition:transform .2s;display:inline-block;margin-left:1px; }
    .nav-item.open .nav-chevron { transform:rotate(180deg);opacity:.9; }

    /* Dropdown */
    .nav-dropdown {
        position:absolute;top:calc(100% + 10px);left:50%;
        transform:translateX(-50%) translateY(-6px) scale(.97);
        background:#fff;border:1px solid rgba(0,0,0,.09);
        border-radius:12px;box-shadow:0 8px 28px rgba(0,0,0,.13),0 2px 8px rgba(0,0,0,.06);
        padding:6px;min-width:186px;
        opacity:0;pointer-events:none;
        transition:opacity .17s ease,transform .17s ease;
        z-index:2000;
    }
    [data-theme="dark"] .nav-dropdown {
        background:#1c1c28;border-color:rgba(255,255,255,.09);
        box-shadow:0 8px 32px rgba(0,0,0,.55);
    }
    .nav-item.open .nav-dropdown { opacity:1;pointer-events:all;transform:translateX(-50%) translateY(0) scale(1); }

    /* Dropdown caret */
    .nav-dropdown::before {
        content:'';position:absolute;top:-5px;left:50%;transform:translateX(-50%) rotate(45deg);
        width:9px;height:9px;background:#fff;
        border-left:1px solid rgba(0,0,0,.09);border-top:1px solid rgba(0,0,0,.09);
    }
    [data-theme="dark"] .nav-dropdown::before { background:#1c1c28;border-color:rgba(255,255,255,.09); }

    /* Drop items */
    .nav-drop-item {
        display:flex;align-items:center;gap:9px;padding:7px 10px;border-radius:8px;
        font-family:'Nunito',sans-serif;font-size:.79rem;font-weight:600;
        color:#374151;text-decoration:none;
        transition:background .12s,color .12s;
    }
    [data-theme="dark"] .nav-drop-item { color:#c9d1d9; }
    .nav-drop-item:hover { background:#fef2f2;color:var(--crimson); }
    [data-theme="dark"] .nav-drop-item:hover { background:rgba(139,0,0,.14);color:#fca5a5; }
    .nav-drop-item.active-item { background:#fef2f2;color:var(--crimson);font-weight:700; }
    [data-theme="dark"] .nav-drop-item.active-item { background:rgba(139,0,0,.2);color:#fca5a5; }

    .drop-icon {
        width:26px;height:26px;border-radius:7px;
        display:flex;align-items:center;justify-content:center;
        font-size:.8rem;background:#f3f4f6;flex-shrink:0;
        transition:background .12s;
    }
    [data-theme="dark"] .drop-icon { background:rgba(255,255,255,.07); }
    .nav-drop-item:hover .drop-icon { background:#fee2e2; }
    [data-theme="dark"] .nav-drop-item:hover .drop-icon { background:rgba(139,0,0,.18); }

    .drop-label { font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.09em;color:#9ca3af;padding:6px 10px 3px;display:block; }
    [data-theme="dark"] .drop-label { color:#4b5563; }
    .drop-divider { height:1px;background:rgba(0,0,0,.05);margin:4px; }
    [data-theme="dark"] .drop-divider { background:rgba(255,255,255,.06); }

    /* Right side */
    .header-right { display:flex;align-items:center;gap:5px;flex-shrink:0; }

    .hdr-icon-btn {
        width:34px;height:34px;border-radius:8px;
        border:1px solid rgba(0,0,0,.08);background:transparent;
        cursor:pointer;display:flex;align-items:center;justify-content:center;
        font-size:.95rem;color:#6b7280;
        transition:background .14s,color .14s,border-color .14s;
        text-decoration:none;position:relative;
    }
    [data-theme="dark"] .hdr-icon-btn { border-color:rgba(255,255,255,.08);color:#9ca3af; }
    .hdr-icon-btn:hover { background:#f3f4f6;color:var(--crimson);border-color:rgba(139,0,0,.18); }
    [data-theme="dark"] .hdr-icon-btn:hover { background:rgba(255,255,255,.07);color:#e8c76a;border-color:rgba(232,199,106,.28); }

    /* Tooltip */
    .hdr-icon-btn::after {
        content:attr(data-tip);position:absolute;bottom:-30px;left:50%;transform:translateX(-50%);
        background:#111827;color:#fff;font-family:'Nunito',sans-serif;
        font-size:.62rem;font-weight:600;padding:3px 7px;border-radius:5px;
        white-space:nowrap;opacity:0;pointer-events:none;transition:opacity .14s;
    }
    .hdr-icon-btn:hover::after { opacity:1; }
    [data-theme="dark"] .hdr-icon-btn::after { background:#374151; }

    /* Theme icon */
    .theme-icon-moon { display:none; }
    .theme-icon-sun  { display:inline; }
    [data-theme="dark"] .theme-icon-moon { display:inline; }
    [data-theme="dark"] .theme-icon-sun  { display:none; }

    /* Logout */
    .hdr-logout {
        display:inline-flex;align-items:center;gap:5px;
        padding:6px 11px;border-radius:7px;
        font-family:'Nunito',sans-serif;font-size:.77rem;font-weight:700;
        color:#9ca3af;text-decoration:none;
        border:1px solid rgba(0,0,0,.07);background:transparent;
        cursor:pointer;transition:all .14s;
    }
    [data-theme="dark"] .hdr-logout { color:#6b7280;border-color:rgba(255,255,255,.07); }
    .hdr-logout:hover { background:#fef2f2;color:#dc2626;border-color:rgba(220,38,38,.22); }
    [data-theme="dark"] .hdr-logout:hover { background:rgba(220,38,38,.1);color:#fca5a5;border-color:rgba(220,38,38,.28); }

    /* Hamburger */
    .hdr-hamburger {
        display:none;width:34px;height:34px;border-radius:8px;
        border:1px solid rgba(0,0,0,.08);background:transparent;
        cursor:pointer;align-items:center;justify-content:center;
        font-size:1.1rem;color:#6b7280;
    }
    [data-theme="dark"] .hdr-hamburger { border-color:rgba(255,255,255,.08);color:#9ca3af; }

    /* Mobile */
    @media(max-width:860px){
        .header-nav{display:none;}
        .hdr-hamburger{display:flex;}
        .site-header,.header-inner{height:56px;}
    }
    .nav-mobile-panel {
        display:none;position:fixed;inset:56px 0 0 0;
        background:#fff;z-index:999;overflow-y:auto;
        padding:1rem;border-top:1px solid rgba(0,0,0,.06);
    }
    [data-theme="dark"] .nav-mobile-panel { background:#12121a;border-top-color:rgba(255,255,255,.07); }
    .nav-mobile-panel.open{display:block;}
    .mob-nav-item {
        display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;
        font-family:'Nunito',sans-serif;font-size:.84rem;font-weight:600;
        color:#374151;text-decoration:none;margin-bottom:2px;
        transition:background .12s,color .12s;
    }
    [data-theme="dark"] .mob-nav-item{color:#c9d1d9;}
    .mob-nav-item:hover{background:#fef2f2;color:var(--crimson);}
    [data-theme="dark"] .mob-nav-item:hover{background:rgba(139,0,0,.14);color:#fca5a5;}
    .mob-section-label{font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#9ca3af;padding:10px 12px 3px;}
    [data-theme="dark"] .mob-section-label{color:#4b5563;}
    </style>
</head>
<body>

<script>
(function(){
    var t=localStorage.getItem('pptc_theme')||'light';
    document.documentElement.setAttribute('data-theme',t);
}());
</script>

<header class="site-header">
    <div class="header-inner">

        <a href="<?= $base ?>dashboard.php" class="header-logo">
            <img src="<?= $base ?>assets/img/pptc_logo.png" alt="PPTC" class="logo-img">
            <div class="header-title">
                <span class="college-name">Pentium Point Technical College</span>
                <span class="college-sub">SMS &middot; Rewa (M.P.)</span>
            </div>
        </a>

        <nav class="header-nav" id="mainNav">

            <div class="nav-item">
                <a href="<?= $base ?>dashboard.php" class="nav-link <?= $cur_page==='dashboard.php'?'active':'' ?>">
                    &#x1F3E0; Dashboard
                </a>
            </div>

            <div class="nav-item" id="navStudents">
                <button class="nav-link <?= in_array($cur_page,['view_all.php','insert.php','update.php','delete.php','view.php'])?'active':'' ?>" onclick="toggleNav('navStudents')">
                    &#x1F464; Students <span class="nav-chevron">&#9660;</span>
                </button>
                <div class="nav-dropdown">
                    <a href="<?= $base ?>view_all.php"  class="nav-drop-item <?= $cur_page==='view_all.php'?'active-item':'' ?>"><span class="drop-icon">&#x1F4CB;</span>All Students</a>
                    <a href="<?= $base ?>insert.php"    class="nav-drop-item <?= $cur_page==='insert.php'?'active-item':'' ?>"><span class="drop-icon">&#x2795;</span>Add Student</a>
                    <a href="<?= $base ?>update.php"    class="nav-drop-item <?= $cur_page==='update.php'?'active-item':'' ?>"><span class="drop-icon">&#x270F;</span>Update Student</a>
                    <a href="<?= $base ?>delete.php"    class="nav-drop-item <?= $cur_page==='delete.php'?'active-item':'' ?>"><span class="drop-icon">&#x1F6AB;</span>Deactivate</a>
                </div>
            </div>

            <div class="nav-item" id="navFees">
                <button class="nav-link <?= in_array($cur_page,['fees.php','fee_report.php','warnings.php'])?'active':'' ?>" onclick="toggleNav('navFees')">
                    &#x20B9; Fees <span class="nav-chevron">&#9660;</span>
                </button>
                <div class="nav-dropdown">
                    <a href="<?= $base ?>fees.php"       class="nav-drop-item <?= $cur_page==='fees.php'?'active-item':'' ?>"><span class="drop-icon">&#x1F4B3;</span>Collect Fee</a>
                    <a href="<?= $base ?>fee_report.php" class="nav-drop-item <?= $cur_page==='fee_report.php'?'active-item':'' ?>"><span class="drop-icon">&#x1F4CA;</span>Fee Report</a>
                    <a href="<?= $base ?>warnings.php"   class="nav-drop-item <?= $cur_page==='warnings.php'?'active-item':'' ?>"><span class="drop-icon">&#x26A0;</span>Fee Warnings</a>
                </div>
            </div>

            <div class="nav-item" id="navReports">
                <button class="nav-link <?= in_array($cur_page,['student_report.php','print_report.php'])?'active':'' ?>" onclick="toggleNav('navReports')">
                    &#x1F4C4; Reports <span class="nav-chevron">&#9660;</span>
                </button>
                <div class="nav-dropdown">
                    <a href="<?= $base ?>student_report.php" class="nav-drop-item <?= $cur_page==='student_report.php'?'active-item':'' ?>"><span class="drop-icon">&#x1F464;</span>Student Report</a>
                    <a href="<?= $base ?>print_report.php"   class="nav-drop-item <?= $cur_page==='print_report.php'?'active-item':'' ?>"><span class="drop-icon">&#x1F5A8;</span>Print Report</a>
                </div>
            </div>

            <div class="nav-item" id="navAnalytics">
                <button class="nav-link <?= in_array($cur_page,['insights.php','activity.php'])?'active':'' ?>" onclick="toggleNav('navAnalytics')">
                    &#x1F4C8; Analytics <span class="nav-chevron">&#9660;</span>
                </button>
                <div class="nav-dropdown">
                    <a href="<?= $base ?>insights.php"  class="nav-drop-item <?= $cur_page==='insights.php'?'active-item':'' ?>"><span class="drop-icon">&#x1F4A1;</span>Insights</a>
                    <a href="<?= $base ?>activity.php"  class="nav-drop-item <?= $cur_page==='activity.php'?'active-item':'' ?>"><span class="drop-icon">&#x26A1;</span>Activity Log</a>
                </div>
            </div>

        </nav>

        <div class="header-right">
            <a href="<?= $base ?>chatbot.php" class="hdr-icon-btn" data-tip="AI Assistant">&#x1F916;</a>
            <button class="hdr-icon-btn" id="dmToggle" onclick="toggleDarkMode()" data-tip="Toggle Theme">
                <span class="theme-icon-sun">&#9728;</span>
                <span class="theme-icon-moon">&#9790;</span>
            </button>
            <a href="<?= $base ?>logout.php" class="hdr-logout" onclick="return confirm('Are you sure you want to logout?')">&#x1F511; Logout</a>
            <button class="hdr-hamburger" onclick="toggleMobileNav()">&#9776;</button>
        </div>
    </div>
</header>

<div class="nav-mobile-panel" id="mobileNavPanel">
    <div class="mob-section-label">Main</div>
    <a href="<?= $base ?>dashboard.php" class="mob-nav-item">&#x1F3E0; Dashboard</a>
    <div class="mob-section-label">Students</div>
    <a href="<?= $base ?>view_all.php"  class="mob-nav-item">&#x1F4CB; All Students</a>
    <a href="<?= $base ?>insert.php"    class="mob-nav-item">&#x2795; Add Student</a>
    <a href="<?= $base ?>update.php"    class="mob-nav-item">&#x270F; Update</a>
    <a href="<?= $base ?>delete.php"    class="mob-nav-item">&#x1F6AB; Deactivate</a>
    <div class="mob-section-label">Fees</div>
    <a href="<?= $base ?>fees.php"       class="mob-nav-item">&#x1F4B3; Collect Fee</a>
    <a href="<?= $base ?>fee_report.php" class="mob-nav-item">&#x1F4CA; Fee Report</a>
    <a href="<?= $base ?>warnings.php"   class="mob-nav-item">&#x26A0; Fee Warnings</a>
    <div class="mob-section-label">Reports</div>
    <a href="<?= $base ?>student_report.php" class="mob-nav-item">&#x1F464; Student Report</a>
    <a href="<?= $base ?>print_report.php"   class="mob-nav-item">&#x1F5A8; Print Report</a>
    <div class="mob-section-label">Analytics</div>
    <a href="<?= $base ?>insights.php"  class="mob-nav-item">&#x1F4A1; Insights</a>
    <a href="<?= $base ?>activity.php"  class="mob-nav-item">&#x26A1; Activity Log</a>
    <div class="mob-section-label">Account</div>
    <a href="<?= $base ?>chatbot.php"   class="mob-nav-item">&#x1F916; AI Assistant</a>
    <a href="<?= $base ?>logout.php"    class="mob-nav-item" onclick="return confirm('Logout?')">&#x1F511; Logout</a>
</div>

<script>
function toggleDarkMode(){
    var h=document.documentElement;
    var isDark=h.getAttribute('data-theme')==='dark';
    h.setAttribute('data-theme',isDark?'light':'dark');
    localStorage.setItem('pptc_theme',isDark?'light':'dark');
}
function toggleNav(id){
    var item=document.getElementById(id);
    var isOpen=item.classList.contains('open');
    document.querySelectorAll('.nav-item.open').forEach(function(el){el.classList.remove('open');});
    if(!isOpen) item.classList.add('open');
}
document.addEventListener('click',function(e){
    if(!e.target.closest('.nav-item')){
        document.querySelectorAll('.nav-item.open').forEach(function(el){el.classList.remove('open');});
    }
});
document.addEventListener('keydown',function(e){
    if(e.key==='Escape'){
        document.querySelectorAll('.nav-item.open').forEach(function(el){el.classList.remove('open');});
        var mob=document.getElementById('mobileNavPanel');
        if(mob) mob.classList.remove('open');
    }
});
function toggleMobileNav(){
    document.getElementById('mobileNavPanel').classList.toggle('open');
}
</script>

<main class="main-content">

<?php if($cur_page!=='chatbot.php'): ?>
<style>
.cb-float-btn{position:fixed;bottom:1.75rem;right:1.75rem;z-index:9999;width:50px;height:50px;border-radius:50%;background:var(--crimson);color:#E8C76A;border:none;cursor:pointer;box-shadow:0 4px 18px rgba(139,0,0,.35);display:flex;align-items:center;justify-content:center;font-size:1.3rem;text-decoration:none;transition:all .22s;animation:cbFloatIn .5s .8s ease both;}
@keyframes cbFloatIn{from{opacity:0;transform:scale(.5) translateY(20px);}to{opacity:1;transform:scale(1) translateY(0);}}
.cb-float-btn:hover{transform:scale(1.1);box-shadow:0 8px 28px rgba(139,0,0,.45);color:#fff;}
.cb-float-badge{position:absolute;top:-2px;right:-2px;width:12px;height:12px;background:#22c55e;border-radius:50%;border:2px solid #fff;animation:cbBlink 2.5s ease-in-out infinite;}
@keyframes cbBlink{0%,100%{opacity:1;}50%{opacity:.3;}}
.cb-float-tooltip{position:absolute;right:58px;background:#111827;color:#e8c76a;padding:.3rem .65rem;border-radius:7px;font-size:.7rem;font-weight:700;white-space:nowrap;font-family:'Nunito',sans-serif;opacity:0;pointer-events:none;transition:opacity .18s;}
.cb-float-btn:hover .cb-float-tooltip{opacity:1;}
</style>
<a href="<?= $base ?>chatbot.php" class="cb-float-btn" title="AI Assistant">
    <span class="cb-float-badge"></span>
    <span class="cb-float-tooltip">AI Assistant</span>
    &#129302;
</a>
<?php endif; ?>
