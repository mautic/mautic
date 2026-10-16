<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\FormBundle\Entity\Form;

final class Version20250127092200 extends PreUpAssertionMigration
{
    private const TRANSLATION_PARENT_ID = 'translation_parent_id';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName(Form::ENTITY_NAME))->hasColumn(self::TRANSLATION_PARENT_ID),
            'Column translation_parent_id already exists in forms table'
        );
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName(Form::ENTITY_NAME));

        $table->addColumn(self::TRANSLATION_PARENT_ID, 'integer', [
            'unsigned' => true,
            'notnull'  => false,
        ]);

        $table->addForeignKeyConstraint(
            $this->getPrefixedTableName(Form::ENTITY_NAME),
            [self::TRANSLATION_PARENT_ID],
            ['id'],
            ['onDelete' => 'CASCADE']
        );

        $table->addIndex(
            [self::TRANSLATION_PARENT_ID],
            $this->generatePropertyName(Form::ENTITY_NAME, 'idx', [self::TRANSLATION_PARENT_ID])
        );
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName(Form::ENTITY_NAME));

        $foreignKeys = $table->getForeignKeys();
        foreach ($foreignKeys as $foreignKey) {
            if (in_array('translation_parent_id', $foreignKey->getLocalColumns())) {
                $table->removeForeignKey($foreignKey->getName());
                break;
            }
        }

        $indexName = $this->generatePropertyName(Form::ENTITY_NAME, 'idx', [self::TRANSLATION_PARENT_ID]);

        if ($table->hasIndex($indexName)) {
            $table->dropIndex($indexName);
        }

        $table->dropColumn(self::TRANSLATION_PARENT_ID);
    }
}
