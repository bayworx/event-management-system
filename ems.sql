/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.3-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: event_management
-- ------------------------------------------------------
-- Server version	11.8.3-MariaDB-1build1 from Ubuntu

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `administrators`
--

DROP TABLE IF EXISTS `administrators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `administrators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`roles`)),
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `department` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_token_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_73A716FE7927C74` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `administrators`
--

LOCK TABLES `administrators` WRITE;
/*!40000 ALTER TABLE `administrators` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `administrators` VALUES
(1,'Test Admin','admin@test.com','[\"ROLE_ADMIN\"]','$2y$13$m6FNVKfRT0tz4oh8W3FUSudDntk5Z0KkXyC/NCrzpppeO5NsGHhHm',1,'2025-10-08 04:09:17','2025-11-27 05:44:01',1,NULL,NULL,NULL),
(2,'Jeff Hill','jeff@bayworx.com','[\"ROLE_ADMIN\"]','$2y$13$eKc93KsrAZJnLFDFxbdzZ.BmI/Iaq17DtLywEajfSuHuP0I4MQ0EC',1,'2025-10-08 04:14:29','2025-12-15 03:26:35',1,'IT',NULL,NULL);
/*!40000 ALTER TABLE `administrators` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `agenda_items`
--

DROP TABLE IF EXISTS `agenda_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `agenda_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `presenter_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `item_type` varchar(50) NOT NULL,
  `speaker` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B126BDD371F7E88B` (`event_id`),
  KEY `IDX_B126BDD3DDE4C635` (`presenter_id`),
  CONSTRAINT `FK_B126BDD371F7E88B` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`),
  CONSTRAINT `FK_B126BDD3DDE4C635` FOREIGN KEY (`presenter_id`) REFERENCES `presenter` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agenda_items`
--

LOCK TABLES `agenda_items` WRITE;
/*!40000 ALTER TABLE `agenda_items` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `agenda_items` VALUES
(1,9,NULL,'Opening Keynote',NULL,'2024-06-15 09:00:00',NULL,'session',NULL,NULL,0,1,'2025-10-10 19:05:17',NULL),
(4,10,NULL,'Welcome!','Opening remarks','2025-11-25 09:00:00',NULL,'session',NULL,NULL,0,1,'2025-10-11 00:05:47','2025-10-23 02:43:27'),
(5,11,NULL,'HR Best Practices in the modern era',NULL,'2026-06-11 09:00:00',NULL,'session',NULL,NULL,0,1,'2025-10-11 00:05:47',NULL),
(7,12,NULL,'Teardown and rebuild',NULL,'2026-01-15 09:00:00',NULL,'session',NULL,NULL,0,1,'2025-10-11 00:05:47',NULL),
(8,9,2,'Securing your network with a limited budget','Additional Workshop','2025-12-15 11:00:00','2025-12-15 11:30:00','workshop','Lisa Adams','Chester Ballroom',2,1,'2025-12-15 03:43:11',NULL);
/*!40000 ALTER TABLE `agenda_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `app_config`
--

DROP TABLE IF EXISTS `app_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(255) NOT NULL,
  `config_value` longtext DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `value_type` varchar(50) NOT NULL DEFAULT 'string',
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_318942FC95D1CAA6` (`config_key`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_config`
--

LOCK TABLES `app_config` WRITE;
/*!40000 ALTER TABLE `app_config` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `app_config` VALUES
(1,'company.name','BAYWORX','Company','Company name displayed throughout the application','string',0,'2025-10-08 15:02:26','2025-10-11 18:52:14'),
(2,'company.description','Professional Event Management System','Company','Company description or tagline','string',0,'2025-10-08 15:02:26','2025-10-11 18:52:14'),
(3,'company.address','11740 Dublin Blvd Suite 205\r\nDublin, CA 94568','Company','Company address','string',0,'2025-10-08 15:02:26','2025-10-11 18:52:14'),
(4,'company.phone','+1 (925) 875-0504','Company','Company phone number','string',0,'2025-10-08 15:02:26','2025-10-11 18:52:14'),
(5,'company.email','info@bayworx.com','Company','Company contact email','string',0,'2025-10-08 15:02:26','2025-10-11 18:52:14'),
(6,'company.website','https://bayworx.com','Company','Company website URL','string',0,'2025-10-08 15:02:26','2025-10-11 18:52:14'),
(7,'company.logo','logos/bayworx-logo-big-new-68e6c3e582b37.jpg','Company','Company logo filename','string',0,'2025-10-08 15:02:26','2025-10-08 20:04:53'),
(8,'app.timezone','America/Los_Angeles','Application','Default application timezone','string',0,'2025-10-08 15:02:26','2025-11-07 15:02:33'),
(9,'app.date_format','M j, Y','Application','Default date display format','string',0,'2025-10-08 15:02:26','2025-11-07 15:02:33'),
(10,'app.time_format','g:i A','Application','Default time display format','string',0,'2025-10-08 15:02:26','2025-11-07 15:02:33'),
(11,'app.items_per_page','25','Application','Default number of items per page in lists','integer',0,'2025-10-08 15:02:26','2025-11-07 15:02:33'),
(12,'app.theme','blue','Application','Application theme','string',0,'2025-10-08 15:02:26','2025-11-07 15:02:33'),
(13,'app.maintenance_mode','0','Application','Enable maintenance mode','string',0,'2025-10-08 15:02:26','2025-11-07 15:02:33'),
(14,'app.version','1.0.2','Application','Application version number','string',0,'2025-10-08 15:02:26','2025-11-07 15:02:33'),
(15,'email.from_name','BAYWORX EMS','Email','Default sender name for emails','string',0,'2025-10-08 15:02:26','2025-10-08 15:02:26'),
(16,'email.from_email','noreply@example.com','Email','Default sender email address','string',0,'2025-10-08 15:02:26','2025-10-08 15:02:26'),
(17,'email.signature','Best regards,<br>The BAYWORX EMS Team','Email','Default email signature','string',0,'2025-10-08 15:02:26','2025-10-08 15:02:26'),
(18,'events.default_max_attendees','100','Events','Default maximum number of attendees for new events','integer',0,'2025-10-08 15:02:26','2025-10-08 15:02:26'),
(19,'events.enable_registration','1','Events','Enable event registration functionality','boolean',0,'2025-10-08 15:02:26','2025-10-08 15:02:26'),
(20,'events.require_approval','0','Events','Require admin approval for event registrations','boolean',0,'2025-10-08 15:02:26','2025-10-08 15:02:26'),
(21,'events.allow_cancellation','1','Events','Allow attendees to cancel their registration','boolean',0,'2025-10-08 15:02:26','2025-10-08 15:02:26'),
(22,'footer.text',NULL,'Footer','Custom footer text or description','string',0,'2025-10-08 18:26:41','2025-10-08 18:26:41'),
(23,'footer.show_company_info','1','Footer','Show company information in footer','string',0,'2025-10-08 18:26:41','2025-10-09 04:41:31'),
(24,'footer.show_version','1','Footer','Show application version in footer','string',0,'2025-10-08 18:26:41','2025-10-09 04:41:31'),
(25,'footer.copyright_text',NULL,'Footer','Custom copyright text (leave empty for auto-generated)','string',0,'2025-10-08 18:26:41','2025-10-08 18:26:41'),
(26,'footer.link_1_text','Privacy Policy','Footer','First footer link text','string',0,'2025-10-08 18:26:41','2025-10-09 04:41:31'),
(27,'footer.link_1_url','https://bayworx.com/privacy','Footer','First footer link URL','string',0,'2025-10-08 18:26:41','2025-10-09 04:41:31'),
(28,'footer.link_2_text','Terms of Service','Footer','Second footer link text','string',0,'2025-10-08 18:26:41','2025-10-09 04:41:31'),
(29,'footer.link_2_url','https://bayworx.com/terms','Footer','Second footer link URL','string',0,'2025-10-08 18:26:41','2025-10-09 04:41:31'),
(30,'footer.link_3_text','Contact Us','Footer','Third footer link text','string',0,'2025-10-08 18:26:41','2025-10-09 04:41:31'),
(31,'footer.link_3_url','https://bayworx.com/contact','Footer','Third footer link URL','string',0,'2025-10-08 18:26:41','2025-10-09 04:41:31'),
(32,'footer.social_facebook',NULL,'Footer','Facebook URL','string',0,'2025-10-08 18:26:41','2025-10-08 18:26:41'),
(33,'footer.social_twitter',NULL,'Footer','Twitter/X URL','string',0,'2025-10-08 18:26:41','2025-10-08 18:26:41'),
(34,'footer.social_linkedin',NULL,'Footer','LinkedIn URL','string',0,'2025-10-08 18:26:41','2025-10-08 18:26:41'),
(35,'footer.social_instagram',NULL,'Footer','Instagram URL','string',0,'2025-10-08 18:26:41','2025-10-08 18:26:41');
/*!40000 ALTER TABLE `app_config` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `attendees`
--

DROP TABLE IF EXISTS `attendees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`roles`)),
  `password` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `is_checked_in` tinyint(1) NOT NULL DEFAULT 0,
  `checked_in_at` datetime DEFAULT NULL,
  `registered_at` datetime NOT NULL,
  `notes` longtext DEFAULT NULL,
  `badge_data` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email_per_event` (`email`,`event_id`),
  KEY `IDX_C8C96B2571F7E88B` (`event_id`),
  CONSTRAINT `FK_C8C96B2571F7E88B` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendees`
