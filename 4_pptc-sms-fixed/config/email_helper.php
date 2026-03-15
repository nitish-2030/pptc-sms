<?php
// ============================================================
// config/email_helper.php - Email Automation System v5
// PHPMailer + Gmail SMTP - localhost + InfinityFree dono pe kaam karta hai
// ============================================================

// == APNA GMAIL APP PASSWORD YAHAN DAALO ==
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_SECURE',   'tls');
define('SMTP_USERNAME', 'nittyish3409@gmail.com');
define('SMTP_PASSWORD', 'aqms prmh fvyo egqa'); // <-- sirf yeh badlo

define('EMAIL_FROM',      'nittyish3409@gmail.com');
define('EMAIL_FROM_NAME', 'Pentium Point Technical College');
define('EMAIL_REPLY_TO',  'nittyish3409@gmail.com');
define('WARNING_THRESHOLD', 15000);

// ── Course fees map ─────────────────────────────────────────
function get_course_fee_map($conn): array {
    $map = [];
    $res = @mysqli_query($conn, "SELECT course_code, annual_fee FROM course_fees");
    if ($res) while ($r = mysqli_fetch_assoc($res)) $map[$r['course_code']] = (float)$r['annual_fee'];
    // Fallback defaults if table not yet created
    if (empty($map)) {
        $map = [
            'BCA'=>48000,'BBA'=>42000,'MBA'=>65000,'BCOM'=>26000,'BCOM_CA'=>29000,
            'BSC_PCM'=>28000,'BSC_CBZ'=>27500,'BSC_BTC'=>32000,'BSC_BT'=>32000,
            'BALLB'=>55000,'LLB'=>45000,'LLM'=>38000,'BBA_LLB'=>58000,'BCOM_LLB'=>55000,
            'D_PHARMA'=>52000,'B_PHARMA'=>68000,'PGDCA'=>26000,
            'BA'=>25500,'BA_CA'=>28000,'BSC_PSM'=>27000,'BSC_PMCs'=>27000,
            'MSW'=>35000,'MA_HIS'=>27000,'MA_ECO'=>27000,
        ];
    }
    return $map;
}

// ── Main send function ───────────────────────────────────────
/**
 * Send an email and log it.
 *
 * @param $conn      mysqli connection
 * @param string $trigger_type  welcome|updated|deactivated|payment_success|warning
 * @param array  $student       Full student row (must have: id, name, email, roll_no, course)
 * @param array  $extra         Additional data: amount, due_amount, receipt_no, etc.
 * @return array ['success'=>bool, 'message'=>string]
 */
function send_email($conn, string $trigger_type, array $student, array $extra = []): array
{
    $to_email     = trim($student['email'] ?? '');
    $student_name = $student['name']    ?? 'Student';
    $student_id   = (int)($student['id'] ?? 0);
    $roll_no      = $student['roll_no'] ?? '';
    $course       = $student['course']  ?? '';

    // ── No email on file ──
    if (empty($to_email)) {
        log_email($conn, $student_id, $student_name, '', $trigger_type,
            'Email not registered', 'no_email', 'Student has no email address on file');
        return ['success' => false, 'message' => 'Email not registered for this student.'];
    }

    // ── Build email content per trigger ──
    [$subject, $html_body] = build_email_content($trigger_type, $student, $extra, $conn);

    // -- Send via PHPMailer + Gmail SMTP --
    $phpmailer_path = __DIR__ . '/phpmailer/';
    if (!file_exists($phpmailer_path . 'PHPMailer.php')) {
        // PHPMailer not installed - fallback log
        log_email($conn, $student_id, $student_name, $to_email, $trigger_type,
            'PHPMailer missing', 'failed', 'Put PHPMailer files in config/phpmailer/ folder');
        return ['success' => false, 'message' => 'PHPMailer not found in config/phpmailer/'];
    }

    require_once $phpmailer_path . 'Exception.php';
    require_once $phpmailer_path . 'PHPMailer.php';
    require_once $phpmailer_path . 'SMTP.php';

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addReplyTo(EMAIL_REPLY_TO);
        $mail->addAddress($to_email, $student_name);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;

        $mail->send();

        log_email($conn, $student_id, $student_name, $to_email, $trigger_type,
            $subject, 'sent', '');
        return ['success' => true, 'message' => "Email sent to $to_email"];

    } catch (Exception $e) {
        $err = $mail->ErrorInfo ?? $e->getMessage();
        log_email($conn, $student_id, $student_name, $to_email, $trigger_type,
            $subject, 'failed', $err);
        return ['success' => false, 'message' => "Email failed: $err"];
    }
}

