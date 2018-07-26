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
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160808000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'integration_entity')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}integration_entity (
	id INT AUTO_INCREMENT NOT NULL,
	integration VARCHAR(255) DEFAULT NULL,
	integration_entity VARCHAR(255) DEFAULT NULL,
	integration_entity_id VARCHAR(255) DEFAULT NULL,
	date_added DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
	last_sync_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
	internal_entity VARCHAR(255) DEFAULT NULL,
	internal_entity_id INT DEFAULT NULL,
	internal LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)',
	PRIMARY KEY(id),
	INDEX {$this->prefix}integration_external_entity (integration, integration_entity, integration_entity_id),
    INDEX {$this->prefix}integration_internal_entity (integration, internal_entity, internal_entity_id),
    INDEX {$this->prefix}integration_entity_match (integration, internal_entity, integration_entity),
    INDEX {$this->prefix}integration_last_sync_date (integration, last_sync_date)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);
    }
}
