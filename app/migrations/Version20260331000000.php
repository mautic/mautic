<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260331000000 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'emails';
    private const COLUMN_NAME  = 'resend_of_id';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->hasColumn(self::COLUMN_NAME),
            sprintf('Column %s.%s already exists.', $this->getPrefixedTableName(), self::COLUMN_NAME)
        );
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());

        if (!$table->hasColumn(self::COLUMN_NAME)) {
            $table->addColumn(self::COLUMN_NAME, 'integer', [
                'unsigned' => true,
                'notnull'  => false,
            ]);

            $table->addIndex([self::COLUMN_NAME], 'IDX_EMAILS_RESEND_OF');

            $table->addForeignKeyConstraint(
                $table,
                [self::COLUMN_NAME],
                ['id'],
                ['onDelete' => 'SET NULL'],
                'FK_EMAILS_RESEND_OF'
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE '.$this->getPrefixedTableName().' DROP FOREIGN KEY FK_EMAILS_RESEND_OF');
        $this->addSql('DROP INDEX IDX_EMAILS_RESEND_OF ON '.$this->getPrefixedTableName());
        $this->addSql('ALTER TABLE '.$this->getPrefixedTableName().' DROP COLUMN '.self::COLUMN_NAME);
    }
}
