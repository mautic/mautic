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

            $table->addIndex(['translation_parent_id'], 'IDX_SMS_TRANSLATION_PARENT');

            $table->addForeignKeyConstraint(
                $table,
                ['translation_parent_id'],
                ['id'],
                ['onDelete' => 'CASCADE'],
                'FK_SMS_TRANSLATION_PARENT'
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE '.$this->getPrefixedTableName().' DROP FOREIGN KEY FK_SMS_TRANSLATION_PARENT');

        $this->addSql('DROP INDEX IDX_SMS_TRANSLATION_PARENT ON '.$this->getPrefixedTableName());

        $this->addSql('ALTER TABLE '.$this->getPrefixedTableName().' DROP COLUMN translation_parent_id');
    }
}
