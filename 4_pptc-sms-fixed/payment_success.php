<?php
// ============================================================
// payment_success.php — PhonePe-style Payment Success Animation
// ============================================================
require_once 'config/auth_check.php';

$receipt = trim($_GET['receipt'] ?? '');
$amount  = (float)($_GET['amount'] ?? 0);
$name    = htmlspecialchars(trim($_GET['name'] ?? 'Student'), ENT_QUOTES);

if (!$receipt) { header('Location: dashboard.php'); exit; }

$redirect_url = 'receipt.php?receipt=' . urlencode($receipt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #0a2e0a 0%, #0d3d0d 40%, #1a5c1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* ── Ripple background ── */
        .ripple-bg {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .ripple {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%) scale(0);
            border-radius: 50%;
            background: rgba(34, 197, 94, 0.08);
            animation: rippleOut 3s ease-out infinite;
        }
        .ripple:nth-child(1) { width: 200px; height: 200px; animation-delay: 0s; }
        .ripple:nth-child(2) { width: 400px; height: 400px; animation-delay: 0.5s; }
        .ripple:nth-child(3) { width: 650px; height: 650px; animation-delay: 1s; }
        .ripple:nth-child(4) { width: 900px; height: 900px; animation-delay: 1.5s; }
        @keyframes rippleOut {
            0%   { transform: translate(-50%,-50%) scale(0); opacity: 1; }
            100% { transform: translate(-50%,-50%) scale(1); opacity: 0; }
        }

        /* ── Main card ── */
        .success-card {
            position: relative;
            z-index: 10;
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(34,197,94,0.25);
            border-radius: 28px;
            padding: 3rem 3.5rem;
            text-align: center;
            max-width: 420px;
            width: 90%;
            animation: cardIn 0.6s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: scale(0.7) translateY(40px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* ── Circle ── */
        .circle-wrap {
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
            position: relative;
        }
        .circle-bg {
            width: 100%; height: 100%;
            border-radius: 50%;
            background: rgba(34,197,94,0.15);
            border: 3px solid rgba(34,197,94,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: circlePop 0.5s 0.3s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        @keyframes circlePop {
            from { transform: scale(0); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }

        /* SVG checkmark */
        .checkmark-svg {
            width: 64px; height: 64px;
        }
        .checkmark-path {
            fill: none;
            stroke: #22c55e;
            stroke-width: 5;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 80;
            stroke-dashoffset: 80;
            animation: drawCheck 0.5s 0.7s ease forwards;
        }
        @keyframes drawCheck {
            to { stroke-dashoffset: 0; }
        }

        /* Spinning ring */
        .spin-ring {
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            border: 3px solid transparent;
            border-top-color: #22c55e;
            border-right-color: #4ade80;
            animation: spin 1.5s 0.3s linear forwards, fadeRing 0.4s 1.8s ease forwards;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes fadeRing { to { opacity: 0; } }

        /* ── Text ── */
        .success-title {
            font-size: 1.65rem;
            font-weight: 900;
            color: #fff;
            margin-bottom: 0.35rem;
            animation: fadeUp 0.5s 0.8s ease both;
        }
        .success-name {
            font-size: 0.95rem;
            color: rgba(255,255,255,0.65);
            margin-bottom: 1.5rem;
            animation: fadeUp 0.5s 0.9s ease both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Amount box ── */
        .amount-box {
            background: rgba(34,197,94,0.18);
            border: 1.5px solid rgba(34,197,94,0.4);
            border-radius: 16px;
            padding: 1.1rem 1.5rem;
            margin-bottom: 1.5rem;
            animation: fadeUp 0.5s 1s ease both;
        }
        .amount-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #4ade80;
            margin-bottom: 0.3rem;
        }
        .amount-value {
            font-size: 2.4rem;
            font-weight: 900;
            color: #fff;
            line-height: 1;
        }

        /* ── Receipt ID ── */
        .receipt-ref {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.4);
            margin-bottom: 1.75rem;
            animation: fadeUp 0.5s 1.1s ease both;
        }
        .receipt-ref span { color: rgba(74,222,128,0.8); font-weight: 700; }

        /* ── Timer bar ── */
        .timer-wrap {
            animation: fadeUp 0.5s 1.2s ease both;
        }
        .timer-label {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.45);
            margin-bottom: 0.5rem;
        }
        .timer-bar-bg {
            height: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .timer-bar {
            height: 100%;
            width: 100%;
            background: linear-gradient(90deg, #22c55e, #4ade80);
            border-radius: 10px;
            animation: drain 3s 1s linear forwards;
            transform-origin: left;
        }
        @keyframes drain {
            from { width: 100%; }
            to   { width: 0%; }
        }

        /* ── Button ── */
        .view-btn {
            display: inline-block;
            padding: 0.7rem 2rem;
            background: #22c55e;
            color: #fff;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 4px 20px rgba(34,197,94,0.4);
            animation: fadeUp 0.5s 1.3s ease both;
        }
        .view-btn:hover {
            background: #16a34a;
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(34,197,94,0.5);
            color: #fff;
        }

        /* Confetti particles */
        .confetti-wrap { position: fixed; inset: 0; pointer-events: none; z-index: 5; }
        .conf {
            position: absolute;
            top: -10px;
            width: 8px; height: 8px;
            border-radius: 2px;
            animation: confettiFall linear forwards;
            opacity: 0.8;
        }
        @keyframes confettiFall {
            0%   { transform: translateY(0) rotate(0deg); opacity: 1; }
            100% { transform: translateY(110vh) rotate(720deg); opacity: 0; }
        }
    </style>
</head>
<body>

<!-- Ripple background -->
<div class="ripple-bg">
    <div class="ripple"></div>
    <div class="ripple"></div>
    <div class="ripple"></div>
    <div class="ripple"></div>
</div>

<!-- Confetti -->
<div class="confetti-wrap" id="confettiWrap"></div>

<!-- Main card -->
<div class="success-card">

    <!-- Circle checkmark -->
    <div class="circle-wrap">
        <div class="spin-ring"></div>
        <div class="circle-bg">
            <svg class="checkmark-svg" viewBox="0 0 64 64">
                <path class="checkmark-path" d="M14 33 L27 46 L50 20"/>
            </svg>
        </div>
    </div>

    <div class="success-title">Payment Successful!</div>
    <div class="success-name">Hello, <?= $name ?> &#128075;</div>

    <!-- Amount -->
    <div class="amount-box">
        <div class="amount-label">&#10003; Amount Paid</div>
        <div class="amount-value">&#8377;<?= number_format($amount, 2) ?></div>
    </div>

    <!-- Receipt ref -->
    <div class="receipt-ref">
        Transaction Ref: <span><?= htmlspecialchars($receipt) ?></span>
    </div>

    <!-- Timer -->
    <div class="timer-wrap">
        <div class="timer-label">Redirecting to receipt in 3 seconds&hellip;</div>
        <div class="timer-bar-bg">
            <div class="timer-bar"></div>
        </div>
    </div>

    <a href="<?= $redirect_url ?>" class="view-btn">&#128424; View Receipt Now</a>

</div>

<script>
// Auto redirect after 3 seconds (+ 1s animation delay = 4s total)
setTimeout(function() {
    window.location.href = <?= json_encode($redirect_url) ?>;
}, 4000);

// Generate confetti
const wrap  = document.getElementById('confettiWrap');
const colors = ['#22c55e','#4ade80','#86efac','#fbbf24','#f9fafb','#6ee7b7'];
const count  = 60;
for (let i = 0; i < count; i++) {
    const el = document.createElement('div');
    el.className = 'conf';
    el.style.left     = Math.random() * 100 + 'vw';
    el.style.background = colors[Math.floor(Math.random() * colors.length)];
    el.style.width    = (6 + Math.random() * 8) + 'px';
    el.style.height   = (6 + Math.random() * 8) + 'px';
    el.style.animationDuration  = (2 + Math.random() * 2.5) + 's';
    el.style.animationDelay     = (Math.random() * 1.5) + 's';
    el.style.borderRadius       = Math.random() > 0.5 ? '50%' : '2px';
    wrap.appendChild(el);
}
</script>
</body>
</html>
