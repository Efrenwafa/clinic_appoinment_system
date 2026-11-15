# PRIVACY NOTE AND DATA PROTECTION COMPLIANCE
**Course:** IT 107 – Information Assurance and Security 1  
**Project:** Secure Clinic Appointment and Monitoring System  
**Student:** Efren Castillano (`efren.castillano@csucc.edu.ph`)  
**Assigned Seed:** $D_1 = 9$, $D_2 = 1$  
**Legal Framework:** Republic Act No. 10173 (Philippine Data Privacy Act of 2012 / DPA)  
**Deliverable Category:** Criteria P5 Compliance Document

---

## 1. Executive Summary and Regulatory Statement
The **Secure Clinic Appointment and Monitoring System** collects, processes, and stores personal data to facilitate patient identity verification, outpatient clinic appointment scheduling, clinical department routing, and security monitoring. In compliance with the **Philippine Data Privacy Act of 2012 (Republic Act No. 10173)**, its Implementing Rules and Regulations (IRR), and National Privacy Commission (NPC) circulars, this system strictly enforces the three core data privacy principles:

1. **Transparency:** Patients are informed prior to registration through a clear, plain-language Privacy Notice detailing what data is gathered, why it is gathered, how it is secured, and how long it is retained.
2. **Legitimate Purpose:** Processing is strictly limited to patient identification, clinical appointment scheduling, emergency medical contact, and system security monitoring.
3. **Proportionality:** Only data strictly necessary for healthcare delivery and identity assurance is collected. No unnecessary or invasive metadata is solicited.

---

## 2. Classification of Collected Data Elements (Section 3, RA 10173)

Under Section 3 of RA 10173, data is classified into **Personal Information (PI)** and **Sensitive Personal Information (SPI)**. The table below documents every data field collected during patient registration and appointment booking, its statutory classification, and the technical necessity justifying its collection.

| Field Name | Category in System | Statutory Classification (RA 10173) | Purpose and Technical Necessity |
| :--- | :--- | :--- | :--- |
| **ID Number** | Core Registration | Personal Information (§3(g)) | Unique institutional identifier linking the user account to physical university or clinic health records. |
| **First Name** | Core Registration | Personal Information (§3(g)) | Required for legal identity verification and matching patient consultation charts. |
| **Middle Name** | Core Registration | Personal Information (§3(g)) | Distinguishes individuals sharing identical first and family names (common in Philippine naming conventions). |
| **Family Name** | Core Registration | Personal Information (§3(g)) | Legal identification and formal medical documentation. |
| **Name Extension** | Core Registration | Personal Information (§3(g)) | Clarifies generational titles (Jr., Sr., III) to avoid chart mix-ups. |
| **Birthdate** | Core Registration | **Sensitive Personal Information** (§3(l)(1)) | Health assessment: essential for calculating accurate age to determine pediatric vs. adult clinical dosage thresholds and medical risk profiles. |
| **Age** | Core Registration | **Sensitive Personal Information** (§3(l)(1)) | Clinical triaging, automated validation against birthdate, and verifying legal age of consent. |
| **Purok / Street** | Core Registration | Personal Information (§3(g)) | Granular residential location for home health follow-ups and epidemiological contact tracing. |
| **Barangay** | Core Registration | Personal Information (§3(g)) | Local government unit demarcation for public health reporting. |
| **Municipality / City** | Core Registration | Personal Information (§3(g)) | Geographic routing and demographic analysis. |
| **Province** | Core Registration | Personal Information (§3(g)) | Regional health administration mapping. |
| **Country** | Core Registration | Personal Information (§3(g)) | International residency verification for university health registry. |
| **Zip Code** | Core Registration | Personal Information (§3(g)) | Postal validation and geographical zoning. |
| **Email Address** | Core Registration | Personal Information (§3(g)) | Out-of-band communication: delivery of 6-digit registration OTPs, login verification codes, password reset tokens, and high-priority security lockout alerts. |
| **Username** | Core Registration | Personal Information (§3(g)) | Primary authentication identifier for login and session state management. |
| **Password** | Core Registration | Authentication Secret | Primary secret verifying identity. Stored strictly as a Bcrypt one-way hash (`PASSWORD_DEFAULT`); never stored in plaintext. |
| **Security Answers** | Core Registration | Authentication Secret | Secondary authentication credential for account recovery. Stored strictly as Bcrypt one-way hashes after normalization. |
| **Contact Number** | Clinic-Specific | **Sensitive Personal Information** (§3(l)(1)) | Direct patient communication channel for urgent appointment re-scheduling, lab result callbacks, and identity verification. Encrypted at rest using AES-256-CBC. |
| **Patient Type** | Clinic-Specific | Operational Metadata | Workflow intake routing: determines whether intake staff must generate a brand new physical health record or retrieve an existing archived medical chart. |
| **Preferred Clinic Department** | Clinic-Specific | **Sensitive Personal Information** (§3(l)(2)) | Healthcare routing: indicates the medical specialty sought (e.g., Dental, Pediatrics, General Medicine, Cardiology, Dermatology), revealing individual medical needs. |
| **Emergency Contact Number** | Clinic-Specific | Personal Information (§3(g)) | Life-safety measure: emergency contact in cases of adverse clinical reactions, acute trauma, or medical distress during on-site consultations. |
| **Appointment Date & Time** | Appointment Module | Operational Healthcare Data | Scheduling and clinic resource allocation. |
| **Appointment Reason** | Appointment Module | **Sensitive Personal Information** (§3(l)(2)) | Chief medical complaint and health concern disclosed by the patient to enable attending physicians to prepare specialized clinical diagnostic equipment. |

