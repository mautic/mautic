<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine;

use Doctrine\DBAL\Schema\Schema;

/**
 * Class VariantMigrationTrait.
 */
trait VariantMigrationTrait
{
    /**
     * Add variant parent/child relationship schema.
     *
     * @param Schema $schema
     * @param        $tableName
     */
    protected function addVariantSchema(Schema $schema, $tableName)
    {
        $fkName    = $this->generatePropertyName($tableName, 'fk', ['variant_parent_id']);
        $idxName   = $this->generatePropertyName($tableName, 'idx', ['variant_parent_id']);
        $tableName = "{$this->prefix}$tableName";
        $table     = $schema->getTable($tableName);

        if (!$table->hasColumn('variant_parent_id')) {
            $this->addSql("ALTER TABLE $tableName ADD variant_parent_id INT DEFAULT NULL");
            $this->addSql(
                "ALTER TABLE $tableName ADD CONSTRAINT ".$fkName
                ." FOREIGN KEY (variant_parent_id) REFERENCES $tableName (id) ON DELETE CASCADE"
            );
            $this->addSql("CREATE INDEX $idxName ON $tableName (variant_parent_id)");
        } else {
            // Drop and recreate the parent FK to ensure DELETE CASCADE
            if ($table->hasForeignKey($fkName)) {
                $this->addSql("ALTER TABLE $tableName DROP FOREIGN KEY $fkName");
            }
            $this->addSql(
                "ALTER TABLE $tableName ADD CONSTRAINT ".$fkName
                ." FOREIGN KEY (variant_parent_id) REFERENCES $tableName (id) ON DELETE CASCADE"
            );

            if (!$table->hasIndex($idxName)) {
                $this->addSql("CREATE INDEX $idxName ON $tableName (variant_parent_id)");
            }
        }

        if (!$table->hasColumn('variant_settings')) {
            $this->addSql(
                "ALTER TABLE $tableName ADD variant_settings LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', ADD variant_start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)'"
            );
        }
    }
}
