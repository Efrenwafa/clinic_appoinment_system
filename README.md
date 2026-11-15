# Secure Clinic Appointment and Monitoring System
**Course:** IT 107 – Information Assurance and Security 1  
**Institution:** Caraga State University (CSU) – Department of Information Technology  
**Student Name:** Efren Castillano  
**Student Email:** `efren.castillano@csucc.edu.ph`  
**GitHub Repository:** [https://github.com/Efrenwafa/clinic_appoinment_system.git](https://github.com/Efrenwafa/clinic_appoinment_system.git)  
**Assigned Seed Numbers:** $D_1 = 9$, $D_2 = 1$

---

## 1. Project Overview
The **Secure Clinic Appointment and Monitoring System** is an enterprise-grade web application engineered for outpatient clinical consultation scheduling, electronic triage, and proactive cybersecurity monitoring. Designed in strict compliance with the **Philippine Data Privacy Act of 2012 (RA 10173)** and the IT 107 Course Security Specifications, the application enforces zero-trust server-side validation, field-level encryption at rest, two-step authentication with out-of-band OTP delivery, progressive rate limiting, and an immutable security audit logging engine.

---

## 2. Seed-Derived System Limits & Thresholds ($D_1 = 9, D_2 = 1$)

All operational constraints and timer thresholds are mathematically calculated from student seed values $D_1 = 9$ and $D_2 = 1$ and strictly enforced on the server:

| Parameter | Handout Formula | Substitution | Computed Limit |
| :--- | :--- | :--- | :--- |
| **Minimum Username Length** | $6 + (D_1 \pmod 3)$ | $6 + (9 \pmod 3)$ | **6 characters** |
| **Maximum Username Length** | $16 + (D_2 \pmod 5)$ | $16 + (1 \pmod 5)$ | **17 characters** |
| **Minimum Password Length** | $8 + (D_2 \pmod 4)$ | $8 + (1 \pmod 4)$ | **9 characters** (max 64) |
| **Maximum Name Field Length** | $20 + D_2$ | $20 + 1$ | **21 characters** |
| **Forgot Password Threshold** | $2 + (D_1 \pmod 2)$ | $2 + (9 \pmod 2)$ | **3 consecutive failed logins** |
| **Lockout Tier 1 Duration** | $15 + D_1$ | $15 + 9$ | **24 seconds** |
| **Lockout Tier 2 Duration** | $30 + (2 \times D_1)$ | $30 + 18$ | **48 seconds** |
| **Lockout Tier 3 Duration** | $60 + (4 \times D_1)$ | $60 + 36$ | **96 seconds** |
| **OTP Expiration Window** | $3 + (D_2 \pmod 3)$ | $3 + (1 \pmod 3)$ | **4 minutes** |
| **Idle Session Timeout** | $5 + (D_1 \pmod 5)$ | $5 + (9 \pmod 5)$ | **9 minutes** (warning at 8 min) |
| **IP Blocking Threshold** | $8 + D_2$ | $8 + 1$ | **9 failures within 10 minutes** |
| **Assigned Security Questions** | Assigned by Seed | Handout Section 5 | **Q2, Q5, and Q7** |
| **Encrypted Sensitive Field** | Assigned by Proposal | Protection 13 | **Patient Contact Number (AES-256-CBC)** |

---

## 3. Implemented Security Protections (13/13 Mandatory Protections)

1. **S1 (Password Hashing):** Bcrypt with adaptive cost work factor (`PASSWORD_DEFAULT`), salting managed by PHP OpenSSL.
2. **S2 (Authentication Answer Hashing):** Security answers (Q2, Q5, Q7) normalized and one-way hashed before database storage.
3. **S3 (Server-Side Validation):** Zero reliance on client-side JS; exact Section 4.10 error messages enforced via PHP PDO.
4. **S4 (SQL Injection Defense):** 100% prepared statements (`PDO::ATTR_EMULATE_PREPARES => false`) with strict parameter binding.
5. **S5 (Direct URL Access Prevention):** State-machine enforcement and single-use 32-byte cryptographic tokens (`hash_equals`).
6. **S6 (Cross-Site Scripting - XSS Defense):** Universal context-aware output escaping via `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
7. **S7 (Password History):** Last 3 passwords stored in `password_history`; reuse prevented during password resets.
8. **S8 (Two-Step Authentication & OTP):** Cryptographically secure random 6-digit OTPs (`random_int`) with 4-min expiry and 3-attempt limit.
9. **S9 (Rate Limiting & Lockout):** Progressive 3-tier lockouts (24s, 48s, 96s) and IP-based blocking (9 failures in 10 min).
10. **S10 (Cross-Site Request Forgery - CSRF):** Per-session cryptographically random tokens validated on all state-changing POST requests.
11. **S11 (Immutable Security Logging):** Append-only audit table with zero `UPDATE` or `DELETE` capabilities across the application.
12. **S12 (Security Headers & Session Hardening):** `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `HttpOnly`, `Strict` cookie flags, 9-minute idle session timeout.
13. **S13 (Encryption at Rest):** `AES-256-CBC` encryption with unique 16-byte random IVs for sensitive patient contact numbers.

---

## 4. Repository Structure

```
clinic_appoinment_system/
├── .htaccess                     # Root security headers and directory listing protection
├── .gitignore                    # Strict exclusion of config.php, vendor/, logs/
├── config.sample.php             # Sanitized configuration template for deployment
├── composer.json                 # PHPMailer dependency definition
├── index.php                     # Portal landing page
├── register.php                  # Secure patient registration with CAPTCHA & consent
├── verify_otp.php                # Two-step OTP verification engine
├── login.php                     # Two-step login with lockout countdown & CAPTCHA
├── logout.php                    # Secure session termination and cache clearing
├── forgot_password.php           # Security question recovery workflow
├── change_password.php           # Token-protected password update & history check
├── appointments.php              # Patient consultation booking & appointment history
├── admin_dashboard.php           # Security monitoring dashboard (Admin only)
├── admin_api.php                 # Protected JSON API for dashboard metrics
├── api_check_username.php        # Real-time username availability endpoint
├── api_keep_alive.php            # Session heartbeat endpoint for idle management
├── css/
│   ├── style.css                 # Primary clinical application stylesheet
│   └── dashboard.css             # Security monitoring dashboard stylesheet
├── js/
│   ├── validate.js               # Client-side input validation and age calculation
│   ├── strength.js               # Password entropy and strength visualizer
│   ├── lockout.js                # Lockout countdown timer and form disabling
│   └── idle_timer.js             # 9-minute idle session modal and auto-logout
├── php/
│   ├── db.php                    # PDO connection with emulated prepares disabled
│   ├── security.php              # AES-256 encryption, IV generation, CSRF helpers
│   ├── auth.php                  # Authentication, progressive lockouts, OTP handling
│   ├── validation.php            # Exhaustive Section 4.10 validation rules
│   ├── mailer.php                # PHPMailer SMTP dispatcher for OTPs and alerts
│   ├── logger.php                # Append-only security audit logging engine
│   ├── captcha.php               # Single-use session CAPTCHA verification
│   ├── captcha_image.php         # Standalone GD image CAPTCHA generator
│   ├── header.php                # Universal header with authenticated navigation
│   └── footer.php                # Standardized system footer
├── database/
│   ├── schema.sql                # Complete 8-table relational MySQL schema
│   └── seed_data.sql             # 3 verified test accounts, appointments & logs
├── scripts/
│   ├── .htaccess                 # Direct web access denial (CLI only)
│   └── monitor.php               # Automated security CLI intrusion detection script
├── tests/
│   └── self_test.php             # Automated 8-point security verification suite
└── docs/
    ├── SYSTEM_PROPOSAL.md        # Official system proposal and clinic requirements
    ├── SEED_TABLE.md             # Formal seed calculation and threshold table
    ├── THREAT_MODEL.md           # Appendix B CIA threat mapping matrix
    ├── DESIGN_DECISIONS.md       # 10 "Your Call" architectural justifications
    ├── PRIVACY_NOTE.md           # RA 10173 compliance and data classification
    ├── ERROR_MESSAGE_MAPPING.md  # Complete rule-to-error string specification
    ├── DEVELOPMENT_JOURNAL.md    # 4-week development journal (Appendix C format)
    └── TEST_ACCOUNTS.md          # Account credentials and live defense scripts
```

---

## 5. Installation and Setup Guide (XAMPP on Windows)

### Prerequisites
- **XAMPP** (Apache, PHP 8.1+ or 8.2+, MySQL / MariaDB)
- **Composer** (for PHPMailer dependencies)
- **Web Browser** (Google Chrome, Microsoft Edge, or Mozilla Firefox)

### Step 1: Clone Repository
Clone the repository into your local XAMPP `htdocs` directory:
```powershell
cd C:\xampp\htdocs
git clone https://github.com/Efrenwafa/clinic_appoinment_system.git
cd clinic_appoinment_system
```

### Step 2: Install PHP Dependencies
```powershell
composer install
```

### Step 3: Configure Local Environment
Copy `config.sample.php` to `config.php`:
```powershell
Copy-Item config.sample.php config.php
```
Open `config.php` in your editor and configure your database and SMTP credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'clinic_appointment_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Generate a random 32-byte AES key:
define('AES_KEY', 'your-32-byte-hex-encoded-key');

// SMTP Settings (Gmail App Password):
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'efren.castillano@csucc.edu.ph');
define('SMTP_PASS', 'your-16-character-app-password');
```

### Step 4: Import Database Schema and Seed Data
In PowerShell or CMD:
```powershell
# Create database and import schema
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS clinic_appointment_db;"
& "C:\xampp\mysql\bin\mysql.exe" -u root clinic_appointment_db < database\schema.sql

