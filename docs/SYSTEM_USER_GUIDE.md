# SECURE CLINIC APPOINTMENT AND MONITORING SYSTEM
## Comprehensive Step-by-Step User & Demonstration Guide

**Course:** IT 107 – Information Assurance and Security 1  
**Institution:** Caraga State University (CSU) – Department of Information Technology  
**Student Name:** Efren Castillano  
**Email:** `efren.castillano@csucc.edu.ph`  
**Assigned Seed Numbers:** $D_1 = 9$, $D_2 = 1$  
**GitHub Repository:** [https://github.com/Efrenwafa/clinic_appoinment_system.git](https://github.com/Efrenwafa/clinic_appoinment_system.git)

---

## Table of Contents
1. [Pre-Flight Checklist & System Startup](#1-pre-flight-checklist--system-startup)
2. [Quick Reference Demo Credentials](#2-quick-reference-demo-credentials)
3. [Module 1: Portal Navigation & Landing Page](#3-module-1-portal-navigation--landing-page)
4. [Module 2: Patient Registration Flow](#4-module-2-patient-registration-flow)
5. [Module 3: Registration Email OTP Verification](#5-module-3-registration-email-otp-verification)
6. [Module 4: Patient Two-Step Authentication (Login)](#6-module-4-patient-two-step-authentication-login)
7. [Module 5: Patient Appointment Management](#7-module-5-patient-appointment-management)
8. [Module 6: Idle Session Timeout & Auto-Logout](#8-module-6-idle-session-timeout--auto-logout)
9. [Module 7: Account Recovery & Password History](#9-module-7-account-recovery--password-history)
10. [Module 8: Progressive Account Lockout & Rate Limiting](#10-module-8-progressive-account-lockout--rate-limiting)
11. [Module 9: Administrator Security Monitoring Dashboard](#11-module-9-administrator-security-monitoring-dashboard)
12. [Module 10: Automated CLI Intrusion Detection Script](#12-module-10-automated-cli-intrusion-detection-script)
13. [Module 11: Automated Security Self-Test Suite](#13-module-11-automated-security-self-test-suite)
14. [Troubleshooting & Frequently Asked Questions](#14-troubleshooting--frequently-asked-questions)

---

## 1. Pre-Flight Checklist & System Startup

Before launching the web portal or presenting to your instructor, ensure the local environment is running:

### Step 1.1: Launch XAMPP Services
1. Open the **XAMPP Control Panel** on your Windows workstation.
2. Click **Start** on **Apache** (Default Port: `80` or `8080`).
3. Click **Start** on **MySQL** (Default Port: `3306`).
4. Ensure both services display a green indicator status.

### Step 1.2: Verify MySQL Database Initialization
Open PowerShell or Command Prompt to confirm the database tables and seed records are active:
```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "USE clinic_appointment_db; SHOW TABLES;"
```
You should see 8 relational tables:
`activity_logs`, `appointments`, `authentication_answers`, `ip_blocks`, `otps`, `password_history`, `security_flags`, and `users`.

### Step 1.3: Access the Portal URL
Open Google Chrome, Microsoft Edge, or Mozilla Firefox and navigate to:
```
http://localhost/clinic_appoinment_system/
```

---

## 2. Quick Reference Demo Credentials

The system comes pre-seeded with 3 active, fully verified test accounts. All emails route to your real Gmail inbox (`efren.castillano@csucc.edu.ph`) using Gmail plus-addressing, allowing live verification of OTPs and alert notifications during demonstration.

| Account Role | Username | Plaintext Password | Registered Email | Contact Number (Encrypted AES-256) |
| :--- | :--- | :--- | :--- | :--- |
| **Clinic Administrator** | `admin_monitor` | `AdminPassword123!` | `efren.castillano@csucc.edu.ph` | `09123456789` |
| **Patient 1 (Juan)** | `patient_juan` | `JuanPassword123!` | `efren.castillano+juan@csucc.edu.ph` | `09876543210` |
| **Patient 2 (Maria)** | `patient_maria` | `MariaPassword123!` | `efren.castillano+maria@csucc.edu.ph` | `09234567891` |

### Assigned Security Questions & Answers (Seed: $D_1=9, D_2=1$)
- **Question 2 (Q2):** *"What was the name of your first elementary school?"*  
  **Answer:** `bantay`
- **Question 5 (Q5):** *"What is the name of the street you grew up on?"*  
  **Answer:** `rizal street`
- **Question 7 (Q7):** *"What was the make and model of your first car?"*  
  **Answer:** `ampayon central elementary school`

---

## 3. Module 1: Portal Navigation & Landing Page

### Purpose
Provides a secure landing portal welcoming patients and healthcare staff while establishing session state and enforcing HTTP security headers (`X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`).

### Step-by-Step Walkthrough:
1. Open `http://localhost/clinic_appoinment_system/index.php`.
2. Notice the navigation layout:
   - **System Title Banner:** *"Secure Clinic Appointment and Monitoring System"* is displayed strictly at the top.
   - **Top-Right Navigation:** For unauthenticated visitors, only **"Home"**, **"Register"**, and **"Sign In"** links appear. **Logout is never displayed to a guest**.
   - **Hero Actions:** Two primary buttons: **"Book an Appointment"** (routes to sign in or appointment booking) and **"Create an Account"**.
   - **Security Protections Showcase:** Highlighting AES-256 field encryption, two-step OTP authentication, and RA 10173 data privacy compliance.
   - **Footer:** Standardized copyright statement.

---

## 4. Module 2: Patient Registration Flow

### Purpose
Allows new patients to create an account while strictly enforcing Section 4 & 5 validation rules, calculating real-time age, generating a CAPTCHA challenge, and capturing informed consent under the Philippine Data Privacy Act of 2012 (RA 10173).

### Step-by-Step Walkthrough:
1. Click **"Register"** in the top navigation or navigate to `register.php`.
2. **Fill out the Core Registration Fields:**
   - **ID Number:** Format must be `YYYY-NNNN` (*e.g., `2026-9001`*). Duplicate IDs are rejected.
   - **First Name:** Letters and spaces only, Title Case (*e.g., `Gabriel`*). Max $20 + D_2 = 21$ characters. Numbers, special characters, and ALL CAPS are blocked.
   - **Middle Name:** Optional. If entered, Title Case is required (*e.g., `Cruz`*).
   - **Family Name:** Required, Title Case (*e.g., `Mendoza`*). Max 21 characters.
   - **Name Extension:** Optional dropdown (*`Jr.`, `Sr.`, `II`, `III`, `IV`*).
   - **Birthdate:** Select a date at least 18 years in the past.
   - **Age:** Automatically calculated by client-side JavaScript (`js/validate.js`) upon selecting birthdate and validated independently on the server.
   - **Address Fields:** Purok/Street, Barangay (*Title Case*), Municipality/City (*Title Case*), Province (*Title Case*), Country (*Title Case*).
   - **ZIP Code:** Exactly 4 numeric digits (*e.g., `8600`*).
   - **Email Address:** Valid email address (*e.g., `efren.castillano+demo@csucc.edu.ph`*).
   - **Username:** Starts with a letter, alphanumeric only. Length must be between $6$ and $17$ characters based on seed ($6 + (9 \pmod 3) = 6$ and $16 + (1 \pmod 5) = 17$). Live availability check verifies uniqueness.
   - **Password:** Minimum $8 + (1 \pmod 4) = 9$ characters. Live password strength visualizer (`js/strength.js`) checks uppercase, lowercase, number, and special character.
   - **Confirm Password:** Must match password exactly.
3. **Fill out the 4 Clinic-Specific Registration Fields:**
   - **Contact Number (Required):** Exactly 11 digits starting with `09` (*e.g., `09171234567`*). Encrypted at rest using AES-256-CBC.
   - **Patient Type (Required Dropdown):** Choose `'New Patient'` or `'Returning Patient'`.
   - **Preferred Clinic Department (Required Dropdown):** Choose one configured specialty (*e.g., `General Medicine`, `Dental`, `Pediatrics`, `Dermatology`, `Cardiology`*).
   - **Emergency Contact Number (Optional):** If supplied, must be 11 digits starting with `09`.
4. **Answer the 3 Assigned Security Questions (Section 5):**
   - **Question 2:** Name of first elementary school (*e.g., `Ampayon Central School`*).
   - **Question 5:** Street you grew up on (*e.g., `Rizal Street`*).
   - **Question 7:** Make and model of first car (*e.g., `Toyota Tamaraw`*).
5. **Security Challenge & Consent:**
   - **GD Image CAPTCHA:** Type the characters displayed in the anti-bot CAPTCHA box.
   - **Privacy Notice Checkbox:** Review the notice and check *"I agree to the collection and processing of my personal health information in accordance with RA 10173"*.
6. Click **"Complete Registration"**.
7. **Expected Result:**
   - The server validates all constraints.
   - Password and security answers are hashed using Bcrypt.
   - Contact number is encrypted using AES-256 with a unique 16-byte random IV.
   - A 6-digit OTP is generated using `random_int()`, hashed into `otps`, and dispatched to your email.
   - Browser redirects to `verify_otp.php`.

---

## 5. Module 3: Registration Email OTP Verification

### Purpose
Guarantees that an account cannot be logged into until the owner proves ownership of the registered email address.

### Step-by-Step Walkthrough:
1. The browser lands on `verify_otp.php`.
2. Notice the security indicators:
   - Purpose displayed: *"Email Verification for Registration"*.
   - Timer countdown showing the **4-minute seed expiration window** ($3 + (1 \pmod 3) = 4\text{ minutes}$).
   - Attempt counter showing *"Attempt 1 of 3"*.
3. Open your Gmail inbox (`efren.castillano@csucc.edu.ph`).
4. Open the email titled: **"Your Verification Code - Clinic Appointment System"**.
5. Copy the 6-digit code.
6. Return to `verify_otp.php`, enter the 6 digits, and click **"Verify Code"**.
7. **Expected Result:**
   - The server verifies the OTP hash against `otps`.
   - The user's account is activated (`users.is_verified = 1`).
   - The single-use OTP is marked as used (`is_used = 1`).
   - A success banner appears: *"Email verified successfully! You may now sign in."*
   - Browser redirects to `login.php`.

---

## 6. Module 4: Patient Two-Step Authentication (Login)

### Purpose
Implements multi-factor authentication requiring username, password, CAPTCHA verification, and a one-time out-of-band email verification code before establishing a session.

### Step-by-Step Walkthrough:
1. Navigate to `login.php`.
2. **Step 1: Primary Authentication:**
   - **Username:** `patient_juan`
   - **Password:** `JuanPassword123!`
   - **CAPTCHA:** Enter the code shown in the CAPTCHA box.
   - Click **"Sign In"**.
3. **Expected Result (Step 1):**
   - The system checks username and verifies password hash using `password_verify()`.
   - **Session is NOT established yet** (defense against premature session creation).
   - A new 6-digit Login OTP is generated, hashed into `otps`, and emailed to `efren.castillano+juan@csucc.edu.ph`.
   - Browser redirects to `verify_otp.php`.
4. **Step 2: Second Factor Verification:**
   - Check your Gmail inbox for the **"Your Login Verification Code"** email.
   - Enter the 6-digit OTP into `verify_otp.php` and submit.
5. **Expected Result (Step 2):**
   - Server validates the OTP hash and verifies expiration.
   - Session ID is regenerated (`session_regenerate_id(true)`) to prevent session fixation attacks.
   - Role-based redirect sends Patient Juan to `appointments.php`.
   - The navigation header now displays: **"Hello, Juan!"**, **"My Appointments"**, and **"Sign Out"**.

---

## 7. Module 5: Patient Appointment Management

### Purpose
Provides self-service outpatient consultation booking, appointment tracking, and on-the-fly decryption of patient contact details, while strictly enforcing Indirect Object Reference (IDOR) prevention.

### Step-by-Step Walkthrough:
1. Land on `appointments.php`.
2. **Review Patient Information Card:**
   - Shows Juan's full name, student ID, and registered email.
   - **Decrypted Contact Number:** Notice that `09876543210` is displayed legibly. In MySQL, this value is stored as an encrypted AES-256 base64 blob (`users.contact_number_encrypted`). The server decrypted it on the fly using the server-side key and per-record IV.
3. **Review Existing Appointments Table:**
   - Displays all appointments registered to Juan Carlo Dela Cruz.
   - Columns: Department, Date, Time, Chief Complaint / Reason, Status badge (*Confirmed*, *Pending*, *Completed*, *Cancelled*).
4. **Book a New Appointment:**
   - **Department:** Select `'General Medicine'` (or `'Dental'`, `'Cardiology'`, `'Dermatology'`, `'Pediatrics'`).
   - **Preferred Date:** Select tomorrow or a future date.
   - **Preferred Time:** Select clinic time (*e.g., `10:00 AM`*).
   - **Reason for Consultation:** Type clinical reason (*e.g., "Follow-up physical checkup and medical clearance for university varsity"*).
   - Click **"Submit Appointment Request"**.
5. **Expected Result:**
   - Input is validated and saved to `appointments`.
   - The new consultation immediately appears in the appointments table with a yellow `Pending` badge.
   - An audit log event (`APPOINTMENT_REQUESTED`) is recorded in `activity_logs`.
6. **Anti-IDOR Verification (Instructor Defense):**
   - Point out to your instructor that the SQL query is strictly scoped: `SELECT * FROM appointments WHERE patient_id = :session_user_id`.
   - Even if Juan tampers with browser developer tools or URL query parameters, he can never read or modify Maria's appointments.

---

## 8. Module 6: Idle Session Timeout & Auto-Logout

### Purpose
Protects patient health information on unattended hospital or computer laboratory terminals by terminating inactive sessions after the seeded timeout window ($5 + (9 \pmod 5) = 9\text{ minutes}$).

### Step-by-Step Walkthrough:
1. Stay on any authenticated page (*e.g., `appointments.php` or `admin_dashboard.php`*).
2. The client script `js/idle_timer.js` monitors user activity (mouse movement, keypresses, clicks, touches).
3. **Warning Phase (At 8 minutes):**
   - An accessible modal overlay pops up: *"Session Expiring Soon: You have been inactive for 8 minutes. You will be logged out in 60 seconds."*
   - Two buttons are provided: **"Stay Logged In"** and **"Log Out Now"**.
   - If the user clicks "Stay Logged In", an asynchronous keep-alive ping is sent to `api_keep_alive.php`, resetting both the client timer and the PHP session timestamp.
4. **Expiration Phase (At 9 minutes):**
   - If no interaction occurs, the browser is redirected to: `logout.php?reason=timeout`.
   - The PHP session is completely wiped (`session_unset()`, `session_destroy()`), and the session cookie is deleted with an expired timestamp.
   - Browser lands on `login.php?timeout=1` displaying an informational notice: *"Your session expired due to 9 minutes of inactivity. Please log in again."*

---

## 9. Module 7: Account Recovery & Password History

### Purpose
Enables legitimate users to recover access without administrator intervention using their assigned security questions, while preventing the reuse of recent passwords (Protection 7).

### Step-by-Step Walkthrough:
1. Go to `login.php`.
2. Click the **"Forgot Password?"** link (or trigger it after 3 failed login attempts).
3. **Step 1: Identity Challenge:**
   - **Username:** `patient_maria`
   - **CAPTCHA:** Solve the image CAPTCHA.
   - Click **"Continue"**.
4. **Step 2: Security Question Verification:**
   - The system displays the security question options assigned to the user (Q2, Q5, or Q7).
   - Select **Question 2**: *"What was the name of your first elementary school?"*
   - Type the answer: `bantay` *(case-insensitive)*.
   - Click **"Verify Answer"**.
5. **Expected Result (Step 2):**
   - The server normalizes the answer and verifies it against the Bcrypt hash in `authentication_answers`.
   - A 6-digit **Password Reset OTP** is dispatched to `efren.castillano+maria@csucc.edu.ph`.
6. **Step 3: Reset OTP Verification:**
   - Retrieve the 6-digit code from your email and enter it on `verify_otp.php`.
   - Submit the code.
7. **Step 4: Token-Protected Password Reset (`change_password.php`):**
   - The server generates a random 32-byte cryptographic token (`bin2hex(random_bytes(32))`) and redirects to `change_password.php?token=<token>`.
   - **Demonstrate Protection S5 (Direct URL Protection):** Copy the URL and try opening it in an Incognito window without the session. The server rejects direct access and redirects to `login.php`.
8. **Step 5: Demonstrate Password History Prevention (Protection 7):**
   - Attempt to reuse Maria's previous password: `MariaPassword123!`.
   - Click **"Update Password"**.
   - **Rejection Message:** The server queries `password_history` for the last 3 passwords and rejects the submission with the exact error:  
     *"You cannot reuse any of your last 3 passwords. Please choose a different password."*
9. **Step 6: Submit a New Valid Password:**
   - Enter a brand new password: `NewMariaSecret!2026`.
   - Confirm the password and submit.
10. **Expected Result:**
    - The new password is saved as a Bcrypt hash in `users` and recorded in `password_history`.
    - Any existing active sessions for this account are invalidated.
    - A **Security Alert Email** is sent to your inbox informing the user of the successful password update with the event timestamp, IP address, and browser information.

---

## 10. Module 8: Progressive Account Lockout & Rate Limiting

### Purpose
Thwarts automated brute-force attacks and credential stuffing by enforcing escalating lockout tiers based on student seed $D_1 = 9$ and blocking abusive IP addresses.

### Step-by-Step Walkthrough:
1. Open a new Incognito / Private browser window.
2. Navigate to `login.php`.
3. Enter **Username:** `patient_juan`.
4. Enter an **incorrect password** (*e.g., `WrongPass123!`*).
5. Solve the image CAPTCHA and click **"Sign In"**.
6. Repeat this failure 3 consecutive times:
   - **Attempt 1:** Generic error: *"Invalid username or password."* (Failure count = 1).
   - **Attempt 2:** Generic error: *"Invalid username or password."* (Failure count = 2).
   - **Attempt 3:** **Lockout Tier 1 Activated!** ($15 + D_1 = 15 + 9 = \mathbf{24\text{ seconds}}$).
7. **Show the Instructor What Happens on Failure 3:**
   - A live countdown timer appears on screen: *"Account is locked. Please wait 24 seconds."*
   - The **"Sign In"** button is disabled.
   - The **"Register"** navigation link is disabled during the lockout.
   - The **"Forgot Password?"** link dynamically appears ($2 + (9 \pmod 2) = 3$).
   - Check your Gmail inbox: A **Lockout Security Alert Email** has arrived with the subject: *"Security Alert: Account Lockout - Clinic Appointment System"*.
   - Refresh the page: explain that refreshing cannot bypass the lockout because the server enforces `lockout_until` in MySQL.
8. **Subsequent Lockout Tiers:**
   - **Tier 2 (6 failures):** $30 + 2(D_1) = 30 + 18 = \mathbf{48\text{ seconds}}$ lockout and second alert email.
   - **Tier 3 (9 failures):** $60 + 4(D_1) = 60 + 36 = \mathbf{96\text{ seconds}}$ lockout and third alert email.
9. **IP-Level Blocking Defense:**
   - If 9 failures occur from the same IP address within 10 minutes ($8 + D_2 = 8 + 1 = 9$), the server inserts an IP ban into `ip_blocks` for 10 minutes.
   - All subsequent requests from that IP address are blocked with:  
     *"Access temporarily blocked due to repeated security failures. Please contact the clinic administrator."*

---

## 11. Module 9: Administrator Security Monitoring Dashboard

### Purpose
Provides authorized clinic administrators with a centralized, read-only operational dashboard to inspect security events, identify brute-force patterns, review active blocks, and audit immutable system logs.

### Step-by-Step Walkthrough:
1. Navigate to `login.php`.
2. Enter the administrator credentials:
   - **Username:** `admin_monitor`
   - **Password:** `AdminPassword123!`
   - **CAPTCHA:** Enter image CAPTCHA.
3. Submit and enter the **Login OTP** dispatched to `efren.castillano@csucc.edu.ph`.
4. The system navigates to `admin_dashboard.php`.
5. **Inspect the 4 Summary Metric Cards:**
   - **Failed Logins (Today):** Total number of failed authentication attempts recorded in the last 24 hours.
   - **Active Account Lockouts:** Count of accounts currently in an active progressive lockout cooldown.
   - **Active IP Blocks:** Number of IP addresses currently banned due to rate-limit violations.
   - **Security Flags (Unresolved):** Suspicious security anomalies flagged by the automated monitoring script.
6. **Inspect the 24-Hour Failed Login Bar Chart:**
   - Responsive CSS/SVG bar chart visualizing failed login attempts broken down by hour of the day.
   - Hovering over a bar reveals the exact hourly count.
7. **Inspect Top 5 Offending IPs Table:**
   - Lists IP addresses generating repeated authentication failures with total attempt counts and status.
8. **Inspect Active Security Monitoring Flags:**
   - Displays real-time intrusion flags generated by `scripts/monitor.php` (*e.g., rapid failures, off-hours logins*).
9. **Inspect the Immutable Audit Logs Table (Protection S11):**
   - Displays the 50 most recent events recorded in `activity_logs`.
   - Columns: Timestamp, Event Type badge, Username, User ID, IP Address, Browser User Agent, Details.
   - **Event Filter:** Filter logs by category (*`LOGIN_SUCCESS`, `LOGIN_FAILED`, `LOCKOUT_TIER_1`, `REGISTERED`, `PASSWORD_RESET`*).
   - **Non-Repudiation Defense:** Emphasize to your instructor that there are **NO edit or delete buttons** on the dashboard. Database privileges prevent alteration of audit records.

---

## 12. Module 10: Automated CLI Intrusion Detection Script

### Purpose
Runs as a background scheduled process (Windows Task Scheduler or Cron) to analyze activity logs and detect suspicious cybersecurity attack patterns.

### The 4 Detection Patterns:
1. **Rapid Consecutive Failures:** Detects $\ge 5$ failed login attempts from a single IP or username within a 5-minute sliding window.
2. **Off-Hours Administrator Logins:** Detects administrator logins occurring outside normal business hours (between 10:00 PM and 6:00 AM).
3. **Active IP Blocks:** Flags IP addresses that have reached the 9-failure threshold for administrative review.
4. **Multi-Account Credential Stuffing:** Detects when a single IP attempts logins across $\ge 3$ distinct usernames within 10 minutes.

### Step-by-Step Walkthrough:
1. Open PowerShell or Command Prompt in the project folder:
   ```powershell
   cd C:\xampp\htdocs\clinic_appoinment_system
   ```
2. Execute the monitoring script using XAMPP PHP:
   ```powershell
   & "C:\xampp\php\php.exe" scripts/monitor.php
   ```
3. **Review the Output in Terminal:**
   ```
   =================================================================
   CLINIC SECURITY INTRUSION MONITORING ENGINE (CLI)
   Execution Time: 2026-11-15 08:00:00 | SAPI: cli
   =================================================================
   [PATTERN 1] Checking for rapid failed logins (>=5 in 5 min)...
               No rapid failed login anomalies detected.
   [PATTERN 2] Checking for off-hours administrator logins (10PM - 6AM)...
               Found: admin_monitor logged in off-hours at 2026-11-15 00:57:22
               Flag recorded into database.
   [PATTERN 3] Checking active IP blocks requiring admin review...
               No active IP blocks detected.
   [PATTERN 4] Checking for multi-account credential stuffing (>=3 users)...
               No distributed credential stuffing detected.
   =================================================================
   Monitoring run complete. Flags recorded: 1 | Duplicate flags ignored: 0
   ```
4. **Demonstrate Duplicate Suppression:** Run the script a second time. Notice that the summary reports `Duplicate flags ignored: 1`, proving that flags are not spammed repeatedly.
5. **Demonstrate Browser Execution Denial:** Open `http://localhost/clinic_appoinment_system/scripts/monitor.php` in a web browser. The server returns **HTTP 403 Forbidden**, proving that web visitors cannot trigger or tamper with the script.

---

## 13. Module 11: Automated Security Self-Test Suite

### Purpose
Runs a comprehensive automated test suite verifying that all 13 protections, cryptographic algorithms, error message strings, and database immutability constraints are functioning properly.

### Step-by-Step Walkthrough:
1. Open PowerShell in the project directory:
   ```powershell
   cd C:\xampp\htdocs\clinic_appoinment_system
   ```
2. Run the self-test suite:
   ```powershell
   & "C:\xampp\php\php.exe" tests/self_test.php
   ```
3. **Verify the 8 Test Assertions:**
   - **Test 1:** Database encryption & Bcrypt hashing at rest (PASSED).
   - **Test 2:** SQL injection resistance with `' OR '1'='1` (PASSED).
   - **Test 3:** Cross-Site Scripting (XSS) escaping via `htmlspecialchars()` (PASSED).
   - **Test 4:** Core registration error messages match Section 4.10 verbatim (PASSED).
   - **Test 5:** Password history refusal blocking reuse of the last 3 passwords (PASSED).
   - **Test 6:** Seeded progressive lockout tier durations ($24$s, $48$s, $96$s) (PASSED).
   - **Test 7:** Seed parameter mathematical integrity ($D_1=9, D_2=1$) (PASSED).
   - **Test 8:** Activity log immutability with zero `UPDATE`/`DELETE` queries (PASSED).
4. **Expected Output:**
   ```
   -----------------------------------------------------------------
   Self-Test Summary: 8 Passed, 0 Failed
   =================================================================
   ```

---

## 14. Troubleshooting & Frequently Asked Questions

### Q1: I am not receiving OTP or alert emails in my inbox.
- **Cause:** Incorrect Google App Password or SMTP port blocked.
- **Solution:**
  1. Verify your 16-character Google App Password in `config.php`.
  2. Ensure 2-Step Verification is enabled on your Google Account (`efren.castillano@csucc.edu.ph`).
  3. Verify port `587` with TLS is open and not blocked by firewall software.
  4. Test email delivery using `& "C:\xampp\php\php.exe" test_email.php`.

### Q2: MySQL database connection error (`Connection refused`).
- **Cause:** MySQL service is stopped.
- **Solution:** Open XAMPP Control Panel and click **Start** on MySQL, or run:
  ```powershell
  & "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
  ```

### Q3: An account is locked out during testing and I need to clear it immediately.
- **Solution:** Run the following SQL query in MySQL to reset the failure counter and lockout timer:
  ```sql
  USE clinic_appointment_db;
  UPDATE users SET failed_logins = 0, lockout_tier = 0, lockout_until = NULL WHERE username = 'patient_juan';
  ```

### Q4: How do I demonstrate that my contact number is really encrypted in the database?
- **Solution:** Run this query to show your instructor the raw ciphertext vs. the decrypted view:
  ```powershell
  & "C:\xampp\mysql\bin\mysql.exe" -u root -e "USE clinic_appointment_db; SELECT username, contact_number_encrypted FROM users;"
  ```
  The contact number will appear as a scrambled base64 string (*e.g., `WHlBpayMg+Pz70Nh...`*), proving that the number is never stored in readable plaintext.
