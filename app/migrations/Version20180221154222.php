<?php

/*
 * @package     Mautic
 * @copyright   2018 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\SkipMigrationException;

/**
 * Convert attribution field from a double float to a numeric/decimal for accurate currency storage.
 */
class Version20180221154222 extends AbstractMauticMigration
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
        if (
            $table->getColumn('attribution')->getScale() == 4 &&
            $table->getColumn('attribution')->getPrecision() == 19 &&
            $table->getColumn('attribution')->getType()->getName() == 'decimal'
        ) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("ALTER TABLE {$this->prefix}leads CHANGE attribution attribution NUMERIC(19, 4) DEFAULT NULL");
    }
}
