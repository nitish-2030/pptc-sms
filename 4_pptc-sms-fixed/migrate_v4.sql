-- ============================================================
-- migrate_v4.sql — Feature 1: Email Automation + Activity Logs
-- Run AFTER migrate_v3.sql
-- Safe: uses IF NOT EXISTS / ADD COLUMN IF NOT EXISTS
-- ============================================================
USE student_db;   -- localhost. Change to if0_41251338_student_db for InfinityFree

-- ── 1. Add warning_sent_at to students ──────────────────────
ALTER TABLE students
    ADD COLUMN IF NOT EXISTS warning_sent_at DATETIME DEFAULT NULL AFTER category;

-- ── 2. email_logs table ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS email_logs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    student_id   INT DEFAULT NULL,
    to_email     VARCHAR(120) DEFAULT NULL,
    student_name VARCHAR(100) DEFAULT NULL,
    subject      VARCHAR(255) NOT NULL,
    trigger_type ENUM('welcome','updated','deactivated','payment_success','warning','manual') NOT NULL,
    status       ENUM('sent','failed','no_email') DEFAULT 'sent',
    fail_reason  VARCHAR(255) DEFAULT NULL,
    sent_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 3. activity_logs table ──────────────────────────────────
CREATE TABLE IF NOT EXISTS activity_logs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    category     ENUM('student','admin','email','system') DEFAULT 'system',
    action       VARCHAR(255) NOT NULL,
    detail       TEXT DEFAULT NULL,
    student_id   INT DEFAULT NULL,
    student_name VARCHAR(100) DEFAULT NULL,
    icon         VARCHAR(10) DEFAULT '📋',
    color        VARCHAR(10) DEFAULT 'blue',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 4. Course fee table ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS course_fees (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(30) NOT NULL UNIQUE,
    annual_fee  DECIMAL(10,2) NOT NULL DEFAULT 25000.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 5. Seed course fees (all > 25000) ───────────────────────
INSERT INTO course_fees (course_code, annual_fee) VALUES
('BCA',      48000.00),
('BSC_PSM',  27000.00),
('BSC_PMCs', 27000.00),
('BSC_PCM',  28000.00),
('BSC_CBZ',  27500.00),
('BSC_BTC',  32000.00),
('BSC_BT',   32000.00),
('BCOM',     26000.00),
('BCOM_CA',  29000.00),
('BBA',      42000.00),
('BA',       25500.00),
('BA_CA',    28000.00),
('MBA',      65000.00),
('MSW',      35000.00),
('MA_HIS',   27000.00),
('MA_ECO',   27000.00),
('BALLB',    55000.00),
('LLB',      45000.00),
('LLM',      38000.00),
('BBA_LLB',  58000.00),
('BCOM_LLB', 55000.00),
('D_PHARMA', 52000.00),
('B_PHARMA', 68000.00),
('PGDCA',    26000.00)
ON DUPLICATE KEY UPDATE annual_fee = VALUES(annual_fee);

