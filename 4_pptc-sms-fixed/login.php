<?php
// ============================================================
// login.php — Admin Login Page (Secure v2)
// FIXED: Password now uses password_hash/password_verify
// FIXED: CSRF token protection added
// ============================================================
session_start();

if (isset($_SESSION['pptc_admin_logged_in']) && $_SESSION['pptc_admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// ---- IMPORTANT: To change password, run this once in PHP:
//   echo password_hash('your_new_password', PASSWORD_DEFAULT);
// Then paste the hash below.
// Default password is: pptc@2024
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$10$sKz17YQOeotTj3tDvZrqOee57QS5q0i5JSItFPv1U1pWwlKNcFzXW'); // pptc@2024
define('ADMIN_EMAIL',    'pptcrewa@rediffmail.com');

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $user_match = ($username === ADMIN_USERNAME || $username === ADMIN_EMAIL);
        $pass_match = password_verify($password, ADMIN_PASSWORD_HASH);

        if ($user_match && $pass_match) {
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['pptc_admin_logged_in'] = true;
            $_SESSION['pptc_admin_user']      = ADMIN_USERNAME;
            $_SESSION['pptc_login_time']      = time();
            // Regenerate CSRF token after login
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            // Feature 2: Log admin login
            require_once 'config/db.php';
            require_once 'config/activity_helper.php';
            log_activity($conn, 'admin', 'Admin logged in', 'IP: '.($_SERVER['REMOTE_ADDR']??'unknown'));
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — PPTC Student Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="assets/img/pptc_logo.png" type="image/png">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --crimson:    #8B0000;
            --crimson-lt: #B22222;
            --crimson-dk: #5C0000;
            --gold:       #C9A84C;
            --gold-lt:    #E8C76A;
            --gold-dk:    #A07830;
            --cream:      #FDF6E3;
            --white:      #FFFFFF;
            --text-dark:  #1A0A0A;
            --text-light: #8B6060;
        }
        body { font-family: 'Nunito', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #0d0003; overflow: hidden; position: relative; }
        .login-bg { position: fixed; inset: 0; background: url('assets/img/college.jpg') center/cover no-repeat; filter: brightness(0.15) saturate(0.4); z-index: 0; transform: scale(1.05); animation: slowZoom 20s ease-in-out alternate infinite; }
        @keyframes slowZoom { from { transform: scale(1.05); } to { transform: scale(1.14); } }
        .login-overlay { position: fixed; inset: 0; background: radial-gradient(ellipse at center, transparent 10%, rgba(92,0,0,0.75) 100%); z-index: 1; }
        .particles { position: fixed; inset: 0; z-index: 2; pointer-events: none; }
        .particle { position: absolute; border-radius: 50%; opacity: 0; animation: floatUp var(--dur) ease-in var(--delay) infinite; }
        @keyframes floatUp { 0% { opacity: 0; transform: translateY(0) scale(0.5); } 20% { opacity: 0.5; } 80% { opacity: 0.2; } 100% { opacity: 0; transform: translateY(-100px) scale(1.2); } }
        .login-wrap { position: relative; z-index: 10; width: 100%; max-width: 440px; padding: 1rem; animation: cardEntry 0.8s cubic-bezier(0.34,1.56,0.64,1) both; }
        @keyframes cardEntry { from { opacity: 0; transform: translateY(40px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .login-card { background: rgba(255,255,255,0.07); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(201,168,76,0.25); border-radius: 20px; padding: 2.5rem 2rem; box-shadow: 0 24px 80px rgba(0,0,0,0.6); }
        .login-logo-wrap { text-align: center; margin-bottom: 1.75rem; position: relative; }
        .login-logo { width: 90px; height: 90px; object-fit: contain; animation: logoFloat 4s ease-in-out infinite; filter: drop-shadow(0 4px 16px rgba(201,168,76,0.5)); }
        @keyframes logoFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .logo-ring { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -60%); width: 106px; height: 106px; border-radius: 50%; border: 1.5px solid var(--gold); opacity: 0; animation: ringPulse 2.5s ease-out infinite; }
        .logo-ring:nth-child(2) { animation-delay: 1.2s; }
        @keyframes ringPulse { 0% { opacity: 0.7; transform: translate(-50%,-60%) scale(1); } 100% { opacity: 0; transform: translate(-50%,-60%) scale(1.9); } }
        .login-college-name { font-family: 'Cinzel', serif; font-size: 1rem; font-weight: 700; color: var(--gold-lt); margin-top: 0.75rem; letter-spacing: 0.04em; }
        .login-college-sub { font-size: 0.72rem; color: rgba(255,255,255,0.5); letter-spacing: 0.08em; text-transform: uppercase; margin-top: 0.2rem; }
        .login-divider { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem; }
        .login-divider::before, .login-divider::after { content: ''; flex: 1; height: 1px; background: rgba(201,168,76,0.25); }
        .login-divider span { font-size: 0.7rem; font-weight: 700; color: var(--gold); text-transform: uppercase; letter-spacing: 0.15em; white-space: nowrap; }
        .form-group { margin-bottom: 1.1rem; }
        .form-group label { display: block; font-size: 0.75rem; font-weight: 700; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.4rem; }
        .input-wrap { position: relative; }
        .input-icon { position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); font-size: 1rem; pointer-events: none; }
        .form-control { width: 100%; padding: 0.8rem 1rem 0.8rem 2.6rem; background: rgba(255,255,255,0.08); border: 1.5px solid rgba(201,168,76,0.25); border-radius: 10px; color: var(--white); font-family: 'Nunito', sans-serif; font-size: 0.95rem; outline: none; transition: border-color 0.3s, background 0.3s, box-shadow 0.3s; }
        .form-control::placeholder { color: rgba(255,255,255,0.3); }
        .form-control:focus { border-color: var(--gold); background: rgba(255,255,255,0.12); box-shadow: 0 0 0 3px rgba(201,168,76,0.15); }
        .toggle-pass { position: absolute; right: 0.9rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: rgba(255,255,255,0.4); cursor: pointer; font-size: 1rem; transition: color 0.3s; padding: 0; }
        .toggle-pass:hover { color: var(--gold); }
        .alert-error { background: rgba(178,34,34,0.2); border: 1px solid rgba(178,34,34,0.5); border-radius: 10px; padding: 0.75rem 1rem; font-size: 0.85rem; color: #ffaaaa; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; animation: shake 0.4s ease; }
        .alert-session { background: rgba(201,168,76,0.15); border: 1px solid rgba(201,168,76,0.4); border-radius: 10px; padding: 0.6rem 1rem; font-size: 0.82rem; color: var(--gold-lt); margin-bottom: 1rem; }
        @keyframes shake { 0%,100% { transform: translateX(0); } 20% { transform: translateX(-8px); } 40% { transform: translateX(8px); } 60% { transform: translateX(-5px); } 80% { transform: translateX(5px); } }
        .btn-login { width: 100%; padding: 0.9rem; background: linear-gradient(135deg, var(--gold-dk), var(--gold-lt)); color: var(--crimson-dk); font-family: 'Cinzel', serif; font-size: 1rem; font-weight: 700; letter-spacing: 0.08em; border: none; border-radius: 50px; cursor: pointer; transition: all 0.35s; box-shadow: 0 4px 20px rgba(201,168,76,0.4); margin-top: 0.5rem; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(201,168,76,0.6); }
        .btn-login:active { transform: translateY(0); }
        .login-note { text-align: center; margin-top: 1.5rem; font-size: 0.72rem; color: rgba(255,255,255,0.3); line-height: 1.7; }
        @media (max-width: 480px) { .login-card { padding: 2rem 1.25rem; } .login-logo { width: 72px; height: 72px; } }
    </style>
</head>
<body>
<div class="login-bg"></div>
<div class="login-overlay"></div>
<div class="particles" id="particles"></div>

<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo-wrap">
            <div class="logo-ring"></div>
            <div class="logo-ring"></div>
            <img src="assets/img/pptc_group_logo.png" alt="PPTC Logo" class="login-logo">
            <div class="login-college-name">Pentium Point Group of Institutions</div>
            <div class="login-college-sub">Student Management System &bull; Rewa (M.P.)</div>
        </div>

        <div class="login-divider"><span>Admin Login</span></div>

        <?php if (isset($_GET['reason']) && $_GET['reason'] === 'auth'): ?>
        <div class="alert-session">⏰ Session expire ho gayi. Dobara login karein.</div>
        <?php endif; ?>
        <?php if (isset($_GET['logout']) && $_GET['logout'] == '1'): ?>
        <div class="alert-session">✅ Successfully logout ho gaye.</div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="form-group">
                <label for="username">Username or Email</label>
                <div class="input-wrap">
                    <span class="input-icon">👤</span>
                    <input type="text" id="username" name="username" class="form-control"
                           placeholder="Enter username or email"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           autocomplete="username" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">🔒</span>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Enter password"
                           autocomplete="current-password" required>
                    <button type="button" class="toggle-pass" id="togglePass" title="Show/Hide password">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn-login">🔐 &nbsp; Login to Dashboard</button>
        </form>

        <div class="login-note">
            Pentium Point Group of Institutions, Rewa (M.P.)<br>
            A Unit of Shiv Computer Institute Society<br>
            &copy; <?= date('Y') ?> — All Rights Reserved
        </div>
    </div>
</div>

<script>
(function() {
    const c = document.getElementById('particles');
    const colors = ['#C9A84C','#E8C76A','rgba(255,255,255,0.5)'];
    for (let i = 0; i < 25; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const size = Math.random() * 4 + 2;
        p.style.cssText = `width:${size}px;height:${size}px;left:${Math.random()*100}%;top:${50+Math.random()*50}%;background:${colors[Math.floor(Math.random()*colors.length)]};--dur:${3+Math.random()*5}s;--delay:${Math.random()*6}s;`;
        c.appendChild(p);
    }
})();
document.getElementById('togglePass').addEventListener('click', function() {
    const pwd = document.getElementById('password');
    pwd.type = pwd.type === 'password' ? 'text' : 'password';
    this.textContent = pwd.type === 'password' ? '👁️' : '🙈';
});
</script>
</body>
</html>
