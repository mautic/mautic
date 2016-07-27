<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine;

use Doctrine\DBAL\Schema\Schema;

/**
 * Class TranslationMigrationTrait
 */
trait TranslationMigrationTrait
{

    /**
     * Add translation parent/child relationship columns
     *
     * @param Schema $schema
     */
    protected function addTranslationSchema(Schema $schema, $tableName, $languageColumnName = 'lang')
    {
        $fkName  = $this->generatePropertyName($tableName, 'fk', ['translation_parent_id']);
        $idxName = $this->generatePropertyName($tableName, 'idx', ['translation_parent_id']);
        $table   = $schema->getTable($this->prefix.$tableName);

        if (!$table->hasColumn('translation_parent_id')) {
            $this->addSql("ALTER TABLE {$this->prefix}$tableName ADD translation_parent_id INT DEFAULT NULL");
            $this->addSql(
                "ALTER TABLE {$this->prefix}$tableName ADD CONSTRAINT ".$fkName
                ." FOREIGN KEY (translation_parent_id) REFERENCES {$this->prefix}$tableName (id) ON DELETE CASCADE"
            );
            $this->addSql( "CREATE INDEX $idxName ON {$this->prefix}$tableName (translation_parent_id)");
        } else {
            // Drop and recreate the parent FK to ensure DELETE CASCADE
            if ($table->hasForeignKey($fkName)) {
                $this->addSql("ALTER TABLE {$this->prefix}$tableName DROP FOREIGN KEY $fkName");
            }
            $this->addSql(
                "ALTER TABLE {$this->prefix}$tableName ADD CONSTRAINT ".$fkName
                ." FOREIGN KEY (translation_parent_id) REFERENCES {$this->prefix}$tableName (id) ON DELETE CASCADE"
            );

            if (!$table->hasIndex($idxName)) {
                $this->addSql( "CREATE INDEX $idxName ON {$this->prefix}$tableName (translation_parent_id)");
            }
        }

        if ($languageColumnName && !$table->hasColumn($languageColumnName)) {
            $this->addSql("ALTER TABLE {$this->prefix}$tableName ADD COLUMN `lang` varchar(255) NULL");
        }
    }
}