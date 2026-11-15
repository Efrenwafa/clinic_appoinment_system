# DESIGN DECISIONS
**Course:** IT 107 – Information Assurance and Security 1  
**Project:** Secure Clinic Appointment and Monitoring System  
**Student:** Efren Castillano (`efren.castillano@csucc.edu.ph`)  
**Assigned Seed:** $D_1 = 9$, $D_2 = 1$

---

### Decision 1: Password Hashing Algorithm and Settings (Section 4.4)
We chose PHP's native `PASSWORD_DEFAULT` implementation, utilizing **Bcrypt** with an adaptive work factor (cost of 10) and support for **Argon2id**. Bcrypt is an industry-standard, slow, memory-hard key derivation function that automatically incorporates a cryptographically secure, pseudo-random 16-byte salt within the resulting hash string. Unlike fast cryptographic hash functions such as SHA-256 or MD5 (which can be cracked at billions of computations per second on modern GPUs), Bcrypt's work factor intentionally introduces computational delay, rendering offline dictionary and brute-force attacks economically and computationally infeasible.

---

### Decision 2: Password Strength Evaluation Rules (Section 4.9)
Our password strength policy classifies passwords into **Weak**, **Medium**, and **Strong** based on entropy, length, and character diversity. A password is rated **Weak** if it is under the seeded minimum of 9 characters or fails to combine multiple character sets; weak passwords are programmatically rejected from form submission. A password is rated **Medium** if it meets the minimum 9-character threshold and contains at least two distinct character types (lowercase, uppercase, numbers, or symbols). A password achieves **Strong** status if it contains at least 12 characters and integrates uppercase letters, lowercase letters, numeric digits, and special symbols. This ensures high Shannon entropy and resistance against automated rainbow table lookups.

---

### Decision 3: Why Security Answers Must Be Hashed, Not Stored as Text (Section 5)
Security question answers constitute secondary authentication credentials equivalent to fallback passwords. Storing security answers in readable plaintext format creates a severe single-point-of-failure vulnerability: any unauthorized database dump, insider threat, or SQL injection leak would immediately compromise all recovery secrets. Hashing security answers using one-way cryptographic functions (`password_hash`) after normalizing whitespace and lowercase casing ensures that the original answer cannot be extracted or reversed from the database. The system only performs one-way comparison (`password_verify`), upholding the principle of least privilege and zero-knowledge storage.

---

### Decision 4: Does a Failed CAPTCHA Count as a Failed Log-in? (Section 7.1)
We decided that a failed CAPTCHA **does not** count toward the account-specific lockout failure counter, but **does** record an entry in the IP-level failure monitoring and activity log (`CAPTCHA_FAILED`). The reasoning is that CAPTCHA serves specifically as bot mitigation (proof of human presence) rather than proof of identity. If failed CAPTCHAs counted directly toward a user's account lockout, an automated adversary could easily launch a targeted Denial of Service (Account Lockout DoS) attack against innocent medical personnel or patients by submitting arbitrary CAPTCHA guesses with the victim's known username, locking their account indefinitely without ever guessing a password.

---

### Decision 5: Is "Disabling the Back Button" Enough? (Section 8.4)
Disabling or manipulating the browser back button via client-side JavaScript (`history.pushState` or `window.onunload`) is cosmetic and provides zero cryptographic or server-side security. An attacker with physical access or browser inspect tools can simply review cached DOM pages or inspect network response dumps if the server does not instruct the browser to discard the content. Real protection is accomplished through two server-enforced countermeasures: first, terminating the session entirely on the server upon logout; second, issuing strict HTTP cache control headers (`Cache-Control: no-store, no-cache, must-revalidate, max-age=0` and `Pragma: no-cache`) on every protected page so the browser never stores sensitive clinic consultation records in its local disk or memory cache.

---

