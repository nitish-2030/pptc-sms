<?php
// ============================================================
// receipt.php — Professional Fee Receipt (v4 — Full Featured)
// ============================================================
require_once 'config/auth_check.php';
require_once 'config/db.php';

$receipt_no = trim($_GET['receipt'] ?? '');

$stmt = mysqli_prepare($conn,
    "SELECT p.*, s.name, s.roll_no, s.course, s.phone, s.email, s.address, s.category,
            s.guardian_name, s.guardian_phone, s.gender, s.dob,
            f.total_fee, f.paid_amount, f.due_amount, f.status AS fee_status
     FROM fee_payments p
     JOIN students s ON p.student_id = s.id
     LEFT JOIN fees f ON f.student_id = p.student_id
     WHERE p.receipt_no = ?"
);
mysqli_stmt_bind_param($stmt, 's', $receipt_no);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$row) { die('<div style="font-family:sans-serif;padding:2rem;color:#8B0000;">&#10060; Receipt not found.</div>'); }

// Academic year
$pay_year  = (int)date('Y', strtotime($row['payment_date']));
$pay_month = (int)date('n', strtotime($row['payment_date']));
$acad_year = $pay_month >= 7
    ? $pay_year . '–' . ($pay_year + 1)
    : ($pay_year - 1) . '–' . $pay_year;

// Fee calculations
$total_fee   = (float)$row['total_fee'];
$remaining   = (float)$row['due_amount'];
$this_payment = (float)$row['amount'];
$prev_due    = $remaining + $this_payment;
$paid_before = $total_fee - $prev_due;

$txn_id       = $row['receipt_no'];
$generated_at = date('d F Y, h:i A');
$pay_date_fmt = date('d F Y', strtotime($row['payment_date']));

