-- MySQL dump 10.13  Distrib 5.7.17, for Win64 (x86_64)
--
-- Host: localhost    Database: automated
-- ------------------------------------------------------
-- Server version	5.7.14

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `mautic_asset_downloads`
--

DROP TABLE IF EXISTS `mautic_asset_downloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_asset_downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) DEFAULT NULL,
  `ip_id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `email_id` int(11) DEFAULT NULL,
  `date_download` datetime NOT NULL,
  `code` int(11) NOT NULL,
  `referer` longtext COLLATE utf8_unicode_ci,
  `tracking_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_99B121615DA1941` (`asset_id`),
  KEY `IDX_99B12161A03F5E9F` (`ip_id`),
  KEY `IDX_99B1216155458D` (`lead_id`),
  KEY `IDX_99B12161A832C1C9` (`email_id`),
  KEY `mautic_download_tracking_search` (`tracking_id`),
  KEY `mautic_download_source_search` (`source`,`source_id`),
  KEY `mautic_asset_date_download` (`date_download`),
  CONSTRAINT `FK_99B1216155458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_99B121615DA1941` FOREIGN KEY (`asset_id`) REFERENCES `mautic_assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_99B12161A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `mautic_ip_addresses` (`id`),
  CONSTRAINT `FK_99B12161A832C1C9` FOREIGN KEY (`email_id`) REFERENCES `mautic_emails` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_asset_downloads`
--

