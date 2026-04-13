<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250923135527 extends PreUpAssertionMigration
{
    protected const TABLE_NAME  = 'push_notifications';
    private const COLUMN_NAME   = 'translation_parent_id';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->hasColumn(self::COLUMN_NAME), sprintf('Column %s.%s already exists.', $this->getPrefixedTableName(), self::COLUMN_NAME));
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());

        if (!$table->hasColumn(self::COLUMN_NAME)) {
            $table->addColumn(self::COLUMN_NAME, 'integer', [
                'unsigned' => true,
                'notnull'  => false,
            ]);

            $table->addIndex([self::COLUMN_NAME], 'IDX_PUSH_NOTIFICATIONS_TRANSLATION_PARENT');

            $table->addForeignKeyConstraint(
                $table,
                [self::COLUMN_NAME],
                ['id'],
                ['onDelete' => 'CASCADE'],
                'FK_PUSH_NOTIFICATIONS_TRANSLATION_PARENT'
            );
        }
    }

    public function down(Schema $schema): void
    {
        $platform     = $this->connection->getDatabasePlatform();

        $this->addSql('ALTER TABLE '.$this->getPrefixedTableName().' DROP FOREIGN KEY FK_PUSH_NOTIFICATIONS_TRANSLATION_PARENT');

        $this->addSql(
            DatabasePlatform::getDropIndexSql(
                $platform,
                $this->getPrefixedTableName(),
                'IDX_PUSH_NOTIFICATIONS_TRANSLATION_PARENT',
                false,
                true
            )
        );

        $this->addSql('ALTER TABLE '.$this->getPrefixedTableName().' DROP COLUMN translation_parent_id');
    }
}