# Import seed data (test accounts and initial records)
& "C:\xampp\mysql\bin\mysql.exe" -u root clinic_appointment_db < database\seed_data.sql
```

### Step 5: Start Apache and MySQL in XAMPP Control Panel
- Open **XAMPP Control Panel**.
- Start **Apache** and **MySQL**.
- Open your browser and navigate to: `http://localhost/clinic_appoinment_system`.

---

## 6. Running Security Tests & Monitoring Script

### Run the Automated Self-Test Suite
To verify that all 13 protections, cryptographic algorithms, error message strings, and database immutability constraints are functioning:
```powershell
& "C:\xampp\php\php.exe" tests/self_test.php
```
**Expected Output:**
```
[ PASS ] 1. Database encryption & hashing at rest
[ PASS ] 2. SQL Injection Resistance (' OR '1'='1)
[ PASS ] 3. XSS Escaping (htmlspecialchars)
[ PASS ] 4. Core Registration Error Messages match Handout Section 4.10 verbatim
[ PASS ] 5. Password history refusal (last 3 passwords)
[ PASS ] 6. Seeded Lockout Durations (D1 = 9)
[ PASS ] 7. Seed Parameter Integrity (D1 = 9, D2 = 1)
[ PASS ] 8. Append-only activity log immutability (Protection S11)
Self-Test Summary: 8 Passed, 0 Failed
```