LOCK TABLES `mautic_asset_downloads` WRITE;
/*!40000 ALTER TABLE `mautic_asset_downloads` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_asset_downloads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_assets`
--

DROP TABLE IF EXISTS `mautic_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `storage_location` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `remote_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `original_file_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lang` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `download_count` int(11) NOT NULL,
  `unique_download_count` int(11) NOT NULL,
  `revision` int(11) NOT NULL,
  `extension` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mime` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_36A1D24212469DE2` (`category_id`),
  KEY `mautic_asset_alias_search` (`alias`),
  CONSTRAINT `FK_36A1D24212469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_assets`
--

LOCK TABLES `mautic_assets` WRITE;
/*!40000 ALTER TABLE `mautic_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_audit_log`
--

DROP TABLE IF EXISTS `mautic_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `bundle` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `object` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `object_id` int(11) NOT NULL,
  `action` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `details` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `date_added` datetime NOT NULL,
  `ip_address` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mautic_object_search` (`object`,`object_id`),
  KEY `mautic_timeline_search` (`bundle`,`object`,`action`,`object_id`),
  KEY `mautic_date_added_index` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_audit_log`
--

LOCK TABLES `mautic_audit_log` WRITE;
/*!40000 ALTER TABLE `mautic_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_cache_items`
--

DROP TABLE IF EXISTS `mautic_cache_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_cache_items` (
  `item_id` varbinary(255) NOT NULL,
  `item_data` longblob NOT NULL,
  `item_lifetime` int(10) unsigned DEFAULT NULL,
  `item_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_cache_items`
--

LOCK TABLES `mautic_cache_items` WRITE;
/*!40000 ALTER TABLE `mautic_cache_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_cache_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_campaign_events`
--

DROP TABLE IF EXISTS `mautic_campaign_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_campaign_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `event_type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `event_order` int(11) NOT NULL,
  `properties` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  `trigger_date` datetime DEFAULT NULL,
  `trigger_interval` int(11) DEFAULT NULL,
  `trigger_interval_unit` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `trigger_mode` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `decision_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `temp_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `channel` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `channel_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B13C4309F639F774` (`campaign_id`),
  KEY `IDX_B13C4309727ACA70` (`parent_id`),
  KEY `mautic_campaign_event_search` (`type`,`event_type`),
  KEY `mautic_campaign_event_type` (`event_type`),
  KEY `mautic_campaign_event_channel` (`channel`,`channel_id`),
  CONSTRAINT `FK_B13C4309727ACA70` FOREIGN KEY (`parent_id`) REFERENCES `mautic_campaign_events` (`id`),
  CONSTRAINT `FK_B13C4309F639F774` FOREIGN KEY (`campaign_id`) REFERENCES `mautic_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_campaign_events`
--

LOCK TABLES `mautic_campaign_events` WRITE;
/*!40000 ALTER TABLE `mautic_campaign_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_campaign_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_campaign_form_xref`
--

DROP TABLE IF EXISTS `mautic_campaign_form_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_campaign_form_xref` (
  `campaign_id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  PRIMARY KEY (`campaign_id`,`form_id`),
  KEY `IDX_F0013AE3F639F774` (`campaign_id`),
  KEY `IDX_F0013AE35FF69B7D` (`form_id`),
  CONSTRAINT `FK_F0013AE35FF69B7D` FOREIGN KEY (`form_id`) REFERENCES `mautic_forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_F0013AE3F639F774` FOREIGN KEY (`campaign_id`) REFERENCES `mautic_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_campaign_form_xref`
--

LOCK TABLES `mautic_campaign_form_xref` WRITE;
/*!40000 ALTER TABLE `mautic_campaign_form_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_campaign_form_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_campaign_lead_event_failed_log`
--

DROP TABLE IF EXISTS `mautic_campaign_lead_event_failed_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_campaign_lead_event_failed_log` (
  `log_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `reason` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`log_id`),
  KEY `mautic_campaign_event_failed_date` (`date_added`),
  CONSTRAINT `FK_19CB6506EA675D86` FOREIGN KEY (`log_id`) REFERENCES `mautic_campaign_lead_event_log` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_campaign_lead_event_failed_log`
--

LOCK TABLES `mautic_campaign_lead_event_failed_log` WRITE;
/*!40000 ALTER TABLE `mautic_campaign_lead_event_failed_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_campaign_lead_event_failed_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_campaign_lead_event_log`
--

DROP TABLE IF EXISTS `mautic_campaign_lead_event_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_campaign_lead_event_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `ip_id` int(11) DEFAULT NULL,
  `rotation` int(11) NOT NULL,
  `date_triggered` datetime DEFAULT NULL,
  `is_scheduled` tinyint(1) NOT NULL,
  `trigger_date` datetime DEFAULT NULL,
  `system_triggered` tinyint(1) NOT NULL,
  `metadata` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `channel` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `channel_id` int(11) DEFAULT NULL,
  `non_action_path_taken` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mautic_campaign_rotation` (`event_id`,`lead_id`,`rotation`),
  KEY `IDX_11C9A5F471F7E88B` (`event_id`),
  KEY `IDX_11C9A5F455458D` (`lead_id`),
  KEY `IDX_11C9A5F4F639F774` (`campaign_id`),
  KEY `IDX_11C9A5F4A03F5E9F` (`ip_id`),
  KEY `mautic_campaign_event_upcoming_search` (`is_scheduled`,`lead_id`),
  KEY `mautic_campaign_date_triggered` (`date_triggered`),
  KEY `mautic_campaign_leads` (`lead_id`,`campaign_id`,`rotation`),
  KEY `mautic_campaign_log_channel` (`channel`,`channel_id`,`lead_id`),
  CONSTRAINT `FK_11C9A5F455458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_11C9A5F471F7E88B` FOREIGN KEY (`event_id`) REFERENCES `mautic_campaign_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_11C9A5F4A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `mautic_ip_addresses` (`id`),
  CONSTRAINT `FK_11C9A5F4F639F774` FOREIGN KEY (`campaign_id`) REFERENCES `mautic_campaigns` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_campaign_lead_event_log`
--

LOCK TABLES `mautic_campaign_lead_event_log` WRITE;
/*!40000 ALTER TABLE `mautic_campaign_lead_event_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_campaign_lead_event_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_campaign_leadlist_xref`
--

DROP TABLE IF EXISTS `mautic_campaign_leadlist_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_campaign_leadlist_xref` (
  `campaign_id` int(11) NOT NULL,
  `leadlist_id` int(11) NOT NULL,
  PRIMARY KEY (`campaign_id`,`leadlist_id`),
  KEY `IDX_5379378BF639F774` (`campaign_id`),
  KEY `IDX_5379378BB9FC8874` (`leadlist_id`),
  CONSTRAINT `FK_5379378BB9FC8874` FOREIGN KEY (`leadlist_id`) REFERENCES `mautic_lead_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_5379378BF639F774` FOREIGN KEY (`campaign_id`) REFERENCES `mautic_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_campaign_leadlist_xref`
--

LOCK TABLES `mautic_campaign_leadlist_xref` WRITE;
/*!40000 ALTER TABLE `mautic_campaign_leadlist_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_campaign_leadlist_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_campaign_leads`
--

DROP TABLE IF EXISTS `mautic_campaign_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_campaign_leads` (
  `campaign_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `manually_removed` tinyint(1) NOT NULL,
  `manually_added` tinyint(1) NOT NULL,
  `date_last_exited` datetime DEFAULT NULL,
  `rotation` int(11) NOT NULL,
  PRIMARY KEY (`campaign_id`,`lead_id`),
  KEY `IDX_14FE129BF639F774` (`campaign_id`),
  KEY `IDX_14FE129B55458D` (`lead_id`),
  KEY `mautic_campaign_leads_date_added` (`date_added`),
  KEY `mautic_campaign_leads_date_exited` (`date_last_exited`),
  KEY `mautic_campaign_leads` (`campaign_id`,`manually_removed`,`lead_id`,`rotation`),
  CONSTRAINT `FK_14FE129B55458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_14FE129BF639F774` FOREIGN KEY (`campaign_id`) REFERENCES `mautic_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_campaign_leads`
--

LOCK TABLES `mautic_campaign_leads` WRITE;
/*!40000 ALTER TABLE `mautic_campaign_leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_campaign_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_campaigns`
--

DROP TABLE IF EXISTS `mautic_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `canvas_settings` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_25CCA0112469DE2` (`category_id`),
  CONSTRAINT `FK_25CCA0112469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_campaigns`
--

LOCK TABLES `mautic_campaigns` WRITE;
/*!40000 ALTER TABLE `mautic_campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_categories`
--

DROP TABLE IF EXISTS `mautic_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `color` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bundle` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mautic_category_alias_search` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_categories`
--

LOCK TABLES `mautic_categories` WRITE;
/*!40000 ALTER TABLE `mautic_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_channel_url_trackables`
--

DROP TABLE IF EXISTS `mautic_channel_url_trackables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_channel_url_trackables` (
  `redirect_id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `channel` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `hits` int(11) NOT NULL,
  `unique_hits` int(11) NOT NULL,
  PRIMARY KEY (`redirect_id`,`channel_id`),
  KEY `IDX_187896B8B42D874D` (`redirect_id`),
  KEY `mautic_channel_url_trackable_search` (`channel`,`channel_id`),
  CONSTRAINT `FK_187896B8B42D874D` FOREIGN KEY (`redirect_id`) REFERENCES `mautic_page_redirects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_channel_url_trackables`
--

LOCK TABLES `mautic_channel_url_trackables` WRITE;
/*!40000 ALTER TABLE `mautic_channel_url_trackables` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_channel_url_trackables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_companies`
--

DROP TABLE IF EXISTS `mautic_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `social_cache` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `score` int(11) DEFAULT NULL,
  `companyemail` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `companyaddress1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `companyaddress2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `companyphone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `companycity` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `companystate` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `companyzipcode` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `companycountry` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `companyname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `companywebsite` longtext COLLATE utf8_unicode_ci,
  `companyindustry` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `companydescription` longtext COLLATE utf8_unicode_ci,
  `companynumber_of_employees` double DEFAULT NULL,
  `companyfax` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `companyannual_revenue` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_636B144B7E3C61F9` (`owner_id`),
  KEY `mautic_companyaddress1_search` (`companyaddress1`),
  KEY `mautic_companyaddress2_search` (`companyaddress2`),
  KEY `mautic_companyemail_search` (`companyemail`),
  KEY `mautic_companyphone_search` (`companyphone`),
  KEY `mautic_companycity_search` (`companycity`),
  KEY `mautic_companystate_search` (`companystate`),
  KEY `mautic_companyzipcode_search` (`companyzipcode`),
  KEY `mautic_companycountry_search` (`companycountry`),
  KEY `mautic_companyname_search` (`companyname`),
  KEY `mautic_companynumber_of_employees_search` (`companynumber_of_employees`),
  KEY `mautic_companyfax_search` (`companyfax`),
  KEY `mautic_companyannual_revenue_search` (`companyannual_revenue`),
  KEY `mautic_companyindustry_search` (`companyindustry`),
  KEY `mautic_company_filter` (`companyname`,`companyemail`),
  KEY `mautic_company_match` (`companyname`,`companycity`,`companycountry`,`companystate`),
  CONSTRAINT `FK_636B144B7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `mautic_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_companies`
--

LOCK TABLES `mautic_companies` WRITE;
/*!40000 ALTER TABLE `mautic_companies` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_companies_leads`
--

DROP TABLE IF EXISTS `mautic_companies_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_companies_leads` (
  `company_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `is_primary` tinyint(1) DEFAULT NULL,
  `manually_removed` tinyint(1) NOT NULL,
  `manually_added` tinyint(1) NOT NULL,
  PRIMARY KEY (`company_id`,`lead_id`),
  KEY `IDX_CBE16758979B1AD6` (`company_id`),
  KEY `IDX_CBE1675855458D` (`lead_id`),
  CONSTRAINT `FK_CBE1675855458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_CBE16758979B1AD6` FOREIGN KEY (`company_id`) REFERENCES `mautic_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_companies_leads`
--

LOCK TABLES `mautic_companies_leads` WRITE;
/*!40000 ALTER TABLE `mautic_companies_leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_companies_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_contact_merge_records`
--

DROP TABLE IF EXISTS `mautic_contact_merge_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_contact_merge_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `merged_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F8BDA754E7A1254A` (`contact_id`),
  KEY `mautic_contact_merge_date_added` (`date_added`),
  KEY `mautic_contact_merge_ids` (`merged_id`),
  CONSTRAINT `FK_F8BDA754E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_contact_merge_records`
--

LOCK TABLES `mautic_contact_merge_records` WRITE;
/*!40000 ALTER TABLE `mautic_contact_merge_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_contact_merge_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_dynamic_content`
--

DROP TABLE IF EXISTS `mautic_dynamic_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_dynamic_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `translation_parent_id` int(11) DEFAULT NULL,
  `variant_parent_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `sent_count` int(11) NOT NULL,
  `content` longtext COLLATE utf8_unicode_ci,
  `lang` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `variant_settings` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `variant_start_date` datetime DEFAULT NULL,
  `filters` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `is_campaign_based` tinyint(1) NOT NULL DEFAULT '1',
  `slot_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1F41B35C12469DE2` (`category_id`),
  KEY `IDX_1F41B35C9091A2FB` (`translation_parent_id`),
  KEY `IDX_1F41B35C91861123` (`variant_parent_id`),
  KEY `mautic_is_campaign_based_index` (`is_campaign_based`),
  KEY `mautic_slot_name_index` (`slot_name`),
  CONSTRAINT `FK_1F41B35C12469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_1F41B35C9091A2FB` FOREIGN KEY (`translation_parent_id`) REFERENCES `mautic_dynamic_content` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1F41B35C91861123` FOREIGN KEY (`variant_parent_id`) REFERENCES `mautic_dynamic_content` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_dynamic_content`
--

LOCK TABLES `mautic_dynamic_content` WRITE;
/*!40000 ALTER TABLE `mautic_dynamic_content` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_dynamic_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_dynamic_content_lead_data`
--

DROP TABLE IF EXISTS `mautic_dynamic_content_lead_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_dynamic_content_lead_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `dynamic_content_id` int(11) DEFAULT NULL,
  `date_added` datetime DEFAULT NULL,
  `slot` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A7F9A36E55458D` (`lead_id`),
  KEY `IDX_A7F9A36ED9D0CD7` (`dynamic_content_id`),
  CONSTRAINT `FK_A7F9A36E55458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_A7F9A36ED9D0CD7` FOREIGN KEY (`dynamic_content_id`) REFERENCES `mautic_dynamic_content` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_dynamic_content_lead_data`
--

LOCK TABLES `mautic_dynamic_content_lead_data` WRITE;
/*!40000 ALTER TABLE `mautic_dynamic_content_lead_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_dynamic_content_lead_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_dynamic_content_stats`
--

DROP TABLE IF EXISTS `mautic_dynamic_content_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_dynamic_content_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dynamic_content_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `date_sent` datetime NOT NULL,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `tokens` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `sent_count` int(11) DEFAULT NULL,
  `last_sent` datetime DEFAULT NULL,
  `sent_details` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_C586EA6BD9D0CD7` (`dynamic_content_id`),
  KEY `IDX_C586EA6B55458D` (`lead_id`),
  KEY `mautic_stat_dynamic_content_search` (`dynamic_content_id`,`lead_id`),
  KEY `mautic_stat_dynamic_content_source_search` (`source`,`source_id`),
  CONSTRAINT `FK_C586EA6B55458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_C586EA6BD9D0CD7` FOREIGN KEY (`dynamic_content_id`) REFERENCES `mautic_dynamic_content` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_dynamic_content_stats`
--

LOCK TABLES `mautic_dynamic_content_stats` WRITE;
/*!40000 ALTER TABLE `mautic_dynamic_content_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_dynamic_content_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_email_assets_xref`
--

DROP TABLE IF EXISTS `mautic_email_assets_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_email_assets_xref` (
  `email_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  PRIMARY KEY (`email_id`,`asset_id`),
  KEY `IDX_39CFAB07A832C1C9` (`email_id`),
  KEY `IDX_39CFAB075DA1941` (`asset_id`),
  CONSTRAINT `FK_39CFAB075DA1941` FOREIGN KEY (`asset_id`) REFERENCES `mautic_assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_39CFAB07A832C1C9` FOREIGN KEY (`email_id`) REFERENCES `mautic_emails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_email_assets_xref`
--

LOCK TABLES `mautic_email_assets_xref` WRITE;
/*!40000 ALTER TABLE `mautic_email_assets_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_email_assets_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_email_copies`
--

DROP TABLE IF EXISTS `mautic_email_copies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_email_copies` (
  `id` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `date_created` datetime NOT NULL,
  `body` longtext COLLATE utf8_unicode_ci,
  `subject` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_email_copies`
--

LOCK TABLES `mautic_email_copies` WRITE;
/*!40000 ALTER TABLE `mautic_email_copies` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_email_copies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_email_list_xref`
--

DROP TABLE IF EXISTS `mautic_email_list_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_email_list_xref` (
  `email_id` int(11) NOT NULL,
  `leadlist_id` int(11) NOT NULL,
  PRIMARY KEY (`email_id`,`leadlist_id`),
  KEY `IDX_11DC9DF2A832C1C9` (`email_id`),
  KEY `IDX_11DC9DF2B9FC8874` (`leadlist_id`),
  CONSTRAINT `FK_11DC9DF2A832C1C9` FOREIGN KEY (`email_id`) REFERENCES `mautic_emails` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_11DC9DF2B9FC8874` FOREIGN KEY (`leadlist_id`) REFERENCES `mautic_lead_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_email_list_xref`
--

LOCK TABLES `mautic_email_list_xref` WRITE;
/*!40000 ALTER TABLE `mautic_email_list_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_email_list_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_email_stat_replies`
--

DROP TABLE IF EXISTS `mautic_email_stat_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_email_stat_replies` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `stat_id` int(11) NOT NULL,
  `date_replied` datetime NOT NULL,
  `message_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D1A064B19502F0B` (`stat_id`),
  KEY `mautic_email_replies` (`stat_id`,`message_id`),
  KEY `mautic_date_email_replied` (`date_replied`),
  CONSTRAINT `FK_D1A064B19502F0B` FOREIGN KEY (`stat_id`) REFERENCES `mautic_email_stats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_email_stat_replies`
--

LOCK TABLES `mautic_email_stat_replies` WRITE;
/*!40000 ALTER TABLE `mautic_email_stat_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_email_stat_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_email_stats`
--

DROP TABLE IF EXISTS `mautic_email_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_email_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `list_id` int(11) DEFAULT NULL,
  `ip_id` int(11) DEFAULT NULL,
  `copy_id` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date_sent` datetime NOT NULL,
  `is_read` tinyint(1) NOT NULL,
  `is_failed` tinyint(1) NOT NULL,
  `viewed_in_browser` tinyint(1) NOT NULL,
  `date_read` datetime DEFAULT NULL,
  `tracking_hash` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `retry_count` int(11) DEFAULT NULL,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `tokens` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `open_count` int(11) DEFAULT NULL,
  `last_opened` datetime DEFAULT NULL,
  `open_details` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_D0F71136A832C1C9` (`email_id`),
  KEY `IDX_D0F7113655458D` (`lead_id`),
  KEY `IDX_D0F711363DAE168B` (`list_id`),
  KEY `IDX_D0F71136A03F5E9F` (`ip_id`),
  KEY `IDX_D0F71136A8752772` (`copy_id`),
  KEY `mautic_stat_email_search` (`email_id`,`lead_id`),
  KEY `mautic_stat_email_search2` (`lead_id`,`email_id`),
  KEY `mautic_stat_email_failed_search` (`is_failed`),
  KEY `mautic_stat_email_read_search` (`is_read`),
  KEY `mautic_stat_email_hash_search` (`tracking_hash`),
  KEY `mautic_stat_email_source_search` (`source`,`source_id`),
  KEY `mautic_email_date_sent` (`date_sent`),
  KEY `mautic_email_date_read` (`date_read`),
  CONSTRAINT `FK_D0F711363DAE168B` FOREIGN KEY (`list_id`) REFERENCES `mautic_lead_lists` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_D0F7113655458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_D0F71136A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `mautic_ip_addresses` (`id`),
  CONSTRAINT `FK_D0F71136A832C1C9` FOREIGN KEY (`email_id`) REFERENCES `mautic_emails` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_D0F71136A8752772` FOREIGN KEY (`copy_id`) REFERENCES `mautic_email_copies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_email_stats`
--

LOCK TABLES `mautic_email_stats` WRITE;
/*!40000 ALTER TABLE `mautic_email_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_email_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_email_stats_devices`
--

DROP TABLE IF EXISTS `mautic_email_stats_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_email_stats_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` int(11) DEFAULT NULL,
  `stat_id` int(11) DEFAULT NULL,
  `ip_id` int(11) DEFAULT NULL,
  `date_opened` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6626349F94A4C7D4` (`device_id`),
  KEY `IDX_6626349F9502F0B` (`stat_id`),
  KEY `IDX_6626349FA03F5E9F` (`ip_id`),
  KEY `mautic_date_opened_search` (`date_opened`),
  CONSTRAINT `FK_6626349F94A4C7D4` FOREIGN KEY (`device_id`) REFERENCES `mautic_lead_devices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6626349F9502F0B` FOREIGN KEY (`stat_id`) REFERENCES `mautic_email_stats` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6626349FA03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `mautic_ip_addresses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_email_stats_devices`
--

LOCK TABLES `mautic_email_stats_devices` WRITE;
/*!40000 ALTER TABLE `mautic_email_stats_devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_email_stats_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_emails`
--

DROP TABLE IF EXISTS `mautic_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `translation_parent_id` int(11) DEFAULT NULL,
  `variant_parent_id` int(11) DEFAULT NULL,
  `unsubscribeform_id` int(11) DEFAULT NULL,
  `preference_center_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `subject` longtext COLLATE utf8_unicode_ci,
  `from_address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `from_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reply_to_address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bcc_address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `template` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `utm_tags` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `plain_text` longtext COLLATE utf8_unicode_ci,
  `custom_html` longtext COLLATE utf8_unicode_ci,
  `email_type` longtext COLLATE utf8_unicode_ci,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `read_count` int(11) NOT NULL,
  `sent_count` int(11) NOT NULL,
  `revision` int(11) NOT NULL,
  `lang` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `variant_settings` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `variant_start_date` datetime DEFAULT NULL,
  `dynamic_content` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `variant_sent_count` int(11) NOT NULL,
  `variant_read_count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_3F1479E12469DE2` (`category_id`),
  KEY `IDX_3F1479E9091A2FB` (`translation_parent_id`),
  KEY `IDX_3F1479E91861123` (`variant_parent_id`),
  KEY `IDX_3F1479E2DC494F6` (`unsubscribeform_id`),
  KEY `IDX_3F1479E834F9C5B` (`preference_center_id`),
  CONSTRAINT `FK_3F1479E12469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_3F1479E2DC494F6` FOREIGN KEY (`unsubscribeform_id`) REFERENCES `mautic_forms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_3F1479E834F9C5B` FOREIGN KEY (`preference_center_id`) REFERENCES `mautic_pages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_3F1479E9091A2FB` FOREIGN KEY (`translation_parent_id`) REFERENCES `mautic_emails` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_3F1479E91861123` FOREIGN KEY (`variant_parent_id`) REFERENCES `mautic_emails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_emails`
--

LOCK TABLES `mautic_emails` WRITE;
/*!40000 ALTER TABLE `mautic_emails` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_emails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_focus`
--

DROP TABLE IF EXISTS `mautic_focus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_focus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `focus_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `style` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `website` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `properties` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `utm_tags` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `form_id` int(11) DEFAULT NULL,
  `cache` longtext COLLATE utf8_unicode_ci,
  `html_mode` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `editor` longtext COLLATE utf8_unicode_ci,
  `html` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_CD9DD44A12469DE2` (`category_id`),
  KEY `mautic_focus_type` (`focus_type`),
  KEY `mautic_focus_style` (`style`),
  KEY `mautic_focus_form` (`form_id`),
  CONSTRAINT `FK_CD9DD44A12469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_focus`
--

LOCK TABLES `mautic_focus` WRITE;
/*!40000 ALTER TABLE `mautic_focus` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_focus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_focus_stats`
--

DROP TABLE IF EXISTS `mautic_focus_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_focus_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `focus_id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type_id` int(11) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D99447CF51804B42` (`focus_id`),
  KEY `IDX_D99447CF55458D` (`lead_id`),
  KEY `mautic_focus_type` (`type`),
  KEY `mautic_focus_type_id` (`type`,`type_id`),
  KEY `mautic_focus_date_added` (`date_added`),
  CONSTRAINT `FK_D99447CF51804B42` FOREIGN KEY (`focus_id`) REFERENCES `mautic_focus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_D99447CF55458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_focus_stats`
--

LOCK TABLES `mautic_focus_stats` WRITE;
/*!40000 ALTER TABLE `mautic_focus_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_focus_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_form_actions`
--

DROP TABLE IF EXISTS `mautic_form_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_form_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `action_order` int(11) NOT NULL,
  `properties` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_B0802D3D5FF69B7D` (`form_id`),
  KEY `mautic_form_action_type_search` (`type`),
  CONSTRAINT `FK_B0802D3D5FF69B7D` FOREIGN KEY (`form_id`) REFERENCES `mautic_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_form_actions`
--

LOCK TABLES `mautic_form_actions` WRITE;
/*!40000 ALTER TABLE `mautic_form_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_form_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_form_fields`
--

DROP TABLE IF EXISTS `mautic_form_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_form_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `label` longtext COLLATE utf8_unicode_ci NOT NULL,
  `show_label` tinyint(1) DEFAULT NULL,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_custom` tinyint(1) NOT NULL,
  `custom_parameters` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `default_value` longtext COLLATE utf8_unicode_ci,
  `is_required` tinyint(1) NOT NULL,
  `validation_message` longtext COLLATE utf8_unicode_ci,
  `help_message` longtext COLLATE utf8_unicode_ci,
  `field_order` int(11) DEFAULT NULL,
  `properties` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `label_attr` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `input_attr` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `container_attr` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lead_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `save_result` tinyint(1) DEFAULT NULL,
  `is_auto_fill` tinyint(1) DEFAULT NULL,
  `show_when_value_exists` tinyint(1) DEFAULT NULL,
  `show_after_x_submissions` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_66F600355FF69B7D` (`form_id`),
  KEY `mautic_form_field_type_search` (`type`),
  CONSTRAINT `FK_66F600355FF69B7D` FOREIGN KEY (`form_id`) REFERENCES `mautic_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_form_fields`
--

LOCK TABLES `mautic_form_fields` WRITE;
/*!40000 ALTER TABLE `mautic_form_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_form_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_form_submissions`
--

DROP TABLE IF EXISTS `mautic_form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_form_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `ip_id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `page_id` int(11) DEFAULT NULL,
  `tracking_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_submitted` datetime NOT NULL,
  `referer` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_8F87CEF45FF69B7D` (`form_id`),
  KEY `IDX_8F87CEF4A03F5E9F` (`ip_id`),
  KEY `IDX_8F87CEF455458D` (`lead_id`),
  KEY `IDX_8F87CEF4C4663E4` (`page_id`),
  KEY `mautic_form_submission_tracking_search` (`tracking_id`),
  KEY `mautic_form_date_submitted` (`date_submitted`),
  CONSTRAINT `FK_8F87CEF455458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_8F87CEF45FF69B7D` FOREIGN KEY (`form_id`) REFERENCES `mautic_forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_8F87CEF4A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `mautic_ip_addresses` (`id`),
  CONSTRAINT `FK_8F87CEF4C4663E4` FOREIGN KEY (`page_id`) REFERENCES `mautic_pages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_form_submissions`
--

LOCK TABLES `mautic_form_submissions` WRITE;
/*!40000 ALTER TABLE `mautic_form_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_form_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_forms`
--

DROP TABLE IF EXISTS `mautic_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cached_html` longtext COLLATE utf8_unicode_ci,
  `post_action` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `post_action_property` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `template` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `in_kiosk_mode` tinyint(1) DEFAULT NULL,
  `render_style` tinyint(1) DEFAULT NULL,
  `form_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5262855412469DE2` (`category_id`),
  CONSTRAINT `FK_5262855412469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_forms`
--

LOCK TABLES `mautic_forms` WRITE;
/*!40000 ALTER TABLE `mautic_forms` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_imports`
--

DROP TABLE IF EXISTS `mautic_imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_imports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dir` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `original_file` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `line_count` int(11) NOT NULL,
  `inserted_count` int(11) NOT NULL,
  `updated_count` int(11) NOT NULL,
  `ignored_count` int(11) NOT NULL,
  `priority` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `date_started` datetime DEFAULT NULL,
  `date_ended` datetime DEFAULT NULL,
  `object` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `properties` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:json_array)',
  PRIMARY KEY (`id`),
  KEY `mautic_import_object` (`object`),
  KEY `mautic_import_status` (`status`),
  KEY `mautic_import_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_imports`
--

LOCK TABLES `mautic_imports` WRITE;
/*!40000 ALTER TABLE `mautic_imports` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_imports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_integration_entity`
--

DROP TABLE IF EXISTS `mautic_integration_entity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_integration_entity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_added` datetime NOT NULL,
  `integration` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `integration_entity` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `integration_entity_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `internal_entity` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `internal_entity_id` int(11) DEFAULT NULL,
  `last_sync_date` datetime DEFAULT NULL,
  `internal` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `mautic_integration_external_entity` (`integration`,`integration_entity`,`integration_entity_id`),
  KEY `mautic_integration_internal_entity` (`integration`,`internal_entity`,`internal_entity_id`),
  KEY `mautic_integration_entity_match` (`integration`,`internal_entity`,`integration_entity`),
  KEY `mautic_integration_last_sync_date` (`integration`,`last_sync_date`),
  KEY `mautic_internal_integration_entity` (`internal_entity_id`,`integration_entity_id`,`internal_entity`,`integration_entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_integration_entity`
--

LOCK TABLES `mautic_integration_entity` WRITE;
/*!40000 ALTER TABLE `mautic_integration_entity` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_integration_entity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_ip_addresses`
--

DROP TABLE IF EXISTS `mautic_ip_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_ip_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `ip_details` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `mautic_ip_search` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_ip_addresses`
--

LOCK TABLES `mautic_ip_addresses` WRITE;
/*!40000 ALTER TABLE `mautic_ip_addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_ip_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_categories`
--

DROP TABLE IF EXISTS `mautic_lead_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `manually_removed` tinyint(1) NOT NULL,
  `manually_added` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2D90301A12469DE2` (`category_id`),
  KEY `IDX_2D90301A55458D` (`lead_id`),
  CONSTRAINT `FK_2D90301A12469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2D90301A55458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_categories`
--

LOCK TABLES `mautic_lead_categories` WRITE;
/*!40000 ALTER TABLE `mautic_lead_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_lead_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_companies_change_log`
--

DROP TABLE IF EXISTS `mautic_lead_companies_change_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_companies_change_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `type` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `event_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `action_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `company_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5696496E55458D` (`lead_id`),
  KEY `mautic_company_date_added` (`date_added`),
  CONSTRAINT `FK_5696496E55458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_companies_change_log`
--

LOCK TABLES `mautic_lead_companies_change_log` WRITE;
/*!40000 ALTER TABLE `mautic_lead_companies_change_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_lead_companies_change_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_devices`
--

DROP TABLE IF EXISTS `mautic_lead_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `client_info` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `device` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `device_os_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `device_os_shortname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `device_os_version` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `device_os_platform` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `device_brand` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `device_model` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `device_fingerprint` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_CC6DAE1D55458D` (`lead_id`),
  KEY `mautic_date_added_search` (`date_added`),
  KEY `mautic_device_search` (`device`),
  KEY `mautic_device_os_name_search` (`device_os_name`),
  KEY `mautic_device_os_shortname_search` (`device_os_shortname`),
  KEY `mautic_device_os_version_search` (`device_os_version`),
  KEY `mautic_device_os_platform_search` (`device_os_platform`),
  KEY `mautic_device_brand_search` (`device_brand`),
  KEY `mautic_device_model_search` (`device_model`),
  KEY `mautic_device_fingerprint_search` (`device_fingerprint`),
  CONSTRAINT `FK_CC6DAE1D55458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_devices`
--

LOCK TABLES `mautic_lead_devices` WRITE;
/*!40000 ALTER TABLE `mautic_lead_devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_lead_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_donotcontact`
--

DROP TABLE IF EXISTS `mautic_lead_donotcontact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_donotcontact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `reason` smallint(6) NOT NULL,
  `channel` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `channel_id` int(11) DEFAULT NULL,
  `comments` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_8222F76255458D` (`lead_id`),
  KEY `mautic_dnc_reason_search` (`reason`),
  CONSTRAINT `FK_8222F76255458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_donotcontact`
--

LOCK TABLES `mautic_lead_donotcontact` WRITE;
/*!40000 ALTER TABLE `mautic_lead_donotcontact` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_lead_donotcontact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_event_log`
--

DROP TABLE IF EXISTS `mautic_lead_event_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_event_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bundle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `object` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `action` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `object_id` int(11) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `properties` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:json_array)',
  PRIMARY KEY (`id`),
  KEY `mautic_lead_id_index` (`lead_id`),
  KEY `mautic_lead_object_index` (`object`,`object_id`),
  KEY `mautic_lead_timeline_index` (`bundle`,`object`,`action`,`object_id`),
  KEY `mautic_lead_date_added_index` (`date_added`),
  CONSTRAINT `FK_4A389C8855458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_event_log`
--

LOCK TABLES `mautic_lead_event_log` WRITE;
/*!40000 ALTER TABLE `mautic_lead_event_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_lead_event_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_fields`
--

DROP TABLE IF EXISTS `mautic_lead_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `field_group` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `default_value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL,
  `is_fixed` tinyint(1) NOT NULL,
  `is_visible` tinyint(1) NOT NULL,
  `is_short_visible` tinyint(1) NOT NULL,
  `is_listable` tinyint(1) NOT NULL,
  `is_publicly_updatable` tinyint(1) NOT NULL,
  `is_unique_identifer` tinyint(1) DEFAULT NULL,
  `field_order` int(11) DEFAULT NULL,
  `object` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `properties` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `mautic_search_by_object` (`object`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_fields`
--

LOCK TABLES `mautic_lead_fields` WRITE;
/*!40000 ALTER TABLE `mautic_lead_fields` DISABLE KEYS */;
INSERT INTO `mautic_lead_fields` VALUES (1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Title','title','lookup','core',NULL,0,1,1,0,0,0,0,1,'lead','a:1:{s:4:\"list\";s:11:\"Mr|Mrs|Miss\";}'),(2,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'First Name','firstname','text','core',NULL,0,1,1,1,0,0,0,2,'lead','a:0:{}'),(3,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Last Name','lastname','text','core',NULL,0,1,1,1,0,0,0,3,'lead','a:0:{}'),(4,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Company','company','text','core',NULL,0,1,1,0,0,0,0,4,'lead','a:0:{}'),(5,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Position','position','text','core',NULL,0,1,1,0,0,0,0,5,'lead','a:0:{}'),(6,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Email','email','email','core',NULL,0,1,1,1,0,0,1,6,'lead','a:0:{}'),(7,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Mobile','mobile','tel','core',NULL,0,1,1,0,1,0,0,7,'lead','a:0:{}'),(8,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Phone','phone','tel','core',NULL,0,1,1,0,1,0,0,8,'lead','a:0:{}'),(9,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Points','points','number','core',NULL,0,1,1,0,0,0,0,9,'lead','a:0:{}'),(10,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Fax','fax','tel','core',NULL,0,0,1,0,1,0,0,10,'lead','a:0:{}'),(11,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Address Line 1','address1','text','core',NULL,0,1,1,0,1,0,0,11,'lead','a:0:{}'),(12,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Address Line 2','address2','text','core',NULL,0,1,1,0,1,0,0,12,'lead','a:0:{}'),(13,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'City','city','text','core',NULL,0,1,1,0,0,0,0,13,'lead','a:0:{}'),(14,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'State','state','region','core',NULL,0,1,1,0,0,0,0,14,'lead','a:0:{}'),(15,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Zip Code','zipcode','text','core',NULL,0,1,1,0,0,0,0,15,'lead','a:0:{}'),(16,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Country','country','country','core',NULL,0,1,1,0,0,0,0,16,'lead','a:0:{}'),(17,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Preferred Locale','preferred_locale','locale','core',NULL,0,1,1,0,1,0,0,17,'lead','a:0:{}'),(18,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Attribution Date','attribution_date','datetime','core',NULL,0,1,1,0,1,0,0,18,'lead','a:0:{}'),(19,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Attribution','attribution','number','core',NULL,0,1,1,0,1,0,0,19,'lead','a:2:{s:9:\"roundmode\";i:4;s:9:\"precision\";i:2;}'),(20,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Website','website','url','core',NULL,0,0,1,0,1,0,0,20,'lead','a:0:{}'),(21,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Facebook','facebook','text','social',NULL,0,0,1,0,1,0,0,21,'lead','a:0:{}'),(22,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Foursquare','foursquare','text','social',NULL,0,0,1,0,1,0,0,22,'lead','a:0:{}'),(23,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Google+','googleplus','text','social',NULL,0,0,1,0,1,0,0,23,'lead','a:0:{}'),(24,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Instagram','instagram','text','social',NULL,0,0,1,0,1,0,0,24,'lead','a:0:{}'),(25,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'LinkedIn','linkedin','text','social',NULL,0,0,1,0,1,0,0,25,'lead','a:0:{}'),(26,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Skype','skype','text','social',NULL,0,0,1,0,1,0,0,26,'lead','a:0:{}'),(27,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Twitter','twitter','text','social',NULL,0,0,1,0,1,0,0,27,'lead','a:0:{}'),(28,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Address 1','companyaddress1','text','core',NULL,0,1,1,0,1,0,0,1,'company','a:0:{}'),(29,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Address 2','companyaddress2','text','core',NULL,0,1,1,0,1,0,0,2,'company','a:0:{}'),(30,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Company Email','companyemail','email','core',NULL,0,1,1,0,0,0,1,3,'company','a:0:{}'),(31,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Phone','companyphone','tel','core',NULL,0,1,1,0,1,0,0,4,'company','a:0:{}'),(32,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'City','companycity','text','core',NULL,0,1,1,0,1,0,0,5,'company','a:0:{}'),(33,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'State','companystate','region','core',NULL,0,1,1,0,0,0,0,6,'company','a:0:{}'),(34,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Zip Code','companyzipcode','text','core',NULL,0,1,1,0,1,0,0,7,'company','a:0:{}'),(35,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Country','companycountry','country','core',NULL,0,1,1,0,0,0,0,8,'company','a:0:{}'),(36,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Company Name','companyname','text','core',NULL,1,1,1,0,0,0,0,9,'company','a:0:{}'),(37,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Website','companywebsite','url','core',NULL,0,1,1,0,1,0,0,10,'company','a:0:{}'),(38,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Number of Employees','companynumber_of_employees','number','professional',NULL,0,0,1,0,0,0,0,11,'company','a:2:{s:9:\"roundmode\";i:4;s:9:\"precision\";i:0;}'),(39,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Fax','companyfax','tel','professional',NULL,0,0,1,0,1,0,0,12,'company','a:0:{}'),(40,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Annual Revenue','companyannual_revenue','number','professional',NULL,0,0,1,0,1,0,0,13,'company','a:2:{s:9:\"roundmode\";i:4;s:9:\"precision\";i:2;}'),(41,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Industry','companyindustry','select','professional',NULL,0,1,1,0,0,0,0,14,'company','a:1:{s:4:\"list\";s:349:\"Agriculture|Apparel|Banking|Biotechnology|Chemicals|Communications|Construction|Education|Electronics|Energy|Engineering|Entertainment|Environmental|Finance|Food & Beverage|Government|Healthcare|Hospitality|Insurance|Machinery|Manufacturing|Media|Not for Profit|Recreation|Retail|Shipping|Technology|Telecommunications|Transportation|Utilities|Other\";}'),(42,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Description','companydescription','text','professional',NULL,0,1,1,0,0,0,0,15,'company','a:0:{}');
/*!40000 ALTER TABLE `mautic_lead_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_frequencyrules`
--

DROP TABLE IF EXISTS `mautic_lead_frequencyrules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_frequencyrules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `frequency_number` smallint(6) DEFAULT NULL,
  `frequency_time` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `channel` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `preferred_channel` tinyint(1) NOT NULL,
  `pause_from_date` datetime DEFAULT NULL,
  `pause_to_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B6267F0455458D` (`lead_id`),
  KEY `mautic_channel_frequency` (`channel`),
  CONSTRAINT `FK_B6267F0455458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_frequencyrules`
--

LOCK TABLES `mautic_lead_frequencyrules` WRITE;
/*!40000 ALTER TABLE `mautic_lead_frequencyrules` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_lead_frequencyrules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_ips_xref`
--

DROP TABLE IF EXISTS `mautic_lead_ips_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_ips_xref` (
  `lead_id` int(11) NOT NULL,
  `ip_id` int(11) NOT NULL,
  PRIMARY KEY (`lead_id`,`ip_id`),
  KEY `IDX_47BF800655458D` (`lead_id`),
  KEY `IDX_47BF8006A03F5E9F` (`ip_id`),
  CONSTRAINT `FK_47BF800655458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_47BF8006A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `mautic_ip_addresses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_ips_xref`
--

LOCK TABLES `mautic_lead_ips_xref` WRITE;
/*!40000 ALTER TABLE `mautic_lead_ips_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_lead_ips_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_lists`
--

DROP TABLE IF EXISTS `mautic_lead_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `filters` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  `is_global` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_lists`
--

LOCK TABLES `mautic_lead_lists` WRITE;
/*!40000 ALTER TABLE `mautic_lead_lists` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_lead_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_lists_leads`
--

DROP TABLE IF EXISTS `mautic_lead_lists_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_lists_leads` (
  `leadlist_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `manually_removed` tinyint(1) NOT NULL,
  `manually_added` tinyint(1) NOT NULL,
  PRIMARY KEY (`leadlist_id`,`lead_id`),
  KEY `IDX_B2794B6EB9FC8874` (`leadlist_id`),
  KEY `IDX_B2794B6E55458D` (`lead_id`),
  CONSTRAINT `FK_B2794B6E55458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_B2794B6EB9FC8874` FOREIGN KEY (`leadlist_id`) REFERENCES `mautic_lead_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_lists_leads`
--

LOCK TABLES `mautic_lead_lists_leads` WRITE;
/*!40000 ALTER TABLE `mautic_lead_lists_leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_lead_lists_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_notes`
--

DROP TABLE IF EXISTS `mautic_lead_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text` longtext COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_401F051755458D` (`lead_id`),
  CONSTRAINT `FK_401F051755458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_notes`
--

LOCK TABLES `mautic_lead_notes` WRITE;
/*!40000 ALTER TABLE `mautic_lead_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_lead_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_points_change_log`
--

DROP TABLE IF EXISTS `mautic_lead_points_change_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_points_change_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `ip_id` int(11) NOT NULL,
  `type` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `event_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `action_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `delta` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A3651E6955458D` (`lead_id`),
  KEY `IDX_A3651E69A03F5E9F` (`ip_id`),
  KEY `mautic_point_date_added` (`date_added`),
  CONSTRAINT `FK_A3651E6955458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_A3651E69A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `mautic_ip_addresses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_points_change_log`
--

LOCK TABLES `mautic_lead_points_change_log` WRITE;
/*!40000 ALTER TABLE `mautic_lead_points_change_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_lead_points_change_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_stages_change_log`
--

DROP TABLE IF EXISTS `mautic_lead_stages_change_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_stages_change_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `stage_id` int(11) DEFAULT NULL,
  `event_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `action_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_444D1C5655458D` (`lead_id`),
  KEY `IDX_444D1C562298D193` (`stage_id`),
  CONSTRAINT `FK_444D1C562298D193` FOREIGN KEY (`stage_id`) REFERENCES `mautic_stages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_444D1C5655458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_stages_change_log`
--

LOCK TABLES `mautic_lead_stages_change_log` WRITE;
/*!40000 ALTER TABLE `mautic_lead_stages_change_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_lead_stages_change_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_tags`
--

DROP TABLE IF EXISTS `mautic_lead_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mautic_lead_tag_search` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_tags`
--

LOCK TABLES `mautic_lead_tags` WRITE;
/*!40000 ALTER TABLE `mautic_lead_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_lead_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_tags_xref`
--

DROP TABLE IF EXISTS `mautic_lead_tags_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_tags_xref` (
  `lead_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`lead_id`,`tag_id`),
  KEY `IDX_BF8E2D1055458D` (`lead_id`),
  KEY `IDX_BF8E2D10BAD26311` (`tag_id`),
  CONSTRAINT `FK_BF8E2D1055458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_BF8E2D10BAD26311` FOREIGN KEY (`tag_id`) REFERENCES `mautic_lead_tags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_tags_xref`
--

LOCK TABLES `mautic_lead_tags_xref` WRITE;
/*!40000 ALTER TABLE `mautic_lead_tags_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_lead_tags_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_lead_utmtags`
--

DROP TABLE IF EXISTS `mautic_lead_utmtags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_lead_utmtags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `query` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `referer` longtext COLLATE utf8_unicode_ci,
  `remote_host` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_agent` longtext COLLATE utf8_unicode_ci,
  `utm_campaign` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `utm_content` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `utm_medium` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `utm_source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `utm_term` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_41BF776455458D` (`lead_id`),
  CONSTRAINT `FK_41BF776455458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_lead_utmtags`
--

LOCK TABLES `mautic_lead_utmtags` WRITE;
/*!40000 ALTER TABLE `mautic_lead_utmtags` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_lead_utmtags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_leads`
--

DROP TABLE IF EXISTS `mautic_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `stage_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `points` int(11) NOT NULL,
  `last_active` datetime DEFAULT NULL,
  `internal` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `social_cache` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `date_identified` datetime DEFAULT NULL,
  `preferred_profile_image` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `company` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `position` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mobile` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `zipcode` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timezone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fax` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `preferred_locale` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `attribution_date` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
  `attribution` double DEFAULT NULL,
  `website` longtext COLLATE utf8_unicode_ci,
  `facebook` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `foursquare` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `googleplus` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `instagram` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `linkedin` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `skype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `twitter` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B8CDDBF17E3C61F9` (`owner_id`),
  KEY `IDX_B8CDDBF12298D193` (`stage_id`),
  KEY `mautic_lead_date_added` (`date_added`),
  KEY `mautic_title_search` (`title`),
  KEY `mautic_firstname_search` (`firstname`),
  KEY `mautic_lastname_search` (`lastname`),
  KEY `mautic_company_search` (`company`),
  KEY `mautic_position_search` (`position`),
  KEY `mautic_email_search` (`email`),
  KEY `mautic_mobile_search` (`mobile`),
  KEY `mautic_phone_search` (`phone`),
  KEY `mautic_points_search` (`points`),
  KEY `mautic_fax_search` (`fax`),
  KEY `mautic_address1_search` (`address1`),
  KEY `mautic_address2_search` (`address2`),
  KEY `mautic_city_search` (`city`),
  KEY `mautic_state_search` (`state`),
  KEY `mautic_zipcode_search` (`zipcode`),
  KEY `mautic_country_search` (`country`),
  KEY `mautic_preferred_locale_search` (`preferred_locale`),
  KEY `mautic_attribution_date_search` (`attribution_date`),
  KEY `mautic_attribution_search` (`attribution`),
  KEY `mautic_facebook_search` (`facebook`),
  KEY `mautic_foursquare_search` (`foursquare`),
  KEY `mautic_googleplus_search` (`googleplus`),
  KEY `mautic_instagram_search` (`instagram`),
  KEY `mautic_linkedin_search` (`linkedin`),
  KEY `mautic_skype_search` (`skype`),
  KEY `mautic_twitter_search` (`twitter`),
  KEY `mautic_contact_attribution` (`attribution`,`attribution_date`),
  KEY `mautic_date_added_country_index` (`date_added`,`country`),
  CONSTRAINT `FK_B8CDDBF12298D193` FOREIGN KEY (`stage_id`) REFERENCES `mautic_stages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_B8CDDBF17E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `mautic_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_leads`
--

LOCK TABLES `mautic_leads` WRITE;
/*!40000 ALTER TABLE `mautic_leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_message_channels`
--

DROP TABLE IF EXISTS `mautic_message_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_message_channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `channel` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `channel_id` int(11) DEFAULT NULL,
  `properties` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:json_array)',
  `is_enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mautic_channel_index` (`message_id`,`channel`),
  KEY `IDX_BDBF11B5537A1329` (`message_id`),
  KEY `mautic_channel_entity_index` (`channel`,`channel_id`),
  KEY `mautic_channel_enabled_index` (`channel`,`is_enabled`),
  CONSTRAINT `FK_BDBF11B5537A1329` FOREIGN KEY (`message_id`) REFERENCES `mautic_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_message_channels`
--

LOCK TABLES `mautic_message_channels` WRITE;
/*!40000 ALTER TABLE `mautic_message_channels` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_message_channels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_message_queue`
--

DROP TABLE IF EXISTS `mautic_message_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_message_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) DEFAULT NULL,
  `lead_id` int(11) NOT NULL,
  `channel` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `channel_id` int(11) NOT NULL,
  `priority` smallint(6) NOT NULL,
  `max_attempts` smallint(6) NOT NULL,
  `attempts` smallint(6) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date_published` datetime DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `last_attempt` datetime DEFAULT NULL,
  `date_sent` datetime DEFAULT NULL,
  `options` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_59097EE871F7E88B` (`event_id`),
  KEY `IDX_59097EE855458D` (`lead_id`),
  KEY `mautic_message_status_search` (`status`),
  KEY `mautic_message_date_sent` (`date_sent`),
  KEY `mautic_message_scheduled_date` (`scheduled_date`),
  KEY `mautic_message_priority` (`priority`),
  KEY `mautic_message_success` (`success`),
  KEY `mautic_message_channel_search` (`channel`,`channel_id`),
  CONSTRAINT `FK_59097EE855458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_59097EE871F7E88B` FOREIGN KEY (`event_id`) REFERENCES `mautic_campaign_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_message_queue`
--

LOCK TABLES `mautic_message_queue` WRITE;
/*!40000 ALTER TABLE `mautic_message_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_message_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_messages`
--

DROP TABLE IF EXISTS `mautic_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_FA2477DD12469DE2` (`category_id`),
  KEY `mautic_date_message_added` (`date_added`),
  CONSTRAINT `FK_FA2477DD12469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_messages`
--

LOCK TABLES `mautic_messages` WRITE;
/*!40000 ALTER TABLE `mautic_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_monitor_post_count`
--

DROP TABLE IF EXISTS `mautic_monitor_post_count`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_monitor_post_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `monitor_id` int(11) DEFAULT NULL,
  `post_date` date NOT NULL,
  `post_count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_23E5B29B4CE1C902` (`monitor_id`),
  CONSTRAINT `FK_23E5B29B4CE1C902` FOREIGN KEY (`monitor_id`) REFERENCES `mautic_monitoring` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_monitor_post_count`
--

LOCK TABLES `mautic_monitor_post_count` WRITE;
/*!40000 ALTER TABLE `mautic_monitor_post_count` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_monitor_post_count` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_monitoring`
--

DROP TABLE IF EXISTS `mautic_monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_monitoring` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `lists` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `network_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `revision` int(11) NOT NULL,
  `stats` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `properties` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9DACF94912469DE2` (`category_id`),
  CONSTRAINT `FK_9DACF94912469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_monitoring`
--

LOCK TABLES `mautic_monitoring` WRITE;
/*!40000 ALTER TABLE `mautic_monitoring` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_monitoring` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_monitoring_leads`
--

DROP TABLE IF EXISTS `mautic_monitoring_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_monitoring_leads` (
  `monitor_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`monitor_id`,`lead_id`),
  KEY `IDX_2AD4D584CE1C902` (`monitor_id`),
  KEY `IDX_2AD4D5855458D` (`lead_id`),
  CONSTRAINT `FK_2AD4D584CE1C902` FOREIGN KEY (`monitor_id`) REFERENCES `mautic_monitoring` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2AD4D5855458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_monitoring_leads`
--

LOCK TABLES `mautic_monitoring_leads` WRITE;
/*!40000 ALTER TABLE `mautic_monitoring_leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_monitoring_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_notifications`
--

DROP TABLE IF EXISTS `mautic_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `header` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `message` longtext COLLATE utf8_unicode_ci NOT NULL,
  `date_added` datetime NOT NULL,
  `icon_class` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B9524EB3A76ED395` (`user_id`),
  KEY `mautic_notification_read_status` (`is_read`),
  KEY `mautic_notification_type` (`type`),
  KEY `mautic_notification_user_read_status` (`is_read`,`user_id`),
  CONSTRAINT `FK_B9524EB3A76ED395` FOREIGN KEY (`user_id`) REFERENCES `mautic_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_notifications`
--

LOCK TABLES `mautic_notifications` WRITE;
/*!40000 ALTER TABLE `mautic_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_oauth1_access_tokens`
--

DROP TABLE IF EXISTS `mautic_oauth1_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_oauth1_access_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consumer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `secret` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `expires_at` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7E9B965637FDBD6D` (`consumer_id`),
  KEY `IDX_7E9B9656A76ED395` (`user_id`),
  KEY `mautic_oauth1_access_token_search` (`token`),
  CONSTRAINT `FK_7E9B965637FDBD6D` FOREIGN KEY (`consumer_id`) REFERENCES `mautic_oauth1_consumers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_7E9B9656A76ED395` FOREIGN KEY (`user_id`) REFERENCES `mautic_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_oauth1_access_tokens`
--

LOCK TABLES `mautic_oauth1_access_tokens` WRITE;
/*!40000 ALTER TABLE `mautic_oauth1_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_oauth1_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_oauth1_consumers`
--

DROP TABLE IF EXISTS `mautic_oauth1_consumers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_oauth1_consumers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `consumer_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `consumer_secret` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `callback` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mautic_consumer_search` (`consumer_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_oauth1_consumers`
--

LOCK TABLES `mautic_oauth1_consumers` WRITE;
/*!40000 ALTER TABLE `mautic_oauth1_consumers` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_oauth1_consumers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_oauth1_nonces`
--

DROP TABLE IF EXISTS `mautic_oauth1_nonces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_oauth1_nonces` (
  `nonce` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`nonce`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_oauth1_nonces`
--

LOCK TABLES `mautic_oauth1_nonces` WRITE;
/*!40000 ALTER TABLE `mautic_oauth1_nonces` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_oauth1_nonces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_oauth1_request_tokens`
--

DROP TABLE IF EXISTS `mautic_oauth1_request_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_oauth1_request_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consumer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `secret` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `expires_at` bigint(20) NOT NULL,
  `verifier` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A1FA930137FDBD6D` (`consumer_id`),
  KEY `IDX_A1FA9301A76ED395` (`user_id`),
  KEY `mautic_oauth1_request_token_search` (`token`),
  CONSTRAINT `FK_A1FA930137FDBD6D` FOREIGN KEY (`consumer_id`) REFERENCES `mautic_oauth1_consumers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_A1FA9301A76ED395` FOREIGN KEY (`user_id`) REFERENCES `mautic_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_oauth1_request_tokens`
--

LOCK TABLES `mautic_oauth1_request_tokens` WRITE;
/*!40000 ALTER TABLE `mautic_oauth1_request_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_oauth1_request_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_oauth2_accesstokens`
--

DROP TABLE IF EXISTS `mautic_oauth2_accesstokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_oauth2_accesstokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `expires_at` bigint(20) DEFAULT NULL,
  `scope` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_26B4E2AA5F37A13B` (`token`),
  KEY `IDX_26B4E2AA19EB6921` (`client_id`),
  KEY `IDX_26B4E2AAA76ED395` (`user_id`),
  KEY `mautic_oauth2_access_token_search` (`token`),
  CONSTRAINT `FK_26B4E2AA19EB6921` FOREIGN KEY (`client_id`) REFERENCES `mautic_oauth2_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_26B4E2AAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `mautic_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_oauth2_accesstokens`
--

LOCK TABLES `mautic_oauth2_accesstokens` WRITE;
/*!40000 ALTER TABLE `mautic_oauth2_accesstokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_oauth2_accesstokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_oauth2_authcodes`
--

DROP TABLE IF EXISTS `mautic_oauth2_authcodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_oauth2_authcodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `expires_at` bigint(20) DEFAULT NULL,
  `scope` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `redirect_uri` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_9539B3695F37A13B` (`token`),
  KEY `IDX_9539B36919EB6921` (`client_id`),
  KEY `IDX_9539B369A76ED395` (`user_id`),
  CONSTRAINT `FK_9539B36919EB6921` FOREIGN KEY (`client_id`) REFERENCES `mautic_oauth2_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_9539B369A76ED395` FOREIGN KEY (`user_id`) REFERENCES `mautic_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_oauth2_authcodes`
--

LOCK TABLES `mautic_oauth2_authcodes` WRITE;
/*!40000 ALTER TABLE `mautic_oauth2_authcodes` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_oauth2_authcodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_oauth2_clients`
--

DROP TABLE IF EXISTS `mautic_oauth2_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_oauth2_clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `random_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `secret` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `redirect_uris` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  `allowed_grant_types` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `mautic_client_id_search` (`random_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_oauth2_clients`
--

LOCK TABLES `mautic_oauth2_clients` WRITE;
/*!40000 ALTER TABLE `mautic_oauth2_clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_oauth2_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_oauth2_refreshtokens`
--

DROP TABLE IF EXISTS `mautic_oauth2_refreshtokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_oauth2_refreshtokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `expires_at` bigint(20) DEFAULT NULL,
  `scope` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8F2D052F5F37A13B` (`token`),
  KEY `IDX_8F2D052F19EB6921` (`client_id`),
  KEY `IDX_8F2D052FA76ED395` (`user_id`),
  KEY `mautic_oauth2_refresh_token_search` (`token`),
  CONSTRAINT `FK_8F2D052F19EB6921` FOREIGN KEY (`client_id`) REFERENCES `mautic_oauth2_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_8F2D052FA76ED395` FOREIGN KEY (`user_id`) REFERENCES `mautic_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_oauth2_refreshtokens`
--

LOCK TABLES `mautic_oauth2_refreshtokens` WRITE;
/*!40000 ALTER TABLE `mautic_oauth2_refreshtokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_oauth2_refreshtokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_oauth2_user_client_xref`
--

DROP TABLE IF EXISTS `mautic_oauth2_user_client_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_oauth2_user_client_xref` (
  `client_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`client_id`,`user_id`),
  KEY `IDX_BC68EA4619EB6921` (`client_id`),
  KEY `IDX_BC68EA46A76ED395` (`user_id`),
  CONSTRAINT `FK_BC68EA4619EB6921` FOREIGN KEY (`client_id`) REFERENCES `mautic_oauth2_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_BC68EA46A76ED395` FOREIGN KEY (`user_id`) REFERENCES `mautic_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_oauth2_user_client_xref`
--

LOCK TABLES `mautic_oauth2_user_client_xref` WRITE;
/*!40000 ALTER TABLE `mautic_oauth2_user_client_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_oauth2_user_client_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_page_hits`
--

DROP TABLE IF EXISTS `mautic_page_hits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_page_hits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) DEFAULT NULL,
  `redirect_id` int(11) DEFAULT NULL,
  `email_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `ip_id` int(11) NOT NULL,
  `device_id` int(11) DEFAULT NULL,
  `date_hit` datetime NOT NULL,
  `date_left` datetime DEFAULT NULL,
  `country` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `region` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `isp` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `organization` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code` int(11) NOT NULL,
  `referer` longtext COLLATE utf8_unicode_ci,
  `url` longtext COLLATE utf8_unicode_ci,
  `url_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_agent` longtext COLLATE utf8_unicode_ci,
  `remote_host` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `page_language` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `browser_languages` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `tracking_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `query` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_7C64CE80C4663E4` (`page_id`),
  KEY `IDX_7C64CE80B42D874D` (`redirect_id`),
  KEY `IDX_7C64CE80A832C1C9` (`email_id`),
  KEY `IDX_7C64CE8055458D` (`lead_id`),
  KEY `IDX_7C64CE80A03F5E9F` (`ip_id`),
  KEY `IDX_7C64CE8094A4C7D4` (`device_id`),
  KEY `mautic_page_hit_tracking_search` (`tracking_id`),
  KEY `mautic_page_hit_code_search` (`code`),
  KEY `mautic_page_hit_source_search` (`source`,`source_id`),
  KEY `mautic_page_date_hit` (`date_hit`),
  KEY `mautic_date_hit_left_index` (`date_hit`,`date_left`),
  CONSTRAINT `FK_7C64CE8055458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_7C64CE8094A4C7D4` FOREIGN KEY (`device_id`) REFERENCES `mautic_lead_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_7C64CE80A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `mautic_ip_addresses` (`id`),
  CONSTRAINT `FK_7C64CE80A832C1C9` FOREIGN KEY (`email_id`) REFERENCES `mautic_emails` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_7C64CE80B42D874D` FOREIGN KEY (`redirect_id`) REFERENCES `mautic_page_redirects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_7C64CE80C4663E4` FOREIGN KEY (`page_id`) REFERENCES `mautic_pages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_page_hits`
--

LOCK TABLES `mautic_page_hits` WRITE;
/*!40000 ALTER TABLE `mautic_page_hits` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_page_hits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_page_redirects`
--

DROP TABLE IF EXISTS `mautic_page_redirects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_page_redirects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `redirect_id` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `url` longtext COLLATE utf8_unicode_ci NOT NULL,
  `hits` int(11) NOT NULL,
  `unique_hits` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_page_redirects`
--

LOCK TABLES `mautic_page_redirects` WRITE;
/*!40000 ALTER TABLE `mautic_page_redirects` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_page_redirects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_pages`
--

DROP TABLE IF EXISTS `mautic_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `translation_parent_id` int(11) DEFAULT NULL,
  `variant_parent_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `template` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `custom_html` longtext COLLATE utf8_unicode_ci,
  `content` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `hits` int(11) NOT NULL,
  `unique_hits` int(11) NOT NULL,
  `variant_hits` int(11) NOT NULL,
  `revision` int(11) NOT NULL,
  `meta_description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `redirect_type` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `redirect_url` varchar(2048) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_preference_center` tinyint(1) DEFAULT NULL,
  `lang` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `variant_settings` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `variant_start_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_8F297BD612469DE2` (`category_id`),
  KEY `IDX_8F297BD69091A2FB` (`translation_parent_id`),
  KEY `IDX_8F297BD691861123` (`variant_parent_id`),
  KEY `mautic_page_alias_search` (`alias`),
  CONSTRAINT `FK_8F297BD612469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_8F297BD69091A2FB` FOREIGN KEY (`translation_parent_id`) REFERENCES `mautic_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_8F297BD691861123` FOREIGN KEY (`variant_parent_id`) REFERENCES `mautic_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_pages`
--

LOCK TABLES `mautic_pages` WRITE;
/*!40000 ALTER TABLE `mautic_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_permissions`
--

DROP TABLE IF EXISTS `mautic_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `bundle` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `bitwise` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mautic_unique_perm` (`bundle`,`name`,`role_id`),
  KEY `IDX_3710FB7CD60322AC` (`role_id`),
  CONSTRAINT `FK_3710FB7CD60322AC` FOREIGN KEY (`role_id`) REFERENCES `mautic_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_permissions`
--

LOCK TABLES `mautic_permissions` WRITE;
/*!40000 ALTER TABLE `mautic_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_plugin_citrix_events`
--

DROP TABLE IF EXISTS `mautic_plugin_citrix_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_plugin_citrix_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `product` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `event_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `event_desc` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `event_type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `event_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B0D62D2455458D` (`lead_id`),
  KEY `mautic_citrix_event_email` (`product`,`email`),
  KEY `mautic_citrix_event_name` (`product`,`event_name`,`event_type`),
  KEY `mautic_citrix_event_type` (`product`,`event_type`,`event_date`),
  KEY `mautic_citrix_event_product` (`product`,`email`,`event_type`),
  KEY `mautic_citrix_event_product_name` (`product`,`email`,`event_type`,`event_name`),
  KEY `mautic_citrix_event_product_name_lead` (`product`,`event_type`,`event_name`,`lead_id`),
  KEY `mautic_citrix_event_product_type_lead` (`product`,`event_type`,`lead_id`),
  KEY `mautic_citrix_event_date` (`event_date`),
  CONSTRAINT `FK_B0D62D2455458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_plugin_citrix_events`
--

LOCK TABLES `mautic_plugin_citrix_events` WRITE;
/*!40000 ALTER TABLE `mautic_plugin_citrix_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_plugin_citrix_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_plugin_crm_pipedrive_owners`
--

DROP TABLE IF EXISTS `mautic_plugin_crm_pipedrive_owners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_plugin_crm_pipedrive_owners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mautic_email` (`email`),
  KEY `mautic_owner_id` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_plugin_crm_pipedrive_owners`
--

LOCK TABLES `mautic_plugin_crm_pipedrive_owners` WRITE;
/*!40000 ALTER TABLE `mautic_plugin_crm_pipedrive_owners` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_plugin_crm_pipedrive_owners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_plugin_integration_settings`
--

DROP TABLE IF EXISTS `mautic_plugin_integration_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_plugin_integration_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_published` tinyint(1) NOT NULL,
  `supported_features` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `api_keys` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  `feature_settings` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_5CEDE447EC942BCF` (`plugin_id`),
  CONSTRAINT `FK_5CEDE447EC942BCF` FOREIGN KEY (`plugin_id`) REFERENCES `mautic_plugins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_plugin_integration_settings`
--

LOCK TABLES `mautic_plugin_integration_settings` WRITE;
/*!40000 ALTER TABLE `mautic_plugin_integration_settings` DISABLE KEYS */;
INSERT INTO `mautic_plugin_integration_settings` VALUES (1,NULL,'OneSignal',0,'a:4:{i:0;s:6:\"mobile\";i:1;s:20:\"landing_page_enabled\";i:2;s:28:\"welcome_notification_enabled\";i:3;s:21:\"tracking_page_enabled\";}','a:0:{}','a:0:{}'),(2,NULL,'Twilio',0,'a:0:{}','a:0:{}','a:0:{}');
/*!40000 ALTER TABLE `mautic_plugin_integration_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_plugins`
--

DROP TABLE IF EXISTS `mautic_plugins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_plugins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `is_missing` tinyint(1) NOT NULL,
  `bundle` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `version` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `author` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mautic_unique_bundle` (`bundle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_plugins`
--

LOCK TABLES `mautic_plugins` WRITE;
/*!40000 ALTER TABLE `mautic_plugins` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_plugins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_point_lead_action_log`
--

DROP TABLE IF EXISTS `mautic_point_lead_action_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_point_lead_action_log` (
  `point_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `ip_id` int(11) DEFAULT NULL,
  `date_fired` datetime NOT NULL,
  PRIMARY KEY (`point_id`,`lead_id`),
  KEY `IDX_4CF01FBDC028CEA2` (`point_id`),
  KEY `IDX_4CF01FBD55458D` (`lead_id`),
  KEY `IDX_4CF01FBDA03F5E9F` (`ip_id`),
  CONSTRAINT `FK_4CF01FBD55458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_4CF01FBDA03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `mautic_ip_addresses` (`id`),
  CONSTRAINT `FK_4CF01FBDC028CEA2` FOREIGN KEY (`point_id`) REFERENCES `mautic_points` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_point_lead_action_log`
--

LOCK TABLES `mautic_point_lead_action_log` WRITE;
/*!40000 ALTER TABLE `mautic_point_lead_action_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_point_lead_action_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_point_lead_event_log`
--

DROP TABLE IF EXISTS `mautic_point_lead_event_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_point_lead_event_log` (
  `event_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `ip_id` int(11) DEFAULT NULL,
  `date_fired` datetime NOT NULL,
  PRIMARY KEY (`event_id`,`lead_id`),
  KEY `IDX_7F02E38E71F7E88B` (`event_id`),
  KEY `IDX_7F02E38E55458D` (`lead_id`),
  KEY `IDX_7F02E38EA03F5E9F` (`ip_id`),
  CONSTRAINT `FK_7F02E38E55458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_7F02E38E71F7E88B` FOREIGN KEY (`event_id`) REFERENCES `mautic_point_trigger_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_7F02E38EA03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `mautic_ip_addresses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_point_lead_event_log`
--

LOCK TABLES `mautic_point_lead_event_log` WRITE;
/*!40000 ALTER TABLE `mautic_point_lead_event_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_point_lead_event_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_point_trigger_events`
--

DROP TABLE IF EXISTS `mautic_point_trigger_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_point_trigger_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trigger_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `action_order` int(11) NOT NULL,
  `properties` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_B0F7376C5FDDDCD6` (`trigger_id`),
  KEY `mautic_trigger_type_search` (`type`),
  CONSTRAINT `FK_B0F7376C5FDDDCD6` FOREIGN KEY (`trigger_id`) REFERENCES `mautic_point_triggers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_point_trigger_events`
--

LOCK TABLES `mautic_point_trigger_events` WRITE;
/*!40000 ALTER TABLE `mautic_point_trigger_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_point_trigger_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_point_triggers`
--

DROP TABLE IF EXISTS `mautic_point_triggers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_point_triggers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `points` int(11) NOT NULL,
  `color` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
  `trigger_existing_leads` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D1C0E08912469DE2` (`category_id`),
  CONSTRAINT `FK_D1C0E08912469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_point_triggers`
--

LOCK TABLES `mautic_point_triggers` WRITE;
/*!40000 ALTER TABLE `mautic_point_triggers` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_point_triggers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_points`
--

DROP TABLE IF EXISTS `mautic_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `delta` int(11) NOT NULL,
  `properties` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_68CA21E512469DE2` (`category_id`),
  KEY `mautic_point_type_search` (`type`),
  CONSTRAINT `FK_68CA21E512469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_points`
--

LOCK TABLES `mautic_points` WRITE;
/*!40000 ALTER TABLE `mautic_points` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_push_ids`
--

DROP TABLE IF EXISTS `mautic_push_ids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_push_ids` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) DEFAULT NULL,
  `push_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `mobile` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6E05FAA355458D` (`lead_id`),
  CONSTRAINT `FK_6E05FAA355458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_push_ids`
--

LOCK TABLES `mautic_push_ids` WRITE;
/*!40000 ALTER TABLE `mautic_push_ids` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_push_ids` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_push_notification_list_xref`
--

DROP TABLE IF EXISTS `mautic_push_notification_list_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_push_notification_list_xref` (
  `notification_id` int(11) NOT NULL,
  `leadlist_id` int(11) NOT NULL,
  PRIMARY KEY (`notification_id`,`leadlist_id`),
  KEY `IDX_8FCED148EF1A9D84` (`notification_id`),
  KEY `IDX_8FCED148B9FC8874` (`leadlist_id`),
  CONSTRAINT `FK_8FCED148B9FC8874` FOREIGN KEY (`leadlist_id`) REFERENCES `mautic_lead_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_8FCED148EF1A9D84` FOREIGN KEY (`notification_id`) REFERENCES `mautic_push_notifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_push_notification_list_xref`
--

LOCK TABLES `mautic_push_notification_list_xref` WRITE;
/*!40000 ALTER TABLE `mautic_push_notification_list_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_push_notification_list_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_push_notification_stats`
--

DROP TABLE IF EXISTS `mautic_push_notification_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_push_notification_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `list_id` int(11) DEFAULT NULL,
  `ip_id` int(11) DEFAULT NULL,
  `date_sent` datetime NOT NULL,
  `date_read` datetime DEFAULT NULL,
  `is_clicked` tinyint(1) NOT NULL,
  `date_clicked` datetime DEFAULT NULL,
  `tracking_hash` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `retry_count` int(11) DEFAULT NULL,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `tokens` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `click_count` int(11) DEFAULT NULL,
  `last_clicked` datetime DEFAULT NULL,
  `click_details` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_78E8C70BEF1A9D84` (`notification_id`),
  KEY `IDX_78E8C70B55458D` (`lead_id`),
  KEY `IDX_78E8C70B3DAE168B` (`list_id`),
  KEY `IDX_78E8C70BA03F5E9F` (`ip_id`),
  KEY `mautic_stat_notification_search` (`notification_id`,`lead_id`),
  KEY `mautic_stat_notification_clicked_search` (`is_clicked`),
  KEY `mautic_stat_notification_hash_search` (`tracking_hash`),
  KEY `mautic_stat_notification_source_search` (`source`,`source_id`),
  CONSTRAINT `FK_78E8C70B3DAE168B` FOREIGN KEY (`list_id`) REFERENCES `mautic_lead_lists` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_78E8C70B55458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_78E8C70BA03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `mautic_ip_addresses` (`id`),
  CONSTRAINT `FK_78E8C70BEF1A9D84` FOREIGN KEY (`notification_id`) REFERENCES `mautic_push_notifications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_push_notification_stats`
--

LOCK TABLES `mautic_push_notification_stats` WRITE;
/*!40000 ALTER TABLE `mautic_push_notification_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_push_notification_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_push_notifications`
--

DROP TABLE IF EXISTS `mautic_push_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_push_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `lang` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `url` longtext COLLATE utf8_unicode_ci,
  `heading` longtext COLLATE utf8_unicode_ci NOT NULL,
  `message` longtext COLLATE utf8_unicode_ci NOT NULL,
  `button` longtext COLLATE utf8_unicode_ci,
  `utm_tags` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `notification_type` longtext COLLATE utf8_unicode_ci,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `read_count` int(11) NOT NULL,
  `sent_count` int(11) NOT NULL,
  `mobile` tinyint(1) NOT NULL,
  `mobileSettings` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_9BD2EC1E12469DE2` (`category_id`),
  CONSTRAINT `FK_9BD2EC1E12469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_push_notifications`
--

LOCK TABLES `mautic_push_notifications` WRITE;
/*!40000 ALTER TABLE `mautic_push_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_push_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_reports`
--

DROP TABLE IF EXISTS `mautic_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `system` tinyint(1) NOT NULL,
  `source` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `columns` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `filters` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `table_order` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `graphs` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `group_by` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `aggregators` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `settings` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:json_array)',
  `is_scheduled` tinyint(1) NOT NULL,
  `schedule_unit` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `to_address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `schedule_day` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `schedule_month_frequency` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_reports`
--

LOCK TABLES `mautic_reports` WRITE;
/*!40000 ALTER TABLE `mautic_reports` DISABLE KEYS */;
INSERT INTO `mautic_reports` VALUES (1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Visits published Pages',NULL,1,'page.hits','a:7:{i:0;s:11:\"ph.date_hit\";i:1;s:6:\"ph.url\";i:2;s:12:\"ph.url_title\";i:3;s:10:\"ph.referer\";i:4;s:12:\"i.ip_address\";i:5;s:7:\"ph.city\";i:6;s:10:\"ph.country\";}','a:2:{i:0;a:3:{s:6:\"column\";s:7:\"ph.code\";s:9:\"condition\";s:2:\"eq\";s:5:\"value\";s:3:\"200\";}i:1;a:3:{s:6:\"column\";s:14:\"p.is_published\";s:9:\"condition\";s:2:\"eq\";s:5:\"value\";s:1:\"1\";}}','a:1:{i:0;a:2:{s:6:\"column\";s:11:\"ph.date_hit\";s:9:\"direction\";s:3:\"ASC\";}}','a:8:{i:0;s:35:\"mautic.page.graph.line.time.on.site\";i:1;s:27:\"mautic.page.graph.line.hits\";i:2;s:38:\"mautic.page.graph.pie.new.vs.returning\";i:3;s:31:\"mautic.page.graph.pie.languages\";i:4;s:34:\"mautic.page.graph.pie.time.on.site\";i:5;s:27:\"mautic.page.table.referrers\";i:6;s:30:\"mautic.page.table.most.visited\";i:7;s:37:\"mautic.page.table.most.visited.unique\";}','a:0:{}','a:0:{}','[]',0,NULL,NULL,NULL,NULL),(2,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Downloads of all Assets',NULL,1,'asset.downloads','a:7:{i:0;s:16:\"ad.date_download\";i:1;s:7:\"a.title\";i:2;s:12:\"i.ip_address\";i:3;s:11:\"l.firstname\";i:4;s:10:\"l.lastname\";i:5;s:7:\"l.email\";i:6;s:4:\"a.id\";}','a:1:{i:0;a:3:{s:6:\"column\";s:14:\"a.is_published\";s:9:\"condition\";s:2:\"eq\";s:5:\"value\";s:1:\"1\";}}','a:1:{i:0;a:2:{s:6:\"column\";s:16:\"ad.date_download\";s:9:\"direction\";s:3:\"ASC\";}}','a:4:{i:0;s:33:\"mautic.asset.graph.line.downloads\";i:1;s:31:\"mautic.asset.graph.pie.statuses\";i:2;s:34:\"mautic.asset.table.most.downloaded\";i:3;s:32:\"mautic.asset.table.top.referrers\";}','a:0:{}','a:0:{}','[]',0,NULL,NULL,NULL,NULL),(3,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Submissions of published Forms',NULL,1,'form.submissions','a:0:{}','a:1:{i:1;a:3:{s:6:\"column\";s:14:\"f.is_published\";s:9:\"condition\";s:2:\"eq\";s:5:\"value\";s:1:\"1\";}}','a:0:{}','a:3:{i:0;s:34:\"mautic.form.graph.line.submissions\";i:1;s:32:\"mautic.form.table.most.submitted\";i:2;s:31:\"mautic.form.table.top.referrers\";}','a:0:{}','a:0:{}','[]',0,NULL,NULL,NULL,NULL),(4,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'All Emails',NULL,1,'email.stats','a:5:{i:0;s:12:\"es.date_sent\";i:1;s:12:\"es.date_read\";i:2;s:9:\"e.subject\";i:3;s:16:\"es.email_address\";i:4;s:4:\"e.id\";}','a:1:{i:0;a:3:{s:6:\"column\";s:14:\"e.is_published\";s:9:\"condition\";s:2:\"eq\";s:5:\"value\";s:1:\"1\";}}','a:1:{i:0;a:2:{s:6:\"column\";s:12:\"es.date_sent\";s:9:\"direction\";s:3:\"ASC\";}}','a:6:{i:0;s:29:\"mautic.email.graph.line.stats\";i:1;s:42:\"mautic.email.graph.pie.ignored.read.failed\";i:2;s:35:\"mautic.email.table.most.emails.read\";i:3;s:35:\"mautic.email.table.most.emails.sent\";i:4;s:43:\"mautic.email.table.most.emails.read.percent\";i:5;s:37:\"mautic.email.table.most.emails.failed\";}','a:0:{}','a:0:{}','[]',0,NULL,NULL,NULL,NULL),(5,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Leads and Points',NULL,1,'lead.pointlog','a:7:{i:0;s:13:\"lp.date_added\";i:1;s:7:\"lp.type\";i:2;s:13:\"lp.event_name\";i:3;s:11:\"l.firstname\";i:4;s:10:\"l.lastname\";i:5;s:7:\"l.email\";i:6;s:8:\"lp.delta\";}','a:0:{}','a:1:{i:0;a:2:{s:6:\"column\";s:13:\"lp.date_added\";s:9:\"direction\";s:3:\"ASC\";}}','a:6:{i:0;s:29:\"mautic.lead.graph.line.points\";i:1;s:29:\"mautic.lead.table.most.points\";i:2;s:29:\"mautic.lead.table.top.actions\";i:3;s:28:\"mautic.lead.table.top.cities\";i:4;s:31:\"mautic.lead.table.top.countries\";i:5;s:28:\"mautic.lead.table.top.events\";}','a:0:{}','a:0:{}','[]',0,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `mautic_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_reports_schedulers`
--

DROP TABLE IF EXISTS `mautic_reports_schedulers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_reports_schedulers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `schedule_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_70534E94BD2A4C0` (`report_id`),
  CONSTRAINT `FK_70534E94BD2A4C0` FOREIGN KEY (`report_id`) REFERENCES `mautic_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_reports_schedulers`
--

LOCK TABLES `mautic_reports_schedulers` WRITE;
/*!40000 ALTER TABLE `mautic_reports_schedulers` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_reports_schedulers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_roles`
--

DROP TABLE IF EXISTS `mautic_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `is_admin` tinyint(1) NOT NULL,
  `readable_permissions` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_roles`
--

LOCK TABLES `mautic_roles` WRITE;
/*!40000 ALTER TABLE `mautic_roles` DISABLE KEYS */;
INSERT INTO `mautic_roles` VALUES (1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Administrator','Full system access',1,'N;');
/*!40000 ALTER TABLE `mautic_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_saml_id_entry`
--

DROP TABLE IF EXISTS `mautic_saml_id_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_saml_id_entry` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `entity_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `expiryTimestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_saml_id_entry`
--

LOCK TABLES `mautic_saml_id_entry` WRITE;
/*!40000 ALTER TABLE `mautic_saml_id_entry` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_saml_id_entry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_sms_message_list_xref`
--

DROP TABLE IF EXISTS `mautic_sms_message_list_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_sms_message_list_xref` (
  `sms_id` int(11) NOT NULL,
  `leadlist_id` int(11) NOT NULL,
  PRIMARY KEY (`sms_id`,`leadlist_id`),
  KEY `IDX_913BA9C5BD5C7E60` (`sms_id`),
  KEY `IDX_913BA9C5B9FC8874` (`leadlist_id`),
  CONSTRAINT `FK_913BA9C5B9FC8874` FOREIGN KEY (`leadlist_id`) REFERENCES `mautic_lead_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_913BA9C5BD5C7E60` FOREIGN KEY (`sms_id`) REFERENCES `mautic_sms_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_sms_message_list_xref`
--

LOCK TABLES `mautic_sms_message_list_xref` WRITE;
/*!40000 ALTER TABLE `mautic_sms_message_list_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_sms_message_list_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_sms_message_stats`
--

DROP TABLE IF EXISTS `mautic_sms_message_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_sms_message_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sms_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `list_id` int(11) DEFAULT NULL,
  `ip_id` int(11) DEFAULT NULL,
  `date_sent` datetime NOT NULL,
  `tracking_hash` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `tokens` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_FC1F4696BD5C7E60` (`sms_id`),
  KEY `IDX_FC1F469655458D` (`lead_id`),
  KEY `IDX_FC1F46963DAE168B` (`list_id`),
  KEY `IDX_FC1F4696A03F5E9F` (`ip_id`),
  KEY `mautic_stat_sms_search` (`sms_id`,`lead_id`),
  KEY `mautic_stat_sms_hash_search` (`tracking_hash`),
  KEY `mautic_stat_sms_source_search` (`source`,`source_id`),
  CONSTRAINT `FK_FC1F46963DAE168B` FOREIGN KEY (`list_id`) REFERENCES `mautic_lead_lists` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_FC1F469655458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_FC1F4696A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `mautic_ip_addresses` (`id`),
  CONSTRAINT `FK_FC1F4696BD5C7E60` FOREIGN KEY (`sms_id`) REFERENCES `mautic_sms_messages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_sms_message_stats`
--

LOCK TABLES `mautic_sms_message_stats` WRITE;
/*!40000 ALTER TABLE `mautic_sms_message_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_sms_message_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_sms_messages`
--

DROP TABLE IF EXISTS `mautic_sms_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_sms_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `lang` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `message` longtext COLLATE utf8_unicode_ci NOT NULL,
  `sms_type` longtext COLLATE utf8_unicode_ci,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `sent_count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_3950837E12469DE2` (`category_id`),
  CONSTRAINT `FK_3950837E12469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_sms_messages`
--

LOCK TABLES `mautic_sms_messages` WRITE;
/*!40000 ALTER TABLE `mautic_sms_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_sms_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_stage_lead_action_log`
--

DROP TABLE IF EXISTS `mautic_stage_lead_action_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_stage_lead_action_log` (
  `stage_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `ip_id` int(11) DEFAULT NULL,
  `date_fired` datetime NOT NULL,
  PRIMARY KEY (`stage_id`,`lead_id`),
  KEY `IDX_840FFA552298D193` (`stage_id`),
  KEY `IDX_840FFA5555458D` (`lead_id`),
  KEY `IDX_840FFA55A03F5E9F` (`ip_id`),
  CONSTRAINT `FK_840FFA552298D193` FOREIGN KEY (`stage_id`) REFERENCES `mautic_stages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_840FFA5555458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_840FFA55A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `mautic_ip_addresses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_stage_lead_action_log`
--

LOCK TABLES `mautic_stage_lead_action_log` WRITE;
/*!40000 ALTER TABLE `mautic_stage_lead_action_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_stage_lead_action_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_stages`
--

DROP TABLE IF EXISTS `mautic_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_stages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `weight` int(11) NOT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_60D2C5A812469DE2` (`category_id`),
  CONSTRAINT `FK_60D2C5A812469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_stages`
--

LOCK TABLES `mautic_stages` WRITE;
/*!40000 ALTER TABLE `mautic_stages` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_stages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_tweet_stats`
--

DROP TABLE IF EXISTS `mautic_tweet_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_tweet_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tweet_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `twitter_tweet_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `handle` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date_sent` datetime DEFAULT NULL,
  `is_failed` tinyint(1) DEFAULT NULL,
  `retry_count` int(11) DEFAULT NULL,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `favorite_count` int(11) DEFAULT NULL,
  `retweet_count` int(11) DEFAULT NULL,
  `response_details` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:json_array)',
  PRIMARY KEY (`id`),
  KEY `IDX_D1718DF61041E39B` (`tweet_id`),
  KEY `IDX_D1718DF655458D` (`lead_id`),
  KEY `mautic_stat_tweet_search` (`tweet_id`,`lead_id`),
  KEY `mautic_stat_tweet_search2` (`lead_id`,`tweet_id`),
  KEY `mautic_stat_tweet_failed_search` (`is_failed`),
  KEY `mautic_stat_tweet_source_search` (`source`,`source_id`),
  KEY `mautic_favorite_count_index` (`favorite_count`),
  KEY `mautic_retweet_count_index` (`retweet_count`),
  KEY `mautic_tweet_date_sent` (`date_sent`),
  KEY `mautic_twitter_tweet_id_index` (`twitter_tweet_id`),
  CONSTRAINT `FK_D1718DF61041E39B` FOREIGN KEY (`tweet_id`) REFERENCES `mautic_tweets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_D1718DF655458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_tweet_stats`
--

LOCK TABLES `mautic_tweet_stats` WRITE;
/*!40000 ALTER TABLE `mautic_tweet_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_tweet_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_tweets`
--

DROP TABLE IF EXISTS `mautic_tweets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_tweets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `page_id` int(11) DEFAULT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `media_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `media_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text` varchar(280) COLLATE utf8_unicode_ci NOT NULL,
  `sent_count` int(11) DEFAULT NULL,
  `favorite_count` int(11) DEFAULT NULL,
  `retweet_count` int(11) DEFAULT NULL,
  `lang` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E548EFE912469DE2` (`category_id`),
  KEY `IDX_E548EFE9C4663E4` (`page_id`),
  KEY `IDX_E548EFE95DA1941` (`asset_id`),
  KEY `mautic_tweet_text_index` (`text`),
  KEY `mautic_sent_count_index` (`sent_count`),
  KEY `mautic_favorite_count_index` (`favorite_count`),
  KEY `mautic_retweet_count_index` (`retweet_count`),
  CONSTRAINT `FK_E548EFE912469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_E548EFE95DA1941` FOREIGN KEY (`asset_id`) REFERENCES `mautic_assets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_E548EFE9C4663E4` FOREIGN KEY (`page_id`) REFERENCES `mautic_pages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_tweets`
--

LOCK TABLES `mautic_tweets` WRITE;
/*!40000 ALTER TABLE `mautic_tweets` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_tweets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_users`
--

DROP TABLE IF EXISTS `mautic_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `first_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `position` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timezone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locale` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_active` datetime DEFAULT NULL,
  `online_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `preferences` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `signature` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_BBDE3B4AF85E0677` (`username`),
  UNIQUE KEY `UNIQ_BBDE3B4AE7927C74` (`email`),
  KEY `IDX_BBDE3B4AD60322AC` (`role_id`),
  CONSTRAINT `FK_BBDE3B4AD60322AC` FOREIGN KEY (`role_id`) REFERENCES `mautic_roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_users`
--

LOCK TABLES `mautic_users` WRITE;
/*!40000 ALTER TABLE `mautic_users` DISABLE KEYS */;
INSERT INTO `mautic_users` VALUES (1,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'admin','$2y$13$q//W.USCah7WAA2rgdW/W.29VoeSSFbR6TfkX.cdtKyq4D2LO0jcW','automated','testing','automated.testing@mautic.com',NULL,'','',NULL,NULL,'offline','a:0:{}',NULL);
/*!40000 ALTER TABLE `mautic_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_video_hits`
--

DROP TABLE IF EXISTS `mautic_video_hits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_video_hits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) DEFAULT NULL,
  `ip_id` int(11) NOT NULL,
  `date_hit` datetime NOT NULL,
  `date_left` datetime DEFAULT NULL,
  `country` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `region` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `isp` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `organization` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code` int(11) NOT NULL,
  `referer` longtext COLLATE utf8_unicode_ci,
  `url` longtext COLLATE utf8_unicode_ci,
  `user_agent` longtext COLLATE utf8_unicode_ci,
  `remote_host` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `guid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `page_language` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `browser_languages` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `channel` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `channel_id` int(11) DEFAULT NULL,
  `time_watched` int(11) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `query` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_3AFB5FE355458D` (`lead_id`),
  KEY `IDX_3AFB5FE3A03F5E9F` (`ip_id`),
  KEY `mautic_video_date_hit` (`date_hit`),
  KEY `mautic_video_channel_search` (`channel`,`channel_id`),
  KEY `mautic_video_guid_lead_search` (`guid`,`lead_id`),
  CONSTRAINT `FK_3AFB5FE355458D` FOREIGN KEY (`lead_id`) REFERENCES `mautic_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_3AFB5FE3A03F5E9F` FOREIGN KEY (`ip_id`) REFERENCES `mautic_ip_addresses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_video_hits`
--

LOCK TABLES `mautic_video_hits` WRITE;
/*!40000 ALTER TABLE `mautic_video_hits` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_video_hits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_webhook_events`
--

DROP TABLE IF EXISTS `mautic_webhook_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_webhook_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webhook_id` int(11) NOT NULL,
  `event_type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_37BF7D915C9BA60B` (`webhook_id`),
  CONSTRAINT `FK_37BF7D915C9BA60B` FOREIGN KEY (`webhook_id`) REFERENCES `mautic_webhooks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_webhook_events`
--

LOCK TABLES `mautic_webhook_events` WRITE;
/*!40000 ALTER TABLE `mautic_webhook_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_webhook_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_webhook_logs`
--

DROP TABLE IF EXISTS `mautic_webhook_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_webhook_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webhook_id` int(11) NOT NULL,
  `status_code` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `note` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `runtime` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C107EFAE5C9BA60B` (`webhook_id`),
  CONSTRAINT `FK_C107EFAE5C9BA60B` FOREIGN KEY (`webhook_id`) REFERENCES `mautic_webhooks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_webhook_logs`
--

LOCK TABLES `mautic_webhook_logs` WRITE;
/*!40000 ALTER TABLE `mautic_webhook_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_webhook_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_webhook_queue`
--

DROP TABLE IF EXISTS `mautic_webhook_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_webhook_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webhook_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `payload` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2C7F647A5C9BA60B` (`webhook_id`),
  KEY `IDX_2C7F647A71F7E88B` (`event_id`),
  CONSTRAINT `FK_2C7F647A5C9BA60B` FOREIGN KEY (`webhook_id`) REFERENCES `mautic_webhooks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2C7F647A71F7E88B` FOREIGN KEY (`event_id`) REFERENCES `mautic_webhook_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_webhook_queue`
--

LOCK TABLES `mautic_webhook_queue` WRITE;
/*!40000 ALTER TABLE `mautic_webhook_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_webhook_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_webhooks`
--

DROP TABLE IF EXISTS `mautic_webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `webhook_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `events_orderby_dir` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B8AA269612469DE2` (`category_id`),
  CONSTRAINT `FK_B8AA269612469DE2` FOREIGN KEY (`category_id`) REFERENCES `mautic_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_webhooks`
--

LOCK TABLES `mautic_webhooks` WRITE;
/*!40000 ALTER TABLE `mautic_webhooks` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_webhooks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mautic_widgets`
--

DROP TABLE IF EXISTS `mautic_widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mautic_widgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `cache_timeout` int(11) DEFAULT NULL,
  `ordering` int(11) DEFAULT NULL,
  `params` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mautic_widgets`
--

LOCK TABLES `mautic_widgets` WRITE;
/*!40000 ALTER TABLE `mautic_widgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `mautic_widgets` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-02-05  0:19:34
