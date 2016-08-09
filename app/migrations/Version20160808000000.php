<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
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
CREATE TABLE {$this->prefix}integration_entity (
	id INT AUTO_INCREMENT NOT NULL,
	integration VARCHAR(255) DEFAULT NULL,
	integration_entity VARCHAR(255) DEFAULT NULL,
	integration_entity_id VARCHAR(255) DEFAULT NULL,
	date_added DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
	last_sync_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
	internal_entity VARCHAR(255) DEFAULT NULL,
	internal_entity_id INT DEFAULT NULL,
	internal LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)',
	PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);

    }
}