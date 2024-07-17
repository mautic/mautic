-- MariaDB dump 10.19  Distrib 10.5.21-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: db    Database: db
-- ------------------------------------------------------
-- Server version	10.3.39-MariaDB-1:10.3.39+maria~ubu2004-log

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
-- Table structure for table `asset_downloads`
--

DROP TABLE IF EXISTS `asset_downloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_downloads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` int(10) unsigned DEFAULT NULL,
  `ip_id` int(10) unsigned DEFAULT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `email_id` int(10) unsigned DEFAULT NULL,
  `date_download` datetime NOT NULL,
  `code` int(11) NOT NULL,
  `referer` longtext DEFAULT NULL,
  `tracking_id` varchar(191) NOT NULL,
  `source` varchar(191) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `utm_campaign` varchar(191) DEFAULT NULL,
  `utm_content` varchar(191) DEFAULT NULL,
  `utm_medium` varchar(191) DEFAULT NULL,
  `utm_source` varchar(191) DEFAULT NULL,
  `utm_term` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A6494C8F5DA1941` (`asset_id`),
  KEY `IDX_A6494C8FA03F5E9F` (`ip_id`),
  KEY `IDX_A6494C8F55458D` (`lead_id`),
  KEY `IDX_A6494C8FA832C1C9` (`email_id`),
  KEY `download_tracking_search` (`tracking_id`),
  KEY `download_source_search` (`source`,`source_id`),
  KEY `asset_date_download` (`date_download`),
  CONSTRAINT `FK_A6494C8F55458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_A6494C8F5DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_A6494C8FA03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `ip_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_A6494C8FA832C1C9` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_downloads`
--

LOCK TABLES `asset_downloads` WRITE;
/*!40000 ALTER TABLE `asset_downloads` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_downloads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `alias` varchar(191) NOT NULL,
  `storage_location` varchar(191) DEFAULT NULL,
  `path` varchar(191) DEFAULT NULL,
  `remote_path` longtext DEFAULT NULL,
  `original_file_name` longtext DEFAULT NULL,
  `lang` varchar(191) NOT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `download_count` int(11) NOT NULL,
  `unique_download_count` int(11) NOT NULL,
  `revision` int(11) NOT NULL,
  `extension` varchar(191) DEFAULT NULL,
  `mime` varchar(191) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `disallow` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_79D17D8E12469DE2` (`category_id`),
  KEY `asset_alias_search` (`alias`),
  CONSTRAINT `FK_79D17D8E12469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
INSERT INTO `assets` VALUES (2,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'@TOCHANGE: Asset1 Title',NULL,'asset1','local','fdb8e28357b02d12d068de3e5661832e21bc08ec.doc',NULL,'@TOCHANGE: Asset1 Original File Name','en',NULL,NULL,1,1,1,NULL,NULL,NULL,1);
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(191) NOT NULL,
  `bundle` varchar(50) NOT NULL,
  `object` varchar(50) NOT NULL,
  `object_id` bigint(20) unsigned NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `date_added` datetime NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `object_search` (`object`,`object_id`),
  KEY `timeline_search` (`bundle`,`object`,`action`,`object_id`),
  KEY `date_added_index` (`date_added`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (12,0,'System','lead','segment',65,'update','a:1:{s:5:\"alias\";a:2:{i:0;s:11:\"lead-list-6\";i:1;s:12:\"lead-list-61\";}}','2024-07-17 23:03:26','127.0.0.1');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bundle_grapesjsbuilder`
--

DROP TABLE IF EXISTS `bundle_grapesjsbuilder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bundle_grapesjsbuilder` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email_id` int(10) unsigned DEFAULT NULL,
  `custom_mjml` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_56A1EB07A832C1C9` (`email_id`),
  CONSTRAINT `FK_56A1EB07A832C1C9` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bundle_grapesjsbuilder`
--

LOCK TABLES `bundle_grapesjsbuilder` WRITE;
/*!40000 ALTER TABLE `bundle_grapesjsbuilder` DISABLE KEYS */;
/*!40000 ALTER TABLE `bundle_grapesjsbuilder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_items`
--

DROP TABLE IF EXISTS `cache_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_items` (
  `item_id` varbinary(255) NOT NULL,
  `item_data` longblob NOT NULL,
  `item_lifetime` int(10) unsigned DEFAULT NULL,
  `item_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_items`
--

LOCK TABLES `cache_items` WRITE;
/*!40000 ALTER TABLE `cache_items` DISABLE KEYS */;
INSERT INTO `cache_items` VALUES ('segment.100.lead','\0\0\0\0',NULL,1721257410),('segment.101.lead','\0\0\0\0',NULL,1721257410),('segment.102.lead','\0\0\0\0',NULL,1721257410),('segment.103.lead','\0\0\0\0',NULL,1721257410),('segment.104.lead','\0\0\0\0',NULL,1721257410),('segment.105.lead','\0\0\06',NULL,1721257408),('segment.107.lead','\0\0\0',NULL,1721257407),('segment.108.lead','\0\0\0',NULL,1721257401),('segment.109.lead','\0\0\04',NULL,1721257406),('segment.110.lead','\0\0\0',NULL,1721257403),('segment.111.lead','\0\0\0',NULL,1721257404),('segment.112.lead','\0\0\0',NULL,1721257405),('segment.113.lead','\0\0\04',NULL,1721257411),('segment.114.lead','\0\0\0',NULL,1721257413),('segment.115.lead','\0\0\02',NULL,1721257393),('segment.116.lead','\0\0\0',NULL,1721257394),('segment.117.lead','\0\0\0',NULL,1721257395),('segment.60.lead','\0\0\0\0',NULL,1721257406),('segment.61.lead','\0\0\0\0',NULL,1721257406),('segment.62.lead','\0\0\0\0',NULL,1721257406),('segment.63.lead','\0\0\0\0',NULL,1721257406),('segment.64.lead','\0\0\0\0',NULL,1721257406),('segment.65.lead','\0\0\0\0',NULL,1721257406),('segment.66.lead','\0\0\0\0',NULL,1721257406),('segment.67.lead','\0\0\0',NULL,1721257380),('segment.68.lead','\0\0\0',NULL,1721257381),('segment.69.lead','\0\0\0',NULL,1721257382),('segment.70.lead','\0\0\0',NULL,1721257410),('segment.71.lead','\0\0\0',NULL,1721257383),('segment.72.lead','\0\0\05',NULL,1721257384),('segment.73.lead','\0\0\0 ',NULL,1721257386),('segment.74.lead','\0\0\0\0',NULL,1721257386),('segment.75.lead','\0\0\0',NULL,1721257387),('segment.86.lead','\0\0\0',NULL,1721257388),('segment.87.lead','\0\0\0',NULL,1721257389),('segment.89.lead','\0\0\0',NULL,1721257390),('segment.90.lead','\0\0\0',NULL,1721257391),('segment.92.lead','\0\0\0\0',NULL,1721257410),('segment.93.lead','\0\0\0\0',NULL,1721257410),('segment.94.lead','\0\0\0\0',NULL,1721257410),('segment.95.lead','\0\0\0\0',NULL,1721257410),('segment.96.lead','\0\0\0\0',NULL,1721257410),('segment.97.lead','\0\0\0\0',NULL,1721257410),('segment.98.lead','\0\0\0\0',NULL,1721257410),('segment.99.lead','\0\0\0\0',NULL,1721257410);
/*!40000 ALTER TABLE `cache_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_events`
--

DROP TABLE IF EXISTS `campaign_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaign_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `event_order` int(11) NOT NULL,
  `properties` longtext NOT NULL COMMENT '(DC2Type:array)',
  `deleted` datetime DEFAULT NULL,
  `trigger_date` datetime DEFAULT NULL,
  `trigger_interval` int(11) DEFAULT NULL,
  `trigger_interval_unit` varchar(1) DEFAULT NULL,
  `trigger_hour` time DEFAULT NULL,
  `trigger_restricted_start_hour` time DEFAULT NULL,
  `trigger_restricted_stop_hour` time DEFAULT NULL,
  `trigger_restricted_dow` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `trigger_mode` varchar(10) DEFAULT NULL,
  `decision_path` varchar(191) DEFAULT NULL,
  `temp_id` varchar(191) DEFAULT NULL,
  `channel` varchar(191) DEFAULT NULL,
  `channel_id` int(11) DEFAULT NULL,
  `failed_count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_8EC42EE7F639F774` (`campaign_id`),
  KEY `IDX_8EC42EE7727ACA70` (`parent_id`),
  KEY `campaign_event_search` (`type`,`event_type`),
  KEY `campaign_event_type` (`event_type`),
  KEY `campaign_event_channel` (`channel`,`channel_id`),
  CONSTRAINT `FK_8EC42EE7727ACA70` FOREIGN KEY (`parent_id`) REFERENCES `campaign_events` (`id`),
  CONSTRAINT `FK_8EC42EE7F639F774` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_events`
--

LOCK TABLES `campaign_events` WRITE;
/*!40000 ALTER TABLE `campaign_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_form_xref`
--

DROP TABLE IF EXISTS `campaign_form_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaign_form_xref` (
  `campaign_id` int(10) unsigned NOT NULL,
  `form_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`campaign_id`,`form_id`),
  KEY `IDX_3048A8B25FF69B7D` (`form_id`),
  CONSTRAINT `FK_3048A8B25FF69B7D` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_3048A8B2F639F774` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_form_xref`
--

