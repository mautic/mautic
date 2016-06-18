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

        $this->keys = array(
            'sms_messages'          => array(
                'idx' => array(
                    'category' => $this->generatePropertyName('sms_messages', 'idx', array('category_id'))
                ),
                'fk'  => array(
                    'category' => $this->generatePropertyName('sms_messages', 'fk', array('category_id'))
                )
            ),
            'sms_message_stats'     => array(
                'idx' => array(
                    'sms'  => $this->generatePropertyName('sms_message_stats', 'idx', array('sms_id')),
                    'lead' => $this->generatePropertyName('sms_message_stats', 'idx', array('lead_id')),
                    'list' => $this->generatePropertyName('sms_message_stats', 'idx', array('list_id')),
                    'ip'   => $this->generatePropertyName('sms_message_stats', 'idx', array('ip_id'))
                ),
                'fk'  => array(
                    'sms'  => $this->generatePropertyName('sms_message_stats', 'fk', array('sms_id')),
                    'lead' => $this->generatePropertyName('sms_message_stats', 'fk', array('lead_id')),
                    'list' => $this->generatePropertyName('sms_message_stats', 'fk', array('list_id')),
                    'ip'   => $this->generatePropertyName('sms_message_stats', 'fk', array('ip_id'))
                )
            ),
            'sms_message_list_xref' => array(
                'idx' => array(
                    'sms'      => $this->generatePropertyName('sms_message_list_xref', 'idx', array('sms_id')),
                    'leadlist' => $this->generatePropertyName('sms_message_list_xref', 'idx', array('leadlist_id'))
                ),
                'fk'  => array(
                    'sms'      => $this->generatePropertyName('sms_message_list_xref', 'fk', array('sms_id')),
                    'leadlist' => $this->generatePropertyName('sms_message_list_xref', 'fk', array('leadlist_id'))
                )
            )
        );
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {
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
  KEY `{$this->keys['sms_messages']['idx']['category']}` (`category_id`),
  CONSTRAINT `{$this->keys['sms_messages']['fk']['category']}` FOREIGN KEY (`category_id`) REFERENCES `{$this->prefix}categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

        $this->addSql($mainTableSql);

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
CREATE TABLE `{$this->prefix}sms_message_list_xref` (
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

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        $this->addSql("CREATE SEQUENCE {$this->prefix}sms_messages_id_seq INCREMENT BY 1 MINVALUE 1 START 1");
        $this->addSql("CREATE SEQUENCE {$this->prefix}sms_message_stats_id_seq INCREMENT BY 1 MINVALUE 1 START 1");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}sms_messages (
  id INT NOT NULL, category_id INT DEFAULT NULL, 
  is_published BOOLEAN NOT NULL, 
  date_added TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
  created_by INT DEFAULT NULL, 
  created_by_user VARCHAR(255) DEFAULT NULL, 
  date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
  modified_by INT DEFAULT NULL, 
  modified_by_user VARCHAR(255) DEFAULT NULL, 
  checked_out TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
  checked_out_by INT DEFAULT NULL, 
  checked_out_by_user VARCHAR(255) DEFAULT NULL, 
  name VARCHAR(255) NOT NULL, 
  description TEXT DEFAULT NULL, 
  lang VARCHAR(255) NOT NULL, 
  message TEXT NOT NULL, 
  sms_type TEXT DEFAULT NULL, 
  publish_up TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
  publish_down TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
  sent_count INT NOT NULL, 
  PRIMARY KEY(id)
)
SQL;
        $this->addSql($sql);
        $this->addSql("CREATE INDEX {$this->keys['sms_messages']['idx']['category']} ON {$this->prefix}sms_messages (category_id)");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}sms_messages.date_added IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}sms_messages.date_modified IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}sms_messages.checked_out IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}sms_messages.publish_up IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}sms_messages.publish_down IS '(DC2Type:datetime)'");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}sms_message_list_xref (
  sms_id INT NOT NULL, 
  leadlist_id INT NOT NULL, 
  PRIMARY KEY(sms_id, leadlist_id)
)
SQL;
        $this->addSql($sql);
        $this->addSql("CREATE INDEX {$this->keys['sms_message_list_xref']['idx']['sms']} ON {$this->prefix}sms_message_list_xref (sms_id)");
        $this->addSql("CREATE INDEX {$this->keys['sms_message_list_xref']['idx']['leadlist']} ON {$this->prefix}sms_message_list_xref (leadlist_id)");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}sms_message_stats (
  id INT NOT NULL, 
  sms_id INT DEFAULT NULL, 
  lead_id INT DEFAULT NULL, 
  list_id INT DEFAULT NULL, 
  ip_id INT DEFAULT NULL, 
  date_sent TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
  tracking_hash VARCHAR(255) DEFAULT NULL, 
  source VARCHAR(255) DEFAULT NULL, 
  source_id INT DEFAULT NULL, 
  tokens TEXT DEFAULT NULL, 
  PRIMARY KEY(id)
)
SQL;
        $this->addSql($sql);
        $this->addSql("CREATE INDEX {$this->keys['sms_message_stats']['idx']['sms']} ON {$this->prefix}sms_message_stats (sms_id)");
        $this->addSql("CREATE INDEX {$this->keys['sms_message_stats']['idx']['lead']} ON {$this->prefix}sms_message_stats (lead_id)");
        $this->addSql("CREATE INDEX {$this->keys['sms_message_stats']['idx']['list']} ON {$this->prefix}sms_message_stats (list_id)");
        $this->addSql("CREATE INDEX {$this->keys['sms_message_stats']['idx']['ip']} ON {$this->prefix}sms_message_stats (ip_id)");
        $this->addSql("CREATE INDEX {$this->prefix}stat_sms_search ON {$this->prefix}sms_message_stats (sms_id, lead_id)");
        $this->addSql("CREATE INDEX {$this->prefix}stat_sms_hash_search ON {$this->prefix}sms_message_stats (tracking_hash)");
        $this->addSql("CREATE INDEX {$this->prefix}stat_sms_source_search ON {$this->prefix}sms_message_stats (source, source_id)");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}sms_message_stats.date_sent IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}sms_message_stats.tokens IS '(DC2Type:array)'");

        $this->addSql("ALTER TABLE {$this->prefix}sms_messages ADD CONSTRAINT {$this->keys['sms_messages']['fk']['category']} FOREIGN KEY (category_id) REFERENCES {$this->prefix}categories (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}sms_message_list_xref ADD CONSTRAINT {$this->keys['sms_message_list_xref']['fk']['sms']} FOREIGN KEY (sms_id) REFERENCES {$this->prefix}sms_messages (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}sms_message_list_xref ADD CONSTRAINT {$this->keys['sms_message_list_xref']['fk']['leadlist']} FOREIGN KEY (leadlist_id) REFERENCES {$this->prefix}lead_lists (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}sms_message_stats ADD CONSTRAINT {$this->keys['sms_message_stats']['fk']['sms']} FOREIGN KEY (sms_id) REFERENCES {$this->prefix}sms_messages (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}sms_message_stats ADD CONSTRAINT {$this->keys['sms_message_stats']['fk']['lead']} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}sms_message_stats ADD CONSTRAINT {$this->keys['sms_message_stats']['fk']['list']} FOREIGN KEY (list_id) REFERENCES {$this->prefix}lead_lists (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}sms_message_stats ADD CONSTRAINT {$this->keys['sms_message_stats']['fk']['ip']} FOREIGN KEY (ip_id) REFERENCES {$this->prefix}ip_addresses (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
    }
}