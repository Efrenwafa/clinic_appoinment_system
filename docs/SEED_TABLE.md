# CARAGA STATE UNIVERSITY
## College of Computing and Information Sciences
### IT 107 – Information Assurance and Security 1
### Final Laboratory Project · 1st Semester A.Y. 2026–2027

---

# PERSONAL SEED TABLE

**Student Seed Values:**
- **D1** (Second-to-last digit of Student ID): **9**
- **D2** (Last digit of Student ID): **1**

The limits, thresholds, timers, and authentication questions below are derived directly from the student seed values ($D_1 = 9$, $D_2 = 1$) according to the formulas prescribed in the official IT 107 Final Laboratory Project Handout (Section 2.2).

---

### Derivation and Parameter Table

| # | Parameter | Formula | Calculation ($D_1=9, D_2=1$) | Resulting System Value | Handout Section Reference |
| :-: | :--- | :--- | :--- | :--- | :-: |
| 1 | **Minimum username length** | $6 + (D_1 \pmod 3)$ | $6 + (9 \pmod 3) = 6 + 0$ | **6 characters** | Sec. 4.9 |
| 2 | **Maximum username length** | $16 + (D_2 \pmod 5)$ | $16 + (1 \pmod 5) = 16 + 1$ | **17 characters** | Sec. 4.9 |
| 3 | **Minimum password length** | $8 + (D_2 \pmod 4)$ | $8 + (1 \pmod 4) = 8 + 1$ | **9 characters** | Sec. 4.9 |
| 4 | **Maximum length of each name field** | $20 + D_2$ | $20 + 1$ | **21 characters** | Sec. 4.5 |
| 5 | **Failed log-ins before "Forgot Password?" appears** | $2 + (D_1 \pmod 2)$ | $2 + (9 \pmod 2) = 2 + 1$ | **3 failures** | Sec. 8.2 |
| 6 | **Lockout Tier 1 duration** | $15 + D_1$ seconds | $15 + 9$ | **24 seconds** (at 3 failures) | Sec. 8.3 |
| 7 | **Lockout Tier 2 duration** | $30 + (2 \times D_1)$ seconds | $30 + (2 \times 9) = 30 + 18$ | **48 seconds** (at 6 failures) | Sec. 8.3 |
| 8 | **Lockout Tier 3 duration** | $60 + (4 \times D_1)$ seconds | $60 + (4 \times 9) = 60 + 36$ | **96 seconds** (at 9 failures) | Sec. 8.3 |
| 9 | **OTP expiry window** | $3 + (D_2 \pmod 3)$ minutes | $3 + (1 \pmod 3) = 3 + 1$ | **4 minutes** | Sec. 6.2 |
| 10 | **Idle session timeout** | $5 + (D_1 \pmod 5)$ minutes | $5 + (9 \pmod 5) = 5 + 4$ | **9 minutes** (warn at 8 min) | Sec. 8.4 |
| 11 | **IP block threshold** | $8 + D_2$ failures | $8 + 1$ | **9 failures** (within 10 min) | Sec. 12 (P5) |
| 12 | **Authentication Question A** | $Q[(D_2 \pmod 8) + 1]$ | $Q[(1 \pmod 8) + 1] = Q[1 + 1] = \mathbf{Q2}$ | **"Who was your best friend in elementary school?"** | Sec. 5 |
| 13 | **Authentication Question B** | $Q[((D_2 + 3) \pmod 8) + 1]$ | $Q[((1 + 3) \pmod 8) + 1] = Q[4 + 1] = \mathbf{Q5}$ | **"What street did you live on in Grade 1?"** | Sec. 5 |
| 14 | **Authentication Question C** | $Q[((D_2 + 5) \pmod 8) + 1]$ | $Q[((1 + 5) \pmod 8) + 1] = Q[6 + 1] = \mathbf{Q7}$ | **"What was the name of your elementary school?"** | Sec. 5 |

---

### Verbatim Security Question Text (Section 5 Question Bank)

The three security questions assigned to this project are copied verbatim from Section 5 of the official course handout:

- **Question A (Q2):** `"Who was your best friend in elementary school?"`
- **Question B (Q5):** `"What street did you live on in Grade 1?"`
- **Question C (Q7):** `"What was the name of your elementary school?"`

