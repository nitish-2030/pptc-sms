<?php
// ============================================================
// chatbot_api.php — Admin AI Assistant (Rule-Based Engine v4)
// Handles keyword detection + insights data access
// Returns JSON responses only
// ============================================================
require_once 'config/db.php';
require_once 'config/auth_check.php';
require_once 'config/activity_helper.php';

header('Content-Type: application/json');

$input   = json_decode(file_get_contents('php://input'), true);
$message = strtolower(trim($input['message'] ?? ''));
$raw_msg = trim($input['message'] ?? '');

if (empty($message)) {
    echo json_encode(['reply' => fallback_reply(), 'suggestions' => true]);
    exit;
}

// ── Helper: fetch insights data ──────────────────────────────
function get_insights($conn): array {
    $data = [];

    // Basic stats
    $s = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS total,
                SUM(is_active=1) AS active,
                SUM(is_active=0) AS inactive
         FROM students"));
    $data['students'] = $s;

    // Fee overview
    $f = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(SUM(total_fee),0) AS total_fee,
                COALESCE(SUM(paid_amount),0) AS collected,
                COALESCE(SUM(due_amount),0) AS pending,
                COUNT(CASE WHEN status='Paid'    THEN 1 END) AS paid_count,
                COUNT(CASE WHEN status='Partial' THEN 1 END) AS partial_count,
                COUNT(CASE WHEN status='Unpaid'  THEN 1 END) AS unpaid_count
         FROM fees"));
    $data['fees'] = $f;
    $data['efficiency'] = $f['total_fee'] > 0
        ? round(($f['collected'] / $f['total_fee']) * 100, 1) : 0;

    // This month collections
    $month_now = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(SUM(amount),0) AS total, COUNT(*) AS txns
         FROM fee_payments
         WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())"));
    $data['month_collected'] = $month_now['total'];
    $data['month_txns']      = $month_now['txns'];

    // Last month
    $month_last = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(SUM(amount),0) AS total
         FROM fee_payments
         WHERE MONTH(payment_date)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))
           AND YEAR(payment_date)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))"));
    $data['last_month_collected'] = $month_last['total'];

    // Today's collections
    $today_fee = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(SUM(amount),0) AS total, COUNT(*) AS txns
         FROM fee_payments WHERE DATE(payment_date)=CURDATE()"));
    $data['today_collected'] = $today_fee['total'];
    $data['today_txns']      = $today_fee['txns'];

    // High risk (due > 15000)
    $risk = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS c, COALESCE(SUM(f.due_amount),0) AS total_due
         FROM students s JOIN fees f ON s.id=f.student_id
         WHERE f.due_amount > 15000 AND s.is_active=1"));
    $data['high_risk_count']    = $risk['c'];
    $data['high_risk_total_due'] = $risk['total_due'];

    // Warnings sent
    $warn = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS c FROM students WHERE warning_sent_at IS NOT NULL"));
    $data['warnings_sent'] = $warn['c'];

    // Emails today
    $emails_today = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS c,
                SUM(status='sent') AS sent_ok,
                SUM(status='failed') AS failed
         FROM email_logs WHERE DATE(sent_at)=CURDATE()"));
    $data['emails_today']       = $emails_today['c'];
    $data['emails_today_ok']    = $emails_today['sent_ok'];
    $data['emails_today_fail']  = $emails_today['failed'];

    // Total emails
    $total_emails = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS c FROM email_logs WHERE status='sent'"));
    $data['total_emails_sent'] = $total_emails['c'];

    // Students with due > 5000
    $due5k = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS c FROM fees f
         JOIN students s ON s.id=f.student_id
         WHERE f.due_amount > 5000 AND s.is_active=1"));
    $data['due_above_5k'] = $due5k['c'];

    // Admin activity today
    $admin_today = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS c FROM activity_logs
         WHERE category='admin' AND DATE(created_at)=CURDATE()"));
    $data['admin_actions_today'] = $admin_today['c'];

    // Recent admin actions
    $admin_res = mysqli_query($conn,
        "SELECT action, created_at FROM activity_logs
         WHERE category='admin' ORDER BY created_at DESC LIMIT 5");
    $data['recent_admin'] = [];
    while ($r = mysqli_fetch_assoc($admin_res)) $data['recent_admin'][] = $r;

    // Top 3 defaulters
    $def_res = mysqli_query($conn,
        "SELECT s.name, s.roll_no, s.course, f.due_amount
         FROM students s JOIN fees f ON s.id=f.student_id
         WHERE f.due_amount > 0 AND s.is_active=1
         ORDER BY f.due_amount DESC LIMIT 3");
    $data['top_defaulters'] = [];
    while ($r = mysqli_fetch_assoc($def_res)) $data['top_defaulters'][] = $r;

    return $data;
}

// ── Intent Detection ─────────────────────────────────────────
function detect_intent(string $msg): string {
    $checks = [
        'collection_today'    => ['today','aaj','collection today','collected today','payment today'],
        'collection_month'    => ['this month','month','monthly','is mahine','mahina','month collection'],
        'collection_total'    => ['total collection','total collected','total fee','overall collection','kitna collect'],
        'pending_due'         => ['pending','due','outstanding','baki','unpaid','baaki'],
        'due_above_5k'        => ['due 5000','due above','due > 5','above 5k','more than 5000','5000 se zyada'],
        'high_risk'           => ['risk','high risk','warning','risky','danger','at risk','warned'],
        'emails_today'        => ['email today','emails today','mail today','aaj email','aaj mail'],
        'emails_total'        => ['total email','all email','email sent','emails sent','kitne email'],
        'efficiency'          => ['efficiency','collection rate','kitna percent','percent collected','rate'],
        'admin_activity'      => ['admin','admin activity','login','logout','admin today','admin action'],
        'insights_summary'    => ['insights','summary','overview','brief','full report','sab kuch','sara'],
        'top_defaulters'      => ['defaulter','top due','highest due','biggest due','most pending'],
        'student_count'       => ['student count','total student','kitne student','how many student','students'],
        'greeting'            => ['hello','hi','hey','namaste','hii','helo','good','help'],
        'help'                => ['help','kya kar sakte','what can you','capabilities','features'],
    ];

    foreach ($checks as $intent => $keywords) {
        foreach ($keywords as $kw) {
            if (str_contains($msg, $kw)) return $intent;
        }
    }
    return 'unknown';
}

// ── Build replies ─────────────────────────────────────────────
function build_reply(string $intent, array $d): string {
    $fmt = fn($n) => '₹' . number_format((float)$n, 2);
    $num = fn($n) => number_format((int)$n);

    switch ($intent) {

        case 'greeting':
            return "👋 Hello, Admin! I'm your PPTC Assistant.\n\nI can help you with:\n• 📊 Fee collection data\n• ⚠️ Risk & warning reports\n• 📧 Email activity\n• 📈 Financial insights\n• 👤 Admin activity logs\n\nTry a quick suggestion below or ask me anything!";

        case 'help':
            return "🤖 Here's what I can answer:\n\n📊 *Collection* — today, this month, total\n💰 *Pending dues* — who owes, how much\n⚠️ *Risk students* — high due, warnings sent\n📧 *Emails* — sent today, total count\n📈 *Insights* — efficiency, growth, overview\n👤 *Admin activity* — logins, actions today\n\nJust type or tap a suggestion!";

        case 'collection_today':
            $growth = '';
            if ($d['month_collected'] > 0)
                $growth = "\n📅 This month so far: {$fmt($d['month_collected'])}";
            return "💰 **Today's Collection**\n\nAmount Collected: {$fmt($d['today_collected'])}\nTransactions: {$num($d['today_txns'])} payments made today{$growth}";

        case 'collection_month':
            $last = $d['last_month_collected'];
            $curr = $d['month_collected'];
            if ($last > 0) {
                $pct  = round((($curr - $last) / $last) * 100, 1);
                $dir  = $pct >= 0 ? "📈 +{$pct}% growth" : "📉 {$pct}% decline";
                $comp = "\n{$dir} vs last month ({$fmt($last)})";
            } else {
                $comp = "\n(No last month data for comparison)";
            }
            return "📅 **This Month's Collection**\n\nCollected: {$fmt($curr)}\nTransactions: {$num($d['month_txns'])}{$comp}";

        case 'collection_total':
            return "📊 **Total Fee Collection Summary**\n\nTotal Fee Assigned: {$fmt($d['fees']['total_fee'])}\nTotal Collected: {$fmt($d['fees']['collected'])}\nTotal Pending: {$fmt($d['fees']['pending'])}\nCollection Efficiency: {$d['efficiency']}%\n\n✅ Fully Paid: {$num($d['fees']['paid_count'])} students\n🟡 Partial: {$num($d['fees']['partial_count'])} students\n🔴 Unpaid: {$num($d['fees']['unpaid_count'])} students";

        case 'pending_due':
            $top = '';
            foreach ($d['top_defaulters'] as $i => $def) {
                $top .= "\n" . ($i+1) . ". {$def['name']} ({$def['course']}) — ₹" . number_format($def['due_amount']);
            }
            return "💸 **Pending Fee Overview**\n\nTotal Outstanding: {$fmt($d['fees']['pending'])}\nStudents with Due > ₹5,000: {$num($d['due_above_5k'])}\nHigh Risk (> ₹15,000): {$num($d['high_risk_count'])} students\n\n🔴 Top Defaulters:{$top}";

        case 'due_above_5k':
            return "💰 **Students with Due > ₹5,000**\n\nTotal: {$num($d['due_above_5k'])} students\nCombined Pending: {$fmt($d['fees']['pending'])}\n\nVisit ⚠️ Warnings page to send reminder emails to these students.";

        case 'high_risk':
            $warn_msg = $d['warnings_sent'] > 0
                ? "Warnings Already Sent: {$num($d['warnings_sent'])} students"
                : "No warnings sent yet.";
            return "⚠️ **Risk Intelligence Report**\n\nHigh Risk Students (due > ₹15,000): {$num($d['high_risk_count'])}\nTotal Due from Risk Students: {$fmt($d['high_risk_total_due'])}\n{$warn_msg}\n\nRecommendation: Head to ⚠️ Warnings page to send fee reminder emails.";

        case 'emails_today':
            $fail_note = $d['emails_today_fail'] > 0
                ? "\n❌ Failed: {$num($d['emails_today_fail'])}"
                : '';
            return "📧 **Email Activity — Today**\n\nEmails Triggered: {$num($d['emails_today'])}\n✅ Sent Successfully: {$num($d['emails_today_ok'])}{$fail_note}\n\nTotal emails sent overall: {$num($d['total_emails_sent'])}";

        case 'emails_total':
            return "📧 **Email System Summary**\n\nTotal Emails Sent (all time): {$num($d['total_emails_sent'])}\nEmails Today: {$num($d['emails_today'])}\nWarnings Sent: {$num($d['warnings_sent'])} students notified\n\nEmail types: Welcome, Updated, Deactivated, Payment Complete, Fee Warning";

        case 'efficiency':
            $eff   = $d['efficiency'];
            $grade = $eff >= 80 ? "🟢 Excellent" : ($eff >= 60 ? "🟡 Moderate — needs attention" : "🔴 Low — urgent action needed");
            return "📈 **Collection Efficiency**\n\nEfficiency Rate: {$eff}%\nStatus: {$grade}\n\nCollected: {$fmt($d['fees']['collected'])} of {$fmt($d['fees']['total_fee'])}\n\n💡 " . ($eff < 70
                ? "Efficiency below 70% — consider sending warning emails to high-due students."
                : "Good efficiency! Keep tracking monthly growth.");

        case 'admin_activity':
            $actions = '';
            foreach ($d['recent_admin'] as $a) {
                $time     = date('h:i A', strtotime($a['created_at']));
                $actions .= "\n• [{$time}] {$a['action']}";
            }
            return "👤 **Admin Activity**\n\nActions Today: {$num($d['admin_actions_today'])}\n\nRecent Activity:{$actions}";

        case 'student_count':
            return "🎓 **Student Overview**\n\nTotal Students: {$num($d['students']['total'])}\nActive: {$num($d['students']['active'])}\nInactive: {$num($d['students']['inactive'])}\n\nStudents with Due > ₹5,000: {$num($d['due_above_5k'])}\nFully Paid: {$num($d['fees']['paid_count'])} students";

        case 'top_defaulters':
            $list = '';
            foreach ($d['top_defaulters'] as $i => $def) {
                $list .= "\n" . ($i+1) . ". **{$def['name']}** ({$def['course']})\n   Roll: {$def['roll_no']} | Due: ₹" . number_format($def['due_amount']);
            }
            return "🔴 **Top Defaulters (Highest Due)**{$list}\n\nTotal High-Risk Students: {$num($d['high_risk_count'])}\nGo to ⚠️ Warnings page to take action.";

        case 'insights_summary':
            $eff    = $d['efficiency'];
            $growth = '';
            if ($d['last_month_collected'] > 0) {
                $pct    = round((($d['month_collected'] - $d['last_month_collected']) / $d['last_month_collected']) * 100, 1);
                $growth = $pct >= 0 ? "📈 Growth: +{$pct}% vs last month" : "📉 Decline: {$pct}% vs last month";
            }
            $risk_alert = $d['high_risk_count'] > 3
                ? "🔴 {$num($d['high_risk_count'])} high-risk students need urgent attention."
                : "✅ Risk level manageable.";

            return "📊 **Full Insights Summary**\n\n🎓 Students: {$num($d['students']['total'])} total ({$num($d['students']['active'])} active)\n💰 Collected: {$fmt($d['fees']['collected'])} / {$fmt($d['fees']['total_fee'])}\n📉 Pending: {$fmt($d['fees']['pending'])}\n📈 Efficiency: {$eff}%\n{$growth}\n\n⚠️ High Risk: {$num($d['high_risk_count'])} students\n📧 Emails Sent (today): {$num($d['emails_today'])}\n{$risk_alert}";

        default:
            return fallback_reply();
    }
}

function fallback_reply(): string {
    return "🤔 I didn't quite catch that.\n\nI can help with:\n• Collection stats (today / month / total)\n• Pending dues & high-risk students\n• Email activity reports\n• Financial insights & efficiency\n• Admin activity logs\n\nPlease try a quick suggestion below or rephrase your question!";
}

// ── Main execution ────────────────────────────────────────────
$insights = get_insights($conn);
$intent   = detect_intent($message);
$reply    = build_reply($intent, $insights);

// Log chatbot query to activity_logs
log_activity($conn, 'admin',
    'Chatbot query: ' . mb_substr($raw_msg, 0, 80),
    'Intent: ' . $intent,
    null, null
);

echo json_encode([
    'reply'       => $reply,
    'intent'      => $intent,
    'suggestions' => ($intent === 'unknown'),
    'timestamp'   => date('h:i A'),
]);
exit;
?>
