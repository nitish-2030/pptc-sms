<?php
// ============================================================
// process_update.php — Handle Update + Photo Upload (v3)
// ============================================================
require_once 'config/db.php';
require_once 'config/courses_helper.php';
require_once 'config/photo_helper.php';
require_once 'config/csrf_helper.php';
require_once 'config/email_helper.php';
require_once 'config/activity_helper.php';

csrf_validate();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: update.php'); exit;
}

$valid_codes    = array_keys(get_course_codes($conn));
$id             = (int)($_POST['id']            ?? 0);
$roll_no        = trim($_POST['roll_no']         ?? '');
$name           = trim($_POST['name']            ?? '');
$course         = trim($_POST['course']          ?? '');
$admission_date = trim($_POST['admission_date']  ?? '');
$is_active      = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
$current_photo  = trim($_POST['current_photo']   ?? '');
$total_fee      = isset($_POST['total_fee']) ? (float)$_POST['total_fee'] : 0.00;

// New fields
$phone          = trim($_POST['phone']          ?? '') ?: null;
$email          = trim($_POST['email']          ?? '') ?: null;
$gender         = trim($_POST['gender']         ?? '') ?: null;
$dob            = trim($_POST['dob']            ?? '') ?: null;
$address        = trim($_POST['address']        ?? '') ?: null;
$guardian_name  = trim($_POST['guardian_name']  ?? '') ?: null;
$guardian_phone = trim($_POST['guardian_phone'] ?? '') ?: null;
$blood_group    = trim($_POST['blood_group']    ?? '') ?: null;
$category       = trim($_POST['category']       ?? 'General');

$errors = [];
if (!$id)                             $errors[] = 'Invalid student ID.';
if (!$name || mb_strlen($name) < 2)  $errors[] = 'Name must be at least 2 characters.';
if (!in_array($course, $valid_codes)) $errors[] = 'Please select a valid course.';
if (!$admission_date)                 $errors[] = 'Invalid date.';
if ($phone && !preg_match('/^[0-9]{10,15}$/', $phone)) { $errors[] = 'Invalid phone.'; $phone = null; }
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Invalid email.'; $email = null; }
if (!in_array($category, ['General','OBC','SC','ST','EWS'])) $category = 'General';

if ($errors) {
    header('Location: update.php?roll_no='.urlencode($roll_no).'&status=error&msg='.urlencode(implode(' | ',$errors)));
    exit;
}

$photo_err = '';
$new_photo = handle_photo_upload($_FILES['photo'] ?? [], $photo_err);
$final_photo = $new_photo !== '' ? $new_photo : $current_photo;

$stmt = mysqli_prepare($conn,
    "UPDATE students SET
        name=?, course=?, admission_date=?, is_active=?, photo=?,
        phone=?, email=?, gender=?, dob=?, address=?,
        guardian_name=?, guardian_phone=?, blood_group=?, category=?
     WHERE id=?"
);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ssssisssssssssi',
        $name, $course, $admission_date, $is_active, $final_photo,
        $phone, $email, $gender, $dob, $address,
        $guardian_name, $guardian_phone, $blood_group, $category,
        $id
    );
} else {
    // Fallback without new columns
    $stmt = mysqli_prepare($conn,
        "UPDATE students SET name=?, course=?, admission_date=?, is_active=? WHERE id=?"
    );
    mysqli_stmt_bind_param($stmt, 'sssii', $name, $course, $admission_date, $is_active, $id);
    $final_photo = $current_photo;
}

if (mysqli_stmt_execute($stmt)) {
    // Update fee record
    $check_fee = mysqli_prepare($conn, "SELECT id FROM fees WHERE student_id = ?");
    mysqli_stmt_bind_param($check_fee, 'i', $id);
    mysqli_stmt_execute($check_fee);
    $fee_exists = mysqli_num_rows(mysqli_stmt_get_result($check_fee)) > 0;

    if ($fee_exists) {
        $upd_fee = mysqli_prepare($conn,
            "UPDATE fees SET total_fee=?, due_amount=?-paid_amount,
             status = CASE
                WHEN (?-paid_amount) <= 0 THEN 'Paid'
                WHEN paid_amount = 0      THEN 'Unpaid'
                ELSE 'Partial'
             END
             WHERE student_id=?"
        );
        mysqli_stmt_bind_param($upd_fee, 'dddi', $total_fee, $total_fee, $total_fee, $id);
        mysqli_stmt_execute($upd_fee);
    } else {
        $ins_fee = mysqli_prepare($conn,
            "INSERT INTO fees (student_id, total_fee, paid_amount, due_amount, status) VALUES (?, ?, 0, ?, ?)"
        );
        $fstatus = ($total_fee > 0) ? 'Unpaid' : 'Paid';
        mysqli_stmt_bind_param($ins_fee, 'idds', $id, $total_fee, $total_fee, $fstatus);
        mysqli_stmt_execute($ins_fee);
    }

    if ($new_photo !== '' && $current_photo !== '') delete_photo($current_photo);

    // ── Feature 1 & 2: Email + Activity Log ──
    $upd_student = ['id'=>$id,'name'=>$name,'email'=>$email,'roll_no'=>$roll_no,'course'=>$course];
    $email_result = send_email($conn, 'updated', $upd_student, []);
    log_activity($conn, 'student',
        "{$name} profile was updated",
        "Roll: {$roll_no} | Course: {$course}",
        $id, $name
    );
    log_activity($conn, 'email',
        "Profile update email {$email_result['message']}",
        "Trigger: updated | To: ".($email??'no email'),
        $id, $name
    );

    header('Location: update.php?roll_no='.urlencode($roll_no).'&status=success');
} else {
    delete_photo($new_photo);
    header('Location: update.php?roll_no='.urlencode($roll_no).'&status=error&msg='.urlencode(mysqli_stmt_error($stmt)));
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
