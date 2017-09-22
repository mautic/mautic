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
 * Class Version20160630000002.
 */
class Version20160630000002 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $table = $schema->getTable($this->prefix.'stages');
        if ($table->hasIndex($this->generatePropertyName('stages', 'idx', ['category_id']))) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $fk  = $this->generatePropertyName('stages', 'fk', ['category_id']);
        $idx = $this->generatePropertyName('stages', 'idx', ['category_id']);

        $this->addSql("ALTER TABLE {$this->prefix}stages ADD CONSTRAINT {$fk} FOREIGN KEY (category_id) REFERENCES {$this->prefix}categories (id) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX {$idx} ON {$this->prefix}stages (category_id)");
    }
}
