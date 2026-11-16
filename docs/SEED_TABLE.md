# CARAGA STATE UNIVERSITY
## College of Computing and Information Sciences
### IT 107 – Information Assurance and Security 1
### Final Laboratory Project · 1st Semester A.Y. 2026–2027

---

# SYSTEM SECURITY SETTINGS & SPECIFICATIONS (UPDATED SECTION 2.3)

> [!NOTE]
> **Instructor Course Update Notice:**
> Section 2.2 ("Derive your seed") student-number formulas have been officially superseded by the instructor. Students no longer compute individual $D_1, D_2$ mod values. Instead, all systems universally adopt the standardized security settings specified in **Section 2.3**.

---

### Universal Security Settings Table (Section 2.3)

| # | Security Parameter | Universal Required Value | Enforcement Mechanism | Handout Section Reference |
| :-: | :--- | :--- | :--- | :-: |
| 1 | **Minimum username length** | **6 characters** | Server validation (`php/validation.php`) | Sec. 4.9 |
| 2 | **Maximum username length** | **20 characters** | Server validation (`php/validation.php`) | Sec. 4.9 |
| 3 | **Minimum password length** | **8 characters** (max 64) | Server validation (`php/validation.php`) | Sec. 4.9 |
| 4 | **Maximum length of each name field** | **50 characters** | Server validation (`php/validation.php`) | Sec. 4.5 |
| 5 | **Failed log-ins before "Forgot Password?" appears** | **2 consecutive failures** | Server session check & UI (`login.php`) | Sec. 8.2 |
| 6 | **Lockout Tier 1 duration** | **15 seconds** (at 3 failures) | Server database timestamp (`users.locked_until`) | Sec. 8.3 |
| 7 | **Lockout Tier 2 duration** | **30 seconds** (at 6 failures) | Server database timestamp (`users.locked_until`) | Sec. 8.3 |
| 8 | **Lockout Tier 3 duration** | **60 seconds** (at 9 failures) | Server database timestamp (`users.locked_until`) | Sec. 8.3 |
| 9 | **OTP expiry window** | **5 minutes** (300 seconds) | Database timestamp (`otps.expires_at`) | Sec. 6.2 |
| 10 | **Idle session timeout** | **15 minutes** (warn at 14 min) | Client timer & server session destroy | Sec. 8.4 |
| 11 | **IP block threshold** | **10 failures within 10 min** | Database rate limit table (`ip_blocks`) | Sec. 12 (P5) |
| 12 | **Authentication questions** | **User picks 3 different questions** | Selected from Section 5 Bank (Q1–Q8) | Sec. 5 |
| 13 | **Sensitive Field Encryption** | **Patient Contact Number (AES-256)** | Field-level AES-256-CBC with random IV | Protection 13 |

---

### Section 5: Official Authentication Question Bank

During registration, users choose **3 different questions** from the following bank:

1. **Q1:** What was the name of your first pet?
2. **Q2:** Who was your best friend in elementary school?
3. **Q3:** Who was your favorite teacher in high school?
4. **Q4:** What is your mother's maiden first name?
5. **Q5:** What street did you live on in Grade 1?
6. **Q6:** What was the first song you learned by heart?
7. **Q7:** What was the name of your elementary school?
8. **Q8:** What dish did you cook for the first time?

*Rules for Answers:*
- Length: 2 to 50 characters.
- Letters and spaces only. No digits or special symbols. No double spaces.
- Stored as one-way Bcrypt hashes (`PASSWORD_DEFAULT`) after converting to lowercase and trimming whitespace.
