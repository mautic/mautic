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
        if ($schema->hasTable($this->prefix.'lead_devices')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}page_hits ADD COLUMN device_id int DEFAULT NULL");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}lead_devices (
`id` int (11) NOT NULL AUTO_INCREMENT,
`lead_id` int (11) NOT NULL,
`client_info` longtext DEFAULT NULL,
`device` VARCHAR (255) DEFAULT NULL,
`device_brand` VARCHAR (255) DEFAULT NULL,
`device_model` VARCHAR (255) DEFAULT NULL,
`device_os_name` VARCHAR (255) DEFAULT NULL,
`device_os_shortname` VARCHAR (255) DEFAULT NULL,
`device_os_version` VARCHAR (255) DEFAULT NULL,
`device_os_platform` VARCHAR (255) DEFAULT NULL,
`date_added` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);

        $this->addSql('ALTER TABLE ' . $this->prefix . 'page_hits ADD CONSTRAINT ' . $this->generatePropertyName('page_hits', 'fk', array('device_id')) . ' FOREIGN KEY (device_id) REFERENCES ' . $this->prefix . 'lead_devices (id)');

        $sql = <<<SQL
CREATE TABLE {$this->prefix}email_stats_devices (
`id` int (11) NOT NULL AUTO_INCREMENT,
`ip_id` int( 11) DEFAULT NULL,
`device_id` int (11) DEFAULT NULL,
`stat_id` int (11) DEFAULT NULL,
`date_opened` datetime DEFAULT NULL,
PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats_devices ADD CONSTRAINT ' . $this->generatePropertyName('email_stats_devices', 'fk', array('ip_id')) . ' FOREIGN KEY (ip_id) REFERENCES ' . $this->prefix . 'ip_addresses (id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats_devices ADD CONSTRAINT ' . $this->generatePropertyName('email_stats_devices', 'fk', array('device_id')) . ' FOREIGN KEY (device_id) REFERENCES ' . $this->prefix . 'lead_devices (id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'email_stats_devices ADD CONSTRAINT ' . $this->generatePropertyName('email_stats_devices', 'fk', array('stat_id')) . ' FOREIGN KEY (stat_id) REFERENCES ' . $this->prefix . 'email_stats (id)');
    }
}