// ── Log to email_logs table ──────────────────────────────────
function log_email($conn, int $student_id, string $name, string $email,
                   string $trigger, string $subject, string $status, string $reason): void
{
    $sid_sql  = $student_id > 0 ? $student_id : 'NULL';
    $name_e   = mysqli_real_escape_string($conn, mb_substr($name,    0, 100));
    $email_e  = mysqli_real_escape_string($conn, mb_substr($email,   0, 120));
    $trig_e   = mysqli_real_escape_string($conn, $trigger);
    $subj_e   = mysqli_real_escape_string($conn, mb_substr($subject, 0, 255));
    $stat_e   = mysqli_real_escape_string($conn, $status);
    $reas_e   = mysqli_real_escape_string($conn, mb_substr($reason,  0, 255));
    mysqli_query($conn,
        "INSERT INTO email_logs
            (student_id, to_email, student_name, subject, trigger_type, status, fail_reason)
         VALUES ($sid_sql,'$email_e','$name_e','$subj_e','$trig_e','$stat_e','$reas_e')"
    );
}

// ── Build email HTML per trigger type ───────────────────────
function build_email_content(string $type, array $s, array $ex, $conn): array
{
    $name    = htmlspecialchars($s['name']    ?? 'Student');
    $roll    = htmlspecialchars($s['roll_no'] ?? '');
    $course  = htmlspecialchars($s['course']  ?? '');
    $email   = htmlspecialchars($s['email']   ?? '');
    $date    = date('d F Y');
    $year    = date('Y');

    // Course fee lookup
    $fee_map  = get_course_fee_map($conn);
    $course_fee = isset($fee_map[$s['course']]) ? '₹' . number_format($fee_map[$s['course']]) : 'as per schedule';

    switch ($type) {

        // ── 1. Welcome ──────────────────────────────────────
        case 'welcome':
            $subject = "Welcome to PPTC — Admission Confirmed | {$roll}";
            $intro   = "Congratulations! Your admission to <strong>Pentium Point Technical College</strong> has been successfully registered.";
            $rows    = [
                'Student Name'   => $name,
                'Roll Number'    => $roll,
                'Course'         => $course,
                'Annual Fee'     => $course_fee,
                'Admission Date' => $date,
                'Status'         => '✅ Active',
            ];
            $note = "Please visit the Accounts Department to complete your fee payment process. Bring this email as reference.";
            break;

        // ── 2. Updated ──────────────────────────────────────
        case 'updated':
            $subject = "Profile Updated — PPTC Student Portal | {$roll}";
            $intro   = "Your student profile at <strong>Pentium Point Technical College</strong> has been updated successfully.";
            $rows    = [
                'Student Name' => $name,
                'Roll Number'  => $roll,
                'Course'       => $course,
                'Updated On'   => $date,
            ];
            $note = "If you did not request this update, please contact the college administration immediately at 07662-438035.";
            break;

        // ── 3. Deactivated ──────────────────────────────────
        case 'deactivated':
            $subject = "Account Deactivated — PPTC Student Portal | {$roll}";
            $intro   = "Your student account at <strong>Pentium Point Technical College</strong> has been deactivated.";
            $rows    = [
                'Student Name'     => $name,
                'Roll Number'      => $roll,
                'Course'           => $course,
                'Deactivated On'   => $date,
            ];
            $note = "If you believe this is an error, please contact the Accounts Department or visit the college office.";
            break;

        // ── 4. Payment Success (full) ────────────────────────
        case 'payment_success':
            $amount  = isset($ex['amount'])  ? '₹' . number_format((float)$ex['amount'], 2) : '';
            $receipt = $ex['receipt_no'] ?? '';
            $subject = "🎉 Fee Payment Complete — PPTC | {$roll}";
            $intro   = "Congratulations! <strong>Your course fee payment is now complete.</strong> Your account shows zero outstanding balance.";
            $rows    = [
                'Student Name'   => $name,
                'Roll Number'    => $roll,
                'Course'         => $course,
                'Amount Paid'    => $amount,
                'Receipt No'     => $receipt,
                'Payment Date'   => $date,
                'Balance Due'    => '₹0.00 ✅ CLEARED',
            ];
            $note = "Please keep your receipt number for future reference. Well done on completing your fee payment!";
            break;

        // ── 5. Warning ──────────────────────────────────────
        case 'warning':
            $due     = isset($ex['due_amount']) ? '₹' . number_format((float)$ex['due_amount'], 2) : '';
            $subject = "⚠️ Fee Payment Reminder — Outstanding Balance | {$roll}";
            $intro   = "This is an <strong>official fee payment reminder</strong> from Pentium Point Technical College. Your account has a significant outstanding balance.";
            $rows    = [
                'Student Name'     => $name,
                'Roll Number'      => $roll,
                'Course'           => $course,
                'Outstanding Due'  => "<span style='color:#c0392b;font-weight:800;font-size:1.1em;'>$due</span>",
                'Warning Date'     => $date,
            ];
            $note = "⚠️ <strong>Important:</strong> Please clear your outstanding balance at the earliest. Failure to pay may result in exam form rejection or hold on certificates. Visit the Accounts Department immediately or contact us at 07662-438035.";
            break;

        default:
            $subject = "Notification from PPTC | {$roll}";
            $intro   = "You have a notification from Pentium Point Technical College.";
            $rows    = ['Student Name' => $name, 'Roll Number' => $roll];
            $note    = "For any queries contact us at 07662-438035.";
    }

    $html = email_template($subject, $intro, $rows, $note, $type);
    return [$subject, $html];
}

