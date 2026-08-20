-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: property_erp
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
-- Table structure for table `amenities`
--

DROP TABLE IF EXISTS `amenities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `amenities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `icon` varchar(60) DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_amenity_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `amenities`
--

LOCK TABLES `amenities` WRITE;
/*!40000 ALTER TABLE `amenities` DISABLE KEYS */;
INSERT INTO `amenities` VALUES (1,'Electricity','bi-lightning-charge','2026-07-31','08:14:12','2026-07-31','08:14:12'),(2,'Gas Connection','bi-fire','2026-07-31','08:14:12','2026-07-31','08:14:12'),(3,'Water Supply','bi-droplet','2026-07-31','08:14:12','2026-07-31','08:14:12'),(4,'Sewerage','bi-arrow-repeat','2026-07-31','08:14:12','2026-07-31','08:14:12'),(5,'Boundary Wall','bi-bricks','2026-07-31','08:14:12','2026-07-31','08:14:12'),(6,'Gated Community','bi-shield-check','2026-07-31','08:14:12','2026-07-31','08:14:12'),(7,'Park','bi-tree','2026-07-31','08:14:12','2026-07-31','08:14:12'),(8,'Mosque','bi-brightness-high','2026-07-31','08:14:12','2026-07-31','08:14:12'),(9,'School','bi-mortarboard','2026-07-31','08:14:12','2026-07-31','08:14:12'),(10,'Hospital','bi-hospital','2026-07-31','08:14:12','2026-07-31','08:14:12'),(11,'Shopping Mall','bi-shop','2026-07-31','08:14:12','2026-07-31','08:14:12'),(12,'Wide Roads','bi-signpost-split','2026-07-31','08:14:12','2026-07-31','08:14:12');
/*!40000 ALTER TABLE `amenities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `areas`
--

DROP TABLE IF EXISTS `areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `areas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `city_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_areas_city` (`city_id`),
  CONSTRAINT `fk_areas_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `areas`
--

LOCK TABLES `areas` WRITE;
/*!40000 ALTER TABLE `areas` DISABLE KEYS */;
/*!40000 ALTER TABLE `areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banks`
--

DROP TABLE IF EXISTS `banks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `account_title` varchar(150) DEFAULT NULL,
  `account_no` varchar(80) DEFAULT NULL,
  `iban` varchar(80) DEFAULT NULL,
  `branch` varchar(150) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `opening_balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_banks_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banks`
--

LOCK TABLES `banks` WRITE;
/*!40000 ALTER TABLE `banks` DISABLE KEYS */;
INSERT INTO `banks` (`id`, `name`, `account_title`, `account_no`, `iban`, `branch`, `status`, `created_date`, `created_time`, `updated_date`, `updated_time`) VALUES (1,'HBL','Prime Estate Pvt Ltd','0012345678901','PK12HBLB000012345678901','Gulberg, Lahore',1,'2026-07-31','08:14:12','2026-07-31','08:14:12'),(2,'UBL','Prime Estate Pvt Ltd','9876543210','PK12UNIL00009876543210','Main Boulevard, Lahore',1,'2026-07-31','08:14:12','2026-07-31','08:14:12'),(3,'Meezan Bank','Prime Estate Pvt Ltd','1122334455','PK12MEZN00001122334455','Gulberg, Lahore',1,'2026-07-31','08:14:12','2026-07-31','08:14:12');
/*!40000 ALTER TABLE `banks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blocks`
--

DROP TABLE IF EXISTS `blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blocks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_blocks_project` (`project_id`),
  CONSTRAINT `fk_blocks_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocks`
--

LOCK TABLES `blocks` WRITE;
/*!40000 ALTER TABLE `blocks` DISABLE KEYS */;
INSERT INTO `blocks` VALUES (1,1,'Block A','2026-07-31','08:14:26','2026-07-31','08:14:26'),(2,1,'Block B','2026-07-31','08:14:26','2026-07-31','08:14:26'),(3,1,'Block C','2026-07-31','08:14:26','2026-07-31','08:14:26'),(4,1,'Block D','2026-07-31','08:14:26','2026-07-31','08:14:26'),(5,2,'Tower 1','2026-07-31','08:14:26','2026-07-31','08:14:26'),(6,2,'Tower 2','2026-07-31','08:14:26','2026-07-31','08:14:26'),(7,3,'Commercial Avenue','2026-07-31','08:14:26','2026-07-31','08:14:26'),(8,4,'4','2026-08-05','12:53:03','2026-08-05','12:53:03');
/*!40000 ALTER TABLE `blocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `booking_no` varchar(40) NOT NULL,
  `quotation_id` int(10) unsigned DEFAULT NULL,
  `property_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `dealer_id` int(10) unsigned DEFAULT NULL,
  `booking_date` date NOT NULL,
  `sale_type` enum('cash','installment','cash_installment') NOT NULL DEFAULT 'installment',
  `total_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `token_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `booking_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `possession_charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `transfer_charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `installment_plan` enum('monthly','quarterly','half_yearly','yearly','lump_sum') NOT NULL DEFAULT 'monthly',
  `installment_years` int(11) NOT NULL DEFAULT 1,
  `installment_months` int(11) NOT NULL DEFAULT 12,
  `status` enum('booking','active','completed','cancelled') NOT NULL DEFAULT 'booking',
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_booking_no` (`booking_no`),
  KEY `idx_book_property` (`property_id`),
  KEY `idx_book_customer` (`customer_id`),
  CONSTRAINT `fk_book_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_book_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (1,'BK-0001',2,3,2,1,'2025-06-15','installment',15000000.00,150000.00,500000.00,500000.00,100000.00,50000.00,'quarterly',2,8,'active','2026-07-31','08:14:26','2026-07-31','08:14:26'),(2,'BK-0002',NULL,1,5,NULL,'2025-03-10','installment',6500000.00,0.00,300000.00,300000.00,0.00,0.00,'lump_sum',1,1,'completed','2026-07-31','08:14:26','2026-07-31','08:14:26'),(3,'BK-0003',3,10,6,2,'2026-07-20','installment',8500000.00,50000.00,250000.00,250000.00,50000.00,25000.00,'monthly',1,12,'booking','2026-07-31','08:14:26','2026-07-31','08:14:26'),(23,'BK-0004',NULL,2,3,NULL,'2026-08-14','cash',11500000.00,0.00,0.00,11500000.00,0.00,0.00,'monthly',1,12,'completed','2026-08-14','20:52:09','2026-08-14','20:52:09'),(24,'BK-0005',NULL,4,3,NULL,'2026-08-15','cash',7200000.00,0.00,0.00,7200000.00,0.00,0.00,'monthly',1,12,'completed','2026-08-15','14:57:10','2026-08-15','14:57:10'),(25,'BK-0006',NULL,6,3,NULL,'2026-08-15','installment',12000000.00,0.00,0.00,2000000.00,0.00,0.00,'monthly',9,100,'active','2026-08-15','15:00:36','2026-08-15','16:42:27');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `city_id` int(10) unsigned DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_branches_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'Head Office','HO','Main Boulevard, Gulberg III, Lahore','042-111111111','info@example.com',NULL,1,'2026-07-31','08:14:11','2026-07-31','08:14:11');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `call_logs`
--

DROP TABLE IF EXISTS `call_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `call_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` int(10) unsigned NOT NULL,
  `call_date` datetime NOT NULL,
  `duration` int(11) NOT NULL DEFAULT 0,
  `direction` enum('inbound','outbound') NOT NULL DEFAULT 'outbound',
  `note` varchar(500) DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cl_lead` (`lead_id`),
  CONSTRAINT `fk_cl_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `call_logs`
--

LOCK TABLES `call_logs` WRITE;
/*!40000 ALTER TABLE `call_logs` DISABLE KEYS */;
INSERT INTO `call_logs` VALUES (1,1,'2026-07-20 11:30:00',12,'outbound','Discussed payment plan options','2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,2,'2026-07-21 15:00:00',8,'inbound','Customer asked about maintenance charges','2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,3,'2026-07-22 10:15:00',5,'outbound','Line busy, call again','2026-07-31','08:14:27','2026-07-31','08:14:27'),(4,5,'2026-06-18 14:45:00',20,'outbound','Budget too low, suggested smaller property','2026-07-31','08:14:27','2026-07-31','08:14:27');
/*!40000 ALTER TABLE `call_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chart_of_accounts`
--

DROP TABLE IF EXISTS `chart_of_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chart_of_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `name` varchar(180) NOT NULL,
  `account_type` enum('asset','liability','equity','income','expense') NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `opening_balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coa_code` (`code`),
  KEY `fk_coa_parent` (`parent_id`),
  CONSTRAINT `fk_coa_parent` FOREIGN KEY (`parent_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chart_of_accounts`
--

LOCK TABLES `chart_of_accounts` WRITE;
/*!40000 ALTER TABLE `chart_of_accounts` DISABLE KEYS */;
INSERT INTO `chart_of_accounts` VALUES (1,'1000','Cash','asset',NULL,0.00,'2026-07-31','08:14:12','2026-07-31','08:14:12'),(2,'1001','Bank Accounts','asset',NULL,0.00,'2026-07-31','08:14:12','2026-07-31','08:14:12'),(3,'1100','Accounts Receivable','asset',NULL,0.00,'2026-07-31','08:14:12','2026-07-31','08:14:12'),(4,'2000','Accounts Payable','liability',NULL,0.00,'2026-07-31','08:14:12','2026-07-31','08:14:12'),(5,'3000','Capital / Owner Equity','equity',NULL,0.00,'2026-07-31','08:14:12','2026-07-31','08:14:12'),(6,'4000','Sales Income','income',NULL,0.00,'2026-07-31','08:14:12','2026-07-31','08:14:12'),(7,'4100','Rental Income','income',NULL,0.00,'2026-07-31','08:14:12','2026-07-31','08:14:12'),(8,'5000','Salaries Expense','expense',NULL,0.00,'2026-07-31','08:14:12','2026-07-31','08:14:12'),(9,'5100','Office Rent Expense','expense',NULL,0.00,'2026-07-31','08:14:12','2026-07-31','08:14:12'),(10,'5200','Utilities Expense','expense',NULL,0.00,'2026-07-31','08:14:12','2026-07-31','08:14:12'),(11,'5300','Marketing Expense','expense',NULL,0.00,'2026-07-31','08:14:12','2026-07-31','08:14:12'),(12,'5400','Transport Expense','expense',NULL,0.00,'2026-07-31','08:14:12','2026-07-31','08:14:12'),(13,'5500','Miscellaneous Expense','expense',NULL,0.00,'2026-07-31','08:14:12','2026-07-31','08:14:12'),(17,'5700','Purchases','expense',NULL,0.00,'2026-08-17','16:14:13','2026-08-17','16:14:13'),(18,'4000-01','Commission Income','income',6,0.00,'2026-08-13','07:36:32','2026-08-13','07:36:32'),(19,'4000-02','Documentation Charges','income',6,0.00,'2026-08-13','07:36:32','2026-08-13','07:36:32'),(20,'4100-01','Plot / Property Rent','income',7,0.00,'2026-08-13','07:36:32','2026-08-13','07:36:32'),(21,'4100-02','Shop / Commercial Rent','income',7,0.00,'2026-08-13','07:36:32','2026-08-13','07:36:32'),(24,'2050','Employee Payable','liability',NULL,0.00,'2026-08-14','18:28:17','2026-08-14','18:28:17'),(25,'2050-002','ABC','liability',24,0.00,'2026-08-14','18:28:17','2026-08-14','18:28:17'),(27,'1001-001','HBL','asset',2,0.00,'2026-08-14','20:52:09','2026-08-14','20:52:09'),(28,'2060','Contractor Payable','liability',NULL,0.00,'2026-08-15','16:48:31','2026-08-15','16:48:31'),(29,'5600','Construction Expense','expense',NULL,0.00,'2026-08-15','16:48:31','2026-08-15','16:48:31'),(30,'2060-001','Ali Masonry Works','liability',28,0.00,'2026-08-15','16:53:38','2026-08-15','16:53:38'),(31,'2070','Investor Payable','liability',NULL,0.00,'2026-08-17','16:52:01','2026-08-17','16:52:01'),(32,'1200','Stock in Hand','asset',NULL,0.00,'2026-08-19','00:00:00','2026-08-19','00:00:00');
/*!40000 ALTER TABLE `chart_of_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cities`
--

DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cities_country` (`country_id`),
  CONSTRAINT `fk_cities_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cities`
--

LOCK TABLES `cities` WRITE;
/*!40000 ALTER TABLE `cities` DISABLE KEYS */;
INSERT INTO `cities` VALUES (1,1,'Karachi','2026-07-31','08:14:11','2026-07-31','08:14:11'),(2,1,'Lahore','2026-07-31','08:14:11','2026-07-31','08:14:11'),(3,1,'Islamabad','2026-07-31','08:14:11','2026-07-31','08:14:11'),(4,1,'Rawalpindi','2026-07-31','08:14:11','2026-07-31','08:14:11'),(5,1,'Faisalabad','2026-07-31','08:14:11','2026-07-31','08:14:11'),(6,1,'Peshawar','2026-07-31','08:14:11','2026-07-31','08:14:11'),(7,1,'Multan','2026-07-31','08:14:11','2026-07-31','08:14:11'),(8,1,'Quetta','2026-07-31','08:14:11','2026-07-31','08:14:11');
/*!40000 ALTER TABLE `cities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_entries`
--

DROP TABLE IF EXISTS `contractor_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contractor_id` int(10) unsigned NOT NULL,
  `entry_no` varchar(40) NOT NULL,
  `entry_date` date NOT NULL,
  `entry_type` enum('payable','paid') NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `narration` varchar(255) DEFAULT NULL,
  `account_id` int(10) unsigned DEFAULT NULL,
  `project_id` int(10) unsigned DEFAULT NULL,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_conte_no` (`entry_no`),
  KEY `idx_conte_contractor` (`contractor_id`),
  KEY `idx_conte_date` (`entry_date`),
  KEY `idx_conte_type` (`entry_type`),
  KEY `fk_conte_voucher` (`voucher_id`),
  KEY `idx_conte_project` (`project_id`),
  CONSTRAINT `fk_conte_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conte_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_conte_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_entries`
--

LOCK TABLES `contractor_entries` WRITE;
/*!40000 ALTER TABLE `contractor_entries` DISABLE KEYS */;
INSERT INTO `contractor_entries` VALUES (1,1,'CONE-0001','2026-08-15','payable',500000.00,'Foundation work',29,4,22,1,'2026-08-15','16:53:38','2026-08-15','16:53:38'),(2,1,'CONE-0002','2026-08-15','paid',200000.00,'Advance payment',NULL,4,23,1,'2026-08-15','16:53:39','2026-08-15','16:53:39'),(3,1,'CONE-0003','2026-08-15','paid',50000.00,'Site payment via cash paid',30,1,24,1,'2026-08-15','16:54:29','2026-08-15','16:54:29'),(4,1,'CONE-0004','2026-08-15','payable',100000.00,'Al Noor foundation',29,1,25,1,'2026-08-15','17:22:43','2026-08-15','17:22:43');
/*!40000 ALTER TABLE `contractor_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_projects`
--

DROP TABLE IF EXISTS `contractor_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_projects` (
  `contractor_id` int(10) unsigned NOT NULL,
  `project_id` int(10) unsigned NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  PRIMARY KEY (`contractor_id`,`project_id`),
  KEY `idx_cp_project` (`project_id`),
  CONSTRAINT `fk_cp_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_projects`
--

LOCK TABLES `contractor_projects` WRITE;
/*!40000 ALTER TABLE `contractor_projects` DISABLE KEYS */;
INSERT INTO `contractor_projects` VALUES (1,1,'2026-08-15','17:17:29'),(1,2,'2026-08-15','17:17:29'),(1,4,'2026-08-15','17:17:46');
/*!40000 ALTER TABLE `contractor_projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractors`
--

DROP TABLE IF EXISTS `contractors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contractor_no` varchar(40) NOT NULL,
  `full_name` varchar(180) NOT NULL,
  `company` varchar(180) DEFAULT NULL,
  `specialty` varchar(120) DEFAULT NULL,
  `cnic` varchar(40) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `whatsapp` varchar(40) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `bank_id` int(10) unsigned DEFAULT NULL,
  `bank_account_title` varchar(150) DEFAULT NULL,
  `bank_account_no` varchar(80) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_contractors_no` (`contractor_no`),
  KEY `idx_contractors_bank` (`bank_id`),
  CONSTRAINT `fk_contractors_bank` FOREIGN KEY (`bank_id`) REFERENCES `banks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractors`
--

LOCK TABLES `contractors` WRITE;
/*!40000 ALTER TABLE `contractors` DISABLE KEYS */;
INSERT INTO `contractors` VALUES (1,'CON-0001','Ali Masonry Works','','Masonry','','03001234567','','','',NULL,'','',1,'2026-08-15','16:53:23','2026-08-15','16:53:23');
/*!40000 ALTER TABLE `contractors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `code` varchar(5) DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_countries_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES (1,'Pakistan','PK','2026-07-31','08:14:11','2026-07-31','08:14:11');
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_no` varchar(40) NOT NULL,
  `full_name` varchar(180) NOT NULL,
  `cnic` varchar(40) DEFAULT NULL,
  `passport_no` varchar(60) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `whatsapp` varchar(40) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city_id` int(10) unsigned DEFAULT NULL,
  `nominee_name` varchar(180) DEFAULT NULL,
  `nominee_cnic` varchar(40) DEFAULT NULL,
  `nominee_relation` varchar(60) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance_type` enum('receivable','payable') NOT NULL DEFAULT 'receivable',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customers_no` (`customer_no`),
  KEY `idx_customers_city` (`city_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` (`id`, `customer_no`, `full_name`, `cnic`, `passport_no`, `phone`, `whatsapp`, `email`, `address`, `city_id`, `nominee_name`, `nominee_cnic`, `nominee_relation`, `photo`, `status`, `created_date`, `created_time`, `updated_date`, `updated_time`) VALUES (1,'CUST-0001','Muhammad Ali','35202-1234567-1',NULL,'03001234001','03001234001','muhammad.ali@gmail.com','House 12, Block G, Model Town, Lahore',2,'Sana Ali','35202-2234567-1','Wife',NULL,1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(2,'CUST-0002','Fatima Noor','35202-7654321-3',NULL,'03001234002','03001234002','fatima.noor@gmail.com','Flat 4B, Gulberg III, Lahore',2,'Omar Noor','35202-8654321-3','Brother',NULL,1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(3,'CUST-0003','Ahmed Hassan','35201-1112223-5',NULL,'03001234003',NULL,'ahmed.hassan@gmail.com','DHA Phase 6, Karachi',1,'Zoya Hassan','35201-2112223-5','Wife',NULL,1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(4,'CUST-0004','Sana Tariq','35202-3334445-7',NULL,'03001234004','03001234004','sana.tariq@gmail.com','House 5, Faisal Town, Lahore',2,'Tariq Mehmood','35202-4334445-7','Father',NULL,1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(5,'CUST-0005','Usman Ghani','35202-5556667-9',NULL,'03001234005','03001234005','usman.ghani@gmail.com','G-10/4, Islamabad',3,'Ghani Bakhsh','35202-6556667-9','Father',NULL,1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(6,'CUST-0006','Hira Shahid','35202-7778889-1',NULL,'03001234006',NULL,'hira.shahid@gmail.com','Westridge 2, Rawalpindi',4,'Shahid Malik','35202-8778889-1','Father',NULL,1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(7,'CUST-0007','Talha Arshad','36103-8619181-9','','03171821122','03211677422','talha353523@gmail.com','Khanewal',2,'Bilal','3','brother',NULL,1,'2026-08-05','13:12:16','2026-08-05','13:12:16');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dealer_ledger`
--

DROP TABLE IF EXISTS `dealer_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dealer_ledger` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dealer_id` int(10) unsigned NOT NULL,
  `entry_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `debit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dl_dealer` (`dealer_id`),
  CONSTRAINT `fk_dl_dealer` FOREIGN KEY (`dealer_id`) REFERENCES `dealers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dealer_ledger`
--

LOCK TABLES `dealer_ledger` WRITE;
/*!40000 ALTER TABLE `dealer_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `dealer_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dealer_payments`
--

DROP TABLE IF EXISTS `dealer_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dealer_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dealer_id` int(10) unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `payment_method_id` int(10) unsigned DEFAULT NULL,
  `bank_id` int(10) unsigned DEFAULT NULL,
  `reference` varchar(80) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dp_dealer` (`dealer_id`),
  CONSTRAINT `fk_dp_dealer` FOREIGN KEY (`dealer_id`) REFERENCES `dealers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dealer_payments`
--

LOCK TABLES `dealer_payments` WRITE;
/*!40000 ALTER TABLE `dealer_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `dealer_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dealers`
--

DROP TABLE IF EXISTS `dealers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dealers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dealer_no` varchar(40) NOT NULL,
  `full_name` varchar(180) NOT NULL,
  `cnic` varchar(40) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `whatsapp` varchar(40) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `dealer_type` enum('dealer','agent') NOT NULL DEFAULT 'dealer',
  `commission_rate` decimal(6,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dealers_no` (`dealer_no`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dealers`
--

LOCK TABLES `dealers` WRITE;
/*!40000 ALTER TABLE `dealers` DISABLE KEYS */;
INSERT INTO `dealers` VALUES (1,'DLR-0001','Property Link Associates','35201-1239876-5','03003001001','03003001001','info@propertylink.pk','Office 2, Liberty Market, Lahore','agent',2.50,1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(2,'DLR-0002','Rashid & Co Real Estate','35201-4563218-9','03003002002','03003002002','rashidco@gmail.com','Shop 8, Johar Town, Lahore','dealer',2.00,1,'2026-07-31','08:14:26','2026-07-31','08:14:26');
/*!40000 ALTER TABLE `dealers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_types`
--

DROP TABLE IF EXISTS `document_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_doctype_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_types`
--

LOCK TABLES `document_types` WRITE;
/*!40000 ALTER TABLE `document_types` DISABLE KEYS */;
INSERT INTO `document_types` VALUES (1,'CNIC','2026-07-31','08:14:12','2026-07-31','08:14:12'),(2,'Passport','2026-07-31','08:14:12','2026-07-31','08:14:12'),(3,'Booking Form','2026-07-31','08:14:12','2026-07-31','08:14:12'),(4,'Agreement','2026-07-31','08:14:12','2026-07-31','08:14:12'),(5,'Receipt','2026-07-31','08:14:12','2026-07-31','08:14:12'),(6,'Transfer Letter','2026-07-31','08:14:12','2026-07-31','08:14:12'),(7,'NOC','2026-07-31','08:14:12','2026-07-31','08:14:12'),(8,'Map / Layout','2026-07-31','08:14:12','2026-07-31','08:14:12');
/*!40000 ALTER TABLE `document_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `related_type` varchar(50) NOT NULL,
  `related_id` int(10) unsigned NOT NULL,
  `document_type_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `uploaded_by` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_doc_related` (`related_type`,`related_id`),
  KEY `idx_doc_type` (`document_type_id`),
  CONSTRAINT `fk_doc_type` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
INSERT INTO `documents` VALUES (1,'customer',23424,4,'ABC','uploads/documents/20260731_151429_f4da0b83.png','Okay',1,'2026-07-31','15:14:29','2026-07-31','15:14:29');
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_entries`
--

DROP TABLE IF EXISTS `employee_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `entry_no` varchar(40) NOT NULL,
  `entry_date` date NOT NULL,
  `entry_type` enum('payable','paid') NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `narration` varchar(255) DEFAULT NULL,
  `account_id` int(10) unsigned DEFAULT NULL,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_empe_no` (`entry_no`),
  KEY `idx_empe_employee` (`employee_id`),
  KEY `idx_empe_date` (`entry_date`),
  KEY `idx_empe_type` (`entry_type`),
  KEY `fk_empe_voucher` (`voucher_id`),
  CONSTRAINT `fk_empe_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_empe_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_entries`
--

LOCK TABLES `employee_entries` WRITE;
/*!40000 ALTER TABLE `employee_entries` DISABLE KEYS */;
INSERT INTO `employee_entries` VALUES (3,2,'EMPE-0001','2026-08-14','payable',50000.00,'Payable - ABC',8,10,1,'2026-08-14','18:28:17','2026-08-14','18:28:17');
/*!40000 ALTER TABLE `employee_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_no` varchar(40) NOT NULL,
  `full_name` varchar(180) NOT NULL,
  `father_name` varchar(180) DEFAULT NULL,
  `cnic` varchar(40) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `whatsapp` varchar(40) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `designation` varchar(120) DEFAULT NULL,
  `department` varchar(120) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `monthly_salary` decimal(14,2) NOT NULL DEFAULT 0.00,
  `bank_id` int(10) unsigned DEFAULT NULL,
  `bank_account_title` varchar(150) DEFAULT NULL,
  `bank_account_no` varchar(80) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employees_no` (`employee_no`),
  KEY `idx_employees_bank` (`bank_id`),
  CONSTRAINT `fk_employees_bank` FOREIGN KEY (`bank_id`) REFERENCES `banks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (2,'EMP-0001','ABC','XYZ','98765467890','965789','','talha353523@gmail.com','kacha khuh','','','2026-02-04',50000.00,NULL,'','',1,'2026-08-14','18:27:32','2026-08-14','18:27:32');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_expcat_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_categories`
--

LOCK TABLES `expense_categories` WRITE;
/*!40000 ALTER TABLE `expense_categories` DISABLE KEYS */;
INSERT INTO `expense_categories` VALUES (1,'Salaries','2026-07-31','08:14:12','2026-07-31','08:14:12'),(2,'Office Rent','2026-07-31','08:14:12','2026-07-31','08:14:12'),(3,'Utilities','2026-07-31','08:14:12','2026-07-31','08:14:12'),(4,'Marketing','2026-07-31','08:14:12','2026-07-31','08:14:12'),(5,'Transport','2026-07-31','08:14:12','2026-07-31','08:14:12'),(6,'Legal Fees','2026-07-31','08:14:12','2026-07-31','08:14:12'),(7,'Maintenance','2026-07-31','08:14:12','2026-07-31','08:14:12'),(8,'Miscellaneous','2026-07-31','08:14:12','2026-07-31','08:14:12');
/*!40000 ALTER TABLE `expense_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `general_parties`
--

DROP TABLE IF EXISTS `general_parties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `general_parties` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `party_no` varchar(40) NOT NULL,
  `party_name` varchar(180) NOT NULL,
  `contact_person` varchar(180) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `whatsapp` varchar(40) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gp_no` (`party_no`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `general_parties`
--

LOCK TABLES `general_parties` WRITE;
/*!40000 ALTER TABLE `general_parties` DISABLE KEYS */;
/*!40000 ALTER TABLE `general_parties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `general_party_entries`
--

DROP TABLE IF EXISTS `general_party_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `general_party_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `party_id` int(10) unsigned NOT NULL,
  `entry_no` varchar(40) NOT NULL,
  `entry_date` date NOT NULL,
  `entry_type` enum('payable','paid','receiving') NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `narration` varchar(255) DEFAULT NULL,
  `account_id` int(10) unsigned DEFAULT NULL,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gpe_no` (`entry_no`),
  KEY `idx_gpe_party` (`party_id`),
  KEY `idx_gpe_date` (`entry_date`),
  KEY `idx_gpe_type` (`entry_type`),
  KEY `fk_gpe_voucher` (`voucher_id`),
  CONSTRAINT `fk_gpe_party` FOREIGN KEY (`party_id`) REFERENCES `general_parties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gpe_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `general_party_entries`
--

LOCK TABLES `general_party_entries` WRITE;
/*!40000 ALTER TABLE `general_party_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `general_party_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `income_categories`
--

DROP TABLE IF EXISTS `income_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `income_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_incat_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `income_categories`
--

LOCK TABLES `income_categories` WRITE;
/*!40000 ALTER TABLE `income_categories` DISABLE KEYS */;
INSERT INTO `income_categories` VALUES (1,'Property Sales','2026-07-31','08:14:12','2026-07-31','08:14:12'),(2,'Rental Income','2026-07-31','08:14:12','2026-07-31','08:14:12'),(3,'Booking Fees','2026-07-31','08:14:12','2026-07-31','08:14:12'),(4,'Transfer Charges','2026-07-31','08:14:12','2026-07-31','08:14:12'),(5,'Service Charges','2026-07-31','08:14:12','2026-07-31','08:14:12'),(6,'Other Income','2026-07-31','08:14:12','2026-07-31','08:14:12');
/*!40000 ALTER TABLE `income_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `installments`
--

DROP TABLE IF EXISTS `installments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `installments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int(10) unsigned NOT NULL,
  `installment_no` int(11) NOT NULL,
  `installment_type` enum('booking','possession','balloting','installment') NOT NULL DEFAULT 'installment',
  `due_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `penalty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','partial','paid','overdue','waived') NOT NULL DEFAULT 'pending',
  `paid_date` date DEFAULT NULL,
  `received_by` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_inst_booking` (`booking_id`),
  KEY `idx_inst_status` (`status`),
  CONSTRAINT `fk_inst_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=319 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `installments`
--

LOCK TABLES `installments` WRITE;
/*!40000 ALTER TABLE `installments` DISABLE KEYS */;
INSERT INTO `installments` VALUES (1,1,1,'booking','2025-06-15',1000000.00,0.00,1000000.00,'paid','2025-06-15',1,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,1,2,'possession','2027-06-15',100000.00,0.00,100000.00,'paid','2026-08-07',1,'2026-07-31','08:14:27','2026-08-07','11:56:41'),(3,1,3,'installment','2025-09-15',1712500.00,0.00,1712500.00,'paid','2025-09-16',1,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(4,1,4,'installment','2025-12-15',1712500.00,0.00,1712500.00,'paid','2025-12-16',1,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(5,1,5,'installment','2026-03-15',1712500.00,0.00,1117500.00,'partial','2026-08-14',1,'2026-07-31','08:14:27','2026-08-14','18:47:51'),(6,1,6,'installment','2026-06-15',1712500.00,0.00,0.00,'overdue',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(7,1,7,'installment','2026-09-15',1712500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(8,1,8,'installment','2026-12-15',1712500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(9,1,9,'installment','2027-03-15',1712500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(10,1,10,'installment','2027-06-15',1712500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(11,2,1,'booking','2025-03-10',600000.00,0.00,600000.00,'paid','2025-03-10',1,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(12,2,2,'possession','2026-03-10',0.00,0.00,0.00,'paid','2026-03-10',1,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(13,2,3,'installment','2026-03-10',5900000.00,0.00,5900000.00,'paid','2026-03-10',1,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(14,3,1,'booking','2026-07-20',500000.00,0.00,500000.00,'paid','2026-08-08',1,'2026-07-31','08:14:27','2026-08-08','12:11:13'),(15,3,2,'possession','2027-07-20',50000.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(16,3,3,'installment','2026-08-20',656250.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(17,3,4,'installment','2026-09-20',656250.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(18,3,5,'installment','2026-10-20',656250.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(19,3,6,'installment','2026-11-20',656250.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(20,3,7,'installment','2026-12-20',656250.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(21,3,8,'installment','2027-01-20',656250.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(22,3,9,'installment','2027-02-20',656250.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(23,3,10,'installment','2027-03-20',656250.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(24,3,11,'installment','2027-04-20',656250.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(25,3,12,'installment','2027-05-20',656250.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(26,3,13,'installment','2027-06-20',656250.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(27,3,14,'installment','2027-07-20',656250.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(216,23,1,'booking','2026-08-14',11500000.00,0.00,11500000.00,'paid','2026-08-14',1,'2026-08-14','20:52:09','2026-08-14','20:52:09'),(217,24,1,'booking','2026-08-15',7200000.00,0.00,7200000.00,'paid','2026-08-15',1,'2026-08-15','14:57:10','2026-08-15','14:57:10'),(218,25,1,'booking','2026-08-15',2000000.00,0.00,2000000.00,'paid','2026-08-15',1,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(219,25,2,'installment','2026-09-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(220,25,3,'installment','2026-10-15',100000.00,0.00,200000.00,'paid','2026-08-15',1,'2026-08-15','15:00:36','2026-08-15','15:01:33'),(221,25,4,'installment','2026-11-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(222,25,5,'installment','2026-12-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(223,25,6,'installment','2027-01-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(224,25,7,'installment','2027-02-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(225,25,8,'installment','2027-03-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(226,25,9,'installment','2027-04-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(227,25,10,'installment','2027-05-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(228,25,11,'installment','2027-06-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(229,25,12,'installment','2027-07-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(230,25,13,'installment','2027-08-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(231,25,14,'installment','2027-09-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(232,25,15,'installment','2027-10-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(233,25,16,'installment','2027-11-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(234,25,17,'installment','2027-12-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(235,25,18,'installment','2028-01-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(236,25,19,'installment','2028-02-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(237,25,20,'installment','2028-03-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(238,25,21,'installment','2028-04-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(239,25,22,'installment','2028-05-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(240,25,23,'installment','2028-06-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(241,25,24,'installment','2028-07-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(242,25,25,'installment','2028-08-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(243,25,26,'installment','2028-09-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(244,25,27,'installment','2028-10-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(245,25,28,'installment','2028-11-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(246,25,29,'installment','2028-12-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(247,25,30,'installment','2029-01-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(248,25,31,'installment','2029-02-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(249,25,32,'installment','2029-03-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(250,25,33,'installment','2029-04-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(251,25,34,'installment','2029-05-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(252,25,35,'installment','2029-06-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(253,25,36,'installment','2029-07-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(254,25,37,'installment','2029-08-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(255,25,38,'installment','2029-09-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(256,25,39,'installment','2029-10-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(257,25,40,'installment','2029-11-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(258,25,41,'installment','2029-12-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(259,25,42,'installment','2030-01-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(260,25,43,'installment','2030-02-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(261,25,44,'installment','2030-03-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(262,25,45,'installment','2030-04-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(263,25,46,'installment','2030-05-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(264,25,47,'installment','2030-06-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(265,25,48,'installment','2030-07-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(266,25,49,'installment','2030-08-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(267,25,50,'installment','2030-09-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(268,25,51,'installment','2030-10-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(269,25,52,'installment','2030-11-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(270,25,53,'installment','2030-12-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(271,25,54,'installment','2031-01-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(272,25,55,'installment','2031-02-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(273,25,56,'installment','2031-03-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(274,25,57,'installment','2031-04-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(275,25,58,'installment','2031-05-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(276,25,59,'installment','2031-06-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(277,25,60,'installment','2031-07-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(278,25,61,'installment','2031-08-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(279,25,62,'installment','2031-09-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(280,25,63,'installment','2031-10-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(281,25,64,'installment','2031-11-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(282,25,65,'installment','2031-12-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(283,25,66,'installment','2032-01-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(284,25,67,'installment','2032-02-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(285,25,68,'installment','2032-03-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(286,25,69,'installment','2032-04-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(287,25,70,'installment','2032-05-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(288,25,71,'installment','2032-06-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(289,25,72,'installment','2032-07-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(290,25,73,'installment','2032-08-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(291,25,74,'installment','2032-09-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(292,25,75,'installment','2032-10-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(293,25,76,'installment','2032-11-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(294,25,77,'installment','2032-12-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(295,25,78,'installment','2033-01-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(296,25,79,'installment','2033-02-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(297,25,80,'installment','2033-03-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(298,25,81,'installment','2033-04-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(299,25,82,'installment','2033-05-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(300,25,83,'installment','2033-06-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(301,25,84,'installment','2033-07-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(302,25,85,'installment','2033-08-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(303,25,86,'installment','2033-09-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(304,25,87,'installment','2033-10-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(305,25,88,'installment','2033-11-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(306,25,89,'installment','2033-12-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(307,25,90,'installment','2034-01-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(308,25,91,'installment','2034-02-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(309,25,92,'installment','2034-03-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(310,25,93,'installment','2034-04-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(311,25,94,'installment','2034-05-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(312,25,95,'installment','2034-06-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(313,25,96,'installment','2034-07-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(314,25,97,'installment','2034-08-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(315,25,98,'installment','2034-09-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(316,25,99,'installment','2034-10-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(317,25,100,'installment','2034-11-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37'),(318,25,101,'installment','2034-12-15',100000.00,0.00,0.00,'pending',NULL,NULL,'2026-08-15','15:00:37','2026-08-15','15:00:37');
/*!40000 ALTER TABLE `installments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `investor_ledger`
--

DROP TABLE IF EXISTS `investor_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investor_ledger` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `investor_id` int(10) unsigned NOT NULL,
  `entry_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `debit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_il_investor` (`investor_id`),
  CONSTRAINT `fk_il_investor` FOREIGN KEY (`investor_id`) REFERENCES `investors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `investor_ledger`
--

LOCK TABLES `investor_ledger` WRITE;
/*!40000 ALTER TABLE `investor_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `investor_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `investors`
--

DROP TABLE IF EXISTS `investors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `investor_no` varchar(40) NOT NULL,
  `full_name` varchar(180) NOT NULL,
  `cnic` varchar(40) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `whatsapp` varchar(40) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `bank_account_title` varchar(120) DEFAULT NULL,
  `bank_account_no` varchar(60) DEFAULT NULL,
  `investment_type` varchar(60) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_investors_no` (`investor_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `investors`
--

LOCK TABLES `investors` WRITE;
/*!40000 ALTER TABLE `investors` DISABLE KEYS */;
/*!40000 ALTER TABLE `investors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_followups`
--

DROP TABLE IF EXISTS `lead_followups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_followups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` int(10) unsigned NOT NULL,
  `followup_date` date NOT NULL,
  `note` varchar(500) DEFAULT NULL,
  `next_follow_up` date DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lf_lead` (`lead_id`),
  CONSTRAINT `fk_lf_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_followups`
--

LOCK TABLES `lead_followups` WRITE;
/*!40000 ALTER TABLE `lead_followups` DISABLE KEYS */;
INSERT INTO `lead_followups` VALUES (1,1,'2026-07-20','Shared project brochure, customer interested in Block A','2026-08-05','2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,2,'2026-07-22','Site visit scheduled for Tower 1 apartment','2026-08-02','2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,4,'2026-05-10','Customer confirmed booking for PRP-0009',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27');
/*!40000 ALTER TABLE `lead_followups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_no` varchar(40) NOT NULL,
  `name` varchar(180) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `whatsapp` varchar(40) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `source` enum('facebook','website','whatsapp','walk_in','referral','other') NOT NULL DEFAULT 'other',
  `property_type_id` int(10) unsigned DEFAULT NULL,
  `project_id` int(10) unsigned DEFAULT NULL,
  `budget` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('new','contacted','qualified','proposal','follow_up','converted','lost') NOT NULL DEFAULT 'new',
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `next_follow_up` date DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lead_no` (`lead_no`),
  KEY `idx_leads_status` (`status`),
  KEY `fk_leads_assigned` (`assigned_to`),
  CONSTRAINT `fk_leads_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES (1,'LD-0001','Taimoor Sheikh','03006666001','03006666001','taimoor.sheikh@gmail.com','facebook',1,1,12000000.00,'follow_up',2,'2026-08-05','Interested in Block A plots','2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,'LD-0002','Rabia Saleem','03006666002','03006666002','rabia.saleem@gmail.com','whatsapp',2,2,10000000.00,'qualified',4,'2026-08-02','Site visit scheduled for Tower 1','2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,'LD-0003','Faisal Mirza','03006666003',NULL,'faisal.mirza@gmail.com','website',4,3,20000000.00,'new',2,'2026-08-01','Wants a main boulevard shop','2026-07-31','08:14:27','2026-07-31','08:14:27'),(4,'LD-0004','Asma Iqbal','03006666004','03006666004','asma.iqbal@gmail.com','referral',1,1,7000000.00,'converted',4,NULL,'Converted - purchased PRP-0009','2026-07-31','08:14:27','2026-07-31','08:14:27'),(5,'LD-0005','Kamran Javed','03006666005',NULL,NULL,'walk_in',3,2,9500000.00,'lost',2,NULL,'Budget too low, deal lost','2026-07-31','08:14:27','2026-07-31','08:14:27');
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_complaints`
--

DROP TABLE IF EXISTS `maintenance_complaints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_complaints` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `complaint_no` varchar(40) NOT NULL,
  `property_id` int(10) unsigned DEFAULT NULL,
  `tenant_id` int(10) unsigned DEFAULT NULL,
  `category` enum('electric','plumbing','painting','structural','cleaning','other') NOT NULL DEFAULT 'other',
  `description` text DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('open','in_progress','completed','cancelled') NOT NULL DEFAULT 'open',
  `reported_by` varchar(150) DEFAULT NULL,
  `reported_date` date NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_complaint_no` (`complaint_no`),
  KEY `fk_mc_property` (`property_id`),
  CONSTRAINT `fk_mc_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_complaints`
--

LOCK TABLES `maintenance_complaints` WRITE;
/*!40000 ALTER TABLE `maintenance_complaints` DISABLE KEYS */;
INSERT INTO `maintenance_complaints` VALUES (1,'MC-0001',5,1,'electric','Power keeps tripping in apartment 3-B','urgent','in_progress','Adnan Yousaf','2026-07-15','2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,'MC-0002',8,2,'plumbing','Leaking pipe in bathroom','medium','completed','Shahzad Riaz','2026-06-20','2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,'MC-0003',11,3,'painting','Wall paint peeling in bedroom','low','open','Mariam Farooq','2026-07-25','2026-07-31','08:14:27','2026-07-31','08:14:27');
/*!40000 ALTER TABLE `maintenance_complaints` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_tasks`
--

DROP TABLE IF EXISTS `maintenance_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_tasks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int(10) unsigned NOT NULL,
  `technician_id` int(10) unsigned DEFAULT NULL,
  `task_description` varchar(255) DEFAULT NULL,
  `cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `completion_date` date DEFAULT NULL,
  `photos` varchar(255) DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mt_complaint` (`complaint_id`),
  CONSTRAINT `fk_mt_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `maintenance_complaints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_tasks`
--

LOCK TABLES `maintenance_tasks` WRITE;
/*!40000 ALTER TABLE `maintenance_tasks` DISABLE KEYS */;
INSERT INTO `maintenance_tasks` VALUES (1,1,1,'Check main wiring and replace circuit breaker',5000.00,'2026-07-18',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,2,2,'Replace bathroom pipe joints',3500.00,'2026-06-25',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27');
/*!40000 ALTER TABLE `maintenance_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meetings`
--

DROP TABLE IF EXISTS `meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meetings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` int(10) unsigned DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `meeting_date` datetime NOT NULL,
  `location` varchar(180) DEFAULT NULL,
  `note` varchar(500) DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_meet_lead` (`lead_id`),
  CONSTRAINT `fk_meet_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meetings`
--

LOCK TABLES `meetings` WRITE;
/*!40000 ALTER TABLE `meetings` DISABLE KEYS */;
INSERT INTO `meetings` VALUES (1,1,NULL,'2026-07-25 11:00:00','DHA Phase 5 office','Site visit Block A plots','completed','2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,2,NULL,'2026-07-28 16:00:00','Green Valley site office','Apartment tour Tower 1','scheduled','2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,NULL,1,'2026-06-15 12:00:00','Head Office','Finalized quotation for Plot 10-B','completed','2026-07-31','08:14:27','2026-07-31','08:14:27');
/*!40000 ALTER TABLE `meetings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meter_readings`
--

DROP TABLE IF EXISTS `meter_readings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meter_readings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `utility_id` int(10) unsigned NOT NULL,
  `reading_date` date NOT NULL,
  `previous_reading` decimal(12,2) NOT NULL DEFAULT 0.00,
  `current_reading` decimal(12,2) NOT NULL DEFAULT 0.00,
  `units` decimal(12,2) NOT NULL DEFAULT 0.00,
  `rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mr_utility` (`utility_id`),
  CONSTRAINT `fk_mr_utility` FOREIGN KEY (`utility_id`) REFERENCES `utilities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meter_readings`
--

LOCK TABLES `meter_readings` WRITE;
/*!40000 ALTER TABLE `meter_readings` DISABLE KEYS */;
INSERT INTO `meter_readings` VALUES (1,1,'2026-06-01',2400.00,2650.00,250.00,28.00,7000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,1,'2026-07-01',2650.00,2910.00,260.00,28.00,7280.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,3,'2026-06-01',1100.00,1320.00,220.00,30.00,6600.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(4,3,'2026-07-01',1320.00,1550.00,230.00,30.00,6900.00,'2026-07-31','08:14:27','2026-07-31','08:14:27');
/*!40000 ALTER TABLE `meter_readings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `notification_type` enum('installment','rent','agreement','lead','general') NOT NULL DEFAULT 'general',
  `channel` enum('sms','whatsapp','email','system') NOT NULL DEFAULT 'system',
  `title` varchar(180) NOT NULL,
  `message` text DEFAULT NULL,
  `recipient_type` varchar(40) DEFAULT NULL,
  `recipient_id` int(10) unsigned DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'rent','system','Rent Due','Rent for PRP-0011 (RA-0003) due on 2026-08-01 - Amount: 95000.00','agreement',3,'2026-08-01','sent','2026-07-31','15:16:50','2026-07-31','15:16:59'),(2,'rent','system','Rent Due','Rent for PRP-0008 (RA-0002) due on 2026-08-01 - Amount: 85000.00','agreement',2,'2026-08-01','sent','2026-07-31','15:16:50','2026-07-31','15:16:59'),(3,'rent','system','Rent Due','Rent for PRP-0005 (RA-0001) due on 2026-08-01 - Amount: 60000.00','agreement',1,'2026-08-01','sent','2026-07-31','15:16:50','2026-07-31','15:16:59'),(4,'lead','system','Lead Follow Up','Lead LD-0003 (Faisal Mirza) needs follow up by 2026-08-01','lead',3,'2026-08-01','sent','2026-07-31','15:16:50','2026-07-31','15:16:59'),(5,'lead','system','Lead Follow Up','Lead LD-0002 (Rabia Saleem) needs follow up by 2026-08-02','lead',2,'2026-08-02','sent','2026-07-31','15:16:50','2026-07-31','15:16:59'),(6,'lead','system','Lead Follow Up','Lead LD-0001 (Taimoor Sheikh) needs follow up by 2026-08-05','lead',1,'2026-08-05','sent','2026-07-31','15:16:50','2026-07-31','15:16:59');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `owner_ledger`
--

DROP TABLE IF EXISTS `owner_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `owner_ledger` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int(10) unsigned NOT NULL,
  `entry_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `debit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ol_owner` (`owner_id`),
  CONSTRAINT `fk_ol_owner` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `owner_ledger`
--

LOCK TABLES `owner_ledger` WRITE;
/*!40000 ALTER TABLE `owner_ledger` DISABLE KEYS */;
INSERT INTO `owner_ledger` (`id`, `owner_id`, `entry_date`, `description`, `debit`, `credit`, `balance`, `created_date`, `created_time`, `updated_date`, `updated_time`) VALUES (1,3,'2026-07-05','Rent settlement RA-0001 June 2026',0.00,360000.00,360000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,2,'2026-06-30','Rent settlement RA-0002 June 2026 (pending)',0.00,335000.00,335000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,2,'2026-07-31','Owner settlement',0.00,335000.00,670000.00,'2026-07-31','14:52:49','2026-07-31','14:52:49');
/*!40000 ALTER TABLE `owner_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `owner_settlements`
--

DROP TABLE IF EXISTS `owner_settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `owner_settlements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int(10) unsigned NOT NULL,
  `agreement_id` int(10) unsigned DEFAULT NULL,
  `settlement_date` date NOT NULL,
  `rent_income` decimal(14,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(14,2) NOT NULL DEFAULT 0.00,
  `settlement_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','paid') NOT NULL DEFAULT 'pending',
  `payment_method_id` int(10) unsigned DEFAULT NULL,
  `bank_id` int(10) unsigned DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_os_owner` (`owner_id`),
  CONSTRAINT `fk_os_owner` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `owner_settlements`
--

LOCK TABLES `owner_settlements` WRITE;
/*!40000 ALTER TABLE `owner_settlements` DISABLE KEYS */;
INSERT INTO `owner_settlements` (`id`, `owner_id`, `agreement_id`, `settlement_date`, `rent_income`, `deductions`, `settlement_amount`, `status`, `payment_method_id`, `bank_id`, `remarks`, `created_date`, `created_time`, `updated_date`, `updated_time`) VALUES (1,3,1,'2026-06-30',360000.00,0.00,360000.00,'paid',1,NULL,'June 2026 settlement RA-0001','2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,2,2,'2026-06-30',340000.00,5000.00,335000.00,'paid',NULL,NULL,'June 2026 settlement RA-0002 (5% management fee)','2026-07-31','08:14:27','2026-07-31','14:52:49');
/*!40000 ALTER TABLE `owner_settlements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `owners`
--

DROP TABLE IF EXISTS `owners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `owners` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `owner_no` varchar(40) NOT NULL,
  `full_name` varchar(180) NOT NULL,
  `cnic` varchar(40) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `whatsapp` varchar(40) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `bank_id` int(10) unsigned DEFAULT NULL,
  `bank_account_title` varchar(150) DEFAULT NULL,
  `bank_account_no` varchar(80) DEFAULT NULL,
  `commission_rate` decimal(6,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_owners_no` (`owner_no`),
  KEY `fk_owners_bank` (`bank_id`),
  CONSTRAINT `fk_owners_bank` FOREIGN KEY (`bank_id`) REFERENCES `banks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `owners`
--

LOCK TABLES `owners` WRITE;
/*!40000 ALTER TABLE `owners` DISABLE KEYS */;
INSERT INTO `owners` VALUES (1,'OWN-0001','Khalid Mahmood','35201-9990001-3','03002001001','03002001001','khalid.mahmood@gmail.com','House 3, Shadman, Lahore',1,'Khalid Mahmood','0102030405',0.00,1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(2,'OWN-0002','Naeem Akhtar','35201-2220003-5','03002002002','03002002002','naeem.akhtar@gmail.com','Street 4, Iqbal Town, Lahore',2,'Naeem Akhtar','0203040506',0.00,1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(3,'OWN-0003','Zahid Mehmood','35201-4440005-7','03002003003',NULL,'zahid.mehmood@gmail.com','Gulshan-e-Ravi, Lahore',3,'Zahid Mehmood','0304050607',0.00,1,'2026-07-31','08:14:26','2026-07-31','08:14:26');
/*!40000 ALTER TABLE `owners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_methods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_paymethod_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES (1,'Cash','2026-07-31','08:14:12','2026-07-31','08:14:12'),(2,'Bank Transfer','2026-07-31','08:14:12','2026-07-31','08:14:12'),(3,'Cheque','2026-07-31','08:14:12','2026-07-31','08:14:12'),(4,'Online Payment','2026-07-31','08:14:12','2026-07-31','08:14:12'),(5,'Credit / Debit Card','2026-07-31','08:14:12','2026-07-31','08:14:12');
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(80) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'Dashboard','dashboard.view','View Dashboard','2026-07-31','08:14:11','2026-07-31','08:14:11'),(2,'Master','master.view','View Master Data','2026-07-31','08:14:11','2026-07-31','08:14:11'),(3,'Projects','projects.view','View Projects','2026-07-31','08:14:11','2026-07-31','08:14:11'),(4,'Projects','projects.manage','Manage Projects','2026-07-31','08:14:11','2026-07-31','08:14:11'),(5,'Inventory','properties.view','View Properties','2026-07-31','08:14:11','2026-07-31','08:14:11'),(6,'Inventory','properties.manage','Manage Properties','2026-07-31','08:14:11','2026-07-31','08:14:11'),(7,'Customers','customers.view','View Customers','2026-07-31','08:14:11','2026-07-31','08:14:11'),(8,'Customers','customers.manage','Manage Customers','2026-07-31','08:14:11','2026-07-31','08:14:11'),(9,'Owners','owners.view','View Owners','2026-07-31','08:14:11','2026-07-31','08:14:11'),(10,'Owners','owners.manage','Manage Owners','2026-07-31','08:14:11','2026-07-31','08:14:11'),(11,'Dealers','dealers.view','View Dealers','2026-07-31','08:14:11','2026-07-31','08:14:11'),(12,'Dealers','dealers.manage','Manage Dealers','2026-07-31','08:14:11','2026-07-31','08:14:11'),(13,'Sales','sales.view','View Sales','2026-07-31','08:14:11','2026-07-31','08:14:11'),(14,'Sales','sales.manage','Manage Sales','2026-07-31','08:14:11','2026-07-31','08:14:11'),(15,'Rentals','rentals.view','View Rentals','2026-07-31','08:14:11','2026-07-31','08:14:11'),(16,'Rentals','rentals.manage','Manage Rentals','2026-07-31','08:14:11','2026-07-31','08:14:11'),(17,'Tenants','tenants.view','View Tenants','2026-07-31','08:14:11','2026-07-31','08:14:11'),(18,'Tenants','tenants.manage','Manage Tenants','2026-07-31','08:14:11','2026-07-31','08:14:11'),(19,'Utilities','utilities.view','View Utilities','2026-07-31','08:14:11','2026-07-31','08:14:11'),(20,'Utilities','utilities.manage','Manage Utilities','2026-07-31','08:14:11','2026-07-31','08:14:11'),(21,'Maintenance','maintenance.view','View Maintenance','2026-07-31','08:14:11','2026-07-31','08:14:11'),(22,'Maintenance','maintenance.manage','Manage Maintenance','2026-07-31','08:14:11','2026-07-31','08:14:11'),(23,'Accounting','accounting.view','View Accounting','2026-07-31','08:14:11','2026-07-31','08:14:11'),(24,'Accounting','accounting.manage','Manage Accounting','2026-07-31','08:14:11','2026-07-31','08:14:11'),(25,'CRM','crm.view','View CRM','2026-07-31','08:14:11','2026-07-31','08:14:11'),(26,'CRM','crm.manage','Manage CRM','2026-07-31','08:14:11','2026-07-31','08:14:11'),(27,'Documents','documents.view','View Documents','2026-07-31','08:14:11','2026-07-31','08:14:11'),(28,'Reports','reports.view','View Reports','2026-07-31','08:14:11','2026-07-31','08:14:11'),(29,'Settings','settings.manage','Manage Settings','2026-07-31','08:14:11','2026-07-31','08:14:11'),(30,'Notifications','notifications.view','View Notifications','2026-07-31','08:14:11','2026-07-31','08:14:11'),(31,'Vendors','vendors.view','View Vendors','2026-08-08','01:42:00','2026-08-08','01:42:00'),(32,'Vendors','vendors.manage','Manage Vendors','2026-08-08','01:42:00','2026-08-08','01:42:00'),(33,'General Parties','general_parties.view','View General Parties','2026-08-08','10:21:59','2026-08-08','10:21:59'),(34,'General Parties','general_parties.manage','Manage General Parties','2026-08-08','10:21:59','2026-08-08','10:21:59'),(35,'Employees','employees.view','View Employees','2026-08-13','09:11:59','2026-08-13','09:11:59'),(36,'Employees','employees.manage','Manage Employees','2026-08-13','09:11:59','2026-08-13','09:11:59'),(37,'Contractors','contractors.view','View Contractors','2026-08-15','16:48:31','2026-08-15','16:48:31'),(38,'Contractors','contractors.manage','Manage Contractors','2026-08-15','16:48:31','2026-08-15','16:48:31'),(39,'Purchases','purchases.view','View Purchases','2026-08-17','15:56:41','2026-08-17','15:56:41'),(40,'Purchases','purchases.manage','Manage Purchases','2026-08-17','15:56:41','2026-08-17','15:56:41'),(41,'Investors','investors.view','View Investors','2026-08-17','16:51:43','2026-08-17','16:51:43'),(42,'Investors','investors.manage','Manage Investors','2026-08-17','16:51:43','2026-08-17','16:51:43'),(43,'Products','products.view','View Products','2026-08-19','00:00:00','2026-08-19','00:00:00'),(44,'Products','products.manage','Manage Products','2026-08-19','00:00:00','2026-08-19','00:00:00'),(45,'Inventory','inventory.view','View Inventory','2026-08-19','00:00:00','2026-08-19','00:00:00'),(46,'Inventory','inventory.manage','Manage Inventory','2026-08-19','00:00:00','2026-08-19','00:00:00');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_documents`
--

DROP TABLE IF EXISTS `project_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(10) unsigned NOT NULL,
  `title` varchar(180) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pdoc_project` (`project_id`),
  CONSTRAINT `fk_pdoc_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_documents`
--

LOCK TABLES `project_documents` WRITE;
/*!40000 ALTER TABLE `project_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_images`
--

DROP TABLE IF EXISTS `project_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(10) unsigned NOT NULL,
  `image_file` varchar(255) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pimg_project` (`project_id`),
  CONSTRAINT `fk_pimg_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_images`
--

LOCK TABLES `project_images` WRITE;
/*!40000 ALTER TABLE `project_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(180) NOT NULL,
  `developer` varchar(150) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `country_id` int(10) unsigned DEFAULT NULL,
  `city_id` int(10) unsigned DEFAULT NULL,
  `area_id` int(10) unsigned DEFAULT NULL,
  `society_id` int(10) unsigned DEFAULT NULL,
  `noc` varchar(80) DEFAULT NULL,
  `noc_file` varchar(255) DEFAULT NULL,
  `map_file` varchar(255) DEFAULT NULL,
  `master_plan_file` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_projects_name` (`name`),
  KEY `fk_projects_city` (`city_id`),
  CONSTRAINT `fk_projects_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (1,'Al-Noor Residency','Al-Noor Developers','DHA Phase 5, Lahore',1,2,NULL,1,'NOC-2024-001',NULL,NULL,NULL,'Residential housing scheme with plots and houses',1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(2,'Green Valley Apartments','Green Valley Builders','Bahria Town, Lahore',1,2,NULL,2,'NOC-2024-014',NULL,NULL,NULL,'Premium apartment towers',1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(3,'Skyline Commercial Plaza','Skyline Group','Main Boulevard, Gulberg III, Lahore',1,2,NULL,2,'NOC-2025-006',NULL,NULL,NULL,'Commercial shops on main boulevard',1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(4,'ABC Developers','ABC','Multan',1,7,NULL,5,'98756456789',NULL,NULL,NULL,'test',1,'2026-08-05','12:52:38','2026-08-05','12:52:38');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `properties`
--

DROP TABLE IF EXISTS `properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `properties` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `property_no` varchar(40) NOT NULL,
  `file_no` varchar(60) DEFAULT NULL,
  `plot_no` varchar(60) DEFAULT NULL,
  `house_no` varchar(60) DEFAULT NULL,
  `apartment_no` varchar(60) DEFAULT NULL,
  `office_no` varchar(60) DEFAULT NULL,
  `shop_no` varchar(60) DEFAULT NULL,
  `warehouse_no` varchar(60) DEFAULT NULL,
  `factory_no` varchar(60) DEFAULT NULL,
  `farm_house_no` varchar(60) DEFAULT NULL,
  `hall_no` varchar(60) DEFAULT NULL,
  `project_id` int(10) unsigned DEFAULT NULL,
  `block_id` int(10) unsigned DEFAULT NULL,
  `road_id` int(10) unsigned DEFAULT NULL,
  `street_id` int(10) unsigned DEFAULT NULL,
  `property_type_id` int(10) unsigned DEFAULT NULL,
  `property_category_id` int(10) unsigned DEFAULT NULL,
  `owner_id` int(10) unsigned DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `size_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `size_unit` enum('marla','kanal','sqft','sqy') NOT NULL DEFAULT 'marla',
  `status` enum('available','booked','reserved','sold','transferred','rental','occupied','vacant') NOT NULL DEFAULT 'available',
  `corner` tinyint(1) NOT NULL DEFAULT 0,
  `main_boulevard` tinyint(1) NOT NULL DEFAULT 0,
  `park_facing` tinyint(1) NOT NULL DEFAULT 0,
  `sale_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `rent_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `extra_charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `possession_status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `possession_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_properties_no` (`property_no`),
  KEY `idx_prop_project` (`project_id`),
  KEY `idx_prop_type` (`property_type_id`),
  KEY `idx_prop_status` (`status`),
  KEY `fk_prop_block` (`block_id`),
  KEY `fk_prop_cat` (`property_category_id`),
  KEY `fk_prop_owner` (`owner_id`),
  KEY `fk_prop_customer` (`customer_id`),
  CONSTRAINT `fk_prop_block` FOREIGN KEY (`block_id`) REFERENCES `blocks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_prop_cat` FOREIGN KEY (`property_category_id`) REFERENCES `property_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_prop_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_prop_owner` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_prop_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_prop_type` FOREIGN KEY (`property_type_id`) REFERENCES `property_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `properties`
--

LOCK TABLES `properties` WRITE;
/*!40000 ALTER TABLE `properties` DISABLE KEYS */;
INSERT INTO `properties` VALUES (1,'PRP-0001','F-1001','Plot 5-A',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,1,1,1,1,1,5,5.00,'marla','sold',0,0,0,6500000.00,0.00,0.00,'completed','2026-04-10','Corner plot near park in Block A','2026-07-31','08:14:26','2026-07-31','08:14:26'),(2,'PRP-0002','F-1002','Plot 10-B',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,1,2,1,1,1,NULL,10.00,'marla','sold',0,0,0,11500000.00,0.00,0.00,'pending',NULL,'Facing 40 ft road','2026-07-31','08:14:26','2026-08-14','20:52:09'),(3,'PRP-0003','F-1003',NULL,'House 1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,2,2,3,2,1,2,NULL,1.00,'kanal','booked',0,0,1,15000000.00,0.00,0.00,'pending',NULL,'Double storey house, park facing','2026-07-31','08:14:26','2026-07-31','08:14:26'),(4,'PRP-0004','F-1004','Plot 5-C',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,2,2,3,1,1,3,NULL,5.00,'marla','sold',0,0,0,7200000.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:26','2026-08-15','14:57:10'),(5,'PRP-0005','F-2001',NULL,NULL,'Apt 3-B',NULL,NULL,NULL,NULL,NULL,NULL,2,5,3,NULL,3,1,3,NULL,3.00,'marla','rental',0,0,0,9500000.00,60000.00,0.00,'in_progress',NULL,'2 bed apartment in Tower 1','2026-07-31','08:14:26','2026-07-31','08:14:26'),(6,'PRP-0006','F-1005','Plot 10-D',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,3,2,NULL,1,1,1,NULL,10.00,'marla','booked',0,0,1,12000000.00,0.00,0.00,'pending',NULL,'Park facing corner in Block C','2026-07-31','08:14:26','2026-08-15','15:00:37'),(7,'PRP-0007','F-3001',NULL,NULL,NULL,NULL,'Shop 12',NULL,NULL,NULL,NULL,3,7,4,5,4,2,2,NULL,2.00,'marla','available',0,1,0,18000000.00,120000.00,0.00,'pending',NULL,'Main boulevard commercial shop','2026-07-31','08:14:26','2026-08-14','20:32:09'),(8,'PRP-0008','F-2002',NULL,'House 8',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,6,3,NULL,2,1,2,NULL,5.00,'marla','rental',0,0,0,11000000.00,85000.00,0.00,'completed','2026-01-15',NULL,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(9,'PRP-0009','F-1006','Plot 5-E',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,3,2,NULL,1,1,1,4,5.00,'marla','sold',0,0,0,7000000.00,0.00,0.00,'completed','2026-01-20',NULL,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(10,'PRP-0010','F-1007','Plot 8-F',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,1,2,1,1,3,NULL,8.00,'marla','booked',0,0,0,8500000.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(11,'PRP-0011','F-2003',NULL,'House 15',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,6,3,NULL,2,1,1,NULL,5.00,'marla','rental',0,0,0,12500000.00,95000.00,0.00,'in_progress',NULL,NULL,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(12,'PRP-9.2233720368548E+18','','','','','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'marla','available',0,0,0,0.00,0.00,0.00,'pending',NULL,'','2026-07-31','14:35:57','2026-07-31','14:35:57');
/*!40000 ALTER TABLE `properties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_amenities`
--

DROP TABLE IF EXISTS `property_amenities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_amenities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` int(10) unsigned NOT NULL,
  `amenity_id` int(10) unsigned NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prop_amenity` (`property_id`,`amenity_id`),
  KEY `fk_pa_amenity` (`amenity_id`),
  CONSTRAINT `fk_pa_amenity` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pa_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_amenities`
--

LOCK TABLES `property_amenities` WRITE;
/*!40000 ALTER TABLE `property_amenities` DISABLE KEYS */;
INSERT INTO `property_amenities` VALUES (1,1,1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(2,1,3,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(3,1,6,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(4,2,1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(5,2,5,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(6,3,1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(7,3,2,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(8,3,7,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(9,5,1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(10,5,7,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(11,5,9,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(12,7,11,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(13,7,1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(14,8,1,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(15,8,2,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(16,11,3,'2026-07-31','08:14:26','2026-07-31','08:14:26');
/*!40000 ALTER TABLE `property_amenities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_categories`
--

DROP TABLE IF EXISTS `property_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pcat_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_categories`
--

LOCK TABLES `property_categories` WRITE;
/*!40000 ALTER TABLE `property_categories` DISABLE KEYS */;
INSERT INTO `property_categories` VALUES (1,'Residential','2026-07-31','08:14:12','2026-07-31','08:14:12'),(2,'Commercial','2026-07-31','08:14:12','2026-07-31','08:14:12'),(3,'Agricultural','2026-07-31','08:14:12','2026-07-31','08:14:12'),(4,'Mixed Use','2026-07-31','08:14:12','2026-07-31','08:14:12');
/*!40000 ALTER TABLE `property_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_documents`
--

DROP TABLE IF EXISTS `property_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` int(10) unsigned NOT NULL,
  `title` varchar(180) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_prd_property` (`property_id`),
  CONSTRAINT `fk_prd_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_documents`
--

LOCK TABLES `property_documents` WRITE;
/*!40000 ALTER TABLE `property_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `property_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_images`
--

DROP TABLE IF EXISTS `property_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` int(10) unsigned NOT NULL,
  `image_file` varchar(255) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pri_property` (`property_id`),
  CONSTRAINT `fk_pri_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_images`
--

LOCK TABLES `property_images` WRITE;
/*!40000 ALTER TABLE `property_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `property_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_types`
--

DROP TABLE IF EXISTS `property_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ptype_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_types`
--

LOCK TABLES `property_types` WRITE;
/*!40000 ALTER TABLE `property_types` DISABLE KEYS */;
INSERT INTO `property_types` VALUES (1,'Plot','2026-07-31','08:14:11','2026-07-31','08:14:11'),(2,'House','2026-07-31','08:14:11','2026-07-31','08:14:11'),(3,'Apartment','2026-07-31','08:14:11','2026-07-31','08:14:11'),(4,'Shop','2026-07-31','08:14:11','2026-07-31','08:14:11'),(5,'Office','2026-07-31','08:14:11','2026-07-31','08:14:11'),(6,'Warehouse','2026-07-31','08:14:11','2026-07-31','08:14:11'),(7,'Factory','2026-07-31','08:14:11','2026-07-31','08:14:11'),(8,'Farm House','2026-07-31','08:14:11','2026-07-31','08:14:11'),(9,'Commercial Hall','2026-07-31','08:14:11','2026-07-31','08:14:11');
/*!40000 ALTER TABLE `property_types` ENABLE KEYS */;
UNLOCK TABLES;

--

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_no` varchar(40) NOT NULL,
  `name` varchar(180) NOT NULL,
  `category` varchar(120) DEFAULT NULL,
  `unit` varchar(40) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `stock_qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `avg_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_no` (`product_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

-- Table structure for table `purchase_items`
--

DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `expense_account_id` int(10) unsigned DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pi_purchase` (`purchase_id`),
  KEY `fk_pi_account` (`expense_account_id`),
  CONSTRAINT `fk_pi_account` FOREIGN KEY (`expense_account_id`) REFERENCES `chart_of_accounts` (`id`),
  CONSTRAINT `fk_pi_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_items`
--

LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
INSERT INTO `purchase_items` (`id`, `purchase_id`, `product_id`, `description`, `quantity`, `unit_price`, `amount`, `created_date`, `created_time`, `updated_date`, `updated_time`) VALUES (1,1,NULL,'CEMENT',100.00,100.00,10000.00,'2026-08-17','16:23:52','2026-08-17','16:23:52');
/*!40000 ALTER TABLE `purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_no` varchar(40) NOT NULL,
  `vendor_id` int(10) unsigned NOT NULL,
  `purchase_date` date NOT NULL,
  `project_id` int(10) unsigned DEFAULT NULL,
  `narration` varchar(255) DEFAULT NULL,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `payment_mode` enum('cash','bank','credit') NOT NULL DEFAULT 'cash',
  `bank_id` int(10) unsigned DEFAULT NULL,
  `reference` varchar(80) DEFAULT NULL,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `payment_voucher_id` int(10) unsigned DEFAULT NULL,
  `status` enum('pending','partial','paid','cancelled') NOT NULL DEFAULT 'pending',
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_purchases_no` (`purchase_no`),
  KEY `idx_purchases_vendor` (`vendor_id`),
  KEY `idx_purchases_project` (`project_id`),
  KEY `idx_purchases_date` (`purchase_date`),
  KEY `fk_purchases_voucher` (`voucher_id`),
  KEY `fk_purchases_pvoucher` (`payment_voucher_id`),
  CONSTRAINT `fk_purchases_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_purchases_pvoucher` FOREIGN KEY (`payment_voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_purchases_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`),
  CONSTRAINT `fk_purchases_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
INSERT INTO `purchases` VALUES (1,'PUR-0001',1,'2026-08-17',1,'',10000.00,0.00,10000.00,'cash',NULL,'',28,29,'paid','2026-08-17','16:23:52','2026-08-17','16:23:52');
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `quotation_no` varchar(40) NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `property_id` int(10) unsigned DEFAULT NULL,
  `dealer_id` int(10) unsigned DEFAULT NULL,
  `quotation_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','sent','accepted','rejected') NOT NULL DEFAULT 'draft',
  `remarks` varchar(255) DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_quotation_no` (`quotation_no`),
  KEY `idx_quo_customer` (`customer_id`),
  KEY `fk_quo_property` (`property_id`),
  CONSTRAINT `fk_quo_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quo_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotations`
--

LOCK TABLES `quotations` WRITE;
/*!40000 ALTER TABLE `quotations` DISABLE KEYS */;
INSERT INTO `quotations` VALUES (1,'QUO-0001',1,2,1,'2026-06-10',11500000.00,'sent','Price slightly negotiable','2026-07-31','08:14:26','2026-07-31','08:14:26'),(2,'QUO-0002',3,4,NULL,'2026-06-25',7200000.00,'accepted',NULL,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(3,'QUO-0003',6,7,2,'2026-07-01',17500000.00,'draft','Awaiting final approval','2026-07-31','08:14:26','2026-07-31','08:14:26');
/*!40000 ALTER TABLE `quotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receipts`
--

DROP TABLE IF EXISTS `receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receipts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(40) NOT NULL,
  `receipt_date` date NOT NULL,
  `project_id` int(10) unsigned DEFAULT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `booking_id` int(10) unsigned DEFAULT NULL,
  `installment_id` int(10) unsigned DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `payment_method_id` int(10) unsigned DEFAULT NULL,
  `bank_id` int(10) unsigned DEFAULT NULL,
  `reference` varchar(80) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `received_by` int(10) unsigned DEFAULT NULL,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_receipt_no` (`receipt_no`),
  KEY `idx_rec_customer` (`customer_id`),
  KEY `fk_rec_booking` (`booking_id`),
  KEY `fk_rec_inst` (`installment_id`),
  KEY `idx_receipt_project` (`project_id`),
  CONSTRAINT `fk_rec_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rec_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rec_inst` FOREIGN KEY (`installment_id`) REFERENCES `installments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_receipt_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receipts`
--

LOCK TABLES `receipts` WRITE;
/*!40000 ALTER TABLE `receipts` DISABLE KEYS */;
INSERT INTO `receipts` VALUES (1,'RCT-0001','2025-06-15',NULL,2,1,1,1000000.00,1,NULL,'Bank draft 1001','Token + booking amount',1,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,'RCT-0002','2025-09-16',NULL,2,1,3,1712500.00,2,1,'TRX-9981','1st quarterly installment',1,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,'RCT-0003','2025-12-16',NULL,2,1,4,1712500.00,2,1,'TRX-10102','2nd quarterly installment',1,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(4,'RCT-0004','2026-03-20',NULL,2,1,5,1000000.00,3,2,'Cheque 0092','Partial 3rd installment',1,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(5,'RCT-0005','2025-03-10',NULL,5,2,11,600000.00,1,NULL,NULL,'Token + booking amount',1,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(6,'RCT-0006','2026-03-10',NULL,5,2,13,5900000.00,2,3,'TRX-12050','Full and final payment',1,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(7,'RCT-0007','2026-07-20',NULL,6,3,14,500000.00,1,NULL,NULL,'Token + booking amount',1,NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(8,'RCT-0008','2026-07-31',NULL,2,1,5,12500.00,1,NULL,'','payment Done',1,NULL,'2026-07-31','14:47:43','2026-07-31','14:47:43'),(27,'RCT-0009','2026-08-14',NULL,2,1,5,100000.00,1,NULL,'','',1,NULL,'2026-08-14','18:47:51','2026-08-14','18:47:51'),(32,'RCT-0010','2026-08-14',NULL,3,23,216,11500000.00,NULL,1,'','Full cash sale',1,NULL,'2026-08-14','20:52:09','2026-08-14','20:52:09'),(33,'RCT-0011','2026-08-15',NULL,3,24,217,7200000.00,NULL,1,'','Full cash sale',1,NULL,'2026-08-15','14:57:10','2026-08-15','14:57:10'),(34,'RCT-0012','2026-08-15',NULL,3,25,218,2000000.00,2,1,'','Booking amount',1,NULL,'2026-08-15','15:00:36','2026-08-15','15:00:36'),(35,'RCT-0013','2026-08-15',NULL,3,25,220,200000.00,2,1,'','',1,NULL,'2026-08-15','15:01:33','2026-08-15','15:01:33'),(37,'RCT-0014','2026-08-15',NULL,3,NULL,219,100000.00,NULL,1,'','',1,NULL,'2026-08-15','15:46:16','2026-08-15','15:46:16'),(38,'RCT-0015','2026-08-17',NULL,3,NULL,219,500.00,NULL,NULL,'','payment from employeee',1,NULL,'2026-08-17','15:48:37','2026-08-17','15:48:37'),(39,'RCT-0016','2026-08-18',NULL,3,25,NULL,800000.00,NULL,NULL,'','',1,NULL,'2026-08-18','08:00:16','2026-08-18','08:00:16');
/*!40000 ALTER TABLE `receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rent_collections`
--

DROP TABLE IF EXISTS `rent_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rent_collections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `schedule_id` int(10) unsigned NOT NULL,
  `agreement_id` int(10) unsigned NOT NULL,
  `collection_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method_id` int(10) unsigned DEFAULT NULL,
  `bank_id` int(10) unsigned DEFAULT NULL,
  `reference` varchar(80) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rc_schedule` (`schedule_id`),
  CONSTRAINT `fk_rc_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `rent_schedule` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rent_collections`
--

LOCK TABLES `rent_collections` WRITE;
/*!40000 ALTER TABLE `rent_collections` DISABLE KEYS */;
INSERT INTO `rent_collections` (`id`, `schedule_id`, `agreement_id`, `collection_date`, `amount`, `payment_method_id`, `bank_id`, `reference`, `remarks`, `created_date`, `created_time`, `updated_date`, `updated_time`) VALUES (2,2,1,'2026-02-05',60000.00,1,NULL,NULL,'February 2026 rent','2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,3,1,'2026-03-05',60000.00,2,1,'TRX-8801','March 2026 rent','2026-07-31','08:14:27','2026-07-31','08:14:27'),(4,4,1,'2026-04-05',60000.00,1,NULL,NULL,'April 2026 rent','2026-07-31','08:14:27','2026-07-31','08:14:27'),(5,5,1,'2026-05-05',60000.00,2,1,'TRX-8877','May 2026 rent','2026-07-31','08:14:27','2026-07-31','08:14:27'),(6,6,1,'2026-06-05',60000.00,1,NULL,NULL,'June 2026 rent','2026-07-31','08:14:27','2026-07-31','08:14:27'),(7,13,2,'2026-02-06',85000.00,1,NULL,NULL,'February 2026 rent','2026-07-31','08:14:27','2026-07-31','08:14:27'),(8,14,2,'2026-03-06',85000.00,2,2,'TRX-8912','March 2026 rent','2026-07-31','08:14:27','2026-07-31','08:14:27'),(9,15,2,'2026-04-06',85000.00,1,NULL,NULL,'April 2026 rent','2026-07-31','08:14:27','2026-07-31','08:14:27'),(10,16,2,'2026-05-06',85000.00,1,NULL,NULL,'May 2026 rent','2026-07-31','08:14:27','2026-07-31','08:14:27'),(11,25,3,'2026-04-07',95000.00,2,3,'TRX-9020','April 2026 rent','2026-07-31','08:14:27','2026-07-31','08:14:27'),(12,1,1,'2026-01-05',60000.00,NULL,NULL,'','','2026-08-13','09:27:40','2026-08-13','09:27:40');
/*!40000 ALTER TABLE `rent_collections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rent_schedule`
--

DROP TABLE IF EXISTS `rent_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rent_schedule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agreement_id` int(10) unsigned NOT NULL,
  `period` varchar(20) NOT NULL,
  `due_date` date NOT NULL,
  `rent_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `late_charges` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','partial','paid','overdue') NOT NULL DEFAULT 'pending',
  `paid_date` date DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rs_agreement` (`agreement_id`),
  CONSTRAINT `fk_rs_agreement` FOREIGN KEY (`agreement_id`) REFERENCES `rental_agreements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rent_schedule`
--

LOCK TABLES `rent_schedule` WRITE;
/*!40000 ALTER TABLE `rent_schedule` DISABLE KEYS */;
INSERT INTO `rent_schedule` VALUES (1,1,'2026-01','2026-01-01',60000.00,0.00,60000.00,'paid','2026-01-05','2026-07-31','08:14:27','2026-08-13','09:27:15'),(2,1,'2026-02','2026-02-01',60000.00,0.00,60000.00,'paid','2026-02-05','2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,1,'2026-03','2026-03-01',60000.00,0.00,60000.00,'paid','2026-03-05','2026-07-31','08:14:27','2026-07-31','08:14:27'),(4,1,'2026-04','2026-04-01',60000.00,0.00,60000.00,'paid','2026-04-05','2026-07-31','08:14:27','2026-07-31','08:14:27'),(5,1,'2026-05','2026-05-01',60000.00,0.00,60000.00,'paid','2026-05-05','2026-07-31','08:14:27','2026-07-31','08:14:27'),(6,1,'2026-06','2026-06-01',60000.00,0.00,60000.00,'paid','2026-06-05','2026-07-31','08:14:27','2026-07-31','08:14:27'),(7,1,'2026-07','2026-07-01',60000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-08-15','15:32:48'),(8,1,'2026-08','2026-08-01',60000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(9,1,'2026-09','2026-09-01',60000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(10,1,'2026-10','2026-10-01',60000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(11,1,'2026-11','2026-11-01',60000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(12,1,'2026-12','2026-12-01',60000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(13,2,'2026-02','2026-02-01',85000.00,0.00,85000.00,'paid','2026-02-06','2026-07-31','08:14:27','2026-07-31','08:14:27'),(14,2,'2026-03','2026-03-01',85000.00,0.00,85000.00,'paid','2026-03-06','2026-07-31','08:14:27','2026-07-31','08:14:27'),(15,2,'2026-04','2026-04-01',85000.00,0.00,85000.00,'paid','2026-04-06','2026-07-31','08:14:27','2026-07-31','08:14:27'),(16,2,'2026-05','2026-05-01',85000.00,0.00,85000.00,'paid','2026-05-06','2026-07-31','08:14:27','2026-07-31','08:14:27'),(17,2,'2026-06','2026-06-01',85000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(18,2,'2026-07','2026-07-01',85000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(19,2,'2026-08','2026-08-01',85000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(20,2,'2026-09','2026-09-01',85000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(21,2,'2026-10','2026-10-01',85000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(22,2,'2026-11','2026-11-01',85000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(23,2,'2026-12','2026-12-01',85000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(24,2,'2027-01','2027-01-01',85000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(25,3,'2026-04','2026-04-01',95000.00,0.00,95000.00,'paid','2026-04-07','2026-07-31','08:14:27','2026-07-31','08:14:27'),(26,3,'2026-05','2026-05-01',95000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(27,3,'2026-06','2026-06-01',95000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(28,3,'2026-07','2026-07-01',95000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(29,3,'2026-08','2026-08-01',95000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(30,3,'2026-09','2026-09-01',95000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(31,3,'2026-10','2026-10-01',95000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(32,3,'2026-11','2026-11-01',95000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(33,3,'2026-12','2026-12-01',95000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(34,3,'2027-01','2027-01-01',95000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(35,3,'2027-02','2027-02-01',95000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(36,3,'2027-03','2027-03-01',95000.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27');
/*!40000 ALTER TABLE `rent_schedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rental_agreements`
--

DROP TABLE IF EXISTS `rental_agreements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rental_agreements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agreement_no` varchar(40) NOT NULL,
  `property_id` int(10) unsigned NOT NULL,
  `tenant_id` int(10) unsigned NOT NULL,
  `owner_id` int(10) unsigned DEFAULT NULL,
  `dealer_id` int(10) unsigned DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `monthly_rent` decimal(12,2) NOT NULL DEFAULT 0.00,
  `security_deposit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `advance_rent` decimal(12,2) NOT NULL DEFAULT 0.00,
  `parking_charges` decimal(10,2) NOT NULL DEFAULT 0.00,
  `maintenance_charges` decimal(10,2) NOT NULL DEFAULT 0.00,
  `utility_included` tinyint(1) NOT NULL DEFAULT 0,
  `rent_increase_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `notice_period_days` int(11) NOT NULL DEFAULT 30,
  `status` enum('active','renewed','expired','terminated','vacated') NOT NULL DEFAULT 'active',
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rent_agr_no` (`agreement_no`),
  KEY `idx_ra_property` (`property_id`),
  KEY `idx_ra_tenant` (`tenant_id`),
  CONSTRAINT `fk_ra_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ra_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_agreements`
--

LOCK TABLES `rental_agreements` WRITE;
/*!40000 ALTER TABLE `rental_agreements` DISABLE KEYS */;
INSERT INTO `rental_agreements` VALUES (1,'RA-0001',5,1,3,NULL,'2026-01-01','2026-12-31',60000.00,120000.00,60000.00,0.00,0.00,0,5.00,60,'active','2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,'RA-0002',8,2,2,NULL,'2026-02-01','2027-01-31',85000.00,170000.00,85000.00,0.00,0.00,0,5.00,30,'active','2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,'RA-0003',11,3,1,NULL,'2026-04-01','2027-03-31',95000.00,190000.00,0.00,0.00,0.00,0,0.00,30,'active','2026-07-31','08:14:27','2026-07-31','08:14:27');
/*!40000 ALTER TABLE `rental_agreements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roads`
--

DROP TABLE IF EXISTS `roads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_roads_project` (`project_id`),
  CONSTRAINT `fk_roads_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roads`
--

LOCK TABLES `roads` WRITE;
/*!40000 ALTER TABLE `roads` DISABLE KEYS */;
INSERT INTO `roads` VALUES (1,1,'Main Boulevard Road','2026-07-31','08:14:26','2026-07-31','08:14:26'),(2,1,'12th Avenue Road','2026-07-31','08:14:26','2026-07-31','08:14:26'),(3,2,'Jinnah Avenue','2026-07-31','08:14:26','2026-07-31','08:14:26'),(4,3,'Commercial Avenue Road','2026-07-31','08:14:26','2026-07-31','08:14:26'),(5,4,'abc','2026-08-05','12:53:21','2026-08-05','12:53:21');
/*!40000 ALTER TABLE `roads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_perm` (`role_id`,`permission_id`),
  KEY `idx_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1,24,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(2,1,23,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(3,1,26,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(4,1,25,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(5,1,8,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(6,1,7,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(7,1,1,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(8,1,12,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(9,1,11,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(10,1,27,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(11,1,22,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(12,1,21,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(13,1,2,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(14,1,30,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(15,1,10,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(16,1,9,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(17,1,4,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(18,1,3,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(19,1,6,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(20,1,5,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(21,1,16,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(22,1,15,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(23,1,28,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(24,1,14,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(25,1,13,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(26,1,29,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(27,1,18,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(28,1,17,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(29,1,20,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(30,1,19,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(32,2,1,'2026-07-31','15:21:45','2026-07-31','15:21:45'),(33,1,32,'2026-08-08','01:42:00','2026-08-08','01:42:00'),(34,1,31,'2026-08-08','01:42:00','2026-08-08','01:42:00'),(36,1,34,'2026-08-08','10:21:59','2026-08-08','10:21:59'),(37,1,33,'2026-08-08','10:21:59','2026-08-08','10:21:59'),(38,1,36,'2026-08-13','09:11:59','2026-08-13','09:11:59'),(39,1,35,'2026-08-13','09:11:59','2026-08-13','09:11:59'),(40,1,38,'2026-08-15','16:48:31','2026-08-15','16:48:31'),(41,1,37,'2026-08-15','16:48:31','2026-08-15','16:48:31'),(42,1,41,'2026-08-17','16:51:43','2026-08-17','16:51:43'),(43,1,42,'2026-08-17','16:51:43','2026-08-17','16:51:43'),(44,1,43,'2026-08-19','00:00:00','2026-08-19','00:00:00'),(45,1,44,'2026-08-19','00:00:00','2026-08-19','00:00:00'),(46,1,45,'2026-08-19','00:00:00','2026-08-19','00:00:00'),(47,1,46,'2026-08-19','00:00:00','2026-08-19','00:00:00');
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Admin','Full system access',1,1,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(2,'Director','Company director',0,1,'2026-07-31','08:14:11','2026-07-31','15:21:45'),(3,'Manager','General manager',0,1,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(4,'Sales Manager','Manages sales team',0,1,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(5,'Recovery Officer','Installment recovery',0,1,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(6,'Accounts','Accounting and finance',0,1,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(7,'Reception','Front desk and CRM',0,1,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(8,'Rental Manager','Rental management',0,1,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(9,'Property Manager','Property maintenance',0,1,'2026-07-31','08:14:11','2026-07-31','08:14:11'),(10,'Dealer','External dealer',0,1,'2026-07-31','08:14:11','2026-07-31','08:14:11');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_agreements`
--

DROP TABLE IF EXISTS `sale_agreements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_agreements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agreement_no` varchar(40) NOT NULL,
  `booking_id` int(10) unsigned NOT NULL,
  `agreement_date` date NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('draft','signed','registered') NOT NULL DEFAULT 'draft',
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sa_no` (`agreement_no`),
  KEY `fk_sa_booking` (`booking_id`),
  CONSTRAINT `fk_sa_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_agreements`
--

LOCK TABLES `sale_agreements` WRITE;
/*!40000 ALTER TABLE `sale_agreements` DISABLE KEYS */;
INSERT INTO `sale_agreements` VALUES (1,'AGR-0001',1,'2025-12-01',NULL,'signed','2026-07-31','08:14:26','2026-07-31','08:14:26'),(2,'AGR-0002',2,'2026-03-15',NULL,'registered','2026-07-31','08:14:26','2026-07-31','08:14:26');
/*!40000 ALTER TABLE `sale_agreements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'company_name','Prime Estate Pvt Ltd','2026-07-31','08:14:11','2026-07-31','08:14:11'),(2,'company_tagline','Real Estate ERP','2026-07-31','08:14:11','2026-07-31','08:14:11'),(3,'company_address','Main Boulevard, Gulberg III, Lahore','2026-07-31','08:14:11','2026-07-31','08:14:11'),(4,'company_phone','042-111111111','2026-07-31','08:14:11','2026-07-31','08:14:11'),(5,'company_email','info@example.com','2026-07-31','08:14:11','2026-07-31','08:14:11'),(6,'company_logo','','2026-07-31','08:14:11','2026-07-31','08:14:11'),(7,'currency','Rs.','2026-07-31','08:14:11','2026-07-31','00:00:00'),(8,'session_timeout','60','2026-07-31','08:14:11','2026-07-31','08:14:11');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `societies`
--

DROP TABLE IF EXISTS `societies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `societies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `city_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_societies_city` (`city_id`),
  CONSTRAINT `fk_societies_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `societies`
--

LOCK TABLES `societies` WRITE;
/*!40000 ALTER TABLE `societies` DISABLE KEYS */;
INSERT INTO `societies` VALUES (6,7,'Al Meezan','2026-08-13','15:08:21','2026-08-13','15:08:21');
/*!40000 ALTER TABLE `societies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `streets`
--

DROP TABLE IF EXISTS `streets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `streets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(10) unsigned NOT NULL,
  `block_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_streets_project` (`project_id`),
  CONSTRAINT `fk_streets_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `streets`
--

LOCK TABLES `streets` WRITE;
/*!40000 ALTER TABLE `streets` DISABLE KEYS */;
INSERT INTO `streets` VALUES (1,1,1,'Street 1','2026-07-31','08:14:26','2026-07-31','08:14:26'),(2,1,1,'Street 2','2026-07-31','08:14:26','2026-07-31','08:14:26'),(3,1,2,'Street 5','2026-07-31','08:14:26','2026-07-31','08:14:26'),(4,1,4,'Street 10','2026-07-31','08:14:26','2026-07-31','08:14:26'),(5,3,7,'Main Walkway','2026-07-31','08:14:26','2026-07-31','08:14:26');
/*!40000 ALTER TABLE `streets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(180) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `related_type` varchar(50) DEFAULT NULL,
  `related_id` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tasks_assigned` (`assigned_to`),
  CONSTRAINT `fk_tasks_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `technicians`
--

DROP TABLE IF EXISTS `technicians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `technicians` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `speciality` varchar(120) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `technicians`
--

LOCK TABLES `technicians` WRITE;
/*!40000 ALTER TABLE `technicians` DISABLE KEYS */;
INSERT INTO `technicians` VALUES (1,'Iqbal Electrician','03005555001','Electric',1,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,'Rashid Plumber','03005555002','Plumbing',1,'2026-07-31','08:14:27','2026-07-31','08:14:27');
/*!40000 ALTER TABLE `technicians` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenant_ledger`
--

DROP TABLE IF EXISTS `tenant_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenant_ledger` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(10) unsigned NOT NULL,
  `entry_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `debit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tl_tenant` (`tenant_id`),
  CONSTRAINT `fk_tl_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_ledger`
--

LOCK TABLES `tenant_ledger` WRITE;
/*!40000 ALTER TABLE `tenant_ledger` DISABLE KEYS */;
INSERT INTO `tenant_ledger` VALUES (1,1,'2026-01-01','Security deposit RA-0001',0.00,120000.00,120000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,1,'2026-01-01','Advance rent RA-0001',0.00,60000.00,180000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,1,'2026-01-01','Rent due January 2026',60000.00,0.00,120000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(4,1,'2026-02-01','Rent due February 2026',60000.00,0.00,60000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(5,1,'2026-02-05','Rent received February 2026',0.00,60000.00,120000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(6,1,'2026-03-01','Rent due March 2026',60000.00,0.00,60000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(7,1,'2026-03-05','Rent received March 2026',0.00,60000.00,120000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(8,2,'2026-02-01','Security deposit RA-0002',0.00,170000.00,170000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(9,2,'2026-02-01','Rent due February 2026',85000.00,0.00,85000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(10,3,'2026-04-01','Security deposit RA-0003',0.00,190000.00,190000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27');
/*!40000 ALTER TABLE `tenant_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenants`
--

DROP TABLE IF EXISTS `tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tenants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_no` varchar(40) NOT NULL,
  `full_name` varchar(180) NOT NULL,
  `cnic` varchar(40) DEFAULT NULL,
  `police_verification` enum('pending','cleared','rejected') NOT NULL DEFAULT 'pending',
  `emergency_contact` varchar(40) DEFAULT NULL,
  `emergency_name` varchar(120) DEFAULT NULL,
  `occupation` varchar(120) DEFAULT NULL,
  `company` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenants_no` (`tenant_no`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenants`
--

LOCK TABLES `tenants` WRITE;
/*!40000 ALTER TABLE `tenants` DISABLE KEYS */;
INSERT INTO `tenants` VALUES (1,'TEN-0001','Adnan Yousaf','35201-3332221-5','cleared','03004444001','Bilal Yousaf','Software Engineer','TechCorp (Pvt) Ltd','Street 4, Gulberg II, Lahore',NULL,1,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,'TEN-0002','Shahzad Riaz','35202-6665554-7','cleared','03004444002','Riaz Ahmed','Businessman',NULL,'Shop 9, Anarkali Bazaar, Lahore',NULL,1,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,'TEN-0003','Mariam Farooq','35202-9998887-9','cleared','03004444003','Farooq Khan','Doctor','City General Hospital','Civic Centre, Bahria Town, Lahore',NULL,1,'2026-07-31','08:14:27','2026-07-31','08:14:27');
/*!40000 ALTER TABLE `tenants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transfers`
--

DROP TABLE IF EXISTS `transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transfers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_no` varchar(40) NOT NULL,
  `transfer_date` date NOT NULL,
  `project_id` int(10) unsigned DEFAULT NULL,
  `transfer_type` enum('customer_to_customer','bank_to_cash','bank_to_bank','customer_withdraw','owner_withdraw') NOT NULL,
  `from_customer_id` int(10) unsigned DEFAULT NULL,
  `to_customer_id` int(10) unsigned DEFAULT NULL,
  `from_bank_id` int(10) unsigned DEFAULT NULL,
  `to_bank_id` int(10) unsigned DEFAULT NULL,
  `booking_id` int(10) unsigned DEFAULT NULL,
  `account_id` int(10) unsigned DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `narration` varchar(255) DEFAULT NULL,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_transfer_no` (`transfer_no`),
  KEY `idx_tr_date` (`transfer_date`),
  KEY `idx_tr_type` (`transfer_type`),
  KEY `idx_tr_from_customer` (`from_customer_id`),
  KEY `idx_tr_to_customer` (`to_customer_id`),
  KEY `idx_tr_booking` (`booking_id`),
  KEY `fk_tr_from_bank` (`from_bank_id`),
  KEY `fk_tr_to_bank` (`to_bank_id`),
  KEY `fk_tr_account` (`account_id`),
  KEY `fk_tr_voucher` (`voucher_id`),
  KEY `idx_transfer_project` (`project_id`),
  CONSTRAINT `fk_tr_account` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tr_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tr_from_bank` FOREIGN KEY (`from_bank_id`) REFERENCES `banks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tr_from_customer` FOREIGN KEY (`from_customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tr_to_bank` FOREIGN KEY (`to_bank_id`) REFERENCES `banks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tr_to_customer` FOREIGN KEY (`to_customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tr_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_transfer_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfers`
--

LOCK TABLES `transfers` WRITE;
/*!40000 ALTER TABLE `transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `username` varchar(80) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_role` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,NULL,'admin','admin123','System Administrator','admin@example.com','03001234567',NULL,1,'2026-08-18 07:55:25',NULL,'2026-07-31','08:14:11','2026-08-18','07:55:25'),(2,4,1,'ahmed','ahmed123','Ahmed Raza','ahmed@example.com','03011112222',NULL,1,NULL,NULL,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(3,6,1,'sara','sara123','Sara Khan','sara@example.com','03011113333',NULL,1,NULL,NULL,'2026-07-31','08:14:26','2026-07-31','08:14:26'),(4,8,1,'bilal','bilal123','Bilal Hussain','bilal@example.com','03011114444',NULL,1,NULL,NULL,'2026-07-31','08:14:26','2026-07-31','08:14:26');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utilities`
--

DROP TABLE IF EXISTS `utilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utilities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` int(10) unsigned NOT NULL,
  `tenant_id` int(10) unsigned DEFAULT NULL,
  `utility_type` enum('electricity','gas','water','internet','maintenance','generator','lift') NOT NULL DEFAULT 'electricity',
  `meter_no` varchar(60) DEFAULT NULL,
  `connection_no` varchar(60) DEFAULT NULL,
  `consumer_no` varchar(60) DEFAULT NULL,
  `rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_util_property` (`property_id`),
  CONSTRAINT `fk_util_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilities`
--

LOCK TABLES `utilities` WRITE;
/*!40000 ALTER TABLE `utilities` DISABLE KEYS */;
INSERT INTO `utilities` VALUES (1,5,1,'electricity','MTR-E-1101','CON-1101','CSR-1101',28.00,'active','2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,5,1,'gas','MTR-G-1102','CON-1102','CSR-1102',80.00,'active','2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,8,2,'electricity','MTR-E-2203','CON-2203','CSR-2203',30.00,'active','2026-07-31','08:14:27','2026-07-31','08:14:27'),(4,11,3,'water',NULL,'CON-3301','CSR-3301',25.00,'active','2026-07-31','08:14:27','2026-07-31','08:14:27');
/*!40000 ALTER TABLE `utilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utility_bills`
--

DROP TABLE IF EXISTS `utility_bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utility_bills` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `utility_id` int(10) unsigned NOT NULL,
  `property_id` int(10) unsigned NOT NULL,
  `tenant_id` int(10) unsigned DEFAULT NULL,
  `billing_month` varchar(20) NOT NULL,
  `bill_date` date NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `penalty` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','partial','paid') NOT NULL DEFAULT 'pending',
  `paid_date` date DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ub_utility` (`utility_id`),
  CONSTRAINT `fk_ub_utility` FOREIGN KEY (`utility_id`) REFERENCES `utilities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utility_bills`
--

LOCK TABLES `utility_bills` WRITE;
/*!40000 ALTER TABLE `utility_bills` DISABLE KEYS */;
INSERT INTO `utility_bills` VALUES (1,1,5,1,'2026-06','2026-06-05','2026-06-20',7000.00,0.00,7000.00,'paid','2026-06-18','2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,1,5,1,'2026-07','2026-07-05','2026-07-20',7280.00,0.00,0.00,'pending',NULL,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,3,8,2,'2026-06','2026-06-05','2026-06-20',6600.00,100.00,4000.00,'partial','2026-06-22','2026-07-31','08:14:27','2026-07-31','08:14:27'),(4,4,11,3,'2026-06','2026-06-05','2026-06-20',2500.00,0.00,2500.00,'paid','2026-06-10','2026-07-31','08:14:27','2026-07-31','08:14:27');
/*!40000 ALTER TABLE `utility_bills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_payments`
--

DROP TABLE IF EXISTS `vendor_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` int(10) unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `payment_method_id` int(10) unsigned DEFAULT NULL,
  `bank_id` int(10) unsigned DEFAULT NULL,
  `reference` varchar(80) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vp_vendor` (`vendor_id`),
  CONSTRAINT `fk_vp_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_payments`
--

LOCK TABLES `vendor_payments` WRITE;
/*!40000 ALTER TABLE `vendor_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_no` varchar(40) NOT NULL,
  `business_name` varchar(180) DEFAULT NULL,
  `contact_person` varchar(180) DEFAULT NULL,
  `cnic` varchar(40) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `whatsapp` varchar(40) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city_id` int(10) unsigned DEFAULT NULL,
  `bank_id` int(10) unsigned DEFAULT NULL,
  `bank_account_title` varchar(150) DEFAULT NULL,
  `bank_account_no` varchar(80) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vendors_no` (`vendor_no`),
  KEY `idx_vendors_city` (`city_id`),
  KEY `fk_vendors_bank` (`bank_id`),
  CONSTRAINT `fk_vendors_bank` FOREIGN KEY (`bank_id`) REFERENCES `banks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors`
--

LOCK TABLES `vendors` WRITE;
/*!40000 ALTER TABLE `vendors` DISABLE KEYS */;
INSERT INTO `vendors` VALUES (1,'VEN-0001','Softynix','Talha','3610384278099','03211677422','','talha353523@gmail.com','Multan',7,NULL,'','',1,'2026-08-17','16:02:07','2026-08-17','16:02:07');
/*!40000 ALTER TABLE `vendors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voucher_items`
--

DROP TABLE IF EXISTS `voucher_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voucher_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` int(10) unsigned NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `item_description` varchar(255) DEFAULT NULL,
  `debit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vi_voucher` (`voucher_id`),
  KEY `idx_vi_account` (`account_id`),
  CONSTRAINT `fk_vi_account` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`),
  CONSTRAINT `fk_vi_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voucher_items`
--

LOCK TABLES `voucher_items` WRITE;
/*!40000 ALTER TABLE `voucher_items` DISABLE KEYS */;
INSERT INTO `voucher_items` VALUES (1,1,1,'Cash received',60000.00,0.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,1,7,'Rental income RA-0001',0.00,60000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,2,8,'Salaries July 2026',150000.00,0.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(4,2,1,'Cash paid',0.00,150000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(5,3,11,'Marketing hoarding Gulberg',25000.00,0.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(6,3,2,'Bank transfer',0.00,25000.00,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(19,10,8,'Payable to ABC',50000.00,0.00,'2026-08-14','18:28:17','2026-08-14','18:28:17'),(20,10,25,'Payable to ABC',0.00,50000.00,'2026-08-14','18:28:17','2026-08-14','18:28:17'),(25,13,27,'Property sale receipt - BK-0004',11500000.00,0.00,'2026-08-14','20:52:09','2026-08-14','20:52:09'),(26,13,6,'Property sale - BK-0004',0.00,11500000.00,'2026-08-14','20:52:09','2026-08-14','20:52:09'),(27,14,27,'Property sale receipt - BK-0005',7200000.00,0.00,'2026-08-15','14:57:10','2026-08-15','14:57:10'),(28,14,6,'Property sale - BK-0005',0.00,7200000.00,'2026-08-15','14:57:10','2026-08-15','14:57:10'),(37,19,27,'Received from Ahmed Hassan',100000.00,0.00,'2026-08-15','15:46:16','2026-08-15','15:46:16'),(38,19,1,'Received in HBL',0.00,100000.00,'2026-08-15','15:46:16','2026-08-15','15:46:16'),(43,21,27,'payment withdraw from bank',0.00,100000.00,'2026-08-15','16:02:45','2026-08-15','16:02:45'),(44,21,25,'ABC',100000.00,0.00,'2026-08-15','16:02:45','2026-08-15','16:02:45'),(45,22,29,'Payable to Ali Masonry Works',500000.00,0.00,'2026-08-15','16:53:38','2026-08-15','16:53:38'),(46,22,30,'Payable to Ali Masonry Works',0.00,500000.00,'2026-08-15','16:53:38','2026-08-15','16:53:38'),(47,23,30,'Paid to Ali Masonry Works',200000.00,0.00,'2026-08-15','16:53:39','2026-08-15','16:53:39'),(48,23,1,'Contractor payment - Cash',0.00,200000.00,'2026-08-15','16:53:39','2026-08-15','16:53:39'),(49,24,30,'Paid to Ali Masonry Works',50000.00,0.00,'2026-08-15','16:54:29','2026-08-15','16:54:29'),(50,24,1,'Paid from Cash',0.00,50000.00,'2026-08-15','16:54:29','2026-08-15','16:54:29'),(51,25,29,'Payable to Ali Masonry Works',100000.00,0.00,'2026-08-15','17:22:43','2026-08-15','17:22:43'),(52,25,30,'Payable to Ali Masonry Works',0.00,100000.00,'2026-08-15','17:22:43','2026-08-15','17:22:43'),(55,27,1,'Received from Ahmed Hassan',500.00,0.00,'2026-08-17','15:48:37','2026-08-17','15:48:37'),(56,27,1,'Received in Cash',0.00,500.00,'2026-08-17','15:48:37','2026-08-17','15:48:37'),(57,28,17,'Purchase - Softynix',10000.00,0.00,'2026-08-17','16:23:52','2026-08-17','16:23:52'),(58,28,4,'Accounts Payable - Softynix',0.00,10000.00,'2026-08-17','16:23:52','2026-08-17','16:23:52'),(59,29,4,'Paid to Softynix',10000.00,0.00,'2026-08-17','16:23:52','2026-08-17','16:23:52'),(60,29,1,'Paid from Cash',0.00,10000.00,'2026-08-17','16:23:52','2026-08-17','16:23:52'),(61,30,1,'Received from Ahmed Hassan',800000.00,0.00,'2026-08-18','08:00:16','2026-08-18','08:00:16'),(62,30,6,'Received in Cash',0.00,800000.00,'2026-08-18','08:00:16','2026-08-18','08:00:16');
/*!40000 ALTER TABLE `voucher_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vouchers`
--

DROP TABLE IF EXISTS `vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vouchers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `voucher_no` varchar(40) NOT NULL,
  `voucher_date` date NOT NULL,
  `voucher_type` enum('cash_payment','cash_receipt','bank_payment','bank_receipt','journal') NOT NULL,
  `project_id` int(10) unsigned DEFAULT NULL,
  `narration` varchar(255) DEFAULT NULL,
  `reference_no` varchar(80) DEFAULT NULL,
  `credit_party_type` varchar(20) DEFAULT NULL,
  `credit_party_id` int(10) unsigned DEFAULT NULL,
  `debit_party_type` varchar(20) DEFAULT NULL,
  `debit_party_id` int(10) unsigned DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `remarks` varchar(255) DEFAULT NULL,
  `status` enum('draft','posted') NOT NULL DEFAULT 'posted',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_voucher_no` (`voucher_no`),
  KEY `idx_voucher_project` (`project_id`),
  CONSTRAINT `fk_voucher_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vouchers`
--

LOCK TABLES `vouchers` WRITE;
/*!40000 ALTER TABLE `vouchers` DISABLE KEYS */;
INSERT INTO `vouchers` (`id`, `voucher_no`, `voucher_date`, `voucher_type`, `project_id`, `narration`, `status`, `created_by`, `created_date`, `created_time`, `updated_date`, `updated_time`) VALUES (1,'CR-0001','2026-07-05','cash_receipt',NULL,'Rent collected RA-0001 June 2026','posted',1,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(2,'CP-0001','2026-07-10','cash_payment',NULL,'Staff salaries - July 2026','posted',1,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(3,'BP-0001','2026-07-15','bank_payment',NULL,'Marketing campaign - Gulberg hoarding','posted',1,'2026-07-31','08:14:27','2026-07-31','08:14:27'),(10,'JV-0001','2026-08-14','journal',NULL,'Payable - ABC','posted',1,'2026-08-14','18:28:17','2026-08-14','18:28:17'),(13,'JV-0002','2026-08-14','journal',1,'Cash sale BK-0004 received in HBL','posted',1,'2026-08-14','20:52:09','2026-08-14','20:52:09'),(14,'JV-0003','2026-08-15','journal',1,'Cash sale BK-0005 received in HBL','posted',1,'2026-08-15','14:57:10','2026-08-15','14:57:10'),(19,'BR-0001','2026-08-15','bank_receipt',1,'Received from Ahmed Hassan','posted',1,'2026-08-15','15:46:16','2026-08-15','15:46:16'),(21,'CP-0002','2026-08-15','cash_payment',1,'Payment to employee','posted',1,'2026-08-15','16:02:18','2026-08-15','16:02:45'),(22,'JV-0004','2026-08-15','journal',NULL,'Foundation work','posted',1,'2026-08-15','16:53:38','2026-08-15','16:53:38'),(23,'JV-0005','2026-08-15','journal',NULL,'Advance payment','posted',1,'2026-08-15','16:53:39','2026-08-15','16:53:39'),(24,'CP-0003','2026-08-15','cash_payment',NULL,'Site payment via cash paid','posted',1,'2026-08-15','16:54:29','2026-08-15','16:54:29'),(25,'JV-0006','2026-08-15','journal',NULL,'Al Noor foundation','posted',1,'2026-08-15','17:22:43','2026-08-15','17:22:43'),(27,'CR-0002','2026-08-17','cash_receipt',1,'payment from employeee','posted',1,'2026-08-17','15:48:37','2026-08-17','15:48:37'),(28,'JV-0007','2026-08-17','journal',1,'Purchase: Softynix','posted',1,'2026-08-17','16:23:52','2026-08-17','16:23:52'),(29,'CP-0004','2026-08-17','cash_payment',1,'Payment for purchase: Softynix','posted',1,'2026-08-17','16:23:52','2026-08-17','16:23:52'),(30,'CR-0003','2026-08-18','cash_receipt',1,'Received from Ahmed Hassan','posted',1,'2026-08-18','08:00:16','2026-08-18','08:00:16');
/*!40000 ALTER TABLE `vouchers` ENABLE KEYS */;
UNLOCK TABLES;

--

--
-- Table structure for table `customer_payments`
--

DROP TABLE IF EXISTS `customer_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `payment_mode` enum('cash','bank','credit') NOT NULL DEFAULT 'cash',
  `bank_id` int(10) unsigned DEFAULT NULL,
  `reference` varchar(120) DEFAULT NULL,
  `narration` varchar(255) DEFAULT NULL,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `project_id` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cp_customer` (`customer_id`),
  CONSTRAINT `fk_cp_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_payments`
--

LOCK TABLES `customer_payments` WRITE;
/*!40000 ALTER TABLE `customer_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `movement_type` enum('purchase','issue','adjustment') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `reference_type` varchar(40) DEFAULT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL,
  `project_id` int(10) unsigned DEFAULT NULL,
  `contractor_id` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sm_product` (`product_id`),
  KEY `idx_sm_project` (`project_id`),
  CONSTRAINT `fk_sm_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_sm_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sm_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_issues`
--

DROP TABLE IF EXISTS `material_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `material_issues` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `issue_no` varchar(40) NOT NULL,
  `issue_date` date NOT NULL,
  `project_id` int(10) unsigned NOT NULL,
  `contractor_id` int(10) unsigned NOT NULL,
  `narration` varchar(255) DEFAULT NULL,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mi_no` (`issue_no`),
  KEY `idx_mi_project` (`project_id`),
  KEY `idx_mi_contractor` (`contractor_id`),
  CONSTRAINT `fk_mi_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `fk_mi_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`),
  CONSTRAINT `fk_mi_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_issues`
--

LOCK TABLES `material_issues` WRITE;
/*!40000 ALTER TABLE `material_issues` DISABLE KEYS */;
/*!40000 ALTER TABLE `material_issues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_issue_items`
--

DROP TABLE IF EXISTS `material_issue_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `material_issue_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `material_issue_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0,
  `unit_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_date` date NOT NULL,
  `created_time` time NOT NULL,
  `updated_date` date NOT NULL,
  `updated_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mii_issue` (`material_issue_id`),
  CONSTRAINT `fk_mii_issue` FOREIGN KEY (`material_issue_id`) REFERENCES `material_issues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mii_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_issue_items`
--

LOCK TABLES `material_issue_items` WRITE;
/*!40000 ALTER TABLE `material_issue_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `material_issue_items` ENABLE KEYS */;
UNLOCK TABLES;

-- Dumping routines for database 'property_erp'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-18  8:23:32
