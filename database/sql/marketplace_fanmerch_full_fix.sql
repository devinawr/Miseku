CREATE DATABASE  IF NOT EXISTS `marketplace_fanmerch` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `marketplace_fanmerch`;
-- MySQL dump 10.13  Distrib 8.0.38, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: marketplace_fanmerch
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.20-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `address`
--

DROP TABLE IF EXISTS `address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `address` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `address_label` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rajaongkir_city_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `province` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `postal_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_address_user` (`user_id`),
  CONSTRAINT `fk_address_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `address`
--

LOCK TABLES `address` WRITE;
/*!40000 ALTER TABLE `address` DISABLE KEYS */;
/*!40000 ALTER TABLE `address` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artist_application`
--

DROP TABLE IF EXISTS `artist_application`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `artist_application` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `shop_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `social_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_city_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_province` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_postal_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_artist_application_user` (`user_id`),
  KEY `idx_artist_app_status` (`status`,`created_at`),
  KEY `idx_artist_app_admin` (`reviewed_by`),
  CONSTRAINT `fk_artist_app_admin` FOREIGN KEY (`reviewed_by`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_artist_app_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artist_application`
--

LOCK TABLES `artist_application` WRITE;
/*!40000 ALTER TABLE `artist_application` DISABLE KEYS */;
/*!40000 ALTER TABLE `artist_application` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artist_profile`
--

DROP TABLE IF EXISTS `artist_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `artist_profile` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `available_balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `held_balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_artist_profile_user` (`user_id`),
  CONSTRAINT `fk_artist_profile_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artist_profile`
--

LOCK TABLES `artist_profile` WRITE;
/*!40000 ALTER TABLE `artist_profile` DISABLE KEYS */;
/*!40000 ALTER TABLE `artist_profile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `balance_transaction`
--

DROP TABLE IF EXISTS `balance_transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `balance_transaction` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `artist_profile_id` bigint(20) unsigned NOT NULL,
  `customer_order_id` bigint(20) unsigned DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `withdrawal_id` bigint(20) unsigned DEFAULT NULL,
  `mutation_type` enum('credit','debit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('hold','release','forfeiture','refund','withdrawal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'release',
  `gross_amount` decimal(14,2) DEFAULT NULL,
  `fee_amount` decimal(14,2) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_balance_artist` (`artist_profile_id`,`created_at`),
  KEY `idx_balance_order` (`customer_order_id`),
  KEY `idx_balance_withdrawal` (`withdrawal_id`),
  KEY `idx_balance_payment` (`payment_id`),
  CONSTRAINT `fk_balance_artist` FOREIGN KEY (`artist_profile_id`) REFERENCES `artist_profile` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_balance_order` FOREIGN KEY (`customer_order_id`) REFERENCES `customer_order` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_balance_payment` FOREIGN KEY (`payment_id`) REFERENCES `payment` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_balance_withdrawal` FOREIGN KEY (`withdrawal_id`) REFERENCES `withdrawal` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `balance_transaction`
--

LOCK TABLES `balance_transaction` WRITE;
/*!40000 ALTER TABLE `balance_transaction` DISABLE KEYS */;
/*!40000 ALTER TABLE `balance_transaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cancellation`
--

DROP TABLE IF EXISTS `cancellation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cancellation` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_order_id` bigint(20) unsigned NOT NULL,
  `requester_id` bigint(20) unsigned DEFAULT NULL,
  `cancellation_type` enum('voluntary','settlement_timeout','shipping_timeout') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'voluntary',
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cancellation_status` enum('submitted','in_discussion','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `artist_response` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `penalty_exempt` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_cancellation_order` (`customer_order_id`),
  KEY `idx_cancellation_requester` (`requester_id`),
  CONSTRAINT `fk_cancellation_order` FOREIGN KEY (`customer_order_id`) REFERENCES `customer_order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cancellation_requester` FOREIGN KEY (`requester_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cancellation`
--

LOCK TABLES `cancellation` WRITE;
/*!40000 ALTER TABLE `cancellation` DISABLE KEYS */;
/*!40000 ALTER TABLE `cancellation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart_item`
--

DROP TABLE IF EXISTS `cart_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_item` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `added_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `product_variant_id` bigint(20) unsigned DEFAULT NULL,
  `variant_key` bigint(20) unsigned GENERATED ALWAYS AS (ifnull(`product_variant_id`,0)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_user_product_variant` (`user_id`,`product_id`,`variant_key`),
  KEY `idx_cart_product` (`product_id`),
  KEY `fk_cart_variant` (`product_variant_id`),
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cart_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variant` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_item`
--

LOCK TABLES `cart_item` WRITE;
/*!40000 ALTER TABLE `cart_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_message`
--

DROP TABLE IF EXISTS `chat_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_message` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` bigint(20) unsigned NOT NULL,
  `receiver_id` bigint(20) unsigned NOT NULL,
  `customer_order_id` bigint(20) unsigned DEFAULT NULL,
  `custom_request_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `message_body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chat_sender` (`sender_id`),
  KEY `idx_chat_receiver` (`receiver_id`),
  KEY `idx_chat_order` (`customer_order_id`),
  KEY `idx_chat_request` (`custom_request_id`),
  KEY `idx_chat_product` (`product_id`),
  CONSTRAINT `fk_chat_order` FOREIGN KEY (`customer_order_id`) REFERENCES `customer_order` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_chat_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_chat_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_chat_request` FOREIGN KEY (`custom_request_id`) REFERENCES `custom_request` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_chat_sender` FOREIGN KEY (`sender_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_message`
--

LOCK TABLES `chat_message` WRITE;
/*!40000 ALTER TABLE `chat_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_report`
--

DROP TABLE IF EXISTS `community_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `community_report` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reporter_id` bigint(20) unsigned NOT NULL COMMENT 'pengguna yang melapor',
  `reported_user_id` bigint(20) unsigned DEFAULT NULL COMMENT 'penulis konten yang dilaporkan',
  `discussion_topic_id` bigint(20) unsigned DEFAULT NULL COMMENT 'topik yang dilaporkan / tempat pesan berada',
  `topic_message_id` bigint(20) unsigned DEFAULT NULL COMMENT 'NULL = yang dilaporkan topiknya',
  `reason_category` enum('spam','sara','pornografi','penipuan','pelecehan','lainnya') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'keterangan tambahan dari pelapor',
  `reported_content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'salinan isi konten saat dilaporkan (tetap ada walau konten dihapus)',
  `report_status` enum('pending','removed','dismissed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'removed = konten dihapus admin, dismissed = laporan ditolak',
  `handled_by` bigint(20) unsigned DEFAULT NULL COMMENT 'admin yang menindaklanjuti',
  `admin_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'alasan penghapusan / alasan laporan ditolak',
  `handled_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_report_status` (`report_status`,`created_at`),
  KEY `idx_report_reporter` (`reporter_id`),
  KEY `idx_report_reported_user` (`reported_user_id`),
  KEY `idx_report_topic` (`discussion_topic_id`),
  KEY `idx_report_message` (`topic_message_id`),
  KEY `idx_report_handler` (`handled_by`),
  CONSTRAINT `fk_report_handler` FOREIGN KEY (`handled_by`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_report_message` FOREIGN KEY (`topic_message_id`) REFERENCES `topic_message` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_report_reported_user` FOREIGN KEY (`reported_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_report_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_report_topic` FOREIGN KEY (`discussion_topic_id`) REFERENCES `discussion_topic` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_report`
--

LOCK TABLES `community_report` WRITE;
/*!40000 ALTER TABLE `community_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custom_offer`
--

DROP TABLE IF EXISTS `custom_offer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_offer` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `custom_request_id` bigint(20) unsigned NOT NULL,
  `artist_profile_id` bigint(20) unsigned NOT NULL,
  `offer_price` decimal(12,2) NOT NULL,
  `estimated_days` int(10) unsigned NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offer_status` enum('pending','selected','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_offer_request_artist` (`custom_request_id`,`artist_profile_id`),
  KEY `idx_offer_artist` (`artist_profile_id`),
  CONSTRAINT `fk_offer_artist` FOREIGN KEY (`artist_profile_id`) REFERENCES `artist_profile` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_offer_request` FOREIGN KEY (`custom_request_id`) REFERENCES `custom_request` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custom_offer`
--

LOCK TABLES `custom_offer` WRITE;
/*!40000 ALTER TABLE `custom_offer` DISABLE KEYS */;
/*!40000 ALTER TABLE `custom_offer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custom_request`
--

DROP TABLE IF EXISTS `custom_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_request` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `buyer_id` bigint(20) unsigned NOT NULL,
  `merchandise_type_id` bigint(20) unsigned NOT NULL,
  `design_character` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `specification` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `budget_price` decimal(12,2) DEFAULT NULL,
  `request_status` enum('open','offered','selected','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_request_buyer` (`buyer_id`),
  KEY `idx_request_type` (`merchandise_type_id`),
  CONSTRAINT `fk_request_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_request_type` FOREIGN KEY (`merchandise_type_id`) REFERENCES `merchandise_type` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custom_request`
--

LOCK TABLES `custom_request` WRITE;
/*!40000 ALTER TABLE `custom_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `custom_request` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_order`
--

DROP TABLE IF EXISTS `customer_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_order` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `buyer_id` bigint(20) unsigned NOT NULL,
  `artist_profile_id` bigint(20) unsigned NOT NULL,
  `address_id` bigint(20) unsigned DEFAULT NULL,
  `event_booth_id` bigint(20) unsigned DEFAULT NULL,
  `order_type` enum('ready_stock','pre_order','custom','ots_event') COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_method` enum('courier','event_pickup') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_scheme` enum('full','dp') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'full',
  `product_subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `shipping_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `admin_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `order_status` enum('awaiting_payment','paid','awaiting_production','in_production','production_completed','awaiting_settlement','shipped','ready_for_pickup','completed','cancelled','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'awaiting_payment',
  `courier` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_number` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `settlement_deadline` datetime DEFAULT NULL,
  `settlement_reminder_stage` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `shipping_deadline` datetime DEFAULT NULL,
  `shipping_reminder_stage` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `custom_request_id` bigint(20) unsigned DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `auto_complete_hold` tinyint(1) NOT NULL DEFAULT 0,
  `custom_estimate_at` datetime DEFAULT NULL,
  `custom_estimate_reason` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_late_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_order_code` (`order_code`),
  UNIQUE KEY `uq_order_request` (`custom_request_id`),
  KEY `idx_order_buyer` (`buyer_id`),
  KEY `idx_order_artist` (`artist_profile_id`),
  KEY `idx_order_address` (`address_id`),
  KEY `idx_order_booth` (`event_booth_id`),
  KEY `idx_order_status` (`order_status`,`created_at`),
  KEY `idx_order_settlement_due` (`order_status`,`settlement_deadline`),
  KEY `idx_order_shipping_due` (`order_status`,`shipping_deadline`),
  CONSTRAINT `fk_order_address` FOREIGN KEY (`address_id`) REFERENCES `address` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_order_artist` FOREIGN KEY (`artist_profile_id`) REFERENCES `artist_profile` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_order_booth` FOREIGN KEY (`event_booth_id`) REFERENCES `event_booth` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_order_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_order_request_custom` FOREIGN KEY (`custom_request_id`) REFERENCES `custom_request` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_order`
--

LOCK TABLES `customer_order` WRITE;
/*!40000 ALTER TABLE `customer_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `design_option`
--

DROP TABLE IF EXISTS `design_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `design_option` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `design_poll_id` bigint(20) unsigned NOT NULL,
  `design_image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_option_poll` (`design_poll_id`),
  CONSTRAINT `fk_option_poll` FOREIGN KEY (`design_poll_id`) REFERENCES `design_poll` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `design_option`
--

LOCK TABLES `design_option` WRITE;
/*!40000 ALTER TABLE `design_option` DISABLE KEYS */;
/*!40000 ALTER TABLE `design_option` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `design_poll`
--

DROP TABLE IF EXISTS `design_poll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `design_poll` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `artist_profile_id` bigint(20) unsigned NOT NULL,
  `poll_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `poll_status` enum('running','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'running',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `fandom_id` bigint(20) unsigned DEFAULT NULL,
  `open_notified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_poll_artist` (`artist_profile_id`),
  KEY `idx_poll_period` (`end_date`,`poll_status`),
  CONSTRAINT `fk_poll_artist` FOREIGN KEY (`artist_profile_id`) REFERENCES `artist_profile` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `design_poll`
--

LOCK TABLES `design_poll` WRITE;
/*!40000 ALTER TABLE `design_poll` DISABLE KEYS */;
/*!40000 ALTER TABLE `design_poll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `design_vote`
--

DROP TABLE IF EXISTS `design_vote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `design_vote` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `design_poll_id` bigint(20) unsigned NOT NULL,
  `design_option_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `voted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vote_poll_user` (`design_poll_id`,`user_id`),
  UNIQUE KEY `uq_vote_option_user` (`design_option_id`,`user_id`),
  KEY `idx_vote_user` (`user_id`),
  CONSTRAINT `fk_vote_option` FOREIGN KEY (`design_option_id`) REFERENCES `design_option` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_vote_poll` FOREIGN KEY (`design_poll_id`) REFERENCES `design_poll` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_vote_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `design_vote`
--

LOCK TABLES `design_vote` WRITE;
/*!40000 ALTER TABLE `design_vote` DISABLE KEYS */;
/*!40000 ALTER TABLE `design_vote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discussion_topic`
--

DROP TABLE IF EXISTS `discussion_topic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discussion_topic` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fandom_id` bigint(20) unsigned NOT NULL,
  `creator_id` bigint(20) unsigned NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_topic_fandom` (`fandom_id`,`created_at`),
  KEY `idx_topic_creator` (`creator_id`),
  CONSTRAINT `fk_topic_creator` FOREIGN KEY (`creator_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_topic_fandom` FOREIGN KEY (`fandom_id`) REFERENCES `fandom` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discussion_topic`
--

LOCK TABLES `discussion_topic` WRITE;
/*!40000 ALTER TABLE `discussion_topic` DISABLE KEYS */;
/*!40000 ALTER TABLE `discussion_topic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_event_period` (`start_date`,`end_date`),
  KEY `fk_event_creator` (`created_by`),
  CONSTRAINT `fk_event_creator` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event`
--

LOCK TABLES `event` WRITE;
/*!40000 ALTER TABLE `event` DISABLE KEYS */;
/*!40000 ALTER TABLE `event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_booth`
--

DROP TABLE IF EXISTS `event_booth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_booth` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `artist_profile_id` bigint(20) unsigned NOT NULL,
  `booth_number` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `operating_hours` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attend_days` set('1','2','3','4','5','6','7') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_booth_event_artist` (`event_id`,`artist_profile_id`,`booth_number`),
  KEY `idx_booth_artist` (`artist_profile_id`),
  CONSTRAINT `fk_booth_artist` FOREIGN KEY (`artist_profile_id`) REFERENCES `artist_profile` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_booth_event` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_booth`
--

LOCK TABLES `event_booth` WRITE;
/*!40000 ALTER TABLE `event_booth` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_booth` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_pickup`
--

DROP TABLE IF EXISTS `event_pickup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_pickup` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_booth_id` bigint(20) unsigned NOT NULL,
  `qr_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pickup_status` enum('not_picked_up','picked_up','forfeited') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_picked_up',
  `picked_up_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `customer_order_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pickup_code_qr` (`qr_code`),
  UNIQUE KEY `uq_pickup_order` (`customer_order_id`),
  KEY `idx_pickup_booth` (`event_booth_id`),
  CONSTRAINT `fk_event_pickup_customer_order1` FOREIGN KEY (`customer_order_id`) REFERENCES `customer_order` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_pickup_booth` FOREIGN KEY (`event_booth_id`) REFERENCES `event_booth` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_pickup`
--

LOCK TABLES `event_pickup` WRITE;
/*!40000 ALTER TABLE `event_pickup` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_pickup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_proposal`
--

DROP TABLE IF EXISTS `event_proposal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_proposal` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `artist_profile_id` bigint(20) unsigned NOT NULL,
  `event_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_eventproposal_status` (`status`,`created_at`),
  KEY `idx_eventproposal_artist` (`artist_profile_id`),
  KEY `idx_eventproposal_admin` (`reviewed_by`),
  KEY `idx_eventproposal_event` (`event_id`),
  CONSTRAINT `fk_eventproposal_admin` FOREIGN KEY (`reviewed_by`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_eventproposal_artist` FOREIGN KEY (`artist_profile_id`) REFERENCES `artist_profile` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_eventproposal_event` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_proposal`
--

LOCK TABLES `event_proposal` WRITE;
/*!40000 ALTER TABLE `event_proposal` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_proposal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fandom`
--

DROP TABLE IF EXISTS `fandom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fandom` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fandom_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','pending','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `proposed_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fandom_name` (`fandom_name`),
  KEY `idx_fandom_status` (`status`),
  KEY `idx_fandom_proposer` (`proposed_by`),
  CONSTRAINT `fk_fandom_proposer` FOREIGN KEY (`proposed_by`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fandom`
--

LOCK TABLES `fandom` WRITE;
/*!40000 ALTER TABLE `fandom` DISABLE KEYS */;
/*!40000 ALTER TABLE `fandom` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fandom_character`
--

DROP TABLE IF EXISTS `fandom_character`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fandom_character` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fandom_id` bigint(20) unsigned NOT NULL,
  `character_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','pending','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `proposed_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_character_fandom_name` (`fandom_id`,`character_name`),
  KEY `idx_character_status` (`status`),
  KEY `idx_character_proposer` (`proposed_by`),
  CONSTRAINT `fk_character_fandom` FOREIGN KEY (`fandom_id`) REFERENCES `fandom` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_character_proposer` FOREIGN KEY (`proposed_by`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fandom_character`
--

LOCK TABLES `fandom_character` WRITE;
/*!40000 ALTER TABLE `fandom_character` DISABLE KEYS */;
/*!40000 ALTER TABLE `fandom_character` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fandom_member`
--

DROP TABLE IF EXISTS `fandom_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fandom_member` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fandom_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` enum('member','moderator') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `joined_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_member_fandom_user` (`fandom_id`,`user_id`),
  KEY `idx_member_user` (`user_id`),
  CONSTRAINT `fk_member_fandom` FOREIGN KEY (`fandom_id`) REFERENCES `fandom` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_member_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fandom_member`
--

LOCK TABLES `fandom_member` WRITE;
/*!40000 ALTER TABLE `fandom_member` DISABLE KEYS */;
/*!40000 ALTER TABLE `fandom_member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fandom_theming`
--

DROP TABLE IF EXISTS `fandom_theming`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fandom_theming` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fandom_id` bigint(20) unsigned NOT NULL,
  `theming_name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `primary_color` char(7) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'hex warna utama',
  `secondary_color` char(7) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'hex ujung gradasi',
  `accent_color` char(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'hex aksen (opsional)',
  `banner_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'gambar banner tema (opsional)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_theming_fandom_name` (`fandom_id`,`theming_name`),
  KEY `idx_theming_active` (`is_active`),
  KEY `idx_theming_created_by` (`created_by`),
  CONSTRAINT `fk_theming_created_by` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_theming_fandom` FOREIGN KEY (`fandom_id`) REFERENCES `fandom` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fandom_theming`
--

LOCK TABLES `fandom_theming` WRITE;
/*!40000 ALTER TABLE `fandom_theming` DISABLE KEYS */;
/*!40000 ALTER TABLE `fandom_theming` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favorite_fandom`
--

DROP TABLE IF EXISTS `favorite_fandom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `favorite_fandom` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `fandom_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_favorite_user_fandom` (`user_id`,`fandom_id`),
  KEY `idx_favorite_fandom` (`fandom_id`),
  CONSTRAINT `fk_favorite_fandom` FOREIGN KEY (`fandom_id`) REFERENCES `fandom` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_favorite_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorite_fandom`
--

LOCK TABLES `favorite_fandom` WRITE;
/*!40000 ALTER TABLE `favorite_fandom` DISABLE KEYS */;
/*!40000 ALTER TABLE `favorite_fandom` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `file_upload`
--

DROP TABLE IF EXISTS `file_upload`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `file_upload` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_table` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` bigint(20) unsigned NOT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_file_source` (`source_table`,`source_id`),
  KEY `idx_file_uploader` (`uploaded_by`),
  CONSTRAINT `fk_file_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `file_upload`
--

LOCK TABLES `file_upload` WRITE;
/*!40000 ALTER TABLE `file_upload` DISABLE KEYS */;
/*!40000 ALTER TABLE `file_upload` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchandise_type`
--

DROP TABLE IF EXISTS `merchandise_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchandise_type` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type_name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','pending','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `proposed_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_type_name` (`type_name`),
  KEY `idx_type_status` (`status`),
  KEY `idx_type_proposer` (`proposed_by`),
  CONSTRAINT `fk_type_proposer` FOREIGN KEY (`proposed_by`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merchandise_type`
--

LOCK TABLES `merchandise_type` WRITE;
/*!40000 ALTER TABLE `merchandise_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `merchandise_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_item`
--

DROP TABLE IF EXISTS `order_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_item` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `po_batch_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL,
  `subtotal` decimal(14,2) NOT NULL,
  `product_variant_id` bigint(20) unsigned DEFAULT NULL,
  `variant_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `variant_image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_item_order` (`customer_order_id`),
  KEY `idx_item_product` (`product_id`),
  KEY `idx_item_batch` (`po_batch_id`),
  KEY `idx_item_variant` (`product_variant_id`),
  CONSTRAINT `fk_item_batch` FOREIGN KEY (`po_batch_id`) REFERENCES `po_batch` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_item_order` FOREIGN KEY (`customer_order_id`) REFERENCES `customer_order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_item_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variant` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_item`
--

LOCK TABLES `order_item` WRITE;
/*!40000 ALTER TABLE `order_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_return`
--

DROP TABLE IF EXISTS `order_return`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_return` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_order_id` bigint(20) unsigned NOT NULL,
  `buyer_id` bigint(20) unsigned NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo_evidence` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `return_status` enum('submitted','processing','approved','rejected','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_return_order` (`customer_order_id`),
  KEY `idx_return_buyer` (`buyer_id`),
  CONSTRAINT `fk_return_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_return_order` FOREIGN KEY (`customer_order_id`) REFERENCES `customer_order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_return`
--

LOCK TABLES `order_return` WRITE;
/*!40000 ALTER TABLE `order_return` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_return` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ots_stock_log`
--

DROP TABLE IF EXISTS `ots_stock_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ots_stock_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `artist_profile_id` bigint(20) unsigned NOT NULL,
  `event_booth_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `event_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `booth_name` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `product_variant_id` bigint(20) unsigned DEFAULT NULL,
  `variant_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_otslog_artist` (`artist_profile_id`,`created_at`),
  KEY `idx_otslog_booth` (`event_booth_id`),
  KEY `idx_otslog_product` (`product_id`),
  KEY `idx_ots_variant` (`product_variant_id`),
  CONSTRAINT `fk_ots_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variant` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_otslog_artist` FOREIGN KEY (`artist_profile_id`) REFERENCES `artist_profile` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_otslog_booth` FOREIGN KEY (`event_booth_id`) REFERENCES `event_booth` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_otslog_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ots_stock_log`
--

LOCK TABLES `ots_stock_log` WRITE;
/*!40000 ALTER TABLE `ots_stock_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `ots_stock_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_order_id` bigint(20) unsigned NOT NULL,
  `payment_type` enum('dp','settlement','full','refund') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `payment_method` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `midtrans_order_id` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `midtrans_transaction_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `refund_method` enum('midtrans_api','manual_transfer') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `refund_proof` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` enum('pending','success','failed','expired','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `snap_token` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `snap_token_expires_at` datetime DEFAULT NULL,
  `midtrans_settled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_midtrans_order` (`midtrans_order_id`),
  UNIQUE KEY `uq_payment_midtrans_trx` (`midtrans_transaction_id`),
  KEY `idx_payment_order` (`customer_order_id`,`payment_type`),
  CONSTRAINT `fk_payment_order` FOREIGN KEY (`customer_order_id`) REFERENCES `customer_order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment`
--

LOCK TABLES `payment` WRITE;
/*!40000 ALTER TABLE `payment` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_setting`
--

DROP TABLE IF EXISTS `platform_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_setting` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fee_percentage` decimal(5,2) NOT NULL,
  `fee_minimum` decimal(12,2) NOT NULL DEFAULT 0.00,
  `penalty_voluntary` decimal(3,2) NOT NULL DEFAULT 0.20,
  `penalty_settlement_timeout` decimal(3,2) NOT NULL DEFAULT 0.20,
  `penalty_shipping_timeout` decimal(3,2) NOT NULL DEFAULT 0.30,
  `effective_from` datetime NOT NULL,
  `changed_by_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_setting_effective` (`effective_from`),
  KEY `idx_setting_changed_by` (`changed_by_id`),
  CONSTRAINT `fk_setting_changed_by` FOREIGN KEY (`changed_by_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_setting`
--

LOCK TABLES `platform_setting` WRITE;
/*!40000 ALTER TABLE `platform_setting` DISABLE KEYS */;
/*!40000 ALTER TABLE `platform_setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `po_batch`
--

DROP TABLE IF EXISTS `po_batch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `po_batch` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `price_po` decimal(12,2) NOT NULL,
  `payment_scheme` enum('dp','full') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'full',
  `dp_amount` decimal(12,2) DEFAULT NULL,
  `min_order` int(10) unsigned DEFAULT NULL,
  `max_slot` int(10) unsigned DEFAULT NULL,
  `opens_at` datetime NOT NULL,
  `closes_at` datetime NOT NULL,
  `estimated_month` date DEFAULT NULL,
  `batch_status` enum('open','closed','in_production','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `vote_po_announced_at` datetime DEFAULT NULL,
  `estimate_change_reason` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimate_reminded_for` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_batch_product` (`product_id`,`batch_status`),
  CONSTRAINT `fk_batch_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po_batch`
--

LOCK TABLES `po_batch` WRITE;
/*!40000 ALTER TABLE `po_batch` DISABLE KEYS */;
/*!40000 ALTER TABLE `po_batch` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `artist_profile_id` bigint(20) unsigned NOT NULL,
  `fandom_character_id` bigint(20) unsigned NOT NULL,
  `merchandise_type_id` bigint(20) unsigned NOT NULL,
  `design_option_id` bigint(20) unsigned DEFAULT NULL,
  `product_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `size` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stock` int(10) unsigned NOT NULL DEFAULT 0,
  `availability_type` enum('ready_stock','pre_order') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ready_stock',
  `allow_event_pickup` tinyint(1) NOT NULL DEFAULT 0,
  `product_status` enum('active','inactive','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_product_artist` (`artist_profile_id`),
  KEY `idx_product_character` (`fandom_character_id`),
  KEY `idx_product_type` (`merchandise_type_id`),
  KEY `idx_product_option_design` (`design_option_id`),
  KEY `idx_product_filter` (`availability_type`,`product_status`,`price`),
  CONSTRAINT `fk_product_artist` FOREIGN KEY (`artist_profile_id`) REFERENCES `artist_profile` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_product_character` FOREIGN KEY (`fandom_character_id`) REFERENCES `fandom_character` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_product_option_design` FOREIGN KEY (`design_option_id`) REFERENCES `design_option` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_product_type` FOREIGN KEY (`merchandise_type_id`) REFERENCES `merchandise_type` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product`
--

LOCK TABLES `product` WRITE;
/*!40000 ALTER TABLE `product` DISABLE KEYS */;
/*!40000 ALTER TABLE `product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_review`
--

DROP TABLE IF EXISTS `product_review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_review` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_preview_order_product` (`customer_order_id`,`product_id`),
  KEY `idx_preview_product` (`product_id`),
  KEY `idx_preview_reviewer` (`reviewer_id`),
  CONSTRAINT `fk_preview_order` FOREIGN KEY (`customer_order_id`) REFERENCES `customer_order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_preview_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_preview_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_review`
--

LOCK TABLES `product_review` WRITE;
/*!40000 ALTER TABLE `product_review` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_review` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variant`
--

DROP TABLE IF EXISTS `product_variant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variant` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_variant_product` (`product_id`,`is_active`),
  CONSTRAINT `fk_variant_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variant`
--

LOCK TABLES `product_variant` WRITE;
/*!40000 ALTER TABLE `product_variant` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_variant` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_view`
--

DROP TABLE IF EXISTS `product_view`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_view` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `viewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_view_user` (`user_id`,`viewed_at`),
  KEY `idx_view_product` (`product_id`),
  CONSTRAINT `fk_view_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_view_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_view`
--

LOCK TABLES `product_view` WRITE;
/*!40000 ALTER TABLE `product_view` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_view` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `refund_bank_account`
--

DROP TABLE IF EXISTS `refund_bank_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `refund_bank_account` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint(20) unsigned NOT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_refund_bank_payment` (`payment_id`),
  CONSTRAINT `fk_refund_bank_payment` FOREIGN KEY (`payment_id`) REFERENCES `payment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `refund_bank_account`
--

LOCK TABLES `refund_bank_account` WRITE;
/*!40000 ALTER TABLE `refund_bank_account` DISABLE KEYS */;
/*!40000 ALTER TABLE `refund_bank_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `review`
--

DROP TABLE IF EXISTS `review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `review` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_order_id` bigint(20) unsigned NOT NULL,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `reviewee_id` bigint(20) unsigned NOT NULL,
  `direction` enum('buyer_to_artist','artist_to_buyer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_review_order_reviewer` (`customer_order_id`,`reviewer_id`),
  KEY `idx_review_reviewee` (`reviewee_id`),
  KEY `idx_review_reviewer` (`reviewer_id`),
  CONSTRAINT `fk_review_order` FOREIGN KEY (`customer_order_id`) REFERENCES `customer_order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_review_reviewee` FOREIGN KEY (`reviewee_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_review_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review`
--

LOCK TABLES `review` WRITE;
/*!40000 ALTER TABLE `review` DISABLE KEYS */;
/*!40000 ALTER TABLE `review` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search_history`
--

DROP TABLE IF EXISTS `search_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `keyword` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fandom_id` bigint(20) unsigned DEFAULT NULL,
  `fandom_character_id` bigint(20) unsigned DEFAULT NULL,
  `merchandise_type_id` bigint(20) unsigned DEFAULT NULL,
  `searched_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_history_user` (`user_id`,`searched_at`),
  KEY `idx_history_fandom` (`fandom_id`),
  KEY `idx_history_character` (`fandom_character_id`),
  KEY `idx_history_type` (`merchandise_type_id`),
  CONSTRAINT `fk_history_character` FOREIGN KEY (`fandom_character_id`) REFERENCES `fandom_character` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_history_fandom` FOREIGN KEY (`fandom_id`) REFERENCES `fandom` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_history_type` FOREIGN KEY (`merchandise_type_id`) REFERENCES `merchandise_type` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_history`
--

LOCK TABLES `search_history` WRITE;
/*!40000 ALTER TABLE `search_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `search_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topic_message`
--

DROP TABLE IF EXISTS `topic_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `topic_message` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `discussion_topic_id` bigint(20) unsigned NOT NULL,
  `parent_message_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `message_body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attached_product_id` bigint(20) unsigned DEFAULT NULL,
  `attached_artist_id` bigint(20) unsigned DEFAULT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_message_topic` (`discussion_topic_id`,`sent_at`),
  KEY `idx_message_parent` (`parent_message_id`),
  KEY `idx_message_group_user` (`user_id`),
  KEY `idx_message_attachment_product` (`attached_product_id`),
  KEY `idx_message_attachment_artist` (`attached_artist_id`),
  CONSTRAINT `fk_message_attachment_artist` FOREIGN KEY (`attached_artist_id`) REFERENCES `artist_profile` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_message_attachment_product` FOREIGN KEY (`attached_product_id`) REFERENCES `product` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_message_group_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_message_parent` FOREIGN KEY (`parent_message_id`) REFERENCES `topic_message` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_message_topic` FOREIGN KEY (`discussion_topic_id`) REFERENCES `discussion_topic` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topic_message`
--

LOCK TABLES `topic_message` WRITE;
/*!40000 ALTER TABLE `topic_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `topic_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `track_transaksi`
--

DROP TABLE IF EXISTS `track_transaksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `track_transaksi` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `customer_order_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `notification_type` enum('order','payment','po_settlement','event_pickup','cancellation','custom','design_poll','withdrawal','attribute','community') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `email_status` enum('pending','sent','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_track_user` (`user_id`,`is_read`),
  KEY `idx_track_email` (`email_status`),
  KEY `idx_track_order` (`customer_order_id`),
  CONSTRAINT `fk_track_order` FOREIGN KEY (`customer_order_id`) REFERENCES `customer_order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_track_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `track_transaksi`
--

LOCK TABLES `track_transaksi` WRITE;
/*!40000 ALTER TABLE `track_transaksi` DISABLE KEYS */;
/*!40000 ALTER TABLE `track_transaksi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','artist','buyer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'buyer',
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `rating_as_buyer` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count_as_buyer` int(10) unsigned NOT NULL DEFAULT 0,
  `theming_mode` enum('auto','manual','off') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto',
  `active_theming_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_email` (`email`),
  KEY `idx_user_role` (`role`),
  KEY `idx_user_active_theming` (`active_theming_id`),
  CONSTRAINT `fk_user_active_theming` FOREIGN KEY (`active_theming_id`) REFERENCES `fandom_theming` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `withdrawal`
--

DROP TABLE IF EXISTS `withdrawal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdrawal` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `artist_profile_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `bank_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_account_number` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `withdrawal_status` enum('submitted','processing','completed','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `transfer_proof` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rejection_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'alasan admin menolak penarikan (dilihat artis)',
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_withdrawal_artist` (`artist_profile_id`,`withdrawal_status`),
  KEY `idx_withdrawal_admin` (`processed_by`),
  CONSTRAINT `fk_withdrawal_admin` FOREIGN KEY (`processed_by`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_withdrawal_artist` FOREIGN KEY (`artist_profile_id`) REFERENCES `artist_profile` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `withdrawal`
--

LOCK TABLES `withdrawal` WRITE;
/*!40000 ALTER TABLE `withdrawal` DISABLE KEYS */;
/*!40000 ALTER TABLE `withdrawal` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-25  9:38:55