// Number to words
function numberToWords(float $num): string {
    $num = (int)round($num);
    if ($num === 0) return 'Zero Rupees';
    $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
             'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    function conv(int $n, $o, $t): string {
        if ($n < 20)      return $o[$n];
        if ($n < 100)     return $t[(int)($n/10)] . ($n%10 ? ' '.$o[$n%10] : '');
        if ($n < 1000)    return $o[(int)($n/100)] . ' Hundred' . ($n%100 ? ' '.conv($n%100,$o,$t) : '');
        if ($n < 100000)  return conv((int)($n/1000),$o,$t) . ' Thousand' . ($n%1000 ? ' '.conv($n%1000,$o,$t) : '');
        if ($n < 10000000)return conv((int)($n/100000),$o,$t) . ' Lakh' . ($n%100000 ? ' '.conv($n%100000,$o,$t) : '');
        return conv((int)($n/10000000),$o,$t) . ' Crore' . ($n%10000000 ? ' '.conv($n%10000000,$o,$t) : '');
    }
    return conv($num, $ones, $tens) . ' Rupees';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt &mdash; <?= htmlspecialchars($row['receipt_no']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Nunito', sans-serif;
            background: #eae6de;
            padding: 2rem 1rem 3rem;
            min-height: 100vh;
        }

        /* ── Action buttons ── */
        .receipt-actions {
            display: flex;
            gap: 0.65rem;
            justify-content: center;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }
        .r-btn {
            padding: 0.55rem 1.4rem;
            border-radius: 50px;
            font-family: 'Nunito', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s;
        }
        .r-btn-print { background: #8B0000; color: #fff; box-shadow: 0 4px 14px rgba(139,0,0,0.3); }
        .r-btn-print:hover { background: #5C0000; transform: translateY(-1px); color: #fff; }
        .r-btn-back  { background: #fff; color: #555; border: 1.5px solid #ddd; }
        .r-btn-back:hover { background: #f0f0f0; color: #333; }

        /* ── Receipt card ── */
        .receipt-page { max-width: 720px; margin: 0 auto; }

        .receipt-card {
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 50px rgba(0,0,0,0.14);
            border: 1px solid #ddd5c8;
            position: relative;
        }

        /* ── HEADER with college.jpg background ── */
        .r-header {
            position: relative;
            min-height: 140px;
            overflow: hidden;
        }
        .r-header-bg {
            position: absolute;
            inset: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            object-position: center 30%;
            filter: brightness(0.28) saturate(0.6);
        }
        .r-header-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg,
                rgba(30,0,0,0.85) 0%,
                rgba(92,0,0,0.75) 50%,
                rgba(139,0,0,0.6) 100%);
        }
        .r-header-content {
            position: relative;
            z-index: 2;
            padding: 1.75rem 2rem 2.25rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }
        .r-logo {
            width: 80px; height: 80px;
            border-radius: 50%;
            border: 3px solid rgba(201,168,76,0.75);
            object-fit: contain;
            background: rgba(255,255,255,0.08);
            flex-shrink: 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        }
        .r-college-block { flex: 1; }
        .r-college-name {
            font-family: 'Cinzel', serif;
            font-size: 1.15rem;
            font-weight: 800;
            color: #E8C76A;
            letter-spacing: 0.04em;
            line-height: 1.25;
        }
        .r-college-sub {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.6);
            margin-top: 0.2rem;
            letter-spacing: 0.05em;
        }
        .r-college-contact {
            margin-top: 0.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem 1rem;
        }
        .r-contact-item {
            font-size: 0.7rem;
            color: rgba(255,255,255,0.55);
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .r-receipt-badge {
            position: absolute;
            top: 1.1rem; right: 1.25rem;
            background: rgba(201,168,76,0.18);
            border: 1px solid rgba(201,168,76,0.45);
            border-radius: 20px;
            padding: 0.28rem 0.9rem;
            font-size: 0.68rem;
            font-weight: 800;
            color: #E8C76A;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            z-index: 3;
        }

        /* ── Status strip ── */
        .r-status-strip {
            background: #f0fdf4;
            border-bottom: 2px solid #bbf7d0;
            padding: 0.7rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .r-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: #16a34a;
            color: #fff;
            padding: 0.32rem 1.1rem;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.07em;
            box-shadow: 0 3px 10px rgba(22,163,74,0.4);
        }
        .r-status-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #fff;
            animation: blink 1.3s ease-in-out infinite;
        }
        @keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.25;} }
        .r-txn-ref {
            font-size: 0.72rem;
            color: #444;
            font-weight: 700;
        }
        .r-txn-ref span { color: #16a34a; }

        /* ── Body ── */
        .r-body { padding: 1.75rem 2rem; }

        .r-section-title {
            font-family: 'Cinzel', serif;
            font-size: 0.72rem;
            font-weight: 700;
            color: #8B0000;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin: 1.25rem 0 0.75rem;
            padding-bottom: 0.4rem;
            border-bottom: 1.5px solid #f0e0e0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .r-section-title::before {
            content: '';
            display: inline-block;
            width: 3px; height: 14px;
            background: linear-gradient(#8B0000, #C9A84C);
            border-radius: 2px;
            flex-shrink: 0;
        }

        /* Info grid */
        .r-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.7rem 1.5rem;
        }
        .r-info-item {}
        .r-info-label {
            font-size: 0.67rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #999;
            margin-bottom: 0.15rem;
        }
        .r-info-value {
            font-size: 0.88rem;
            font-weight: 700;
            color: #1a0a0a;
        }

        /* ── Amount hero ── */
        .r-amount-hero {
            background: linear-gradient(135deg, #14532d 0%, #16a34a 50%, #22c55e 100%);
            border-radius: 14px;
            padding: 1.5rem;
            text-align: center;
            margin: 1.25rem 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 6px 24px rgba(22,163,74,0.35);
        }
        .r-amount-hero::before {
            content: '&#10003;';
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%,-50%);
            font-size: 8rem;
            color: rgba(255,255,255,0.04);
            font-weight: 900;
            pointer-events: none;
        }
        .r-amount-label {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: rgba(255,255,255,0.75);
            margin-bottom: 0.35rem;
        }
        .r-amount-value {
            font-size: 3rem;
            font-weight: 900;
            color: #fff;
            line-height: 1;
            text-shadow: 0 2px 12px rgba(0,0,0,0.2);
        }
        .r-amount-words {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.65);
            margin-top: 0.5rem;
            letter-spacing: 0.04em;
        }

        /* ── Fee summary table ── */
        .r-fee-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        .r-fee-table tr {
            border-bottom: 1px solid #f5ede0;
        }
        .r-fee-table tr:last-child { border-bottom: none; }
        .r-fee-table td {
            padding: 0.6rem 0.75rem;
            vertical-align: middle;
        }
        .r-fee-lbl {
            color: #666;
            font-size: 0.83rem;
        }
        .r-fee-table td:last-child {
            text-align: right;
            font-weight: 700;
            color: #1a0a0a;
        }
        .r-fee-val-green { color: #16a34a !important; }
        .r-fee-val-red   { color: #8B0000 !important; }
        .r-fee-total-row {
            background: #fdf6e3;
            border-radius: 8px;
        }
        .r-fee-total-row td {
            font-weight: 800 !important;
            font-size: 0.95rem;
            padding: 0.75rem;
        }

        /* ── Progress bar ── */
        .r-progress-wrap {
            margin: 0.75rem 0 0.25rem;
            padding: 0 0.75rem;
        }
        .r-progress-bar-bg {
            height: 8px;
            background: #f0e8e8;
            border-radius: 20px;
            overflow: hidden;
        }
        .r-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #16a34a, #22c55e);
            border-radius: 20px;
            transition: width 0.8s ease;
        }
        .r-progress-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.65rem;
            color: #999;
            margin-top: 0.3rem;
        }

        /* ── Footer ── */
        .r-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 1.25rem;
            margin-top: 1.25rem;
            border-top: 1px dashed #e0cece;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .r-generated {
            font-size: 0.7rem;
            color: #aaa;
            line-height: 1.7;
        }
        .r-generated strong { color: #777; }
        .r-sig {
            text-align: right;
        }
        .r-sig-line {
            border-top: 1.5px solid #ccc;
            padding-top: 0.3rem;
            font-size: 0.72rem;
            color: #888;
            font-weight: 700;
            letter-spacing: 0.06em;
            min-width: 140px;
        }

        /* ── Watermark ── */
        .r-watermark {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%,-50%) rotate(-35deg);
            font-family: 'Cinzel', serif;
            font-size: 5.5rem;
            font-weight: 800;
            color: rgba(34,197,94,0.045);
            white-space: nowrap;
            pointer-events: none;
            user-select: none;
            letter-spacing: 0.2em;
            z-index: 0;
        }

        /* ── QR-style decorative corner ── */
        .r-corner {
            position: absolute;
            bottom: 1rem; right: 1rem;
            width: 44px; height: 44px;
            border: 2px solid rgba(139,0,0,0.1);
            border-radius: 6px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 3px;
            padding: 5px;
            opacity: 0.4;
        }
        .r-corner div {
            background: #8B0000;
            border-radius: 2px;
        }

        /* ── Print ── */
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-actions { display: none !important; }
            .receipt-card { box-shadow: none; border: none; border-radius: 0; }
            .r-header-bg { filter: brightness(0.32) saturate(0.5); }
        }

        @media (max-width: 540px) {
            .r-info-grid { grid-template-columns: 1fr; }
            .r-body { padding: 1.25rem; }
            .r-header-content { padding: 1.25rem 1.25rem 1.75rem; }
            .r-amount-value { font-size: 2.2rem; }
        }
    </style>
