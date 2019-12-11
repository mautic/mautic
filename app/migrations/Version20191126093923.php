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
 * Removing 2 columns that were copied from other similar part of Mautic and never used.
 */
class Version20191126093923 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $table = $schema->getTable($this->prefix.'companies_leads');

        if (!$table->hasColumn('manually_added')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}companies_leads DROP manually_added");
        $this->addSql("ALTER TABLE {$this->prefix}companies_leads DROP manually_removed");
    }
}
