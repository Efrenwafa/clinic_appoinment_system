-- ========================================================
-- IT 107 - Information Assurance and Security 1
-- Final Laboratory Project: Secure Clinic Appointment and Monitoring System
-- Database Schema: clinic_appointment_db
-- Seed: D1 = 9, D2 = 1
-- ========================================================

CREATE DATABASE IF NOT EXISTS `clinic_appointment_db`
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `clinic_appointment_db`;

-- 1. USERS TABLE
-- Stores patient & administrative identity and authentication attributes.
-- Contact number encrypted at rest with AES-256 (Protection 13).
CREATE TABLE IF NOT EXISTS `users` (
    `id_number` VARCHAR(9) NOT NULL, -- Format: xxxx-xxxx (Primary Key)
    `first_name` VARCHAR(50) NOT NULL, -- Universal limit: max 50 chars (Section 2.3)
    `middle_name` VARCHAR(50) NULL,
    `family_name` VARCHAR(50) NOT NULL,
    `name_extension` VARCHAR(10) NULL, -- Jr., Sr., III, etc.
    `birthdate` DATE NOT NULL,
    `age` INT NOT NULL, -- Server recalculated (Must be >= 18)
    `purok_street` VARCHAR(50) NOT NULL,
    `barangay` VARCHAR(50) NOT NULL,
    `municipality_city` VARCHAR(50) NOT NULL,
    `province` VARCHAR(50) NOT NULL,
    `country` VARCHAR(50) NOT NULL,
    `zip_code` VARCHAR(4) NOT NULL, -- Exactly 4 digits
    `email` VARCHAR(100) NOT NULL,
    `username` VARCHAR(20) NOT NULL, -- Universal limits: 6 to 20 chars (Section 2.3)
    `password_hash` VARCHAR(255) NOT NULL, -- Never stored plaintext (Protection 1, S1)
    `contact_number_encrypted` TEXT NOT NULL, -- Sensitive field encrypted with AES-256 (Protection 13)
    `patient_type` VARCHAR(25) NOT NULL, -- New Patient / Returning Patient
    `preferred_department` VARCHAR(50) NOT NULL, -- Configured Clinic Departments
    `emergency_contact_encrypted` TEXT NULL, -- Optional (AES-256 encrypted if provided)
    `role` ENUM('patient', 'admin') NOT NULL DEFAULT 'patient',
    `is_verified` TINYINT(1) NOT NULL DEFAULT 0, -- 0 = Pending OTP, 1 = Verified
    `failed_logins` INT NOT NULL DEFAULT 0, -- Consecutive failed login attempts
    `lockout_tier` INT NOT NULL DEFAULT 0, -- 0 = None, 1 = Tier 1 (24s), 2 = Tier 2 (48s), 3 = Tier 3 (96s)
    `locked_until` DATETIME NULL, -- Enforced on server
    `privacy_consent_at` DATETIME NOT NULL, -- Timestamp of RA 10173 consent
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id_number`),
    UNIQUE KEY `idx_users_email` (`email`),
    UNIQUE KEY `idx_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. AUTHENTICATION ANSWERS TABLE
-- Stores hashed answers to assigned security questions (Q2, Q5, Q7)
-- Normalized to lowercase and trimmed before hashing (Section 5)
CREATE TABLE IF NOT EXISTS `authentication_answers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(9) NOT NULL,
    `question_key` VARCHAR(10) NOT NULL, -- 'Q2', 'Q5', 'Q7'
    `answer_hash` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL,
    UNIQUE KEY `idx_user_question` (`user_id`, `question_key`),
    CONSTRAINT `fk_auth_answers_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id_number`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. OTPS TABLE
-- 6-digit one-time codes generated with random_int(), stored hashed.
-- Seed expiry: 4 minutes (3 + (1 mod 3)). Max 3 attempts before cancellation.
CREATE TABLE IF NOT EXISTS `otps` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(9) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `otp_hash` VARCHAR(255) NOT NULL,
    `purpose` ENUM('register', 'login', 'reset') NOT NULL,
    `attempts` INT NOT NULL DEFAULT 0,
    `is_used` TINYINT(1) NOT NULL DEFAULT 0,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_otps_lookup` (`user_id`, `purpose`, `is_used`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. PASSWORD HISTORY TABLE
-- Retains hashes of last 3 passwords to prevent password reuse (Protection 7)
CREATE TABLE IF NOT EXISTS `password_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(9) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_pwd_history_user` (`user_id`, `created_at`),
    CONSTRAINT `fk_pwd_history_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id_number`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. IP BLOCKS TABLE
-- Blocks IP after 9 failures within 10 minutes (Seed limit: 8 + D2 = 9)
CREATE TABLE IF NOT EXISTS `ip_blocks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `failure_count` INT NOT NULL DEFAULT 1,
    `first_failure_at` DATETIME NOT NULL,
    `blocked_until` DATETIME NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    UNIQUE KEY `idx_ip_blocks_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ACTIVITY LOGS TABLE
-- Read-only, append-only security log for all authentication events (Section 13.4)
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_type` VARCHAR(50) NOT NULL,
    `username_typed` VARCHAR(100) NULL,
    `user_id` VARCHAR(9) NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NOT NULL,
    `details` TEXT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_logs_event_type` (`event_type`),
    INDEX `idx_logs_ip` (`ip_address`),
    INDEX `idx_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. SECURITY FLAGS TABLE
-- Findings recorded by CLI monitoring script (Section 14)
CREATE TABLE IF NOT EXISTS `security_flags` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pattern_type` VARCHAR(50) NOT NULL, -- 'IP over threshold', 'Account targeted', 'OTP guessing', 'Off-hours log-in'
    `severity` ENUM('LOW', 'MED', 'HIGH') NOT NULL DEFAULT 'MED',
    `target` VARCHAR(100) NOT NULL,
    `details` TEXT NOT NULL,
    `flagged_date` DATE NOT NULL,
    `created_at` DATETIME NOT NULL,
    UNIQUE KEY `idx_flag_unique_daily` (`pattern_type`, `target`, `flagged_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. APPOINTMENTS TABLE
-- Clinic appointment booking, scheduling, and status tracking
CREATE TABLE IF NOT EXISTS `appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` VARCHAR(9) NOT NULL,
    `department` VARCHAR(50) NOT NULL,
    `appointment_date` DATE NOT NULL,
    `appointment_time` TIME NOT NULL,
    `reason` TEXT NOT NULL,
    `status` ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    `admin_notes` TEXT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    INDEX `idx_appts_patient` (`patient_id`),
    INDEX `idx_appts_status` (`status`),
    INDEX `idx_appts_date` (`appointment_date`),
    CONSTRAINT `fk_appts_patient` FOREIGN KEY (`patient_id`)
        REFERENCES `users` (`id_number`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
