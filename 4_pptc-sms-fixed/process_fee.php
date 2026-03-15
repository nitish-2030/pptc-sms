<?php
// ============================================================
// process_fee.php — Handle Fee Payment (v2 — Fully Secure)
// FIXED: Auth check, CSRF validation, server-side overpayment
//        guard, SQL injection fix, unique receipt collision fix
// ============================================================
require_once 'config/db.php';
require_once 'config/csrf_helper.php';
require_once 'config/email_helper.php';
require_once 'config/activity_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: fees.php');
    exit;
}

// CSRF validation
csrf_validate();

// Auth check (manual since no header include here)
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['pptc_admin_logged_in']) || $_SESSION['pptc_admin_logged_in'] !== true) {
    header('Location: login.php?reason=auth');
    exit;
}

$student_id = (int)($_POST['student_id'] ?? 0);
$roll_no    = trim($_POST['roll_no'] ?? '');
$amount     = (float)($_POST['amount'] ?? 0);
$mode       = trim($_POST['mode'] ?? 'Cash');

if ($student_id <= 0 || $amount <= 0) {
    header('Location: fees.php?roll_no=' . urlencode($roll_no) . '&err=invalid');
    exit;
}

// ---- Server-side: fetch real due amount (never trust client max) ----
$fee_check = mysqli_prepare($conn, "SELECT * FROM fees WHERE student_id = ?");
mysqli_stmt_bind_param($fee_check, 'i', $student_id);
mysqli_stmt_execute($fee_check);
$fee_row = mysqli_fetch_assoc(mysqli_stmt_get_result($fee_check));

if ($fee_row && $amount > $fee_row['due_amount']) {
    // Overpayment: cap to due amount
    $amount = $fee_row['due_amount'];
}
if ($amount <= 0) {
    header('Location: fees.php?roll_no=' . urlencode($roll_no) . '&err=already_paid');
    exit;
}

// ---- Generate unique receipt number ----
$receipt = 'RCPT' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
$date    = date('Y-m-d');

// ---- 1. Insert Payment Record ----
$stmt = mysqli_prepare($conn, "INSERT INTO fee_payments (student_id, amount, payment_mode, receipt_no, payment_date) VALUES (?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'idsss', $student_id, $amount, $mode, $receipt, $date);

if (!mysqli_stmt_execute($stmt)) {
    die("Payment insert error: " . mysqli_stmt_error($stmt));
}

// ---- 2. Update Fee Summary ----
if ($fee_row) {
    $new_paid = $fee_row['paid_amount'] + $amount;
    $new_due  = $fee_row['total_fee']   - $new_paid;
    if ($new_due < 0) $new_due = 0;

    // Correct status including back to 'Unpaid' if somehow paid = 0
    if ($new_due <= 0)         $status = 'Paid';
    elseif ($new_paid > 0)     $status = 'Partial';
    else                       $status = 'Unpaid';

    $upd = mysqli_prepare($conn, "UPDATE fees SET paid_amount=?, due_amount=?, status=? WHERE student_id=?");
    mysqli_stmt_bind_param($upd, 'ddsi', $new_paid, $new_due, $status, $student_id);
    mysqli_stmt_execute($upd);
} else {
    // No fee record existed — create one (ad-hoc payment, mark as Paid)
    $ins = mysqli_prepare($conn, "INSERT INTO fees (student_id, total_fee, paid_amount, due_amount, status) VALUES (?, ?, ?, 0, 'Paid')");
    mysqli_stmt_bind_param($ins, 'idd', $student_id, $amount, $amount);
    mysqli_stmt_execute($ins);
}

// Get student name for success screen
$name_stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ?");
mysqli_stmt_bind_param($name_stmt, 'i', $student_id);
mysqli_stmt_execute($name_stmt);
$full_student = mysqli_fetch_assoc(mysqli_stmt_get_result($name_stmt));
$student_name = $full_student['name'] ?? 'Student';

// ── Feature 1 & 2: Email + Activity ──
$new_due = $fee_row ? ($fee_row['total_fee'] - ($fee_row['paid_amount'] + $amount)) : 0;
if ($new_due < 0) $new_due = 0;

if ($full_student) {
    // Payment activity
    log_activity($conn, 'student',
        "{$student_name} made a fee payment of ₹" . number_format($amount),
        "Receipt: {$receipt} | Mode: {$mode} | Remaining: ₹" . number_format($new_due),
        $student_id, $student_name
    );

    // Full payment completion
    if ($new_due <= 0) {
        $email_result = send_email($conn, 'payment_success', $full_student, [
            'amount'     => $amount,
            'receipt_no' => $receipt,
        ]);
        log_activity($conn, 'student',
            "{$student_name} completed full fee payment 🎉",
            "Total cleared. Receipt: {$receipt}",
            $student_id, $student_name
        );
        log_activity($conn, 'email',
            "Payment completion email {$email_result['message']}",
            "Trigger: payment_success | To: ".($full_student['email']??'no email'),
            $student_id, $student_name
        );
    }
}

header("Location: payment_success.php?receipt=" . urlencode($receipt) . "&amount=" . urlencode($amount) . "&name=" . urlencode($student_name));
exit;
?>