--

LOCK TABLES `attendees` WRITE;
/*!40000 ALTER TABLE `attendees` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `attendees` VALUES
(1,1,'Jeff Hill','jeff@bayworx.com','9258750504','BAYWORX LLC','','[\"ROLE_ATTENDEE\"]',NULL,1,'e576d8c9f4e75afe5bee02bee7fe12f231ea6394adba7e6de635370be764e3d7','2025-10-09 09:47:07',1,'2025-10-09 09:47:19','2025-10-08 20:08:08','',NULL),
(2,1,'Info Account','info@bayworx.com','9253212731','test company','admin','[\"ROLE_ATTENDEE\"]',NULL,1,'f35614262578308942543a5d6f02618f8ea818f6e354ea118b24a66ecf01c7e1','2025-11-12 18:52:19',0,NULL,'2025-11-12 17:58:33','none',NULL),
(3,1,'support account','support@bayworx.com','9258750504','BAYWORX LLC','CEO','[\"ROLE_ATTENDEE\"]',NULL,0,'b3020571e42b67628b7ae133e43923a0b964a1719d83cb31244ee2c0efae53c6',NULL,0,NULL,'2025-11-13 02:01:04','thanks',NULL),
(4,4,'James Hillis','ebjoker4@yahoo.com','9253212731','BAYWORX LLC','admin','[\"ROLE_ATTENDEE\"]',NULL,0,'03316551658f9f7357521b0eb880366219fe75f12456f93d8981f7f459801b1c',NULL,0,NULL,'2025-11-26 20:30:52','',NULL),
(5,9,'test','ebjoker4@yahoo.com','','','','[\"ROLE_ATTENDEE\"]',NULL,1,'28791cb109a31f2621b709cb4f98ace52aa341fcd80607ae87dde7744d3a37c5','2025-11-26 21:05:58',0,NULL,'2025-11-26 21:05:20','',NULL),
(6,11,'Jeff Hill','jeff@bayworx.com','9253212731','','','[\"ROLE_ATTENDEE\"]',NULL,1,NULL,'2025-11-27 01:26:35',0,NULL,'2025-11-27 01:26:17','',NULL),
(7,12,'user3 test','ebjoker4@yahoo.com','','','','[\"ROLE_ATTENDEE\"]',NULL,1,NULL,'2025-11-27 05:34:55',0,NULL,'2025-11-27 05:34:36','',NULL),
(8,10,'derp','tyrranicide@gmail.com','','','','[\"ROLE_ATTENDEE\"]',NULL,1,NULL,'2025-11-27 05:55:09',0,NULL,'2025-11-27 05:54:54','',NULL),
(9,9,'Jeff Hill','jeff@bayworx.com','9258750504','BAYWORX LLC','','[\"ROLE_ATTENDEE\"]',NULL,1,NULL,'2025-12-15 02:59:03',0,NULL,'2025-12-15 02:58:39','',NULL);
/*!40000 ALTER TABLE `attendees` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctrine_migration_versions`
--

LOCK TABLES `doctrine_migration_versions` WRITE;
/*!40000 ALTER TABLE `doctrine_migration_versions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `doctrine_migration_versions` VALUES
('DoctrineMigrations\\Version20251006003512',NULL,NULL),
('DoctrineMigrations\\Version20251006060957','2025-10-06 06:10:10',285),
('DoctrineMigrations\\Version20251006063739','2025-10-06 06:37:56',91),
('DoctrineMigrations\\Version20251006170641','2025-10-06 17:12:03',242),
('DoctrineMigrations\\Version20251006195545','2025-10-06 19:56:00',524),
('DoctrineMigrations\\Version20251006222756','2025-10-06 22:29:04',140),
('DoctrineMigrations\\Version20251010203006','2025-10-10 20:31:43',260),
('DoctrineMigrations\\Version20251013013717','2025-10-13 01:37:28',66),
('DoctrineMigrations\\Version20251024165349','2025-10-24 16:55:06',255),
('DoctrineMigrations\\Version20251107033945','2025-11-07 03:39:53',68),
('DoctrineMigrations\\Version20251107044535','2025-11-07 04:46:03',104),
('DoctrineMigrations\\Version20251126205540','2025-11-26 20:57:36',149),
('DoctrineMigrations\\Version20251215032056','2025-12-15 03:21:14',66);
/*!40000 ALTER TABLE `doctrine_migration_versions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `max_attendees` int(11) DEFAULT NULL,
  `banner_image` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `parent_event_id` int(11) DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `recurrence_pattern` varchar(50) DEFAULT NULL,
  `recurrence_interval` int(11) DEFAULT NULL,
  `recurrence_end_date` datetime DEFAULT NULL,
  `recurrence_count` int(11) DEFAULT NULL,
  `wifi_ssid` varchar(255) DEFAULT NULL,
  `wifi_password` varchar(255) DEFAULT NULL,
  `wifi_security_type` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_3BAE0AA7989D9B62` (`slug`),
  KEY `IDX_3BAE0AA7EE3A445A` (`parent_event_id`),
  CONSTRAINT `FK_3BAE0AA7EE3A445A` FOREIGN KEY (`parent_event_id`) REFERENCES `event` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event`
--

LOCK TABLES `event` WRITE;
/*!40000 ALTER TABLE `event` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `event` VALUES
(1,'Symfony Development Workshop','Learn modern PHP web development with Symfony framework. This hands-on workshop covers controllers, templates, database integration, and security best practices.','2025-11-25 08:00:00','2025-11-25 17:00:00','Tech Conference Center, San Francisco','symfony-development-workshop',1,50,'68e708b86e0f5651154506.jpg','2025-10-08 04:09:36','2025-10-23 18:14:48',NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(2,'Digital Marketing Summit 2024','Join industry leaders as they share the latest trends in digital marketing, social media strategies, and content creation.','2025-11-26 04:09:00','2025-11-28 12:09:00','Grand Hotel, New York','digital-marketing-summit-2024',1,200,'68e7094ab2f5a272264469.jpg','2025-10-08 04:09:36','2025-10-23 18:11:26',NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(3,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2025-11-08 04:09:00','2025-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference',1,150,'69138eb60a66c392707960.jpg','2025-10-08 04:09:36','2025-11-11 19:29:58',NULL,1,'yearly',1,'2042-12-31 00:00:00',NULL,'HotelWiFi','anotherone','WPA'),
(4,'DevOps Best Practices Meetup','A local meetup discussing DevOps practices, containerization, CI/CD pipelines, and infrastructure as code.','2025-11-29 09:00:00','2025-11-29 16:00:00','San Francisco Convention Center','devops-best-practices-meetup',1,30,'68e65432b5188524016249.jpg','2025-10-08 04:09:36','2025-10-23 18:12:18',NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(5,'UX/UI Design Workshop','Learn user experience and interface design principles. Practical exercises in wireframing, prototyping, and user testing.','2025-12-16 04:09:00','2025-12-18 09:09:00','Design Studio, Los Angeles','ux-ui-design-workshop',1,25,'68e708db965e0151298945.jpg','2025-10-08 04:09:36','2025-10-23 18:13:30',NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(9,'Annual Conference','Our annual technology conference','2025-12-15 09:00:00','2025-12-15 17:00:00','Convention Center','annual-conference',1,NULL,'68e959d4591c0728047649.jpg','2025-10-10 19:05:17','2025-12-15 03:41:34',NULL,0,NULL,1,NULL,NULL,'HotelWifi','wifi12345678','WPA'),
(10,'Monthly Technology Roundup','Monthly gathering of technology professionals','2025-11-30 09:00:00','2025-11-30 17:00:00','San Jose Tech Hub','monthly-technology-roundup',1,NULL,'68e9a10d2d8f9031697626.jpg','2025-10-11 00:05:47','2025-11-27 05:55:52',NULL,0,NULL,1,NULL,NULL,NULL,NULL,'WPA'),
(11,'HR Seminar','All day seminar on HR best practices','2026-06-11 09:00:00','2026-06-11 17:00:00','Dublin Chamber of Commerce','hr-seminar',1,NULL,'68e9a0b6ed0e0025063225.jpg','2025-10-11 00:05:47','2025-11-07 04:06:44',NULL,0,NULL,1,NULL,NULL,'HotelWiFi','passwordforwifi','WPA'),
(12,'Data Restoration Workshop','Can data be restored from SSDs','2026-01-15 09:00:00','2024-01-15 17:00:00','San Jose Arena','data-restoration-workshop',1,NULL,'68e9a0709caf8124785861.jpg','2025-10-11 00:05:47','2025-10-11 00:10:24',NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(13,'Monthly Technology Meetup (November 2025)','Knowledge-sharing session for IT professionals','2025-11-23 02:38:00','2025-11-23 10:38:00','San Jose Tech Hub','monthly-technology-meetup-november-2025',1,NULL,'68fbb7c27882f171593672.jpg','2025-10-23 02:38:29','2025-11-12 18:41:39',NULL,0,NULL,1,NULL,NULL,'LocalWifi','12345678','WPA'),
(65,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2026-11-08 04:09:00','2026-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2026-11-08-1',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA'),
(66,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2027-11-08 04:09:00','2027-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2027-11-08-2',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA'),
(67,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2028-11-08 04:09:00','2028-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2028-11-08-3',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA'),
(68,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2029-11-08 04:09:00','2029-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2029-11-08-4',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA'),
(69,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2030-11-08 04:09:00','2030-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-1',1,150,'6914cd22a820b501962121.jpg','2025-11-11 19:29:58','2025-11-12 18:08:34',3,0,NULL,1,NULL,NULL,NULL,NULL,'WPA'),
(70,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2031-11-08 04:09:00','2031-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2031-11-08-6',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA'),
(71,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2032-11-08 04:09:00','2032-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2032-11-08-7',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA'),
(72,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2033-11-08 04:09:00','2033-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2033-11-08-8',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA'),
(73,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2034-11-08 04:09:00','2034-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2034-11-08-9',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA'),
(74,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2035-11-08 04:09:00','2035-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2035-11-08-10',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA'),
(75,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2036-11-08 04:09:00','2036-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2036-11-08-11',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA'),
(76,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2037-11-08 04:09:00','2037-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2037-11-08-12',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA'),
(77,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2038-11-08 04:09:00','2038-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2038-11-08-13',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA'),
(78,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2039-11-08 04:09:00','2039-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2039-11-08-14',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA'),
(79,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2040-11-08 04:09:00','2040-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2040-11-08-15',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA'),
(80,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2041-11-08 04:09:00','2041-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2041-11-08-16',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA'),
(81,'AI and Machine Learning Conference','Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.','2042-11-08 04:09:00','2042-11-10 04:09:00','Innovation Center, Austin','ai-and-machine-learning-conference-2042-11-08-17',1,150,'69138eb60a66c392707960.jpg','2025-11-11 19:29:58',NULL,3,0,NULL,NULL,NULL,NULL,NULL,NULL,'WPA');
/*!40000 ALTER TABLE `event` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `event_administrators`
--

DROP TABLE IF EXISTS `event_administrators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_administrators` (
  `event_id` int(11) NOT NULL,
  `administrator_id` int(11) NOT NULL,
  PRIMARY KEY (`event_id`,`administrator_id`),
  KEY `IDX_D87EC71C71F7E88B` (`event_id`),
  KEY `IDX_D87EC71C4B09E92C` (`administrator_id`),
  CONSTRAINT `FK_D87EC71C4B09E92C` FOREIGN KEY (`administrator_id`) REFERENCES `administrators` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_D87EC71C71F7E88B` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_administrators`
--

LOCK TABLES `event_administrators` WRITE;
/*!40000 ALTER TABLE `event_administrators` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `event_administrators` VALUES
(1,1),
(2,1),
(3,1),
(3,2),
(4,1),
(5,1),
(9,1),
(9,2),
(10,1),
(10,2),
(11,1),
(11,2),
(12,1),
(12,2),
(13,1),
(13,2),
(65,1),
(65,2),
(66,1),
(66,2),
(67,1),
(67,2),
(68,1),
(68,2),
(69,1),
(69,2),
(70,1),
(70,2),
(71,1),
(71,2),
(72,1),
(72,2),
(73,1),
(73,2),
(74,1),
(74,2),
(75,1),
(75,2),
(76,1),
(76,2),
(77,1),
(77,2),
(78,1),
(78,2),
(79,1),
(79,2),
(80,1),
(80,2),
(81,1),
(81,2);
/*!40000 ALTER TABLE `event_administrators` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `event_files`
--

DROP TABLE IF EXISTS `event_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `uploaded_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `IDX_472EF17571F7E88B` (`event_id`),
  CONSTRAINT `FK_472EF17571F7E88B` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_files`
--

LOCK TABLES `event_files` WRITE;
/*!40000 ALTER TABLE `event_files` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `event_files` VALUES
(1,9,'BAYWORX Fact Sheet','Fact sheet for BAYWORX MSP Services','68ec618538165539911316.pdf','BAYWORX-FACT-SHEET.pdf','application/pdf',900450,0,1,'2025-10-13 02:18:45','2025-10-13 02:18:45',0),
(2,13,'A Sample PDF File','Just a sample','690d790c2f997581571462.pdf','sample-local-pdf.pdf','application/pdf',49672,0,1,'2025-11-07 04:43:56','2025-11-07 04:43:56',0),
(3,13,'Just a simple spreadsheet','sample spreadsheet','690d79b0ddad8869622391.xlsx','testsheet.xlsx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',5662,0,1,'2025-11-07 04:46:40','2025-11-07 04:46:40',0),
(4,10,'sample','sample','6927e7e84f508574971540.pdf','sample-local-pdf.pdf','application/pdf',49672,0,1,'2025-11-27 05:55:52','2025-11-27 05:55:52',0);
/*!40000 ALTER TABLE `event_files` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `event_imports`
--

DROP TABLE IF EXISTS `event_imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_imports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `import_type` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `processed_at` datetime DEFAULT NULL,
  `results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`results`)),
  `errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`errors`)),
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `successful_rows` int(11) NOT NULL DEFAULT 0,
  `failed_rows` int(11) NOT NULL DEFAULT 0,
  `imported_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`imported_data`)),
  PRIMARY KEY (`id`),
  KEY `IDX_FDC244B3B03A8386` (`created_by_id`),
  CONSTRAINT `FK_FDC244B3B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `administrators` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_imports`
--

LOCK TABLES `event_imports` WRITE;
/*!40000 ALTER TABLE `event_imports` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `event_imports` VALUES
(2,2,'complete_template.csv','completed','complete','2025-10-10 18:56:26','2025-10-10 19:05:17','{\"events\":[\"Row 2: Created event \'Annual Conference\'\"],\"agenda\":[\"Row 2: Created agenda item for \'Annual Conference\'\"],\"presenters\":[\"Row 2: Created presenter for \'Annual Conference\'\"],\"attendees\":[]}','[]',1,1,0,'{\"headers\":[\"Event\",\"Description\",\"Start Date\",\"End Date\",\"Location\",\"Attendee Name\",\"Attendee Email\",\"Agenda Item\",\"Presenter\"],\"records\":[{\"Event\":\"Annual Conference\",\"Description\":\"Our annual technology conference\",\"Start Date\":\"2024-06-15 09:00:00\",\"End Date\":\"2024-06-15 17:00:00\",\"Location\":\"Convention Center\",\"Attendee Name\":\"John Doe\",\"Attendee Email\":\"john@example.com\",\"Agenda Item\":\"Opening Keynote\",\"Presenter\":\"Jane Smith\"}],\"total_rows\":1,\"import_type\":\"complete\",\"expected_columns\":{\"Event\":[\"title\",\"event_title\",\"Event Title\",\"Event\"],\"Description\":[\"description\",\"Description\"],\"Start Date\":[\"start_date\",\"Start Date\"],\"End Date\":[\"end_date\",\"End Date\"],\"Location\":[\"location\",\"Location\"],\"Attendee Name\":[\"attendee_name\",\"name\",\"Name\"],\"Attendee Email\":[\"attendee_email\",\"email\",\"Email\"],\"Agenda Item\":[\"agenda_title\",\"agenda_item\",\"Agenda Item\"],\"Presenter\":[\"presenter_name\",\"presenter\",\"Presenter\"]},\"mapping_suggestions\":{\"Event\":\"Event\",\"Description\":\"Description\",\"Start Date\":\"Start Date\",\"End Date\":\"End Date\",\"Location\":\"Location\",\"Attendee Name\":\"Attendee Name\",\"Attendee Email\":\"Attendee Email\",\"Agenda Item\":\"Agenda Item\",\"Presenter\":\"Presenter\"}}'),
(3,2,'complete_template (1).csv','completed','complete','2025-10-10 19:06:55','2025-10-10 19:07:08','{\"events\":[\"Row 2: Created event \'Annual Conference\'\"],\"agenda\":[\"Row 2: Created agenda item for \'Annual Conference\'\"],\"presenters\":[\"Row 2: Created presenter for \'Annual Conference\'\"],\"attendees\":[]}','[]',1,1,0,'{\"headers\":[\"Event\",\"Description\",\"Start Date\",\"End Date\",\"Location\",\"Attendee Name\",\"Attendee Email\",\"Agenda Item\",\"Presenter\"],\"records\":[{\"Event\":\"Annual Conference\",\"Description\":\"Our annual technology conference\",\"Start Date\":\"2024-06-15 09:00:00\",\"End Date\":\"2024-06-15 17:00:00\",\"Location\":\"Convention Center\",\"Attendee Name\":\"John Doe\",\"Attendee Email\":\"john@example.com\",\"Agenda Item\":\"Opening Keynote\",\"Presenter\":\"Jane Smith\"}],\"total_rows\":1,\"import_type\":\"complete\",\"expected_columns\":{\"Event\":[\"title\",\"event_title\",\"Event Title\",\"Event\"],\"Description\":[\"description\",\"Description\"],\"Start Date\":[\"start_date\",\"Start Date\"],\"End Date\":[\"end_date\",\"End Date\"],\"Location\":[\"location\",\"Location\"],\"Attendee Name\":[\"attendee_name\",\"name\",\"Name\"],\"Attendee Email\":[\"attendee_email\",\"email\",\"Email\"],\"Agenda Item\":[\"agenda_title\",\"agenda_item\",\"Agenda Item\"],\"Agenda Start Time\":[\"agenda_start\",\"Start Time\"],\"Item Type\":[\"item_type\",\"Item Type\",\"Type\"],\"Presenter\":[\"presenter_name\",\"presenter\",\"Presenter\"]},\"mapping_suggestions\":{\"Event\":\"Event\",\"Description\":\"Description\",\"Start Date\":\"Start Date\",\"End Date\":\"End Date\",\"Location\":\"Location\",\"Attendee Name\":\"Attendee Name\",\"Attendee Email\":\"Attendee Email\",\"Agenda Item\":\"Agenda Item\",\"Agenda Start Time\":\"Start Date\",\"Presenter\":\"Presenter\"}}'),
(4,2,'complete_template.csv','completed','complete','2025-10-11 00:05:37','2025-10-11 00:05:46','{\"events\":[\"Row 2: Created event \'Annual Conference\'\",\"Row 3: Created event \'Monthly Technology Roundup\'\",\"Row 4: Created event \'HR Seminar\'\",\"Row 5: Created event \'Monthly Technology Roundup\'\",\"Row 6: Created event \'Data Restoration Workshop\'\"],\"agenda\":[\"Row 2: Created agenda item for \'Annual Conference\'\",\"Row 3: Created agenda item for \'Monthly Technology Roundup\'\",\"Row 4: Created agenda item for \'HR Seminar\'\",\"Row 5: Created agenda item for \'Monthly Technology Roundup\'\",\"Row 6: Created agenda item for \'Data Restoration Workshop\'\"],\"presenters\":[\"Row 2: Created presenter for \'Annual Conference\'\",\"Row 3: Created presenter for \'Monthly Technology Roundup\'\",\"Row 4: Created presenter for \'HR Seminar\'\",\"Row 5: Created presenter for \'Monthly Technology Roundup\'\",\"Row 6: Created presenter for \'Data Restoration Workshop\'\"],\"attendees\":[]}','[]',5,5,0,'{\"headers\":[\"Event\",\"Description\",\"Start Date\",\"End Date\",\"Location\",\"Attendee Name\",\"Attendee Email\",\"Agenda Item\",\"Presenter\"],\"records\":[{\"Event\":\"Annual Conference\",\"Description\":\"Our annual technology conference\",\"Start Date\":\"2024-06-15 09:00:00\",\"End Date\":\"2024-06-15 17:00:00\",\"Location\":\"Convention Center\",\"Attendee Name\":\"John Doe\",\"Attendee Email\":\"john@example.com\",\"Agenda Item\":\"Opening Keynote\",\"Presenter\":\"Jane Smith\"},{\"Event\":\"Monthly Technology Roundup\",\"Description\":\"Monthly gathering of technology professionals\",\"Start Date\":\"2025-11-25 09:00:00\",\"End Date\":\"2025-11-25 17:00:00\",\"Location\":\"San Jose Tech Hub\",\"Attendee Name\":\"Darryl Sheets\",\"Attendee Email\":\"darryl@sheets.com\",\"Agenda Item\":\"Welcome\",\"Presenter\":\"Steve Simpson\"},{\"Event\":\"HR Seminar\",\"Description\":\"All day seminar on HR best practices\",\"Start Date\":\"2026-06-11 09:00:00\",\"End Date\":\"2026-06-11 17:00:00\",\"Location\":\"Dublin Chamber of Commerce\",\"Attendee Name\":\"Larry Housel\",\"Attendee Email\":\"larry@housel.org\",\"Agenda Item\":\"HR Best Practices in the modern era\",\"Presenter\":\"Gail Jameson\"},{\"Event\":\"Monthly Technology Roundup\",\"Description\":\"Monthly gathering of technology professionals\",\"Start Date\":\"2025-12-23 09:00:00\",\"End Date\":\"2025-12-23 17:00:00\",\"Location\":\"San Jose Tech Hub\",\"Attendee Name\":\"Darryl Sheets\",\"Attendee Email\":\"darryl@sheets.com\",\"Agenda Item\":\"Welcome\",\"Presenter\":\"Steve Simpson\"},{\"Event\":\"Data Restoration Workshop\",\"Description\":\"Can data be restored from SSDs\",\"Start Date\":\"2026-01-15 09:00:00\",\"End Date\":\"2024-01-15 17:00:00\",\"Location\":\"San Jose Arena\",\"Attendee Name\":\"Mike Lawlor\",\"Attendee Email\":\"mike@mikelawlor.com\",\"Agenda Item\":\"Teardown and rebuild\",\"Presenter\":\"Scott Dobson\"}],\"total_rows\":5,\"import_type\":\"complete\",\"expected_columns\":{\"Event\":[\"title\",\"event_title\",\"Event Title\",\"Event\"],\"Description\":[\"description\",\"Description\"],\"Start Date\":[\"start_date\",\"Start Date\"],\"End Date\":[\"end_date\",\"End Date\"],\"Location\":[\"location\",\"Location\"],\"Attendee Name\":[\"attendee_name\",\"name\",\"Name\"],\"Attendee Email\":[\"attendee_email\",\"email\",\"Email\"],\"Agenda Item\":[\"agenda_title\",\"agenda_item\",\"Agenda Item\"],\"Agenda Start Time\":[\"agenda_start\",\"Start Time\"],\"Item Type\":[\"item_type\",\"Item Type\",\"Type\"],\"Presenter\":[\"presenter_name\",\"presenter\",\"Presenter\"]},\"mapping_suggestions\":{\"Event\":\"Event\",\"Description\":\"Description\",\"Start Date\":\"Start Date\",\"End Date\":\"End Date\",\"Location\":\"Location\",\"Attendee Name\":\"Attendee Name\",\"Attendee Email\":\"Attendee Email\",\"Agenda Item\":\"Agenda Item\",\"Agenda Start Time\":\"Start Date\",\"Presenter\":\"Presenter\"}}');
/*!40000 ALTER TABLE `event_imports` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `event_presenter`
--

DROP TABLE IF EXISTS `event_presenter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_presenter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `presenter_id` int(11) NOT NULL,
  `presentation_title` varchar(255) DEFAULT NULL,
  `presentation_description` longtext DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_552054F571F7E88B` (`event_id`),
  KEY `IDX_552054F5DDE4C635` (`presenter_id`),
  CONSTRAINT `FK_552054F571F7E88B` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`),
  CONSTRAINT `FK_552054F5DDE4C635` FOREIGN KEY (`presenter_id`) REFERENCES `presenter` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_presenter`
--

LOCK TABLES `event_presenter` WRITE;
/*!40000 ALTER TABLE `event_presenter` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `event_presenter` VALUES
(1,3,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(2,3,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(5,9,5,NULL,NULL,NULL,NULL,1,1,'2025-10-11 00:05:47'),
(6,10,6,NULL,NULL,NULL,NULL,1,1,'2025-10-11 00:05:47'),
(7,11,7,NULL,NULL,NULL,NULL,1,1,'2025-10-11 00:05:47'),
(9,12,8,NULL,NULL,NULL,NULL,1,1,'2025-10-11 00:05:47'),
(114,13,8,'Open Source - The future of Desktop Computing?','Take a deep dive into the latest in open-source desktop tools',NULL,NULL,1,1,'2025-11-07 05:01:06'),
(115,65,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(116,65,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(117,66,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(118,66,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(119,67,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(120,67,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(121,68,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(122,68,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(123,69,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(124,69,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(125,70,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(126,70,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(127,71,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(128,71,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(129,72,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(130,72,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(131,73,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(132,73,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(133,74,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(134,74,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(135,75,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(136,75,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(137,76,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(138,76,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(139,77,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(140,77,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(141,78,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(142,78,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(143,79,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(144,79,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(145,80,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(146,80,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(147,81,3,'Securing your network on a budget','We will cover basic firewall rules, best practices for wifi and wired networks and EDR tools','10:00:00',NULL,1,1,'2025-10-10 01:56:27'),
(148,81,1,'Using Large Language Models to your advantage','We will cover how to use LLMs to help tighten your network security','11:00:00',NULL,2,1,'2025-10-10 01:56:27'),
(149,13,1,NULL,NULL,NULL,NULL,2,1,'2025-11-12 18:41:39'),
(150,9,7,NULL,NULL,'11:00:00','11:30:00',2,1,'2025-12-15 03:41:34');
/*!40000 ALTER TABLE `event_presenter` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `featured_events`
--

DROP TABLE IF EXISTS `featured_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `featured_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `related_event_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `link_text` varchar(100) DEFAULT NULL,
  `priority` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `display_type` varchar(50) NOT NULL DEFAULT 'banner',
  `display_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`display_settings`)),
  `view_count` int(11) NOT NULL,
  `click_count` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `banner_image_name` varchar(255) DEFAULT NULL,
  `banner_image_size` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_430464EBD774A626` (`related_event_id`),
  KEY `IDX_430464EBB03A8386` (`created_by_id`),
  CONSTRAINT `FK_430464EBB03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `administrators` (`id`),
  CONSTRAINT `FK_430464EBD774A626` FOREIGN KEY (`related_event_id`) REFERENCES `event` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `featured_events`
--

LOCK TABLES `featured_events` WRITE;
/*!40000 ALTER TABLE `featured_events` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `featured_events` VALUES
(1,9,1,'Join Us For Our 15th Annual Conference','Pioneering advances in Information Technology','http://127.0.0.1:8000/uploads/events/68e65432b5188524016249.jpg',NULL,'Register Now',10,1,NULL,NULL,'banner',NULL,84,8,'2025-10-10 13:41:48','2025-10-14 22:05:45','68ec5a01c7220366481589.jpg',2868662),
(2,3,2,'Chat GPT 101 - Getting the most out of your prompts','Learn the fine art of optimized prompts',NULL,NULL,NULL,50,1,'2025-10-13 12:00:00',NULL,'banner','{\"autoRotate\":true,\"rotationInterval\":5000,\"showControls\":true,\"showIndicators\":true,\"fadeEffect\":true}',62,2,'2025-10-15 03:54:55','2025-11-08 17:30:33','68ef1b0fa305d483726537.jpg',3422560);
/*!40000 ALTER TABLE `featured_events` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `reply_to_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `sent_at` datetime NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `priority` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DB021E96F624B39D` (`sender_id`),
  KEY `IDX_DB021E96E92F8F78` (`recipient_id`),
  KEY `IDX_DB021E9671F7E88B` (`event_id`),
  KEY `IDX_DB021E96FFDF7169` (`reply_to_id`),
  CONSTRAINT `FK_DB021E9671F7E88B` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`),
  CONSTRAINT `FK_DB021E96E92F8F78` FOREIGN KEY (`recipient_id`) REFERENCES `administrators` (`id`),
  CONSTRAINT `FK_DB021E96F624B39D` FOREIGN KEY (`sender_id`) REFERENCES `attendees` (`id`),
  CONSTRAINT `FK_DB021E96FFDF7169` FOREIGN KEY (`reply_to_id`) REFERENCES `messages` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `messages` VALUES
(1,1,2,1,NULL,'Question about workshop materials','Hi, I was wondering if the workshop materials will be made available before the event? I\'d like to review them in advance. Also, will there be hands-on coding exercises? Thanks!',0,'2025-11-04 02:33:51',NULL,'sent','normal'),
(2,1,2,1,NULL,'Dietary restrictions','Hello, I have a severe nut allergy. Will lunch be provided during the workshop? If so, could you please ensure there are nut-free options available? Thank you for your help!',1,'2025-11-06 02:33:51','2025-11-07 02:33:51','sent','high'),
(3,1,2,1,NULL,'Need parking pass information','Hello, I will be driving to the event and need information about parking. Are there designated parking areas for attendees? Also, will parking passes be provided or do I need to arrange my own parking?',1,'2025-11-04 18:42:45','2025-11-12 18:44:45','sent','normal'),
(4,1,2,1,NULL,'Question about session recordings','Will the workshop sessions be recorded? I may need to leave early and would like to review any content I miss. If recordings will be available, how can I access them after the event?',1,'2025-11-02 18:42:45',NULL,'sent','low'),
(5,1,2,1,NULL,'Accessibility accommodations','I use a wheelchair and want to ensure the venue is fully accessible. Can you confirm that there are accessible entrances, restrooms, and seating areas? Please let me know if I need to make any special arrangements.',1,'2025-11-05 18:42:45','2025-11-07 04:33:05','replied','high'),
(6,1,2,1,5,'Re: Accessibility accommodations','Park on the south side of the building',0,'2025-11-12 18:45:07',NULL,'sent','normal'),
(7,9,2,9,NULL,'Wifi','What is the wifi password again?',0,'2025-12-15 03:08:06',NULL,'sent','normal'),
(8,9,2,9,NULL,'Testing again','Test...',1,'2025-12-15 03:28:37','2025-12-15 03:30:52','replied','normal'),
(9,9,2,9,8,'Re: Testing again','works on my end!',0,'2025-12-15 03:30:52',NULL,'sent','normal');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `messenger_messages`
--

DROP TABLE IF EXISTS `messenger_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messenger_messages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `available_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `delivered_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  KEY `IDX_75EA56E016BA31DB` (`delivered_at`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messenger_messages`
--

LOCK TABLES `messenger_messages` WRITE;
/*!40000 ALTER TABLE `messenger_messages` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `messenger_messages` VALUES
(1,'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;N;i:1;N;i:2;s:4469:\\\"<!DOCTYPE html>\n<html>\n<head>\n    <meta charset=\\\"UTF-8\\\">\n    <meta name=\\\"viewport\\\" content=\\\"width=device-width, initial-scale=1.0\\\">\n    <title>Verify Your Registration - Symfony Development Workshop</title>\n    <style>\n        body {\n            font-family: Arial, sans-serif;\n            line-height: 1.6;\n            color: #333;\n            max-width: 600px;\n            margin: 0 auto;\n            padding: 20px;\n        }\n        .header {\n            background-color: #28a745;\n            color: white;\n            padding: 20px;\n            text-align: center;\n            border-radius: 5px 5px 0 0;\n        }\n        .content {\n            background-color: #f8f9fa;\n            padding: 30px;\n            border-radius: 0 0 5px 5px;\n        }\n        .button {\n            display: inline-block;\n            background-color: #28a745;\n            color: white;\n            padding: 12px 30px;\n            text-decoration: none;\n            border-radius: 5px;\n            margin: 20px 0;\n            font-weight: bold;\n        }\n        .button:hover {\n            background-color: #1e7e34;\n        }\n        .event-details {\n            background-color: white;\n            padding: 20px;\n            border-left: 4px solid #28a745;\n            margin: 20px 0;\n        }\n        .footer {\n            text-align: center;\n            margin-top: 30px;\n            font-size: 12px;\n            color: #666;\n        }\n        .verification-note {\n            background-color: #fff3cd;\n            border: 1px solid #ffeaa7;\n            color: #856404;\n            padding: 15px;\n            border-radius: 5px;\n            margin: 20px 0;\n        }\n    </style>\n</head>\n<body>\n    <div class=\\\"header\\\">\n        <h1>✅ Verify Your Registration</h1>\n        <p>Symfony Development Workshop</p>\n    </div>\n    \n    <div class=\\\"content\\\">\n        <h2>Hello Jeff Hill!</h2>\n        \n        <p>Thank you for registering for <strong>Symfony Development Workshop</strong>! To complete your registration and gain access to event materials, please verify your email address by clicking the button below:</p>\n        \n        <div style=\\\"text-align: center;\\\">\n            <a href=\\\"/event/symfony-development-workshop/verify/e576d8c9f4e75afe5bee02bee7fe12f231ea6394adba7e6de635370be764e3d7\\\" class=\\\"button\\\">🔐 Verify Email & Complete Registration</a>\n        </div>\n        \n        <div class=\\\"event-details\\\">\n            <h3>Event Details:</h3>\n            <ul>\n                <li><strong>Title:</strong> Symfony Development Workshop</li>\n                <li><strong>Date:</strong> October 15, 2025 \\\\am\\\\31 4:09 AM</li>\n                                    <li><strong>End Date:</strong> October 15, 2025 \\\\am\\\\31 10:09 AM</li>\n                                                    <li><strong>Location:</strong> Tech Conference Center, San Francisco</li>\n                            </ul>\n            \n                            <p><strong>About this event:</strong></p>\n                <p>Learn modern PHP web development with Symfony framework. This hands-on workshop covers controllers, templates, database integration, and security best practices.</p>\n                    </div>\n        \n        <div class=\\\"verification-note\\\">\n            <h4>📋 What happens after verification?</h4>\n            <ul>\n                <li>✅ Your registration will be confirmed</li>\n                <li>📁 You\\\'ll get access to event materials and downloads</li>\n                <li>🔑 You can use the \\\"Already Registered?\\\" login on the event page</li>\n                <li>📧 You\\\'ll receive updates about the event</li>\n            </ul>\n        </div>\n        \n        <p><strong>Important:</strong> This verification link is unique to your email address and will expire after 24 hours. If you didn\\\'t register for this event, you can safely ignore this email.</p>\n        \n        <p>If the button above doesn\\\'t work, you can copy and paste this link into your browser:</p>\n        <p style=\\\"word-break: break-all; font-family: monospace; background-color: #e9ecef; padding: 10px;\\\">/event/symfony-development-workshop/verify/e576d8c9f4e75afe5bee02bee7fe12f231ea6394adba7e6de635370be764e3d7</p>\n    </div>\n    \n    <div class=\\\"footer\\\">\n        <p>This email was sent automatically by the Event Management System.<br>\n        Please do not reply to this email.</p>\n        \n        <p>Having trouble? Contact the event organizer for assistance.</p>\n    </div>\n</body>\n</html>\\\";i:3;s:5:\\\"utf-8\\\";i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:19:\\\"noreply@example.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:16:\\\"jeff@bayworx.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:57:\\\"Verify your registration for Symfony Development Workshop\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}','[]','default','2025-10-08 20:08:08','2025-10-08 20:08:08',NULL),
(2,'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;N;i:1;N;i:2;s:5064:\\\"<!DOCTYPE html>\n<html>\n<head>\n    <meta charset=\\\"UTF-8\\\">\n    <meta name=\\\"viewport\\\" content=\\\"width=device-width, initial-scale=1.0\\\">\n    <title>Verify Your Registration - Symfony Development Workshop</title>\n    <style>\n        body {\n            font-family: Arial, sans-serif;\n            line-height: 1.6;\n            color: #333;\n            max-width: 600px;\n            margin: 0 auto;\n            padding: 20px;\n        }\n        .header {\n            background-color: #28a745;\n            color: white;\n            padding: 20px;\n            text-align: center;\n            border-radius: 5px 5px 0 0;\n        }\n        .content {\n            background-color: #f8f9fa;\n            padding: 30px;\n            border-radius: 0 0 5px 5px;\n        }\n        .button {\n            display: inline-block;\n            background-color: #28a745;\n            color: white;\n            padding: 12px 30px;\n            text-decoration: none;\n            border-radius: 5px;\n            margin: 20px 0;\n            font-weight: bold;\n        }\n        .button:hover {\n            background-color: #1e7e34;\n        }\n        .event-details {\n            background-color: white;\n            padding: 20px;\n            border-left: 4px solid #28a745;\n            margin: 20px 0;\n        }\n        .footer {\n            text-align: center;\n            margin-top: 30px;\n            font-size: 12px;\n            color: #666;\n        }\n        .verification-note {\n            background-color: #fff3cd;\n            border: 1px solid #ffeaa7;\n            color: #856404;\n            padding: 15px;\n            border-radius: 5px;\n            margin: 20px 0;\n        }\n        .wifi-section {\n            background-color: #e7f3ff;\n            border: 2px solid #2196F3;\n            padding: 20px;\n            border-radius: 8px;\n            margin: 20px 0;\n            text-align: center;\n        }\n        .wifi-qr-code {\n            max-width: 300px;\n            height: auto;\n            margin: 15px auto;\n            display: block;\n        }\n        .wifi-credentials {\n            background-color: white;\n            padding: 15px;\n            border-radius: 5px;\n            margin-top: 15px;\n            text-align: left;\n        }\n    </style>\n</head>\n<body>\n    <div class=\\\"header\\\">\n        <h1>✅ Verify Your Registration</h1>\n        <p>Symfony Development Workshop</p>\n    </div>\n    \n    <div class=\\\"content\\\">\n        <h2>Hello Info Account!</h2>\n        \n        <p>Thank you for registering for <strong>Symfony Development Workshop</strong>! To complete your registration and gain access to event materials, please verify your email address by clicking the button below:</p>\n        \n        <div style=\\\"text-align: center;\\\">\n            <a href=\\\"/event/symfony-development-workshop/verify/f35614262578308942543a5d6f02618f8ea818f6e354ea118b24a66ecf01c7e1\\\" class=\\\"button\\\">🔐 Verify Email & Complete Registration</a>\n        </div>\n        \n        <div class=\\\"event-details\\\">\n            <h3>Event Details:</h3>\n            <ul>\n                <li><strong>Title:</strong> Symfony Development Workshop</li>\n                <li><strong>Date:</strong> November 25, 2025 \\\\am\\\\30 8:00 AM</li>\n                                    <li><strong>End Date:</strong> November 25, 2025 \\\\pm\\\\30 5:00 PM</li>\n                                                    <li><strong>Location:</strong> Tech Conference Center, San Francisco</li>\n                            </ul>\n            \n                            <p><strong>About this event:</strong></p>\n                <p>Learn modern PHP web development with Symfony framework. This hands-on workshop covers controllers, templates, database integration, and security best practices.</p>\n                    </div>\n        \n                \n        <div class=\\\"verification-note\\\">\n            <h4>📋 What happens after verification?</h4>\n            <ul>\n                <li>✅ Your registration will be confirmed</li>\n                <li>📁 You\\\'ll get access to event materials and downloads</li>\n                <li>🔑 You can use the \\\"Already Registered?\\\" login on the event page</li>\n                <li>📧 You\\\'ll receive updates about the event</li>\n            </ul>\n        </div>\n        \n        <p><strong>Important:</strong> This verification link is unique to your email address and will expire after 24 hours. If you didn\\\'t register for this event, you can safely ignore this email.</p>\n        \n        <p>If the button above doesn\\\'t work, you can copy and paste this link into your browser:</p>\n        <p style=\\\"word-break: break-all; font-family: monospace; background-color: #e9ecef; padding: 10px;\\\">/event/symfony-development-workshop/verify/f35614262578308942543a5d6f02618f8ea818f6e354ea118b24a66ecf01c7e1</p>\n    </div>\n    \n    <div class=\\\"footer\\\">\n        <p>This email was sent automatically by the Event Management System.<br>\n        Please do not reply to this email.</p>\n        \n        <p>Having trouble? Contact the event organizer for assistance.</p>\n    </div>\n</body>\n</html>\\\";i:3;s:5:\\\"utf-8\\\";i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:19:\\\"noreply@example.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:16:\\\"info@bayworx.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:57:\\\"Verify your registration for Symfony Development Workshop\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}','[]','default','2025-11-12 17:58:33','2025-11-12 17:58:33',NULL),
(3,'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;N;i:1;N;i:2;s:5067:\\\"<!DOCTYPE html>\n<html>\n<head>\n    <meta charset=\\\"UTF-8\\\">\n    <meta name=\\\"viewport\\\" content=\\\"width=device-width, initial-scale=1.0\\\">\n    <title>Verify Your Registration - Symfony Development Workshop</title>\n    <style>\n        body {\n            font-family: Arial, sans-serif;\n            line-height: 1.6;\n            color: #333;\n            max-width: 600px;\n            margin: 0 auto;\n            padding: 20px;\n        }\n        .header {\n            background-color: #28a745;\n            color: white;\n            padding: 20px;\n            text-align: center;\n            border-radius: 5px 5px 0 0;\n        }\n        .content {\n            background-color: #f8f9fa;\n            padding: 30px;\n            border-radius: 0 0 5px 5px;\n        }\n        .button {\n            display: inline-block;\n            background-color: #28a745;\n            color: white;\n            padding: 12px 30px;\n            text-decoration: none;\n            border-radius: 5px;\n            margin: 20px 0;\n            font-weight: bold;\n        }\n        .button:hover {\n            background-color: #1e7e34;\n        }\n        .event-details {\n            background-color: white;\n            padding: 20px;\n            border-left: 4px solid #28a745;\n            margin: 20px 0;\n        }\n        .footer {\n            text-align: center;\n            margin-top: 30px;\n            font-size: 12px;\n            color: #666;\n        }\n        .verification-note {\n            background-color: #fff3cd;\n            border: 1px solid #ffeaa7;\n            color: #856404;\n            padding: 15px;\n            border-radius: 5px;\n            margin: 20px 0;\n        }\n        .wifi-section {\n            background-color: #e7f3ff;\n            border: 2px solid #2196F3;\n            padding: 20px;\n            border-radius: 8px;\n            margin: 20px 0;\n            text-align: center;\n        }\n        .wifi-qr-code {\n            max-width: 300px;\n            height: auto;\n            margin: 15px auto;\n            display: block;\n        }\n        .wifi-credentials {\n            background-color: white;\n            padding: 15px;\n            border-radius: 5px;\n            margin-top: 15px;\n            text-align: left;\n        }\n    </style>\n</head>\n<body>\n    <div class=\\\"header\\\">\n        <h1>✅ Verify Your Registration</h1>\n        <p>Symfony Development Workshop</p>\n    </div>\n    \n    <div class=\\\"content\\\">\n        <h2>Hello support account!</h2>\n        \n        <p>Thank you for registering for <strong>Symfony Development Workshop</strong>! To complete your registration and gain access to event materials, please verify your email address by clicking the button below:</p>\n        \n        <div style=\\\"text-align: center;\\\">\n            <a href=\\\"/event/symfony-development-workshop/verify/b3020571e42b67628b7ae133e43923a0b964a1719d83cb31244ee2c0efae53c6\\\" class=\\\"button\\\">🔐 Verify Email & Complete Registration</a>\n        </div>\n        \n        <div class=\\\"event-details\\\">\n            <h3>Event Details:</h3>\n            <ul>\n                <li><strong>Title:</strong> Symfony Development Workshop</li>\n                <li><strong>Date:</strong> November 25, 2025 \\\\am\\\\30 8:00 AM</li>\n                                    <li><strong>End Date:</strong> November 25, 2025 \\\\pm\\\\30 5:00 PM</li>\n                                                    <li><strong>Location:</strong> Tech Conference Center, San Francisco</li>\n                            </ul>\n            \n                            <p><strong>About this event:</strong></p>\n                <p>Learn modern PHP web development with Symfony framework. This hands-on workshop covers controllers, templates, database integration, and security best practices.</p>\n                    </div>\n        \n                \n        <div class=\\\"verification-note\\\">\n            <h4>📋 What happens after verification?</h4>\n            <ul>\n                <li>✅ Your registration will be confirmed</li>\n                <li>📁 You\\\'ll get access to event materials and downloads</li>\n                <li>🔑 You can use the \\\"Already Registered?\\\" login on the event page</li>\n                <li>📧 You\\\'ll receive updates about the event</li>\n            </ul>\n        </div>\n        \n        <p><strong>Important:</strong> This verification link is unique to your email address and will expire after 24 hours. If you didn\\\'t register for this event, you can safely ignore this email.</p>\n        \n        <p>If the button above doesn\\\'t work, you can copy and paste this link into your browser:</p>\n        <p style=\\\"word-break: break-all; font-family: monospace; background-color: #e9ecef; padding: 10px;\\\">/event/symfony-development-workshop/verify/b3020571e42b67628b7ae133e43923a0b964a1719d83cb31244ee2c0efae53c6</p>\n    </div>\n    \n    <div class=\\\"footer\\\">\n        <p>This email was sent automatically by the Event Management System.<br>\n        Please do not reply to this email.</p>\n        \n        <p>Having trouble? Contact the event organizer for assistance.</p>\n    </div>\n</body>\n</html>\\\";i:3;s:5:\\\"utf-8\\\";i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:19:\\\"noreply@example.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:19:\\\"support@bayworx.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:57:\\\"Verify your registration for Symfony Development Workshop\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}','[]','default','2025-11-13 02:01:04','2025-11-13 02:01:04',NULL),
(4,'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;N;i:1;N;i:2;s:5003:\\\"<!DOCTYPE html>\n<html>\n<head>\n    <meta charset=\\\"UTF-8\\\">\n    <meta name=\\\"viewport\\\" content=\\\"width=device-width, initial-scale=1.0\\\">\n    <title>Verify Your Registration - DevOps Best Practices Meetup</title>\n    <style>\n        body {\n            font-family: Arial, sans-serif;\n            line-height: 1.6;\n            color: #333;\n            max-width: 600px;\n            margin: 0 auto;\n            padding: 20px;\n        }\n        .header {\n            background-color: #28a745;\n            color: white;\n            padding: 20px;\n            text-align: center;\n            border-radius: 5px 5px 0 0;\n        }\n        .content {\n            background-color: #f8f9fa;\n            padding: 30px;\n            border-radius: 0 0 5px 5px;\n        }\n        .button {\n            display: inline-block;\n            background-color: #28a745;\n            color: white;\n            padding: 12px 30px;\n            text-decoration: none;\n            border-radius: 5px;\n            margin: 20px 0;\n            font-weight: bold;\n        }\n        .button:hover {\n            background-color: #1e7e34;\n        }\n        .event-details {\n            background-color: white;\n            padding: 20px;\n            border-left: 4px solid #28a745;\n            margin: 20px 0;\n        }\n        .footer {\n            text-align: center;\n            margin-top: 30px;\n            font-size: 12px;\n            color: #666;\n        }\n        .verification-note {\n            background-color: #fff3cd;\n            border: 1px solid #ffeaa7;\n            color: #856404;\n            padding: 15px;\n            border-radius: 5px;\n            margin: 20px 0;\n        }\n        .wifi-section {\n            background-color: #e7f3ff;\n            border: 2px solid #2196F3;\n            padding: 20px;\n            border-radius: 8px;\n            margin: 20px 0;\n            text-align: center;\n        }\n        .wifi-qr-code {\n            max-width: 300px;\n            height: auto;\n            margin: 15px auto;\n            display: block;\n        }\n        .wifi-credentials {\n            background-color: white;\n            padding: 15px;\n            border-radius: 5px;\n            margin-top: 15px;\n            text-align: left;\n        }\n    </style>\n</head>\n<body>\n    <div class=\\\"header\\\">\n        <h1>✅ Verify Your Registration</h1>\n        <p>DevOps Best Practices Meetup</p>\n    </div>\n    \n    <div class=\\\"content\\\">\n        <h2>Hello James Hillis!</h2>\n        \n        <p>Thank you for registering for <strong>DevOps Best Practices Meetup</strong>! To complete your registration and gain access to event materials, please verify your email address by clicking the button below:</p>\n        \n        <div style=\\\"text-align: center;\\\">\n            <a href=\\\"/event/devops-best-practices-meetup/verify/03316551658f9f7357521b0eb880366219fe75f12456f93d8981f7f459801b1c\\\" class=\\\"button\\\">🔐 Verify Email & Complete Registration</a>\n        </div>\n        \n        <div class=\\\"event-details\\\">\n            <h3>Event Details:</h3>\n            <ul>\n                <li><strong>Title:</strong> DevOps Best Practices Meetup</li>\n                <li><strong>Date:</strong> November 29, 2025 \\\\am\\\\30 9:00 AM</li>\n                                    <li><strong>End Date:</strong> November 29, 2025 \\\\pm\\\\30 4:00 PM</li>\n                                                    <li><strong>Location:</strong> San Francisco Convention Center</li>\n                            </ul>\n            \n                            <p><strong>About this event:</strong></p>\n                <p>A local meetup discussing DevOps practices, containerization, CI/CD pipelines, and infrastructure as code.</p>\n                    </div>\n        \n                \n        <div class=\\\"verification-note\\\">\n            <h4>📋 What happens after verification?</h4>\n            <ul>\n                <li>✅ Your registration will be confirmed</li>\n                <li>📁 You\\\'ll get access to event materials and downloads</li>\n                <li>🔑 You can use the \\\"Already Registered?\\\" login on the event page</li>\n                <li>📧 You\\\'ll receive updates about the event</li>\n            </ul>\n        </div>\n        \n        <p><strong>Important:</strong> This verification link is unique to your email address and will expire after 24 hours. If you didn\\\'t register for this event, you can safely ignore this email.</p>\n        \n        <p>If the button above doesn\\\'t work, you can copy and paste this link into your browser:</p>\n        <p style=\\\"word-break: break-all; font-family: monospace; background-color: #e9ecef; padding: 10px;\\\">/event/devops-best-practices-meetup/verify/03316551658f9f7357521b0eb880366219fe75f12456f93d8981f7f459801b1c</p>\n    </div>\n    \n    <div class=\\\"footer\\\">\n        <p>This email was sent automatically by the Event Management System.<br>\n        Please do not reply to this email.</p>\n        \n        <p>Having trouble? Contact the event organizer for assistance.</p>\n    </div>\n</body>\n</html>\\\";i:3;s:5:\\\"utf-8\\\";i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:19:\\\"noreply@example.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:18:\\\"ebjoker4@yahoo.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:57:\\\"Verify your registration for DevOps Best Practices Meetup\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}','[]','default','2025-11-26 20:30:52','2025-11-26 20:30:52',NULL),
(5,'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;N;i:1;N;i:2;s:3155:\\\"<!DOCTYPE html>\n<html>\n<head>\n    <meta charset=\\\"UTF-8\\\">\n    <meta name=\\\"viewport\\\" content=\\\"width=device-width, initial-scale=1.0\\\">\n    <title>Login Link - Annual Conference</title>\n    <style>\n        body {\n            font-family: Arial, sans-serif;\n            line-height: 1.6;\n            color: #333;\n            max-width: 600px;\n            margin: 0 auto;\n            padding: 20px;\n        }\n        .header {\n            background-color: #007bff;\n            color: white;\n            padding: 20px;\n            text-align: center;\n            border-radius: 5px 5px 0 0;\n        }\n        .content {\n            background-color: #f8f9fa;\n            padding: 30px;\n            border-radius: 0 0 5px 5px;\n        }\n        .button {\n            display: inline-block;\n            background-color: #007bff;\n            color: white;\n            padding: 12px 30px;\n            text-decoration: none;\n            border-radius: 5px;\n            margin: 20px 0;\n            font-weight: bold;\n        }\n        .button:hover {\n            background-color: #0056b3;\n        }\n        .event-details {\n            background-color: white;\n            padding: 20px;\n            border-left: 4px solid #007bff;\n            margin: 20px 0;\n        }\n        .footer {\n            text-align: center;\n            margin-top: 30px;\n            font-size: 12px;\n            color: #666;\n        }\n    </style>\n</head>\n<body>\n    <div class=\\\"header\\\">\n        <h1>📅 Annual Conference</h1>\n    </div>\n    \n    <div class=\\\"content\\\">\n        <h2>Hello test!</h2>\n        \n        <p>You requested access to the <strong>Annual Conference</strong> event. Click the button below to securely log in and access event materials:</p>\n        \n        <div style=\\\"text-align: center;\\\">\n            <a href=\\\"/event/annual-conference/verify/28791cb109a31f2621b709cb4f98ace52aa341fcd80607ae87dde7744d3a37c5\\\" class=\\\"button\\\">🔐 Access Event</a>\n        </div>\n        \n        <div class=\\\"event-details\\\">\n            <h3>Event Details:</h3>\n            <ul>\n                <li><strong>Title:</strong> Annual Conference</li>\n                <li><strong>Date:</strong> December 15, 2025 at 9:00 AM</li>\n                                    <li><strong>End Date:</strong> December 15, 2025 at 5:00 PM</li>\n                                                    <li><strong>Location:</strong> Convention Center</li>\n                            </ul>\n        </div>\n        \n        <p><strong>Note:</strong> This login link is unique to your email address and will expire after use. If you didn\\\'t request this link, you can safely ignore this email.</p>\n        \n        <p>If the button above doesn\\\'t work, you can copy and paste this link into your browser:</p>\n        <p style=\\\"word-break: break-all; font-family: monospace; background-color: #e9ecef; padding: 10px;\\\">/event/annual-conference/verify/28791cb109a31f2621b709cb4f98ace52aa341fcd80607ae87dde7744d3a37c5</p>\n    </div>\n    \n    <div class=\\\"footer\\\">\n        <p>This email was sent automatically by the Event Management System.<br>\n        Please do not reply to this email.</p>\n    </div>\n</body>\n</html>\\\";i:3;s:5:\\\"utf-8\\\";i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:19:\\\"noreply@example.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:18:\\\"ebjoker4@yahoo.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:26:\\\"Login to Annual Conference\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}','[]','default','2025-11-26 21:49:05','2025-11-26 21:49:05',NULL),
(6,'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;N;i:1;N;i:2;s:3209:\\\"<!DOCTYPE html>\n<html>\n<head>\n    <meta charset=\\\"UTF-8\\\">\n    <meta name=\\\"viewport\\\" content=\\\"width=device-width, initial-scale=1.0\\\">\n    <title>Login Link - Monthly Technology Roundup</title>\n    <style>\n        body {\n            font-family: Arial, sans-serif;\n            line-height: 1.6;\n            color: #333;\n            max-width: 600px;\n            margin: 0 auto;\n            padding: 20px;\n        }\n        .header {\n            background-color: #007bff;\n            color: white;\n            padding: 20px;\n            text-align: center;\n            border-radius: 5px 5px 0 0;\n        }\n        .content {\n            background-color: #f8f9fa;\n            padding: 30px;\n            border-radius: 0 0 5px 5px;\n        }\n        .button {\n            display: inline-block;\n            background-color: #007bff;\n            color: white;\n            padding: 12px 30px;\n            text-decoration: none;\n            border-radius: 5px;\n            margin: 20px 0;\n            font-weight: bold;\n        }\n        .button:hover {\n            background-color: #0056b3;\n        }\n        .event-details {\n            background-color: white;\n            padding: 20px;\n            border-left: 4px solid #007bff;\n            margin: 20px 0;\n        }\n        .footer {\n            text-align: center;\n            margin-top: 30px;\n            font-size: 12px;\n            color: #666;\n        }\n    </style>\n</head>\n<body>\n    <div class=\\\"header\\\">\n        <h1>📅 Monthly Technology Roundup</h1>\n    </div>\n    \n    <div class=\\\"content\\\">\n        <h2>Hello derp!</h2>\n        \n        <p>You requested access to the <strong>Monthly Technology Roundup</strong> event. Click the button below to securely log in and access event materials:</p>\n        \n        <div style=\\\"text-align: center;\\\">\n            <a href=\\\"/event/monthly-technology-roundup/verify/eb6b40e1fa8e381c03afe05c9aef6b035cddf334a661c080c5d94f0de52f2feb\\\" class=\\\"button\\\">🔐 Access Event</a>\n        </div>\n        \n        <div class=\\\"event-details\\\">\n            <h3>Event Details:</h3>\n            <ul>\n                <li><strong>Title:</strong> Monthly Technology Roundup</li>\n                <li><strong>Date:</strong> November 30, 2025 at 9:00 AM</li>\n                                    <li><strong>End Date:</strong> November 30, 2025 at 5:00 PM</li>\n                                                    <li><strong>Location:</strong> San Jose Tech Hub</li>\n                            </ul>\n        </div>\n        \n        <p><strong>Note:</strong> This login link is unique to your email address and will expire after use. If you didn\\\'t request this link, you can safely ignore this email.</p>\n        \n        <p>If the button above doesn\\\'t work, you can copy and paste this link into your browser:</p>\n        <p style=\\\"word-break: break-all; font-family: monospace; background-color: #e9ecef; padding: 10px;\\\">/event/monthly-technology-roundup/verify/eb6b40e1fa8e381c03afe05c9aef6b035cddf334a661c080c5d94f0de52f2feb</p>\n    </div>\n    \n    <div class=\\\"footer\\\">\n        <p>This email was sent automatically by the Event Management System.<br>\n        Please do not reply to this email.</p>\n    </div>\n</body>\n</html>\\\";i:3;s:5:\\\"utf-8\\\";i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:19:\\\"noreply@example.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:21:\\\"tyrranicide@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:35:\\\"Login to Monthly Technology Roundup\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}','[]','default','2025-11-27 05:56:47','2025-11-27 05:56:47',NULL),
(7,'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;N;i:1;N;i:2;s:3621:\\\"<!DOCTYPE html>\n<html>\n<head>\n    <meta charset=\\\"UTF-8\\\">\n    <meta name=\\\"viewport\\\" content=\\\"width=device-width, initial-scale=1.0\\\">\n    <title>Password Reset Request</title>\n    <style>\n        body {\n            font-family: Arial, sans-serif;\n            line-height: 1.6;\n            color: #333;\n            max-width: 600px;\n            margin: 0 auto;\n            padding: 20px;\n        }\n        .header {\n            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);\n            color: white;\n            padding: 30px;\n            text-align: center;\n            border-radius: 8px 8px 0 0;\n        }\n        .content {\n            background: #f8f9fa;\n            padding: 30px;\n            border: 1px solid #e9ecef;\n        }\n        .button {\n            display: inline-block;\n            padding: 12px 30px;\n            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);\n            color: white;\n            text-decoration: none;\n            border-radius: 5px;\n            margin: 20px 0;\n            font-weight: bold;\n        }\n        .footer {\n            background: #e9ecef;\n            padding: 20px;\n            text-align: center;\n            font-size: 12px;\n            color: #6c757d;\n            border-radius: 0 0 8px 8px;\n        }\n        .warning {\n            background: #fff3cd;\n            border: 1px solid #ffc107;\n            padding: 15px;\n            border-radius: 5px;\n            margin: 20px 0;\n        }\n        .info-box {\n            background: #d1ecf1;\n            border: 1px solid #0c5460;\n            padding: 15px;\n            border-radius: 5px;\n            margin: 20px 0;\n        }\n    </style>\n</head>\n<body>\n    <div class=\\\"header\\\">\n        <h1 style=\\\"margin: 0;\\\">🔐 Password Reset Request</h1>\n    </div>\n    \n    <div class=\\\"content\\\">\n        <p>Hello Jeff Hill,</p>\n        \n        <p>We received a request to reset the password for your administrator account associated with <strong>jeff@bayworx.com</strong>.</p>\n        \n        <p>Click the button below to reset your password:</p>\n        \n        <div style=\\\"text-align: center;\\\">\n            <a href=\\\"/admin/reset-password/b6b4e2550de417c83abf9b3e8952909c377de6ed84f79c8b7fddc03b078ba9be\\\" class=\\\"button\\\">Reset My Password</a>\n        </div>\n        \n        <div class=\\\"info-box\\\">\n            <strong>⏰ Important:</strong> This password reset link will expire in <strong>1 hour</strong> for security reasons.\n        </div>\n        \n        <p>If the button above doesn\\\'t work, you can copy and paste the following link into your browser:</p>\n        <p style=\\\"word-break: break-all; background: #fff; padding: 10px; border: 1px solid #dee2e6; border-radius: 4px;\\\">\n            /admin/reset-password/b6b4e2550de417c83abf9b3e8952909c377de6ed84f79c8b7fddc03b078ba9be\n        </p>\n        \n        <div class=\\\"warning\\\">\n            <strong>⚠️ Didn\\\'t request this?</strong><br>\n            If you didn\\\'t request a password reset, you can safely ignore this email. Your password will remain unchanged.\n        </div>\n        \n        <p>For security reasons, we recommend:</p>\n        <ul>\n            <li>Using a strong, unique password</li>\n            <li>Not sharing your password with anyone</li>\n            <li>Changing your password regularly</li>\n        </ul>\n    </div>\n    \n    <div class=\\\"footer\\\">\n        <p><strong>Event Management System - Administrator Portal</strong></p>\n        <p>This is an automated email. Please do not reply to this message.</p>\n        <p>&copy; 2025 Event Management System. All rights reserved.</p>\n    </div>\n</body>\n</html>\n\\\";i:3;s:5:\\\"utf-8\\\";i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:19:\\\"noreply@example.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:16:\\\"jeff@bayworx.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:22:\\\"Password Reset Request\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}','[]','default','2025-12-15 03:24:48','2025-12-15 03:24:48',NULL);
/*!40000 ALTER TABLE `messenger_messages` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `presenter`
--

DROP TABLE IF EXISTS `presenter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `presenter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `bio` longtext DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `presenter`
--

LOCK TABLES `presenter` WRITE;
/*!40000 ALTER TABLE `presenter` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `presenter` VALUES
(1,'Jeff Hill','jeff@bayworx.com','CEO','BAYWORX LLC','Jeff is a 30 year veteran in the IT industry',NULL,NULL,NULL,'68e6c43a29b36696439769.jpg','2025-10-08 20:06:18','2025-10-09 14:47:53'),
(2,'Lisa Adams','lisaadams6@gmail.com','Principal','Lisa Adams Consulting','A 15 year veteran in the field of Digital Forensics, Lisa has worked on many famous cases for the FBI, CIA and NSA',NULL,NULL,NULL,'68e7caed9ea47298724794.jpg','2025-10-09 14:47:09','2025-10-09 14:47:09'),
(3,'Darlene Bostaph','darlene@tcgaccounting.com','CFO','TCG Accounting','Darlene has led the finance department of several companies in her 26 year career.',NULL,NULL,NULL,'68e865da9cc81951011853.jpg','2025-10-10 01:48:10','2025-10-10 01:48:10'),
(4,'John Terry','john.terry@terryandassociates.com','Principal','Terry Consulting','John is an industry veteran and has helped several companies go public in his 11 year career.',NULL,NULL,NULL,'68e8667aeb4a1536186137.jpg','2025-10-10 01:50:50','2025-10-10 01:50:50'),
(5,'Jane Smith',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'68ec62498ae62979912668.jpg','2025-10-10 19:05:17','2025-10-13 02:22:01'),
(6,'Steve Simpson',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'68f9960d5a327626872389.jpg','2025-10-11 00:05:47','2025-10-23 02:42:21'),
(7,'Gail Jameson',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-10-11 00:05:47',NULL),
(8,'Scott Dobson',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-10-11 00:05:47',NULL);
/*!40000 ALTER TABLE `presenter` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-12-15 12:58:40
