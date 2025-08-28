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
        foreach (['translation_parent_id', 'variant_parent_id'] as $column) {
            $this->skipAssertion(fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->hasColumn($column), "Column {$this->prefix}sms_messages.{$column} already exists");
        }
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());

        // translation_parent_id
        if (!$table->hasColumn('translation_parent_id')) {
            $table->addColumn('translation_parent_id', 'integer', [
                'unsigned' => true,
                'notnull' => false,
            ]);
            $table->addIndex(['translation_parent_id'], 'IDX_SMS_TRANSLATION_PARENT');
            $table->addForeignKeyConstraint(
                $table, // self-reference to sms_messages
                ['translation_parent_id'],
                ['id'],
                ['onDelete' => 'CASCADE'],
                'FK_SMS_TRANSLATION_PARENT'
            );
        }

        // variant_parent_id
        if (!$table->hasColumn('variant_parent_id')) {
            $table->addColumn('variant_parent_id', 'integer', [
                'unsigned' => true,
                'notnull' => false,
            ]);
            $table->addIndex(['variant_parent_id'], 'IDX_SMS_VARIANT_PARENT');
            $table->addForeignKeyConstraint(
                $table,
                ['variant_parent_id'],
                ['id'],
                ['onDelete' => 'CASCADE'],
                'FK_SMS_VARIANT_PARENT'
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE '.$this->getPrefixedTableName().' DROP FOREIGN KEY FK_SMS_TRANSLATION_PARENT');
        $this->addSql('ALTER TABLE '.$this->getPrefixedTableName().' DROP FOREIGN KEY FK_SMS_VARIANT_PARENT');

        $this->addSql('DROP INDEX IDX_SMS_TRANSLATION_PARENT ON '.$this->getPrefixedTableName());
        $this->addSql('DROP INDEX IDX_SMS_VARIANT_PARENT ON '.$this->getPrefixedTableName());

        $this->addSql('ALTER TABLE '.$this->getPrefixedTableName().' DROP COLUMN translation_parent_id, DROP COLUMN variant_parent_id');
    }
}
