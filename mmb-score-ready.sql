/*
SQLyog Community v13.3.0 (64 bit)
MySQL - 10.4.32-MariaDB : Database - db_mmbts
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`db_mmbts` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `db_mmbts`;

/*Table structure for table `cache` */

DROP TABLE IF EXISTS `cache`;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `cache` */

insert  into `cache`(`key`,`value`,`expiration`) values 
('mmbts-cache-0d90c6abe6ac89d6859a9bad9961cbe9','i:1;',1763946787),
('mmbts-cache-0d90c6abe6ac89d6859a9bad9961cbe9:timer','i:1763946787;',1763946787),
('mmbts-cache-1fa506e4ba6fa583d5aaffb01bdec5e3','i:1;',1763708922),
('mmbts-cache-1fa506e4ba6fa583d5aaffb01bdec5e3:timer','i:1763708922;',1763708922),
('mmbts-cache-9295302d30d58f784de50d640538d29f','i:1;',1763946793),
('mmbts-cache-9295302d30d58f784de50d640538d29f:timer','i:1763946793;',1763946793),
('mmbts-cache-admin@mmb.local|192.168.6.169','i:1;',1763946787),
('mmbts-cache-admin@mmb.local|192.168.6.169:timer','i:1763946787;',1763946787),
('mmbts-cache-b048dfee44fbb508fa0a1792efb09ab4','i:1;',1763948485),
('mmbts-cache-b048dfee44fbb508fa0a1792efb09ab4:timer','i:1763948485;',1763948485),
('mmbts-cache-fb4ff36009efc4776ba34b773902d930','i:1;',1763946701),
('mmbts-cache-fb4ff36009efc4776ba34b773902d930:timer','i:1763946701;',1763946701);

/*Table structure for table `cache_locks` */

DROP TABLE IF EXISTS `cache_locks`;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `cache_locks` */

/*Table structure for table `categories` */

DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `phase_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_event_slug_unique` (`event_id`,`slug`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_phase_id_foreign` (`phase_id`),
  CONSTRAINT `categories_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `pageant_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `categories_phase_id_foreign` FOREIGN KEY (`phase_id`) REFERENCES `phases` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `categories` */

insert  into `categories`(`id`,`event_id`,`phase_id`,`name`,`slug`,`weight`,`order`,`is_active`,`created_at`,`updated_at`) values 
(1,1,1,'Benguet Attire','benguet-attire',10.00,0,1,'2025-11-21 08:03:00','2025-11-21 08:03:00'),
(2,1,1,'Casual Interview','casual-interview',10.00,0,1,'2025-11-21 08:03:17','2025-11-21 08:03:17'),
(3,1,2,'Advocacy','advocacy',15.00,0,1,'2025-11-21 08:04:53','2025-11-21 08:04:53'),
(4,1,3,'Swim Wear','swim-wear',15.00,0,1,'2025-11-21 08:05:47','2025-11-21 08:05:59'),
(5,1,3,'Talent','talent',10.00,0,1,'2025-11-21 08:06:13','2025-11-21 08:06:13'),
(6,1,4,'Creative Wear','creative-wear',20.00,0,1,'2025-11-21 08:06:50','2025-11-21 08:06:50'),
(7,1,4,'Formal Wear','formal-wear',20.00,0,1,'2025-11-21 08:07:12','2025-11-21 08:07:12'),
(8,1,4,'Q & A','q-&-a',40.00,0,1,'2025-11-24 02:07:58','2025-11-24 02:07:58');

/*Table structure for table `category_results` */

DROP TABLE IF EXISTS `category_results`;

CREATE TABLE `category_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contestant_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `event_id` bigint(20) unsigned NOT NULL,
  `phase_id` bigint(20) unsigned DEFAULT NULL,
  `average_score` decimal(8,5) NOT NULL DEFAULT 0.00000,
  `category_total` decimal(8,5) NOT NULL DEFAULT 0.00000,
  `rank` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_contestant_category_event` (`contestant_id`,`category_id`,`event_id`),
  KEY `category_results_category_id_foreign` (`category_id`),
  KEY `category_results_event_id_foreign` (`event_id`),
  KEY `category_results_phase_id_foreign` (`phase_id`),
  CONSTRAINT `category_results_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_results_contestant_id_foreign` FOREIGN KEY (`contestant_id`) REFERENCES `contestants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_results_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `pageant_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_results_phase_id_foreign` FOREIGN KEY (`phase_id`) REFERENCES `phases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `category_results` */

/*Table structure for table `contestants` */

DROP TABLE IF EXISTS `contestants`;

CREATE TABLE `contestants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `municipality_id` bigint(20) unsigned DEFAULT NULL,
  `event_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contestants_municipality_id_foreign` (`municipality_id`),
  KEY `contestants_event_id_gender_index` (`event_id`,`gender`),
  CONSTRAINT `contestants_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `pageant_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contestants_municipality_id_foreign` FOREIGN KEY (`municipality_id`) REFERENCES `municipalities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `contestants` */

insert  into `contestants`(`id`,`municipality_id`,`event_id`,`name`,`gender`,`number`,`created_at`,`updated_at`) values 
(1,4,1,'Kurt Russell P. Taynan','male','01','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(2,4,1,'Claudine A. Tokiyas','female','01','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(3,5,1,'Jomarie K. Tanacio','male','02','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(4,5,1,'Shikara Dumayag','female','02','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(5,13,1,'Randolph M. Bonce','male','03','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(6,13,1,'Fara Mae V. Macido','female','03','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(7,11,1,'Orlino T. Lerot','male','04','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(8,11,1,'Shazely C. Palaci','female','04','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(9,7,1,'Jamroel L. Damugo','male','05','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(10,7,1,'Christal A. Dagupen','female','05','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(11,9,1,'Clifford D. Yagyagan','male','06','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(12,9,1,'Angelika Josierny B. Servinas','female','06','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(13,1,1,'Rudee L. Dolipas, Jr.','male','07','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(14,1,1,'Zairee D. Alcid','female','07','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(15,6,1,'Benar John D. Wallace','male','08','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(16,6,1,'Venus N. Belao','female','08','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(17,3,1,'Raymund D. Pablo','male','09','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(18,3,1,'Honey Lynne A. Tictic','female','09','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(19,10,1,'Reden D. Saliwey','male','10','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(20,10,1,'Maria Teresa T. Velasco','female','10','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(21,12,1,'Sidrick Azrell L. Sevilla','male','11','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(22,12,1,'Trixie Marie N. Togatag','female','11','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(23,8,1,'Kyle Brent S. Kimayong','male','12','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(24,8,1,'Harleth P. Pantaleon','female','12','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(25,2,1,'Areli A. Ang-ayon','male','13','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(26,2,1,'Irish Amor B. Kudan','female','13','2025-11-21 07:07:04','2025-11-21 07:07:04');

/*Table structure for table `criteria` */

DROP TABLE IF EXISTS `criteria`;

CREATE TABLE `criteria` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `criteria_event_id_foreign` (`event_id`),
  KEY `criteria_parent_id_foreign` (`parent_id`),
  KEY `criteria_category_id_parent_id_index` (`category_id`,`parent_id`),
  CONSTRAINT `criteria_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `criteria_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `pageant_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `criteria_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `criteria` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `criteria` */

insert  into `criteria`(`id`,`event_id`,`category_id`,`parent_id`,`name`,`percentage`,`order`,`created_at`,`updated_at`) values 
(1,1,1,NULL,'Suitability to the candidate',40.00,0,'2025-11-24 01:33:55','2025-11-24 01:33:55'),
(2,1,1,NULL,'Poise & Bearing',30.00,0,'2025-11-24 01:39:40','2025-11-24 01:39:40'),
(3,1,1,NULL,'Projection',30.00,0,'2025-11-24 01:39:54','2025-11-24 01:39:54'),
(4,1,2,NULL,'Substance',50.00,0,'2025-11-24 01:41:31','2025-11-24 01:41:31'),
(5,1,2,NULL,'Spontaneity & Confidence',50.00,0,'2025-11-24 01:41:58','2025-11-24 01:41:58'),
(6,1,3,NULL,'Written Proposal',50.00,0,'2025-11-24 01:52:54','2025-11-24 01:52:54'),
(7,1,3,6,'Feasibility',60.00,0,'2025-11-24 01:53:38','2025-11-24 01:53:38'),
(8,1,3,6,'Clarity',20.00,0,'2025-11-24 01:54:25','2025-11-24 01:54:25'),
(9,1,3,6,'Organization',20.00,0,'2025-11-24 01:54:45','2025-11-24 01:54:45'),
(10,1,3,NULL,'Presentation',50.00,0,'2025-11-24 01:55:13','2025-11-24 01:55:13'),
(11,1,3,10,'Persuasiveness',30.00,0,'2025-11-24 01:55:58','2025-11-24 01:55:58'),
(12,1,3,10,'Effective Delivery',30.00,0,'2025-11-24 01:57:22','2025-11-24 01:57:22'),
(13,1,3,10,'Ability to Answer Questions',40.00,0,'2025-11-24 01:58:03','2025-11-24 01:58:03'),
(14,1,5,NULL,'Presentation / Delivery / Mastery',40.00,0,'2025-11-24 02:00:14','2025-11-24 02:00:14'),
(15,1,5,NULL,'Artistic Skill',30.00,0,'2025-11-24 02:00:36','2025-11-24 02:00:36'),
(16,1,5,NULL,'Originality',30.00,0,'2025-11-24 02:01:05','2025-11-24 02:01:05'),
(17,1,4,NULL,'Suitability to the candidate',40.00,0,'2025-11-24 02:01:52','2025-11-24 02:01:52'),
(18,1,4,NULL,'Poise & Bearing',30.00,0,'2025-11-24 02:02:14','2025-11-24 02:02:14'),
(19,1,4,NULL,'Stage Presence',30.00,0,'2025-11-24 02:02:42','2025-11-24 02:02:42'),
(20,1,6,NULL,'Suitability to the candidate',40.00,0,'2025-11-24 02:03:08','2025-11-24 02:03:08'),
(21,1,6,NULL,'Poise & Bearing',30.00,0,'2025-11-24 02:03:47','2025-11-24 02:03:47'),
(22,1,6,NULL,'Stage Presence',30.00,0,'2025-11-24 02:04:53','2025-11-24 02:04:53'),
(23,1,7,NULL,'Suitability to the candidate',40.00,0,'2025-11-24 02:06:14','2025-11-24 02:06:14'),
(24,1,7,NULL,'Poise & Bearing',30.00,0,'2025-11-24 02:06:35','2025-11-24 02:06:35'),
(25,1,7,NULL,'Stage Presence',30.00,0,'2025-11-24 02:07:13','2025-11-24 02:07:13'),
(26,1,8,NULL,'Content',50.00,0,'2025-11-24 02:10:19','2025-11-24 02:10:19'),
(27,1,8,NULL,'Delivery',25.00,0,'2025-11-24 02:10:40','2025-11-24 02:10:40'),
(28,1,8,NULL,'Confidence & Overall Presence',25.00,0,'2025-11-24 02:11:16','2025-11-24 02:11:16');

/*Table structure for table `failed_jobs` */

DROP TABLE IF EXISTS `failed_jobs`;

CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `failed_jobs` */

/*Table structure for table `job_batches` */

DROP TABLE IF EXISTS `job_batches`;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `job_batches` */

/*Table structure for table `jobs` */

DROP TABLE IF EXISTS `jobs`;

CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `jobs` */

/*Table structure for table `judge_phase` */

DROP TABLE IF EXISTS `judge_phase`;

CREATE TABLE `judge_phase` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `judge_id` bigint(20) unsigned NOT NULL,
  `phase_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `judge_phase_judge_id_phase_id_unique` (`judge_id`,`phase_id`),
  KEY `judge_phase_phase_id_foreign` (`phase_id`),
  CONSTRAINT `judge_phase_judge_id_foreign` FOREIGN KEY (`judge_id`) REFERENCES `judges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `judge_phase_phase_id_foreign` FOREIGN KEY (`phase_id`) REFERENCES `phases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `judge_phase` */

insert  into `judge_phase`(`id`,`judge_id`,`phase_id`,`created_at`,`updated_at`) values 
(1,1,1,NULL,NULL),
(2,2,1,NULL,NULL),
(3,3,1,NULL,NULL),
(4,4,2,NULL,NULL),
(5,5,2,NULL,NULL),
(6,6,2,NULL,NULL),
(7,7,2,NULL,NULL),
(8,8,3,NULL,NULL),
(9,9,3,NULL,NULL),
(10,10,3,NULL,NULL);

/*Table structure for table `judges` */

DROP TABLE IF EXISTS `judges`;

CREATE TABLE `judges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `event_id` bigint(20) unsigned NOT NULL,
  `category_assignment` varchar(255) DEFAULT NULL,
  `judge_number` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `judges_user_id_event_id_unique` (`user_id`,`event_id`),
  KEY `judges_event_id_foreign` (`event_id`),
  CONSTRAINT `judges_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `pageant_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `judges_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `judges` */

insert  into `judges`(`id`,`user_id`,`event_id`,`category_assignment`,`judge_number`,`is_active`,`created_at`,`updated_at`) values 
(1,7,1,'Benguet Attire, Casual Interview',1,1,'2025-11-21 07:46:48','2025-11-21 07:46:48'),
(2,8,1,'Benguet Attire, Casual Interview',2,1,'2025-11-21 07:47:39','2025-11-21 07:47:39'),
(3,9,1,'Benguet Attire, Casual Interview',3,1,'2025-11-21 07:47:46','2025-11-21 07:47:46'),
(4,10,1,'Advocacy Pitch',1,1,'2025-11-21 07:57:58','2025-11-21 07:57:58'),
(5,11,1,'Advocacy Pitch',2,1,'2025-11-21 07:58:08','2025-11-21 07:58:08'),
(6,12,1,'Advocacy Pitch',3,1,'2025-11-21 07:58:21','2025-11-21 07:58:21'),
(7,13,1,'Advocacy Pitch',4,1,'2025-11-21 07:58:32','2025-11-21 07:58:32'),
(8,14,1,'Talent, Swimwear',1,1,'2025-11-21 08:00:00','2025-11-21 08:00:00'),
(9,15,1,'Talent, Swimwear',2,1,'2025-11-21 08:00:22','2025-11-21 08:00:22'),
(10,16,1,'Talent, Swimwear',3,1,'2025-11-21 08:00:33','2025-11-21 08:00:33');

/*Table structure for table `migrations` */

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `migrations` */

insert  into `migrations`(`id`,`migration`,`batch`) values 
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2025_09_26_025050_create_roles_table',1),
(5,'2025_09_26_025051_create_permissions_table',1),
(6,'2025_09_26_025052_create_permission_role_table',1),
(7,'2025_09_26_025052_create_role_user_table',1),
(8,'2025_09_26_025053_create_pageant_events_table',1),
(9,'2025_09_26_025053_create_phases_table',1),
(10,'2025_09_26_025054_create_municipalities_table',1),
(11,'2025_09_26_025055_create_contestants_table',1),
(12,'2025_09_26_025207_create_judges_table',1),
(13,'2025_09_26_025215_create_categories_table',1),
(14,'2025_09_26_025225_create_criteria_table',1),
(15,'2025_09_26_025233_create_scores_table',1),
(16,'2025_09_26_025245_create_results_table',1),
(17,'2025_09_26_064055_create_personal_access_tokens_table',1),
(18,'2025_10_20_062413_create_category_results_table',1),
(19,'2025_10_21_052959_add_two_factor_columns_to_users_table',1),
(20,'2025_10_26_121119_create_judge_phase_table',1);

/*Table structure for table `municipalities` */

DROP TABLE IF EXISTS `municipalities`;

CREATE TABLE `municipalities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `municipalities_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `municipalities` */

insert  into `municipalities`(`id`,`name`,`created_at`,`updated_at`) values 
(1,'Atok','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(2,'Bakun','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(3,'Bokod','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(4,'Buguias','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(5,'Itogon','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(6,'Kabayan','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(7,'Kapangan','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(8,'Kibungan','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(9,'La Trinidad','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(10,'Mankayan','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(11,'Sablan','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(12,'Tuba','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(13,'Tublay','2025-11-21 07:07:03','2025-11-21 07:07:03');

/*Table structure for table `pageant_events` */

DROP TABLE IF EXISTS `pageant_events`;

CREATE TABLE `pageant_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `year` year(4) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `pageant_events` */

insert  into `pageant_events`(`id`,`title`,`year`,`start_date`,`end_date`,`status`,`created_at`,`updated_at`) values 
(1,'Mr. & Ms. Benguet 2025',2025,'2025-01-10','2025-01-12','active','2025-11-21 07:07:04','2025-11-21 07:07:04');

/*Table structure for table `password_reset_tokens` */

DROP TABLE IF EXISTS `password_reset_tokens`;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `password_reset_tokens` */

/*Table structure for table `permission_role` */

DROP TABLE IF EXISTS `permission_role`;

CREATE TABLE `permission_role` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `permission_role_permission_id_foreign` (`permission_id`),
  KEY `permission_role_role_id_foreign` (`role_id`),
  CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `permission_role` */

insert  into `permission_role`(`id`,`permission_id`,`role_id`,`created_at`,`updated_at`) values 
(1,1,1,NULL,NULL),
(2,2,1,NULL,NULL),
(3,3,1,NULL,NULL),
(4,4,1,NULL,NULL),
(5,5,1,NULL,NULL),
(6,6,1,NULL,NULL),
(7,7,1,NULL,NULL),
(8,8,1,NULL,NULL),
(9,9,1,NULL,NULL),
(10,10,1,NULL,NULL),
(11,11,1,NULL,NULL),
(12,12,1,NULL,NULL),
(13,13,1,NULL,NULL),
(14,9,2,NULL,NULL),
(15,10,2,NULL,NULL),
(16,11,2,NULL,NULL),
(17,9,3,NULL,NULL);

/*Table structure for table `permissions` */

DROP TABLE IF EXISTS `permissions`;

CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `permissions` */

insert  into `permissions`(`id`,`title`,`created_at`,`updated_at`) values 
(1,'user_view','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(2,'user_manage','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(3,'role_view','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(4,'role_manage','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(5,'contestant_view','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(6,'contestant_manage','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(7,'judge_view','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(8,'judge_manage','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(9,'score_enter','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(10,'score_view','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(11,'results_view','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(12,'event_view','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(13,'event_manage','2025-11-21 07:07:03','2025-11-21 07:07:03');

/*Table structure for table `personal_access_tokens` */

DROP TABLE IF EXISTS `personal_access_tokens`;

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `personal_access_tokens` */

/*Table structure for table `phases` */

DROP TABLE IF EXISTS `phases`;

CREATE TABLE `phases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order` int(10) unsigned NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phases_event_id_order_unique` (`event_id`,`order`),
  CONSTRAINT `phases_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `pageant_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `phases` */

insert  into `phases`(`id`,`event_id`,`name`,`description`,`order`,`is_active`,`created_at`,`updated_at`) values 
(1,1,'Kickoff','Opening phase of the pageant.',1,1,'2025-11-21 07:07:04','2025-11-21 07:07:04'),
(2,1,'Advocacy Pitch','Candidates present their advocacies.',2,1,'2025-11-21 07:07:04','2025-11-21 07:07:04'),
(3,1,'Pre Pageant','Preliminary competition and presentation.',3,1,'2025-11-21 07:07:04','2025-11-21 07:07:04'),
(4,1,'Final Pageant','The coronation and final judging.',4,1,'2025-11-21 07:07:04','2025-11-21 07:07:04');

/*Table structure for table `results` */

DROP TABLE IF EXISTS `results`;

CREATE TABLE `results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contestant_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `event_id` bigint(20) unsigned NOT NULL,
  `total_score` decimal(8,5) NOT NULL DEFAULT 0.00000,
  `rank` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `results_contestant_id_category_id_unique` (`contestant_id`,`category_id`),
  KEY `results_category_id_foreign` (`category_id`),
  KEY `results_event_id_foreign` (`event_id`),
  CONSTRAINT `results_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `results_contestant_id_foreign` FOREIGN KEY (`contestant_id`) REFERENCES `contestants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `results_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `pageant_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `results` */

/*Table structure for table `role_user` */

DROP TABLE IF EXISTS `role_user`;

CREATE TABLE `role_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `role_user_role_id_foreign` (`role_id`),
  KEY `role_user_user_id_foreign` (`user_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `role_user` */

insert  into `role_user`(`id`,`role_id`,`user_id`,`created_at`,`updated_at`) values 
(1,1,1,NULL,NULL),
(2,2,2,NULL,NULL),
(3,2,3,NULL,NULL),
(4,2,4,NULL,NULL),
(5,2,5,NULL,NULL),
(6,2,6,NULL,NULL),
(7,3,7,NULL,NULL),
(8,3,8,NULL,NULL),
(9,3,9,NULL,NULL),
(10,3,10,NULL,NULL),
(11,3,11,NULL,NULL),
(12,3,12,NULL,NULL),
(13,3,13,NULL,NULL),
(14,3,14,NULL,NULL),
(15,3,15,NULL,NULL),
(16,3,16,NULL,NULL);

/*Table structure for table `roles` */

DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `roles` */

insert  into `roles`(`id`,`title`,`created_at`,`updated_at`) values 
(1,'Admin','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(2,'Tabulator','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(3,'Judge','2025-11-21 07:07:03','2025-11-21 07:07:03');

/*Table structure for table `scores` */

DROP TABLE IF EXISTS `scores`;

CREATE TABLE `scores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `judge_id` bigint(20) unsigned NOT NULL,
  `contestant_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `criterion_id` bigint(20) unsigned NOT NULL,
  `score` decimal(8,5) NOT NULL DEFAULT 0.00000,
  `weighted_score` decimal(8,5) NOT NULL DEFAULT 0.00000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_score_per_event` (`event_id`,`judge_id`,`contestant_id`,`criterion_id`),
  KEY `scores_judge_id_foreign` (`judge_id`),
  KEY `scores_contestant_id_foreign` (`contestant_id`),
  KEY `scores_category_id_foreign` (`category_id`),
  KEY `scores_criterion_id_foreign` (`criterion_id`),
  CONSTRAINT `scores_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scores_contestant_id_foreign` FOREIGN KEY (`contestant_id`) REFERENCES `contestants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scores_criterion_id_foreign` FOREIGN KEY (`criterion_id`) REFERENCES `criteria` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scores_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `pageant_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scores_judge_id_foreign` FOREIGN KEY (`judge_id`) REFERENCES `judges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `scores` */

/*Table structure for table `sessions` */

DROP TABLE IF EXISTS `sessions`;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `sessions` */

insert  into `sessions`(`id`,`user_id`,`ip_address`,`user_agent`,`payload`,`last_activity`) values 
('dCzJMricDOr5dYeCiROUpV0H5K02CqOErNE2RiRM',1,'192.168.6.169','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiQUY5NzlmYlgzQnVEQ3duTUp3eTJLbml2dHdrdGphTWpKUDFnYTNGSyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6ODY6Imh0dHA6Ly8xOTIuMTY4LjYuMTY5L2FwaS9jcml0ZXJpYT9vcmRlcj1kZXNjJnBhZ2U9MSZwZXJfcGFnZT01JnNlYXJjaD0mc29ydD1jcmVhdGVkX2F0Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEyJHJkTG9RR3BHcVhlWFJLeENYdVFpeWVTcVEvQ0dRbHhHemNmRUlrTjE0QnJNM2FacnR3aVpXIjt9',1763950320),
('lIvyLgfYu3hvmMk2Kdl0QBeHZdkrz1AixsfpP8Zs',9,'192.168.6.77','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiYUp6WmtMcHZYUUVpdGFQc2VRTkV5cXk3T2JQVjNUOTAyajVHVjY1biI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6OTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEyJFZUSFdJVXp3R2JTWjIzZDlCUnB6Mk9TNWhwN0xkYkhXM2tXUjkzcWI1MkwxdHN5YklNOG5XIjtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozMDE6Imh0dHA6Ly8xOTIuMTY4LjYuMTY5L2FwaS9zY29yZXMvZmluYWxpc3RzP2NhdGVnb3J5X3NsdWdzJTVCMCU1RD1ldGhuaWMtd2VhciZjYXRlZ29yeV9zbHVncyU1QjElNUQ9Y2FzdWFsLWludGVydmlldyZjYXRlZ29yeV9zbHVncyU1QjIlNUQ9YWR2b2NhY3ktcGl0Y2gmY2F0ZWdvcnlfc2x1Z3MlNUIzJTVEPXN3aW0td2VhciZjYXRlZ29yeV9zbHVncyU1QjQlNUQ9dGFsZW50JmNhdGVnb3J5X3NsdWdzJTVCNSU1RD1jcmVhdGl2ZS13ZWFyJmNhdGVnb3J5X3NsdWdzJTVCNiU1RD1mb3JtYWwtd2VhciZldmVudF9pZD0xJmxpbWl0PTUiO319',1763948631);

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `users` */

insert  into `users`(`id`,`name`,`email`,`email_verified_at`,`password`,`two_factor_secret`,`two_factor_recovery_codes`,`two_factor_confirmed_at`,`remember_token`,`created_at`,`updated_at`) values 
(1,'System Administrator','admin@mmb.com','2025-11-21 07:07:03','$2y$12$rdLoQGpGqXeXRKxCXuQiyeSqQ/CGQlxGzcfEIkN14BrM3aZrtwiZW',NULL,NULL,NULL,'HzhaPdLE8L','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(2,'Tabulator 1','tabulator1@mmb.com','2025-11-21 07:07:03','$2y$12$mV/bTITxPA2ce/cL6V4Fbu5ny.hDcqjfDimMkGiT73ixPqdT6pZWO',NULL,NULL,NULL,'NAe4rFmj63','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(3,'Tabulator 2','tabulator2@mmb.com','2025-11-21 07:07:03','$2y$12$NObJLeZXoQVcOLP1lviVfuBsVoLVl96da2ZIQPS67VMBOhq8VmPh2',NULL,NULL,NULL,'JHLwcEhhjq','2025-11-21 07:07:03','2025-11-21 07:07:03'),
(4,'Tabulator 3','tabulator3@mmb.com','2025-11-21 07:07:04','$2y$12$0k154Oi209m2C23cEGMnpOkjEZzHDbjGn4T4zJu3VOoPKFE1PHXOe',NULL,NULL,NULL,'4hjUsuMbO2','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(5,'Tabulator 4','tabulator4@mmb.com','2025-11-21 07:07:04','$2y$12$jIpOtdmjfsdZRLkdZp6yTOPOpbGp3X2AIDlcGk/db2vVlOSjwtMa.',NULL,NULL,NULL,'QdXv5QTJKl','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(6,'Tabulator 5','tabulator5@mmb.com','2025-11-21 07:07:04','$2y$12$xw/F4RHCvWneemBxqGLE9umpYTCl8m57xHeJ/MdYH.V1dPtNw4oJ2',NULL,NULL,NULL,'cc3VwuYyLh','2025-11-21 07:07:04','2025-11-21 07:07:04'),
(7,'Denver Jones Aluyen','djaluyen@mmb.com',NULL,'$2y$12$sRTLXYajbOINrjiFHZQSJOagvFfFoXRzEZCsdxJth1AYmoEiVJEPu',NULL,NULL,NULL,NULL,'2025-11-21 07:32:10','2025-11-21 07:32:10'),
(8,'Brynne Cessary P. Bayang','bcpbayang@mmb.com',NULL,'$2y$12$906ug5uF/yaxTbWlBf1FluheqFr.N01ftWZhb.0u2sgCm51F3X8Lq',NULL,NULL,NULL,NULL,'2025-11-21 07:33:30','2025-11-21 07:34:40'),
(9,'Raymundo H. Pawid Jr.','rhpawid@mmb.com',NULL,'$2y$12$VTHWIUzwGbSZ23d9BRpz2OS5hp7LdbHW3kWR93qb52L1tsybIM8nW',NULL,NULL,NULL,NULL,'2025-11-21 07:34:29','2025-11-21 07:34:29'),
(10,'Dr. Godfrey Mendoza','gmendoza@mmb.com',NULL,'$2y$12$RJIXrA6TTyUqi0WQ4UuPs.ijE.LMP2zEN57EBy7GaqO3Dz/SOoP4O',NULL,NULL,NULL,NULL,'2025-11-21 07:52:32','2025-11-21 07:52:32'),
(11,'Francis Louis S. Likigan','flslikigan@mmb.com',NULL,'$2y$12$oH4Yx1qrR4PQZfR/WDQ6DezQ0MqAFM52aKy0PrvWoB68P3QlO7laW',NULL,NULL,NULL,NULL,'2025-11-21 07:52:58','2025-11-21 07:55:52'),
(12,'Silverio Pilo Jr.','spilo@mmb.com',NULL,'$2y$12$N19xWXYUfQO6gNUPtXlT8uQRj4s1Ss9gySrF92axQ7NB1h3fNXXKC',NULL,NULL,NULL,NULL,'2025-11-21 07:53:48','2025-11-21 07:55:57'),
(13,'Eunice Engwet','eengwet@mmb.com',NULL,'$2y$12$pT3cKFh9z0YzY4odVZgjJedC35Zfs/t/y3N.GQEfzZkv0wLzDyRAi',NULL,NULL,NULL,NULL,'2025-11-21 07:54:12','2025-11-21 07:56:01'),
(14,'Mia Magdalena C. Fokno','mmcfokno@mmb.com',NULL,'$2y$12$LBTWTfdhfHKTbrnIKKMy6.ZDWTYPar7mMgYl2hZ2qeH9A8fiwaXL.',NULL,NULL,NULL,NULL,'2025-11-21 07:56:25','2025-11-21 07:56:25'),
(15,'Jabby Montemayor','jmontemayor@mmb.com',NULL,'$2y$12$PXLFTorGnSKD8F.cGC5L8eMX6QNjBEg8LD8qQiwCLhT5oMFl1Cw.K',NULL,NULL,NULL,NULL,'2025-11-21 07:56:49','2025-11-21 07:56:49'),
(16,'NJ Torres','ntorres@mmb.com',NULL,'$2y$12$7.rkdaO9aAi9G7AmzVRqYOHLRitGb.rKcSQqgw6j/USwXafaUQyWS',NULL,NULL,NULL,NULL,'2025-11-21 07:57:10','2025-11-21 07:57:10');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
