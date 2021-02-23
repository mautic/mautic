<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20210223174702 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $idxName = $this->getIdxName();
        if ($schema->getTable($this->prefix.'lead_lists')->hasIndex($idxName)) {
            // The category_id column is assumed to have been created with the foreign key and index by Version20210104171005
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        // Fix bad migration from 3.3.0
        $fkName  = $this->getFkName();
        $idxName = $this->getIdxName();
        $table   = $schema->getTable($this->prefix.'lead_lists');

        // fk and idx names may be different based on the table name so remove hard coded names in favor of what Doctrine would dynamically generate
        $oldFkName = 'FK_6EC1522A12469DE2';
        if ($oldFkName !== $fkName && $table->hasForeignKey($oldFkName)) {
            $this->addSql("ALTER TABLE {$this->prefix}lead_lists DROP FOREIGN KEY $oldFkName");
        }

        $oldIdxName = 'IDX_6EC1522A12469DE2';
        if ($oldIdxName !== $idxName && $table->hasIndex($idxName)) {
            $this->addSql("ALTER TABLE {$this->prefix}lead_lists DROP INDEX $oldIdxName");
        }

        $catTable         = $schema->getTable("{$this->prefix}categories");
        $categoryIdColumn = $catTable->getColumn('id');

        // Add the new column if it failed for any reason
        if (!$table->hasColumn('category_id')) {
            if ($categoryIdColumn->getUnsigned()) {
                $this->addSql("ALTER TABLE {$this->prefix}lead_lists ADD category_id INT UNSIGNED DEFAULT NULL");
            } else {
                $this->addSql("ALTER TABLE {$this->prefix}lead_lists ADD category_id INT DEFAULT NULL");
            }
        } else {
            // The column was already added by the faulty migration, update it based on the type of the `category`.`id` column
            if ($categoryIdColumn->getUnsigned()) {
                $this->addSql("ALTER TABLE {$this->prefix}lead_lists CHANGE category_id category_id INT UNSIGNED DEFAULT NULL");
            } else {
                $this->addSql("ALTER TABLE {$this->prefix}lead_lists CHANGE category_id category_id INT DEFAULT NULL");
            }
        }

        // Add the foreign key if it was removed above and/or failed to create due to M2 schema
        if (!$table->hasForeignKey($fkName)) {
            $this->addSql(
                "ALTER TABLE {$this->prefix}lead_lists ADD CONSTRAINT $fkName FOREIGN KEY (category_id) REFERENCES {$this->prefix}categories (id) ON DELETE SET NULL"
            );
        }

        // Add the index if it was removed above and/or failed to create due to M2 schema
        if (!$table->hasIndex($idxName)) {
            $this->addSql("CREATE INDEX $idxName ON {$this->prefix}lead_lists (category_id)");
        }
    }

    private function getFkName(): string
    {
        return $this->generatePropertyName('lead_lists', 'fk', ['category_id']);
    }

    private function getIdxName(): string
    {
        return $this->generatePropertyName('lead_lists', 'idx', ['category_id']);
    }
}
