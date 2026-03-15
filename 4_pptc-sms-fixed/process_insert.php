<?php
// ============================================================
// process_insert.php — Handle Insert + Photo Upload (v3)
// ============================================================
require_once 'config/db.php';
require_once 'config/courses_helper.php';
require_once 'config/photo_helper.php';
require_once 'config/csrf_helper.php';
require_once 'config/email_helper.php';
require_once 'config/activity_helper.php';

csrf_validate();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: insert.php'); exit;
}

$valid_codes    = array_keys(get_course_codes($conn));
$roll_no        = trim($_POST['roll_no']        ?? '');
$name           = trim($_POST['name']           ?? '');
$course         = trim($_POST['course']         ?? '');
$admission_date = trim($_POST['admission_date'] ?? '');
$is_active      = isset($_POST['is_active'])  ? (int)$_POST['is_active']  : 1;
$total_fee      = isset($_POST['total_fee'])   ? (float)$_POST['total_fee'] : 0.00;

// New fields
$phone          = trim($_POST['phone']          ?? '');
$email          = trim($_POST['email']          ?? '');
$gender         = trim($_POST['gender']         ?? '');
$dob            = trim($_POST['dob']            ?? '');
$address        = trim($_POST['address']        ?? '');
$guardian_name  = trim($_POST['guardian_name']  ?? '');
$guardian_phone = trim($_POST['guardian_phone'] ?? '');
$blood_group    = trim($_POST['blood_group']    ?? '');
$category       = trim($_POST['category']       ?? 'General');

// Sanitize / validate
$errors = [];
if (!$roll_no || !preg_match('/^[A-Za-z0-9_]{3,30}$/', $roll_no))
    $errors[] = 'Invalid Roll No (3–30 alphanumeric characters).';
if (!$name || mb_strlen($name) < 2)
    $errors[] = 'Name must be at least 2 characters.';
if (!in_array($course, $valid_codes))
    $errors[] = 'Please select a valid course.';
if (!$admission_date || !strtotime($admission_date))
    $errors[] = 'Please provide a valid admission date.';
if (!in_array($gender, ['Male','Female','Other','']))
    $gender = '';
if (!in_array($category, ['General','OBC','SC','ST','EWS']))
    $category = 'General';
if ($phone && !preg_match('/^[0-9]{10,15}$/', $phone))
    $errors[] = 'Phone number must be 10–15 digits.';
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors[] = 'Invalid email address.';
if ($dob && !strtotime($dob))
    $dob = null;

// Nullify empty optional fields
$phone          = $phone          ?: null;
$email          = $email          ?: null;
$gender         = $gender         ?: null;
$dob            = $dob            ?: null;
$address        = $address        ?: null;
$guardian_name  = $guardian_name  ?: null;
$guardian_phone = $guardian_phone ?: null;
$blood_group    = $blood_group    ?: null;

if ($errors) {
    header('Location: insert.php?status=error&msg=' . urlencode(implode(' | ', $errors)));
    exit;
}

// Handle photo upload
$photo_err  = '';
$photo_file = handle_photo_upload($_FILES['photo'] ?? [], $photo_err);

// Insert student
$stmt = mysqli_prepare($conn,
    "INSERT INTO students
        (roll_no, name, course, admission_date, is_active, photo,
         phone, email, gender, dob, address,
         guardian_name, guardian_phone, blood_group, category)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ssssissssssssss',
        $roll_no, $name, $course, $admission_date, $is_active, $photo_file,
        $phone, $email, $gender, $dob, $address,
        $guardian_name, $guardian_phone, $blood_group, $category
    );
} else {
    // Fallback without new columns if schema not migrated yet
    $stmt = mysqli_prepare($conn,
        "INSERT INTO students (roll_no, name, course, admission_date, is_active) VALUES (?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, 'ssssi', $roll_no, $name, $course, $admission_date, $is_active);
}

if ($stmt && mysqli_stmt_execute($stmt)) {
    $new_id = mysqli_insert_id($conn);
    if ($new_id && $total_fee >= 0) {
        $fee_stmt = mysqli_prepare($conn,
            "INSERT INTO fees (student_id, total_fee, paid_amount, due_amount, status) VALUES (?, ?, 0, ?, ?)"
        );
        $fstatus = ($total_fee > 0) ? 'Unpaid' : 'Paid';
        mysqli_stmt_bind_param($fee_stmt, 'idds', $new_id, $total_fee, $total_fee, $fstatus);
        mysqli_stmt_execute($fee_stmt);
    }

    // ── Feature 1 & 2: Email + Activity Log ──
    $new_student = ['id'=>$new_id,'name'=>$name,'email'=>$email,'roll_no'=>$roll_no,'course'=>$course];
    $email_result = send_email($conn, 'welcome', $new_student, []);
    log_activity($conn, 'student',
        "{$name} was registered as a new student",
        "Roll: {$roll_no} | Course: {$course}",
        $new_id, $name
    );
    log_activity($conn, 'email',
        "Welcome email {$email_result['message']}",
        "Trigger: welcome | To: ".($email??'no email'),
        $new_id, $name
    );

    $msg = $photo_err ? '?status=success&photo_warn=' . urlencode($photo_err) : '?status=success';
    header('Location: insert.php' . $msg);
} else {
    delete_photo($photo_file);
    $err = $stmt ? mysqli_stmt_error($stmt) : mysqli_error($conn);
    $msg = (stripos($err, 'Duplicate') !== false)
        ? "Roll No '$roll_no' already exists."
        : 'Database error: ' . $err;
    header('Location: insert.php?status=error&msg=' . urlencode($msg));
}
if ($stmt) mysqli_stmt_close($stmt);
mysqli_close($conn);
