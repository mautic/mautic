<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * SMS Channel Migration.
 */
class Version20160420000000 extends AbstractMauticMigration
{
    private $keys;

    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'sms_messages')) {
            throw new SkipMigrationException('Schema includes this migration');
        }

        $this->keys = [
            'sms_messages' => [
                'idx' => [
                    'category' => $this->generatePropertyName('sms_messages', 'idx', ['category_id']),
                ],
                'fk' => [
                    'category' => $this->generatePropertyName('sms_messages', 'fk', ['category_id']),
                ],
            ],
            'sms_message_stats' => [
                'idx' => [
                    'sms'  => $this->generatePropertyName('sms_message_stats', 'idx', ['sms_id']),
                    'lead' => $this->generatePropertyName('sms_message_stats', 'idx', ['lead_id']),
                    'list' => $this->generatePropertyName('sms_message_stats', 'idx', ['list_id']),
                    'ip'   => $this->generatePropertyName('sms_message_stats', 'idx', ['ip_id']),
                ],
                'fk' => [
                    'sms'  => $this->generatePropertyName('sms_message_stats', 'fk', ['sms_id']),
                    'lead' => $this->generatePropertyName('sms_message_stats', 'fk', ['lead_id']),
                    'list' => $this->generatePropertyName('sms_message_stats', 'fk', ['list_id']),
                    'ip'   => $this->generatePropertyName('sms_message_stats', 'fk', ['ip_id']),
                ],
            ],
            'sms_message_list_xref' => [
                'idx' => [
                    'sms'      => $this->generatePropertyName('sms_message_list_xref', 'idx', ['sms_id']),
                    'leadlist' => $this->generatePropertyName('sms_message_list_xref', 'idx', ['leadlist_id']),
                ],
                'fk' => [
                    'sms'      => $this->generatePropertyName('sms_message_list_xref', 'fk', ['sms_id']),
                    'leadlist' => $this->generatePropertyName('sms_message_list_xref', 'fk', ['leadlist_id']),
                ],
            ],
        ];
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $mainTableSql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$this->prefix}sms_messages` (
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
  KEY `{$this->keys['sms_messages']['idx']['category']}` (`category_id`),
  CONSTRAINT `{$this->keys['sms_messages']['fk']['category']}` FOREIGN KEY (`category_id`) REFERENCES `{$this->prefix}categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

        $this->addSql($mainTableSql);

        $statsSql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$this->prefix}sms_message_stats` (
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
  KEY `{$this->keys['sms_message_stats']['idx']['sms']}` (`sms_id`),
  KEY `{$this->keys['sms_message_stats']['idx']['lead']}` (`lead_id`),
  KEY `{$this->keys['sms_message_stats']['idx']['list']}` (`list_id`),
  KEY `{$this->keys['sms_message_stats']['idx']['ip']}` (`ip_id`),
  KEY `{$this->prefix}stat_sms_search` (`sms_id`,`lead_id`),
  KEY `{$this->prefix}stat_sms_hash_search` (`tracking_hash`),
  KEY `{$this->prefix}stat_sms_source_search` (`source`,`source_id`),
  CONSTRAINT `{$this->keys['sms_message_stats']['fk']['list']}` FOREIGN KEY (`list_id`) REFERENCES `{$this->prefix}lead_lists` (`id`) ON DELETE SET NULL,
  CONSTRAINT `{$this->keys['sms_message_stats']['fk']['lead']}` FOREIGN KEY (`lead_id`) REFERENCES `{$this->prefix}leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `{$this->keys['sms_message_stats']['fk']['ip']}` FOREIGN KEY (`ip_id`) REFERENCES `{$this->prefix}ip_addresses` (`id`),
  CONSTRAINT `{$this->keys['sms_message_stats']['fk']['sms']}` FOREIGN KEY (`sms_id`) REFERENCES `{$this->prefix}sms_messages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;
        $this->addSql($statsSql);

        $listXrefSql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$this->prefix}sms_message_list_xref` (
  `sms_id` int(11) NOT NULL,
  `leadlist_id` int(11) NOT NULL,
  PRIMARY KEY (`sms_id`,`leadlist_id`),
  KEY `{$this->keys['sms_message_list_xref']['idx']['sms']}` (`sms_id`),
  KEY `{$this->keys['sms_message_list_xref']['idx']['leadlist']}` (`leadlist_id`),
  CONSTRAINT `{$this->keys['sms_message_list_xref']['idx']['sms']}` FOREIGN KEY (`sms_id`) REFERENCES `{$this->prefix}sms_messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `{$this->keys['sms_message_list_xref']['idx']['leadlist']}` FOREIGN KEY (`leadlist_id`) REFERENCES `{$this->prefix}lead_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;
        $this->addSql($listXrefSql);
    }
}
