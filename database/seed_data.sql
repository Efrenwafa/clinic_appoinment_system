
USE `clinic_appointment_db`;

-- Clear existing data for a fresh seed
DELETE FROM `security_flags`;
DELETE FROM `activity_logs`;
DELETE FROM `appointments`;
DELETE FROM `password_history`;
DELETE FROM `otps`;
DELETE FROM `authentication_answers`;
DELETE FROM `ip_blocks`;
DELETE FROM `users`;

-- 1. Insert 3 Users (1 Admin, 2 Patients)
-- All test emails route to user's real inbox (efren.castillano@csucc.edu.ph) via Gmail plus-addressing
INSERT INTO `users` (
    `id_number`, `first_name`, `middle_name`, `family_name`, `name_extension`,
    `birthdate`, `age`, `purok_street`, `barangay`, `municipality_city`,
    `province`, `country`, `zip_code`, `email`, `username`, `password_hash`,
    `contact_number_encrypted`, `patient_type`, `preferred_department`,
    `role`, `is_verified`, `failed_logins`, `lockout_tier`, `privacy_consent_at`,
    `created_at`, `updated_at`
) VALUES 
('2023-0001', 'Admin', NULL, 'Officer', NULL, '1990-01-15', 36, 'Purok 1 Ampayon', 'Ampayon', 'Butuan City', 'Agusan Del Norte', 'Philippines', '8600', 'efren.castillano@csucc.edu.ph', 'admin_monitor', '$2y$10$9V8bbvD2/piEq9ZqKKL5n.jBdZ8/m74cIEdwbaajSw.gTNg2dTmei', 'f5HavGU3h2I5KxStyRgE1JY6AIrngP1x7yrs8yUUrQI=', 'Returning Patient', 'General Medicine', 'admin', 1, 0, 0, '2026-11-14 00:57:22', '2026-11-14 00:57:22', '2026-11-15 00:57:22'),
('2023-0101', 'Juan', 'Carlo', 'Dela Cruz', NULL, '2001-05-20', 25, 'Purok 2 Libertad', 'Libertad', 'Butuan City', 'Agusan Del Norte', 'Philippines', '8600', 'efren.castillano+juan@csucc.edu.ph', 'patient_juan', '$2y$10$SKulwJKPpEWt9Ve9FcOBG.VrXuLk8t4XQp4iNH3Xt5vhvrCbota0u', 'WHlBpayMg+Pz70NhZhrkSQJMBIJ/5kMvvgJUFtufMc8=', 'New Patient', 'General Medicine', 'patient', 1, 0, 0, '2026-11-14 00:57:22', '2026-11-14 00:57:22', '2026-11-15 00:57:22'),
('2023-0102', 'Maria', 'Clara', 'Santos', NULL, '2003-09-10', 23, 'Purok 5 Villa Kananga', 'Villa Kananga', 'Butuan City', 'Agusan Del Norte', 'Philippines', '8600', 'efren.castillano+maria@csucc.edu.ph', 'patient_maria', '$2y$10$lXwgud2Krbx/GAwLzlfe/.GvxjAtGmyLFoCJVh/DF4txXTx8xv1QG', '7/HT6l5lHmSN/Vb2L5TZs35RaO58r6/9XVECHk8uVhM=', 'Returning Patient', 'Dental', 'patient', 1, 0, 0, '2026-11-14 00:57:22', '2026-11-14 00:57:22', '2026-11-15 00:57:22');

