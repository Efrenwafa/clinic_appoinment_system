# TEST ACCOUNTS & DEMONSTRATION GUIDE
**Course:** IT 107 – Information Assurance and Security 1  
**Project:** Secure Clinic Appointment and Monitoring System  
**Student:** Efren Castillano (`efren.castillano@csucc.edu.ph`)  
**Assigned Seed:** $D_1 = 9$, $D_2 = 1$  
**Deliverable Category:** Criteria - Live Defense Demonstration Sheet

---

## 1. Verified Test Account Credentials

The database contains three verified active test accounts populated by `database/seed_data.sql`. All test accounts utilize Gmail plus-addressing routing directly to your real Gmail inbox (`efren.castillano@csucc.edu.ph`) so that you can verify real OTP delivery and security alert emails live during the lab presentation.

| Role | User ID | Full Name | Username | Test Password | Registered Email | Contact Number (Encrypted AES-256) |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Admin** | `2023-0001` | Admin Officer | `admin_monitor` | `AdminPassword123!` | `efren.castillano@csucc.edu.ph` | `09123456789` |
| **Patient 1** | `2023-0101` | Juan Carlo Dela Cruz | `patient_juan` | `JuanPassword123!` | `efren.castillano+juan@csucc.edu.ph` | `09876543210` |
| **Patient 2** | `2023-0102` | Maria Clara Santos | `patient_maria` | `MariaPassword123!` | `efren.castillano+maria@csucc.edu.ph` | `09234567891` |

> [!NOTE]
> All passwords exceed the seeded minimum length ($9$ characters) and incorporate uppercase, lowercase, numbers, and symbols. Passwords are saved strictly as Bcrypt one-way hashes (`PASSWORD_DEFAULT`, cost 10).

---

## 2. Assigned Security Questions and Answers (Section 5)

All three accounts have pre-seeded hashed answers for the three questions assigned by seed ($D_1 = 9, D_2 = 1$): **Q2**, **Q5**, and **Q7**. Answers are case-insensitive and trimmed before hashing.

| Question Key | Official Handout Question Text | Plaintext Test Answer |
| :--- | :--- | :--- |
| **Q2** | *"What was the name of your first elementary school?"* | `bantay` |
| **Q5** | *"What is the name of the street you grew up on?"* | `rizal street` |
| **Q7** | *"What was the make and model of your first car?"* | `ampayon central elementary school` |

---

## 3. Step-by-Step Instructor Demonstration Scenarios

### Scenario 1: Clinic Administrator Login & Security Monitoring Dashboard
1. Open your browser and navigate to: `http://localhost/clinic_appoinment_system/login.php`.
2. Enter the credentials for the administrator:
   - **Username:** `admin_monitor`
   - **Password:** `AdminPassword123!`
   - **CAPTCHA:** Enter the text shown in the image CAPTCHA.
3. Click **Sign In**.
4. Check your Gmail inbox (`efren.castillano@csucc.edu.ph`) for the **6-digit Login OTP**.
5. Enter the OTP on `verify_otp.php` and submit.
6. The system regenerates the session ID and redirects you to `admin_dashboard.php`.
7. **Show the Instructor:**
   - **Summary Metric Cards:** Failed Logins Today, Active Lockouts, Active IP Blocks, Flagged Events.
   - **Interactive 24-Hour Bar Chart:** Hourly distribution of failed authentication attempts.
   - **Top 5 Offending IPs:** IP addresses generating repeated failures.
   - **Active Security Flags:** Records flagged by the automated monitoring engine.
   - **Audit Logs Table:** The 50 most recent immutable logs with event filtering.
   - **Non-Repudiation (Protection S11):** Point out to your instructor that there are **no edit or delete buttons** anywhere on the dashboard.

---

### Scenario 2: Patient Portal, Appointment Booking & IDOR Prevention
1. Log out from the administrator account (`logout.php`).
2. Log in using Patient Juan's credentials:
   - **Username:** `patient_juan`
   - **Password:** `JuanPassword123!`
   - **CAPTCHA:** Solve image CAPTCHA.
