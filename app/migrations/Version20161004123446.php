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
 * This migration file adds stage_id to the lead_stages_change_log as a foreign key.
 */
class Version20161004123446 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->getTable($this->prefix.'lead_stages_change_log')->hasColumn('stage_id')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}lead_stages_change_log ADD stage_id INT(11) NULL DEFAULT NULL;");
        $stageFk  = $this->generatePropertyName('lead_stages_change_log', 'fk', ['stage_id']);
        $stageIdx = $this->generatePropertyName('lead_stages_change_log', 'idx', ['stage_id']);
        $this->addSql("ALTER TABLE {$this->prefix}lead_stages_change_log ADD CONSTRAINT $stageFk FOREIGN KEY (stage_id) REFERENCES {$this->prefix}stages (id) ON DELETE CASCADE");
        $this->addSql("CREATE INDEX $stageIdx ON {$this->prefix}lead_stages_change_log (stage_id)");
    }
}
