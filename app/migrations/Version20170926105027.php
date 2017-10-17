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
class Version20170926105027 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->getTable($this->prefix.'dynamic_content')->hasColumn('filters') ||
            $schema->getTable($this->prefix.'dynamic_content')->hasColumn('is_campaign_based') ||
            $schema->getTable($this->prefix.'dynamic_content')->hasColumn('slot_name')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE '.$this->prefix.'dynamic_content ADD COLUMN(filters LONGTEXT DEFAULT NULL  COMMENT \'(DC2Type:array)\', is_campaign_based TINYINT(1) DEFAULT 1 NOT NULL, slot_name VARCHAR(255) DEFAULT NULL)');
        $this->addSql("CREATE INDEX {$this->prefix}is_campaign_based_index ON {$this->prefix}dynamic_content (slot_name)");
        $this->addSql("CREATE INDEX {$this->prefix}slot_name_index ON {$this->prefix}dynamic_content (is_campaign_based)");
    }
}