3. Enter the 6-digit OTP received in your inbox.
4. The system navigates to `appointments.php`.
5. **Show the Instructor:**
   - **Decrypted Contact Number:** Show that the patient's phone number (`09876543210`) is cleanly displayed after on-the-fly AES-256 decryption.
   - **Appointment History:** Review the existing appointment records for Juan.
   - **Book an Appointment:** Fill out the booking form: select Department (*e.g., General Medicine*), Date (*tomorrow*), Time (*10:00 AM*), and Reason (*"Follow-up consult"*). Submit and verify it appears as `Pending`.
   - **Access Control & Anti-IDOR Defense:** Explain that the query is parameterized with `WHERE patient_id = :session_user_id`. Juan cannot view or alter Maria's appointments even if manipulating URL parameters or form posts.

---

### Scenario 3: Progressive Lockout & IP Protection Defense
1. Open a new Incognito / Private browser window.
2. Go to `http://localhost/clinic_appoinment_system/login.php`.
3. Enter **Username:** `patient_juan` and intentionally type a **wrong password** (*e.g., `WrongPassword123!`*).
4. Enter the CAPTCHA and submit 3 consecutive times.
5. **Show the Instructor:**
   - After failure 3, the account triggers **Lockout Tier 1 ($15 + D_1 = 24$ seconds)**.
   - The screen shows a live countdown timer: *"Account locked. Please wait 24 seconds."*
   - The **Sign In** button and **Register** link are disabled.
   - The **"Forgot Password?"** link dynamically appears (triggered after $2 + (D_1 \pmod 2) = 3$ failures).
   - Check your Gmail inbox: a real-time **Security Lockout Alert** email is received detailing the timestamp, IP address, and browser user agent.
   - Refresh the page: explain that the lockout is strictly enforced by the server in MySQL (`users.lockout_tier` and `users.lockout_until`), making browser refresh or JavaScript modification useless.

---

### Scenario 4: Account Recovery, Reset OTP & Password History
1. Click the newly revealed **"Forgot Password?"** link on `login.php`.
2. Enter **Username:** `patient_maria` and solve the CAPTCHA.
3. On the security challenge screen:
   - Select **Question 2**: *"What was the name of your first elementary school?"*
   - Enter Answer: `bantay`
4. Click **Verify Answer**.
5. The system verifies the answer against the normalized Bcrypt hash and sends a **Password Reset OTP** to your inbox.
6. Enter the 6-digit Reset OTP on `verify_otp.php`.
7. The system generates a cryptographically secure 32-byte single-use token and redirects to `change_password.php?token=<token>`.
8. **Test Password History (Protection 7):**
   - Try setting the new password to Maria's current password: `MariaPassword123!`.
   - The server rejects the submission with the message: *"You cannot reuse any of your last 3 passwords. Please choose a different password."*
   - Now enter a valid new password: `NewMariaSecret!2026`.
   - Password changes successfully, existing sessions are invalidated, and a confirmation alert email is sent to your inbox.

---

### Scenario 5: CLI Monitoring Script Execution (`scripts/monitor.php`)
1. Open a PowerShell terminal in `C:\xampp\htdocs\clinic_appoinment_system`.
2. Run the monitoring script:
   ```powershell
   & "C:\xampp\php\php.exe" scripts/monitor.php
   ```
3. **Show the Instructor:**
   - The script outputs a formatted console inspection report.
   - It checks all 4 detection patterns:
     1. Rapid consecutive login failures ($\ge 5$ within 5 minutes).
     2. Off-hours administrator logins (10:00 PM – 6:00 AM).
     3. Active IP blocks requiring investigation.
     4. Distributed credential stuffing from a single IP.
   - Explain deduplication: running the script again does not spam the database with duplicate flags.
   - Show how the flags automatically appear in the Administrator Dashboard (`admin_dashboard.php`).

---

### Scenario 6: Automated Security Self-Test Suite (`tests/self_test.php`)
1. In the PowerShell terminal, run:
   ```powershell
   & "C:\xampp\php\php.exe" tests/self_test.php
   ```
2. **Show the Instructor:**
   - All 8 tests pass cleanly:
     1. Database encryption & hashing at rest.
     2. SQL injection resistance (`' OR '1'='1`).
     3. XSS escaping (`htmlspecialchars`).
     4. Exact Section 4.10 error message compliance.
     5. Password history refusal (last 3 passwords).
     6. Seeded lockout tier timers ($24$s, $48$s, $96$s).
     7. Mathematical seed integrity ($D_1 = 9, D_2 = 1$).
     8. Append-only activity log immutability.
