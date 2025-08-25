<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250127092200 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'forms';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME))->hasColumn('translation_parent_id'),
            'Column translation_parent_id already exists in forms table'
        );
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME));
        
        // Add translation_parent_id column
        $table->addColumn('translation_parent_id', 'integer', [
            'unsigned' => true,
            'notnull' => false,
        ]);

        // Add foreign key constraint
        $table->addForeignKeyConstraint(
            $this->getPrefixedTableName(self::TABLE_NAME),
            ['translation_parent_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );

        // Add index on translation_parent_id for performance
        $table->addIndex(['translation_parent_id'], 'idx_forms_translation_parent_id');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME));
        
        // Drop foreign key constraint
        $foreignKeys = $table->getForeignKeys();
        foreach ($foreignKeys as $foreignKey) {
            if (in_array('translation_parent_id', $foreignKey->getLocalColumns())) {
                $table->removeForeignKey($foreignKey->getName());
                break;
            }
        }
        
        // Drop index
        if ($table->hasIndex('idx_forms_translation_parent_id')) {
            $table->dropIndex('idx_forms_translation_parent_id');
        }
        
        // Drop column
        $table->dropColumn('translation_parent_id');
    }
}
