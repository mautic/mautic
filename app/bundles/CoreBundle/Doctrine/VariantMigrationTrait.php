<?php

namespace Mautic\CoreBundle\Doctrine;

use Doctrine\DBAL\Schema\Schema;

/**
 * @deprecated since Mautic 5.0, to be removed in 6.0 with no replacement.
 */
trait VariantMigrationTrait
{
    /**
     * Add variant parent/child relationship schema.
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
