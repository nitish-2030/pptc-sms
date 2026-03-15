<?php
// ============================================================
// config/activity_helper.php — Central Activity Logger v4
// ============================================================

function log_activity(
    $conn,
    string $category,
    string $action,
    string $detail        = '',
    ?int   $student_id    = null,
    ?string $student_name = null
): void {
    // Icon by action keywords
    $icon  = '📋';
    $color = 'blue';
    $kw    = strtolower($action);
    if (str_contains($kw,'full payment') || str_contains($kw,'completed'))   { $icon='💰'; $color='green'; }
    elseif (str_contains($kw,'payment') || str_contains($kw,'paid'))          { $icon='💳'; $color='green'; }
    elseif (str_contains($kw,'warning'))                                       { $icon='⚠️'; $color='red'; }
    elseif (str_contains($kw,'deactivat'))                                     { $icon='🔴'; $color='red'; }
    elseif (str_contains($kw,'added') || str_contains($kw,'registered'))      { $icon='🟢'; $color='green'; }
    elseif (str_contains($kw,'updated') || str_contains($kw,'profile'))       { $icon='🔵'; $color='blue'; }
    elseif (str_contains($kw,'login'))                                         { $icon='🔑'; $color='purple'; }
    elseif (str_contains($kw,'logout'))                                        { $icon='🚪'; $color='purple'; }
    elseif (str_contains($kw,'email') || str_contains($kw,'sent'))            { $icon='📧'; $color='teal'; }
    elseif ($category === 'admin')                                             { $icon='🔐'; $color='purple'; }

    $cat_esc   = mysqli_real_escape_string($conn, $category);
    $act_esc   = mysqli_real_escape_string($conn, mb_substr($action, 0, 255));
    $det_esc   = mysqli_real_escape_string($conn, mb_substr($detail, 0, 1000));
    $snam_esc  = mysqli_real_escape_string($conn, mb_substr($student_name ?? '', 0, 100));
    $icon_esc  = mysqli_real_escape_string($conn, $icon);
    $color_esc = mysqli_real_escape_string($conn, $color);
    $sid_sql   = $student_id !== null ? (int)$student_id : 'NULL';

    mysqli_query($conn,
        "INSERT INTO activity_logs
            (category, action, detail, student_id, student_name, icon, color)
         VALUES
            ('$cat_esc','$act_esc','$det_esc',$sid_sql,'$snam_esc','$icon_esc','$color_esc')"
    );
}