### Decision 6: How the Verified User Reaches Change Password (Section 10)
To prevent unauthorized direct access to `change_password.php`, the system enforces a strict state machine verified on the server. Upon successful verification of the 6-digit password reset OTP, the server generates a cryptographically secure, 32-byte pseudo-random reset token (`bin2hex(random_bytes(32))`) and stores it inside the temporary server session along with the verified user ID. The user is redirected to `change_password.php?token=<token>`. When the page loads, the server verifies that `$_SESSION['reset_authorized_user_id']` exists and that the query token matches the session token via timing-attack safe comparison (`hash_equals`). If an unauthorized user or instructor attempts to navigate directly to `change_password.php`, the check fails, the unauthorized attempt is logged, and the user is redirected to `login.php`. Once the password is changed, the token is destroyed immediately, preventing replay attacks.

---

### Decision 7: Protecting the Log Table Itself from Deletion (Section 13.3)
In an enterprise production deployment, the database user account utilized by the web application should possess strictly **`SELECT` and `INSERT` privileges only** on the `activity_logs` table, with all `UPDATE`, `DELETE`, `DROP`, and `ALTER` grants revoked at the MySQL user permission level (`REVOKE DELETE, UPDATE ON clinic_appointment_db.activity_logs FROM 'clinic_app'@'localhost'`). In our application codebase, there are zero `UPDATE` or `DELETE` queries targeting `activity_logs`. This ensures that even if an attacker were to obtain application-level remote code execution or hijack an administrator's web session, they could never alter, truncate, or wipe previous audit records, guaranteeing non-repudiation and forensic evidentiary integrity.

---

### Decision 8: Scheduling the Monitoring Script (Windows Task Scheduler vs. Cron) (Section 14.2)
To automate the execution of `scripts/monitor.php` every 15 minutes in a production environment: on Windows Server environments, this is implemented via **Windows Task Scheduler** by creating a scheduled task that triggers every 15 minutes, invoking the action Program/script: `C:\xampp\php\php.exe` with arguments: `C:\xampp\htdocs\clinic_appoinment_system\scripts\monitor.php`. On Linux or POSIX environments, this is accomplished by adding a cron entry via `crontab -e`: `*/15 * * * * /usr/bin/php /var/www/html/clinic_appoinment_system/scripts/monitor.php > /dev/null 2>&1`. During live evaluation, the script is run directly from the command line to demonstrate real-time console log detection and database flag recording.

---

### Decision 9: Can a JavaScript File Really Be Hidden? (Section 15)
A JavaScript file delivered to a web client **cannot** truly be hidden from a determined user because the browser requires full access to download, parse, and execute the JavaScript source code. Any attempt to obfuscate, disable right-click, or use HTTP referer checks can be trivially inspected by opening browser DevTools, reviewing the Sources tab, or utilizing command-line tools like `curl`. Our implementation places JavaScript in `/js/validate.js` purely for user experience and interface convenience (such as live age calculation and password meters). Our **real security protection resides 100% on the server** in `php/validation.php`: every input format, length boundary, data type, and authorization check is executed independently in PHP PDO, ensuring that bypassing or altering client-side scripts results in immediate server-side rejection.

---

### Decision 10: Difference Between Hashing and Encryption (Protection 13 Note)
**Hashing is a one-way mathematical function**: it transforms variable-length input data into a fixed-length cryptographic digest that cannot be reversed or decrypted back into the original plaintext. Hashing is the correct cryptographic primitive for passwords, security answers, and OTPs because the system only ever needs to verify equality (i.e., whether the hash of the provided input matches the stored hash). In contrast, **encryption is a two-way mathematical transformation** governed by a secret cryptographic key: plaintext is converted into ciphertext and can be decrypted back into its original form using the authorized key. Encryption (specifically AES-256-CBC with random IV) is the correct primitive for sensitive patient contact numbers because legitimate clinic healthcare personnel and the patient must be able to read and utilize the original phone number to communicate and confirm medical appointments.