// ── HTML Email Template ──────────────────────────────────────
function email_template(string $title, string $intro, array $rows, string $note, string $type): string
{
    $accent = match($type) {
        'payment_success' => '#16a34a',
        'warning'         => '#c0392b',
        'deactivated'     => '#7f1d1d',
        default           => '#8B0000',
    };

    $rows_html = '';
    foreach ($rows as $label => $value) {
        $rows_html .= "
        <tr>
            <td style='padding:8px 12px;font-size:13px;color:#666;font-weight:600;width:160px;border-bottom:1px solid #f5f5f5;'>{$label}</td>
            <td style='padding:8px 12px;font-size:13px;color:#1a1a1a;font-weight:700;border-bottom:1px solid #f5f5f5;'>{$value}</td>
        </tr>";
    }

    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f0eb;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f0eb;padding:30px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">

      <!-- Header -->
      <tr>
        <td style="background:linear-gradient(135deg,#5C0000,{$accent});padding:28px 32px;text-align:center;">
          <div style="font-family:Georgia,serif;font-size:22px;font-weight:700;color:#E8C76A;letter-spacing:1px;">
            Pentium Point Technical College
          </div>
          <div style="font-size:12px;color:rgba(255,255,255,0.65);margin-top:4px;letter-spacing:2px;text-transform:uppercase;">
            Student Management System
          </div>
        </td>
      </tr>

      <!-- Body -->
      <tr>
        <td style="padding:28px 32px;">
          <p style="font-size:15px;color:#333;line-height:1.7;margin-bottom:20px;">{$intro}</p>

          <table width="100%" cellpadding="0" cellspacing="0" style="background:#fafafa;border-radius:8px;border:1px solid #eee;margin-bottom:20px;">
            {$rows_html}
          </table>

          <div style="background:#fff8e1;border-left:4px solid {$accent};border-radius:4px;padding:14px 16px;font-size:13px;color:#555;line-height:1.7;">
            {$note}
          </div>
        </td>
      </tr>

      <!-- Footer -->
      <tr>
        <td style="background:#fafafa;border-top:1px solid #eee;padding:16px 32px;text-align:center;">
          <p style="font-size:12px;color:#aaa;margin:0;line-height:1.8;">
            Pentium Point Technical College &bull; Rewa, M.P. &bull; 07662-438035<br>
            nittyish3409@gmail.com &bull; This is an automated email. Do not reply.
          </p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
}