### Run the CLI Security Monitoring Script
To run the background intrusion detection engine:
```powershell
& "C:\xampp\php\php.exe" scripts/monitor.php
```
This script scans for:
- $\ge 5$ rapid consecutive failed logins within 5 minutes.
- Administrator logins occurring during off-hours (10:00 PM – 6:00 AM).
- Active IP blocks requiring review.
- Multi-account credential stuffing from a single IP address.

---

## 7. Verified Demo Accounts (For Presentation Defense)

| Username | Password | Role | Description |
| :--- | :--- | :--- | :--- |
| `admin_monitor` | `AdminPassword123!` | Administrator | Accesses `admin_dashboard.php` and reviews security flags and logs. |
| `patient_juan` | `JuanPassword123!` | Patient | Books appointments, checks consultation statuses on `appointments.php`. |
| `patient_maria` | `MariaPassword123!` | Patient | Used for demonstrating Forgot Password, Security Questions (Q2/Q5/Q7), and OTP recovery. |

*Detailed scenario-by-scenario presentation steps are provided in [`docs/TEST_ACCOUNTS.md`](docs/TEST_ACCOUNTS.md).*

---

## 8. License & Academic Integrity Statement
This project is developed as the Final Laboratory Project for **IT 107 (Information Assurance and Security 1)** at Caraga State University. All rights reserved. Code is subject to university academic integrity standards.