</head>
<body>

<div class="receipt-page">

    <!-- ── Action buttons ── -->
    <div class="receipt-actions">
        <button onclick="window.print()" class="r-btn r-btn-print">&#128424; Print Receipt</button>
        <a href="fees.php?roll_no=<?= urlencode($row['roll_no']) ?>" class="r-btn r-btn-back">&#8592; Back to Fees</a>
        <a href="view.php?roll_no=<?= urlencode($row['roll_no']) ?>" class="r-btn r-btn-back">&#128065; Student Profile</a>
        <a href="dashboard.php" class="r-btn r-btn-back">&#127968; Dashboard</a>
    </div>

    <div class="receipt-card">
        <div class="r-watermark">PAID</div>

        <!-- ── HEADER ── -->
        <div class="r-header">
            <img src="assets/img/college.jpg" alt="College" class="r-header-bg">
            <div class="r-header-overlay"></div>
            <div class="r-receipt-badge">Fee Receipt</div>
            <div class="r-header-content">
                <img src="assets/img/pptc_logo.png" alt="PPTC Logo" class="r-logo">
                <div class="r-college-block">
                    <div class="r-college-name">Pentium Point Group of Institutions</div>
                    <div class="r-college-sub">A Unit of Shiv Computer Institute Society &bull; Rewa, Madhya Pradesh</div>
                    <div class="r-college-contact">
                        <span class="r-contact-item">&#128205; Station Road, Rewa, M.P. &ndash; 486001</span>
                        <span class="r-contact-item">&#128222; 07662-438035</span>
                        <span class="r-contact-item">&#9993; pptcrewa@rediffmail.com</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── STATUS STRIP ── -->
        <div class="r-status-strip">
            <div class="r-status-badge">
                <div class="r-status-dot"></div>
                &#10003; PAYMENT SUCCESSFUL
            </div>
            <div class="r-txn-ref">
                Transaction ID: <span><?= htmlspecialchars($txn_id) ?></span>
            </div>
        </div>

        <!-- ── BODY ── -->
        <div class="r-body" style="position:relative;z-index:1;">

            <!-- Student Details -->
            <div class="r-section-title">Student Details</div>
            <div class="r-info-grid">
                <div class="r-info-item">
                    <div class="r-info-label">Student Name</div>
                    <div class="r-info-value"><?= htmlspecialchars($row['name']) ?></div>
                </div>
                <div class="r-info-item">
                    <div class="r-info-label">Roll Number</div>
                    <div class="r-info-value" style="color:#8B0000;"><?= htmlspecialchars($row['roll_no']) ?></div>
                </div>
                <div class="r-info-item">
                    <div class="r-info-label">Course</div>
                    <div class="r-info-value"><?= htmlspecialchars($row['course']) ?></div>
                </div>
                <div class="r-info-item">
                    <div class="r-info-label">Academic Year</div>
                    <div class="r-info-value"><?= $acad_year ?></div>
                </div>
                <div class="r-info-item">
                    <div class="r-info-label">Category</div>
                    <div class="r-info-value"><?= htmlspecialchars($row['category'] ?? 'General') ?></div>
                </div>
                <?php if (!empty($row['phone'])): ?>
                <div class="r-info-item">
                    <div class="r-info-label">Phone</div>
                    <div class="r-info-value"><?= htmlspecialchars($row['phone']) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Payment Details -->
            <div class="r-section-title">Payment Details</div>
            <div class="r-info-grid">
                <div class="r-info-item">
                    <div class="r-info-label">Receipt No</div>
                    <div class="r-info-value" style="color:#8B0000;"><?= htmlspecialchars($row['receipt_no']) ?></div>
                </div>
                <div class="r-info-item">
                    <div class="r-info-label">Payment Date</div>
                    <div class="r-info-value"><?= $pay_date_fmt ?></div>
                </div>
                <div class="r-info-item">
                    <div class="r-info-label">Payment Mode</div>
                    <div class="r-info-value"><?= htmlspecialchars($row['payment_mode']) ?></div>
                </div>
                <div class="r-info-item">
                    <div class="r-info-label">Fee Status After Payment</div>
                    <div class="r-info-value">
                        <?php
                        $fs = $row['fee_status'] ?? 'Partial';
                        $fc = match($fs) { 'Paid' => '#16a34a', 'Partial' => '#b45309', default => '#8B0000' };
                        ?>
                        <span style="color:<?= $fc ?>;font-weight:800;"><?= $fs ?></span>
                    </div>
                </div>
            </div>

            <!-- Amount Paid Hero -->
            <div class="r-amount-hero">
                <div class="r-amount-label">&#10003;&nbsp; Amount Paid This Transaction</div>
                <div class="r-amount-value">&#8377;<?= number_format($this_payment, 2) ?></div>
                <div class="r-amount-words"><?= strtoupper(numberToWords($this_payment)) ?> ONLY</div>
            </div>

            <!-- Fee Breakdown -->
            <div class="r-section-title">Fee Summary</div>
            <table class="r-fee-table">
                <tr>
                    <td class="r-fee-lbl">Total Course Fee</td>
                    <td>&#8377;<?= number_format($total_fee, 2) ?></td>
                </tr>
                <tr>
                    <td class="r-fee-lbl">Previously Paid (Before This)</td>
                    <td class="r-fee-val-green">&#8377;<?= number_format($paid_before, 2) ?></td>
                </tr>
                <tr>
                    <td class="r-fee-lbl">Previous Outstanding Due</td>
                    <td class="r-fee-val-red">&#8377;<?= number_format($prev_due, 2) ?></td>
                </tr>
                <tr>
                    <td class="r-fee-lbl">&#10003; This Payment</td>
                    <td class="r-fee-val-green">&#8377;<?= number_format($this_payment, 2) ?></td>
                </tr>
                <tr class="r-fee-total-row">
                    <td style="color:#333;font-weight:800;">Remaining Balance</td>
                    <td style="color:<?= $remaining > 0 ? '#8B0000' : '#16a34a' ?>;">
                        &#8377;<?= number_format($remaining, 2) ?>
                        <?= $remaining <= 0 ? '&nbsp;&#10003; CLEARED' : '' ?>
                    </td>
                </tr>
            </table>

            <!-- Progress bar -->
            <?php $pct = $total_fee > 0 ? min(100, round((($total_fee - $remaining) / $total_fee) * 100)) : 100; ?>
            <div class="r-progress-wrap">
                <div class="r-progress-bar-bg">
                    <div class="r-progress-bar" style="width:<?= $pct ?>%;"></div>
                </div>
                <div class="r-progress-labels">
                    <span>0%</span>
                    <span><?= $pct ?>% collected</span>
                    <span>100%</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="r-footer">
                <div class="r-generated">
                    <strong>Generated On:</strong> <?= $generated_at ?><br>
                    <span style="font-size:0.62rem;color:#bbb;">This is a computer generated receipt. No signature required.</span>
                </div>
                <div class="r-sig">
                    <div style="height:28px;"></div>
                    <div class="r-sig-line">Authorized Signatory</div>
                    <div style="font-size:0.62rem;color:#aaa;margin-top:0.2rem;">Accounts Dept &bull; PPTC Rewa</div>
                </div>
            </div>

        </div><!-- /r-body -->

        <!-- decorative corner dots -->
        <div class="r-corner">
            <?php for($i=0;$i<9;$i++) echo '<div></div>'; ?>
        </div>

    </div><!-- /receipt-card -->
</div>

</body>
</html>
