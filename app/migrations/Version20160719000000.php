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
 * Class Version20160719000000
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
        if ($schema->hasTable($this->prefix.'lead_stats_devices')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}page_hits ADD COLUMN devicestat_id int DEFAULT NULL");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}lead_stats_devices (
`id` int (11) NOT NULL AUTO_INCREMENT,
`lead_id` int (11) NOT NULL,
`ip_id` int( 11) DEFAULT NULL,
`stat_id` int (11) DEFAULT NULL,
`channel` VARCHAR (255) DEFAULT NULL,
`channel_id` int (11) DEFAULT NULL,
`client_info` longtext DEFAULT NULL,
`device` VARCHAR (255) DEFAULT NULL,
`device_brand` VARCHAR (255) DEFAULT NULL,
`device_model` VARCHAR (255) DEFAULT NULL,
`device_os_name` VARCHAR (255) DEFAULT NULL,
`device_os_shortname` VARCHAR (255) DEFAULT NULL,
`device_os_version` VARCHAR (255) DEFAULT NULL,
`device_os_platform` VARCHAR (255) DEFAULT NULL,
`date_opened` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_stats_devices ADD CONSTRAINT ' . $this->generatePropertyName('lead_stats_devices', 'fk', array('ip_id')) . ' FOREIGN KEY (ip_id) REFERENCES ' . $this->prefix . 'ip_addresses (id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'page_hits ADD CONSTRAINT ' . $this->generatePropertyName('page_hits', 'fk', array('devicestat_id')) . ' FOREIGN KEY (devicestat_id) REFERENCES ' . $this->prefix . 'lead_stats_devices (id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'page_hits CREATE UNIQUE INDEX ' .$this->generatePropertyName('page_hits', 'uniq', ['devicestat_id']));
    }
}