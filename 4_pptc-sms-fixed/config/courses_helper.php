<?php
// ============================================================
// config/courses_helper.php
// Returns course list from DB. Used in insert/update dropdowns
// and landing page sections.
// ============================================================

/**
 * Get all active courses from DB grouped by category.
 * Falls back to a static array if courses table doesn't exist yet.
 */
function get_all_courses($conn) {
    $grouped = [];
    $result  = @mysqli_query($conn, "SELECT * FROM courses WHERE is_active=1 ORDER BY category, name ASC");

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $grouped[$row['category']][] = $row;
        }
    } else {
        // Static fallback (same list)
        $grouped = get_static_courses();
    }
    return $grouped;
}

/**
 * Flat list of course codes for dropdown
 */
function get_course_codes($conn) {
    $codes  = [];
    $result = @mysqli_query($conn, "SELECT code, name FROM courses WHERE is_active=1 ORDER BY name ASC");
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $codes[$row['code']] = $row['name'];
        }
    } else {
        foreach (get_static_courses() as $cat => $list) {
            foreach ($list as $c) {
                $codes[$c['code']] = $c['name'];
            }
        }
    }
    return $codes;
}

/**
 * Static fallback course list
 */
function get_static_courses() {
    return [
        'UG' => [
            ['code'=>'BCA',      'name'=>'BCA',               'full_title'=>'Bachelor of Computer Application',                                    'icon'=>'💻'],
            ['code'=>'BSC_PSM',  'name'=>'B.Sc (PSM)',        'full_title'=>'B.Sc – Physics, Statistics, Math',                                    'icon'=>'🔭'],
            ['code'=>'BSC_PMCs', 'name'=>'B.Sc (PMCs)',       'full_title'=>'B.Sc – Physics, Math, Computer Science',                              'icon'=>'📐'],
            ['code'=>'BSC_PCM',  'name'=>'B.Sc PCM',         'full_title'=>'B.Sc – Physics, Chemistry, Maths',                                    'icon'=>'⚗️'],
            ['code'=>'BSC_CBZ',  'name'=>'B.Sc (CBZ)',        'full_title'=>'B.Sc – Chemistry, Botany, Zoology',                                   'icon'=>'🌿'],
            ['code'=>'BSC_BTC',  'name'=>'B.Sc Biotech+Chem','full_title'=>'B.Sc Biotechnology (Biotech, Botany, Chemistry)',                     'icon'=>'🧬'],
            ['code'=>'BSC_BT',   'name'=>'B.Sc Biotechnology','full_title'=>'B.Sc Biotechnology (Biotech, Botany, CS)',                            'icon'=>'🔬'],
            ['code'=>'BCOM',     'name'=>'B.Com (Plain)',     'full_title'=>'Bachelor of Commerce (Plain)',                                         'icon'=>'📊'],
            ['code'=>'BCOM_CA',  'name'=>'B.Com (CA)',        'full_title'=>'Bachelor of Commerce – Computer Application',                         'icon'=>'🖥️'],
            ['code'=>'BBA',      'name'=>'BBA',               'full_title'=>'Bachelor of Business Administration (Approved AICTE, New Delhi)',     'icon'=>'📈'],
            ['code'=>'BA',       'name'=>'BA (Plain)',        'full_title'=>'Bachelor of Arts (Plain)',                                             'icon'=>'📚'],
            ['code'=>'BA_CA',    'name'=>'BA (CA)',           'full_title'=>'Bachelor of Arts – Computer Application',                             'icon'=>'🎨'],
        ],
        'PG' => [
            ['code'=>'MSC_BT',   'name'=>'M.Sc Biotechnology',   'full_title'=>'Master of Science – Biotechnology',                               'icon'=>'🔬'],
            ['code'=>'MSC_CHEM', 'name'=>'M.Sc Chemistry',       'full_title'=>'Master of Science – Chemistry',                                   'icon'=>'⚗️'],
            ['code'=>'MSC_CS',   'name'=>'M.Sc Computer Science','full_title'=>'Master of Science – Computer Science',                            'icon'=>'💻'],
            ['code'=>'MSC_PHY',  'name'=>'M.Sc Physics',         'full_title'=>'Master of Science – Physics',                                     'icon'=>'🔭'],
            ['code'=>'MCOM',     'name'=>'M.Com (Plain)',         'full_title'=>'Master of Commerce (Plain)',                                      'icon'=>'📊'],
            ['code'=>'MBA',      'name'=>'MBA',                   'full_title'=>'Master of Business Administration (AICTE, RGPV Bhopal)',         'icon'=>'📈'],
            ['code'=>'MSW',      'name'=>'MSW',                   'full_title'=>'Master of Social Works',                                         'icon'=>'🤲'],
            ['code'=>'MA_HIS',   'name'=>'MA (History)',          'full_title'=>'Master of Arts – History',                                       'icon'=>'🏺'],
            ['code'=>'MA_ECO',   'name'=>'MA (Economics)',        'full_title'=>'Master of Arts – Economics',                                     'icon'=>'💹'],
            ['code'=>'MA_SOC',   'name'=>'MA (Sociology)',        'full_title'=>'Master of Arts – Sociology',                                     'icon'=>'👥'],
        ],
        'Law' => [
            ['code'=>'BALLB',    'name'=>'BALLB',     'full_title'=>'BALLB – Recognized by Bar Council of India, New Delhi',      'icon'=>'⚖️'],
            ['code'=>'LLB',      'name'=>'LLB',       'full_title'=>'Bachelor of Law – Recognized by Bar Council of India',       'icon'=>'🏛️'],
            ['code'=>'LLM',      'name'=>'LLM',       'full_title'=>'Master of Law – Recognized by Bar Council of India',         'icon'=>'📜'],
            ['code'=>'BBA_LLB',  'name'=>'BBA LLB',  'full_title'=>'BBA LLB – Recognized by Bar Council of India, New Delhi',   'icon'=>'🤝'],
            ['code'=>'BCOM_LLB', 'name'=>'B.Com LLB','full_title'=>'B.Com LLB – Recognized by Bar Council of India, New Delhi', 'icon'=>'💼'],
        ],
        'Pharma' => [
            ['code'=>'D_PHARMA', 'name'=>'D. Pharma', 'full_title'=>'D. Pharma – Recognized by PCI New Delhi & Affiliated RGPV Bhopal','icon'=>'💊'],
            ['code'=>'B_PHARMA', 'name'=>'B. Pharma', 'full_title'=>'B. Pharma – Affiliated RGPV Bhopal, Approved PCI New Delhi',       'icon'=>'🧪'],
        ],
        'Diploma' => [
            ['code'=>'PGDCA', 'name'=>'PGDCA', 'full_title'=>'Post Graduate Diploma in Computer Applications', 'icon'=>'🖱️'],
        ],
    ];
}
