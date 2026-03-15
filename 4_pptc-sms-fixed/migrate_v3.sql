-- ============================================================
-- migrate_v3.sql — Add Student Profile Fields
-- Run this on your existing database (student_db)
-- Safe to run: uses IF NOT EXISTS / IGNORE
-- ============================================================
USE student_db;

ALTER TABLE students
    ADD COLUMN IF NOT EXISTS phone        VARCHAR(15)  DEFAULT NULL AFTER photo,
    ADD COLUMN IF NOT EXISTS email        VARCHAR(120) DEFAULT NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS gender       ENUM('Male','Female','Other') DEFAULT NULL AFTER email,
    ADD COLUMN IF NOT EXISTS dob          DATE         DEFAULT NULL AFTER gender,
    ADD COLUMN IF NOT EXISTS address      TEXT         DEFAULT NULL AFTER dob,
    ADD COLUMN IF NOT EXISTS guardian_name  VARCHAR(100) DEFAULT NULL AFTER address,
    ADD COLUMN IF NOT EXISTS guardian_phone VARCHAR(15)  DEFAULT NULL AFTER guardian_name,
    ADD COLUMN IF NOT EXISTS blood_group  VARCHAR(5)   DEFAULT NULL AFTER guardian_phone,
    ADD COLUMN IF NOT EXISTS category     ENUM('General','OBC','SC','ST','EWS') DEFAULT 'General' AFTER blood_group;

-- Update sample students with realistic data
UPDATE students SET
    phone='9876543210', email='aarav.sharma@email.com', gender='Male',
    dob='2005-03-12', address='123 Gandhi Nagar, Rewa, M.P.',
    guardian_name='Ramesh Sharma', guardian_phone='9812345670',
    blood_group='B+', category='General'
WHERE roll_no='BCA2024001';

UPDATE students SET
    phone='9865432109', email='priya.mishra@email.com', gender='Female',
    dob='2004-11-25', address='45 Nehru Colony, Rewa, M.P.',
    guardian_name='Suresh Mishra', guardian_phone='9823456781',
    blood_group='O+', category='OBC'
WHERE roll_no='BCOM2024002';

UPDATE students SET
    phone='9754321098', email='rohan.tiwari@email.com', gender='Male',
    dob='2004-07-08', address='78 Shastri Road, Satna, M.P.',
    guardian_name='Vijay Tiwari', guardian_phone='9834567892',
    blood_group='A+', category='General'
WHERE roll_no='BBA2024003';

UPDATE students SET
    phone='9643210987', email='kiran.patel@email.com', gender='Female',
    dob='2005-01-30', address='12 MG Road, Rewa, M.P.',
    guardian_name='Harish Patel', guardian_phone='9845678903',
    blood_group='AB+', category='OBC'
WHERE roll_no='BSC2024005';
