-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: clinic_appointment_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `username_typed` varchar(100) DEFAULT NULL,
  `user_id` varchar(9) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_logs_event_type` (`event_type`),
  KEY `idx_logs_ip` (`ip_address`),
  KEY `idx_logs_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,'REGISTERED','patient_juan','2023-0101','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64)','New patient account registered.','2026-11-14 00:57:22'),(2,'ACCOUNT_VERIFIED','patient_juan','2023-0101','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64)','Registration OTP verified successfully.','2026-11-14 00:57:22'),(3,'REGISTERED','patient_maria','2023-0102','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64)','New patient account registered.','2026-11-14 00:57:22'),(4,'ACCOUNT_VERIFIED','patient_maria','2023-0102','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64)','Registration OTP verified successfully.','2026-11-14 00:57:22'),(5,'LOGIN_SUCCESS','admin_monitor','2023-0001','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64)','Administrator authenticated via 2-step verification.','2026-11-15 00:57:22'),(6,'ADMIN_VIEWED_DASHBOARD','admin_monitor','2023-0001','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64)','Administrator accessed security monitoring dashboard.','2026-11-15 00:57:22'),(7,'CAPTCHA_FAILED',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','Registration CAPTCHA failed.','2026-11-15 01:15:50'),(8,'CAPTCHA_FAILED',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','Registration CAPTCHA failed.','2026-11-15 01:16:18'),(9,'CAPTCHA_FAILED',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','Registration CAPTCHA failed.','2026-11-15 01:16:22'),(10,'OTP_SENT',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','Purpose: login sent to efren.castillano+maria@csucc.edu.ph','2026-11-16 13:41:22'),(11,'OTP_SENT',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','Purpose: login sent to efren.castillano@csucc.edu.ph','2026-11-16 13:42:18'),(12,'LOGIN_SUCCESS','admin_monitor','2023-0001','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','Successful two-step login (Password + OTP). Session established.','2026-11-16 13:42:48'),(13,'ADMIN_VIEWED_DASHBOARD','admin_monitor','2023-0001','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','Administrator accessed security monitoring dashboard.','2026-11-16 13:42:48'),(14,'LOGOUT','admin_monitor','2023-0001','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0','User logged out.','2026-11-16 13:43:02');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` varchar(9) NOT NULL,
  `department` varchar(50) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_appts_patient` (`patient_id`),
  KEY `idx_appts_status` (`status`),
  KEY `idx_appts_date` (`appointment_date`),
  CONSTRAINT `fk_appts_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id_number`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (1,'2023-0101','General Medicine','2026-11-15','09:30:00','Annual student physical examination and medical clearance.','Confirmed',NULL,'2026-11-14 00:57:22','2026-11-15 00:57:22'),(2,'2023-0101','Dental','2026-11-17','10:30:00','Routine dental cleaning and prophylaxis.','Pending',NULL,'2026-11-15 00:57:22','2026-11-15 00:57:22'),(3,'2023-0102','Dermatology','2026-11-15','13:30:00','Skin rash consultation and allergy evaluation.','Pending',NULL,'2026-11-14 00:57:22','2026-11-14 00:57:22'),(4,'2023-0102','General Medicine','2026-11-10','08:30:00','Flu symptoms, headache, and fever evaluation.','Completed',NULL,'2026-11-10 00:00:00','2026-11-15 00:57:22'),(5,'2023-0101','Cardiology','2026-11-13','14:30:00','Electrocardiogram (ECG) screening follow-up.','Cancelled',NULL,'2026-11-13 00:00:00','2026-11-15 00:57:22');
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `authentication_answers`
--

DROP TABLE IF EXISTS `authentication_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `authentication_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(9) NOT NULL,
  `question_key` varchar(10) NOT NULL,
  `answer_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_question` (`user_id`,`question_key`),
  CONSTRAINT `fk_auth_answers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_number`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authentication_answers`
--

LOCK TABLES `authentication_answers` WRITE;
/*!40000 ALTER TABLE `authentication_answers` DISABLE KEYS */;
INSERT INTO `authentication_answers` VALUES (1,'2023-0001','Q2','$2y$10$I0QkHeYDjm9ahP.Cn9ZvLufQgKvdJrpqbK4cuYw01iCXOxlwEIK7a','2026-11-15 00:57:22'),(2,'2023-0001','Q5','$2y$10$lCo7laogwmu4uL7nBD.yAeCl.ecAad5xh5Yx830ssoT4CtdZeOU02','2026-11-15 00:57:22'),(3,'2023-0001','Q7','$2y$10$qk8aoofeXURY4WTJYgfd.ObyDBegXKC3TgpH8f0G7xvWNlHMOnVtO','2026-11-15 00:57:22'),(4,'2023-0101','Q2','$2y$10$I0QkHeYDjm9ahP.Cn9ZvLufQgKvdJrpqbK4cuYw01iCXOxlwEIK7a','2026-11-15 00:57:22'),(5,'2023-0101','Q5','$2y$10$lCo7laogwmu4uL7nBD.yAeCl.ecAad5xh5Yx830ssoT4CtdZeOU02','2026-11-15 00:57:22'),(6,'2023-0101','Q7','$2y$10$qk8aoofeXURY4WTJYgfd.ObyDBegXKC3TgpH8f0G7xvWNlHMOnVtO','2026-11-15 00:57:22'),(7,'2023-0102','Q2','$2y$10$I0QkHeYDjm9ahP.Cn9ZvLufQgKvdJrpqbK4cuYw01iCXOxlwEIK7a','2026-11-15 00:57:22'),(8,'2023-0102','Q5','$2y$10$lCo7laogwmu4uL7nBD.yAeCl.ecAad5xh5Yx830ssoT4CtdZeOU02','2026-11-15 00:57:22'),(9,'2023-0102','Q7','$2y$10$qk8aoofeXURY4WTJYgfd.ObyDBegXKC3TgpH8f0G7xvWNlHMOnVtO','2026-11-15 00:57:22');
/*!40000 ALTER TABLE `authentication_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ip_blocks`
--

DROP TABLE IF EXISTS `ip_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ip_blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `failure_count` int(11) NOT NULL DEFAULT 1,
  `first_failure_at` datetime NOT NULL,
  `blocked_until` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ip_blocks_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ip_blocks`
--

LOCK TABLES `ip_blocks` WRITE;
/*!40000 ALTER TABLE `ip_blocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `ip_blocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otps`
--

DROP TABLE IF EXISTS `otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(9) NOT NULL,
  `email` varchar(100) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `purpose` enum('register','login','reset') NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_otps_lookup` (`user_id`,`purpose`,`is_used`,`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otps`
--

LOCK TABLES `otps` WRITE;
/*!40000 ALTER TABLE `otps` DISABLE KEYS */;
INSERT INTO `otps` VALUES (1,'2023-0102','efren.castillano+maria@csucc.edu.ph','$2y$10$uJlydw0kDceuP3TGmD4k.e5Jr0V1gOY33vwCTz/Zqokner.NtMPKy','login',0,0,'2026-11-16 13:46:14','2026-11-16 13:41:14'),(2,'2023-0001','efren.castillano@csucc.edu.ph','$2y$10$wZJiqIyXHfREJXZAqFLN7OotcnEvE9QgAgyIl/WXNLZbWoIxpNybC','login',0,1,'2026-11-16 13:47:12','2026-11-16 13:42:12');
/*!40000 ALTER TABLE `otps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_history`
--

DROP TABLE IF EXISTS `password_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(9) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pwd_history_user` (`user_id`,`created_at`),
  CONSTRAINT `fk_pwd_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_number`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_history`
--

LOCK TABLES `password_history` WRITE;
/*!40000 ALTER TABLE `password_history` DISABLE KEYS */;
INSERT INTO `password_history` VALUES (1,'2023-0001','$2y$10$9V8bbvD2/piEq9ZqKKL5n.jBdZ8/m74cIEdwbaajSw.gTNg2dTmei','2026-11-14 00:57:22'),(2,'2023-0101','$2y$10$SKulwJKPpEWt9Ve9FcOBG.VrXuLk8t4XQp4iNH3Xt5vhvrCbota0u','2026-11-14 00:57:22'),(3,'2023-0102','$2y$10$lXwgud2Krbx/GAwLzlfe/.GvxjAtGmyLFoCJVh/DF4txXTx8xv1QG','2026-11-14 00:57:22');
/*!40000 ALTER TABLE `password_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_flags`
--

DROP TABLE IF EXISTS `security_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_flags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pattern_type` varchar(50) NOT NULL,
  `severity` enum('LOW','MED','HIGH') NOT NULL DEFAULT 'MED',
  `target` varchar(100) NOT NULL,
  `details` text NOT NULL,
  `flagged_date` date NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_flag_unique_daily` (`pattern_type`,`target`,`flagged_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_flags`
--

LOCK TABLES `security_flags` WRITE;
/*!40000 ALTER TABLE `security_flags` DISABLE KEYS */;
INSERT INTO `security_flags` VALUES (1,'Off-hours log-in','LOW','admin_monitor','Successful login during off-hours at 12:57 AM','2026-11-15','2026-11-15 07:57:31');
/*!40000 ALTER TABLE `security_flags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id_number` varchar(9) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `family_name` varchar(50) NOT NULL,
  `name_extension` varchar(10) DEFAULT NULL,
  `birthdate` date NOT NULL,
  `age` int(11) NOT NULL,
  `purok_street` varchar(50) NOT NULL,
  `barangay` varchar(50) NOT NULL,
  `municipality_city` varchar(50) NOT NULL,
  `province` varchar(50) NOT NULL,
  `country` varchar(50) NOT NULL,
  `zip_code` varchar(4) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `contact_number_encrypted` text NOT NULL,
  `patient_type` varchar(25) NOT NULL,
  `preferred_department` varchar(50) NOT NULL,
  `emergency_contact_encrypted` text DEFAULT NULL,
  `role` enum('patient','admin') NOT NULL DEFAULT 'patient',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `failed_logins` int(11) NOT NULL DEFAULT 0,
  `lockout_tier` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `privacy_consent_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_number`),
  UNIQUE KEY `idx_users_email` (`email`),
  UNIQUE KEY `idx_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('2023-0001','Admin',NULL,'Officer',NULL,'1990-01-15',36,'Purok 1 Ampayon','Ampayon','Butuan City','Agusan Del Norte','Philippines','8600','efren.castillano@csucc.edu.ph','admin_monitor','$2y$10$9V8bbvD2/piEq9ZqKKL5n.jBdZ8/m74cIEdwbaajSw.gTNg2dTmei','f5HavGU3h2I5KxStyRgE1JY6AIrngP1x7yrs8yUUrQI=','Returning Patient','General Medicine',NULL,'admin',1,0,0,NULL,'2026-11-14 00:57:22','2026-11-14 00:57:22','2026-11-16 20:42:48'),('2023-0101','Juan','Carlo','Dela Cruz',NULL,'2001-05-20',25,'Purok 2 Libertad','Libertad','Butuan City','Agusan Del Norte','Philippines','8600','efren.castillano+juan@csucc.edu.ph','patient_juan','$2y$10$SKulwJKPpEWt9Ve9FcOBG.VrXuLk8t4XQp4iNH3Xt5vhvrCbota0u','WHlBpayMg+Pz70NhZhrkSQJMBIJ/5kMvvgJUFtufMc8=','New Patient','General Medicine',NULL,'patient',1,0,0,NULL,'2026-11-14 00:57:22','2026-11-14 00:57:22','2026-11-15 00:57:22'),('2023-0102','Maria','Clara','Santos',NULL,'2003-09-10',23,'Purok 5 Villa Kananga','Villa Kananga','Butuan City','Agusan Del Norte','Philippines','8600','efren.castillano+maria@csucc.edu.ph','patient_maria','$2y$10$lXwgud2Krbx/GAwLzlfe/.GvxjAtGmyLFoCJVh/DF4txXTx8xv1QG','7/HT6l5lHmSN/Vb2L5TZs35RaO58r6/9XVECHk8uVhM=','Returning Patient','Dental',NULL,'patient',1,0,0,NULL,'2026-11-14 00:57:22','2026-11-14 00:57:22','2026-11-15 00:57:22');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-17 22:42:16
