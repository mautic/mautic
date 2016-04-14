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
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160414000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix . 'push_notifications')) {

            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {
        $mainTableSql = <<<SQL
CREATE TABLE `{$this->prefix}push_notifications` (
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
  `content` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `read_count` int(11) NOT NULL,
  `sent_count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1C66029812469DEN` (`category_id`),
  CONSTRAINT `FK_1C66029812469DEN` FOREIGN KEY (`category_id`) REFERENCES `mtc_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

        $this->addSql($mainTableSql);

        $statsSql = <<<SQL
CREATE TABLE `{$this->prefix}push_notification_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `list_id` int(11) DEFAULT NULL,
  `ip_id` int(11) DEFAULT NULL,
  `date_sent` datetime NOT NULL,
  `is_read` tinyint(1) NOT NULL,
  `date_read` datetime DEFAULT NULL,
  `tracking_hash` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `tokens` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `open_count` int(11) DEFAULT NULL,
  `last_opened` datetime DEFAULT NULL,
  `open_details` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_C67BEC72A832C1CN` (`notification_id`),
  KEY `IDX_C67BEC7255458N` (`lead_id`),
  KEY `IDX_C67BEC723DAE168N` (`list_id`),
  KEY `IDX_C67BEC72A03F5E9N` (`ip_id`),
  KEY `mtc_stat_notification_search` (`notification_id`,`lead_id`),
  KEY `mtc_stat_notification_read_search` (`is_read`),
  KEY `mtc_stat_notification_hash_search` (`tracking_hash`),
  KEY `mtc_stat_notification_source_search` (`source`,`source_id`),
  CONSTRAINT `FK_C67BEC723DAE168N` FOREIGN KEY (`list_id`) REFERENCES `mtc_lead_lists` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_C67BEC7255458N` FOREIGN KEY (`lead_id`) REFERENCES `mtc_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_C67BEC72A03F5E9N` FOREIGN KEY (`ip_id`) REFERENCES `mtc_ip_addresses` (`id`),
  CONSTRAINT `FK_C67BEC72A832C1CN` FOREIGN KEY (`notification_id`) REFERENCES `mtc_push_notifications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;
        $this->addSql($statsSql);

        $listXrefSql = <<<SQL
CREATE TABLE `{$this->prefix}push_notification_list_xref` (
  `notification_id` int(11) NOT NULL,
  `leadlist_id` int(11) NOT NULL,
  PRIMARY KEY (`notification_id`,`leadlist_id`),
  KEY `IDX_DB54D00DA832C1CN` (`notification_id`),
  KEY `IDX_DB54D00DB9FC887N` (`leadlist_id`),
  CONSTRAINT `FK_DB54D00DA832C1CN` FOREIGN KEY (`notification_id`) REFERENCES `mtc_push_notifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_DB54D00DB9FC887N` FOREIGN KEY (`leadlist_id`) REFERENCES `mtc_lead_lists` (`id`) ON DELETE CASCADE
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