-- 2. Insert Security Answers for Q2, Q5, Q7 (Section 5)
INSERT INTO `authentication_answers` (`user_id`, `question_key`, `answer_hash`, `created_at`) VALUES
('2023-0001', 'Q2', '$2y$10$I0QkHeYDjm9ahP.Cn9ZvLufQgKvdJrpqbK4cuYw01iCXOxlwEIK7a', '2026-11-15 00:57:22'),
('2023-0001', 'Q5', '$2y$10$lCo7laogwmu4uL7nBD.yAeCl.ecAad5xh5Yx830ssoT4CtdZeOU02', '2026-11-15 00:57:22'),
('2023-0001', 'Q7', '$2y$10$qk8aoofeXURY4WTJYgfd.ObyDBegXKC3TgpH8f0G7xvWNlHMOnVtO', '2026-11-15 00:57:22'),
('2023-0101', 'Q2', '$2y$10$I0QkHeYDjm9ahP.Cn9ZvLufQgKvdJrpqbK4cuYw01iCXOxlwEIK7a', '2026-11-15 00:57:22'),
('2023-0101', 'Q5', '$2y$10$lCo7laogwmu4uL7nBD.yAeCl.ecAad5xh5Yx830ssoT4CtdZeOU02', '2026-11-15 00:57:22'),
('2023-0101', 'Q7', '$2y$10$qk8aoofeXURY4WTJYgfd.ObyDBegXKC3TgpH8f0G7xvWNlHMOnVtO', '2026-11-15 00:57:22'),
('2023-0102', 'Q2', '$2y$10$I0QkHeYDjm9ahP.Cn9ZvLufQgKvdJrpqbK4cuYw01iCXOxlwEIK7a', '2026-11-15 00:57:22'),
('2023-0102', 'Q5', '$2y$10$lCo7laogwmu4uL7nBD.yAeCl.ecAad5xh5Yx830ssoT4CtdZeOU02', '2026-11-15 00:57:22'),
('2023-0102', 'Q7', '$2y$10$qk8aoofeXURY4WTJYgfd.ObyDBegXKC3TgpH8f0G7xvWNlHMOnVtO', '2026-11-15 00:57:22');

-- 3. Insert Password History (Protection 7)
INSERT INTO `password_history` (`user_id`, `password_hash`, `created_at`) VALUES
('2023-0001', '$2y$10$9V8bbvD2/piEq9ZqKKL5n.jBdZ8/m74cIEdwbaajSw.gTNg2dTmei', '2026-11-14 00:57:22'),
('2023-0101', '$2y$10$SKulwJKPpEWt9Ve9FcOBG.VrXuLk8t4XQp4iNH3Xt5vhvrCbota0u', '2026-11-14 00:57:22'),
('2023-0102', '$2y$10$lXwgud2Krbx/GAwLzlfe/.GvxjAtGmyLFoCJVh/DF4txXTx8xv1QG', '2026-11-14 00:57:22');

-- 4. Insert Appointments (At least 5 records)
INSERT INTO `appointments` (`id`, `patient_id`, `department`, `appointment_date`, `appointment_time`, `reason`, `status`, `created_at`, `updated_at`) VALUES
(1, '2023-0101', 'General Medicine', CURDATE(), '09:30:00', 'Annual student physical examination and medical clearance.', 'Confirmed', '2026-11-14 00:57:22', '2026-11-15 00:57:22'),
(2, '2023-0101', 'Dental', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:30:00', 'Routine dental cleaning and prophylaxis.', 'Pending', '2026-11-15 00:57:22', '2026-11-15 00:57:22'),
(3, '2023-0102', 'Dermatology', CURDATE(), '13:30:00', 'Skin rash consultation and allergy evaluation.', 'Pending', '2026-11-14 00:57:22', '2026-11-14 00:57:22'),
(4, '2023-0102', 'General Medicine', DATE_SUB(CURDATE(), INTERVAL 5 DAY), '08:30:00', 'Flu symptoms, headache, and fever evaluation.', 'Completed', DATE_SUB(CURDATE(), INTERVAL 5 DAY), '2026-11-15 00:57:22'),
(5, '2023-0101', 'Cardiology', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '14:30:00', 'Electrocardiogram (ECG) screening follow-up.', 'Cancelled', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '2026-11-15 00:57:22');

-- 5. Insert Activity Logs (Section 13.4)
INSERT INTO `activity_logs` (`event_type`, `username_typed`, `user_id`, `ip_address`, `user_agent`, `details`, `created_at`) VALUES
('REGISTERED', 'patient_juan', '2023-0101', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'New patient account registered.', '2026-11-14 00:57:22'),
('ACCOUNT_VERIFIED', 'patient_juan', '2023-0101', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'Registration OTP verified successfully.', '2026-11-14 00:57:22'),
('REGISTERED', 'patient_maria', '2023-0102', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'New patient account registered.', '2026-11-14 00:57:22'),
('ACCOUNT_VERIFIED', 'patient_maria', '2023-0102', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'Registration OTP verified successfully.', '2026-11-14 00:57:22'),
('LOGIN_SUCCESS', 'admin_monitor', '2023-0001', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'Administrator authenticated via 2-step verification.', '2026-11-15 00:57:22'),
('ADMIN_VIEWED_DASHBOARD', 'admin_monitor', '2023-0001', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'Administrator accessed security monitoring dashboard.', '2026-11-15 00:57:22');
