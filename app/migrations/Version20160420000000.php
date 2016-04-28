<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * SMS Channel Migration
 */
class Version20160420000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix . 'sms_messages')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {
        $categoryIdIdx = $this->generatePropertyName('sms_messages', 'idx', array('category_id'));
        $categoryIdFk  = $this->generatePropertyName('sms_messages', 'fk', array('category_id'));

        $mainTableSql = <<<SQL
CREATE TABLE `{$this->prefix}sms_messages` (
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
  `sms_type` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `sent_count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `{$categoryIdIdx}` (`category_id`),
  CONSTRAINT `{$categoryIdFk}` FOREIGN KEY (`category_id`) REFERENCES `mtc_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

        $this->addSql($mainTableSql);

        $smsIdIdx = $this->generatePropertyName('sms_message_stats', 'idx', array('sms_id'));
        $leadIdIdx = $this->generatePropertyName('sms_message_stats', 'idx', array('lead_id'));
        $listIdIdx = $this->generatePropertyName('sms_message_stats', 'idx', array('list_id'));
        $ipIdIdx = $this->generatePropertyName('sms_message_stats', 'idx', array('ip_id'));
        $smsIdFk = $this->generatePropertyName('sms_message_stats', 'fk', array('sms_id'));
        $leadIdFk = $this->generatePropertyName('sms_message_stats', 'fk', array('lead_id'));
        $listIdFk = $this->generatePropertyName('sms_message_stats', 'fk', array('list_id'));
        $ipIdFk = $this->generatePropertyName('sms_message_stats', 'fk', array('ip_id'));

        $statsSql = <<<SQL
CREATE TABLE `{$this->prefix}sms_message_stats` (
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
  KEY `{$smsIdIdx}` (`sms_id`),
  KEY `{$leadIdIdx}` (`lead_id`),
  KEY `{$listIdIdx}` (`list_id`),
  KEY `{$ipIdIdx}` (`ip_id`),
  KEY `mtc_stat_sms_search` (`sms_id`,`lead_id`),
  KEY `mtc_stat_sms_hash_search` (`tracking_hash`),
  KEY `mtc_stat_sms_source_search` (`source`,`source_id`),
  CONSTRAINT `{$listIdFk}` FOREIGN KEY (`list_id`) REFERENCES `mtc_lead_lists` (`id`) ON DELETE SET NULL,
  CONSTRAINT `{$leadIdFk}` FOREIGN KEY (`lead_id`) REFERENCES `mtc_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `{$ipIdFk}` FOREIGN KEY (`ip_id`) REFERENCES `mtc_ip_addresses` (`id`),
  CONSTRAINT `{$smsIdFk}` FOREIGN KEY (`sms_id`) REFERENCES `mtc_sms_messages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;
        $this->addSql($statsSql);

        $smsIdIdx = $this->generatePropertyName('sms_message_list_xref', 'idx', array('sms_id'));
        $leadlistIdIdx = $this->generatePropertyName('sms_message_list_xref', 'idx', array('leadlist_id'));
        $smsIdFk = $this->generatePropertyName('sms_message_list_xref', 'fk', array('sms_id'));
        $leadlistIdFk = $this->generatePropertyName('sms_message_list_xref', 'fk', array('leadlist_id'));

        $listXrefSql = <<<SQL
CREATE TABLE `{$this->prefix}sms_message_list_xref` (
  `sms_id` int(11) NOT NULL,
  `leadlist_id` int(11) NOT NULL,
  PRIMARY KEY (`sms_id`,`leadlist_id`),
  KEY `{$smsIdIdx}` (`sms_id`),
  KEY `{$leadlistIdIdx}` (`leadlist_id`),
  CONSTRAINT `{$smsIdFk}` FOREIGN KEY (`sms_id`) REFERENCES `mtc_sms_messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `{$leadlistIdFk}` FOREIGN KEY (`leadlist_id`) REFERENCES `mtc_lead_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;
        $this->addSql($listXrefSql);
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {

    }
}