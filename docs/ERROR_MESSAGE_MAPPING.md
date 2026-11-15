# ERROR MESSAGE MAPPING SPECIFICATION
**Course:** IT 107 – Information Assurance and Security 1  
**Project:** Secure Clinic Appointment and Monitoring System  
**Student:** Efren Castillano (`efren.castillano@csucc.edu.ph`)  
**Assigned Seed:** $D_1 = 9$, $D_2 = 1$  
**Deliverable Category:** Criteria P6 Compliance Document

---

## 1. Overview
This document provides the exhaustive mapping of every validation rule implemented across the **Secure Clinic Appointment and Monitoring System**. In accordance with **Protection S3 (Server-Side Validation)** and **Section 4.10 of the IT 107 Handout**, error messages are designed to be specific and actionable for benign users during registration, while generic and non-revealing during authentication to prevent user enumeration attacks.

The system enforces validation on both the client side (`js/validate.js`) for interactive user experience and on the server side (`php/validation.php`) for security. The server independently verifies all constraints regardless of client manipulation.

---

## 2. Core Registration Validation Rules & Error Messages

| Field Name | Validation Rule / Constraint | Code / Pattern | Exact Error Message |
| :--- | :--- | :--- | :--- |
| **ID Number** | Required check | Cannot be empty or whitespace | `"ID Number is required."` |
| **ID Number** | Format check | Exactly 4 digits, hyphen, 4 digits (`^\d{4}-\d{4}$`) | `"ID Number must follow the format xxxx-xxxx."` |
| **ID Number** | Database uniqueness | Prepared `SELECT COUNT(*) FROM users` | `"This ID Number is already registered."` |
| **First Name** | Required check | Cannot be empty or whitespace | `"First Name is required."` |
| **First Name** | Minimum length | At least 2 characters | `"First Name must be at least 2 characters."` |
| **First Name** | Maximum length | Cannot exceed $20 + D_2 = 21$ characters | `"First Name cannot exceed 21 characters."` |
| **First Name** | No numeric digits | Reject characters `0-9` | `"Numbers are not allowed in First Name."` |
| **First Name** | No special symbols | Letters and spaces only (`^[a-zA-Z\s]+$`) | `"Special characters are not allowed in First Name."` |
| **First Name** | No double spaces | Cannot contain `'  '` | `"Double space is not allowed."` |
| **First Name** | No ALL CAPS | More than 2 characters all uppercase | `"Do not use all capital letters."` |
| **First Name** | No 3 identical letters | Case-insensitive regex `([a-zA-Z])\1\1` | `"Three consecutive identical letters are not allowed."` |
| **First Name** | Capitalization | First letter must be capital | `"First letter of your name must be capital."` |
| **First Name** | Title Case | Rest of word must be lowercase | `"Only the first letter of each word should be capitalized."` |
| **Middle Name** | Optional field | Allowed to be empty | `null` (valid if empty) |
| **Middle Name** | Rules if provided | Same length (2-21), title case, no numbers/symbols | `"Middle Name must be at least 2 characters."` / `"Numbers are not allowed in Middle Name."` |
| **Family Name** | Required check | Cannot be empty or whitespace | `"Family Name is required."` |
| **Family Name** | Constraints | Same rules as First Name, max 21 characters | Matches First Name error formats with `"Family Name"` label |
| **Name Extension**| Allowed values | Optional; if provided must match whitelist: `Jr.`, `Sr.`, `II`, `III`, `IV`, `V` | `"Select a valid name extension."` |
| **Birthdate** | Required check | Cannot be empty | `"Birthdate is required."` |
| **Birthdate** | Format validity | Must parse as `YYYY-MM-DD` | `"Invalid birthdate format."` |
| **Birthdate** | No future dates | Date cannot be greater than today | `"Birthdate cannot be in the future."` |
| **Age** | Minimum age limit | Derived age from birthdate must be $\ge 18$ | `"You must be at least 18 years old to register."` |
| **Purok / Street**| Required check | Cannot be empty or whitespace | `"Purok / Street is required."` |
| **Purok / Street**| Length boundaries | Length between 3 and 50 characters | `"Purok / Street must be between 3 and 50 characters."` |
| **Purok / Street**| Character whitelist | Alphanumeric, spaces, periods, commas, hashes, hyphens | `"Purok / Street contains invalid characters."` |
| **Purok / Street**| No double spaces | Cannot contain `'  '` | `"Double space is not allowed."` |
| **Barangay** | Required check | Cannot be empty | `"Barangay is required."` |
| **Barangay** | Length boundaries | Length between 2 and 50 characters | `"Barangay must be between 2 and 50 characters."` |
| **Barangay** | Letters & spaces only| Regex `^[a-zA-Z\s]+$` | `"Barangay must contain letters and spaces only."` |
| **Barangay** | Title Case | Words must begin with uppercase | `"Barangay must be in Title Case."` |
| **Municipality** | Format & length | Same rules as Barangay, 2-50 chars, title case | `"Municipality / City must be between 2 and 50 characters."` / `"Municipality / City must be in Title Case."` |
| **Province** | Format & length | Same rules as Barangay, 2-50 chars, title case | `"Province must be between 2 and 50 characters."` / `"Province must be in Title Case."` |
| **Country** | Format & length | Same rules as Barangay, 2-50 chars, title case | `"Country must be between 2 and 50 characters."` / `"Country must be in Title Case."` |
| **Zip Code** | Required check | Cannot be empty | `"ZIP Code is required."` |
| **Zip Code** | 4-digit check | Exactly 4 digits (`^\d{4}$`) | `"ZIP Code must be exactly 4 digits."` |
| **Email Address**| Required check | Cannot be empty | `"Email is required."` |
| **Email Address**| No internal spaces | Cannot contain spaces | `"Please enter a valid email address."` |
| **Email Address**| Length limit | Cannot exceed 100 characters | `"Email cannot exceed 100 characters."` |
| **Email Address**| RFC Compliance | Standard `filter_var(..., FILTER_VALIDATE_EMAIL)` | `"Please enter a valid email address."` |
| **Email Address**| Database uniqueness | Prepared `SELECT COUNT(*) FROM users` | `"This email is already registered."` |
| **Username** | Required check | Cannot be empty | `"Username is required."` |
| **Username** | First character | Must begin with a letter `^[a-zA-Z]` | `"Username must start with a letter."` |
| **Username** | Character set | Letters and numbers only (`^[a-zA-Z0-9]+$`) | `"Username must contain letters and numbers only."` |
| **Username** | Seed length limit | Min $6 + (9 \pmod 3) = 6$, Max $16 + (1 \pmod 5) = 17$ | `"Username must be between 6 and 17 characters."` |
| **Username** | Database uniqueness | Prepared `SELECT COUNT(*) FROM users` | `"This username is already registered."` |
| **Password** | Required check | Cannot be empty | `"Password is required."` |
| **Password** | Seed length limit | Min $8 + (1 \pmod 4) = 9$ characters | `"Password must be at least 9 characters."` |
| **Password** | Maximum length | Cannot exceed 64 characters | `"Password cannot exceed 64 characters."` |
| **Password** | Strength check | Must score $\ge 1$ (Medium or Strong entropy) | `"Password is too weak. Must include a mix of uppercase, lowercase, numbers, or symbols."` |
| **Confirm Pwd** | Match check | Must strictly equal `password` | `"Passwords do not match."` |
| **Security Q2** | Answer format | Length 2-50 characters, letters and spaces only | `"Security answer is required."` / `"Answer must contain letters and spaces only."` |
| **Security Q5** | Answer format | Length 2-50 characters, letters and spaces only | `"Security answer is required."` / `"Answer must contain letters and spaces only."` |
| **Security Q7** | Answer format | Length 2-50 characters, letters and spaces only | `"Security answer is required."` / `"Answer must contain letters and spaces only."` |

