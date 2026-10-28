# CARAGA STATE UNIVERSITY &middot; BS INFORMATION TECHNOLOGY
## IT 107 &middot; Information Assurance and Security 1

# Final Laboratory Project
### Secure Registration, Authentication and Monitoring System
**STUDENT HANDOUT &middot; 1st Semester A.Y. 2026&ndash;2027**

---

| Student Submission Details | |
| :--- | :--- |
| **Name** | Efren Castillano |
| **Student No.** | |
| **D1 / D2** | 9 / 1 |
| **My System** | Secure Clinic Appointment and Monitoring System |
| **Approved by** | |
| **Date approved** | |

---

# APPENDIX A: SYSTEM PROPOSAL FORM

| Item | Details |
| :--- | :--- |
| **System Name** | **Secure Clinic Appointment and Monitoring System** |
| **Who Uses It** | **Patients**: Register, verify their email via OTP, log in using two-step authentication, request clinic appointments, view their own appointment status, use self-service password reset, and log out securely.<br><br>**Clinic Administrator**: Authenticates through a protected login flow with OTP, reviews and manages clinic appointment requests, updates appointment statuses (Pending, Confirmed, Completed, Cancelled), and accesses the protected security monitoring dashboard to inspect real-time security events, lockout status, IP blocking, and automated monitoring flags. |
| **What It Stores** | 1. **Patient Account & Identity Data**: ID Number (`xxxx-xxxx`), First Name, Middle Name, Family Name, Name Extension, Birthdate, Age, Address (Purok/Street, Barangay, Municipality/City, Province, Country, ZIP Code), Email Address, and Username.<br>2. **Authentication & Security Data**: Password hashes (`password_hash`), hashed security question answers, hashed one-time passwords (OTP codes, purpose, expiry, and attempt counts), password history (hashes of last 3 passwords), and privacy consent records with timestamps.<br>3. **Clinic Appointment Data**: Appointment date, requested department, patient type, reason for visit, status, and encrypted contact numbers.<br>4. **Security & Audit Logs**: Activity logs (event type, username typed, user ID, IP address, user-agent/browser, event details, timestamp), failed login records, lockout tier records, IP block entries, and security monitoring flags. |
| **Why Accounts Need Protecting** | The system handles personal information and sensitive health appointment records subject to the **Philippine Data Privacy Act of 2012 (Republic Act No. 10173)**. Unauthorized access could lead to identity theft, patient medical record exposure, unauthorized rescheduling or cancellation of medical consultations, credential stuffing attacks, and administrative privilege compromise. Accounts and sessions must be strictly defended against SQL injection, Cross-Site Scripting (XSS), Cross-Site Request Forgery (CSRF), brute-force attacks, and session hijacking. |
| **Sensitive Field to Encrypt (Protection 13)** | **Patient Contact Number**.<br>Encrypted at rest in the database using **AES-256** (via PHP OpenSSL `openssl_encrypt()` with AES-256-CBC/GCM and a cryptographically secure random Initialization Vector [IV]). The plaintext contact number is decrypted only when displayed to its authorized owner or treating clinic staff. The encryption key is isolated outside Git and web root. Passwords, security answers, and OTPs are one-way hashed, not encrypted. |

---

### System-Specific Registration Fields
*(At least four fields, of at least three different kinds, each with explicit rules and error messages)*

| # | Field Name | Kind / Data Type | Required? | Validation Rule | Exact Error Message |
| :-: | :--- | :--- | :-: | :--- | :--- |
| **1** | **Contact Number** | Formatted text / Phone | **Required** | Valid Philippine mobile number format: exactly 11 digits, starting with `09` (`09XXXXXXXXX`). Encrypted at rest in database using AES-256 (Protection 13). | `Enter a valid contact number in the format 09XXXXXXXXX.` |
| **2** | **Patient Type** | Dropdown / Select | **Required** | Must be selected from preconfigured values: `New Patient` or `Returning Patient`. Server-side rejection of unlisted options. | `Select a valid patient type.` |
| **3** | **Preferred Clinic Department** | Dropdown / Select | **Required** | Must match an authorized clinic department configured in the system (e.g., `General Medicine`, `Pediatrics`, `Dental`, `Dermatology`, `Cardiology`). Server rejects unlisted options. | `Select a valid clinic department.` |
| **4** | **Emergency Contact Number** | Formatted text / Phone | **Optional** | Optional. If provided by patient, must strictly follow Philippine mobile number format: exactly 11 digits starting with `09`. | `Enter a valid emergency contact number in the format 09XXXXXXXXX.` |

---

### Instructor Evaluation & Approval

| Field | Value / Space |
| :--- | :--- |
| **Instructor's Decision** | ☐ Approved &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ☐ Revise and resubmit |
| **Instructor Signature** | &nbsp; |
| **Date** | &nbsp; |

*(Note: Approval, signature, and date are left blank for the instructor at Checkpoint 1).*