---

## 3. Data Protection and Security Countermeasures

To uphold integrity and confidentiality (§20, RA 10173), the following safeguards are implemented at the architectural and application layers:

### 3.1 Field-Level Cryptographic Encryption at Rest (Protection 13)
- **Target Field:** Patient Contact Number (`contact_number_encrypted`).
- **Algorithm:** Advanced Encryption Standard (AES) with a 256-bit key in Cipher Block Chaining mode (`AES-256-CBC`).
- **Initialization Vector (IV):** A unique, cryptographically random 16-byte IV (`openssl_random_pseudo_bytes(16)`) is generated for every individual record, preventing pattern matching across identical contact numbers.
- **Key Management:** The 32-byte secret key is defined in `config.php`, located strictly outside the Git repository via `.gitignore`.
- **Contrast with Hashing:** Unlike passwords and security answers (which use one-way hashing because the plain values never need to be retrieved), the contact number must be reversibly decrypted by authorized clinic staff and the patient to confirm bookings.

### 3.2 One-Way Cryptographic Hashing
- **Passwords:** Hashed with PHP `password_hash()` utilizing Bcrypt with a work factor cost of 10. Passwords are never sent via email or stored in session variables.
- **Security Question Answers:** Normalized (trimmed and lowercased) and hashed with Bcrypt before persistence in `authentication_answers`.
- **One-Time Passwords (OTPs):** Hashed with Bcrypt before saving to `otps`, ensuring database compromise does not expose live OTP codes.

### 3.3 Strict Access Control and Principle of Least Privilege
- **Role-Based Access Control (RBAC):** Distinct roles (`patient` vs. `admin`) enforced by server-side session checks.
- **Direct Object Reference Prevention (IDOR):** Appointment queries strictly bind `:session_user_id` to `patient_id` in SQL statements (`WHERE patient_id = :uid`), preventing users from accessing or tampering with other patients' medical consultations.
- **Admin Dashboard Isolation:** Only authenticated accounts with `role = 'admin'` can access `admin_dashboard.php` and `admin_api.php`. Unauthorized attempts trigger instant termination and security audit logging.

### 3.4 Transport and Browser-Level Protections
- **Cache Invalidation:** Sensitive medical pages issue HTTP headers:  
  `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`  
  `Pragma: no-cache`  
  This prevents browser caching and history traversal attacks on shared clinic terminals.
- **Clickjacking and MIME Sniffing Defense:** Server emits `X-Frame-Options: DENY` and `X-Content-Type-Options: nosniff`.
- **Session Protection:** Session cookies are configured with `HttpOnly = true`, `SameSite = 'Strict'`, and regenerated upon authentication.

---

## 4. Security Audit Logging and Privacy Boundaries

Section 13 of the project guidelines mandates comprehensive activity logging. To prevent security logs from becoming an unauthorized secondary repository of sensitive personal data, strict boundaries are enforced:

### 4.1 What IS Logged in `activity_logs`:
- High-level event categories (`REGISTERED`, `LOGIN_SUCCESS`, `LOGIN_FAILED`, `CAPTCHA_FAILED`, `LOCKOUT_TIER_1`, `PASSWORD_RESET_SUCCESS`, `APPOINTMENT_REQUESTED`).
- The username submitted in the request.
- Matched user ID (if account exists).
- Client IP address (`$_SERVER['REMOTE_ADDR']`).
- Client browser User-Agent string.
- High-level descriptive operational summary.
- Server timestamp (`created_at`).

### 4.2 What is STRICTLY EXCLUDED from Logs:
- Plaintext or hashed passwords.
- Plaintext or hashed security answers.
- Live 6-digit OTP codes.
- Credit/financial data.
- Medical consultation details, symptoms, and appointment reasons.
- AES encryption keys or IV secrets.

---

## 5. Patient Consent Mechanism

The registration workflow implements an explicit, unbundled consent mechanism:
1. **Unchecked Checkbox:** The registration form contains an explicit checkbox (`privacy_consent`) that is unchecked by default. Form submission without checking this box is rejected on both client-side and server-side.
2. **Access to Policy:** An immediate hyperlink directs the patient to `privacy_notice.php`, explaining data processing terms prior to registration.
3. **Audit Evidence:** Upon successful registration, the exact timestamp of consent is permanently written to `users.privacy_consent_at`, providing non-repudiable legal proof of informed consent under RA 10173.
