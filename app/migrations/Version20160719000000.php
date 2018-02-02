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
 * Class Version20160719000000.
 */
class Version20160719000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'lead_devices')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $leadIdx = $this->generatePropertyName('lead_devices', 'idx', ['lead_id']);
        $sql     = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}lead_devices (
`id` int (11) NOT NULL AUTO_INCREMENT,
`lead_id` int (11) NOT NULL,
`client_info` LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)',
`device` VARCHAR (255) DEFAULT NULL,
`device_brand` VARCHAR (255) DEFAULT NULL,
`device_model` VARCHAR (255) DEFAULT NULL,
`device_os_name` VARCHAR (255) DEFAULT NULL,
`device_os_shortname` VARCHAR (255) DEFAULT NULL,
`device_os_version` VARCHAR (255) DEFAULT NULL,
`device_os_platform` VARCHAR (255) DEFAULT NULL,
`date_added` datetime NOT NULL COMMENT '(DC2Type:datetime)',
PRIMARY KEY (`id`),
KEY {$this->prefix}date_added_search (date_added),
KEY {$this->prefix}device_search (device),
KEY {$this->prefix}device_brand_search (device_brand),
KEY {$this->prefix}device_model_search (device_model),
KEY {$this->prefix}device_os_name_search (device_os_name),
KEY {$this->prefix}device_os_shortname_search (device_os_shortname),
KEY {$this->prefix}device_os_version_search (device_os_version),
KEY {$this->prefix}device_os_platform_search (device_os_platform),
KEY $leadIdx (lead_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);
        $this->addSql(
            "ALTER TABLE {$this->prefix}lead_devices ADD CONSTRAINT ".$this->generatePropertyName('lead_devices', 'fk', ['lead_id'])
            ." FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE"
        );

        $this->addSql("ALTER TABLE {$this->prefix}page_hits ADD COLUMN device_id INT DEFAULT NULL");
        $this->addSql('CREATE INDEX '.$this->generatePropertyName('page_hits', 'idx', ['device_id'])." ON {$this->prefix}page_hits (device_id)");

        $this->addSql(
            'ALTER TABLE '.$this->prefix.'page_hits ADD CONSTRAINT '.$this->generatePropertyName('page_hits', 'fk', ['device_id'])
            .' FOREIGN KEY (device_id) REFERENCES '.$this->prefix.'lead_devices (id) ON DELETE SET NULL'
        );

        $ipIdx     = $this->generatePropertyName('email_stats_devices', 'idx', ['ip_id']);
        $deviceIdx = $this->generatePropertyName('email_stats_devices', 'idx', ['device_id']);
        $statIdx   = $this->generatePropertyName('email_stats_devices', 'idx', ['stat_id']);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}email_stats_devices (
`id` int (11) NOT NULL AUTO_INCREMENT,
`ip_id` int( 11) DEFAULT NULL,
`device_id` int (11) DEFAULT NULL,
`stat_id` int (11) DEFAULT NULL,
`date_opened` datetime NOT NULL COMMENT '(DC2Type:datetime)',
PRIMARY KEY (`id`),
KEY {$this->prefix}date_opened_search (date_opened),
KEY $ipIdx (ip_id),
KEY $deviceIdx (device_id),
KEY $statIdx (stat_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);
        $this->addSql(
            'ALTER TABLE '.$this->prefix.'email_stats_devices ADD CONSTRAINT '.$this->generatePropertyName('email_stats_devices', 'fk', ['ip_id'])
            .' FOREIGN KEY (ip_id) REFERENCES '.$this->prefix.'ip_addresses (id)'
        );
        $this->addSql(
            'ALTER TABLE '.$this->prefix.'email_stats_devices ADD CONSTRAINT '.$this->generatePropertyName('email_stats_devices', 'fk', ['device_id'])
            .' FOREIGN KEY (device_id) REFERENCES '.$this->prefix.'lead_devices (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE '.$this->prefix.'email_stats_devices ADD CONSTRAINT '.$this->generatePropertyName('email_stats_devices', 'fk', ['stat_id'])
            .' FOREIGN KEY (stat_id) REFERENCES '.$this->prefix.'email_stats (id) ON DELETE CASCADE'
        );
    }
}
