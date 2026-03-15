-- ============================================================
-- Student Management System — Pentium Point Technical College
-- Database: student_db | COMPLETE SETUP (v2 — All Tables)
-- Run this single file to set up everything from scratch.
-- ============================================================

CREATE DATABASE IF NOT EXISTS student_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_db;

-- -------------------------------------------------------
-- Table: students
-- -------------------------------------------------------
DROP TABLE IF EXISTS fee_payments;
DROP TABLE IF EXISTS fees;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS courses;

CREATE TABLE students (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    roll_no         VARCHAR(30) NOT NULL UNIQUE,
    name            VARCHAR(100) NOT NULL,
    course          VARCHAR(80) NOT NULL,
    admission_date  DATE NOT NULL,
    is_active       TINYINT(1) DEFAULT 1,
    photo           VARCHAR(200) DEFAULT NULL,
    phone           VARCHAR(15)  DEFAULT NULL,
    email           VARCHAR(120) DEFAULT NULL,
    gender          ENUM('Male','Female','Other') DEFAULT NULL,
    dob             DATE         DEFAULT NULL,
    address         TEXT         DEFAULT NULL,
    guardian_name   VARCHAR(100) DEFAULT NULL,
    guardian_phone  VARCHAR(15)  DEFAULT NULL,
    blood_group     VARCHAR(5)   DEFAULT NULL,
    category        ENUM('General','OBC','SC','ST','EWS') DEFAULT 'General',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Table: courses
-- -------------------------------------------------------
CREATE TABLE courses (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    code         VARCHAR(30) NOT NULL UNIQUE,
    name         VARCHAR(120) NOT NULL,
    full_title   VARCHAR(255) NOT NULL,
    category     ENUM('UG','PG','Law','Pharma','Diploma') NOT NULL,
    icon         VARCHAR(10) DEFAULT '🎓',
    is_active    TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Table: fees (one row per student — summary)
-- -------------------------------------------------------
CREATE TABLE fees (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    student_id   INT NOT NULL UNIQUE,
    total_fee    DECIMAL(10,2) NOT NULL DEFAULT 0,
    paid_amount  DECIMAL(10,2) DEFAULT 0,
    due_amount   DECIMAL(10,2) DEFAULT 0,
    status       ENUM('Unpaid','Partial','Paid') DEFAULT 'Unpaid',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Table: fee_payments (one row per payment transaction)
-- -------------------------------------------------------
CREATE TABLE fee_payments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    student_id    INT NOT NULL,
    amount        DECIMAL(10,2) NOT NULL,
    payment_mode  VARCHAR(50),
    receipt_no    VARCHAR(60) UNIQUE,
    payment_date  DATE,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Insert all courses
-- -------------------------------------------------------
INSERT INTO courses (code, name, full_title, category, icon) VALUES
('BCA',        'BCA',                    'Bachelor of Computer Application',                                      'UG',      '💻'),
('BSC_PSM',    'B.Sc (PSM)',             'B.Sc – Physics, Statistics, Math',                                      'UG',      '🔭'),
('BSC_PMCs',   'B.Sc (PMCs)',            'B.Sc – Physics, Math, Computer Science',                                'UG',      '📐'),
('BSC_PCM',    'B.Sc PCM',              'B.Sc – Physics, Chemistry, Maths',                                      'UG',      '⚗️'),
('BSC_CBZ',    'B.Sc (CBZ)',             'B.Sc – Chemistry, Botany, Zoology',                                     'UG',      '🌿'),
('BSC_BTC',    'B.Sc Biotech+Chem',     'B.Sc Biotechnology (Biotech, Botany, Chemistry)',                       'UG',      '🧬'),
('BSC_BT',     'B.Sc Biotechnology',    'B.Sc Biotechnology (Biotech, Botany, CS)',                              'UG',      '🔬'),
('BCOM',       'B.Com (Plain)',          'Bachelor of Commerce (Plain)',                                           'UG',      '📊'),
('BCOM_CA',    'B.Com (CA)',             'Bachelor of Commerce – Computer Application',                           'UG',      '🖥️'),
('BBA',        'BBA',                    'Bachelor of Business Administration (Approved with AICTE, New Delhi)',  'UG',      '📈'),
('BA',         'BA (Plain)',             'Bachelor of Arts (Plain)',                                               'UG',      '📚'),
('BA_CA',      'BA (CA)',               'Bachelor of Arts – Computer Application',                               'UG',      '🎨'),
('PGDCA',      'PGDCA',                 'Post Graduate Diploma in Computer Applications',                        'Diploma', '🖱️'),
('BALLB',      'BALLB',                 'BALLB – Recognized by Bar Council of India, New Delhi',                 'Law',     '⚖️'),
('LLB',        'LLB',                   'Bachelor of Law – Recognized by Bar Council of India, New Delhi',      'Law',     '🏛️'),
('LLM',        'LLM',                   'Master of Law – Recognized by Bar Council of India, New Delhi',        'Law',     '📜'),
('BBA_LLB',    'BBA LLB',              'BBA LLB – Recognized by Bar Council of India, New Delhi',              'Law',     '🤝'),
('BCOM_LLB',   'B.Com LLB',            'B.Com LLB – Recognized by Bar Council of India, New Delhi',            'Law',     '💼'),
('D_PHARMA',   'D. Pharma',            'D. Pharma – Recognized by PCI New Delhi & Affiliated RGPV Bhopal',     'Pharma',  '💊'),
('B_PHARMA',   'B. Pharma',            'B. Pharma – Affiliated RGPV Bhopal, Approved PCI New Delhi',           'Pharma',  '🧪'),
('MSC_BT',     'M.Sc Biotechnology',   'Master of Science – Biotechnology',                                     'PG',      '🔬'),
('MSC_CHEM',   'M.Sc Chemistry',       'Master of Science – Chemistry',                                         'PG',      '⚗️'),
('MSC_CS',     'M.Sc Computer Science','Master of Science – Computer Science',                                   'PG',      '💻'),
('MSC_PHY',    'M.Sc Physics',         'Master of Science – Physics',                                           'PG',      '🔭'),
('MCOM',       'M.Com (Plain)',         'Master of Commerce (Plain)',                                             'PG',      '📊'),
('MBA',        'MBA',                   'Master of Business Administration (Recognized AICTE, Affiliated RGPV)', 'PG',      '📈'),
('MSW',        'MSW',                   'Master of Social Works',                                                'PG',      '🤲'),
('MA_HIS',     'MA (History)',          'Master of Arts – History',                                              'PG',      '🏺'),
('MA_ECO',     'MA (Economics)',        'Master of Arts – Economics',                                            'PG',      '💹'),
('MA_SOC',     'MA (Sociology)',        'Master of Arts – Sociology',                                            'PG',      '👥');

-- -------------------------------------------------------
-- Sample student data
-- -------------------------------------------------------
INSERT INTO students (roll_no, name, course, admission_date, is_active, phone, email, gender, dob, address, guardian_name, guardian_phone, blood_group, category) VALUES
('BCA2024001',     'Aarav Sharma',  'BCA',      '2024-07-15', 1, '9876543210', 'aarav.sharma@email.com',  'Male',   '2005-03-12', '123 Gandhi Nagar, Rewa, M.P.',   'Ramesh Sharma', '9812345670', 'B+',  'General'),
('BCOM2024002',    'Priya Mishra',  'BCOM',     '2024-07-15', 1, '9865432109', 'priya.mishra@email.com',  'Female', '2004-11-25', '45 Nehru Colony, Rewa, M.P.',    'Suresh Mishra', '9823456781', 'O+',  'OBC'),
('BBA2024003',     'Rohan Tiwari',  'BBA',      '2023-07-20', 1, '9754321098', 'rohan.tiwari@email.com',  'Male',   '2004-07-08', '78 Shastri Road, Satna, M.P.',   'Vijay Tiwari',  '9834567892', 'A+',  'General'),
('LLB2024004',     'Sneha Gupta',   'LLB',      '2024-07-15', 0, '9643210987', 'sneha.gupta@email.com',   'Female', '2003-05-19', '56 Civil Lines, Rewa, M.P.',     'Arun Gupta',    '9845678903', 'A-',  'General'),
('BSC2024005',     'Kiran Patel',   'BSC_PCM',  '2024-07-10', 1, '9532109876', 'kiran.patel@email.com',   'Female', '2005-01-30', '12 MG Road, Rewa, M.P.',         'Harish Patel',  '9856789014', 'AB+', 'OBC'),
('MBA2024006',     'Amit Verma',    'MBA',      '2024-07-15', 1, '9421098765', 'amit.verma@email.com',    'Male',   '2001-09-14', '34 Station Road, Rewa, M.P.',    'Dinesh Verma',  '9867890125', 'B-',  'General'),
('BALLB2024007',   'Pooja Singh',   'BALLB',    '2024-07-15', 1, '9310987654', 'pooja.singh@email.com',   'Female', '2003-12-03', '90 Nehru Nagar, Rewa, M.P.',     'Manoj Singh',   '9878901236', 'O-',  'SC'),
('DPHARMA2024008', 'Rahul Dubey',   'D_PHARMA', '2024-07-20', 1, '9209876543', 'rahul.dubey@email.com',   'Male',   '2004-06-22', '67 Doctors Colony, Rewa, M.P.', 'Rakesh Dubey',  '9889012347', 'A+',  'OBC');

-- Sample fee records
INSERT INTO fees (student_id, total_fee, paid_amount, due_amount, status) VALUES
(1, 45000, 20000, 25000, 'Partial'),
(2, 35000, 35000, 0,     'Paid'),
(3, 50000, 0,     50000, 'Unpaid'),
(5, 40000, 10000, 30000, 'Partial'),
(6, 75000, 75000, 0,     'Paid'),
(7, 55000, 0,     55000, 'Unpaid'),
(8, 60000, 30000, 30000, 'Partial');

-- NOTE: fees_tables.sql is now merged here — no separate file needed.
