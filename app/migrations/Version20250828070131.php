<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250828070131 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'sms_messages';

    protected function preUpAssertions(): void
    {
        $column = 'translation_parent_id';
        $this->skipAssertion(fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->hasColumn($column), "Column {$this->prefix}sms_messages.{$column} already exists");
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());

        // translation_parent_id
        if (!$table->hasColumn('translation_parent_id')) {
            $table->addColumn('translation_parent_id', 'integer', [
                'unsigned' => true,
                'notnull'  => false,
            ]);

            $table->addIndex(['translation_parent_id'], $this->generatePropertyName(self::TABLE_NAME, 'idx', ['translation_parent_id']));

            $table->addForeignKeyConstraint(
                $table,
                ['translation_parent_id'],
                ['id'],
                ['onDelete' => 'CASCADE'],
                $this->generatePropertyName(self::TABLE_NAME, 'fk', ['translation_parent_id'])
            );
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());
        foreach ([$this->generatePropertyName(self::TABLE_NAME, 'fk', ['translation_parent_id']), 'FK_SMS_TRANSLATION_PARENT'] as $name) {
            if ($table->hasForeignKey($name)) {
                $table->removeForeignKey($name);
            }
        }
        foreach ([$this->generatePropertyName(self::TABLE_NAME, 'idx', ['translation_parent_id']), 'IDX_SMS_TRANSLATION_PARENT'] as $name) {
            if ($table->hasIndex($name)) {
                $table->dropIndex($name);
            }
        }
        $table->dropColumn('translation_parent_id');
    }
}
