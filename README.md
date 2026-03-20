[README (2).md](https://github.com/user-attachments/files/26135287/README.2.md)
# 🎓 PPTC Student Management System (SMS)

> **Pentium Point Technical College — Student Management System**
> Built with PHP + MySQL | Admin Panel | Fee Management | Email Automation | Chatbot

---

## 📋 Table of Contents

1. [About the Project](#about-the-project)
2. [Features](#features)
3. [Tech Stack](#tech-stack)
4. [Project Structure](#project-structure)
5. [Requirements](#requirements)
6. [Installation & Setup](#installation--setup)
7. [Database Setup](#database-setup)
8. [Changing the Admin Password](#changing-the-admin-password)
9. [Email (SMTP) Configuration](#email-smtp-configuration)
10. [Default Login Credentials](#default-login-credentials)
11. [Running on Localhost](#running-on-localhost)
12. [Running on Live Hosting (InfinityFree / cPanel)](#running-on-live-hosting)
13. [Common Issues & Fixes](#common-issues--fixes)
14. [Screenshots](#screenshots)
15. [License](#license)

---

## About the Project

This is a **web-based Student Management System** for Pentium Point Technical College (PPTC), Rewa, M.P. It allows college admins to manage students, track fee payments, send automated emails, generate reports, and more — all from a secure admin panel.

---

## Features

| Feature | Description |
|---|---|
| 🔐 Secure Admin Login | Password hashed with `password_hash()`, CSRF protection, session timeout (2 hrs) |
| 👨‍🎓 Student CRUD | Add, View, Update, Delete student records with photo upload |
| 💰 Fee Management | Record fee payments, track due amounts, generate receipts |
| 📊 Reports & Insights | Student reports, fee reports, print-ready pages |
| 📧 Email Automation | Automated emails via Gmail SMTP (PHPMailer) |
| ⚠️ Fee Warnings | Auto-alerts for students with fee dues above threshold |
| 🤖 Chatbot | Built-in chatbot powered by Claude AI API |
| 📝 Activity Log | Tracks every admin action (login, insert, update, delete) |
| 🔍 Search & Filter | AJAX-based live search and course/status filters |
| 🖨️ Print Reports | Printable student and fee reports |
| 📱 Responsive UI | Works on desktop and mobile |

---

## Tech Stack

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+ / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (AJAX)
- **Email:** PHPMailer + Gmail SMTP
- **Server:** Apache (XAMPP / WAMP / LAMP / cPanel)

---

## Project Structure

```
4_pptc-sms-fixed/
│
├── config/
│   ├── db.php                  ← 🔧 DATABASE CONNECTION (edit this!)
│   ├── auth_check.php          ← Session/auth guard
│   ├── email_helper.php        ← 🔧 SMTP EMAIL CONFIG (edit this!)
│   ├── activity_helper.php     ← Activity log functions
│   ├── courses_helper.php      ← Course list helpers
│   ├── csrf_helper.php         ← CSRF token helpers
│   ├── photo_helper.php        ← Photo upload helpers
│   └── phpmailer/              ← PHPMailer library files
│
├── assets/
│   ├── css/style.css           ← Main stylesheet
│   ├── js/script.js            ← Frontend JS
│   └── img/                    ← College logos & images
│
├── includes/
│   ├── header.php              ← Navbar/header include
│   └── footer.php              ← Footer include
│
├── index.php                   ← Dashboard (home)
├── login.php                   ← 🔧 ADMIN LOGIN + PASSWORD HASH
├── logout.php                  ← Logout
├── insert.php                  ← Add new student (form)
├── process_insert.php          ← Add student (backend)
├── update.php                  ← Edit student (form)
├── process_update.php          ← Edit student (backend)
├── delete.php                  ← Delete student (form)
├── process_delete.php          ← Delete student (backend)
├── view.php                    ← View single student
├── view_all.php                ← View all students
├── fees.php                    ← Fee management
├── process_fee.php             ← Process fee payment
├── fee_report.php              ← Fee report
├── student_report.php          ← Student report
├── print_report.php            ← Print-ready report
├── receipt.php                 ← Payment receipt
├── insights.php                ← Analytics/insights
├── warnings.php                ← Fee warning alerts
├── activity.php                ← Activity log viewer
├── chatbot.php                 ← Chatbot UI
├── chatbot_api.php             ← Chatbot API handler
├── payment_success.php         ← Payment success page
│
├── ajax_activity.php           ← AJAX: activity log
├── ajax_filter.php             ← AJAX: filter students
├── ajax_name_search.php        ← AJAX: name search
├── ajax_search.php             ← AJAX: general search
│
├── database.sql                ← ✅ MAIN DB SETUP FILE (run this!)
├── migrate_v3.sql              ← Migration from v3
└── migrate_v4.sql              ← Migration from v4
```

---

## Requirements

Before you begin, make sure you have:

- ✅ **PHP 7.4 or higher**
- ✅ **MySQL 5.7+ or MariaDB**
- ✅ **Apache web server** (XAMPP / WAMP / LAMP)
- ✅ A **Gmail account** (for email features — optional)

---

## Installation & Setup

### Step 1 — Download & Place Files

1. Download/extract the project zip.
2. Copy the `4_pptc-sms-fixed` folder into your server root:
   - **XAMPP (Windows):** `C:/xampp/htdocs/pptc-sms/`
   - **WAMP (Windows):** `C:/wamp64/www/pptc-sms/`
   - **Linux/Mac (LAMP):** `/var/www/html/pptc-sms/`

### Step 2 — Set Up the Database

See [Database Setup](#database-setup) below.

### Step 3 — Configure Database Connection

Open `config/db.php` and update your credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // ← Your MySQL username
define('DB_PASS', '');            // ← Your MySQL password (empty for default XAMPP)
define('DB_NAME', 'student_db');  // ← Leave this as-is (or change if you renamed the DB)
```

### Step 4 — Start the Server

- Open **XAMPP Control Panel** → Start **Apache** and **MySQL**
- Visit: `http://localhost/pptc-sms/`

---

## Database Setup

You only need to run **one file** to set up the entire database.

### Option A — Using phpMyAdmin (Easiest)

1. Open your browser → go to `http://localhost/phpmyadmin`
2. Click **"New"** in the left sidebar to create a database
   - Name it: `student_db`
   - Collation: `utf8mb4_unicode_ci`
   - Click **Create**
3. Select `student_db` from the left sidebar
4. Click the **"Import"** tab at the top
5. Click **"Choose File"** → select `database.sql` from the project folder
6. Scroll down → click **"Import"** (Go)
7. ✅ Done! All tables and sample data are now created.

### Option B — Using MySQL Command Line

```bash
mysql -u root -p
```
```sql
CREATE DATABASE student_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_db;
SOURCE /path/to/4_pptc-sms-fixed/database.sql;
```

### What Gets Created

| Table | Description |
|---|---|
| `students` | All student records |
| `courses` | 30 courses (BCA, BBA, LLB, MBA, D.Pharma, etc.) |
| `fees` | Fee summary per student |
| `fee_payments` | Individual payment transactions |

> **Sample data** (8 students + fee records) is included automatically.

---

## Changing the Admin Password

The admin password is **NOT stored in the database** — it is hardcoded in `login.php` as a **bcrypt hash** for security.

### Step 1 — Generate a New Hash

Run this one-liner in any PHP file (e.g., create a temp file `gen_hash.php`):

```php
<?php
echo password_hash('your_new_password_here', PASSWORD_DEFAULT);
?>
```

Visit `http://localhost/pptc-sms/gen_hash.php` in your browser.
Copy the hash that appears (it will look like `$2y$10$xxxxx...`).

**Delete `gen_hash.php` immediately after!** Never leave it on a live server.

### Step 2 — Paste the Hash into login.php

Open `login.php` and find these lines near the top:

```php
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$10$sKz17YQOeotTj3tDvZrqOee57QS5q0i5JSItFPv1U1pWwlKNcFzXW'); // pptc@2024
define('ADMIN_EMAIL',    'pptcrewa@rediffmail.com');
```

Replace the hash string with your new one:

```php
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$10$YOUR_NEW_HASH_HERE');
define('ADMIN_EMAIL',    'your_email@example.com');  // optional: update email too
```

### Step 3 — Save & Test

Save `login.php`, then log in with your new password. ✅

> ⚠️ **Never store plain-text passwords.** Always use `password_hash()` and `password_verify()` like this project already does.

---

## Email (SMTP) Configuration

The system sends automated emails using **Gmail SMTP via PHPMailer**.

Open `config/email_helper.php` and update these lines:

```php
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_SECURE',   'tls');
define('SMTP_USERNAME', 'your_gmail@gmail.com');   // ← Your Gmail address
define('SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx');    // ← Your Gmail App Password (NOT your Gmail login password)

define('EMAIL_FROM',      'your_gmail@gmail.com');
define('EMAIL_FROM_NAME', 'Your College Name');
define('EMAIL_REPLY_TO',  'your_gmail@gmail.com');
```

### How to Get a Gmail App Password

1. Go to your Google Account → **Security**
2. Enable **2-Step Verification** (required)
3. Go to **Security → App Passwords**
4. Select App: **Mail** | Device: **Other (Custom name)** → type "PPTC SMS"
5. Click **Generate** → copy the 16-character password
6. Paste it into `SMTP_PASSWORD` above (with or without spaces, both work)

> ⚠️ Your regular Gmail password will NOT work. You must use an App Password.

---

## Default Login Credentials

| Field | Value |
|---|---|
| **URL** | `http://localhost/pptc-sms/login.php` |
| **Username** | `admin` |
| **Password** | `pptc@2024` |

> 🔴 **Change the default password immediately** before deploying to any live/shared server. See [Changing the Admin Password](#changing-the-admin-password).

---

## Running on Localhost

```
1. Start XAMPP → Apache + MySQL
2. Place project in: C:/xampp/htdocs/pptc-sms/
3. Import database.sql via phpMyAdmin
4. Open: http://localhost/pptc-sms/
5. Login with: admin / pptc@2024
```

---

## Running on Live Hosting

### InfinityFree / cPanel / Any Shared Host

1. **Upload files** via File Manager or FTP to `public_html/pptc-sms/`
2. **Create Database** in cPanel → MySQL Databases
   - Create a new database (e.g., `epiz_xxx_student_db`)
   - Create a new user + password
   - Assign user to database with **All Privileges**
3. **Import database.sql** via phpMyAdmin in cPanel
4. **Update `config/db.php`**:
   ```php
   define('DB_HOST', 'localhost');         // usually stays localhost
   define('DB_USER', 'epiz_xxx_myuser');   // ← your cPanel DB username
   define('DB_PASS', 'your_db_password');  // ← your cPanel DB password
   define('DB_NAME', 'epiz_xxx_student_db'); // ← your cPanel DB name
   ```
5. Visit `https://yourdomain.com/pptc-sms/` ✅

---

## Common Issues & Fixes

| Problem | Fix |
|---|---|
| **"Database connection failed"** | Check `config/db.php` — wrong username/password/DB name |
| **Blank page after login** | Check PHP error log; likely a missing `session_start()` or wrong file path |
| **"Invalid username or password"** | You may have changed the password in `login.php` incorrectly. Re-generate the hash. |
| **Emails not sending** | Gmail App Password is wrong or 2FA not enabled on Gmail |
| **Photo upload not working** | Ensure `assets/uploads/students/` folder exists and has **write permissions** (`chmod 755`) |
| **CSRF error on form submit** | Clear browser cookies/session and try again |
| **White screen on live host** | Add `ini_set('display_errors', 1);` temporarily to `index.php` to see the error |
| **Migration needed from old version** | Run `migrate_v3.sql` or `migrate_v4.sql` — do NOT run `database.sql` again (it drops tables!) |

---

## Security Notes

- ✅ Passwords stored as bcrypt hashes (`password_hash`)
- ✅ CSRF tokens on all forms
- ✅ Session timeout after 2 hours of inactivity
- ✅ Session ID regenerated on login (prevents session fixation)
- ✅ `mysqli_real_escape_string` / prepared statements used
- ⚠️ Change default admin credentials before going live
- ⚠️ Remove any test files (like `gen_hash.php`) from the server
- ⚠️ Do not expose `config/` folder — add `.htaccess` if needed

---

## License

This project was built for **Pentium Point Technical College, Rewa (M.P.)**.
For internal/educational use only.
