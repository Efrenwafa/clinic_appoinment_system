# IT 107 Checkpoint 1: Presentation & Defense Guide
**Course:** IT 107 – Information Assurance and Security 1  
**Project:** Secure Clinic Appointment and Monitoring System  
**Student:** Efren Castillano (`efren.castillano@csucc.edu.ph`)  
**Assigned Seed:** $D_1 = 9$, $D_2 = 1$  
**Submission Due:** October 30, 2026

---

## 1. Quick Pre-Demo Checklist

Before calling the instructor or stepping up to present, ensure these **3 items** are ready:

- [ ] **Physical Printout or PDF Tab:**
  - Open [`docs/CHECKPOINT_1_DOCUMENTATION.html`](file:///c:/xampp/htdocs/clinic_appoinment_system/docs/CHECKPOINT_1_DOCUMENTATION.html) or have the printed Appendix A proposal ready for physical signature.
- [ ] **GitHub Repository Tab:**
  - Browser tab open to: [https://github.com/Efrenwafa/clinic_appoinment_system](https://github.com/Efrenwafa/clinic_appoinment_system)
- [ ] **VS Code & Terminal Ready:**
  - Terminal open inside `C:\xampp\htdocs\clinic_appoinment_system`.
  - Gmail inbox open in a browser tab showing the test email received from `efren.castillano@csucc.edu.ph`.

---

## 2. Step-by-Step Presentation Script

Follow these 4 sequential steps during your demonstration:

### Step 1: Proposal & System Overview (1–2 minutes)
**Action:** Hand the printed proposal or show the documentation page.  
**What to say:**
> *"Good day, Sir/Ma'am. My project is the **Secure Clinic Appointment and Monitoring System**.*
> *The system allows patients to securely register, verify their email with an OTP, log in using two-step authentication, request appointments, and monitor their appointment status. The clinic administrator has administrative access to manage appointments and review a dedicated security monitoring dashboard.*
> *The system is designed to comply with the Philippine Data Privacy Act of 2012 (RA 10173). Under Protection 13, the sensitive field to encrypt at rest is the **Patient Contact Number**, which uses AES-256 encryption with a securely generated IV.*
> *I have also defined 4 clinic-specific registration fields: Contact Number, Patient Type, Preferred Clinic Department, and Emergency Contact Number."*

---

### Step 2: Seed Table & Calculated Parameters (1 minute)
**Action:** Point to the Personal Seed Table.  
**What to say:**
> *"My student seed values are **D1 = 9** (second-to-last digit) and **D2 = 1** (last digit).*
> *Using the official handout formulas:*
> - *Username length: 6 to 17 characters [$6 + (9 \pmod 3) = 6$ and $16 + (1 \pmod 5) = 17$]*
> - *Minimum password length: 9 characters [$8 + (1 \pmod 4) = 8 + 1$]*
> - *Maximum name length: 21 characters [$20 + 1$]*
> - *Forgot Password link appears after 3 failed login attempts [$2 + (9 \pmod 2) = 2 + 1$]*
> - *Lockout tiers: Tier 1 is 24 seconds [$15 + 9$], Tier 2 is 48 seconds [$30 + 18$], and Tier 3 is 96 seconds [$60 + 36$]*
> - *OTP expiry: exactly 4 minutes [$3 + (1 \pmod 3) = 3 + 1$]*
> - *Idle session timeout: 9 minutes [$5 + (9 \pmod 5) = 5 + 4$]*
> - *IP blocking threshold: 9 failures within 10 minutes [$8 + 1$]*
> - *My three authentication questions from Section 5 are **Q2**, **Q5**, and **Q7**."*

---

### Step 3: Git Repository & Protection 11 (Secrets Management) (1–2 minutes)
**Action:** Show your GitHub repository and terminal.  
**What to say:**
> *"For version control, the repository is initialized and pushed to GitHub with 5 structured, atomic commits.*
> *To satisfy **Security Protection 11**, secrets must never exist in Git history. I established `.gitignore` before writing functional code.*
> *My real database credentials and SMTP App Password reside strictly in `config.php`, which is git-ignored. For version control, I provide `config.sample.php` with placeholder values."*

**Live Terminal Commands to Demonstrate:**
```powershell
# 1. Show that config.php and vendor/ are safely ignored:
git status --ignored

# 2. Show commit history and authorship:
git log --oneline
```

---

### Step 4: Real Email Verification (Deliverable E) (1 minute)
**Action:** Show the received email in Gmail, or run the live test script.  
**What to say:**
> *"For Deliverable E, I configured PHPMailer to send emails using Google SMTP over TLS on port 587. Here is the verified email received in my university inbox."*

**Optional Live Execution (if asked to send live):**
```powershell
& "C:\xampp\php\php.exe" test_email.php efren.castillano@csucc.edu.ph
```
*Point to the console output:* `[SUCCESS] Real test email was successfully sent!`

---

## 3. Instructor Q&A Defense Cheat Sheet

| Question | Why the Instructor Asks It | Your Exact Response |
| :--- | :--- | :--- |
| **"Why is the contact number encrypted while passwords and answers are hashed?"** | Tests your understanding of **Hashing vs. Encryption** (Protection 13 & Design Decisions). | *"Encryption is two-way: data can be decrypted back to plaintext with the secret key. The clinic staff and patient legitimately need to view the contact number to communicate. In contrast, passwords, security answers, and OTPs are one-way hashed because they are only ever verified for equality and should never be reversible, even by a database administrator."* |
| **"How does your system enforce security if JavaScript is disabled?"** | Tests **Server-Side Enforcement** (Protection S3). | *"Browser-side JavaScript validation is for user experience only. The server recalculates and validates every field independently using PHP. Even if a user turns off JavaScript or tampers with the HTTP POST request using developer tools, the server will reject invalid inputs."* |
| **"What happens if someone accidentally commits an App Password and deletes it later?"** | Tests **Git Security Integrity** (Protection 11). | *"Git preserves the entire historical tree. Even if a file is deleted in a later commit, any clone or commit log inspection can extract the secret from the past commit. Protection 11 would score zero. That is why `.gitignore` must be configured before credentials ever touch the working directory."* |
| **"Where do your lockout countdowns live?"** | Tests **Server-Enforced Lockout** (Protection S4). | *"The lockout timer and attempt counters are strictly tracked and calculated on the server in the database and session. A browser countdown is purely visual; refreshing the page, disabling JavaScript, or clearing cookies cannot bypass a server-enforced lockout."* |
| **"Why do you normalize security answers before hashing?"** | Tests **Section 5 Security Answer Rules**. | *"The handout specifies that answers must be converted to lowercase and trimmed of extra spaces before hashing (`password_hash`). This ensures that case variations like 'Bantay' and 'bantay' match the same hash without having to store plaintext answers."* |

---

## 4. What to Get from the Instructor

Before leaving the demo desk:
1. Ensure the instructor marks **☐ Approved** on your System Proposal form.
2. Obtain the instructor's **Signature** and **Date**.
3. Keep this signed form safe; it is required for your final submission package at the end of the semester.

---

## 5. Next Phase: Checkpoint 2 Roadmap

Once Checkpoint 1 is signed, we will proceed directly to:
1. **Database Schema:** Creating `clinic_appointment_db` in MySQL with PDO.
2. **Registration System:** Building `register.php` with all 4 clinic fields and RA 10173 privacy consent.
3. **Registration OTP:** 6-digit cryptographic OTP generation (`random_int()`) with hashed database storage and 4-minute expiry.
4. **CAPTCHA Protection:** Server-verified bot protection on registration.

