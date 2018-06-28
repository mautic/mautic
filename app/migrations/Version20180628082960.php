<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
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
 * 1.1.3 - 1.1.4.
 *
 * Class Version20180628082960
 */
class Version20180628082960 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        // Test to see if this migration has already been applied
        $eventTable = $schema->getTable($this->prefix.'campaign_events');
        if ($eventTable->hasColumn('is_published')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->getTable($this->prefix.'campaign_events');
        if (!$table->hasColumn('is_published')) {
            $this->addSql('ALTER TABLE '.$this->prefix.'campaign_events ADD COLUMN is_published bool DEFAULT 1');
        }
    }
}
