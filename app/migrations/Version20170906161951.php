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
class Version20170906161951 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable(MAUTIC_TABLE_PREFIX.'contact_merge_records')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $idx = $this->generatePropertyName('contact_merge_records', 'idx', ['contact_id']);
        $fk  = $this->generatePropertyName('contact_merge_records', 'fk', ['contact_id']);

        $sql = <<<SQL
CREATE TABLE {$this->prefix}contact_merge_records (
    id INT AUTO_INCREMENT NOT NULL, 
    contact_id INT NOT NULL, 
    date_added DATETIME NOT NULL COMMENT '(DC2Type:datetime)', 
    merged_id INT NOT NULL, 
    name VARCHAR(255) NOT NULL, 
    INDEX $idx (contact_id), 
    INDEX {$this->prefix}contact_merge_date_added (date_added), INDEX {$this->prefix}contact_merge_ids (merged_id), 
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;
        $this->addSql($sql);

        $this->addSql("ALTER TABLE {$this->prefix}contact_merge_records ADD CONSTRAINT $fk FOREIGN KEY (contact_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE");
    }
}
