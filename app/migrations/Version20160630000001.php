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
 * Class Version20160630000001.
 */
class Version20160630000001 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $table = $schema->getTable($this->prefix.'leads');
        if ($table->hasIndex($this->generatePropertyName('leads', 'idx', ['stage_id']))) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $stageFk  = $this->generatePropertyName('leads', 'fk', ['stage_id']);
        $stageIdx = $this->generatePropertyName('leads', 'idx', ['stage_id']);

        $this->addSql("ALTER TABLE {$this->prefix}leads ADD CONSTRAINT {$stageFk} FOREIGN KEY (stage_id) REFERENCES {$this->prefix}stages (id) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX $stageIdx ON {$this->prefix}leads (stage_id)");
    }
}
