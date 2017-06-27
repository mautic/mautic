<?php

/*
 * @package     Mautic
 * @copyright   2017 Mautic Contributors. All rights reserved.
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
class Version20170517091309 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'lead_event_log')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
CREATE TABLE {$this->prefix}lead_event_log (
    id INT AUTO_INCREMENT NOT NULL,
    lead_id INT DEFAULT NULL,
    user_id INT DEFAULT NULL,
    user_name VARCHAR(255) DEFAULT NULL,
    bundle VARCHAR(255) DEFAULT NULL,
    object VARCHAR(255) DEFAULT NULL,
    action VARCHAR(255) DEFAULT NULL,
    object_id INT DEFAULT NULL,
    date_added DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
    properties LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)',
    INDEX {$this->prefix}lead_id_index (lead_id),
    INDEX {$this->prefix}lead_object_index (object, object_id),
    INDEX {$this->prefix}lead_timeline_index (bundle, object, action, object_id),
    INDEX {$this->prefix}lead_date_added_index (date_added),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;

        $this->addSql($sql);

        $fk = $this->generatePropertyName('lead_event_log', 'fk', ['lead_id']);
        $this->addSql("ALTER TABLE {$this->prefix}lead_event_log ADD CONSTRAINT {$fk} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE SET NULL;");
    }
}