---

## 3. Clinic-Specific Field Validation Rules & Error Messages

| Field Name | Kind & Requirement | Rule Specification | Exact Error Message |
| :--- | :--- | :--- | :--- |
| **Contact Number** | Formatted text, Required | Philippine mobile number in format `09XXXXXXXXX` (exactly 11 numeric digits starting with `09`). | `"Enter a valid contact number in the format 09XXXXXXXXX."` |
| **Patient Type** | Dropdown, Required | Whitelist membership: must strictly equal either `'New Patient'` or `'Returning Patient'`. | `"Select a valid patient type."` |
| **Clinic Department** | Dropdown, Required | Whitelist membership: must strictly equal one of: `'General Medicine'`, `'Pediatrics'`, `'Dental'`, `'Dermatology'`, `'Cardiology'`. | `"Select a valid clinic department."` |
| **Emergency Contact** | Formatted text, Optional | If provided, must strictly match Philippine mobile format `09XXXXXXXXX` (11 numeric digits). Allowed to be blank. | `"Enter a valid emergency contact number in the format 09XXXXXXXXX."` |

---

## 4. Authentication, Session & System Security Error Messages

| Context / Operation | Security Constraint | Underlying Threat Mitigated | Exact Error Message Displayed to User |
| :--- | :--- | :--- | :--- |
| **Login Attempt** | Non-existent username OR wrong password | User enumeration (Attacker determining valid accounts) | `"Invalid username or password."` *(Generic, Section 4.10)* |
| **Login Attempt** | Account lockout active | Brute-force credential guessing | `"Account is temporarily locked due to multiple failed login attempts. Please try again in X seconds."` |
| **Login / Any Form** | IP block triggered ($9$ failures within 10 min) | Distributed automated dictionary attacks | `"Access temporarily blocked due to repeated security failures. Please contact the clinic administrator."` |
| **CAPTCHA Check** | Missing or incorrect CAPTCHA code | Automated submission bots / credential stuffers | `"Invalid CAPTCHA code. Please try again."` |
| **CSRF Validation** | Missing or mismatched CSRF token | Cross-Site Request Forgery | `"Security validation failed. Please refresh the page and try again."` |
| **OTP Submission** | Incorrect 6-digit OTP code | OTP guessing | `"Invalid verification code. You have X attempt(s) remaining."` |
| **OTP Submission** | Max attempts ($3$) exceeded | OTP brute-force bypass | `"Maximum verification attempts exceeded. Please request a new code."` |
| **OTP Submission** | Code past 4-minute seed expiry | Replay attacks using stale codes | `"Verification code has expired. Please request a new code."` |
| **OTP Resend** | Cooldown timer ($60$s) not elapsed | Mail server flooding / SMS spamming | `"Please wait X seconds before requesting another code."` |
| **Password Reset** | Wrong security question answer | Unauthorized account takeover | `"The security answer provided is incorrect."` |
| **Change Password** | Password reuse (last 3 passwords) | Stolen credential recycling | `"You cannot reuse any of your last 3 passwords. Please choose a different password."` |
| **Direct URL Access** | Accessing `change_password.php` without token | Authorization bypass (Protection S5) | Redirect to `login.php` with session warning: `"Unauthorized access attempt."` |
| **Access Control** | Patient navigating to `admin_dashboard.php` | Privilege escalation / unauthorized data viewing | HTTP 403 Forbidden: `"Access Denied: Administrator privilege required."` |
| **Session State** | Inactive for $> 9$ minutes | Session hijacking on unattended workstation | Redirect to `login.php?timeout=1`: `"Your session has expired due to 9 minutes of inactivity. Please log in again."` |
| **Privacy Consent** | Consent checkbox unselected on registration | RA 10173 consent requirement | `"You must agree to the privacy notice and consent terms to register."` |
