-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: corms_database
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
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'user',
  `term` varchar(255) DEFAULT NULL,
  `school_year` varchar(255) DEFAULT NULL,
  `sc_president` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `org_name` varchar(255) DEFAULT NULL,
  `org_type` varchar(255) DEFAULT NULL,
  `college` varchar(255) DEFAULT NULL,
  `organization_id` bigint(20) unsigned DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','admin@admin.com',NULL,NULL,'$2y$12$3wyelZbfLAtMtVyWOypIBenVXMP2kkq4TLA96WoRMTel6TfU4WDjS','admin',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-03-30 05:33:20','2026-03-30 05:33:20'),(2,'ITOUCH','itouch@gmail.com',NULL,NULL,'$2y$12$v3HJibkzanP6W5VftCGPf.cn.28CBrSRPWpnzJ64uZze1ZrmHSB7a','user','2nd Term','2026-2027',NULL,'Secretary',NULL,'Publication','CICS',NULL,NULL,'2026-03-30 05:33:20','2026-03-30 17:11:52'),(3,'CTE-SC','ctesc@gmail.com',NULL,NULL,'$2y$12$iT/0VRn/g9jThH8hMatJCui1R1ZdnDMFSQy9lFs5sZwd0xHfza6p.','user','1st Term','2026-2027',NULL,'President',NULL,NULL,'CTED',NULL,NULL,'2026-03-30 05:33:21','2026-03-30 17:05:15'),(4,'CICS-SC','cicssc@gmail.com',NULL,NULL,'$2y$12$SWL5U2cGuuRr9SKlAvGxQOx0HnEJChCEIDMzQKoerGgNVK38rTuSe','user','1st Term','2026-2027',NULL,'President',NULL,NULL,'CICS',NULL,NULL,'2026-03-30 05:33:21','2026-03-30 17:25:31'),(5,'CHM-SC','chmsc@gmail.com',NULL,NULL,'$2y$12$9Nai89s2js7nb/vwXohM8OBSa6YpLeXb8dj6DSTnZ3p93ahVqFh7a','user','1st Term','2026-2027',NULL,'President','CHM-SC',NULL,'CHM',NULL,NULL,'2026-03-30 16:48:07','2026-03-30 16:50:20');
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

-- Dump completed on 2026-07-15  8:16:59