LOCK TABLES `campaign_form_xref` WRITE;
/*!40000 ALTER TABLE `campaign_form_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_form_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_lead_event_failed_log`
--

DROP TABLE IF EXISTS `campaign_lead_event_failed_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaign_lead_event_failed_log` (
  `log_id` bigint(20) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  `reason` longtext DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `campaign_event_failed_date` (`date_added`),
  CONSTRAINT `FK_E50614D2EA675D86` FOREIGN KEY (`log_id`) REFERENCES `campaign_lead_event_log` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_lead_event_failed_log`
--

LOCK TABLES `campaign_lead_event_failed_log` WRITE;
/*!40000 ALTER TABLE `campaign_lead_event_failed_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_lead_event_failed_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_lead_event_log`
--

DROP TABLE IF EXISTS `campaign_lead_event_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaign_lead_event_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(10) unsigned NOT NULL,
  `lead_id` bigint(20) unsigned NOT NULL,
  `campaign_id` int(10) unsigned DEFAULT NULL,
  `ip_id` int(10) unsigned DEFAULT NULL,
  `rotation` int(11) NOT NULL,
  `date_triggered` datetime DEFAULT NULL,
  `is_scheduled` tinyint(1) NOT NULL,
  `trigger_date` datetime DEFAULT NULL,
  `system_triggered` tinyint(1) NOT NULL,
  `metadata` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `channel` varchar(191) DEFAULT NULL,
  `channel_id` int(11) DEFAULT NULL,
  `non_action_path_taken` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_rotation` (`event_id`,`lead_id`,`rotation`),
  KEY `IDX_B7420BA171F7E88B` (`event_id`),
  KEY `IDX_B7420BA155458D` (`lead_id`),
  KEY `IDX_B7420BA1F639F774` (`campaign_id`),
  KEY `IDX_B7420BA1A03F5E9F` (`ip_id`),
  KEY `campaign_event_upcoming_search` (`is_scheduled`,`lead_id`),
  KEY `campaign_event_schedule_counts` (`campaign_id`,`is_scheduled`,`trigger_date`),
  KEY `campaign_date_triggered` (`date_triggered`),
  KEY `campaign_leads` (`lead_id`,`campaign_id`,`rotation`),
  KEY `campaign_log_channel` (`channel`,`channel_id`,`lead_id`),
  KEY `campaign_actions` (`campaign_id`,`event_id`,`date_triggered`),
  KEY `campaign_stats` (`campaign_id`,`date_triggered`,`event_id`,`non_action_path_taken`),
  KEY `campaign_trigger_date_order` (`trigger_date`),
  CONSTRAINT `FK_B7420BA155458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_B7420BA171F7E88B` FOREIGN KEY (`event_id`) REFERENCES `campaign_events` (`id`),
  CONSTRAINT `FK_B7420BA1A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `ip_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_B7420BA1F639F774` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_lead_event_log`
--

LOCK TABLES `campaign_lead_event_log` WRITE;
/*!40000 ALTER TABLE `campaign_lead_event_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_lead_event_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_leadlist_xref`
--

DROP TABLE IF EXISTS `campaign_leadlist_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaign_leadlist_xref` (
  `campaign_id` int(10) unsigned NOT NULL,
  `leadlist_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`campaign_id`,`leadlist_id`),
  KEY `IDX_6480052EB9FC8874` (`leadlist_id`),
  CONSTRAINT `FK_6480052EB9FC8874` FOREIGN KEY (`leadlist_id`) REFERENCES `lead_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6480052EF639F774` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_leadlist_xref`
--

LOCK TABLES `campaign_leadlist_xref` WRITE;
/*!40000 ALTER TABLE `campaign_leadlist_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_leadlist_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_leads`
--

DROP TABLE IF EXISTS `campaign_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaign_leads` (
  `campaign_id` int(10) unsigned NOT NULL,
  `lead_id` bigint(20) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  `manually_removed` tinyint(1) NOT NULL,
  `manually_added` tinyint(1) NOT NULL,
  `date_last_exited` datetime DEFAULT NULL,
  `rotation` int(11) NOT NULL,
  PRIMARY KEY (`campaign_id`,`lead_id`),
  KEY `IDX_5995213D55458D` (`lead_id`),
  KEY `campaign_leads_date_added` (`date_added`),
  KEY `campaign_leads_date_exited` (`date_last_exited`),
  KEY `campaign_leads` (`campaign_id`,`manually_removed`,`lead_id`,`rotation`),
  CONSTRAINT `FK_5995213D55458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_5995213DF639F774` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_leads`
--

LOCK TABLES `campaign_leads` WRITE;
/*!40000 ALTER TABLE `campaign_leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_summary`
--

DROP TABLE IF EXISTS `campaign_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaign_summary` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` int(10) unsigned DEFAULT NULL,
  `event_id` int(10) unsigned NOT NULL,
  `date_triggered` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `scheduled_count` int(11) NOT NULL,
  `triggered_count` int(11) NOT NULL,
  `non_action_path_taken_count` int(11) NOT NULL,
  `failed_count` int(11) NOT NULL,
  `log_counts_processed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_event_date_triggered` (`campaign_id`,`event_id`,`date_triggered`),
  KEY `IDX_6692FA4FF639F774` (`campaign_id`),
  KEY `IDX_6692FA4F71F7E88B` (`event_id`),
  CONSTRAINT `FK_6692FA4F71F7E88B` FOREIGN KEY (`event_id`) REFERENCES `campaign_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6692FA4FF639F774` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_summary`
--

LOCK TABLES `campaign_summary` WRITE;
/*!40000 ALTER TABLE `campaign_summary` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_summary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaigns`
--

DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaigns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `canvas_settings` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `allow_restart` tinyint(1) NOT NULL,
  `deleted` datetime DEFAULT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `IDX_E373747012469DE2` (`category_id`),
  CONSTRAINT `FK_E373747012469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaigns`
--

LOCK TABLES `campaigns` WRITE;
/*!40000 ALTER TABLE `campaigns` DISABLE KEYS */;
INSERT INTO `campaigns` VALUES (2,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Campaign A',NULL,NULL,NULL,'a:2:{s:5:\"nodes\";a:2:{i:0;a:3:{s:2:\"id\";s:3:\"148\";s:9:\"positionX\";s:3:\"760\";s:9:\"positionY\";s:3:\"155\";}i:1;a:3:{s:2:\"id\";s:5:\"lists\";s:9:\"positionX\";s:3:\"860\";s:9:\"positionY\";s:2:\"50\";}}s:11:\"connections\";a:1:{i:0;a:3:{s:8:\"sourceId\";s:5:\"lists\";s:8:\"targetId\";s:3:\"148\";s:7:\"anchors\";a:2:{s:6:\"source\";s:10:\"leadsource\";s:6:\"target\";s:3:\"top\";}}}}',0,NULL,1);
/*!40000 ALTER TABLE `campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `alias` varchar(191) NOT NULL,
  `color` varchar(7) DEFAULT NULL,
  `bundle` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `category_alias_search` (`alias`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (10,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Segment Test Category 1',NULL,'segment-test-category-1',NULL,'segment'),(11,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Segment Test Category 2',NULL,'segment-test-category-2',NULL,'segment'),(12,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Form Test Category 1',NULL,'segment-form-category-1',NULL,'form'),(13,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Form Test Category 2',NULL,'segment-form-category-2',NULL,'form'),(14,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Asset Test Category 1',NULL,'segment-asset-category-1',NULL,'asset'),(15,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Asset Test Category 2',NULL,'segment-asset-category-2',NULL,'asset'),(16,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Email Test Category 1',NULL,'segment-email-category-1',NULL,'email'),(17,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Email Test Category 2',NULL,'segment-email-category-2',NULL,'email'),(18,1,'2024-07-17 23:03:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Events',NULL,'events',NULL,'page');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `channel_url_trackables`
--

DROP TABLE IF EXISTS `channel_url_trackables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `channel_url_trackables` (
  `channel_id` int(11) NOT NULL,
  `redirect_id` bigint(20) unsigned NOT NULL,
  `channel` varchar(191) NOT NULL,
  `hits` int(11) NOT NULL,
  `unique_hits` int(11) NOT NULL,
  PRIMARY KEY (`redirect_id`,`channel_id`),
  KEY `channel_url_trackable_search` (`channel`,`channel_id`),
  CONSTRAINT `FK_2F81A41DB42D874D` FOREIGN KEY (`redirect_id`) REFERENCES `page_redirects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `channel_url_trackables`
--

LOCK TABLES `channel_url_trackables` WRITE;
/*!40000 ALTER TABLE `channel_url_trackables` DISABLE KEYS */;
/*!40000 ALTER TABLE `channel_url_trackables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `social_cache` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `score` int(11) DEFAULT NULL,
  `companyemail` varchar(191) DEFAULT NULL,
  `companyaddress1` varchar(191) DEFAULT NULL,
  `companyaddress2` varchar(191) DEFAULT NULL,
  `companyphone` varchar(191) DEFAULT NULL,
  `companycity` varchar(191) DEFAULT NULL,
  `companystate` varchar(191) DEFAULT NULL,
  `companyzipcode` varchar(191) DEFAULT NULL,
  `companycountry` varchar(191) DEFAULT NULL,
  `companyname` varchar(191) DEFAULT NULL,
  `companywebsite` varchar(191) DEFAULT NULL,
  `companyindustry` varchar(191) DEFAULT NULL,
  `companydescription` longtext DEFAULT NULL,
  `companynumber_of_employees` double DEFAULT NULL,
  `companyfax` varchar(191) DEFAULT NULL,
  `companyannual_revenue` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_8244AA3A7E3C61F9` (`owner_id`),
  KEY `companynumber_of_employees_search` (`companynumber_of_employees`),
  KEY `companyfax_search` (`companyfax`),
  KEY `companyannual_revenue_search` (`companyannual_revenue`),
  KEY `company_filter` (`companyname`,`companyemail`),
  KEY `company_match` (`companyname`,`companycity`,`companycountry`,`companystate`),
  CONSTRAINT `FK_8244AA3A7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'a:0:{}',0,NULL,NULL,NULL,NULL,'Boston','Massachusetts',NULL,'United States','Mautic',NULL,'Software',NULL,NULL,NULL,NULL),(6,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'a:0:{}',0,NULL,NULL,NULL,NULL,'Cupertino','California',NULL,'United states','Apple',NULL,'Hardware',NULL,NULL,NULL,NULL),(7,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'a:0:{}',0,NULL,NULL,NULL,NULL,'Seattle','Washington',NULL,'United States','Amazon',NULL,'Goods',NULL,NULL,NULL,NULL),(8,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'a:0:{}',0,NULL,NULL,NULL,NULL,'Houston','Texas',NULL,'United States','HostGator',NULL,'Software',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies_leads`
--

DROP TABLE IF EXISTS `companies_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies_leads` (
  `company_id` int(11) NOT NULL,
  `lead_id` bigint(20) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  `is_primary` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`company_id`,`lead_id`),
  KEY `IDX_F4190AB655458D` (`lead_id`),
  CONSTRAINT `FK_F4190AB655458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_F4190AB6979B1AD6` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies_leads`
--

LOCK TABLES `companies_leads` WRITE;
/*!40000 ALTER TABLE `companies_leads` DISABLE KEYS */;
INSERT INTO `companies_leads` VALUES (5,57,'2024-07-17 23:02:59',1),(5,67,'2024-07-17 23:02:59',1),(5,77,'2024-07-17 23:02:59',1),(5,87,'2024-07-17 23:02:59',1),(5,97,'2024-07-17 23:02:59',1),(5,107,'2024-07-17 23:02:59',1),(6,58,'2024-07-17 23:02:59',1),(6,68,'2024-07-17 23:02:59',1),(6,78,'2024-07-17 23:02:59',1),(6,88,'2024-07-17 23:02:59',1),(6,98,'2024-07-17 23:02:59',1),(6,108,'2024-07-17 23:02:59',1),(7,59,'2024-07-17 23:02:59',1),(7,69,'2024-07-17 23:02:59',1),(7,79,'2024-07-17 23:02:59',1),(7,89,'2024-07-17 23:02:59',1),(7,99,'2024-07-17 23:02:59',1),(7,109,'2024-07-17 23:02:59',1),(8,60,'2024-07-17 23:02:59',1),(8,70,'2024-07-17 23:02:59',1),(8,80,'2024-07-17 23:02:59',1),(8,90,'2024-07-17 23:02:59',1),(8,100,'2024-07-17 23:02:59',1),(8,110,'2024-07-17 23:02:59',1);
/*!40000 ALTER TABLE `companies_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_export_scheduler`
--

DROP TABLE IF EXISTS `contact_export_scheduler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_export_scheduler` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `scheduled_datetime` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `data` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_AC0A03CA76ED395` (`user_id`),
  CONSTRAINT `FK_AC0A03CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_export_scheduler`
--

LOCK TABLES `contact_export_scheduler` WRITE;
/*!40000 ALTER TABLE `contact_export_scheduler` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_export_scheduler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_merge_records`
--

DROP TABLE IF EXISTS `contact_merge_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_merge_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` bigint(20) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  `merged_id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D9B4F2BFE7A1254A` (`contact_id`),
  KEY `contact_merge_date_added` (`date_added`),
  KEY `contact_merge_ids` (`merged_id`),
  CONSTRAINT `FK_D9B4F2BFE7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_merge_records`
--

LOCK TABLES `contact_merge_records` WRITE;
/*!40000 ALTER TABLE `contact_merge_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_merge_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dynamic_content`
--

DROP TABLE IF EXISTS `dynamic_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dynamic_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `translation_parent_id` int(10) unsigned DEFAULT NULL,
  `variant_parent_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `sent_count` int(11) NOT NULL,
  `content` longtext DEFAULT NULL,
  `utm_tags` longtext DEFAULT NULL COMMENT '(DC2Type:json)',
  `lang` varchar(191) NOT NULL,
  `variant_settings` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `variant_start_date` datetime DEFAULT NULL,
  `filters` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `is_campaign_based` tinyint(1) NOT NULL DEFAULT 1,
  `slot_name` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_20B9DEB212469DE2` (`category_id`),
  KEY `IDX_20B9DEB29091A2FB` (`translation_parent_id`),
  KEY `IDX_20B9DEB291861123` (`variant_parent_id`),
  KEY `is_campaign_based_index` (`is_campaign_based`),
  KEY `slot_name_index` (`slot_name`),
  CONSTRAINT `FK_20B9DEB212469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_20B9DEB29091A2FB` FOREIGN KEY (`translation_parent_id`) REFERENCES `dynamic_content` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_20B9DEB291861123` FOREIGN KEY (`variant_parent_id`) REFERENCES `dynamic_content` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dynamic_content`
--

LOCK TABLES `dynamic_content` WRITE;
/*!40000 ALTER TABLE `dynamic_content` DISABLE KEYS */;
/*!40000 ALTER TABLE `dynamic_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dynamic_content_lead_data`
--

DROP TABLE IF EXISTS `dynamic_content_lead_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dynamic_content_lead_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `dynamic_content_id` int(10) unsigned DEFAULT NULL,
  `date_added` datetime DEFAULT NULL,
  `slot` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_515B221B55458D` (`lead_id`),
  KEY `IDX_515B221BD9D0CD7` (`dynamic_content_id`),
  CONSTRAINT `FK_515B221B55458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_515B221BD9D0CD7` FOREIGN KEY (`dynamic_content_id`) REFERENCES `dynamic_content` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dynamic_content_lead_data`
--

LOCK TABLES `dynamic_content_lead_data` WRITE;
/*!40000 ALTER TABLE `dynamic_content_lead_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `dynamic_content_lead_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dynamic_content_stats`
--

DROP TABLE IF EXISTS `dynamic_content_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dynamic_content_stats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dynamic_content_id` int(10) unsigned DEFAULT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `date_sent` datetime NOT NULL,
  `source` varchar(191) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `tokens` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `sent_count` int(11) DEFAULT NULL,
  `last_sent` datetime DEFAULT NULL,
  `sent_details` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_E48FBF80D9D0CD7` (`dynamic_content_id`),
  KEY `IDX_E48FBF8055458D` (`lead_id`),
  KEY `stat_dynamic_content_search` (`dynamic_content_id`,`lead_id`),
  KEY `stat_dynamic_content_source_search` (`source`,`source_id`),
  CONSTRAINT `FK_E48FBF8055458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_E48FBF80D9D0CD7` FOREIGN KEY (`dynamic_content_id`) REFERENCES `dynamic_content` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dynamic_content_stats`
--

LOCK TABLES `dynamic_content_stats` WRITE;
/*!40000 ALTER TABLE `dynamic_content_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `dynamic_content_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_assets_xref`
--

DROP TABLE IF EXISTS `email_assets_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_assets_xref` (
  `email_id` int(10) unsigned NOT NULL,
  `asset_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`email_id`,`asset_id`),
  KEY `IDX_CA3157785DA1941` (`asset_id`),
  CONSTRAINT `FK_CA3157785DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_CA315778A832C1C9` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_assets_xref`
--

LOCK TABLES `email_assets_xref` WRITE;
/*!40000 ALTER TABLE `email_assets_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_assets_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_copies`
--

DROP TABLE IF EXISTS `email_copies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_copies` (
  `id` varchar(32) NOT NULL,
  `date_created` datetime NOT NULL,
  `body` longtext DEFAULT NULL,
  `body_text` longtext DEFAULT NULL,
  `subject` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_copies`
--

LOCK TABLES `email_copies` WRITE;
/*!40000 ALTER TABLE `email_copies` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_copies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_list_excluded`
--

DROP TABLE IF EXISTS `email_list_excluded`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_list_excluded` (
  `email_id` int(10) unsigned NOT NULL,
  `leadlist_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`email_id`,`leadlist_id`),
  KEY `IDX_3D3C217BB9FC8874` (`leadlist_id`),
  CONSTRAINT `FK_3D3C217BA832C1C9` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_3D3C217BB9FC8874` FOREIGN KEY (`leadlist_id`) REFERENCES `lead_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_list_excluded`
--

LOCK TABLES `email_list_excluded` WRITE;
/*!40000 ALTER TABLE `email_list_excluded` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_list_excluded` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_list_xref`
--

DROP TABLE IF EXISTS `email_list_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_list_xref` (
  `email_id` int(10) unsigned NOT NULL,
  `leadlist_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`email_id`,`leadlist_id`),
  KEY `IDX_2E24F01CB9FC8874` (`leadlist_id`),
  CONSTRAINT `FK_2E24F01CA832C1C9` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2E24F01CB9FC8874` FOREIGN KEY (`leadlist_id`) REFERENCES `lead_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_list_xref`
--

LOCK TABLES `email_list_xref` WRITE;
/*!40000 ALTER TABLE `email_list_xref` DISABLE KEYS */;
INSERT INTO `email_list_xref` VALUES (5,67),(6,67);
/*!40000 ALTER TABLE `email_list_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_stat_replies`
--

DROP TABLE IF EXISTS `email_stat_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_stat_replies` (
  `id` char(36) NOT NULL COMMENT '(DC2Type:guid)',
  `stat_id` bigint(20) unsigned NOT NULL,
  `date_replied` datetime NOT NULL,
  `message_id` varchar(191) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_11E9F6E09502F0B` (`stat_id`),
  KEY `email_replies` (`stat_id`,`message_id`),
  KEY `date_email_replied` (`date_replied`),
  CONSTRAINT `FK_11E9F6E09502F0B` FOREIGN KEY (`stat_id`) REFERENCES `email_stats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_stat_replies`
--

LOCK TABLES `email_stat_replies` WRITE;
/*!40000 ALTER TABLE `email_stat_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_stat_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_stats`
--

DROP TABLE IF EXISTS `email_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_stats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email_id` int(10) unsigned DEFAULT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `list_id` int(10) unsigned DEFAULT NULL,
  `ip_id` int(10) unsigned DEFAULT NULL,
  `copy_id` varchar(32) DEFAULT NULL,
  `email_address` varchar(191) NOT NULL,
  `date_sent` datetime NOT NULL,
  `is_read` tinyint(1) NOT NULL,
  `is_failed` tinyint(1) NOT NULL,
  `viewed_in_browser` tinyint(1) NOT NULL,
  `date_read` datetime DEFAULT NULL,
  `tracking_hash` varchar(191) DEFAULT NULL,
  `retry_count` int(11) DEFAULT NULL,
  `source` varchar(191) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `tokens` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `open_count` int(11) DEFAULT NULL,
  `last_opened` datetime DEFAULT NULL,
  `open_details` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `generated_sent_date` date GENERATED ALWAYS AS (concat(year(`date_sent`),'-',lpad(month(`date_sent`),2,'0'),'-',lpad(dayofmonth(`date_sent`),2,'0'))) VIRTUAL COMMENT '(DC2Type:generated)',
  PRIMARY KEY (`id`),
  KEY `IDX_CA0A2625A832C1C9` (`email_id`),
  KEY `IDX_CA0A262555458D` (`lead_id`),
  KEY `IDX_CA0A26253DAE168B` (`list_id`),
  KEY `IDX_CA0A2625A03F5E9F` (`ip_id`),
  KEY `IDX_CA0A2625A8752772` (`copy_id`),
  KEY `stat_email_search` (`email_id`,`lead_id`),
  KEY `stat_email_search2` (`lead_id`,`email_id`),
  KEY `stat_email_failed_search` (`is_failed`),
  KEY `is_read_date_sent` (`is_read`,`date_sent`),
  KEY `stat_email_hash_search` (`tracking_hash`),
  KEY `stat_email_source_search` (`source`,`source_id`),
  KEY `email_date_sent` (`date_sent`),
  KEY `email_date_read_lead` (`date_read`,`lead_id`),
  KEY `stat_email_lead_id_date_sent` (`lead_id`,`date_sent`),
  KEY `stat_email_email_id_is_read` (`email_id`,`is_read`),
  KEY `generated_sent_date_email_id` (`generated_sent_date`,`email_id`),
  CONSTRAINT `FK_CA0A26253DAE168B` FOREIGN KEY (`list_id`) REFERENCES `lead_lists` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_CA0A262555458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_CA0A2625A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `ip_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_CA0A2625A832C1C9` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_CA0A2625A8752772` FOREIGN KEY (`copy_id`) REFERENCES `email_copies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_stats`
--

LOCK TABLES `email_stats` WRITE;
/*!40000 ALTER TABLE `email_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_stats_devices`
--

DROP TABLE IF EXISTS `email_stats_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_stats_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) unsigned DEFAULT NULL,
  `stat_id` bigint(20) unsigned DEFAULT NULL,
  `ip_id` int(10) unsigned DEFAULT NULL,
  `date_opened` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7A8A1C6F94A4C7D4` (`device_id`),
  KEY `IDX_7A8A1C6F9502F0B` (`stat_id`),
  KEY `IDX_7A8A1C6FA03F5E9F` (`ip_id`),
  KEY `date_opened_search` (`date_opened`),
  CONSTRAINT `FK_7A8A1C6F94A4C7D4` FOREIGN KEY (`device_id`) REFERENCES `lead_devices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_7A8A1C6F9502F0B` FOREIGN KEY (`stat_id`) REFERENCES `email_stats` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_7A8A1C6FA03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `ip_addresses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_stats_devices`
--

LOCK TABLES `email_stats_devices` WRITE;
/*!40000 ALTER TABLE `email_stats_devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_stats_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emails`
--

DROP TABLE IF EXISTS `emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `emails` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `translation_parent_id` int(10) unsigned DEFAULT NULL,
  `variant_parent_id` int(10) unsigned DEFAULT NULL,
  `unsubscribeform_id` int(10) unsigned DEFAULT NULL,
  `preference_center_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `subject` longtext DEFAULT NULL,
  `from_address` varchar(191) DEFAULT NULL,
  `from_name` varchar(191) DEFAULT NULL,
  `reply_to_address` varchar(191) DEFAULT NULL,
  `bcc_address` varchar(191) DEFAULT NULL,
  `use_owner_as_mailer` tinyint(1) DEFAULT NULL,
  `template` varchar(191) DEFAULT NULL,
  `content` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `utm_tags` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `plain_text` longtext DEFAULT NULL,
  `custom_html` longtext DEFAULT NULL,
  `email_type` longtext DEFAULT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `read_count` int(11) NOT NULL,
  `sent_count` int(11) NOT NULL,
  `variant_sent_count` int(11) NOT NULL,
  `variant_read_count` int(11) NOT NULL,
  `revision` int(11) NOT NULL,
  `lang` varchar(191) NOT NULL,
  `variant_settings` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `variant_start_date` datetime DEFAULT NULL,
  `dynamic_content` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `headers` longtext NOT NULL COMMENT '(DC2Type:json)',
  `public_preview` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4C81E85212469DE2` (`category_id`),
  KEY `IDX_4C81E8529091A2FB` (`translation_parent_id`),
  KEY `IDX_4C81E85291861123` (`variant_parent_id`),
  KEY `IDX_4C81E8522DC494F6` (`unsubscribeform_id`),
  KEY `IDX_4C81E852834F9C5B` (`preference_center_id`),
  CONSTRAINT `FK_4C81E85212469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_4C81E8522DC494F6` FOREIGN KEY (`unsubscribeform_id`) REFERENCES `forms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_4C81E852834F9C5B` FOREIGN KEY (`preference_center_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_4C81E8529091A2FB` FOREIGN KEY (`translation_parent_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_4C81E85291861123` FOREIGN KEY (`variant_parent_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emails`
--

LOCK TABLES `emails` WRITE;
/*!40000 ALTER TABLE `emails` DISABLE KEYS */;
INSERT INTO `emails` VALUES (4,NULL,NULL,NULL,NULL,NULL,1,'2024-07-17 23:03:00',NULL,NULL,'2024-07-17 23:03:00',NULL,NULL,NULL,NULL,NULL,'Email Test',NULL,'Email Test',NULL,NULL,NULL,NULL,NULL,NULL,'a:0:{}','a:0:{}',NULL,'some content','template',NULL,NULL,0,0,0,0,1,'en','a:0:{}',NULL,'a:0:{}','[]',0),(5,NULL,NULL,NULL,NULL,NULL,1,'2024-07-17 23:03:15',NULL,NULL,'2024-07-17 23:03:15',NULL,NULL,NULL,NULL,NULL,'Kaleidoscope Conf List',NULL,'Join us at the 2014  Kaleidoscope Conference!',NULL,NULL,NULL,NULL,NULL,'blank','a:2:{s:4:\"body\";s:113:\"<div>Dear {contactfield=firstname},</div><div>&nbsp;</div><div>Join us at the 2014 Kaleidoscope Conference!</div>\";s:6:\"footer\";s:71:\"<div>{webview_text}</div><div>&nbsp;</div><div>{unsubscribe_text}</div>\";}','a:0:{}','Join us at the 2014  Kaleidoscope Conference!',NULL,'list',NULL,NULL,0,0,0,0,1,'en','a:0:{}',NULL,'a:0:{}','[]',0),(6,NULL,NULL,NULL,NULL,NULL,1,'2024-07-17 23:03:15',NULL,NULL,'2024-07-17 23:03:15',NULL,NULL,NULL,NULL,NULL,'Kaleidoscope Conf Campaign',NULL,'Join us at the 2014  Kaleidoscope Conference!',NULL,NULL,NULL,NULL,NULL,'blank','a:2:{s:4:\"body\";s:113:\"<div>Dear {contactfield=firstname},</div><div>&nbsp;</div><div>Join us at the 2014 Kaleidoscope Conference!</div>\";s:6:\"footer\";s:71:\"<div>{webview_text}</div><div>&nbsp;</div><div>{unsubscribe_text}</div>\";}','a:0:{}','Join us at the 2014  Kaleidoscope Conference!',NULL,'template',NULL,NULL,0,0,0,0,1,'en','a:0:{}',NULL,'a:0:{}','[]',0);
/*!40000 ALTER TABLE `emails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `focus`
--

DROP TABLE IF EXISTS `focus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `focus` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `focus_type` varchar(191) NOT NULL,
  `style` varchar(191) NOT NULL,
  `website` varchar(191) DEFAULT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `properties` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `utm_tags` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `form_id` int(11) DEFAULT NULL,
  `cache` longtext DEFAULT NULL,
  `html_mode` varchar(191) DEFAULT NULL,
  `editor` longtext DEFAULT NULL,
  `html` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_62C04AE912469DE2` (`category_id`),
  KEY `focus_type` (`focus_type`),
  KEY `focus_style` (`style`),
  KEY `focus_form` (`form_id`),
  KEY `focus_name` (`name`),
  CONSTRAINT `FK_62C04AE912469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `focus`
--

LOCK TABLES `focus` WRITE;
/*!40000 ALTER TABLE `focus` DISABLE KEYS */;
/*!40000 ALTER TABLE `focus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `focus_stats`
--

DROP TABLE IF EXISTS `focus_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `focus_stats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `focus_id` int(10) unsigned NOT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(191) NOT NULL,
  `type_id` int(11) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C36970DC51804B42` (`focus_id`),
  KEY `IDX_C36970DC55458D` (`lead_id`),
  KEY `focus_type` (`type`),
  KEY `focus_type_id` (`type`,`type_id`),
  KEY `focus_date_added` (`date_added`),
  CONSTRAINT `FK_C36970DC51804B42` FOREIGN KEY (`focus_id`) REFERENCES `focus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_C36970DC55458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `focus_stats`
--

LOCK TABLES `focus_stats` WRITE;
/*!40000 ALTER TABLE `focus_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `focus_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_actions`
--

DROP TABLE IF EXISTS `form_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form_actions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int(10) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `action_order` int(11) NOT NULL,
  `properties` longtext NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_342491D45FF69B7D` (`form_id`),
  KEY `form_action_type_search` (`type`),
  CONSTRAINT `FK_342491D45FF69B7D` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_actions`
--

LOCK TABLES `form_actions` WRITE;
/*!40000 ALTER TABLE `form_actions` DISABLE KEYS */;
INSERT INTO `form_actions` VALUES (3,3,'Create a lead',NULL,'lead.create',1,'a:2:{s:6:\"points\";d:10;s:12:\"mappedFields\";a:22:{i:1;i:1;i:2;N;i:3;N;i:4;N;i:5;N;i:6;i:2;i:7;N;i:8;N;i:9;N;i:10;N;i:11;N;i:12;N;i:13;N;i:14;N;i:15;N;i:16;N;i:17;N;i:18;N;i:19;N;i:20;N;i:21;N;i:22;N;}}'),(4,4,'Create a lead',NULL,'lead.create',1,'a:2:{s:6:\"points\";d:10;s:12:\"mappedFields\";a:22:{i:1;i:5;i:2;N;i:3;N;i:4;N;i:5;N;i:6;i:6;i:7;N;i:8;N;i:9;N;i:10;N;i:11;N;i:12;N;i:13;N;i:14;N;i:15;N;i:16;N;i:17;N;i:18;N;i:19;N;i:20;N;i:21;N;i:22;N;}}');
/*!40000 ALTER TABLE `form_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_fields`
--

DROP TABLE IF EXISTS `form_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int(10) unsigned NOT NULL,
  `label` longtext NOT NULL,
  `show_label` tinyint(1) DEFAULT NULL,
  `alias` varchar(191) NOT NULL,
  `type` varchar(191) NOT NULL,
  `is_custom` tinyint(1) NOT NULL,
  `custom_parameters` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `default_value` longtext DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL,
  `validation_message` longtext DEFAULT NULL,
  `help_message` longtext DEFAULT NULL,
  `field_order` int(11) DEFAULT NULL,
  `properties` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `validation` longtext DEFAULT NULL COMMENT '(DC2Type:json)',
  `parent_id` varchar(191) DEFAULT NULL,
  `conditions` longtext DEFAULT NULL COMMENT '(DC2Type:json)',
  `label_attr` varchar(191) DEFAULT NULL,
  `input_attr` varchar(191) DEFAULT NULL,
  `container_attr` varchar(191) DEFAULT NULL,
  `lead_field` varchar(191) DEFAULT NULL,
  `save_result` tinyint(1) DEFAULT NULL,
  `is_auto_fill` tinyint(1) DEFAULT NULL,
  `show_when_value_exists` tinyint(1) DEFAULT NULL,
  `show_after_x_submissions` int(11) DEFAULT NULL,
  `always_display` tinyint(1) DEFAULT NULL,
  `mapped_object` varchar(191) DEFAULT NULL,
  `mapped_field` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7C0B37265FF69B7D` (`form_id`),
  KEY `form_field_type_search` (`type`),
  CONSTRAINT `FK_7C0B37265FF69B7D` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_fields`
--

LOCK TABLES `form_fields` WRITE;
/*!40000 ALTER TABLE `form_fields` DISABLE KEYS */;
INSERT INTO `form_fields` VALUES (25,3,'Name',1,'name','text',0,'a:0:{}',NULL,1,NULL,NULL,1,'a:1:{s:11:\"placeholder\";N;}','[]',NULL,'[]',NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL),(26,3,'Email',1,'email','email',0,'a:0:{}',NULL,1,NULL,NULL,2,'a:1:{s:11:\"placeholder\";N;}','[]',NULL,'[]',NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL),(27,3,'Number of attendees',1,'numberofattendees','number',0,'a:0:{}','1',1,NULL,NULL,3,'a:1:{s:11:\"placeholder\";N;}','[]',NULL,'[]',NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL),(28,3,'Register',1,'register','button',0,'a:0:{}',NULL,0,NULL,NULL,4,'a:1:{s:4:\"type\";s:6:\"submit\";}','[]',NULL,'[]',NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL),(29,4,'Nombre',1,'nombre','text',0,'a:0:{}',NULL,1,NULL,NULL,1,'a:1:{s:11:\"placeholder\";N;}','[]',NULL,'[]',NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL),(30,4,'Email',1,'email','email',0,'a:0:{}',NULL,1,NULL,NULL,2,'a:1:{s:11:\"placeholder\";N;}','[]',NULL,'[]',NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL),(31,4,'Numero de asistentes',1,'nmerodeasistentes','number',0,'a:0:{}','1',1,NULL,NULL,3,'a:1:{s:11:\"placeholder\";N;}','[]',NULL,'[]',NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL),(32,4,'Registro',1,'registro','button',0,'a:0:{}',NULL,0,NULL,NULL,4,'a:1:{s:4:\"type\";s:6:\"submit\";}','[]',NULL,'[]',NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `form_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_results_1_kaleidosco`
--

DROP TABLE IF EXISTS `form_results_1_kaleidosco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form_results_1_kaleidosco` (
  `submission_id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `name` longtext DEFAULT NULL,
  `email` longtext DEFAULT NULL,
  `numberofattendees` longtext DEFAULT NULL,
  PRIMARY KEY (`submission_id`),
  UNIQUE KEY `UNIQ_EEF899FCE1FD49335FF69B7D` (`submission_id`,`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_results_1_kaleidosco`
--

LOCK TABLES `form_results_1_kaleidosco` WRITE;
/*!40000 ALTER TABLE `form_results_1_kaleidosco` DISABLE KEYS */;
INSERT INTO `form_results_1_kaleidosco` VALUES (1,1,'Penny','PennyKMoore@dayrep.com','1'),(2,1,'Henry','HenryLCatalano@einrot.com','3'),(3,1,'Stephanie','StephanieMCone@teleworm.us','2'),(4,1,'Andrew','AndrewVFlanagan@dayrep.com','5'),(5,1,'Daniel','DanielAWright@dayrep.com','1'),(6,1,'Jose','JoseMPatton@jourrapide.com','1'),(7,1,'Jean','JeanGCross@armyspy.com','1'),(8,1,'Kevin','KevinBKennedy@gustr.com','1'),(9,1,'Leonard','LeonardMSinclair@teleworm.us','1'),(10,1,'Bruce','BruceMCampbell@einrot.com','1'),(11,1,'Guadalupe','GuadalupeHStrauss@teleworm.us','5'),(12,1,'Pamela','PamelaSWise@gustr.com','7'),(13,1,'Margaret','MargaretDMaguire@cuvox.de','2'),(14,1,'Regina','ReginaBDolph@teleworm.us','3'),(15,1,'Paula','PaulaWHill@dayrep.com','4'),(16,1,'Jimmy','JimmyCSanchez@dayrep.com','1'),(17,1,'Mildred','MildredARodriguez@rhyta.com','3'),(18,1,'Kyung','KyungBBrittain@dayrep.com','1'),(19,1,'Willie','WillieJPerez@jourrapide.com','2'),(20,1,'Marvin','MarvinPPatterson@jourrapide.com','1'),(21,1,'Rosemary','RosemaryKSalinas@superrito.com','3'),(22,1,'Paul','PaulDWilson@superrito.com','1'),(23,1,'Roxie','RoxieLShaw@fleckens.hu','3'),(24,1,'Angie','AngieHRobles@einrot.com','2'),(25,1,'Charlotte','CharlotteAFender@einrot.com','5'),(26,1,'Lashawnda','LashawndaDJoseph@gustr.com','1'),(27,1,'Helen','HelenPManley@dayrep.com','1'),(28,1,'Annie','AnnieARicharson@armyspy.com','1'),(29,1,'Mary','MaryWNevarez@armyspy.com','1'),(30,1,'David','DavidEFahy@dayrep.com','1'),(31,1,'Aaron','AaronMGuild@rhyta.com','1'),(32,1,'Lee','LeeACole@fleckens.hu','5'),(33,1,'Matthew','MatthewSDell@armyspy.com','7'),(34,1,'Raquel','RaquelTOSullivan@gustr.com','2'),(35,1,'Debra','DebraCShackelford@dayrep.com','3'),(36,1,'Marcia','MarciaBHibbard@fleckens.hu','4'),(37,1,'Thomas','ThomasJDomingue@armyspy.com','1'),(38,1,'Jeremy','JeremyJNewell@fleckens.hu','3'),(39,1,'Justin','JustinRWaller@cuvox.de','1'),(40,1,'Brenda','BrendaWBolton@dayrep.com','2'),(41,1,'Renee','ReneeTSmith@teleworm.us','1'),(42,1,'David','DavidECook@dayrep.com','3'),(43,1,'June','JuneLBond@superrito.com','1'),(44,1,'James','JamesTDuffy@armyspy.com','3'),(45,1,'Jonathan','JonathanJLane@jourrapide.com','2'),(46,1,'Peter','PeterJHoward@armyspy.com','5'),(47,1,'Irene','IreneGMartin@cuvox.de','1'),(48,1,'David','DavidLJameson@einrot.com','1'),(49,1,'Lewis','LewisTSyed@gustr.com','1'),(50,1,'Nellie','NellieABaird@armyspy.com','1');
/*!40000 ALTER TABLE `form_results_1_kaleidosco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_results_2_kaleidosco`
--

DROP TABLE IF EXISTS `form_results_2_kaleidosco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form_results_2_kaleidosco` (
  `submission_id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `nombre` longtext DEFAULT NULL,
  `email` longtext DEFAULT NULL,
  `nmerodeasistentes` longtext DEFAULT NULL,
  PRIMARY KEY (`submission_id`),
  UNIQUE KEY `UNIQ_99664B0CE1FD49335FF69B7D` (`submission_id`,`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_results_2_kaleidosco`
--

LOCK TABLES `form_results_2_kaleidosco` WRITE;
/*!40000 ALTER TABLE `form_results_2_kaleidosco` DISABLE KEYS */;
INSERT INTO `form_results_2_kaleidosco` VALUES (51,2,'Penny','PennyKMoore@dayrep.com','1'),(52,2,'Henry','HenryLCatalano@einrot.com','3'),(53,2,'Stephanie','StephanieMCone@teleworm.us','2'),(54,2,'Andrew','AndrewVFlanagan@dayrep.com','5'),(55,2,'Daniel','DanielAWright@dayrep.com','1'),(56,2,'Jose','JoseMPatton@jourrapide.com','1'),(57,2,'Jean','JeanGCross@armyspy.com','1'),(58,2,'Kevin','KevinBKennedy@gustr.com','1'),(59,2,'Leonard','LeonardMSinclair@teleworm.us','1'),(60,2,'Bruce','BruceMCampbell@einrot.com','1'),(61,2,'Guadalupe','GuadalupeHStrauss@teleworm.us','5'),(62,2,'Pamela','PamelaSWise@gustr.com','7'),(63,2,'Margaret','MargaretDMaguire@cuvox.de','2'),(64,2,'Regina','ReginaBDolph@teleworm.us','3'),(65,2,'Paula','PaulaWHill@dayrep.com','4'),(66,2,'Jimmy','JimmyCSanchez@dayrep.com','1'),(67,2,'Mildred','MildredARodriguez@rhyta.com','3'),(68,2,'Kyung','KyungBBrittain@dayrep.com','1'),(69,2,'Willie','WillieJPerez@jourrapide.com','2'),(70,2,'Marvin','MarvinPPatterson@jourrapide.com','1'),(71,2,'Rosemary','RosemaryKSalinas@superrito.com','3'),(72,2,'Paul','PaulDWilson@superrito.com','1'),(73,2,'Roxie','RoxieLShaw@fleckens.hu','3'),(74,2,'Angie','AngieHRobles@einrot.com','2'),(75,2,'Charlotte','CharlotteAFender@einrot.com','5'),(76,2,'Lashawnda','LashawndaDJoseph@gustr.com','1'),(77,2,'Helen','HelenPManley@dayrep.com','1'),(78,2,'Annie','AnnieARicharson@armyspy.com','1'),(79,2,'Mary','MaryWNevarez@armyspy.com','1'),(80,2,'David','DavidEFahy@dayrep.com','1'),(81,2,'Aaron','AaronMGuild@rhyta.com','1'),(82,2,'Lee','LeeACole@fleckens.hu','5'),(83,2,'Matthew','MatthewSDell@armyspy.com','7'),(84,2,'Raquel','RaquelTOSullivan@gustr.com','2'),(85,2,'Debra','DebraCShackelford@dayrep.com','3'),(86,2,'Marcia','MarciaBHibbard@fleckens.hu','4'),(87,2,'Thomas','ThomasJDomingue@armyspy.com','1'),(88,2,'Jeremy','JeremyJNewell@fleckens.hu','3'),(89,2,'Justin','JustinRWaller@cuvox.de','1'),(90,2,'Brenda','BrendaWBolton@dayrep.com','2'),(91,2,'Renee','ReneeTSmith@teleworm.us','1'),(92,2,'David','DavidECook@dayrep.com','3'),(93,2,'June','JuneLBond@superrito.com','1'),(94,2,'James','JamesTDuffy@armyspy.com','3'),(95,2,'Jonathan','JonathanJLane@jourrapide.com','2'),(96,2,'Peter','PeterJHoward@armyspy.com','5'),(97,2,'Irene','IreneGMartin@cuvox.de','1'),(98,2,'David','DavidLJameson@einrot.com','1'),(99,2,'Lewis','LewisTSyed@gustr.com','1'),(100,2,'Nellie','NellieABaird@armyspy.com','1');
/*!40000 ALTER TABLE `form_results_2_kaleidosco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_results_3_kaleidosco`
--

DROP TABLE IF EXISTS `form_results_3_kaleidosco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form_results_3_kaleidosco` (
  `submission_id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `name` longtext DEFAULT NULL,
  `email` longtext DEFAULT NULL,
  `numberofattendees` longtext DEFAULT NULL,
  PRIMARY KEY (`submission_id`),
  UNIQUE KEY `UNIQ_2C30763E1FD49335FF69B7D` (`submission_id`,`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_results_3_kaleidosco`
--

LOCK TABLES `form_results_3_kaleidosco` WRITE;
/*!40000 ALTER TABLE `form_results_3_kaleidosco` DISABLE KEYS */;
INSERT INTO `form_results_3_kaleidosco` VALUES (101,3,'Penny','PennyKMoore@dayrep.com','1'),(102,3,'Henry','HenryLCatalano@einrot.com','3'),(103,3,'Stephanie','StephanieMCone@teleworm.us','2'),(104,3,'Andrew','AndrewVFlanagan@dayrep.com','5'),(105,3,'Daniel','DanielAWright@dayrep.com','1'),(106,3,'Jose','JoseMPatton@jourrapide.com','1'),(107,3,'Jean','JeanGCross@armyspy.com','1'),(108,3,'Kevin','KevinBKennedy@gustr.com','1'),(109,3,'Leonard','LeonardMSinclair@teleworm.us','1'),(110,3,'Bruce','BruceMCampbell@einrot.com','1'),(111,3,'Guadalupe','GuadalupeHStrauss@teleworm.us','5'),(112,3,'Pamela','PamelaSWise@gustr.com','7'),(113,3,'Margaret','MargaretDMaguire@cuvox.de','2'),(114,3,'Regina','ReginaBDolph@teleworm.us','3'),(115,3,'Paula','PaulaWHill@dayrep.com','4'),(116,3,'Jimmy','JimmyCSanchez@dayrep.com','1'),(117,3,'Mildred','MildredARodriguez@rhyta.com','3'),(118,3,'Kyung','KyungBBrittain@dayrep.com','1'),(119,3,'Willie','WillieJPerez@jourrapide.com','2'),(120,3,'Marvin','MarvinPPatterson@jourrapide.com','1'),(121,3,'Rosemary','RosemaryKSalinas@superrito.com','3'),(122,3,'Paul','PaulDWilson@superrito.com','1'),(123,3,'Roxie','RoxieLShaw@fleckens.hu','3'),(124,3,'Angie','AngieHRobles@einrot.com','2'),(125,3,'Charlotte','CharlotteAFender@einrot.com','5'),(126,3,'Lashawnda','LashawndaDJoseph@gustr.com','1'),(127,3,'Helen','HelenPManley@dayrep.com','1'),(128,3,'Annie','AnnieARicharson@armyspy.com','1'),(129,3,'Mary','MaryWNevarez@armyspy.com','1'),(130,3,'David','DavidEFahy@dayrep.com','1'),(131,3,'Aaron','AaronMGuild@rhyta.com','1'),(132,3,'Lee','LeeACole@fleckens.hu','5'),(133,3,'Matthew','MatthewSDell@armyspy.com','7'),(134,3,'Raquel','RaquelTOSullivan@gustr.com','2'),(135,3,'Debra','DebraCShackelford@dayrep.com','3'),(136,3,'Marcia','MarciaBHibbard@fleckens.hu','4'),(137,3,'Thomas','ThomasJDomingue@armyspy.com','1'),(138,3,'Jeremy','JeremyJNewell@fleckens.hu','3'),(139,3,'Justin','JustinRWaller@cuvox.de','1'),(140,3,'Brenda','BrendaWBolton@dayrep.com','2'),(141,3,'Renee','ReneeTSmith@teleworm.us','1'),(142,3,'David','DavidECook@dayrep.com','3'),(143,3,'June','JuneLBond@superrito.com','1'),(144,3,'James','JamesTDuffy@armyspy.com','3'),(145,3,'Jonathan','JonathanJLane@jourrapide.com','2'),(146,3,'Peter','PeterJHoward@armyspy.com','5'),(147,3,'Irene','IreneGMartin@cuvox.de','1'),(148,3,'David','DavidLJameson@einrot.com','1'),(149,3,'Lewis','LewisTSyed@gustr.com','1'),(150,3,'Nellie','NellieABaird@armyspy.com','1');
/*!40000 ALTER TABLE `form_results_3_kaleidosco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_results_4_kaleidosco`
--

DROP TABLE IF EXISTS `form_results_4_kaleidosco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form_results_4_kaleidosco` (
  `submission_id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `nombre` longtext DEFAULT NULL,
  `email` longtext DEFAULT NULL,
  `nmerodeasistentes` longtext DEFAULT NULL,
  PRIMARY KEY (`submission_id`),
  UNIQUE KEY `UNIQ_765BEEECE1FD49335FF69B7D` (`submission_id`,`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_results_4_kaleidosco`
--

LOCK TABLES `form_results_4_kaleidosco` WRITE;
/*!40000 ALTER TABLE `form_results_4_kaleidosco` DISABLE KEYS */;
INSERT INTO `form_results_4_kaleidosco` VALUES (151,4,'Penny','PennyKMoore@dayrep.com','1'),(152,4,'Henry','HenryLCatalano@einrot.com','3'),(153,4,'Stephanie','StephanieMCone@teleworm.us','2'),(154,4,'Andrew','AndrewVFlanagan@dayrep.com','5'),(155,4,'Daniel','DanielAWright@dayrep.com','1'),(156,4,'Jose','JoseMPatton@jourrapide.com','1'),(157,4,'Jean','JeanGCross@armyspy.com','1'),(158,4,'Kevin','KevinBKennedy@gustr.com','1'),(159,4,'Leonard','LeonardMSinclair@teleworm.us','1'),(160,4,'Bruce','BruceMCampbell@einrot.com','1'),(161,4,'Guadalupe','GuadalupeHStrauss@teleworm.us','5'),(162,4,'Pamela','PamelaSWise@gustr.com','7'),(163,4,'Margaret','MargaretDMaguire@cuvox.de','2'),(164,4,'Regina','ReginaBDolph@teleworm.us','3'),(165,4,'Paula','PaulaWHill@dayrep.com','4'),(166,4,'Jimmy','JimmyCSanchez@dayrep.com','1'),(167,4,'Mildred','MildredARodriguez@rhyta.com','3'),(168,4,'Kyung','KyungBBrittain@dayrep.com','1'),(169,4,'Willie','WillieJPerez@jourrapide.com','2'),(170,4,'Marvin','MarvinPPatterson@jourrapide.com','1'),(171,4,'Rosemary','RosemaryKSalinas@superrito.com','3'),(172,4,'Paul','PaulDWilson@superrito.com','1'),(173,4,'Roxie','RoxieLShaw@fleckens.hu','3'),(174,4,'Angie','AngieHRobles@einrot.com','2'),(175,4,'Charlotte','CharlotteAFender@einrot.com','5'),(176,4,'Lashawnda','LashawndaDJoseph@gustr.com','1'),(177,4,'Helen','HelenPManley@dayrep.com','1'),(178,4,'Annie','AnnieARicharson@armyspy.com','1'),(179,4,'Mary','MaryWNevarez@armyspy.com','1'),(180,4,'David','DavidEFahy@dayrep.com','1'),(181,4,'Aaron','AaronMGuild@rhyta.com','1'),(182,4,'Lee','LeeACole@fleckens.hu','5'),(183,4,'Matthew','MatthewSDell@armyspy.com','7'),(184,4,'Raquel','RaquelTOSullivan@gustr.com','2'),(185,4,'Debra','DebraCShackelford@dayrep.com','3'),(186,4,'Marcia','MarciaBHibbard@fleckens.hu','4'),(187,4,'Thomas','ThomasJDomingue@armyspy.com','1'),(188,4,'Jeremy','JeremyJNewell@fleckens.hu','3'),(189,4,'Justin','JustinRWaller@cuvox.de','1'),(190,4,'Brenda','BrendaWBolton@dayrep.com','2'),(191,4,'Renee','ReneeTSmith@teleworm.us','1'),(192,4,'David','DavidECook@dayrep.com','3'),(193,4,'June','JuneLBond@superrito.com','1'),(194,4,'James','JamesTDuffy@armyspy.com','3'),(195,4,'Jonathan','JonathanJLane@jourrapide.com','2'),(196,4,'Peter','PeterJHoward@armyspy.com','5'),(197,4,'Irene','IreneGMartin@cuvox.de','1'),(198,4,'David','DavidLJameson@einrot.com','1'),(199,4,'Lewis','LewisTSyed@gustr.com','1'),(200,4,'Nellie','NellieABaird@armyspy.com','1');
/*!40000 ALTER TABLE `form_results_4_kaleidosco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_submissions`
--

DROP TABLE IF EXISTS `form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int(10) unsigned NOT NULL,
  `ip_id` int(10) unsigned DEFAULT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `page_id` int(10) unsigned DEFAULT NULL,
  `tracking_id` varchar(191) DEFAULT NULL,
  `date_submitted` datetime NOT NULL,
  `referer` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C80AF9E65FF69B7D` (`form_id`),
  KEY `IDX_C80AF9E6A03F5E9F` (`ip_id`),
  KEY `IDX_C80AF9E655458D` (`lead_id`),
  KEY `IDX_C80AF9E6C4663E4` (`page_id`),
  KEY `form_submission_tracking_search` (`tracking_id`),
  KEY `form_date_submitted` (`date_submitted`),
  CONSTRAINT `FK_C80AF9E655458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_C80AF9E65FF69B7D` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_C80AF9E6A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `ip_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_C80AF9E6C4663E4` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_submissions`
--

LOCK TABLES `form_submissions` WRITE;
/*!40000 ALTER TABLE `form_submissions` DISABLE KEYS */;
INSERT INTO `form_submissions` VALUES (101,3,55,57,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(102,3,56,58,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(103,3,57,59,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(104,3,58,60,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(105,3,59,61,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(106,3,60,62,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(107,3,61,63,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(108,3,62,64,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(109,3,63,65,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(110,3,64,66,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(111,3,65,67,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(112,3,66,68,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(113,3,67,69,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(114,3,68,70,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(115,3,69,71,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(116,3,70,72,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(117,3,71,73,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(118,3,72,74,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(119,3,73,75,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(120,3,74,76,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(121,3,75,77,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(122,3,76,78,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(123,3,77,79,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(124,3,78,80,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(125,3,79,81,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(126,3,80,82,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(127,3,81,83,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(128,3,82,84,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(129,3,83,85,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(130,3,84,86,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(131,3,85,87,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(132,3,86,88,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(133,3,87,89,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(134,3,88,90,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(135,3,89,91,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(136,3,90,92,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(137,3,91,93,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(138,3,92,94,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(139,3,93,95,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(140,3,94,96,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(141,3,95,97,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(142,3,96,98,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(143,3,97,99,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(144,3,98,100,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(145,3,99,101,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(146,3,100,102,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(147,3,101,103,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(148,3,102,104,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(149,3,103,105,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(150,3,104,106,4,NULL,'2024-07-17 23:03:15','https://mautic.ddev.site/kaleidoscope-conference-2014'),(151,4,55,57,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(152,4,56,58,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(153,4,57,59,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(154,4,58,60,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(155,4,59,61,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(156,4,60,62,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(157,4,61,63,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(158,4,62,64,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(159,4,63,65,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(160,4,64,66,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(161,4,65,67,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(162,4,66,68,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(163,4,67,69,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(164,4,68,70,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(165,4,69,71,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(166,4,70,72,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(167,4,71,73,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(168,4,72,74,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(169,4,73,75,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(170,4,74,76,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(171,4,75,77,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(172,4,76,78,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(173,4,77,79,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(174,4,78,80,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(175,4,79,81,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(176,4,80,82,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(177,4,81,83,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(178,4,82,84,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(179,4,83,85,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(180,4,84,86,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(181,4,85,87,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(182,4,86,88,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(183,4,87,89,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(184,4,88,90,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(185,4,89,91,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(186,4,90,92,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(187,4,91,93,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(188,4,92,94,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(189,4,93,95,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(190,4,94,96,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(191,4,95,97,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(192,4,96,98,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(193,4,97,99,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(194,4,98,100,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(195,4,99,101,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(196,4,100,102,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(197,4,101,103,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(198,4,102,104,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(199,4,103,105,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014'),(200,4,104,106,5,NULL,'2024-07-17 23:03:17','https://mautic.ddev.site/kaleidoscope-conference-2014');
/*!40000 ALTER TABLE `form_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forms`
--

DROP TABLE IF EXISTS `forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `alias` varchar(191) NOT NULL,
  `lang` varchar(191) DEFAULT NULL,
  `form_attr` varchar(191) DEFAULT NULL,
  `cached_html` longtext DEFAULT NULL,
  `post_action` varchar(191) NOT NULL,
  `post_action_property` longtext DEFAULT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `template` varchar(191) DEFAULT NULL,
  `in_kiosk_mode` tinyint(1) DEFAULT NULL,
  `render_style` tinyint(1) DEFAULT NULL,
  `form_type` varchar(191) DEFAULT NULL,
  `no_index` tinyint(1) DEFAULT NULL,
  `progressive_profiling_limit` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_FD3F1BF712469DE2` (`category_id`),
  CONSTRAINT `FK_FD3F1BF712469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forms`
--

LOCK TABLES `forms` WRITE;
/*!40000 ALTER TABLE `forms` DISABLE KEYS */;
INSERT INTO `forms` VALUES (3,NULL,1,'2014-08-10 00:43:12',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Kaleidoscope Registration',NULL,'kaleidosco',NULL,NULL,NULL,'message','Thank you for registering!',NULL,NULL,'blank',0,0,'standalone',NULL,NULL),(4,NULL,1,'2014-08-10 00:44:27',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Kaleidoscope Registro (ES)',NULL,'kaleidosco',NULL,NULL,NULL,'message','Gracias por registrarse!',NULL,NULL,'blank',0,0,'standalone',NULL,NULL);
/*!40000 ALTER TABLE `forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `imports`
--

DROP TABLE IF EXISTS `imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `dir` varchar(191) NOT NULL,
  `file` varchar(191) NOT NULL,
  `original_file` varchar(191) DEFAULT NULL,
  `line_count` int(11) NOT NULL,
  `inserted_count` int(11) NOT NULL,
  `updated_count` int(11) NOT NULL,
  `ignored_count` int(11) NOT NULL,
  `priority` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `date_started` datetime DEFAULT NULL,
  `date_ended` datetime DEFAULT NULL,
  `object` varchar(191) NOT NULL,
  `properties` longtext DEFAULT NULL COMMENT '(DC2Type:json)',
  PRIMARY KEY (`id`),
  KEY `import_object` (`object`),
  KEY `import_status` (`status`),
  KEY `import_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `imports`
--

LOCK TABLES `imports` WRITE;
/*!40000 ALTER TABLE `imports` DISABLE KEYS */;
/*!40000 ALTER TABLE `imports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `integration_entity`
--

DROP TABLE IF EXISTS `integration_entity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `integration_entity` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date_added` datetime NOT NULL,
  `integration` varchar(191) DEFAULT NULL,
  `integration_entity` varchar(191) DEFAULT NULL,
  `integration_entity_id` varchar(191) DEFAULT NULL,
  `internal_entity` varchar(191) DEFAULT NULL,
  `internal_entity_id` int(11) DEFAULT NULL,
  `last_sync_date` datetime DEFAULT NULL,
  `internal` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `integration_external_entity` (`integration`,`integration_entity`,`integration_entity_id`),
  KEY `integration_internal_entity` (`integration`,`internal_entity`,`internal_entity_id`),
  KEY `integration_entity_match` (`integration`,`internal_entity`,`integration_entity`),
  KEY `integration_last_sync_date` (`integration`,`last_sync_date`),
  KEY `internal_integration_entity` (`internal_entity_id`,`integration_entity_id`,`internal_entity`,`integration_entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `integration_entity`
--

LOCK TABLES `integration_entity` WRITE;
/*!40000 ALTER TABLE `integration_entity` DISABLE KEYS */;
/*!40000 ALTER TABLE `integration_entity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ip_addresses`
--

DROP TABLE IF EXISTS `ip_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ip_addresses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `ip_details` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `ip_search` (`ip_address`)
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ip_addresses`
--

LOCK TABLES `ip_addresses` WRITE;
/*!40000 ALTER TABLE `ip_addresses` DISABLE KEYS */;
INSERT INTO `ip_addresses` VALUES (55,'44.242.120.158','N;'),(56,'83.215.242.109','N;'),(57,'169.194.102.58','N;'),(58,'12.53.195.22','N;'),(59,'86.210.48.56','N;'),(60,'205.240.234.201','N;'),(61,'160.183.198.246','N;'),(62,'185.58.123.229','N;'),(63,'46.243.132.129','N;'),(64,'204.119.237.119','N;'),(65,'59.85.176.70','N;'),(66,'137.116.91.223','N;'),(67,'72.139.24.22','N;'),(68,'125.4.222.31','N;'),(69,'187.166.22.117','N;'),(70,'224.145.91.15','N;'),(71,'134.222.144.84','N;'),(72,'86.127.202.144','N;'),(73,'211.124.214.94','N;'),(74,'240.51.62.57','N;'),(75,'189.86.78.59','N;'),(76,'89.46.89.21','N;'),(77,'211.111.137.180','N;'),(78,'2.227.195.136','N;'),(79,'194.85.219.26','N;'),(80,'211.167.170.168','N;'),(81,'36.129.7.21','N;'),(82,'179.68.77.113','N;'),(83,'153.154.172.242','N;'),(84,'199.7.8.156','N;'),(85,'90.41.142.23','N;'),(86,'209.183.6.82','N;'),(87,'92.90.197.187','N;'),(88,'38.82.155.239','N;'),(89,'93.213.3.76','N;'),(90,'52.122.202.53','N;'),(91,'235.234.136.88','N;'),(92,'10.248.239.100','N;'),(93,'34.126.122.243','N;'),(94,'54.128.70.146','N;'),(95,'218.13.78.2','N;'),(96,'94.232.240.187','N;'),(97,'190.243.9.242','N;'),(98,'110.211.41.91','N;'),(99,'190.176.178.200','N;'),(100,'169.163.46.203','N;'),(101,'34.167.191.88','N;'),(102,'41.6.233.4','N;'),(103,'18.57.6.112','N;'),(104,'34.245.44.224','N;'),(105,'34.245.44.224','N;'),(106,'34.245.44.224','N;'),(107,'34.245.44.224','N;'),(108,'34.245.44.224','N;');
/*!40000 ALTER TABLE `ip_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_categories`
--

DROP TABLE IF EXISTS `lead_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `lead_id` bigint(20) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  `manually_removed` tinyint(1) NOT NULL,
  `manually_added` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_12685DF412469DE2` (`category_id`),
  KEY `IDX_12685DF455458D` (`lead_id`),
  CONSTRAINT `FK_12685DF412469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_12685DF455458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_categories`
--

LOCK TABLES `lead_categories` WRITE;
/*!40000 ALTER TABLE `lead_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_companies_change_log`
--

DROP TABLE IF EXISTS `lead_companies_change_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_companies_change_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `type` tinytext NOT NULL,
  `event_name` varchar(191) NOT NULL,
  `action_name` varchar(191) NOT NULL,
  `company_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A034C81B55458D` (`lead_id`),
  KEY `company_date_added` (`date_added`),
  CONSTRAINT `FK_A034C81B55458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_companies_change_log`
--

LOCK TABLES `lead_companies_change_log` WRITE;
/*!40000 ALTER TABLE `lead_companies_change_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_companies_change_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_devices`
--

DROP TABLE IF EXISTS `lead_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  `client_info` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `device` varchar(191) DEFAULT NULL,
  `device_os_name` varchar(191) DEFAULT NULL,
  `device_os_shortname` varchar(191) DEFAULT NULL,
  `device_os_version` varchar(191) DEFAULT NULL,
  `device_os_platform` varchar(191) DEFAULT NULL,
  `device_brand` varchar(191) DEFAULT NULL,
  `device_model` varchar(191) DEFAULT NULL,
  `tracking_id` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_48C912F47D05ABBE` (`tracking_id`),
  KEY `IDX_48C912F455458D` (`lead_id`),
  KEY `date_added_search` (`date_added`),
  KEY `device_search` (`device`),
  KEY `device_os_name_search` (`device_os_name`),
  KEY `device_os_shortname_search` (`device_os_shortname`),
  KEY `device_os_version_search` (`device_os_version`),
  KEY `device_os_platform_search` (`device_os_platform`),
  KEY `device_brand_search` (`device_brand`),
  KEY `device_model_search` (`device_model`),
  CONSTRAINT `FK_48C912F455458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_devices`
--

LOCK TABLES `lead_devices` WRITE;
/*!40000 ALTER TABLE `lead_devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_donotcontact`
--

DROP TABLE IF EXISTS `lead_donotcontact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_donotcontact` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `reason` smallint(6) NOT NULL,
  `channel` varchar(191) NOT NULL,
  `channel_id` int(11) DEFAULT NULL,
  `comments` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_71DC0B1D55458D` (`lead_id`),
  KEY `leadid_reason_channel` (`lead_id`,`channel`,`reason`),
  CONSTRAINT `FK_71DC0B1D55458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_donotcontact`
--

LOCK TABLES `lead_donotcontact` WRITE;
/*!40000 ALTER TABLE `lead_donotcontact` DISABLE KEYS */;
INSERT INTO `lead_donotcontact` VALUES (2,58,'2024-07-17 23:03:15',3,'sms',NULL,NULL);
/*!40000 ALTER TABLE `lead_donotcontact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_event_log`
--

DROP TABLE IF EXISTS `lead_event_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_event_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(191) DEFAULT NULL,
  `bundle` varchar(191) DEFAULT NULL,
  `object` varchar(191) DEFAULT NULL,
  `action` varchar(191) DEFAULT NULL,
  `object_id` int(11) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `properties` longtext DEFAULT NULL COMMENT '(DC2Type:json)',
  PRIMARY KEY (`id`),
  KEY `lead_id_index` (`lead_id`),
  KEY `lead_object_index` (`object`,`object_id`),
  KEY `lead_timeline_index` (`bundle`,`object`,`action`,`object_id`),
  KEY `IDX_SEARCH` (`bundle`,`object`,`action`,`object_id`,`date_added`),
  KEY `lead_timeline_action_index` (`action`),
  KEY `lead_date_added_index` (`date_added`),
  CONSTRAINT `FK_753AF2E55458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=637 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_event_log`
--

LOCK TABLES `lead_event_log` WRITE;
/*!40000 ALTER TABLE `lead_event_log` DISABLE KEYS */;
INSERT INTO `lead_event_log` VALUES (235,75,NULL,'System','lead','segment','added',67,'2024-07-17 23:03:00','{\"object_description\":\"United States\"}'),(236,81,NULL,'System','lead','segment','added',67,'2024-07-17 23:03:00','{\"object_description\":\"United States\"}'),(237,84,NULL,'System','lead','segment','added',67,'2024-07-17 23:03:00','{\"object_description\":\"United States\"}'),(238,89,NULL,'System','lead','segment','added',67,'2024-07-17 23:03:00','{\"object_description\":\"United States\"}'),(239,93,NULL,'System','lead','segment','added',67,'2024-07-17 23:03:00','{\"object_description\":\"United States\"}'),(240,94,NULL,'System','lead','segment','added',67,'2024-07-17 23:03:00','{\"object_description\":\"United States\"}'),(241,100,NULL,'System','lead','segment','added',67,'2024-07-17 23:03:00','{\"object_description\":\"United States\"}'),(242,105,NULL,'System','lead','segment','added',67,'2024-07-17 23:03:00','{\"object_description\":\"United States\"}'),(243,81,NULL,'System','lead','segment','added',68,'2024-07-17 23:03:01','{\"object_description\":\"Segment Test 1\"}'),(244,66,NULL,'System','lead','segment','added',69,'2024-07-17 23:03:02','{\"object_description\":\"Segment Test 2\"}'),(245,72,NULL,'System','lead','segment','added',69,'2024-07-17 23:03:02','{\"object_description\":\"Segment Test 2\"}'),(246,81,NULL,'System','lead','segment','added',69,'2024-07-17 23:03:02','{\"object_description\":\"Segment Test 2\"}'),(247,97,NULL,'System','lead','segment','added',69,'2024-07-17 23:03:02','{\"object_description\":\"Segment Test 2\"}'),(248,58,NULL,'System','lead','segment','added',71,'2024-07-17 23:03:03','{\"object_description\":\"Segment Test 4\"}'),(249,57,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(250,59,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(251,60,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(252,61,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(253,62,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(254,63,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(255,64,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(256,65,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(257,66,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(258,67,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(259,68,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(260,69,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(261,70,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(262,71,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(263,72,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(264,73,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(265,74,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(266,75,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(267,76,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(268,77,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(269,78,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(270,79,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(271,80,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(272,81,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(273,82,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(274,83,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(275,84,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(276,85,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(277,86,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(278,87,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(279,88,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(280,89,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(281,90,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(282,91,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(283,92,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(284,93,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(285,94,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(286,95,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(287,96,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(288,97,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(289,98,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(290,99,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(291,100,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(292,101,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(293,102,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(294,103,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(295,104,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(296,105,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(297,106,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(298,107,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(299,108,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(300,109,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(301,110,NULL,'System','lead','segment','added',72,'2024-07-17 23:03:04','{\"object_description\":\"Segment Test 5\"}'),(302,57,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(303,58,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(304,60,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(305,61,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(306,62,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(307,64,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(308,65,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(309,66,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(310,71,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(311,72,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(312,74,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(313,75,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(314,76,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(315,78,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(316,81,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(317,82,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(318,83,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(319,86,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(320,87,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(321,88,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(322,89,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(323,91,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(324,93,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(325,94,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(326,95,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(327,98,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(328,99,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(329,100,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(330,101,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(331,102,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(332,104,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(333,105,NULL,'System','lead','segment','added',73,'2024-07-17 23:03:06','{\"object_description\":\"Like segment test with field percent sign at end\"}'),(334,57,NULL,'System','lead','segment','added',75,'2024-07-17 23:03:07','{\"object_description\":\"Segment with manual members added and removed\"}'),(335,59,NULL,'System','lead','segment','added',75,'2024-07-17 23:03:07','{\"object_description\":\"Segment with manual members added and removed\"}'),(336,60,NULL,'System','lead','segment','added',75,'2024-07-17 23:03:07','{\"object_description\":\"Segment with manual members added and removed\"}'),(337,63,NULL,'System','lead','segment','added',75,'2024-07-17 23:03:07','{\"object_description\":\"Segment with manual members added and removed\"}'),(338,70,NULL,'System','lead','segment','added',75,'2024-07-17 23:03:07','{\"object_description\":\"Segment with manual members added and removed\"}'),(339,76,NULL,'System','lead','segment','added',75,'2024-07-17 23:03:07','{\"object_description\":\"Segment with manual members added and removed\"}'),(340,79,NULL,'System','lead','segment','added',75,'2024-07-17 23:03:07','{\"object_description\":\"Segment with manual members added and removed\"}'),(341,82,NULL,'System','lead','segment','added',75,'2024-07-17 23:03:07','{\"object_description\":\"Segment with manual members added and removed\"}'),(342,85,NULL,'System','lead','segment','added',75,'2024-07-17 23:03:07','{\"object_description\":\"Segment with manual members added and removed\"}'),(343,86,NULL,'System','lead','segment','added',75,'2024-07-17 23:03:07','{\"object_description\":\"Segment with manual members added and removed\"}'),(344,87,NULL,'System','lead','segment','added',75,'2024-07-17 23:03:07','{\"object_description\":\"Segment with manual members added and removed\"}'),(345,58,NULL,'System','lead','segment','added',75,'2024-07-17 23:03:07','{\"object_description\":\"Segment with manual members added and removed\"}'),(346,57,NULL,'System','lead','segment','added',86,'2024-07-17 23:03:08','{\"object_description\":\"Segment with filters and only manually removed contacts\"}'),(347,59,NULL,'System','lead','segment','added',86,'2024-07-17 23:03:08','{\"object_description\":\"Segment with filters and only manually removed contacts\"}'),(348,60,NULL,'System','lead','segment','added',86,'2024-07-17 23:03:08','{\"object_description\":\"Segment with filters and only manually removed contacts\"}'),(349,63,NULL,'System','lead','segment','added',86,'2024-07-17 23:03:08','{\"object_description\":\"Segment with filters and only manually removed contacts\"}'),(350,70,NULL,'System','lead','segment','added',86,'2024-07-17 23:03:08','{\"object_description\":\"Segment with filters and only manually removed contacts\"}'),(351,76,NULL,'System','lead','segment','added',86,'2024-07-17 23:03:08','{\"object_description\":\"Segment with filters and only manually removed contacts\"}'),(352,79,NULL,'System','lead','segment','added',86,'2024-07-17 23:03:08','{\"object_description\":\"Segment with filters and only manually removed contacts\"}'),(353,82,NULL,'System','lead','segment','added',86,'2024-07-17 23:03:08','{\"object_description\":\"Segment with filters and only manually removed contacts\"}'),(354,85,NULL,'System','lead','segment','added',86,'2024-07-17 23:03:08','{\"object_description\":\"Segment with filters and only manually removed contacts\"}'),(355,86,NULL,'System','lead','segment','added',86,'2024-07-17 23:03:08','{\"object_description\":\"Segment with filters and only manually removed contacts\"}'),(356,87,NULL,'System','lead','segment','added',86,'2024-07-17 23:03:08','{\"object_description\":\"Segment with filters and only manually removed contacts\"}'),(357,57,NULL,'System','lead','segment','added',87,'2024-07-17 23:03:09','{\"object_description\":\"Segment with same filters as another that has manually removed contacts\"}'),(358,59,NULL,'System','lead','segment','added',87,'2024-07-17 23:03:09','{\"object_description\":\"Segment with same filters as another that has manually removed contacts\"}'),(359,60,NULL,'System','lead','segment','added',87,'2024-07-17 23:03:09','{\"object_description\":\"Segment with same filters as another that has manually removed contacts\"}'),(360,63,NULL,'System','lead','segment','added',87,'2024-07-17 23:03:09','{\"object_description\":\"Segment with same filters as another that has manually removed contacts\"}'),(361,70,NULL,'System','lead','segment','added',87,'2024-07-17 23:03:09','{\"object_description\":\"Segment with same filters as another that has manually removed contacts\"}'),(362,76,NULL,'System','lead','segment','added',87,'2024-07-17 23:03:09','{\"object_description\":\"Segment with same filters as another that has manually removed contacts\"}'),(363,79,NULL,'System','lead','segment','added',87,'2024-07-17 23:03:09','{\"object_description\":\"Segment with same filters as another that has manually removed contacts\"}'),(364,82,NULL,'System','lead','segment','added',87,'2024-07-17 23:03:09','{\"object_description\":\"Segment with same filters as another that has manually removed contacts\"}'),(365,85,NULL,'System','lead','segment','added',87,'2024-07-17 23:03:09','{\"object_description\":\"Segment with same filters as another that has manually removed contacts\"}'),(366,86,NULL,'System','lead','segment','added',87,'2024-07-17 23:03:09','{\"object_description\":\"Segment with same filters as another that has manually removed contacts\"}'),(367,87,NULL,'System','lead','segment','added',87,'2024-07-17 23:03:09','{\"object_description\":\"Segment with same filters as another that has manually removed contacts\"}'),(368,57,NULL,'System','lead','segment','added',89,'2024-07-17 23:03:10','{\"object_description\":\"Segment membership based on regex with special characters\"}'),(369,60,NULL,'System','lead','segment','added',89,'2024-07-17 23:03:10','{\"object_description\":\"Segment membership based on regex with special characters\"}'),(370,61,NULL,'System','lead','segment','added',89,'2024-07-17 23:03:10','{\"object_description\":\"Segment membership based on regex with special characters\"}'),(371,71,NULL,'System','lead','segment','added',89,'2024-07-17 23:03:10','{\"object_description\":\"Segment membership based on regex with special characters\"}'),(372,72,NULL,'System','lead','segment','added',89,'2024-07-17 23:03:10','{\"object_description\":\"Segment membership based on regex with special characters\"}'),(373,74,NULL,'System','lead','segment','added',89,'2024-07-17 23:03:10','{\"object_description\":\"Segment membership based on regex with special characters\"}'),(374,83,NULL,'System','lead','segment','added',89,'2024-07-17 23:03:10','{\"object_description\":\"Segment membership based on regex with special characters\"}'),(375,86,NULL,'System','lead','segment','added',89,'2024-07-17 23:03:10','{\"object_description\":\"Segment membership based on regex with special characters\"}'),(376,91,NULL,'System','lead','segment','added',89,'2024-07-17 23:03:10','{\"object_description\":\"Segment membership based on regex with special characters\"}'),(377,96,NULL,'System','lead','segment','added',89,'2024-07-17 23:03:10','{\"object_description\":\"Segment membership based on regex with special characters\"}'),(378,98,NULL,'System','lead','segment','added',89,'2024-07-17 23:03:10','{\"object_description\":\"Segment membership based on regex with special characters\"}'),(379,60,NULL,'System','lead','segment','added',90,'2024-07-17 23:03:11','{\"object_description\":\"Segment membership based on only company fields\"}'),(380,70,NULL,'System','lead','segment','added',90,'2024-07-17 23:03:11','{\"object_description\":\"Segment membership based on only company fields\"}'),(381,80,NULL,'System','lead','segment','added',90,'2024-07-17 23:03:11','{\"object_description\":\"Segment membership based on only company fields\"}'),(382,90,NULL,'System','lead','segment','added',90,'2024-07-17 23:03:11','{\"object_description\":\"Segment membership based on only company fields\"}'),(383,100,NULL,'System','lead','segment','added',90,'2024-07-17 23:03:11','{\"object_description\":\"Segment membership based on only company fields\"}'),(384,110,NULL,'System','lead','segment','added',90,'2024-07-17 23:03:11','{\"object_description\":\"Segment membership based on only company fields\"}'),(385,57,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(386,58,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(387,59,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(388,60,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(389,61,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(390,62,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(391,63,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(392,64,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(393,65,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(394,66,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(395,67,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(396,68,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(397,69,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(398,70,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(399,71,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(400,72,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(401,73,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(402,74,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(403,75,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(404,76,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(405,77,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(406,78,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(407,79,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(408,80,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(409,81,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(410,82,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(411,83,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(412,84,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(413,85,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(414,86,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(415,87,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(416,88,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(417,89,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(418,90,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(419,91,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(420,92,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(421,93,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(422,94,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(423,95,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(424,96,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(425,97,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(426,98,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(427,99,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(428,100,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(429,101,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(430,102,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(431,103,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(432,104,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(433,105,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(434,106,NULL,'System','lead','segment','added',115,'2024-07-17 23:03:13','{\"object_description\":\"Has company\"}'),(435,107,NULL,'System','lead','segment','added',116,'2024-07-17 23:03:14','{\"object_description\":\"Has no company\"}'),(436,108,NULL,'System','lead','segment','added',116,'2024-07-17 23:03:14','{\"object_description\":\"Has no company\"}'),(437,109,NULL,'System','lead','segment','added',116,'2024-07-17 23:03:14','{\"object_description\":\"Has no company\"}'),(438,110,NULL,'System','lead','segment','added',116,'2024-07-17 23:03:14','{\"object_description\":\"Has no company\"}'),(439,59,NULL,'System','lead','segment','added',117,'2024-07-17 23:03:15','{\"object_description\":\"Has Email and visited URL\"}'),(440,60,NULL,'System','lead','segment','added',117,'2024-07-17 23:03:15','{\"object_description\":\"Has Email and visited URL\"}'),(441,61,NULL,'System','lead','segment','added',117,'2024-07-17 23:03:15','{\"object_description\":\"Has Email and visited URL\"}'),(442,62,NULL,'System','lead','segment','added',117,'2024-07-17 23:03:15','{\"object_description\":\"Has Email and visited URL\"}'),(443,58,NULL,'System','lead','segment','added',108,'2024-07-17 23:03:21','{\"object_description\":\"Clicked link in any email\"}'),(444,59,NULL,'System','lead','segment','added',108,'2024-07-17 23:03:21','{\"object_description\":\"Clicked link in any email\"}'),(445,58,NULL,'System','lead','segment','added',110,'2024-07-17 23:03:23','{\"object_description\":\"Clicked link in any email on specific date\"}'),(446,59,NULL,'System','lead','segment','added',110,'2024-07-17 23:03:23','{\"object_description\":\"Clicked link in any email on specific date\"}'),(447,58,NULL,'System','lead','segment','added',111,'2024-07-17 23:03:24','{\"object_description\":\"Clicked link in any sms\"}'),(448,59,NULL,'System','lead','segment','added',111,'2024-07-17 23:03:24','{\"object_description\":\"Clicked link in any sms\"}'),(449,60,NULL,'System','lead','segment','added',111,'2024-07-17 23:03:24','{\"object_description\":\"Clicked link in any sms\"}'),(450,59,NULL,'System','lead','segment','added',112,'2024-07-17 23:03:25','{\"object_description\":\"Clicked link in any sms on specific date\"}'),(451,60,NULL,'System','lead','segment','added',112,'2024-07-17 23:03:25','{\"object_description\":\"Clicked link in any sms on specific date\"}'),(452,57,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(453,60,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(454,61,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(455,62,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(456,63,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(457,64,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(458,65,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(459,66,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(460,67,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(461,68,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(462,69,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(463,70,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(464,71,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(465,72,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(466,73,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(467,74,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(468,75,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(469,76,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(470,77,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(471,78,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(472,79,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(473,80,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(474,81,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(475,82,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(476,83,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(477,84,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(478,85,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(479,86,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(480,87,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(481,88,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(482,89,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(483,90,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(484,91,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(485,92,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(486,93,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(487,94,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(488,95,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(489,96,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(490,97,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(491,98,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(492,99,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(493,100,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(494,101,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(495,102,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(496,103,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(497,104,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(498,105,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(499,106,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(500,107,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(501,108,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(502,109,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(503,110,NULL,'System','lead','segment','added',109,'2024-07-17 23:03:26','{\"object_description\":\"Did not click link in any email\"}'),(504,58,NULL,'System','lead','segment','added',107,'2024-07-17 23:03:27','{\"object_description\":\"Manually unsubscribed SMS\"}'),(505,57,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(506,58,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(507,59,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(508,60,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(509,61,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(510,62,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(511,63,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(512,64,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(513,65,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(514,66,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(515,67,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(516,68,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(517,69,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(518,70,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(519,71,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(520,72,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(521,73,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(522,74,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(523,75,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(524,76,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(525,77,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(526,78,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(527,79,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(528,80,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(529,81,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(530,82,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(531,83,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(532,84,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(533,85,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(534,86,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(535,87,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(536,88,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(537,89,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(538,90,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(539,91,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(540,92,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(541,93,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(542,94,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(543,95,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(544,96,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(545,97,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(546,98,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(547,99,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(548,100,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(549,101,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(550,102,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(551,103,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(552,104,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(553,105,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(554,106,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(555,107,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(556,108,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(557,109,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(558,110,NULL,'System','lead','segment','added',105,'2024-07-17 23:03:28','{\"object_description\":\"Name is not equal (not null test)\"}'),(559,58,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(560,60,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(561,61,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(562,62,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(563,64,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(564,65,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(565,66,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(566,72,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(567,75,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(568,76,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(569,78,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(570,86,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(571,87,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(572,88,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(573,89,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(574,93,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(575,94,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(576,95,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(577,98,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(578,100,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(579,101,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(580,102,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(581,104,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(582,105,NULL,'System','lead','segment','added',70,'2024-07-17 23:03:30','{\"object_description\":\"Segment Test 3\"}'),(583,57,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(584,59,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(585,61,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(586,62,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(587,63,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(588,64,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(589,65,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(590,66,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(591,67,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(592,68,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(593,69,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(594,70,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(595,71,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(596,72,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(597,73,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(598,74,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(599,75,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(600,76,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(601,77,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(602,78,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(603,79,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(604,80,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(605,81,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(606,82,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(607,83,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(608,84,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(609,85,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(610,86,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(611,87,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(612,88,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(613,89,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(614,90,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(615,91,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(616,92,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(617,93,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(618,94,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(619,95,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(620,96,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(621,97,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(622,98,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(623,99,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(624,100,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(625,101,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(626,102,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(627,103,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(628,104,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(629,105,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(630,106,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(631,107,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(632,108,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(633,109,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(634,110,NULL,'System','lead','segment','added',113,'2024-07-17 23:03:31','{\"object_description\":\"Tags empty\"}'),(635,58,NULL,'System','lead','segment','added',114,'2024-07-17 23:03:33','{\"object_description\":\"Tags not empty\"}'),(636,60,NULL,'System','lead','segment','added',114,'2024-07-17 23:03:33','{\"object_description\":\"Tags not empty\"}');
/*!40000 ALTER TABLE `lead_event_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_fields`
--

DROP TABLE IF EXISTS `lead_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `label` varchar(191) NOT NULL,
  `alias` varchar(191) NOT NULL,
  `type` varchar(50) NOT NULL,
  `field_group` varchar(191) DEFAULT NULL,
  `default_value` varchar(191) DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL,
  `is_fixed` tinyint(1) NOT NULL,
  `is_visible` tinyint(1) NOT NULL,
  `is_short_visible` tinyint(1) NOT NULL,
  `is_listable` tinyint(1) NOT NULL,
  `is_publicly_updatable` tinyint(1) NOT NULL,
  `is_unique_identifer` tinyint(1) DEFAULT NULL,
  `is_index` tinyint(1) DEFAULT NULL,
  `char_length_limit` int(11) DEFAULT NULL,
  `field_order` int(11) DEFAULT NULL,
  `object` varchar(191) DEFAULT NULL,
  `properties` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `column_is_not_created` tinyint(1) NOT NULL DEFAULT 0,
  `original_is_published_value` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_object_field_order_is_published` (`object`,`field_order`,`is_published`)
) ENGINE=InnoDB AUTO_INCREMENT=130 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_fields`
--

LOCK TABLES `lead_fields` WRITE;
/*!40000 ALTER TABLE `lead_fields` DISABLE KEYS */;
INSERT INTO `lead_fields` VALUES (87,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Title','title','lookup','core',NULL,0,1,1,0,1,0,0,0,64,1,'lead','a:1:{s:4:\"list\";a:3:{i:0;s:2:\"Mr\";i:1;s:3:\"Mrs\";i:2;s:4:\"Miss\";}}',0,0),(88,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'First Name','firstname','text','core',NULL,0,1,1,1,1,0,0,0,64,2,'lead','a:0:{}',0,0),(89,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Last Name','lastname','text','core',NULL,0,1,1,1,1,0,0,0,64,3,'lead','a:0:{}',0,0),(90,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Primary company','company','text','core',NULL,0,1,1,0,1,0,0,0,64,4,'lead','a:0:{}',0,0),(91,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Position','position','text','core',NULL,0,1,1,0,1,0,0,0,64,5,'lead','a:0:{}',0,0),(92,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Email','email','email','core',NULL,0,1,1,1,1,0,1,1,64,6,'lead','a:0:{}',0,0),(93,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Mobile','mobile','tel','core',NULL,0,1,1,0,1,0,0,0,64,7,'lead','a:0:{}',0,0),(94,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Phone','phone','tel','core',NULL,0,1,1,0,1,0,0,0,64,8,'lead','a:0:{}',0,0),(95,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Points','points','number','core','0',0,1,1,0,1,0,0,0,64,9,'lead','a:0:{}',0,0),(96,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Fax','fax','tel','core',NULL,0,0,1,0,1,0,0,0,64,10,'lead','a:0:{}',0,0),(97,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Address Line 1','address1','text','core',NULL,0,1,1,0,1,0,0,0,64,11,'lead','a:0:{}',0,0),(98,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Address Line 2','address2','text','core',NULL,0,1,1,0,1,0,0,0,64,12,'lead','a:0:{}',0,0),(99,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'City','city','text','core',NULL,0,1,1,0,1,0,0,0,64,13,'lead','a:0:{}',0,0),(100,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'State','state','region','core',NULL,0,1,1,0,1,0,0,0,64,14,'lead','a:0:{}',0,0),(101,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Zip Code','zipcode','text','core',NULL,0,1,1,0,1,0,0,0,64,15,'lead','a:0:{}',0,0),(102,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Country','country','country','core',NULL,0,1,1,0,1,0,0,0,64,16,'lead','a:0:{}',0,0),(103,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Preferred Locale','preferred_locale','locale','core',NULL,0,1,1,0,1,0,0,0,64,17,'lead','a:0:{}',0,0),(104,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Preferred Timezone','timezone','timezone','core',NULL,0,1,1,0,1,0,0,0,64,18,'lead','a:0:{}',0,0),(105,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Date Last Active','last_active','datetime','core',NULL,0,1,1,0,1,0,0,0,64,19,'lead','a:0:{}',0,0),(106,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Attribution Date','attribution_date','datetime','core',NULL,0,1,1,0,1,0,0,0,64,20,'lead','a:0:{}',0,0),(107,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Attribution','attribution','number','core',NULL,0,1,1,0,1,0,0,0,64,21,'lead','a:2:{s:9:\"roundmode\";i:4;s:5:\"scale\";i:2;}',0,0),(108,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Website','website','url','core',NULL,0,0,1,0,1,0,0,0,64,22,'lead','a:0:{}',0,0),(109,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Facebook','facebook','text','social',NULL,0,0,1,0,1,0,0,0,64,23,'lead','a:0:{}',0,0),(110,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Foursquare','foursquare','text','social',NULL,0,0,1,0,1,0,0,0,64,24,'lead','a:0:{}',0,0),(111,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Instagram','instagram','text','social',NULL,0,0,1,0,1,0,0,0,64,25,'lead','a:0:{}',0,0),(112,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mautic.lead.field.linkedin','linkedin','text','social',NULL,0,0,1,0,1,0,0,0,64,26,'lead','a:0:{}',0,0),(113,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Skype','skype','text','social',NULL,0,0,1,0,1,0,0,0,64,27,'lead','a:0:{}',0,0),(114,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Twitter','twitter','text','social',NULL,0,0,1,0,1,0,0,0,64,28,'lead','a:0:{}',0,0),(115,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Address 1','companyaddress1','text','core',NULL,0,1,1,0,1,0,0,0,64,1,'company','a:0:{}',0,0),(116,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Address 2','companyaddress2','text','core',NULL,0,1,1,0,1,0,0,0,64,2,'company','a:0:{}',0,0),(117,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Company Email','companyemail','email','core',NULL,0,1,1,0,1,0,0,0,64,3,'company','a:0:{}',0,0),(118,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Phone','companyphone','tel','core',NULL,0,1,1,0,1,0,0,0,64,4,'company','a:0:{}',0,0),(119,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'City','companycity','text','core',NULL,0,1,1,0,1,0,0,0,64,5,'company','a:0:{}',0,0),(120,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'State','companystate','region','core',NULL,0,1,1,0,1,0,0,0,64,6,'company','a:0:{}',0,0),(121,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Zip Code','companyzipcode','text','core',NULL,0,1,1,0,1,0,0,0,64,7,'company','a:0:{}',0,0),(122,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Country','companycountry','country','core',NULL,0,1,1,0,1,0,0,0,64,8,'company','a:0:{}',0,0),(123,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Company Name','companyname','text','core',NULL,1,1,1,0,1,0,1,1,64,9,'company','a:0:{}',0,0),(124,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Website','companywebsite','url','core',NULL,0,1,1,0,1,0,0,0,64,10,'company','a:0:{}',0,0),(125,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Number of Employees','companynumber_of_employees','number','professional',NULL,0,0,1,0,1,0,0,0,64,11,'company','a:2:{s:9:\"roundmode\";i:4;s:5:\"scale\";i:0;}',0,0),(126,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Fax','companyfax','tel','professional',NULL,0,0,1,0,1,0,0,0,64,12,'company','a:0:{}',0,0),(127,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Annual Revenue','companyannual_revenue','number','professional',NULL,0,0,1,0,1,0,0,0,64,13,'company','a:2:{s:9:\"roundmode\";i:4;s:5:\"scale\";i:2;}',0,0),(128,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Industry','companyindustry','select','professional',NULL,0,1,1,0,1,0,0,0,64,14,'company','a:1:{s:4:\"list\";a:41:{i:0;a:2:{s:5:\"label\";s:19:\"Aerospace & Defense\";s:5:\"value\";s:19:\"Aerospace & Defense\";}i:1;a:2:{s:5:\"label\";s:11:\"Agriculture\";s:5:\"value\";s:11:\"Agriculture\";}i:2;a:2:{s:5:\"label\";s:7:\"Apparel\";s:5:\"value\";s:7:\"Apparel\";}i:3;a:2:{s:5:\"label\";s:21:\"Automotive & Assembly\";s:5:\"value\";s:21:\"Automotive & Assembly\";}i:4;a:2:{s:5:\"label\";s:7:\"Banking\";s:5:\"value\";s:7:\"Banking\";}i:5;a:2:{s:5:\"label\";s:13:\"Biotechnology\";s:5:\"value\";s:13:\"Biotechnology\";}i:6;a:2:{s:5:\"label\";s:9:\"Chemicals\";s:5:\"value\";s:9:\"Chemicals\";}i:7;a:2:{s:5:\"label\";s:14:\"Communications\";s:5:\"value\";s:14:\"Communications\";}i:8;a:2:{s:5:\"label\";s:12:\"Construction\";s:5:\"value\";s:12:\"Construction\";}i:9;a:2:{s:5:\"label\";s:23:\"Consumer Packaged Goods\";s:5:\"value\";s:23:\"Consumer Packaged Goods\";}i:10;a:2:{s:5:\"label\";s:9:\"Education\";s:5:\"value\";s:9:\"Education\";}i:11;a:2:{s:5:\"label\";s:11:\"Electronics\";s:5:\"value\";s:11:\"Electronics\";}i:12;a:2:{s:5:\"label\";s:6:\"Energy\";s:5:\"value\";s:6:\"Energy\";}i:13;a:2:{s:5:\"label\";s:11:\"Engineering\";s:5:\"value\";s:11:\"Engineering\";}i:14;a:2:{s:5:\"label\";s:13:\"Entertainment\";s:5:\"value\";s:13:\"Entertainment\";}i:15;a:2:{s:5:\"label\";s:13:\"Environmental\";s:5:\"value\";s:13:\"Environmental\";}i:16;a:2:{s:5:\"label\";s:7:\"Finance\";s:5:\"value\";s:7:\"Finance\";}i:17;a:2:{s:5:\"label\";s:15:\"Food & Beverage\";s:5:\"value\";s:15:\"Food & Beverage\";}i:18;a:2:{s:5:\"label\";s:10:\"Government\";s:5:\"value\";s:10:\"Government\";}i:19;a:2:{s:5:\"label\";s:10:\"Healthcare\";s:5:\"value\";s:10:\"Healthcare\";}i:20;a:2:{s:5:\"label\";s:11:\"Hospitality\";s:5:\"value\";s:11:\"Hospitality\";}i:21;a:2:{s:5:\"label\";s:9:\"Insurance\";s:5:\"value\";s:9:\"Insurance\";}i:22;a:2:{s:5:\"label\";s:9:\"Machinery\";s:5:\"value\";s:9:\"Machinery\";}i:23;a:2:{s:5:\"label\";s:13:\"Manufacturing\";s:5:\"value\";s:13:\"Manufacturing\";}i:24;a:2:{s:5:\"label\";s:5:\"Media\";s:5:\"value\";s:5:\"Media\";}i:25;a:2:{s:5:\"label\";s:15:\"Metals & Mining\";s:5:\"value\";s:15:\"Metals & Mining\";}i:26;a:2:{s:5:\"label\";s:14:\"Not for Profit\";s:5:\"value\";s:14:\"Not for Profit\";}i:27;a:2:{s:5:\"label\";s:9:\"Oil & Gas\";s:5:\"value\";s:9:\"Oil & Gas\";}i:28;a:2:{s:5:\"label\";s:17:\"Packaging & Paper\";s:5:\"value\";s:17:\"Packaging & Paper\";}i:29;a:2:{s:5:\"label\";s:36:\"Private Equity & Principal Investors\";s:5:\"value\";s:36:\"Private Equity & Principal Investors\";}i:30;a:2:{s:5:\"label\";s:10:\"Recreation\";s:5:\"value\";s:10:\"Recreation\";}i:31;a:2:{s:5:\"label\";s:11:\"Real Estate\";s:5:\"value\";s:11:\"Real Estate\";}i:32;a:2:{s:5:\"label\";s:6:\"Retail\";s:5:\"value\";s:6:\"Retail\";}i:33;a:2:{s:5:\"label\";s:14:\"Semiconductors\";s:5:\"value\";s:14:\"Semiconductors\";}i:34;a:2:{s:5:\"label\";s:8:\"Shipping\";s:5:\"value\";s:8:\"Shipping\";}i:35;a:2:{s:5:\"label\";s:13:\"Social Sector\";s:5:\"value\";s:13:\"Social Sector\";}i:36;a:2:{s:5:\"label\";s:10:\"Technology\";s:5:\"value\";s:10:\"Technology\";}i:37;a:2:{s:5:\"label\";s:18:\"Telecommunications\";s:5:\"value\";s:18:\"Telecommunications\";}i:38;a:2:{s:5:\"label\";s:14:\"Transportation\";s:5:\"value\";s:14:\"Transportation\";}i:39;a:2:{s:5:\"label\";s:9:\"Utilities\";s:5:\"value\";s:9:\"Utilities\";}i:40;a:2:{s:5:\"label\";s:5:\"Other\";s:5:\"value\";s:5:\"Other\";}}}',0,0),(129,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Description','companydescription','text','professional',NULL,0,1,1,0,1,0,0,0,64,15,'company','a:0:{}',0,0);
/*!40000 ALTER TABLE `lead_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_frequencyrules`
--

DROP TABLE IF EXISTS `lead_frequencyrules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_frequencyrules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  `frequency_number` smallint(6) DEFAULT NULL,
  `frequency_time` varchar(25) DEFAULT NULL,
  `channel` varchar(191) NOT NULL,
  `preferred_channel` tinyint(1) NOT NULL,
  `pause_from_date` datetime DEFAULT NULL,
  `pause_to_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_AA8A57F455458D` (`lead_id`),
  KEY `channel_frequency` (`channel`),
  KEY `idx_frequency_date_added` (`lead_id`,`date_added`),
  CONSTRAINT `FK_AA8A57F455458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_frequencyrules`
--

LOCK TABLES `lead_frequencyrules` WRITE;
/*!40000 ALTER TABLE `lead_frequencyrules` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_frequencyrules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_ips_xref`
--

DROP TABLE IF EXISTS `lead_ips_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_ips_xref` (
  `lead_id` bigint(20) unsigned NOT NULL,
  `ip_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`lead_id`,`ip_id`),
  KEY `IDX_9EED7E66A03F5E9F` (`ip_id`),
  CONSTRAINT `FK_9EED7E6655458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_9EED7E66A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `ip_addresses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_ips_xref`
--

LOCK TABLES `lead_ips_xref` WRITE;
/*!40000 ALTER TABLE `lead_ips_xref` DISABLE KEYS */;
INSERT INTO `lead_ips_xref` VALUES (57,55),(58,56),(59,57),(60,58),(61,59),(62,60),(63,61),(64,62),(65,63),(66,64),(67,65),(68,66),(69,67),(70,68),(71,69),(72,70),(73,71),(74,72),(75,73),(76,74),(77,75),(78,76),(79,77),(80,78),(81,79),(82,80),(83,81),(84,82),(85,83),(86,84),(87,85),(88,86),(89,87),(90,88),(91,89),(92,90),(93,91),(94,92),(95,93),(96,94),(97,95),(98,96),(99,97),(100,98),(101,99),(102,100),(103,101),(104,102),(105,103),(106,104),(107,105),(108,106),(109,107),(110,108);
/*!40000 ALTER TABLE `lead_ips_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_lists`
--

DROP TABLE IF EXISTS `lead_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_lists` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `alias` varchar(191) NOT NULL,
  `public_name` varchar(191) NOT NULL,
  `filters` longtext NOT NULL COMMENT '(DC2Type:array)',
  `is_global` tinyint(1) NOT NULL,
  `is_preference_center` tinyint(1) NOT NULL,
  `last_built_date` datetime DEFAULT NULL,
  `last_built_time` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6EC1522A12469DE2` (`category_id`),
  KEY `lead_list_alias` (`alias`),
  CONSTRAINT `FK_6EC1522A12469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=119 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_lists`
--

LOCK TABLES `lead_lists` WRITE;
/*!40000 ALTER TABLE `lead_lists` DISABLE KEYS */;
INSERT INTO `lead_lists` VALUES (60,NULL,1,NULL,NULL,NULL,NULL,NULL,' ',NULL,NULL,NULL,'Lead List 1 - Segment Category 1',NULL,'lead-list-1','Lead List 1','a:0:{}',1,0,'2024-07-17 23:03:26',0),(61,NULL,1,NULL,NULL,NULL,NULL,NULL,' ',NULL,NULL,NULL,'Lead List 2 - Segment Category 2',NULL,'lead-list-2','Lead List 2','a:0:{}',1,0,'2024-07-17 23:03:26',0),(62,NULL,1,NULL,NULL,NULL,NULL,NULL,' ',NULL,NULL,NULL,'Lead List 3 - Segment Category 2',NULL,'lead-list-3','Lead List 3','a:0:{}',1,0,'2024-07-17 23:03:26',0),(63,NULL,1,NULL,NULL,NULL,NULL,NULL,' ',NULL,NULL,NULL,'Lead List 4 - Segment Category 1',NULL,'lead-list-4','Lead List 4','a:0:{}',1,0,'2024-07-17 23:03:26',0),(64,NULL,1,NULL,NULL,NULL,NULL,NULL,' ',NULL,NULL,NULL,'Lead List 5 - Segment Category 1',NULL,'lead-list-5','Lead List 5','a:0:{}',1,0,'2024-07-17 23:03:26',0),(65,NULL,1,NULL,NULL,NULL,NULL,NULL,' ',NULL,NULL,NULL,'Lead List 6 - Segment Category 1',NULL,'lead-list-61','Lead List 6','a:0:{}',1,0,'2024-07-17 23:03:26',0),(66,NULL,1,NULL,NULL,NULL,NULL,NULL,' ',NULL,NULL,NULL,'Lead List 7 - Segment No Category ',NULL,'lead-list-6','Lead List 6','a:0:{}',1,0,'2024-07-17 23:03:26',0),(67,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'United States',NULL,'us','United States','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:6:\"lookup\";s:5:\"field\";s:7:\"country\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:13:\"United States\";s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:33',0.01),(68,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment Test 1',NULL,'segment-test-1','Segment Test 1','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:6:\"lookup\";s:5:\"field\";s:5:\"state\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:2:\"IA\";s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:29',0),(69,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment Test 2',NULL,'segment-test-2','Segment Test 2','a:2:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:6:\"lookup\";s:5:\"field\";s:5:\"state\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:2:\"IA\";s:7:\"display\";s:0:\"\";}i:1;a:6:{s:4:\"glue\";s:2:\"or\";s:4:\"type\";s:6:\"lookup\";s:5:\"field\";s:5:\"state\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:3:\"QLD\";s:7:\"display\";s:0:\"\";}}',0,0,'2024-07-17 23:03:29',0.01),(70,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment Test 3',NULL,'segment-test-3','Segment Test 3','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:6:\"lookup\";s:5:\"field\";s:5:\"title\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:3:\"Mr.\";s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:30',1.3),(71,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment Test 4',NULL,'segment-test-4','Segment Test 4','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:5:\"field\";s:7:\"hit_url\";s:8:\"operator\";s:4:\"like\";s:6:\"filter\";s:8:\"test.com\";s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:30',0.01),(72,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment Test 5',NULL,'segment-test-5','Segment Test 5','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:5:\"field\";s:7:\"hit_url\";s:8:\"operator\";s:5:\"!like\";s:6:\"filter\";s:8:\"test.com\";s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:30',0),(73,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Like segment test with field percent sign at end',NULL,'like-percent-end','Like segment test with field percent sign at end','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:5:\"field\";s:5:\"title\";s:8:\"operator\";s:4:\"like\";s:6:\"filter\";s:3:\"Mr%\";s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:26',0),(74,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment without filters',NULL,'segment-test-without-filters','Segment without filters','a:0:{}',1,0,'2024-07-17 23:03:30',0),(75,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with manual members added and removed',NULL,'segment-test-manual-membership','Segment with manual members added and removed','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:5:\"field\";s:7:\"country\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:14:\"United Kingdom\";s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:30',0.01),(76,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Include segment membership with filters',NULL,'segment-test-include-segment-with-filters','Include segment membership with filters','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"leadlist\";s:5:\"field\";s:8:\"leadlist\";s:8:\"operator\";s:2:\"in\";s:6:\"filter\";a:2:{i:0;i:3;i:1;i:4;}s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:26',0),(77,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Exclude segment membership with filters',NULL,'segment-test-exclude-segment-with-filters','Exclude segment membership with filters','a:2:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:5:\"field\";s:7:\"country\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:13:\"United States\";s:7:\"display\";s:0:\"\";}i:1;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"leadlist\";s:5:\"field\";s:8:\"leadlist\";s:8:\"operator\";s:3:\"!in\";s:6:\"filter\";a:1:{i:0;i:3;}s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:26',0.02),(78,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Include segment membership without filters',NULL,'segment-test-include-segment-without-filters','Include segment membership without filters','a:2:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:5:\"field\";s:7:\"country\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:14:\"United Kingdom\";s:7:\"display\";s:0:\"\";}i:1;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"leadlist\";s:5:\"field\";s:8:\"leadlist\";s:8:\"operator\";s:2:\"in\";s:6:\"filter\";a:1:{i:0;i:8;}s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:26',0),(79,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Exclude segment membership without filters',NULL,'segment-test-exclude-segment-without-filters','Exclude segment membership without filters','a:2:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:5:\"field\";s:7:\"country\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:14:\"United Kingdom\";s:7:\"display\";s:0:\"\";}i:1;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"leadlist\";s:5:\"field\";s:8:\"leadlist\";s:8:\"operator\";s:3:\"!in\";s:6:\"filter\";a:1:{i:0;i:8;}s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:26',0),(80,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Include segment membership with mixed filters',NULL,'segment-test-include-segment-mixed-filters','Include segment membership with mixed filters','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"leadlist\";s:5:\"field\";s:8:\"leadlist\";s:8:\"operator\";s:2:\"in\";s:6:\"filter\";a:2:{i:0;i:4;i:1;i:8;}s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:26',0),(81,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Exclude segment membership with mixed filters',NULL,'segment-test-exclude-segment-mixed-filters','Exclude segment membership with mixed filters','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"leadlist\";s:5:\"field\";s:8:\"leadlist\";s:8:\"operator\";s:3:\"!in\";s:6:\"filter\";a:2:{i:0;i:4;i:1;i:8;}s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:26',0),(82,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment membership with mixed include and exclude',NULL,'segment-test-mixed-include-exclude-filters','Segment membership with mixed include and exclude','a:2:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"leadlist\";s:5:\"field\";s:8:\"leadlist\";s:8:\"operator\";s:2:\"in\";s:6:\"filter\";a:1:{i:0;i:7;}s:7:\"display\";s:0:\"\";}i:1;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"leadlist\";s:5:\"field\";s:8:\"leadlist\";s:8:\"operator\";s:3:\"!in\";s:6:\"filter\";a:1:{i:0;i:4;}s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:29',0),(83,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment membership with including segment that has manual membership',NULL,'segment-test-include-segment-manual-members','Segment membership with including segment that has manual membership','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"leadlist\";s:5:\"field\";s:8:\"leadlist\";s:8:\"operator\";s:2:\"in\";s:6:\"filter\";a:1:{i:0;i:9;}s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:29',0),(84,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment membership with excluded segment that has manual membership',NULL,'segment-test-exclude-segment-manual-members','Segment membership with excluded segment that has manual membership','a:2:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:5:\"field\";s:5:\"title\";s:8:\"operator\";s:4:\"like\";s:6:\"filter\";s:3:\"Mr%\";s:7:\"display\";s:0:\"\";}i:1;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"leadlist\";s:5:\"field\";s:8:\"leadlist\";s:8:\"operator\";s:3:\"!in\";s:6:\"filter\";a:1:{i:0;i:9;}s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:29',0),(85,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment membership with excluded segment without other filters',NULL,'segment-test-exclude-segment-without-other-filters','Segment membership with excluded segment without other filters','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"leadlist\";s:5:\"field\";s:8:\"leadlist\";s:8:\"operator\";s:3:\"!in\";s:6:\"filter\";a:1:{i:0;i:9;}s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:29',0),(86,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with filters and only manually removed contacts',NULL,'segment-test-filters-and-removed','Segment with filters and only manually removed contacts','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:5:\"field\";s:7:\"country\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:14:\"United Kingdom\";s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:30',0.01),(87,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with same filters as another that has manually removed contacts',NULL,'segment-test-include-segment-with-segment-manual-removal-same-filters','Segment with same filters as another that has manually removed contacts','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:5:\"field\";s:7:\"country\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:14:\"United Kingdom\";s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:30',0.01),(88,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment membership with including segment that has a contact thats been removed from non-related segment',NULL,'segment-test-include-segment-with-unrelated-segment-manual-removal','Segment membership with including segment that has a contact thats been removed from non-related segment','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"leadlist\";s:5:\"field\";s:8:\"leadlist\";s:8:\"operator\";s:2:\"in\";s:6:\"filter\";a:1:{i:0;i:21;}s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:29',0),(89,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment membership based on regex with special characters',NULL,'segment-membership-regexp','Segment membership based on regex with special characters','a:2:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:5:\"field\";s:5:\"email\";s:8:\"operator\";s:6:\"regexp\";s:6:\"filter\";s:83:\"^.*(#|!|\\\\$|%|&|\\\\*|\\\\(|\\\\)|\\\\^|\\\\?|\\\\+|-|dayrep\\\\.com|http|gmail|abc|qwe|[0-9]).*$\";s:7:\"display\";s:0:\"\";}i:1;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:5:\"field\";s:5:\"email\";s:8:\"operator\";s:6:\"!empty\";s:6:\"filter\";N;s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:29',0.01),(90,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment membership based on only company fields',NULL,'segment-company-only-fields','Segment membership based on only company fields','a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:7:\"company\";s:5:\"field\";s:11:\"companycity\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:7:\"Houston\";s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:29',0.01),(91,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment membership with excluded segment without other filters',NULL,'segment-including-segment-with-company-only-fields','Segment membership with excluded segment without other filters','a:2:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:7:\"company\";s:5:\"field\";s:15:\"companyindustry\";s:8:\"operator\";s:2:\"in\";s:6:\"filter\";a:2:{i:0;s:8:\"Software\";i:1;s:8:\"Hardware\";}s:7:\"display\";s:0:\"\";}i:1;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"leadlist\";s:5:\"field\";s:8:\"leadlist\";s:8:\"operator\";s:3:\"!in\";s:6:\"filter\";a:1:{i:0;i:21;}s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:29',0),(92,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with relative date - today',NULL,'segment-with-relative-date-today','Segment with relative date - today','a:2:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"datetime\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:15:\"date_identified\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:5:\"today\";s:7:\"display\";N;}i:1;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:8:\"lastname\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:4:\"Date\";s:7:\"display\";N;}}',1,0,'2024-07-17 23:03:30',0.01),(93,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with relative date - tomorrow',NULL,'segment-with-relative-date-tomorrow','Segment with relative date - tomorrow','a:2:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"datetime\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:15:\"date_identified\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:8:\"tomorrow\";s:7:\"display\";N;}i:1;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:8:\"lastname\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:4:\"Date\";s:7:\"display\";N;}}',1,0,'2024-07-17 23:03:30',0.01),(94,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with relative date - yesterday',NULL,'segment-with-relative-date-yesterday','Segment with relative date - yesterday','a:2:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"datetime\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:15:\"date_identified\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:9:\"yesterday\";s:7:\"display\";N;}i:1;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:8:\"lastname\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:4:\"Date\";s:7:\"display\";N;}}',1,0,'2024-07-17 23:03:30',0.01),(95,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with relative date - last week',NULL,'segment-with-relative-date-last-week','Segment with relative date - last week','a:2:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"datetime\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:15:\"date_identified\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:9:\"last week\";s:7:\"display\";N;}i:1;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:8:\"lastname\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:4:\"Date\";s:7:\"display\";N;}}',1,0,'2024-07-17 23:03:30',0.01),(96,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with relative date - next week',NULL,'segment-with-relative-date-next-week','Segment with relative date - next week','a:2:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"datetime\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:15:\"date_identified\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:9:\"next week\";s:7:\"display\";N;}i:1;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:8:\"lastname\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:4:\"Date\";s:7:\"display\";N;}}',1,0,'2024-07-17 23:03:30',0.01),(97,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with relative date - this week',NULL,'segment-with-relative-date-this-week','Segment with relative date - this week','a:2:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"datetime\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:15:\"date_identified\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:9:\"this week\";s:7:\"display\";N;}i:1;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:8:\"lastname\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:4:\"Date\";s:7:\"display\";N;}}',1,0,'2024-07-17 23:03:30',0.01),(98,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with relative date - last month',NULL,'segment-with-relative-date-last-month','Segment with relative date - last month','a:2:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"datetime\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:15:\"date_identified\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:10:\"last month\";s:7:\"display\";N;}i:1;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:8:\"lastname\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:4:\"Date\";s:7:\"display\";N;}}',1,0,'2024-07-17 23:03:30',0.01),(99,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with relative date - next month',NULL,'segment-with-relative-date-next-month','Segment with relative date - next month','a:2:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"datetime\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:15:\"date_identified\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:10:\"next month\";s:7:\"display\";N;}i:1;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:8:\"lastname\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:4:\"Date\";s:7:\"display\";N;}}',1,0,'2024-07-17 23:03:30',0.01),(100,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with relative date - this month',NULL,'segment-with-relative-date-this-month','Segment with relative date - this month','a:2:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"datetime\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:15:\"date_identified\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:10:\"this month\";s:7:\"display\";N;}i:1;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:8:\"lastname\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:4:\"Date\";s:7:\"display\";N;}}',1,0,'2024-07-17 23:03:30',0.01),(101,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with relative date - last year',NULL,'segment-with-relative-date-last-year','Segment with relative date - last year','a:2:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"datetime\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:15:\"date_identified\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:9:\"last year\";s:7:\"display\";N;}i:1;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:8:\"lastname\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:4:\"Date\";s:7:\"display\";N;}}',1,0,'2024-07-17 23:03:30',0.02),(102,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with relative date - next year',NULL,'segment-with-relative-date-next-year','Segment with relative date - next year','a:2:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"datetime\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:15:\"date_identified\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:9:\"next year\";s:7:\"display\";N;}i:1;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:8:\"lastname\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:4:\"Date\";s:7:\"display\";N;}}',1,0,'2024-07-17 23:03:30',0.01),(103,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with relative date - relative plus',NULL,'segment-with-relative-date-relative-plus','Segment with relative date - relative plus','a:2:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"datetime\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:15:\"date_identified\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:7:\"+5 days\";s:7:\"display\";N;}i:1;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:8:\"lastname\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:4:\"Date\";s:7:\"display\";N;}}',1,0,'2024-07-17 23:03:30',0.01),(104,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Segment with relative date - relative minus',NULL,'segment-with-relative-date-relative-minus','Segment with relative date - relative minus','a:2:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:8:\"datetime\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:15:\"date_identified\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:7:\"-4 days\";s:7:\"display\";N;}i:1;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:8:\"lastname\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:4:\"Date\";s:7:\"display\";N;}}',1,0,'2024-07-17 23:03:30',0.01),(105,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Name is not equal (not null test)',NULL,'name-is-not-equal-not-null-test','Name is not equal (not null test)','a:1:{i:0;a:7:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:9:\"firstname\";s:8:\"operator\";s:2:\"!=\";s:6:\"filter\";s:5:\"xxxxx\";s:7:\"display\";N;}}',1,0,'2024-07-17 23:03:29',1.36),(106,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Include segment membership with or',NULL,'segment-test-include-segment-with-or','Include segment membership with or','a:2:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:5:\"field\";s:7:\"country\";s:8:\"operator\";s:1:\"=\";s:6:\"filter\";s:14:\"United Kingdom\";s:7:\"display\";s:0:\"\";}i:1;a:6:{s:4:\"glue\";s:2:\"or\";s:4:\"type\";s:8:\"leadlist\";s:5:\"field\";s:8:\"leadlist\";s:8:\"operator\";s:2:\"in\";s:6:\"filter\";a:1:{i:0;i:8;}s:7:\"display\";s:0:\"\";}}',1,0,'2024-07-17 23:03:26',0),(107,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Manually unsubscribed SMS',NULL,'manually-unsubscribed-sms-test','Manually unsubscribed SMS','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:14:\"dnc_manual_sms\";s:6:\"object\";s:4:\"lead\";s:4:\"type\";s:7:\"boolean\";s:8:\"operator\";s:1:\"=\";s:10:\"properties\";a:1:{s:6:\"filter\";i:1;}}}',1,0,'2024-07-17 23:03:27',1.08),(108,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Clicked link in any email',NULL,'clicked-link-in-any-email','Clicked link in any email','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:8:\"email_id\";s:6:\"object\";s:9:\"behaviors\";s:4:\"type\";s:7:\"boolean\";s:8:\"operator\";s:1:\"=\";s:10:\"properties\";a:1:{s:6:\"filter\";i:1;}}}',1,0,'2024-07-17 23:03:21',1.17),(109,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Did not click link in any email',NULL,'did-not-click-link-in-any-email','Did not click link in any email','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:8:\"email_id\";s:6:\"object\";s:9:\"behaviors\";s:4:\"type\";s:7:\"boolean\";s:8:\"operator\";s:1:\"=\";s:10:\"properties\";a:1:{s:6:\"filter\";i:0;}}}',1,0,'2024-07-17 23:03:26',1.4),(110,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Clicked link in any email on specific date',NULL,'clicked-link-in-any-email-on-specific-date','Clicked link in any email on specific date','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:23:\"email_clicked_link_date\";s:6:\"object\";s:9:\"behaviors\";s:4:\"type\";s:8:\"datetime\";s:8:\"operator\";s:3:\"gte\";s:10:\"properties\";a:1:{s:6:\"filter\";s:16:\"2024-07-17 23:03\";}}}',1,0,'2024-07-17 23:03:23',1.04),(111,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Clicked link in any sms',NULL,'clicked-link-in-any-sms','Clicked link in any sms','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:16:\"sms_clicked_link\";s:6:\"object\";s:9:\"behaviors\";s:4:\"type\";s:7:\"boolean\";s:8:\"operator\";s:1:\"=\";s:10:\"properties\";a:1:{s:6:\"filter\";i:1;}}}',1,0,'2024-07-17 23:03:24',1.03),(112,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Clicked link in any sms on specific date',NULL,'clicked-link-in-any-sms-on-specific-date','Clicked link in any sms on specific date','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:5:\"field\";s:21:\"sms_clicked_link_date\";s:6:\"object\";s:9:\"behaviors\";s:4:\"type\";s:8:\"datetime\";s:8:\"operator\";s:3:\"gte\";s:10:\"properties\";a:1:{s:6:\"filter\";s:16:\"2024-07-17 23:03\";}}}',1,0,'2024-07-17 23:03:25',1.02),(113,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Tags empty',NULL,'tags-empty','Tags empty','a:1:{i:0;a:5:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"tags\";s:5:\"field\";s:4:\"tags\";s:8:\"operator\";s:5:\"empty\";s:10:\"properties\";a:1:{s:6:\"filter\";s:0:\"\";}}}',1,0,'2024-07-17 23:03:32',1.28),(114,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Tags not empty',NULL,'tags-not-empty','Tags not empty','a:1:{i:0;a:5:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"tags\";s:5:\"field\";s:4:\"tags\";s:8:\"operator\";s:8:\"notEmpty\";s:10:\"properties\";a:1:{s:6:\"filter\";s:0:\"\";}}}',1,0,'2024-07-17 23:03:33',1.07),(115,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Has company',NULL,'segment-having-company','Has company','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:7:\"company\";s:8:\"operator\";s:6:\"!empty\";s:10:\"properties\";a:1:{s:6:\"filter\";N;}}}',1,0,'2024-07-17 23:03:26',0.01),(116,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Has no company',NULL,'segment-not-having-company','Has no company','a:1:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:7:\"company\";s:8:\"operator\";s:5:\"empty\";s:10:\"properties\";a:1:{s:6:\"filter\";N;}}}',1,0,'2024-07-17 23:03:26',0.01),(117,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Has Email and visited URL',NULL,'has-email-and-visited-url','Has Email and visited URL','a:2:{i:0;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:5:\"email\";s:6:\"object\";s:4:\"lead\";s:5:\"field\";s:5:\"email\";s:8:\"operator\";s:6:\"!empty\";s:10:\"properties\";a:2:{s:6:\"filter\";N;s:7:\"display\";N;}}i:1;a:6:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:9:\"behaviors\";s:5:\"field\";s:7:\"hit_url\";s:8:\"operator\";s:6:\"regexp\";s:10:\"properties\";a:1:{s:6:\"filter\";s:20:\"segment-[[:digit:]]+\";}}}',1,0,'2024-07-17 23:03:26',0.02),(118,NULL,1,NULL,4,'Admin User',NULL,NULL,' ',NULL,NULL,NULL,'Missing table name',NULL,'table-name-missing-in-filter','Missing table name','a:1:{i:0;a:8:{s:4:\"glue\";s:3:\"and\";s:4:\"type\";s:4:\"text\";s:6:\"object\";s:13:\"custom_object\";s:5:\"field\";s:12:\"firstnameLOL\";s:8:\"operator\";s:2:\"!=\";s:6:\"filter\";s:5:\"xxxxx\";s:7:\"display\";N;s:5:\"table\";s:0:\"\";}}',1,0,'2024-07-17 23:03:27',0);
/*!40000 ALTER TABLE `lead_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_lists_leads`
--

DROP TABLE IF EXISTS `lead_lists_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_lists_leads` (
  `leadlist_id` int(10) unsigned NOT NULL,
  `lead_id` bigint(20) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  `manually_removed` tinyint(1) NOT NULL,
  `manually_added` tinyint(1) NOT NULL,
  PRIMARY KEY (`leadlist_id`,`lead_id`),
  KEY `IDX_F5F47C7C55458D` (`lead_id`),
  KEY `manually_removed` (`manually_removed`),
  CONSTRAINT `FK_F5F47C7C55458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_F5F47C7CB9FC8874` FOREIGN KEY (`leadlist_id`) REFERENCES `lead_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_lists_leads`
--

LOCK TABLES `lead_lists_leads` WRITE;
/*!40000 ALTER TABLE `lead_lists_leads` DISABLE KEYS */;
INSERT INTO `lead_lists_leads` VALUES (67,75,'2024-07-17 23:02:59',0,0),(67,81,'2024-07-17 23:02:59',0,0),(67,84,'2024-07-17 23:02:59',0,0),(67,89,'2024-07-17 23:02:59',0,0),(67,93,'2024-07-17 23:02:59',0,0),(67,94,'2024-07-17 23:02:59',0,0),(67,100,'2024-07-17 23:02:59',0,0),(67,105,'2024-07-17 23:02:59',0,0),(68,81,'2024-07-17 23:03:00',0,0),(69,66,'2024-07-17 23:03:01',0,0),(69,72,'2024-07-17 23:03:01',0,0),(69,81,'2024-07-17 23:03:01',0,0),(69,97,'2024-07-17 23:03:01',0,0),(70,58,'2024-07-17 23:03:29',0,0),(70,60,'2024-07-17 23:03:29',0,0),(70,61,'2024-07-17 23:03:29',0,0),(70,62,'2024-07-17 23:03:29',0,0),(70,64,'2024-07-17 23:03:29',0,0),(70,65,'2024-07-17 23:03:29',0,0),(70,66,'2024-07-17 23:03:29',0,0),(70,72,'2024-07-17 23:03:29',0,0),(70,75,'2024-07-17 23:03:29',0,0),(70,76,'2024-07-17 23:03:29',0,0),(70,78,'2024-07-17 23:03:29',0,0),(70,86,'2024-07-17 23:03:29',0,0),(70,87,'2024-07-17 23:03:29',0,0),(70,88,'2024-07-17 23:03:29',0,0),(70,89,'2024-07-17 23:03:29',0,0),(70,93,'2024-07-17 23:03:29',0,0),(70,94,'2024-07-17 23:03:29',0,0),(70,95,'2024-07-17 23:03:29',0,0),(70,98,'2024-07-17 23:03:29',0,0),(70,100,'2024-07-17 23:03:29',0,0),(70,101,'2024-07-17 23:03:29',0,0),(70,102,'2024-07-17 23:03:29',0,0),(70,104,'2024-07-17 23:03:29',0,0),(70,105,'2024-07-17 23:03:29',0,0),(71,58,'2024-07-17 23:03:02',0,0),(72,57,'2024-07-17 23:03:03',0,0),(72,59,'2024-07-17 23:03:03',0,0),(72,60,'2024-07-17 23:03:03',0,0),(72,61,'2024-07-17 23:03:03',0,0),(72,62,'2024-07-17 23:03:03',0,0),(72,63,'2024-07-17 23:03:03',0,0),(72,64,'2024-07-17 23:03:03',0,0),(72,65,'2024-07-17 23:03:03',0,0),(72,66,'2024-07-17 23:03:03',0,0),(72,67,'2024-07-17 23:03:03',0,0),(72,68,'2024-07-17 23:03:03',0,0),(72,69,'2024-07-17 23:03:03',0,0),(72,70,'2024-07-17 23:03:03',0,0),(72,71,'2024-07-17 23:03:03',0,0),(72,72,'2024-07-17 23:03:03',0,0),(72,73,'2024-07-17 23:03:03',0,0),(72,74,'2024-07-17 23:03:03',0,0),(72,75,'2024-07-17 23:03:03',0,0),(72,76,'2024-07-17 23:03:03',0,0),(72,77,'2024-07-17 23:03:03',0,0),(72,78,'2024-07-17 23:03:03',0,0),(72,79,'2024-07-17 23:03:03',0,0),(72,80,'2024-07-17 23:03:03',0,0),(72,81,'2024-07-17 23:03:03',0,0),(72,82,'2024-07-17 23:03:03',0,0),(72,83,'2024-07-17 23:03:03',0,0),(72,84,'2024-07-17 23:03:03',0,0),(72,85,'2024-07-17 23:03:03',0,0),(72,86,'2024-07-17 23:03:03',0,0),(72,87,'2024-07-17 23:03:03',0,0),(72,88,'2024-07-17 23:03:03',0,0),(72,89,'2024-07-17 23:03:03',0,0),(72,90,'2024-07-17 23:03:03',0,0),(72,91,'2024-07-17 23:03:03',0,0),(72,92,'2024-07-17 23:03:03',0,0),(72,93,'2024-07-17 23:03:03',0,0),(72,94,'2024-07-17 23:03:03',0,0),(72,95,'2024-07-17 23:03:03',0,0),(72,96,'2024-07-17 23:03:03',0,0),(72,97,'2024-07-17 23:03:03',0,0),(72,98,'2024-07-17 23:03:03',0,0),(72,99,'2024-07-17 23:03:03',0,0),(72,100,'2024-07-17 23:03:03',0,0),(72,101,'2024-07-17 23:03:03',0,0),(72,102,'2024-07-17 23:03:03',0,0),(72,103,'2024-07-17 23:03:03',0,0),(72,104,'2024-07-17 23:03:03',0,0),(72,105,'2024-07-17 23:03:03',0,0),(72,106,'2024-07-17 23:03:03',0,0),(72,107,'2024-07-17 23:03:03',0,0),(72,108,'2024-07-17 23:03:03',0,0),(72,109,'2024-07-17 23:03:03',0,0),(72,110,'2024-07-17 23:03:03',0,0),(73,57,'2024-07-17 23:03:05',0,0),(73,58,'2024-07-17 23:03:05',0,0),(73,60,'2024-07-17 23:03:05',0,0),(73,61,'2024-07-17 23:03:05',0,0),(73,62,'2024-07-17 23:03:05',0,0),(73,64,'2024-07-17 23:03:05',0,0),(73,65,'2024-07-17 23:03:05',0,0),(73,66,'2024-07-17 23:03:05',0,0),(73,71,'2024-07-17 23:03:05',0,0),(73,72,'2024-07-17 23:03:05',0,0),(73,74,'2024-07-17 23:03:05',0,0),(73,75,'2024-07-17 23:03:05',0,0),(73,76,'2024-07-17 23:03:05',0,0),(73,78,'2024-07-17 23:03:05',0,0),(73,81,'2024-07-17 23:03:05',0,0),(73,82,'2024-07-17 23:03:05',0,0),(73,83,'2024-07-17 23:03:05',0,0),(73,86,'2024-07-17 23:03:05',0,0),(73,87,'2024-07-17 23:03:05',0,0),(73,88,'2024-07-17 23:03:05',0,0),(73,89,'2024-07-17 23:03:05',0,0),(73,91,'2024-07-17 23:03:05',0,0),(73,93,'2024-07-17 23:03:05',0,0),(73,94,'2024-07-17 23:03:05',0,0),(73,95,'2024-07-17 23:03:05',0,0),(73,98,'2024-07-17 23:03:05',0,0),(73,99,'2024-07-17 23:03:05',0,0),(73,100,'2024-07-17 23:03:05',0,0),(73,101,'2024-07-17 23:03:05',0,0),(73,102,'2024-07-17 23:03:05',0,0),(73,104,'2024-07-17 23:03:05',0,0),(73,105,'2024-07-17 23:03:05',0,0),(75,57,'2024-07-17 23:03:06',0,0),(75,58,'2024-07-17 23:03:07',0,1),(75,59,'2024-07-17 23:03:06',0,0),(75,60,'2024-07-17 23:03:06',0,0),(75,63,'2024-07-17 23:03:06',0,0),(75,70,'2024-07-17 23:03:06',0,0),(75,76,'2024-07-17 23:03:06',0,0),(75,79,'2024-07-17 23:03:06',0,0),(75,82,'2024-07-17 23:03:06',0,0),(75,85,'2024-07-17 23:03:06',0,0),(75,86,'2024-07-17 23:03:06',0,0),(75,87,'2024-07-17 23:03:06',0,0),(86,57,'2024-07-17 23:03:07',0,0),(86,59,'2024-07-17 23:03:07',0,0),(86,60,'2024-07-17 23:03:07',0,0),(86,63,'2024-07-17 23:03:07',0,0),(86,70,'2024-07-17 23:03:07',0,0),(86,76,'2024-07-17 23:03:07',0,0),(86,79,'2024-07-17 23:03:07',0,0),(86,82,'2024-07-17 23:03:07',0,0),(86,85,'2024-07-17 23:03:07',0,0),(86,86,'2024-07-17 23:03:07',0,0),(86,87,'2024-07-17 23:03:07',0,0),(87,57,'2024-07-17 23:03:08',0,0),(87,59,'2024-07-17 23:03:08',0,0),(87,60,'2024-07-17 23:03:08',0,0),(87,63,'2024-07-17 23:03:08',0,0),(87,70,'2024-07-17 23:03:08',0,0),(87,76,'2024-07-17 23:03:08',0,0),(87,79,'2024-07-17 23:03:08',0,0),(87,82,'2024-07-17 23:03:08',0,0),(87,85,'2024-07-17 23:03:08',0,0),(87,86,'2024-07-17 23:03:08',0,0),(87,87,'2024-07-17 23:03:08',0,0),(89,57,'2024-07-17 23:03:09',0,0),(89,60,'2024-07-17 23:03:09',0,0),(89,61,'2024-07-17 23:03:09',0,0),(89,71,'2024-07-17 23:03:09',0,0),(89,72,'2024-07-17 23:03:09',0,0),(89,74,'2024-07-17 23:03:09',0,0),(89,83,'2024-07-17 23:03:09',0,0),(89,86,'2024-07-17 23:03:09',0,0),(89,91,'2024-07-17 23:03:09',0,0),(89,96,'2024-07-17 23:03:09',0,0),(89,98,'2024-07-17 23:03:09',0,0),(90,60,'2024-07-17 23:03:10',0,0),(90,70,'2024-07-17 23:03:10',0,0),(90,80,'2024-07-17 23:03:10',0,0),(90,90,'2024-07-17 23:03:10',0,0),(90,100,'2024-07-17 23:03:10',0,0),(90,110,'2024-07-17 23:03:10',0,0),(105,57,'2024-07-17 23:03:27',0,0),(105,58,'2024-07-17 23:03:27',0,0),(105,59,'2024-07-17 23:03:27',0,0),(105,60,'2024-07-17 23:03:27',0,0),(105,61,'2024-07-17 23:03:27',0,0),(105,62,'2024-07-17 23:03:27',0,0),(105,63,'2024-07-17 23:03:27',0,0),(105,64,'2024-07-17 23:03:27',0,0),(105,65,'2024-07-17 23:03:27',0,0),(105,66,'2024-07-17 23:03:27',0,0),(105,67,'2024-07-17 23:03:27',0,0),(105,68,'2024-07-17 23:03:27',0,0),(105,69,'2024-07-17 23:03:27',0,0),(105,70,'2024-07-17 23:03:27',0,0),(105,71,'2024-07-17 23:03:27',0,0),(105,72,'2024-07-17 23:03:27',0,0),(105,73,'2024-07-17 23:03:27',0,0),(105,74,'2024-07-17 23:03:27',0,0),(105,75,'2024-07-17 23:03:27',0,0),(105,76,'2024-07-17 23:03:27',0,0),(105,77,'2024-07-17 23:03:27',0,0),(105,78,'2024-07-17 23:03:27',0,0),(105,79,'2024-07-17 23:03:27',0,0),(105,80,'2024-07-17 23:03:27',0,0),(105,81,'2024-07-17 23:03:27',0,0),(105,82,'2024-07-17 23:03:27',0,0),(105,83,'2024-07-17 23:03:27',0,0),(105,84,'2024-07-17 23:03:27',0,0),(105,85,'2024-07-17 23:03:27',0,0),(105,86,'2024-07-17 23:03:27',0,0),(105,87,'2024-07-17 23:03:27',0,0),(105,88,'2024-07-17 23:03:27',0,0),(105,89,'2024-07-17 23:03:27',0,0),(105,90,'2024-07-17 23:03:27',0,0),(105,91,'2024-07-17 23:03:27',0,0),(105,92,'2024-07-17 23:03:27',0,0),(105,93,'2024-07-17 23:03:27',0,0),(105,94,'2024-07-17 23:03:27',0,0),(105,95,'2024-07-17 23:03:27',0,0),(105,96,'2024-07-17 23:03:27',0,0),(105,97,'2024-07-17 23:03:27',0,0),(105,98,'2024-07-17 23:03:27',0,0),(105,99,'2024-07-17 23:03:27',0,0),(105,100,'2024-07-17 23:03:27',0,0),(105,101,'2024-07-17 23:03:27',0,0),(105,102,'2024-07-17 23:03:27',0,0),(105,103,'2024-07-17 23:03:27',0,0),(105,104,'2024-07-17 23:03:27',0,0),(105,105,'2024-07-17 23:03:27',0,0),(105,106,'2024-07-17 23:03:27',0,0),(105,107,'2024-07-17 23:03:27',0,0),(105,108,'2024-07-17 23:03:27',0,0),(105,109,'2024-07-17 23:03:27',0,0),(105,110,'2024-07-17 23:03:27',0,0),(107,58,'2024-07-17 23:03:26',0,0),(108,58,'2024-07-17 23:03:20',0,0),(108,59,'2024-07-17 23:03:20',0,0),(109,57,'2024-07-17 23:03:25',0,0),(109,60,'2024-07-17 23:03:25',0,0),(109,61,'2024-07-17 23:03:25',0,0),(109,62,'2024-07-17 23:03:25',0,0),(109,63,'2024-07-17 23:03:25',0,0),(109,64,'2024-07-17 23:03:25',0,0),(109,65,'2024-07-17 23:03:25',0,0),(109,66,'2024-07-17 23:03:25',0,0),(109,67,'2024-07-17 23:03:25',0,0),(109,68,'2024-07-17 23:03:25',0,0),(109,69,'2024-07-17 23:03:25',0,0),(109,70,'2024-07-17 23:03:25',0,0),(109,71,'2024-07-17 23:03:25',0,0),(109,72,'2024-07-17 23:03:25',0,0),(109,73,'2024-07-17 23:03:25',0,0),(109,74,'2024-07-17 23:03:25',0,0),(109,75,'2024-07-17 23:03:25',0,0),(109,76,'2024-07-17 23:03:25',0,0),(109,77,'2024-07-17 23:03:25',0,0),(109,78,'2024-07-17 23:03:25',0,0),(109,79,'2024-07-17 23:03:25',0,0),(109,80,'2024-07-17 23:03:25',0,0),(109,81,'2024-07-17 23:03:25',0,0),(109,82,'2024-07-17 23:03:25',0,0),(109,83,'2024-07-17 23:03:25',0,0),(109,84,'2024-07-17 23:03:25',0,0),(109,85,'2024-07-17 23:03:25',0,0),(109,86,'2024-07-17 23:03:25',0,0),(109,87,'2024-07-17 23:03:25',0,0),(109,88,'2024-07-17 23:03:25',0,0),(109,89,'2024-07-17 23:03:25',0,0),(109,90,'2024-07-17 23:03:25',0,0),(109,91,'2024-07-17 23:03:25',0,0),(109,92,'2024-07-17 23:03:25',0,0),(109,93,'2024-07-17 23:03:25',0,0),(109,94,'2024-07-17 23:03:25',0,0),(109,95,'2024-07-17 23:03:25',0,0),(109,96,'2024-07-17 23:03:25',0,0),(109,97,'2024-07-17 23:03:25',0,0),(109,98,'2024-07-17 23:03:25',0,0),(109,99,'2024-07-17 23:03:25',0,0),(109,100,'2024-07-17 23:03:25',0,0),(109,101,'2024-07-17 23:03:25',0,0),(109,102,'2024-07-17 23:03:25',0,0),(109,103,'2024-07-17 23:03:25',0,0),(109,104,'2024-07-17 23:03:25',0,0),(109,105,'2024-07-17 23:03:25',0,0),(109,106,'2024-07-17 23:03:25',0,0),(109,107,'2024-07-17 23:03:25',0,0),(109,108,'2024-07-17 23:03:25',0,0),(109,109,'2024-07-17 23:03:25',0,0),(109,110,'2024-07-17 23:03:25',0,0),(110,58,'2024-07-17 23:03:21',0,0),(110,59,'2024-07-17 23:03:21',0,0),(111,58,'2024-07-17 23:03:23',0,0),(111,59,'2024-07-17 23:03:23',0,0),(111,60,'2024-07-17 23:03:23',0,0),(112,59,'2024-07-17 23:03:24',0,0),(112,60,'2024-07-17 23:03:24',0,0),(113,57,'2024-07-17 23:03:30',0,0),(113,59,'2024-07-17 23:03:30',0,0),(113,61,'2024-07-17 23:03:30',0,0),(113,62,'2024-07-17 23:03:30',0,0),(113,63,'2024-07-17 23:03:30',0,0),(113,64,'2024-07-17 23:03:30',0,0),(113,65,'2024-07-17 23:03:30',0,0),(113,66,'2024-07-17 23:03:30',0,0),(113,67,'2024-07-17 23:03:30',0,0),(113,68,'2024-07-17 23:03:30',0,0),(113,69,'2024-07-17 23:03:30',0,0),(113,70,'2024-07-17 23:03:30',0,0),(113,71,'2024-07-17 23:03:30',0,0),(113,72,'2024-07-17 23:03:30',0,0),(113,73,'2024-07-17 23:03:30',0,0),(113,74,'2024-07-17 23:03:30',0,0),(113,75,'2024-07-17 23:03:30',0,0),(113,76,'2024-07-17 23:03:30',0,0),(113,77,'2024-07-17 23:03:30',0,0),(113,78,'2024-07-17 23:03:30',0,0),(113,79,'2024-07-17 23:03:30',0,0),(113,80,'2024-07-17 23:03:30',0,0),(113,81,'2024-07-17 23:03:30',0,0),(113,82,'2024-07-17 23:03:30',0,0),(113,83,'2024-07-17 23:03:30',0,0),(113,84,'2024-07-17 23:03:30',0,0),(113,85,'2024-07-17 23:03:30',0,0),(113,86,'2024-07-17 23:03:30',0,0),(113,87,'2024-07-17 23:03:30',0,0),(113,88,'2024-07-17 23:03:30',0,0),(113,89,'2024-07-17 23:03:30',0,0),(113,90,'2024-07-17 23:03:30',0,0),(113,91,'2024-07-17 23:03:30',0,0),(113,92,'2024-07-17 23:03:30',0,0),(113,93,'2024-07-17 23:03:30',0,0),(113,94,'2024-07-17 23:03:30',0,0),(113,95,'2024-07-17 23:03:30',0,0),(113,96,'2024-07-17 23:03:30',0,0),(113,97,'2024-07-17 23:03:30',0,0),(113,98,'2024-07-17 23:03:30',0,0),(113,99,'2024-07-17 23:03:30',0,0),(113,100,'2024-07-17 23:03:30',0,0),(113,101,'2024-07-17 23:03:30',0,0),(113,102,'2024-07-17 23:03:30',0,0),(113,103,'2024-07-17 23:03:30',0,0),(113,104,'2024-07-17 23:03:30',0,0),(113,105,'2024-07-17 23:03:30',0,0),(113,106,'2024-07-17 23:03:30',0,0),(113,107,'2024-07-17 23:03:30',0,0),(113,108,'2024-07-17 23:03:30',0,0),(113,109,'2024-07-17 23:03:30',0,0),(113,110,'2024-07-17 23:03:30',0,0),(114,58,'2024-07-17 23:03:32',0,0),(114,60,'2024-07-17 23:03:32',0,0),(115,57,'2024-07-17 23:03:11',0,0),(115,58,'2024-07-17 23:03:11',0,0),(115,59,'2024-07-17 23:03:11',0,0),(115,60,'2024-07-17 23:03:11',0,0),(115,61,'2024-07-17 23:03:11',0,0),(115,62,'2024-07-17 23:03:11',0,0),(115,63,'2024-07-17 23:03:11',0,0),(115,64,'2024-07-17 23:03:11',0,0),(115,65,'2024-07-17 23:03:11',0,0),(115,66,'2024-07-17 23:03:11',0,0),(115,67,'2024-07-17 23:03:11',0,0),(115,68,'2024-07-17 23:03:11',0,0),(115,69,'2024-07-17 23:03:11',0,0),(115,70,'2024-07-17 23:03:11',0,0),(115,71,'2024-07-17 23:03:11',0,0),(115,72,'2024-07-17 23:03:11',0,0),(115,73,'2024-07-17 23:03:11',0,0),(115,74,'2024-07-17 23:03:11',0,0),(115,75,'2024-07-17 23:03:11',0,0),(115,76,'2024-07-17 23:03:11',0,0),(115,77,'2024-07-17 23:03:11',0,0),(115,78,'2024-07-17 23:03:11',0,0),(115,79,'2024-07-17 23:03:11',0,0),(115,80,'2024-07-17 23:03:11',0,0),(115,81,'2024-07-17 23:03:11',0,0),(115,82,'2024-07-17 23:03:11',0,0),(115,83,'2024-07-17 23:03:11',0,0),(115,84,'2024-07-17 23:03:11',0,0),(115,85,'2024-07-17 23:03:11',0,0),(115,86,'2024-07-17 23:03:11',0,0),(115,87,'2024-07-17 23:03:11',0,0),(115,88,'2024-07-17 23:03:11',0,0),(115,89,'2024-07-17 23:03:11',0,0),(115,90,'2024-07-17 23:03:11',0,0),(115,91,'2024-07-17 23:03:11',0,0),(115,92,'2024-07-17 23:03:11',0,0),(115,93,'2024-07-17 23:03:11',0,0),(115,94,'2024-07-17 23:03:11',0,0),(115,95,'2024-07-17 23:03:11',0,0),(115,96,'2024-07-17 23:03:11',0,0),(115,97,'2024-07-17 23:03:11',0,0),(115,98,'2024-07-17 23:03:11',0,0),(115,99,'2024-07-17 23:03:11',0,0),(115,100,'2024-07-17 23:03:11',0,0),(115,101,'2024-07-17 23:03:11',0,0),(115,102,'2024-07-17 23:03:11',0,0),(115,103,'2024-07-17 23:03:11',0,0),(115,104,'2024-07-17 23:03:11',0,0),(115,105,'2024-07-17 23:03:11',0,0),(115,106,'2024-07-17 23:03:11',0,0),(116,107,'2024-07-17 23:03:13',0,0),(116,108,'2024-07-17 23:03:13',0,0),(116,109,'2024-07-17 23:03:13',0,0),(116,110,'2024-07-17 23:03:13',0,0),(117,59,'2024-07-17 23:03:14',0,0),(117,60,'2024-07-17 23:03:14',0,0),(117,61,'2024-07-17 23:03:14',0,0),(117,62,'2024-07-17 23:03:14',0,0);
/*!40000 ALTER TABLE `lead_lists_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_notes`
--

DROP TABLE IF EXISTS `lead_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `text` longtext NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `date_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_67FC6B0355458D` (`lead_id`),
  CONSTRAINT `FK_67FC6B0355458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_notes`
--

LOCK TABLES `lead_notes` WRITE;
/*!40000 ALTER TABLE `lead_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_points_change_log`
--

DROP TABLE IF EXISTS `lead_points_change_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_points_change_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `ip_id` int(10) unsigned DEFAULT NULL,
  `group_id` int(10) unsigned DEFAULT NULL,
  `type` tinytext NOT NULL,
  `event_name` varchar(191) NOT NULL,
  `action_name` varchar(191) NOT NULL,
  `delta` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_949C2CCC55458D` (`lead_id`),
  KEY `IDX_949C2CCCA03F5E9F` (`ip_id`),
  KEY `IDX_949C2CCCFE54D947` (`group_id`),
  KEY `point_date_added` (`date_added`),
  CONSTRAINT `FK_949C2CCC55458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_949C2CCCA03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `ip_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_949C2CCCFE54D947` FOREIGN KEY (`group_id`) REFERENCES `point_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_points_change_log`
--

LOCK TABLES `lead_points_change_log` WRITE;
/*!40000 ALTER TABLE `lead_points_change_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_points_change_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_stages_change_log`
--

DROP TABLE IF EXISTS `lead_stages_change_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_stages_change_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `stage_id` int(10) unsigned DEFAULT NULL,
  `event_name` varchar(191) NOT NULL,
  `action_name` varchar(191) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_73B42EF355458D` (`lead_id`),
  KEY `IDX_73B42EF32298D193` (`stage_id`),
  CONSTRAINT `FK_73B42EF32298D193` FOREIGN KEY (`stage_id`) REFERENCES `stages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_73B42EF355458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_stages_change_log`
--

LOCK TABLES `lead_stages_change_log` WRITE;
/*!40000 ALTER TABLE `lead_stages_change_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_stages_change_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_tags`
--

DROP TABLE IF EXISTS `lead_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_tag_search` (`tag`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_tags`
--

LOCK TABLES `lead_tags` WRITE;
/*!40000 ALTER TABLE `lead_tags` DISABLE KEYS */;
INSERT INTO `lead_tags` VALUES (3,'Tag A',NULL);
/*!40000 ALTER TABLE `lead_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_tags_xref`
--

DROP TABLE IF EXISTS `lead_tags_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_tags_xref` (
  `lead_id` bigint(20) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`lead_id`,`tag_id`),
  KEY `IDX_F2E51EB6BAD26311` (`tag_id`),
  CONSTRAINT `FK_F2E51EB655458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_F2E51EB6BAD26311` FOREIGN KEY (`tag_id`) REFERENCES `lead_tags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_tags_xref`
--

LOCK TABLES `lead_tags_xref` WRITE;
/*!40000 ALTER TABLE `lead_tags_xref` DISABLE KEYS */;
INSERT INTO `lead_tags_xref` VALUES (58,3),(60,3);
/*!40000 ALTER TABLE `lead_tags_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_utmtags`
--

DROP TABLE IF EXISTS `lead_utmtags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_utmtags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  `query` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `referer` longtext DEFAULT NULL,
  `remote_host` varchar(191) DEFAULT NULL,
  `url` longtext DEFAULT NULL,
  `user_agent` longtext DEFAULT NULL,
  `utm_campaign` varchar(191) DEFAULT NULL,
  `utm_content` varchar(191) DEFAULT NULL,
  `utm_medium` varchar(191) DEFAULT NULL,
  `utm_source` varchar(191) DEFAULT NULL,
  `utm_term` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C51BCB8D55458D` (`lead_id`),
  CONSTRAINT `FK_C51BCB8D55458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_utmtags`
--

LOCK TABLES `lead_utmtags` WRITE;
/*!40000 ALTER TABLE `lead_utmtags` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_utmtags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int(10) unsigned DEFAULT NULL,
  `stage_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `points` int(11) NOT NULL,
  `last_active` datetime DEFAULT NULL,
  `internal` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `social_cache` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `date_identified` datetime DEFAULT NULL,
  `preferred_profile_image` varchar(191) DEFAULT NULL,
  `title` varchar(191) DEFAULT NULL,
  `firstname` varchar(191) DEFAULT NULL,
  `lastname` varchar(191) DEFAULT NULL,
  `company` varchar(191) DEFAULT NULL,
  `position` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `mobile` varchar(191) DEFAULT NULL,
  `address1` varchar(191) DEFAULT NULL,
  `address2` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `zipcode` varchar(191) DEFAULT NULL,
  `timezone` varchar(191) DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `fax` varchar(191) DEFAULT NULL,
  `preferred_locale` varchar(191) DEFAULT NULL,
  `attribution_date` datetime DEFAULT NULL,
  `attribution` double DEFAULT NULL,
  `website` varchar(191) DEFAULT NULL,
  `facebook` varchar(191) DEFAULT NULL,
  `foursquare` varchar(191) DEFAULT NULL,
  `instagram` varchar(191) DEFAULT NULL,
  `linkedin` varchar(191) DEFAULT NULL,
  `skype` varchar(191) DEFAULT NULL,
  `twitter` varchar(191) DEFAULT NULL,
  `generated_email_domain` varchar(255) GENERATED ALWAYS AS (substr(`email`,locate('@',`email`) + 1)) VIRTUAL COMMENT '(DC2Type:generated)',
  PRIMARY KEY (`id`),
  KEY `IDX_179045527E3C61F9` (`owner_id`),
  KEY `IDX_179045522298D193` (`stage_id`),
  KEY `lead_date_added` (`date_added`),
  KEY `date_identified` (`date_identified`),
  KEY `fax_search` (`fax`),
  KEY `preferred_locale_search` (`preferred_locale`),
  KEY `attribution_date_search` (`attribution_date`),
  KEY `attribution_search` (`attribution`),
  KEY `website_search` (`website`),
  KEY `facebook_search` (`facebook`),
  KEY `foursquare_search` (`foursquare`),
  KEY `instagram_search` (`instagram`),
  KEY `linkedin_search` (`linkedin`),
  KEY `skype_search` (`skype`),
  KEY `twitter_search` (`twitter`),
  KEY `contact_attribution` (`attribution`,`attribution_date`),
  KEY `date_added_country_index` (`date_added`,`country`),
  KEY `generated_email_domain` (`generated_email_domain`),
  CONSTRAINT `FK_179045522298D193` FOREIGN KEY (`stage_id`) REFERENCES `stages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_179045527E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES (57,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,100,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mrs.','Penny','Moore','Williams Bros.',NULL,'PennyKMoore@dayrep.com','070 7086 0753',NULL,'88 Clasper Way',NULL,'HEWELSFIELD COMMON',NULL,'GL15 1XD',NULL,'United Kingdom',NULL,NULL,NULL,NULL,'WikiFire.co.uk','PennyKMoore',NULL,NULL,NULL,NULL,'PennyKMoore','dayrep.com'),(58,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Henry','Catalano','Pro Garden Management',NULL,'HenryLCatalano@einrot.com','082 118 9037',NULL,'960 Doreen St',NULL,'Rustenburg','North West','347',NULL,'South Africa',NULL,NULL,NULL,NULL,'MultiFlavors.co.za','HenryLCatalano',NULL,NULL,NULL,NULL,'HenryLCatalano','einrot.com'),(59,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Stephanie','Cone','Success Is Yours',NULL,'StephanieMCone@teleworm.us','078 4515 7520',NULL,'2 Hull Road',NULL,'PANT',NULL,'SY10 6ND',NULL,'United Kingdom',NULL,NULL,NULL,NULL,'CampingProfessionals.co.uk','StephanieMCone',NULL,NULL,NULL,NULL,'StephanieMCone','teleworm.us'),(60,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Andrew','Flanagan','William Wanamaker & Sons',NULL,'AndrewVFlanagan@dayrep.com','077 6574 7295',NULL,'40 Simone Weil Avenue',NULL,'WEATHERCOTE',NULL,'LA6 6ZT',NULL,'United Kingdom',NULL,NULL,NULL,NULL,'CommonFit.co.uk','AndrewVFlanagan',NULL,NULL,NULL,NULL,'AndrewVFlanagan','dayrep.com'),(61,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Daniel','Wright','Purity Supreme',NULL,'DanielAWright@dayrep.com','082 673 3168',NULL,'1084 Bhoola Rd',NULL,'Qumbu','Eastern Cape','5185',NULL,'South Africa',NULL,NULL,NULL,NULL,'MysteryShoes.co.za','DanielAWright',NULL,NULL,NULL,NULL,'DanielAWright','dayrep.com'),(62,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Jose','Patton','Cut Rite',NULL,'JoseMPatton@jourrapide.com','250-453-4211',NULL,'727 Mesa Vista Drive',NULL,'Ashcroft','BC','V0K 1A0',NULL,'Canada',NULL,NULL,NULL,NULL,'CartridgeExpo.ca','JoseMPatton',NULL,NULL,NULL,NULL,'JoseMPatton','jourrapide.com'),(63,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Dr.','Jean','Cross','Superior Appraisals',NULL,'JeanGCross@armyspy.com','077 8114 7167',NULL,'28 Warren St',NULL,'WEST CHINNOCK',NULL,'TA18 7XS',NULL,'United Kingdom',NULL,NULL,NULL,NULL,'FirstInstructor.co.uk','JeanGCross',NULL,NULL,NULL,NULL,'JeanGCross','armyspy.com'),(64,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Kevin','Kennedy','Franklin Simon',NULL,'KevinBKennedy@gustr.com','(03) 5330 2874',NULL,'3 Fitzroy Street',NULL,'COLBROOK','VIC','3342',NULL,'Australia',NULL,NULL,NULL,NULL,'SlamLounge.com.au','KevinBKennedy',NULL,NULL,NULL,NULL,'KevinBKennedy','gustr.com'),(65,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Leonard','Sinclair','Thorofare',NULL,'LeonardMSinclair@teleworm.us','084 524 8203',NULL,'719 Loop St',NULL,'Cape Town','Western Cape','7435',NULL,'South Africa',NULL,NULL,NULL,NULL,'SizeMedium.co.za','LeonardMSinclair',NULL,NULL,NULL,NULL,'LeonardMSinclair','teleworm.us'),(66,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Bruce','Campbell','Town and Country Convenience Stores',NULL,'BruceMCampbell@einrot.com','(07) 3187 6375',NULL,'7 Delan Road',NULL,'MOOLBOOLAMAN','QLD','4671',NULL,'Australia',NULL,NULL,NULL,NULL,'RightWingLunacy.com.au','BruceMCampbell',NULL,NULL,NULL,NULL,'BruceMCampbell','einrot.com'),(67,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Guadalupe','Strauss','Gas Zone',NULL,'GuadalupeHStrauss@teleworm.us','(08) 9029 4631',NULL,'2 Loris Way',NULL,'TOOLIBIN','WA','6312',NULL,'Australia',NULL,NULL,NULL,NULL,'ShowDirectories.com.au','GuadalupeHStrauss',NULL,NULL,NULL,NULL,'GuadalupeHStrauss','teleworm.us'),(68,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Pamela','Wise','Showbiz Pizza Place',NULL,'PamelaSWise@gustr.com','(03) 5389 0975',NULL,'25 Normans Road',NULL,'WARROCK','VIC','3312',NULL,'Australia',NULL,NULL,NULL,NULL,'IRCMagazine.com.au','PamelaSWise',NULL,NULL,NULL,NULL,'PamelaSWise','gustr.com'),(69,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Margaret','Maguire','Pender\'s Food Stores',NULL,'MargaretDMaguire@cuvox.de','450-439-2306',NULL,'282 rue des Églises Est',NULL,'Laurentides','QC','J0R 1C0',NULL,'Canada',NULL,NULL,NULL,NULL,'AmateurCredit.ca','MargaretDMaguire',NULL,NULL,NULL,NULL,'MargaretDMaguire','cuvox.de'),(70,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Regina','Dolph','Record & Tape Outlet',NULL,'ReginaBDolph@teleworm.us','077 0685 3094',NULL,'27 Hounslow Rd',NULL,'SOLAS',NULL,'HS6 2YL',NULL,'United Kingdom',NULL,NULL,NULL,NULL,'MediumNews.co.uk','ReginaBDolph',NULL,NULL,NULL,NULL,'ReginaBDolph','teleworm.us'),(71,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mrs.','Paula','Hill','Alladin\'s Lamp',NULL,'PaulaWHill@dayrep.com','085 488 7773',NULL,'2383 South St',NULL,'Brits','North West','220',NULL,'South Africa',NULL,NULL,NULL,NULL,'DominoRoom.co.za','PaulaWHill',NULL,NULL,NULL,NULL,'PaulaWHill','dayrep.com'),(72,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Jimmy','Sanchez','On Cue',NULL,'JimmyCSanchez@dayrep.com','(07) 4042 9552',NULL,'90 Boulter Close',NULL,'COQUETTE POINT','QLD','4860',NULL,'Australia',NULL,NULL,NULL,NULL,'GrandMassage.com.au','JimmyCSanchez',NULL,NULL,NULL,NULL,'JimmyCSanchez','dayrep.com'),(73,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Mildred','Rodriguez','Team Electronics',NULL,'MildredARodriguez@rhyta.com','(08) 9060 4567',NULL,'17 Muscat Street',NULL,'BADGERIN ROCK','WA','6475',NULL,'Australia',NULL,NULL,NULL,NULL,'DoubleLimousine.com.au','MildredARodriguez',NULL,NULL,NULL,NULL,'MildredARodriguez','rhyta.com'),(74,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mrs.','Kyung','Brittain','Dream Home Improvements',NULL,'KyungBBrittain@dayrep.com','(02) 4997 6844',NULL,'17 Hart Street',NULL,'MIDDLE BROOK','NSW','2337',NULL,'Australia',NULL,NULL,NULL,NULL,'BloggerRoom.com.au','KyungBBrittain',NULL,NULL,NULL,NULL,'KyungBBrittain','dayrep.com'),(75,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Willie','Perez','Crandall\'s Fine Furniture',NULL,'WillieJPerez@jourrapide.com','505-292-1492',NULL,'3683 Byrd Lane',NULL,'Albuquerque','NM','87112',NULL,'United States',NULL,NULL,NULL,NULL,'ThermalNetworks.com','WillieJPerez',NULL,NULL,NULL,NULL,'WillieJPerez','jourrapide.com'),(76,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Marvin','Patterson','Chi-Chi\'s',NULL,'MarvinPPatterson@jourrapide.com','079 4031 1312',NULL,'40 Glandovey Terrace',NULL,'TREFNANNEY',NULL,'SY22 2AD',NULL,'United Kingdom',NULL,NULL,NULL,NULL,'FabulousSeats.co.uk','MarvinPPatterson',NULL,NULL,NULL,NULL,'MarvinPPatterson','jourrapide.com'),(77,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Rosemary','Salinas','Omni Tech',NULL,'RosemaryKSalinas@superrito.com','083 893 4273',NULL,'2131 Hoog St',NULL,'Brakpan','Gauteng','1544',NULL,'South Africa',NULL,NULL,NULL,NULL,'RegionHotels.co.za','RosemaryKSalinas',NULL,NULL,NULL,NULL,'RosemaryKSalinas','superrito.com'),(78,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Paul','Wilson','Magna Solution',NULL,'PaulDWilson@superrito.com','085 388 8905',NULL,'244 Hoog St',NULL,'Petit','Gauteng','1512',NULL,'South Africa',NULL,NULL,NULL,NULL,'WebDivorces.co.za','PaulDWilson',NULL,NULL,NULL,NULL,'PaulDWilson','superrito.com'),(79,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Roxie','Shaw','Old America Stores',NULL,'RoxieLShaw@fleckens.hu','079 6441 8665',NULL,'97 Boat Lane',NULL,'RHAOINE',NULL,'IV28 9UH',NULL,'United Kingdom',NULL,NULL,NULL,NULL,'WifeReplacement.co.uk','RoxieLShaw',NULL,NULL,NULL,NULL,'RoxieLShaw','fleckens.hu'),(80,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Angie','Robles','Pro Garden Management',NULL,'AngieHRobles@einrot.com','905-573-0032',NULL,'2393 Barton Street',NULL,'Stoney Creek','ON','L8G 2V1',NULL,'Canada',NULL,NULL,NULL,NULL,'EscrowWireless.ca','AngieHRobles',NULL,NULL,NULL,NULL,'AngieHRobles','einrot.com'),(81,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mrs.','Charlotte','Fender','CSK Auto',NULL,'CharlotteAFender@einrot.com','515-729-9343',NULL,'113 Nutters Barn Lane',NULL,'Des Moines','IA','50313',NULL,'United States',NULL,NULL,NULL,NULL,'AffordableIncentive.com','CharlotteAFender',NULL,NULL,NULL,NULL,'CharlotteAFender','einrot.com'),(82,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mrs.','Lashawnda','Joseph','Tam\'s Stationers',NULL,'LashawndaDJoseph@gustr.com','070 1704 6116',NULL,'43 South Crescent',NULL,'LYDFORD-ON-FOSSE',NULL,'TA11 7UU',NULL,'United Kingdom',NULL,NULL,NULL,NULL,'MakeupDiscounts.co.uk','LashawndaDJoseph',NULL,NULL,NULL,NULL,'LashawndaDJoseph','gustr.com'),(83,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mrs.','Helen','Manley','Farrell\'s Ice Cream Parlour',NULL,'HelenPManley@dayrep.com','082 107 6053',NULL,'339 Impala St',NULL,'Creighton','KwaZulu-Natal','3263',NULL,'South Africa',NULL,NULL,NULL,NULL,'BankingVentures.co.za','HelenPManley',NULL,NULL,NULL,NULL,'HelenPManley','dayrep.com'),(84,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Annie','Richarson','Bodega Club',NULL,'AnnieARicharson@armyspy.com','517-266-4755',NULL,'40 Amethyst Drive',NULL,'Adrian','MI','49221',NULL,'United States',NULL,NULL,NULL,NULL,'GolfCleaners.com','AnnieARicharson',NULL,NULL,NULL,NULL,'AnnieARicharson','armyspy.com'),(85,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Mary','Nevarez','Builders Square',NULL,'MaryWNevarez@armyspy.com','077 8292 2559',NULL,'76 Asfordby Rd',NULL,'AITH',NULL,'ZE2 4FH',NULL,'United Kingdom',NULL,NULL,NULL,NULL,'JollyForum.co.uk','MaryWNevarez',NULL,NULL,NULL,NULL,'MaryWNevarez','armyspy.com'),(86,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','David','Fahy','Bumper to Bumper Auto Parts',NULL,'DavidEFahy@dayrep.com','079 7960 8698',NULL,'28 Lairg Road',NULL,'NEW ULVA',NULL,'PA31 8WG',NULL,'United Kingdom',NULL,NULL,NULL,NULL,'SearchWealth.co.uk','DavidEFahy',NULL,NULL,NULL,NULL,'DavidEFahy','dayrep.com'),(87,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Aaron','Guild','Scotty\'s Builders Supply',NULL,'AaronMGuild@rhyta.com','070 8638 9402',NULL,'86 Wrexham Rd',NULL,'EWLOE',NULL,'CH5 9LB',NULL,'United Kingdom',NULL,NULL,NULL,NULL,'MobLag.co.uk','AaronMGuild',NULL,NULL,NULL,NULL,'AaronMGuild','rhyta.com'),(88,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Lee','Cole','Checker Auto Parts',NULL,'LeeACole@fleckens.hu','085 470 6278',NULL,'1969 Dikbas Road',NULL,'Marble Hall','Mpumalanga','451',NULL,'South Africa',NULL,NULL,NULL,NULL,'BadProtection.co.za','LeeACole',NULL,NULL,NULL,NULL,'LeeACole','fleckens.hu'),(89,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Matthew','Dell','Wealthy Ideas',NULL,'MatthewSDell@armyspy.com','605-790-9178',NULL,'1087 Hartway Street',NULL,'Aberdeen','SD','57401',NULL,'United States',NULL,NULL,NULL,NULL,'FireGourd.com','MatthewSDell',NULL,NULL,NULL,NULL,'MatthewSDell','armyspy.com'),(90,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Raquel','O\'Sullivan','Asian Solutions',NULL,'RaquelTOSullivan@gustr.com','519-919-6675',NULL,'1227 Goyeau Ave',NULL,'Windsor','ON','N9A 1H9',NULL,'Canada',NULL,NULL,NULL,NULL,'RobotMarketing.ca','RaquelTOSullivan',NULL,NULL,NULL,NULL,'RaquelTOSullivan','gustr.com'),(91,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mrs.','Debra','Shackelford','Lum\'s',NULL,'DebraCShackelford@dayrep.com','450-463-1825',NULL,'1879 rue Saint-Charles',NULL,'Longueuil','QC','J4H 1M3',NULL,'Canada',NULL,NULL,NULL,NULL,'BetterSearchTool.ca','DebraCShackelford',NULL,NULL,NULL,NULL,'DebraCShackelford','dayrep.com'),(92,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Marcia','Hibbard','Stop N Shop',NULL,'MarciaBHibbard@fleckens.hu','250-577-9200',NULL,'4251 Blind Bay Road',NULL,'Pritchard','BC','V0E 2P0',NULL,'Canada',NULL,NULL,NULL,NULL,'DishRebates.ca','MarciaBHibbard',NULL,NULL,NULL,NULL,'MarciaBHibbard','fleckens.hu'),(93,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Thomas','Domingue','Holly Tree Inn',NULL,'ThomasJDomingue@armyspy.com','717-566-3468',NULL,'1988 Lincoln Drive',NULL,'Hummelstown','PA','17036',NULL,'United States',NULL,NULL,NULL,NULL,'ThinkingMeds.com','ThomasJDomingue',NULL,NULL,NULL,NULL,'ThomasJDomingue','armyspy.com'),(94,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Jeremy','Newell','Leo\'s Stereo',NULL,'JeremyJNewell@fleckens.hu','269-372-6669',NULL,'600 Shingleton Road',NULL,'Oshtemo','MI','49077',NULL,'United States',NULL,NULL,NULL,NULL,'ZBlvd.com','JeremyJNewell',NULL,NULL,NULL,NULL,'JeremyJNewell','fleckens.hu'),(95,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Justin','Waller','LoRay',NULL,'JustinRWaller@cuvox.de','083 324 8545',NULL,'1348 President St',NULL,'Johannesburg','Gauteng','2102',NULL,'South Africa',NULL,NULL,NULL,NULL,'SecurityWorkshops.co.za','JustinRWaller',NULL,NULL,NULL,NULL,'JustinRWaller','cuvox.de'),(96,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Brenda','Bolton','Huffman and Boyle',NULL,'BrendaWBolton@dayrep.com','083 411 4857',NULL,'2143 Robertson Ave',NULL,'Temba','North West','505',NULL,'South Africa',NULL,NULL,NULL,NULL,'GuyHumor.co.za','BrendaWBolton',NULL,NULL,NULL,NULL,'BrendaWBolton','dayrep.com'),(97,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Renee','Smith','Linens \'n Things',NULL,'ReneeTSmith@teleworm.us','(07) 4527 1699',NULL,'44 Railway Street',NULL,'WATTLE RIDGE','QLD','4357',NULL,'Australia',NULL,NULL,NULL,NULL,'ShowFever.com.au','ReneeTSmith',NULL,NULL,NULL,NULL,'ReneeTSmith','teleworm.us'),(98,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','David','Cook','Big Star Markets',NULL,'DavidECook@dayrep.com','083 326 3665',NULL,'1818 Mark Street',NULL,'Ga-Maraba','Limpopo','705',NULL,'South Africa',NULL,NULL,NULL,NULL,'CreditCardChronicles.co.za','DavidECook',NULL,NULL,NULL,NULL,'DavidECook','dayrep.com'),(99,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mrs.','June','Bond','Realty Depot',NULL,'JuneLBond@superrito.com','450-885-5404',NULL,'1921 chemin Georges',NULL,'St Barthelemy','QC','J0K 1X0',NULL,'Canada',NULL,NULL,NULL,NULL,'NoteNews.ca','JuneLBond',NULL,NULL,NULL,NULL,'JuneLBond','superrito.com'),(100,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','James','Duffy','Reliable Guidance',NULL,'JamesTDuffy@armyspy.com','336-508-1155',NULL,'3716 Keyser Ridge Road',NULL,'Greensboro','NC','27401',NULL,'United States',NULL,NULL,NULL,NULL,'AnonymousMortgage.com','JamesTDuffy',NULL,NULL,NULL,NULL,'JamesTDuffy','armyspy.com'),(101,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Jonathan','Lane','Life Map',NULL,'JonathanJLane@jourrapide.com','082 917 7446',NULL,'1408 Ireland St',NULL,'Nelspruit','Mpumalanga','1220',NULL,'South Africa',NULL,NULL,NULL,NULL,'YouBlogs.co.za','JonathanJLane',NULL,NULL,NULL,NULL,'JonathanJLane','jourrapide.com'),(102,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Peter','Howard','Payless Cashways',NULL,'PeterJHoward@armyspy.com','604-916-2079',NULL,'4514 Robson St',NULL,'Vancouver','BC','V6B 3K9',NULL,'Canada',NULL,NULL,NULL,NULL,'FriendTraders.ca','PeterJHoward',NULL,NULL,NULL,NULL,'PeterJHoward','armyspy.com'),(103,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Irene','Martin','Helios Air',NULL,'IreneGMartin@cuvox.de','613-818-9603',NULL,'2312 Bank St',NULL,'Ottawa','ON','K1H 7Z1',NULL,'Canada',NULL,NULL,NULL,NULL,'ForumJet.ca','IreneGMartin',NULL,NULL,NULL,NULL,'IreneGMartin','cuvox.de'),(104,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','David','Jameson','House Of Denmark',NULL,'DavidLJameson@einrot.com','(02) 4069 4141',NULL,'52 Tooraweenah Road',NULL,'MUGINCOBLE','NSW','2870',NULL,'Australia',NULL,NULL,NULL,NULL,'ModelSolar.com.au','DavidLJameson',NULL,NULL,NULL,NULL,'DavidLJameson','einrot.com'),(105,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Mr.','Lewis','Syed','Record Bar',NULL,'LewisTSyed@gustr.com','912-682-3070',NULL,'107 Yorkie Lane',NULL,'Statesboro','GA','30458',NULL,'United States',NULL,NULL,NULL,NULL,'BetterSearchTool.com','LewisTSyed',NULL,NULL,NULL,NULL,'LewisTSyed','gustr.com'),(106,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}','2024-07-17 23:02:59','gravatar','Ms.','Nellie','Baird','Huyler\'s',NULL,'NellieABaird@armyspy.com','083 926 4318',NULL,'1930 Uitsig St',NULL,'Port Elizabeth','Eastern Cape','6204',NULL,'South Africa',NULL,NULL,NULL,NULL,'CosmeticsCritic.co.za','NellieABaird',NULL,NULL,NULL,NULL,'NellieABaird','armyspy.com'),(107,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}',NULL,'gravatar',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1930 Uitsig St',NULL,'Port Elizabeth','Eastern Cape','6204',NULL,'South Africa',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(108,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}',NULL,'gravatar',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1930 Uitsig St',NULL,'Port Elizabeth','Eastern Cape','6204',NULL,'South Africa',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(109,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}',NULL,'gravatar',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1930 Uitsig St',NULL,'Port Elizabeth','Eastern Cape','6204',NULL,'South Africa',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(110,5,NULL,1,'2024-07-17 23:02:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'a:0:{}','a:0:{}',NULL,'gravatar',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1930 Uitsig St',NULL,'Port Elizabeth','Eastern Cape','6204',NULL,'South Africa',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_channels`
--

DROP TABLE IF EXISTS `message_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_channels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(10) unsigned NOT NULL,
  `channel` varchar(191) NOT NULL,
  `channel_id` int(11) DEFAULT NULL,
  `properties` longtext NOT NULL COMMENT '(DC2Type:json)',
  `is_enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `channel_index` (`message_id`,`channel`),
  KEY `IDX_FA3226A7537A1329` (`message_id`),
  KEY `channel_entity_index` (`channel`,`channel_id`),
  KEY `channel_enabled_index` (`channel`,`is_enabled`),
  CONSTRAINT `FK_FA3226A7537A1329` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_channels`
--

LOCK TABLES `message_channels` WRITE;
/*!40000 ALTER TABLE `message_channels` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_channels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_queue`
--

DROP TABLE IF EXISTS `message_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(10) unsigned DEFAULT NULL,
  `lead_id` bigint(20) unsigned NOT NULL,
  `channel` varchar(191) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `priority` smallint(6) NOT NULL,
  `max_attempts` smallint(6) NOT NULL,
  `attempts` smallint(6) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `status` varchar(191) NOT NULL,
  `date_published` datetime DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `last_attempt` datetime DEFAULT NULL,
  `date_sent` datetime DEFAULT NULL,
  `options` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_805B808871F7E88B` (`event_id`),
  KEY `IDX_805B808855458D` (`lead_id`),
  KEY `message_status_search` (`status`),
  KEY `message_date_sent` (`date_sent`),
  KEY `message_scheduled_date` (`scheduled_date`),
  KEY `message_priority` (`priority`),
  KEY `message_success` (`success`),
  KEY `message_channel_search` (`channel`,`channel_id`),
  CONSTRAINT `FK_805B808855458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_805B808871F7E88B` FOREIGN KEY (`event_id`) REFERENCES `campaign_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_queue`
--

LOCK TABLES `message_queue` WRITE;
/*!40000 ALTER TABLE `message_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DB021E9612469DE2` (`category_id`),
  KEY `date_message_added` (`date_added`),
  CONSTRAINT `FK_DB021E9612469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monitor_post_count`
--

DROP TABLE IF EXISTS `monitor_post_count`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monitor_post_count` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `monitor_id` int(10) unsigned DEFAULT NULL,
  `post_date` date NOT NULL,
  `post_count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E3AC20CA4CE1C902` (`monitor_id`),
  CONSTRAINT `FK_E3AC20CA4CE1C902` FOREIGN KEY (`monitor_id`) REFERENCES `monitoring` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monitor_post_count`
--

LOCK TABLES `monitor_post_count` WRITE;
/*!40000 ALTER TABLE `monitor_post_count` DISABLE KEYS */;
/*!40000 ALTER TABLE `monitor_post_count` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monitoring`
--

DROP TABLE IF EXISTS `monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monitoring` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `lists` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `network_type` varchar(191) DEFAULT NULL,
  `revision` int(11) NOT NULL,
  `stats` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `properties` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BA4F975D12469DE2` (`category_id`),
  CONSTRAINT `FK_BA4F975D12469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monitoring`
--

LOCK TABLES `monitoring` WRITE;
/*!40000 ALTER TABLE `monitoring` DISABLE KEYS */;
/*!40000 ALTER TABLE `monitoring` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monitoring_leads`
--

DROP TABLE IF EXISTS `monitoring_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monitoring_leads` (
  `monitor_id` int(10) unsigned NOT NULL,
  `lead_id` bigint(20) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`monitor_id`,`lead_id`),
  KEY `IDX_45207A4A55458D` (`lead_id`),
  CONSTRAINT `FK_45207A4A4CE1C902` FOREIGN KEY (`monitor_id`) REFERENCES `monitoring` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_45207A4A55458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monitoring_leads`
--

LOCK TABLES `monitoring_leads` WRITE;
/*!40000 ALTER TABLE `monitoring_leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `monitoring_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `type` varchar(25) DEFAULT NULL,
  `header` varchar(512) DEFAULT NULL,
  `message` longtext NOT NULL,
  `date_added` datetime NOT NULL,
  `icon_class` varchar(191) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL,
  `deduplicate` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6000B0D3A76ED395` (`user_id`),
  KEY `notification_read_status` (`is_read`),
  KEY `notification_type` (`type`),
  KEY `notification_user_read_status` (`is_read`,`user_id`),
  KEY `deduplicate_date_added` (`deduplicate`,`date_added`),
  CONSTRAINT `FK_6000B0D3A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth2_accesstokens`
--

DROP TABLE IF EXISTS `oauth2_accesstokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth2_accesstokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `token` varchar(191) NOT NULL,
  `expires_at` bigint(20) DEFAULT NULL,
  `scope` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_3A18CA5A5F37A13B` (`token`),
  KEY `IDX_3A18CA5A19EB6921` (`client_id`),
  KEY `IDX_3A18CA5AA76ED395` (`user_id`),
  KEY `oauth2_access_token_search` (`token`),
  CONSTRAINT `FK_3A18CA5A19EB6921` FOREIGN KEY (`client_id`) REFERENCES `oauth2_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_3A18CA5AA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth2_accesstokens`
--

LOCK TABLES `oauth2_accesstokens` WRITE;
/*!40000 ALTER TABLE `oauth2_accesstokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth2_accesstokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth2_authcodes`
--

DROP TABLE IF EXISTS `oauth2_authcodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth2_authcodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(191) NOT NULL,
  `expires_at` bigint(20) DEFAULT NULL,
  `scope` varchar(191) DEFAULT NULL,
  `redirect_uri` longtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_D2B4847B5F37A13B` (`token`),
  KEY `IDX_D2B4847B19EB6921` (`client_id`),
  KEY `IDX_D2B4847BA76ED395` (`user_id`),
  CONSTRAINT `FK_D2B4847B19EB6921` FOREIGN KEY (`client_id`) REFERENCES `oauth2_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_D2B4847BA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth2_authcodes`
--

LOCK TABLES `oauth2_authcodes` WRITE;
/*!40000 ALTER TABLE `oauth2_authcodes` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth2_authcodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth2_clients`
--

DROP TABLE IF EXISTS `oauth2_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth2_clients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `random_id` varchar(191) NOT NULL,
  `secret` varchar(191) NOT NULL,
  `redirect_uris` longtext NOT NULL COMMENT '(DC2Type:array)',
  `allowed_grant_types` longtext NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_F9D02AE6D60322AC` (`role_id`),
  KEY `client_id_search` (`random_id`),
  CONSTRAINT `FK_F9D02AE6D60322AC` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth2_clients`
--

LOCK TABLES `oauth2_clients` WRITE;
/*!40000 ALTER TABLE `oauth2_clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth2_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth2_refreshtokens`
--

DROP TABLE IF EXISTS `oauth2_refreshtokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth2_refreshtokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(191) NOT NULL,
  `expires_at` bigint(20) DEFAULT NULL,
  `scope` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_328C5B1B5F37A13B` (`token`),
  KEY `IDX_328C5B1B19EB6921` (`client_id`),
  KEY `IDX_328C5B1BA76ED395` (`user_id`),
  KEY `oauth2_refresh_token_search` (`token`),
  CONSTRAINT `FK_328C5B1B19EB6921` FOREIGN KEY (`client_id`) REFERENCES `oauth2_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_328C5B1BA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth2_refreshtokens`
--

LOCK TABLES `oauth2_refreshtokens` WRITE;
/*!40000 ALTER TABLE `oauth2_refreshtokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth2_refreshtokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth2_user_client_xref`
--

DROP TABLE IF EXISTS `oauth2_user_client_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth2_user_client_xref` (
  `client_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`client_id`,`user_id`),
  KEY `IDX_1AE34413A76ED395` (`user_id`),
  CONSTRAINT `FK_1AE3441319EB6921` FOREIGN KEY (`client_id`) REFERENCES `oauth2_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1AE34413A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth2_user_client_xref`
--

LOCK TABLES `oauth2_user_client_xref` WRITE;
/*!40000 ALTER TABLE `oauth2_user_client_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth2_user_client_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `page_hits`
--

DROP TABLE IF EXISTS `page_hits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page_hits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(10) unsigned DEFAULT NULL,
  `redirect_id` bigint(20) unsigned DEFAULT NULL,
  `email_id` int(10) unsigned DEFAULT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `ip_id` int(10) unsigned DEFAULT NULL,
  `device_id` bigint(20) unsigned DEFAULT NULL,
  `date_hit` datetime NOT NULL,
  `date_left` datetime DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `region` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `isp` varchar(191) DEFAULT NULL,
  `organization` varchar(191) DEFAULT NULL,
  `code` int(11) NOT NULL,
  `referer` longtext DEFAULT NULL,
  `url` longtext DEFAULT NULL,
  `url_title` varchar(191) DEFAULT NULL,
  `user_agent` longtext DEFAULT NULL,
  `remote_host` varchar(191) DEFAULT NULL,
  `page_language` varchar(191) DEFAULT NULL,
  `browser_languages` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `tracking_id` varchar(191) NOT NULL,
  `source` varchar(191) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `query` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_9D4B70F1C4663E4` (`page_id`),
  KEY `IDX_9D4B70F1B42D874D` (`redirect_id`),
  KEY `IDX_9D4B70F1A832C1C9` (`email_id`),
  KEY `IDX_9D4B70F155458D` (`lead_id`),
  KEY `IDX_9D4B70F1A03F5E9F` (`ip_id`),
  KEY `IDX_9D4B70F194A4C7D4` (`device_id`),
  KEY `page_hit_tracking_search` (`tracking_id`),
  KEY `page_hit_code_search` (`code`),
  KEY `page_hit_source_search` (`source`,`source_id`),
  KEY `date_hit_left_index` (`date_hit`,`date_left`),
  KEY `page_hit_url` (`url`(128)),
  CONSTRAINT `FK_9D4B70F155458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9D4B70F194A4C7D4` FOREIGN KEY (`device_id`) REFERENCES `lead_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9D4B70F1A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `ip_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9D4B70F1A832C1C9` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9D4B70F1B42D874D` FOREIGN KEY (`redirect_id`) REFERENCES `page_redirects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9D4B70F1C4663E4` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=285 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page_hits`
--

LOCK TABLES `page_hits` WRITE;
/*!40000 ALTER TABLE `page_hits` DISABLE KEYS */;
INSERT INTO `page_hits` VALUES (143,NULL,NULL,NULL,58,55,NULL,'2024-07-16 23:03:00',NULL,NULL,NULL,NULL,NULL,NULL,200,'http://mautic.com','http://test.com','Test Title',NULL,NULL,NULL,'a:0:{}','asdf',NULL,NULL,'a:0:{}'),(144,NULL,NULL,NULL,59,56,NULL,'2024-07-15 23:03:00',NULL,NULL,NULL,NULL,NULL,NULL,200,'https://test.com','https://test/regex-segment-3.com','Test Regex Url',NULL,NULL,NULL,'a:0:{}','abcdr',NULL,NULL,'a:0:{}'),(145,NULL,NULL,NULL,60,57,NULL,'2024-07-14 23:03:00',NULL,NULL,NULL,NULL,NULL,NULL,200,'https://test.com','https://test/regex-segment-2.com','Test Regex Url',NULL,NULL,NULL,'a:0:{}','abcdr',NULL,NULL,'a:0:{}'),(146,NULL,NULL,NULL,61,58,NULL,'2024-07-12 23:03:00',NULL,NULL,NULL,NULL,NULL,NULL,200,'https://test.com','https://test/regex-segment-85.com','Test Regex Url',NULL,NULL,NULL,'a:0:{}','abcdr',NULL,NULL,'a:0:{}'),(147,NULL,NULL,NULL,62,59,NULL,'2024-07-14 23:03:00',NULL,NULL,NULL,NULL,NULL,NULL,200,'https://test.com','https://test/regex-segment-0.com','Test Regex Url',NULL,NULL,NULL,'a:0:{}','abcdr',NULL,NULL,'a:0:{}'),(148,NULL,NULL,NULL,62,59,NULL,'2024-07-14 23:03:00',NULL,NULL,NULL,NULL,NULL,NULL,200,'https://test.com','https://test/regex-segment-other.com','Test Title',NULL,NULL,NULL,'a:0:{}','iomio',NULL,NULL,'a:0:{}'),(149,NULL,2,4,58,55,NULL,'2024-07-17 23:03:00',NULL,NULL,NULL,NULL,NULL,NULL,200,'https://google.com','https://mautic.org','Test Title',NULL,NULL,NULL,'a:0:{}','abc','email',4,'a:0:{}'),(150,NULL,2,4,59,55,NULL,'2024-07-16 23:03:00',NULL,NULL,NULL,NULL,NULL,NULL,200,'https://google.com','https://mautic.org','Test Title',NULL,NULL,NULL,'a:0:{}','abc','email',4,'a:0:{}'),(151,NULL,2,4,59,55,NULL,'2024-07-18 23:03:00',NULL,NULL,NULL,NULL,NULL,NULL,200,'https://google.com','https://mautic.org','Test Title',NULL,NULL,NULL,'a:0:{}','abc','email',4,'a:0:{}'),(152,NULL,2,NULL,59,55,NULL,'2024-07-17 23:03:00',NULL,NULL,NULL,NULL,NULL,NULL,200,'https://google.com','https://mautic.org','Test Title',NULL,NULL,NULL,'a:0:{}','abc','sms',2,'a:0:{}'),(153,NULL,2,NULL,58,55,NULL,'2024-07-16 23:03:00',NULL,NULL,NULL,NULL,NULL,NULL,200,'https://google.com','https://mautic.org','Test Title',NULL,NULL,NULL,'a:0:{}','abc','sms',2,'a:0:{}'),(154,NULL,2,NULL,60,55,NULL,'2024-07-18 23:03:00',NULL,NULL,NULL,NULL,NULL,NULL,200,'https://google.com','https://mautic.org','Test Title',NULL,NULL,NULL,'a:0:{}','abc','sms',2,'a:0:{}'),(155,6,NULL,NULL,NULL,55,NULL,'2014-08-09 19:00:00','2014-08-09 19:01:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/3','http://localhost/mautic/p/page/es_MX/3:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'es_MX','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','H764T844',NULL,NULL,'a:0:{}'),(156,4,NULL,NULL,NULL,92,NULL,'2014-08-10 00:22:00','2014-08-10 00:22:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','5KPMMUCC',NULL,NULL,'a:0:{}'),(157,4,NULL,NULL,NULL,90,NULL,'2014-08-10 00:22:00','2014-08-10 00:22:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','5KPMMUCC',NULL,NULL,'a:0:{}'),(158,4,NULL,NULL,NULL,69,NULL,'2014-08-10 00:22:00','2014-08-10 00:22:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','E2Q6OBTT',NULL,NULL,'a:0:{}'),(159,4,NULL,NULL,NULL,72,NULL,'2014-08-10 00:23:00','2014-08-10 00:23:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','RZT87U00',NULL,NULL,'a:0:{}'),(160,4,NULL,NULL,NULL,96,NULL,'2014-08-10 00:23:00','2014-08-10 00:23:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','RZT87U00',NULL,NULL,'a:0:{}'),(161,4,NULL,NULL,NULL,59,NULL,'2014-08-10 00:23:00','2014-08-10 00:23:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','YOQLXM44',NULL,NULL,'a:0:{}'),(162,4,NULL,NULL,NULL,55,NULL,'2014-08-10 00:23:00','2014-08-10 00:23:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','OBKQCCRR',NULL,NULL,'a:0:{}'),(163,4,NULL,NULL,NULL,93,NULL,'2014-08-10 00:23:00','2014-08-10 00:23:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','OBKQCCRR',NULL,NULL,'a:0:{}'),(164,4,NULL,NULL,NULL,96,NULL,'2014-08-10 00:24:00','2014-08-10 00:24:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','SUDI7EZZ',NULL,NULL,'a:0:{}'),(165,4,NULL,NULL,NULL,96,NULL,'2014-08-10 00:24:00','2014-08-10 00:24:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','6QM3ZGEE',NULL,NULL,'a:0:{}'),(166,4,NULL,NULL,NULL,86,NULL,'2014-08-10 00:24:00','2014-08-10 00:24:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','JBW4CYRR',NULL,NULL,'a:0:{}'),(167,4,NULL,NULL,NULL,91,NULL,'2014-08-10 00:24:00','2014-08-10 00:24:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','IYPAQXX',NULL,NULL,'a:0:{}'),(168,4,NULL,NULL,NULL,89,NULL,'2014-08-10 00:24:00','2014-08-10 00:24:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','RDDQEK44',NULL,NULL,'a:0:{}'),(169,4,NULL,NULL,NULL,68,NULL,'2014-08-10 00:24:00','2014-08-10 00:24:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','3GLTTX00',NULL,NULL,'a:0:{}'),(170,4,NULL,NULL,NULL,69,NULL,'2014-08-10 00:24:00','2014-08-10 00:24:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','Z7H6S6CC',NULL,NULL,'a:0:{}'),(171,4,NULL,NULL,NULL,86,NULL,'2014-08-10 00:24:00','2014-08-10 00:24:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','ZIPRWGMM',NULL,NULL,'a:0:{}'),(172,4,NULL,NULL,NULL,69,NULL,'2014-08-10 00:24:00','2014-08-10 00:24:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','9WOPU3WW',NULL,NULL,'a:0:{}'),(173,4,NULL,NULL,NULL,84,NULL,'2014-08-10 00:26:00','2014-08-10 00:26:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','9QW2PE44',NULL,NULL,'a:0:{}'),(174,4,NULL,NULL,NULL,56,NULL,'2014-08-10 00:26:00','2014-08-10 00:26:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','KT7FHXSS',NULL,NULL,'a:0:{}'),(175,4,NULL,NULL,NULL,74,NULL,'2014-08-10 00:26:00','2014-08-10 00:26:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','D3O0F7NN',NULL,NULL,'a:0:{}'),(176,4,NULL,NULL,NULL,97,NULL,'2014-08-10 00:27:00','2014-08-10 00:27:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','QRT1O6VV',NULL,NULL,'a:0:{}'),(177,4,NULL,NULL,NULL,58,NULL,'2014-08-10 00:27:00','2014-08-10 00:27:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','CLRK3CMM',NULL,NULL,'a:0:{}'),(178,4,NULL,NULL,NULL,97,NULL,'2014-08-10 00:27:00','2014-08-10 00:27:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','4H7BVT22',NULL,NULL,'a:0:{}'),(179,4,NULL,NULL,NULL,56,NULL,'2014-08-10 00:27:00','2014-08-10 00:28:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','6F8ZXEE',NULL,NULL,'a:0:{}'),(180,4,NULL,NULL,NULL,85,NULL,'2014-08-10 00:30:00','2014-08-10 00:30:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','WYY3KF66',NULL,NULL,'a:0:{}'),(181,5,NULL,NULL,NULL,100,NULL,'2014-08-10 00:30:00','2014-08-10 00:30:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','HSZ4NWKK',NULL,NULL,'a:0:{}'),(182,5,NULL,NULL,NULL,90,NULL,'2014-08-10 00:30:00','2014-08-10 00:30:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','4M9RF144',NULL,NULL,'a:0:{}'),(183,5,NULL,NULL,NULL,99,NULL,'2014-08-10 00:30:00','2014-08-10 00:30:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','GIYIEJ22',NULL,NULL,'a:0:{}'),(184,5,NULL,NULL,NULL,68,NULL,'2014-08-10 00:30:00','2014-08-10 00:30:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','OPAIDMM',NULL,NULL,'a:0:{}'),(185,5,NULL,NULL,NULL,89,NULL,'2014-08-10 00:30:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','P9JOSSVV',NULL,NULL,'a:0:{}'),(186,5,NULL,NULL,NULL,87,NULL,'2014-08-10 00:30:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','Q8SLO5YY',NULL,NULL,'a:0:{}'),(187,5,NULL,NULL,NULL,62,NULL,'2014-08-10 00:30:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','OGRXTRQQ',NULL,NULL,'a:0:{}'),(188,5,NULL,NULL,NULL,96,NULL,'2014-08-10 00:30:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','1R45QTUU',NULL,NULL,'a:0:{}'),(189,5,NULL,NULL,NULL,89,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','OB1QZ144',NULL,NULL,'a:0:{}'),(190,5,NULL,NULL,NULL,101,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','UZ00ZR66',NULL,NULL,'a:0:{}'),(191,5,NULL,NULL,NULL,85,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','KRQ2F4FF',NULL,NULL,'a:0:{}'),(192,5,NULL,NULL,NULL,69,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','NVN2JEFF',NULL,NULL,'a:0:{}'),(193,5,NULL,NULL,NULL,82,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','UJFR71NN',NULL,NULL,'a:0:{}'),(194,5,NULL,NULL,NULL,101,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','CZ69ZOUU',NULL,NULL,'a:0:{}'),(195,5,NULL,NULL,NULL,56,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','Z03WC1OO',NULL,NULL,'a:0:{}'),(196,5,NULL,NULL,NULL,69,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','YLUNOS33',NULL,NULL,'a:0:{}'),(197,5,NULL,NULL,NULL,75,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','4Q4L3EDD',NULL,NULL,'a:0:{}'),(198,5,NULL,NULL,NULL,63,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','F73DD644',NULL,NULL,'a:0:{}'),(199,5,NULL,NULL,NULL,87,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','RVOJXM00',NULL,NULL,'a:0:{}'),(200,5,NULL,NULL,NULL,90,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','WYRHCFF',NULL,NULL,'a:0:{}'),(201,5,NULL,NULL,NULL,84,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','PX57555',NULL,NULL,'a:0:{}'),(202,5,NULL,NULL,NULL,98,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','0IBIS0GG',NULL,NULL,'a:0:{}'),(203,5,NULL,NULL,NULL,82,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','LFOHPMNN',NULL,NULL,'a:0:{}'),(204,5,NULL,NULL,NULL,63,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','74CM5J99',NULL,NULL,'a:0:{}'),(205,5,NULL,NULL,NULL,63,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','4EZBX0GG',NULL,NULL,'a:0:{}'),(206,5,NULL,NULL,NULL,73,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','MKEVKDZZ',NULL,NULL,'a:0:{}'),(207,5,NULL,NULL,NULL,73,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','99LNEEKK',NULL,NULL,'a:0:{}'),(208,5,NULL,NULL,NULL,89,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','QDPXP9',NULL,NULL,'a:0:{}'),(209,5,NULL,NULL,NULL,72,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','ICW7BS77',NULL,NULL,'a:0:{}'),(210,5,NULL,NULL,NULL,90,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','EFAHDC99',NULL,NULL,'a:0:{}'),(211,5,NULL,NULL,NULL,78,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','LM4J9PII',NULL,NULL,'a:0:{}'),(212,5,NULL,NULL,NULL,65,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','2S3J1K66',NULL,NULL,'a:0:{}'),(213,5,NULL,NULL,NULL,88,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','3EXUCLEE',NULL,NULL,'a:0:{}'),(214,5,NULL,NULL,NULL,91,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','W5TR0BRR',NULL,NULL,'a:0:{}'),(215,5,NULL,NULL,NULL,85,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','3I4TC288',NULL,NULL,'a:0:{}'),(216,5,NULL,NULL,NULL,98,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','IQLVMMUU',NULL,NULL,'a:0:{}'),(217,5,NULL,NULL,NULL,79,NULL,'2014-08-10 00:31:00','2014-08-10 00:31:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','XC5ZPDXX',NULL,NULL,'a:0:{}'),(218,5,NULL,NULL,NULL,97,NULL,'2014-08-10 00:32:00','2014-08-10 00:32:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','WNOL0UZZ',NULL,NULL,'a:0:{}'),(219,5,NULL,NULL,NULL,93,NULL,'2014-08-10 00:32:00','2014-08-10 00:32:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','KOFQ3044',NULL,NULL,'a:0:{}'),(220,5,NULL,NULL,NULL,71,NULL,'2014-08-10 00:32:00','2014-08-10 00:32:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','GI26RS66',NULL,NULL,'a:0:{}'),(221,5,NULL,NULL,NULL,71,NULL,'2014-08-10 00:32:00','2014-08-10 00:32:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','GQEN0GEE',NULL,NULL,'a:0:{}'),(222,5,NULL,NULL,NULL,87,NULL,'2014-08-10 00:40:00','2014-08-10 00:40:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','ONZRBVGG',NULL,NULL,'a:0:{}'),(223,5,NULL,NULL,NULL,68,NULL,'2014-08-10 00:40:00','2014-08-10 00:41:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','N4MLY555',NULL,NULL,'a:0:{}'),(224,4,NULL,NULL,NULL,76,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','AGEYKUYY',NULL,NULL,'a:0:{}'),(225,5,NULL,NULL,NULL,69,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','TOWNL9UU',NULL,NULL,'a:0:{}'),(226,5,NULL,NULL,NULL,64,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','3SN3WPJJ',NULL,NULL,'a:0:{}'),(227,5,NULL,NULL,NULL,58,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','RQY64ZHH',NULL,NULL,'a:0:{}'),(228,5,NULL,NULL,NULL,95,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','O3BDIVWW',NULL,NULL,'a:0:{}'),(229,5,NULL,NULL,NULL,94,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','G2JULRR',NULL,NULL,'a:0:{}'),(230,5,NULL,NULL,NULL,81,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','9Q4LJOWW',NULL,NULL,'a:0:{}'),(231,5,NULL,NULL,NULL,68,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','ZKDZ9N00',NULL,NULL,'a:0:{}'),(232,5,NULL,NULL,NULL,91,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','JLPUNPII',NULL,NULL,'a:0:{}'),(233,5,NULL,NULL,NULL,100,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','ATVT2KUU',NULL,NULL,'a:0:{}'),(234,5,NULL,NULL,NULL,70,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','6L5GEWII',NULL,NULL,'a:0:{}'),(235,5,NULL,NULL,NULL,97,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','C0EX4933',NULL,NULL,'a:0:{}'),(236,5,NULL,NULL,NULL,71,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','1JWEEY',NULL,NULL,'a:0:{}'),(237,5,NULL,NULL,NULL,60,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','HMN1FZ77',NULL,NULL,'a:0:{}'),(238,5,NULL,NULL,NULL,83,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','4V37K977',NULL,NULL,'a:0:{}'),(239,5,NULL,NULL,NULL,78,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','GBDDG0JJ',NULL,NULL,'a:0:{}'),(240,5,NULL,NULL,NULL,89,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','8EJ7OW99',NULL,NULL,'a:0:{}'),(241,5,NULL,NULL,NULL,55,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','Y21VCNLL',NULL,NULL,'a:0:{}'),(242,5,NULL,NULL,NULL,55,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','4OOI0C66',NULL,NULL,'a:0:{}'),(243,5,NULL,NULL,NULL,56,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','3XQD0E33',NULL,NULL,'a:0:{}'),(244,5,NULL,NULL,NULL,60,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','W5PNQ9CC',NULL,NULL,'a:0:{}'),(245,5,NULL,NULL,NULL,79,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','11BBNP',NULL,NULL,'a:0:{}'),(246,5,NULL,NULL,NULL,59,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','3QVAHF11',NULL,NULL,'a:0:{}'),(247,5,NULL,NULL,NULL,103,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','4DCW3PVV',NULL,NULL,'a:0:{}'),(248,5,NULL,NULL,NULL,82,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','0EZB9OCC',NULL,NULL,'a:0:{}'),(249,5,NULL,NULL,NULL,100,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','ODB8SKHH',NULL,NULL,'a:0:{}'),(250,5,NULL,NULL,NULL,98,NULL,'2014-08-10 00:47:00','2014-08-10 00:47:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','F744CMII',NULL,NULL,'a:0:{}'),(251,4,NULL,NULL,NULL,86,NULL,'2014-08-10 01:08:00','2014-08-10 01:08:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','ZGWS2Z33',NULL,NULL,'a:0:{}'),(252,5,NULL,NULL,NULL,80,NULL,'2014-08-10 01:08:00','2014-08-10 01:08:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','95XW2JVV',NULL,NULL,'a:0:{}'),(253,5,NULL,NULL,NULL,88,NULL,'2014-08-10 01:11:00','2014-08-10 01:11:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','C7EYGG66',NULL,NULL,'a:0:{}'),(254,5,NULL,NULL,NULL,95,NULL,'2014-08-10 01:11:00','2014-08-10 01:11:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','J6C2KJJ',NULL,NULL,'a:0:{}'),(255,5,NULL,NULL,NULL,56,NULL,'2014-08-10 01:11:00','2014-08-10 01:11:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','1US5SPBB',NULL,NULL,'a:0:{}'),(256,5,NULL,NULL,NULL,93,NULL,'2014-08-10 01:12:00','2014-08-10 01:12:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','CW6YD533',NULL,NULL,'a:0:{}'),(257,5,NULL,NULL,NULL,92,NULL,'2014-08-10 01:12:00','2014-08-10 01:12:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','FY9FEAWW',NULL,NULL,'a:0:{}'),(258,5,NULL,NULL,NULL,78,NULL,'2014-08-10 01:12:00','2014-08-10 01:12:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','Z4LXDL66',NULL,NULL,'a:0:{}'),(259,5,NULL,NULL,NULL,57,NULL,'2014-08-10 01:12:00','2014-08-10 01:12:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','IJPE4Z33',NULL,NULL,'a:0:{}'),(260,5,NULL,NULL,NULL,99,NULL,'2014-08-10 01:12:00','2014-08-10 01:12:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','3L76FSS',NULL,NULL,'a:0:{}'),(261,5,NULL,NULL,NULL,67,NULL,'2014-08-10 01:12:00','2014-08-10 01:12:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','UD2OGC44',NULL,NULL,'a:0:{}'),(262,5,NULL,NULL,NULL,85,NULL,'2014-08-10 01:12:00','2014-08-10 01:13:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','Z36Y96YY',NULL,NULL,'a:0:{}'),(263,5,NULL,NULL,NULL,71,NULL,'2014-08-10 01:12:00','2014-08-10 01:13:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','IX3S9766',NULL,NULL,'a:0:{}'),(264,5,NULL,NULL,NULL,92,NULL,'2014-08-10 01:13:00','2014-08-10 01:13:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','OFX17FUU',NULL,NULL,'a:0:{}'),(265,5,NULL,NULL,NULL,96,NULL,'2014-08-10 01:13:00','2014-08-10 01:13:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','7259A4YY',NULL,NULL,'a:0:{}'),(266,5,NULL,NULL,NULL,64,NULL,'2014-08-10 01:15:00','2014-08-10 01:15:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','TK9649UU',NULL,NULL,'a:0:{}'),(267,5,NULL,NULL,NULL,79,NULL,'2014-08-10 01:15:00','2014-08-10 01:15:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','9NZ8HLWW',NULL,NULL,'a:0:{}'),(268,5,NULL,NULL,NULL,85,NULL,'2014-08-10 01:15:00','2014-08-10 01:15:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','8N6RW177',NULL,NULL,'a:0:{}'),(269,5,NULL,NULL,NULL,99,NULL,'2014-08-10 01:15:00','2014-08-10 01:15:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','OC6LS7RR',NULL,NULL,'a:0:{}'),(270,5,NULL,NULL,NULL,80,NULL,'2014-08-10 01:15:00','2014-08-10 01:15:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','QTZ8XMM',NULL,NULL,'a:0:{}'),(271,5,NULL,NULL,NULL,55,NULL,'2014-08-10 01:15:00','2014-08-10 01:15:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','RC7GLHQQ',NULL,NULL,'a:0:{}'),(272,5,NULL,NULL,NULL,70,NULL,'2014-08-10 01:15:00','2014-08-10 01:15:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','MPFEO811',NULL,NULL,'a:0:{}'),(273,5,NULL,NULL,NULL,83,NULL,'2014-08-10 01:15:00','2014-08-10 01:15:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','PSOP6XOO',NULL,NULL,'a:0:{}'),(274,5,NULL,NULL,NULL,90,NULL,'2014-08-10 01:15:00','2014-08-10 01:15:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','7K6Z7LXX',NULL,NULL,'a:0:{}'),(275,5,NULL,NULL,NULL,95,NULL,'2014-08-10 01:17:00','2014-08-10 01:18:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','53e6bd3323eb9',NULL,NULL,'a:0:{}'),(276,5,NULL,NULL,NULL,102,NULL,'2014-08-10 01:18:00','2014-08-10 01:18:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','53e6bd3323eb9',NULL,NULL,'a:0:{}'),(277,4,NULL,NULL,NULL,104,NULL,'2014-08-10 01:20:00','2014-08-10 01:21:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','53e6bb48e5053',NULL,NULL,'a:0:{}'),(278,4,NULL,NULL,NULL,72,NULL,'2014-08-10 01:20:00','2014-08-10 01:20:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','53e6bb48e5053',NULL,NULL,'a:0:{}'),(279,5,NULL,NULL,NULL,59,NULL,'2014-08-10 01:48:00','2014-08-10 01:48:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',NULL,'en','a:1:{i:0;s:5:\"en-US\";}','53e6bd3323eb9',NULL,NULL,'a:0:{}'),(280,4,NULL,NULL,NULL,68,NULL,'2014-08-12 19:51:00','2014-08-12 19:51:00',NULL,NULL,NULL,NULL,NULL,200,'http://localhost/mautic/pages/view/1','http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','53ea6d090aab5',NULL,NULL,'a:0:{}'),(281,5,NULL,NULL,NULL,63,NULL,'2014-08-12 19:51:00','2014-08-12 19:51:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/2:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','53ea6d090aab5',NULL,NULL,'a:0:{}'),(282,5,NULL,NULL,NULL,61,NULL,'2014-08-12 20:02:00','2014-08-12 20:02:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/2:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','53ea72c1c0232',NULL,NULL,'a:0:{}'),(283,4,NULL,NULL,NULL,73,NULL,'2014-08-12 20:02:00','2014-08-12 20:02:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/1:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','53ea72c1c0232',NULL,NULL,'a:0:{}'),(284,5,NULL,NULL,NULL,89,NULL,'2014-08-12 20:02:00','2014-08-12 20:02:00',NULL,NULL,NULL,NULL,NULL,200,NULL,'http://localhost/mautic/p/page/2:kaleidoscope-conference-2014',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:31.0) Gecko/20100101 Firefox/31.0',NULL,'en','a:2:{i:0;s:5:\"en-US\";i:1;s:8:\"en;q=0.5\";}','53ea72c1c0232',NULL,NULL,'a:0:{}');
/*!40000 ALTER TABLE `page_hits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `page_redirects`
--

DROP TABLE IF EXISTS `page_redirects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page_redirects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `redirect_id` varchar(25) NOT NULL,
  `url` longtext NOT NULL,
  `hits` int(11) NOT NULL,
  `unique_hits` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page_redirects`
--

LOCK TABLES `page_redirects` WRITE;
/*!40000 ALTER TABLE `page_redirects` DISABLE KEYS */;
INSERT INTO `page_redirects` VALUES (2,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'dad9de3f80905150e0190a575','https://mautic.org',0,0);
/*!40000 ALTER TABLE `page_redirects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `translation_parent_id` int(10) unsigned DEFAULT NULL,
  `variant_parent_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `alias` varchar(191) NOT NULL,
  `template` varchar(191) DEFAULT NULL,
  `custom_html` longtext DEFAULT NULL,
  `content` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `hits` int(11) NOT NULL,
  `unique_hits` int(11) NOT NULL,
  `variant_hits` int(11) NOT NULL,
  `revision` int(11) NOT NULL,
  `meta_description` varchar(191) DEFAULT NULL,
  `head_script` longtext DEFAULT NULL,
  `footer_script` longtext DEFAULT NULL,
  `redirect_type` varchar(100) DEFAULT NULL,
  `redirect_url` varchar(2048) DEFAULT NULL,
  `is_preference_center` tinyint(1) DEFAULT NULL,
  `no_index` tinyint(1) DEFAULT NULL,
  `lang` varchar(191) NOT NULL,
  `variant_settings` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `variant_start_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2074E57512469DE2` (`category_id`),
  KEY `IDX_2074E5759091A2FB` (`translation_parent_id`),
  KEY `IDX_2074E57591861123` (`variant_parent_id`),
  KEY `page_alias_search` (`alias`),
  CONSTRAINT `FK_2074E57512469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_2074E5759091A2FB` FOREIGN KEY (`translation_parent_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2074E57591861123` FOREIGN KEY (`variant_parent_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (4,18,NULL,NULL,1,'2014-08-09 00:00:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Kaleidoscope Conference 2014','kaleidoscope-conference-2014','blank',NULL,'a:5:{s:4:\"top2\";s:151:\"<div><h1><span style=\"font-family:comic sans\"><span style=\"color:#0000FF\"><span dir=\"auto\">Kaleidoscope Conference 2014</span></span></span></h1></div>\";s:4:\"main\";s:44:\"<div>Sign up today!</div><div>{form=1}</div>\";s:6:\"footer\";s:0:\"\";s:6:\"right1\";s:0:\"\";s:7:\"bottom3\";s:0:\"\";}',NULL,NULL,31,28,28,8,'Join your fellow kaleidoscopians at the 2014 Kaleidoscope Conference.  Learn new techniques, attend workshops, share ideas with others, or just hang out!',NULL,NULL,NULL,NULL,NULL,NULL,'en','a:0:{}','2014-08-09 00:00:00'),(5,18,NULL,4,1,'2014-08-09 00:12:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Kaleidoscope Conference 2014 v2','kaleidoscope-conference-2014','blank',NULL,'a:5:{s:4:\"top2\";s:151:\"<div><h1><span style=\"font-family:comic sans\"><span style=\"color:#0000FF\"><span dir=\"auto\">Kaleidoscope Conference 2014</span></span></span></h1></div>\";s:4:\"main\";s:153:\"<div>Don&#39;t be afraid to reach out to your inner kid once again! Let your creativity roll at the annual Kaleidoscope Conference. Register today!</div>\";s:6:\"footer\";s:0:\"\";s:6:\"right1\";s:55:\"<div><div>Sign up today!</div><div>{form=1}</div></div>\";s:7:\"bottom3\";s:0:\"\";}',NULL,NULL,95,85,85,4,'Join your fellow kaleidoscopians at the 2014 Kaleidoscope Conference.  Learn new techniques, attend workshops, share ideas with others, or just hang out!',NULL,NULL,NULL,NULL,NULL,NULL,'en','a:2:{s:6:\"weight\";i:50;s:14:\"winnerCriteria\";s:14:\"page.dwelltime\";}','2014-08-09 00:00:00'),(6,18,4,NULL,1,'2014-08-09 01:07:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Kaleidoscope Conferencia 2014','kaleidoscope-conference-2014','blank',NULL,'a:5:{s:4:\"top2\";s:151:\"<div><h1><span style=\"font-family:comic sans\"><span style=\"color:#0000FF\"><span dir=\"auto\">Kaleidoscope Conference 2014</span></span></span></h1></div>\";s:4:\"main\";s:44:\"<div>Sign up today!</div><div>{form=2}</div>\";s:6:\"footer\";s:0:\"\";s:6:\"right1\";s:0:\"\";s:7:\"bottom3\";s:0:\"\";}',NULL,NULL,1,1,1,9,'Únete a tus compañeros kaleidoscopians en la Conferencia de 2014 Kaleidoscope. Aprenda nuevas técnicas, asistir a talleres, compartir ideas con los demás, o simplemente pasar el rato!',NULL,NULL,NULL,NULL,NULL,NULL,'es_MX','a:0:{}',NULL);
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned NOT NULL,
  `bundle` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `bitwise` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_perm` (`bundle`,`name`,`role_id`),
  KEY `IDX_2DEDCC6FD60322AC` (`role_id`),
  CONSTRAINT `FK_2DEDCC6FD60322AC` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (3,5,'user','profile',8),(4,5,'lead','leads',1024);
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_integration_settings`
--

DROP TABLE IF EXISTS `plugin_integration_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_integration_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `is_published` tinyint(1) NOT NULL,
  `supported_features` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `api_keys` longtext NOT NULL COMMENT '(DC2Type:array)',
  `feature_settings` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_941A2CE0EC942BCF` (`plugin_id`),
  CONSTRAINT `FK_941A2CE0EC942BCF` FOREIGN KEY (`plugin_id`) REFERENCES `plugins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_integration_settings`
--

LOCK TABLES `plugin_integration_settings` WRITE;
/*!40000 ALTER TABLE `plugin_integration_settings` DISABLE KEYS */;
INSERT INTO `plugin_integration_settings` VALUES (27,14,'GrapesJsBuilder',1,'a:0:{}','a:0:{}','a:0:{}');
/*!40000 ALTER TABLE `plugin_integration_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugins`
--

DROP TABLE IF EXISTS `plugins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `is_missing` tinyint(1) NOT NULL,
  `bundle` varchar(50) NOT NULL,
  `version` varchar(191) DEFAULT NULL,
  `author` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_bundle` (`bundle`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugins`
--

LOCK TABLES `plugins` WRITE;
/*!40000 ALTER TABLE `plugins` DISABLE KEYS */;
INSERT INTO `plugins` VALUES (14,'GrapesJS Builder','GrapesJS Builder with MJML support for Mautic',0,'GrapesJsBuilderBundle','1.0.0','Mautic Community');
/*!40000 ALTER TABLE `plugins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `point_group_contact_score`
--

DROP TABLE IF EXISTS `point_group_contact_score`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point_group_contact_score` (
  `contact_id` bigint(20) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  `score` int(11) NOT NULL,
  PRIMARY KEY (`contact_id`,`group_id`),
  KEY `IDX_9D85A703FE54D947` (`group_id`),
  CONSTRAINT `FK_9D85A703E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_9D85A703FE54D947` FOREIGN KEY (`group_id`) REFERENCES `point_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `point_group_contact_score`
--

LOCK TABLES `point_group_contact_score` WRITE;
/*!40000 ALTER TABLE `point_group_contact_score` DISABLE KEYS */;
/*!40000 ALTER TABLE `point_group_contact_score` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `point_groups`
--

DROP TABLE IF EXISTS `point_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `point_groups`
--

LOCK TABLES `point_groups` WRITE;
/*!40000 ALTER TABLE `point_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `point_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `point_lead_action_log`
--

DROP TABLE IF EXISTS `point_lead_action_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point_lead_action_log` (
  `point_id` int(10) unsigned NOT NULL,
  `lead_id` bigint(20) unsigned NOT NULL,
  `ip_id` int(10) unsigned DEFAULT NULL,
  `date_fired` datetime NOT NULL,
  PRIMARY KEY (`point_id`,`lead_id`),
  KEY `IDX_6DF94A5655458D` (`lead_id`),
  KEY `IDX_6DF94A56A03F5E9F` (`ip_id`),
  CONSTRAINT `FK_6DF94A5655458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6DF94A56A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `ip_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_6DF94A56C028CEA2` FOREIGN KEY (`point_id`) REFERENCES `points` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `point_lead_action_log`
--

LOCK TABLES `point_lead_action_log` WRITE;
/*!40000 ALTER TABLE `point_lead_action_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `point_lead_action_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `point_lead_event_log`
--

DROP TABLE IF EXISTS `point_lead_event_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point_lead_event_log` (
  `event_id` int(10) unsigned NOT NULL,
  `lead_id` bigint(20) unsigned NOT NULL,
  `ip_id` int(10) unsigned DEFAULT NULL,
  `date_fired` datetime NOT NULL,
  PRIMARY KEY (`event_id`,`lead_id`),
  KEY `IDX_C2A3BDBA55458D` (`lead_id`),
  KEY `IDX_C2A3BDBAA03F5E9F` (`ip_id`),
  CONSTRAINT `FK_C2A3BDBA55458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_C2A3BDBA71F7E88B` FOREIGN KEY (`event_id`) REFERENCES `point_trigger_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_C2A3BDBAA03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `ip_addresses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `point_lead_event_log`
--

LOCK TABLES `point_lead_event_log` WRITE;
/*!40000 ALTER TABLE `point_lead_event_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `point_lead_event_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `point_trigger_events`
--

DROP TABLE IF EXISTS `point_trigger_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point_trigger_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trigger_id` int(10) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `action_order` int(11) NOT NULL,
  `properties` longtext NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_D5669585FDDDCD6` (`trigger_id`),
  KEY `trigger_type_search` (`type`),
  CONSTRAINT `FK_D5669585FDDDCD6` FOREIGN KEY (`trigger_id`) REFERENCES `point_triggers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `point_trigger_events`
--

LOCK TABLES `point_trigger_events` WRITE;
/*!40000 ALTER TABLE `point_trigger_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `point_trigger_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `point_triggers`
--

DROP TABLE IF EXISTS `point_triggers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point_triggers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `group_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `points` int(11) NOT NULL,
  `color` varchar(7) NOT NULL,
  `trigger_existing_leads` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9CABD32F12469DE2` (`category_id`),
  KEY `IDX_9CABD32FFE54D947` (`group_id`),
  CONSTRAINT `FK_9CABD32F12469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9CABD32FFE54D947` FOREIGN KEY (`group_id`) REFERENCES `point_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `point_triggers`
--

LOCK TABLES `point_triggers` WRITE;
/*!40000 ALTER TABLE `point_triggers` DISABLE KEYS */;
/*!40000 ALTER TABLE `point_triggers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `points`
--

DROP TABLE IF EXISTS `points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `points` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `group_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `repeatable` tinyint(1) NOT NULL,
  `delta` int(11) NOT NULL,
  `properties` longtext NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_27BA8E2912469DE2` (`category_id`),
  KEY `IDX_27BA8E29FE54D947` (`group_id`),
  KEY `point_type_search` (`type`),
  CONSTRAINT `FK_27BA8E2912469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_27BA8E29FE54D947` FOREIGN KEY (`group_id`) REFERENCES `point_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `points`
--

LOCK TABLES `points` WRITE;
/*!40000 ALTER TABLE `points` DISABLE KEYS */;
/*!40000 ALTER TABLE `points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_ids`
--

DROP TABLE IF EXISTS `push_ids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `push_ids` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `push_id` varchar(191) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `mobile` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4F2393E855458D` (`lead_id`),
  CONSTRAINT `FK_4F2393E855458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_ids`
--

LOCK TABLES `push_ids` WRITE;
/*!40000 ALTER TABLE `push_ids` DISABLE KEYS */;
/*!40000 ALTER TABLE `push_ids` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_notification_list_xref`
--

DROP TABLE IF EXISTS `push_notification_list_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `push_notification_list_xref` (
  `notification_id` int(10) unsigned NOT NULL,
  `leadlist_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`notification_id`,`leadlist_id`),
  KEY `IDX_473919EFB9FC8874` (`leadlist_id`),
  CONSTRAINT `FK_473919EFB9FC8874` FOREIGN KEY (`leadlist_id`) REFERENCES `lead_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_473919EFEF1A9D84` FOREIGN KEY (`notification_id`) REFERENCES `push_notifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_notification_list_xref`
--

LOCK TABLES `push_notification_list_xref` WRITE;
/*!40000 ALTER TABLE `push_notification_list_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `push_notification_list_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_notification_stats`
--

DROP TABLE IF EXISTS `push_notification_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `push_notification_stats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` int(10) unsigned DEFAULT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `list_id` int(10) unsigned DEFAULT NULL,
  `ip_id` int(10) unsigned DEFAULT NULL,
  `date_sent` datetime NOT NULL,
  `date_read` datetime DEFAULT NULL,
  `is_clicked` tinyint(1) NOT NULL,
  `date_clicked` datetime DEFAULT NULL,
  `tracking_hash` varchar(191) DEFAULT NULL,
  `retry_count` int(11) DEFAULT NULL,
  `source` varchar(191) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `tokens` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `click_count` int(11) DEFAULT NULL,
  `last_clicked` datetime DEFAULT NULL,
  `click_details` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_DE63695EEF1A9D84` (`notification_id`),
  KEY `IDX_DE63695E55458D` (`lead_id`),
  KEY `IDX_DE63695E3DAE168B` (`list_id`),
  KEY `IDX_DE63695EA03F5E9F` (`ip_id`),
  KEY `stat_notification_search` (`notification_id`,`lead_id`),
  KEY `stat_notification_clicked_search` (`is_clicked`),
  KEY `stat_notification_hash_search` (`tracking_hash`),
  KEY `stat_notification_source_search` (`source`,`source_id`),
  CONSTRAINT `FK_DE63695E3DAE168B` FOREIGN KEY (`list_id`) REFERENCES `lead_lists` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_DE63695E55458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_DE63695EA03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `ip_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_DE63695EEF1A9D84` FOREIGN KEY (`notification_id`) REFERENCES `push_notifications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_notification_stats`
--

LOCK TABLES `push_notification_stats` WRITE;
/*!40000 ALTER TABLE `push_notification_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `push_notification_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_notifications`
--

DROP TABLE IF EXISTS `push_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `push_notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `lang` varchar(191) NOT NULL,
  `url` longtext DEFAULT NULL,
  `heading` longtext NOT NULL,
  `message` longtext NOT NULL,
  `button` longtext DEFAULT NULL,
  `utm_tags` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `notification_type` longtext DEFAULT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `read_count` int(11) NOT NULL,
  `sent_count` int(11) NOT NULL,
  `mobile` tinyint(1) NOT NULL,
  `mobileSettings` longtext NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_5B9B7E4F12469DE2` (`category_id`),
  CONSTRAINT `FK_5B9B7E4F12469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_notifications`
--

LOCK TABLES `push_notifications` WRITE;
/*!40000 ALTER TABLE `push_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `push_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `system` tinyint(1) NOT NULL,
  `source` varchar(191) NOT NULL,
  `columns` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `filters` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `table_order` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `graphs` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `group_by` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `aggregators` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `settings` longtext DEFAULT NULL COMMENT '(DC2Type:json)',
  `is_scheduled` tinyint(1) NOT NULL,
  `schedule_unit` varchar(191) DEFAULT NULL,
  `to_address` varchar(191) DEFAULT NULL,
  `schedule_day` varchar(191) DEFAULT NULL,
  `schedule_month_frequency` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports`
--

LOCK TABLES `reports` WRITE;
/*!40000 ALTER TABLE `reports` DISABLE KEYS */;
INSERT INTO `reports` VALUES (11,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Visits published Pages',NULL,1,'page.hits','a:7:{i:0;s:11:\"ph.date_hit\";i:1;s:6:\"ph.url\";i:2;s:12:\"ph.url_title\";i:3;s:10:\"ph.referer\";i:4;s:12:\"i.ip_address\";i:5;s:7:\"ph.city\";i:6;s:10:\"ph.country\";}','a:2:{i:0;a:3:{s:6:\"column\";s:7:\"ph.code\";s:9:\"condition\";s:2:\"eq\";s:5:\"value\";s:3:\"200\";}i:1;a:3:{s:6:\"column\";s:14:\"p.is_published\";s:9:\"condition\";s:2:\"eq\";s:5:\"value\";s:1:\"1\";}}','a:1:{i:0;a:2:{s:6:\"column\";s:11:\"ph.date_hit\";s:9:\"direction\";s:3:\"ASC\";}}','a:8:{i:0;s:35:\"mautic.page.graph.line.time.on.site\";i:1;s:27:\"mautic.page.graph.line.hits\";i:2;s:38:\"mautic.page.graph.pie.new.vs.returning\";i:3;s:31:\"mautic.page.graph.pie.languages\";i:4;s:34:\"mautic.page.graph.pie.time.on.site\";i:5;s:27:\"mautic.page.table.referrers\";i:6;s:30:\"mautic.page.table.most.visited\";i:7;s:37:\"mautic.page.table.most.visited.unique\";}','a:0:{}','a:0:{}','[]',0,NULL,NULL,NULL,NULL),(12,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Downloads of all Assets',NULL,1,'asset.downloads','a:7:{i:0;s:16:\"ad.date_download\";i:1;s:7:\"a.title\";i:2;s:12:\"i.ip_address\";i:3;s:11:\"l.firstname\";i:4;s:10:\"l.lastname\";i:5;s:7:\"l.email\";i:6;s:4:\"a.id\";}','a:1:{i:0;a:3:{s:6:\"column\";s:14:\"a.is_published\";s:9:\"condition\";s:2:\"eq\";s:5:\"value\";s:1:\"1\";}}','a:1:{i:0;a:2:{s:6:\"column\";s:16:\"ad.date_download\";s:9:\"direction\";s:3:\"ASC\";}}','a:4:{i:0;s:33:\"mautic.asset.graph.line.downloads\";i:1;s:31:\"mautic.asset.graph.pie.statuses\";i:2;s:34:\"mautic.asset.table.most.downloaded\";i:3;s:32:\"mautic.asset.table.top.referrers\";}','a:0:{}','a:0:{}','[]',0,NULL,NULL,NULL,NULL),(13,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Submissions of published Forms',NULL,1,'form.submissions','a:0:{}','a:1:{i:1;a:3:{s:6:\"column\";s:14:\"f.is_published\";s:9:\"condition\";s:2:\"eq\";s:5:\"value\";s:1:\"1\";}}','a:0:{}','a:3:{i:0;s:34:\"mautic.form.graph.line.submissions\";i:1;s:32:\"mautic.form.table.most.submitted\";i:2;s:31:\"mautic.form.table.top.referrers\";}','a:0:{}','a:0:{}','[]',0,NULL,NULL,NULL,NULL),(14,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'All Emails',NULL,1,'email.stats','a:5:{i:0;s:12:\"es.date_sent\";i:1;s:12:\"es.date_read\";i:2;s:9:\"e.subject\";i:3;s:16:\"es.email_address\";i:4;s:4:\"e.id\";}','a:1:{i:0;a:3:{s:6:\"column\";s:14:\"e.is_published\";s:9:\"condition\";s:2:\"eq\";s:5:\"value\";s:1:\"1\";}}','a:1:{i:0;a:2:{s:6:\"column\";s:12:\"es.date_sent\";s:9:\"direction\";s:3:\"ASC\";}}','a:6:{i:0;s:29:\"mautic.email.graph.line.stats\";i:1;s:42:\"mautic.email.graph.pie.ignored.read.failed\";i:2;s:35:\"mautic.email.table.most.emails.read\";i:3;s:35:\"mautic.email.table.most.emails.sent\";i:4;s:43:\"mautic.email.table.most.emails.read.percent\";i:5;s:37:\"mautic.email.table.most.emails.failed\";}','a:0:{}','a:0:{}','[]',0,NULL,NULL,NULL,NULL),(15,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Leads and Points',NULL,1,'lead.pointlog','a:7:{i:0;s:13:\"lp.date_added\";i:1;s:7:\"lp.type\";i:2;s:13:\"lp.event_name\";i:3;s:11:\"l.firstname\";i:4;s:10:\"l.lastname\";i:5;s:7:\"l.email\";i:6;s:8:\"lp.delta\";}','a:0:{}','a:1:{i:0;a:2:{s:6:\"column\";s:13:\"lp.date_added\";s:9:\"direction\";s:3:\"ASC\";}}','a:6:{i:0;s:29:\"mautic.lead.graph.line.points\";i:1;s:29:\"mautic.lead.table.most.points\";i:2;s:29:\"mautic.lead.table.top.actions\";i:3;s:28:\"mautic.lead.table.top.cities\";i:4;s:31:\"mautic.lead.table.top.countries\";i:5;s:28:\"mautic.lead.table.top.events\";}','a:0:{}','a:0:{}','[]',0,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reports_schedulers`
--

DROP TABLE IF EXISTS `reports_schedulers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports_schedulers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` int(10) unsigned NOT NULL,
  `schedule_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C74CA6B84BD2A4C0` (`report_id`),
  CONSTRAINT `FK_C74CA6B84BD2A4C0` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports_schedulers`
--

LOCK TABLES `reports_schedulers` WRITE;
/*!40000 ALTER TABLE `reports_schedulers` DISABLE KEYS */;
/*!40000 ALTER TABLE `reports_schedulers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL,
  `readable_permissions` longtext NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (4,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Administrator','Full system access',1,'N;'),(5,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Sales Team','Has access to sales',0,'a:2:{s:12:\"user:profile\";a:1:{i:0;s:8:\"editname\";}s:10:\"lead:leads\";a:1:{i:0;s:4:\"full\";}}');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saml_id_entry`
--

DROP TABLE IF EXISTS `saml_id_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saml_id_entry` (
  `id` varchar(191) NOT NULL,
  `entity_id` varchar(191) NOT NULL,
  `expiryTimestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saml_id_entry`
--

LOCK TABLES `saml_id_entry` WRITE;
/*!40000 ALTER TABLE `saml_id_entry` DISABLE KEYS */;
/*!40000 ALTER TABLE `saml_id_entry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_message_list_xref`
--

DROP TABLE IF EXISTS `sms_message_list_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_message_list_xref` (
  `sms_id` int(10) unsigned NOT NULL,
  `leadlist_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`sms_id`,`leadlist_id`),
  KEY `IDX_B032FC2EB9FC8874` (`leadlist_id`),
  CONSTRAINT `FK_B032FC2EB9FC8874` FOREIGN KEY (`leadlist_id`) REFERENCES `lead_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_B032FC2EBD5C7E60` FOREIGN KEY (`sms_id`) REFERENCES `sms_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_message_list_xref`
--

LOCK TABLES `sms_message_list_xref` WRITE;
/*!40000 ALTER TABLE `sms_message_list_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_message_list_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_message_stats`
--

DROP TABLE IF EXISTS `sms_message_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_message_stats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sms_id` int(10) unsigned DEFAULT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `list_id` int(10) unsigned DEFAULT NULL,
  `ip_id` int(10) unsigned DEFAULT NULL,
  `date_sent` datetime NOT NULL,
  `is_failed` tinyint(1) DEFAULT NULL,
  `tracking_hash` varchar(191) DEFAULT NULL,
  `source` varchar(191) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `tokens` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `details` longtext NOT NULL COMMENT '(DC2Type:json)',
  PRIMARY KEY (`id`),
  KEY `IDX_FE1BAE9BD5C7E60` (`sms_id`),
  KEY `IDX_FE1BAE955458D` (`lead_id`),
  KEY `IDX_FE1BAE93DAE168B` (`list_id`),
  KEY `IDX_FE1BAE9A03F5E9F` (`ip_id`),
  KEY `stat_sms_search` (`sms_id`,`lead_id`),
  KEY `stat_sms_hash_search` (`tracking_hash`),
  KEY `stat_sms_source_search` (`source`,`source_id`),
  KEY `stat_sms_failed_search` (`is_failed`),
  CONSTRAINT `FK_FE1BAE93DAE168B` FOREIGN KEY (`list_id`) REFERENCES `lead_lists` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_FE1BAE955458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_FE1BAE9A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `ip_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_FE1BAE9BD5C7E60` FOREIGN KEY (`sms_id`) REFERENCES `sms_messages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_message_stats`
--

LOCK TABLES `sms_message_stats` WRITE;
/*!40000 ALTER TABLE `sms_message_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_message_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_messages`
--

DROP TABLE IF EXISTS `sms_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `lang` varchar(191) NOT NULL,
  `message` longtext NOT NULL,
  `sms_type` longtext DEFAULT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `sent_count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BDF43F9712469DE2` (`category_id`),
  CONSTRAINT `FK_BDF43F9712469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_messages`
--

LOCK TABLES `sms_messages` WRITE;
/*!40000 ALTER TABLE `sms_messages` DISABLE KEYS */;
INSERT INTO `sms_messages` VALUES (2,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'SMS Test',NULL,'en','hello','template',NULL,NULL,0);
/*!40000 ALTER TABLE `sms_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stage_lead_action_log`
--

DROP TABLE IF EXISTS `stage_lead_action_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stage_lead_action_log` (
  `stage_id` int(10) unsigned NOT NULL,
  `lead_id` bigint(20) unsigned NOT NULL,
  `ip_id` int(10) unsigned DEFAULT NULL,
  `date_fired` datetime NOT NULL,
  PRIMARY KEY (`stage_id`,`lead_id`),
  KEY `IDX_A506AFBE55458D` (`lead_id`),
  KEY `IDX_A506AFBEA03F5E9F` (`ip_id`),
  CONSTRAINT `FK_A506AFBE2298D193` FOREIGN KEY (`stage_id`) REFERENCES `stages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_A506AFBE55458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_A506AFBEA03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `ip_addresses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stage_lead_action_log`
--

LOCK TABLES `stage_lead_action_log` WRITE;
/*!40000 ALTER TABLE `stage_lead_action_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `stage_lead_action_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stages`
--

DROP TABLE IF EXISTS `stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `weight` int(11) NOT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2FA26A6412469DE2` (`category_id`),
  CONSTRAINT `FK_2FA26A6412469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stages`
--

LOCK TABLES `stages` WRITE;
/*!40000 ALTER TABLE `stages` DISABLE KEYS */;
/*!40000 ALTER TABLE `stages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sync_object_field_change_report`
--

DROP TABLE IF EXISTS `sync_object_field_change_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sync_object_field_change_report` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `integration` varchar(191) NOT NULL,
  `object_id` bigint(20) unsigned NOT NULL,
  `object_type` varchar(191) NOT NULL,
  `modified_at` datetime NOT NULL,
  `column_name` varchar(191) NOT NULL,
  `column_type` varchar(191) NOT NULL,
  `column_value` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `object_composite_key` (`object_type`,`object_id`,`column_name`),
  KEY `integration_object_composite_key` (`integration`,`object_type`,`object_id`,`column_name`),
  KEY `integration_object_type_modification_composite_key` (`integration`,`object_type`,`modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sync_object_field_change_report`
--

LOCK TABLES `sync_object_field_change_report` WRITE;
/*!40000 ALTER TABLE `sync_object_field_change_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `sync_object_field_change_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sync_object_mapping`
--

DROP TABLE IF EXISTS `sync_object_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sync_object_mapping` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date_created` datetime NOT NULL,
  `integration` varchar(191) NOT NULL,
  `internal_object_name` varchar(191) NOT NULL,
  `internal_object_id` bigint(20) unsigned NOT NULL,
  `integration_object_name` varchar(191) NOT NULL,
  `integration_object_id` varchar(191) NOT NULL,
  `last_sync_date` datetime NOT NULL,
  `internal_storage` longtext NOT NULL COMMENT '(DC2Type:json)',
  `is_deleted` tinyint(1) NOT NULL,
  `integration_reference_id` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `integration_object` (`integration`,`integration_object_name`,`integration_object_id`,`integration_reference_id`),
  KEY `integration_reference` (`integration`,`integration_object_name`,`integration_reference_id`,`integration_object_id`),
  KEY `integration_integration_object_name_last_sync_date` (`integration`,`internal_object_name`,`last_sync_date`),
  KEY `integration_last_sync_date` (`integration`,`last_sync_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sync_object_mapping`
--

LOCK TABLES `sync_object_mapping` WRITE;
/*!40000 ALTER TABLE `sync_object_mapping` DISABLE KEYS */;
/*!40000 ALTER TABLE `sync_object_mapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tweet_stats`
--

DROP TABLE IF EXISTS `tweet_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tweet_stats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tweet_id` int(10) unsigned DEFAULT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `twitter_tweet_id` varchar(191) DEFAULT NULL,
  `handle` varchar(191) NOT NULL,
  `date_sent` datetime DEFAULT NULL,
  `is_failed` tinyint(1) DEFAULT NULL,
  `retry_count` int(11) DEFAULT NULL,
  `source` varchar(191) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `favorite_count` int(11) DEFAULT NULL,
  `retweet_count` int(11) DEFAULT NULL,
  `response_details` longtext DEFAULT NULL COMMENT '(DC2Type:json)',
  PRIMARY KEY (`id`),
  KEY `IDX_CB8CBAE51041E39B` (`tweet_id`),
  KEY `IDX_CB8CBAE555458D` (`lead_id`),
  KEY `stat_tweet_search` (`tweet_id`,`lead_id`),
  KEY `stat_tweet_search2` (`lead_id`,`tweet_id`),
  KEY `stat_tweet_failed_search` (`is_failed`),
  KEY `stat_tweet_source_search` (`source`,`source_id`),
  KEY `favorite_count_index` (`favorite_count`),
  KEY `retweet_count_index` (`retweet_count`),
  KEY `tweet_date_sent` (`date_sent`),
  KEY `twitter_tweet_id_index` (`twitter_tweet_id`),
  CONSTRAINT `FK_CB8CBAE51041E39B` FOREIGN KEY (`tweet_id`) REFERENCES `tweets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_CB8CBAE555458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tweet_stats`
--

LOCK TABLES `tweet_stats` WRITE;
/*!40000 ALTER TABLE `tweet_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `tweet_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tweets`
--

DROP TABLE IF EXISTS `tweets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tweets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `page_id` int(10) unsigned DEFAULT NULL,
  `asset_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `media_id` varchar(191) DEFAULT NULL,
  `media_path` varchar(191) DEFAULT NULL,
  `text` varchar(191) NOT NULL,
  `sent_count` int(11) DEFAULT NULL,
  `favorite_count` int(11) DEFAULT NULL,
  `retweet_count` int(11) DEFAULT NULL,
  `lang` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_AA38402512469DE2` (`category_id`),
  KEY `IDX_AA384025C4663E4` (`page_id`),
  KEY `IDX_AA3840255DA1941` (`asset_id`),
  KEY `sent_count_index` (`sent_count`),
  KEY `favorite_count_index` (`favorite_count`),
  KEY `retweet_count_index` (`retweet_count`),
  CONSTRAINT `FK_AA38402512469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_AA3840255DA1941` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_AA384025C4663E4` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tweets`
--

LOCK TABLES `tweets` WRITE;
/*!40000 ALTER TABLE `tweets` DISABLE KEYS */;
/*!40000 ALTER TABLE `tweets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_tokens`
--

DROP TABLE IF EXISTS `user_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `authorizator` varchar(32) NOT NULL,
  `secret` varchar(120) NOT NULL,
  `expiration` datetime DEFAULT NULL,
  `one_time_only` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_CF080AB35CA2E8E5` (`secret`),
  KEY `IDX_CF080AB3A76ED395` (`user_id`),
  CONSTRAINT `FK_CF080AB3A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_tokens`
--

LOCK TABLES `user_tokens` WRITE;
/*!40000 ALTER TABLE `user_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_tokens` ENABLE KEYS */;
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
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `username` varchar(191) NOT NULL,
  `password` varchar(64) NOT NULL,
  `first_name` varchar(191) NOT NULL,
  `last_name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `position` varchar(191) DEFAULT NULL,
  `timezone` varchar(191) DEFAULT NULL,
  `locale` varchar(191) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_active` datetime DEFAULT NULL,
  `preferences` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `signature` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1483A5E9F85E0677` (`username`),
  UNIQUE KEY `UNIQ_1483A5E9E7927C74` (`email`),
  KEY `IDX_1483A5E9D60322AC` (`role_id`),
  CONSTRAINT `FK_1483A5E9D60322AC` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (4,4,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'admin','$2y$13$BbKq8xE4xCLYc29HnG.NxOpNeWnPF5iR9CAfBw.bCoU2HUfonV6Yq','Admin','User','admin@yoursite.com',NULL,'','',NULL,NULL,'a:0:{}',NULL),(5,5,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'sales','$2y$13$W9iXI59L2Q5RAq1ZAayJ/.193Q4bhUWKW8UBs0MWObE7MiTHo5J0q','Sales','User','sales@yoursite.com',NULL,'','',NULL,NULL,'a:0:{}',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video_hits`
--

DROP TABLE IF EXISTS `video_hits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `video_hits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `ip_id` int(10) unsigned DEFAULT NULL,
  `date_hit` datetime NOT NULL,
  `date_left` datetime DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `region` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `isp` varchar(191) DEFAULT NULL,
  `organization` varchar(191) DEFAULT NULL,
  `code` int(11) NOT NULL,
  `referer` longtext DEFAULT NULL,
  `url` longtext DEFAULT NULL,
  `user_agent` longtext DEFAULT NULL,
  `remote_host` varchar(191) DEFAULT NULL,
  `guid` varchar(191) NOT NULL,
  `page_language` varchar(191) DEFAULT NULL,
  `browser_languages` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `channel` varchar(191) DEFAULT NULL,
  `channel_id` int(11) DEFAULT NULL,
  `time_watched` int(11) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `query` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_1D1831F755458D` (`lead_id`),
  KEY `IDX_1D1831F7A03F5E9F` (`ip_id`),
  KEY `video_date_hit` (`date_hit`),
  KEY `video_channel_search` (`channel`,`channel_id`),
  KEY `video_guid_lead_search` (`guid`,`lead_id`),
  CONSTRAINT `FK_1D1831F755458D` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_1D1831F7A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `ip_addresses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video_hits`
--

LOCK TABLES `video_hits` WRITE;
/*!40000 ALTER TABLE `video_hits` DISABLE KEYS */;
/*!40000 ALTER TABLE `video_hits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhook_events`
--

DROP TABLE IF EXISTS `webhook_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `webhook_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `webhook_id` int(10) unsigned NOT NULL,
  `event_type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7AD44E375C9BA60B` (`webhook_id`),
  CONSTRAINT `FK_7AD44E375C9BA60B` FOREIGN KEY (`webhook_id`) REFERENCES `webhooks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhook_events`
--

LOCK TABLES `webhook_events` WRITE;
/*!40000 ALTER TABLE `webhook_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhook_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhook_logs`
--

DROP TABLE IF EXISTS `webhook_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `webhook_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `webhook_id` int(10) unsigned NOT NULL,
  `status_code` varchar(50) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `note` varchar(191) DEFAULT NULL,
  `runtime` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_45A353475C9BA60B` (`webhook_id`),
  KEY `webhook_id_date_added` (`webhook_id`,`date_added`),
  CONSTRAINT `FK_45A353475C9BA60B` FOREIGN KEY (`webhook_id`) REFERENCES `webhooks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhook_logs`
--

LOCK TABLES `webhook_logs` WRITE;
/*!40000 ALTER TABLE `webhook_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhook_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhook_queue`
--

DROP TABLE IF EXISTS `webhook_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `webhook_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `webhook_id` int(10) unsigned NOT NULL,
  `event_id` int(10) unsigned NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `payload` longtext DEFAULT NULL,
  `payload_compressed` mediumblob DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F52D9A1A5C9BA60B` (`webhook_id`),
  KEY `IDX_F52D9A1A71F7E88B` (`event_id`),
  CONSTRAINT `FK_F52D9A1A5C9BA60B` FOREIGN KEY (`webhook_id`) REFERENCES `webhooks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_F52D9A1A71F7E88B` FOREIGN KEY (`event_id`) REFERENCES `webhook_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhook_queue`
--

LOCK TABLES `webhook_queue` WRITE;
/*!40000 ALTER TABLE `webhook_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhook_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhooks`
--

DROP TABLE IF EXISTS `webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `webhooks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `webhook_url` longtext NOT NULL,
  `secret` varchar(191) NOT NULL,
  `events_orderby_dir` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_998C4FDD12469DE2` (`category_id`),
  CONSTRAINT `FK_998C4FDD12469DE2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhooks`
--

LOCK TABLES `webhooks` WRITE;
/*!40000 ALTER TABLE `webhooks` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhooks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `widgets`
--

DROP TABLE IF EXISTS `widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `widgets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(191) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(191) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `type` varchar(191) NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `cache_timeout` int(11) DEFAULT NULL,
  `ordering` int(11) DEFAULT NULL,
  `params` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `widgets`
--

LOCK TABLES `widgets` WRITE;
/*!40000 ALTER TABLE `widgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `widgets` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-07-17 23:03:33
