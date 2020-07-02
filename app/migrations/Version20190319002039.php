<?php

/*
 * @package     Mautic
 * @copyright   2019 Mautic Contributors. All rights reserved.
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
class Version20190319002039 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'point_lead_action_log');

        if ($table->hasColumn('internal_id')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}point_lead_action_log ADD internal_id VARCHAR(255) DEFAULT NULL");
        $this->addSql('ALTER TABLE '.$this->prefix.'`point_lead_action_log`
	DROP PRIMARY KEY,
	ADD PRIMARY KEY (`point_id`, `lead_id`, `internal_id`)');

        $this->addSql("CREATE INDEX {$this->prefix}internal_id ON {$this->prefix}point_lead_action_log (internal_id)");
    }
}
