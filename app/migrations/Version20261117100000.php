<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\EmailBundle\Entity\EmailForward;
use Mautic\EmailBundle\Entity\Stat;

final class Version20261117100000 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName(EmailForward::TABLE_NAME)),
            'Table '.EmailForward::TABLE_NAME.' already exists'
        );
    }

    public function getDescription(): string
    {
        return 'Record the copies of a sent email that were forwarded to somebody else';
    }

    public function up(Schema $schema): void
    {
        $statIdDataType = $this->getColumnTypeSignedOrUnsigned($schema, Stat::TABLE_NAME, 'id');

        $table = $schema->createTable($this->getPrefixedTableName(EmailForward::TABLE_NAME));
        $table->addColumn('id', Types::INTEGER, ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('stat_id', Types::BIGINT, ['unsigned' => self::COLUMN_TYPE_UNSIGNED === $statIdDataType]);
        $table->addColumn('date_sent', Types::DATETIME_IMMUTABLE);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['stat_id'], $this->generatePropertyName(EmailForward::TABLE_NAME, 'idx', ['stat_id']));
        $table->addForeignKeyConstraint(
            $this->getPrefixedTableName(Stat::TABLE_NAME),
            ['stat_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            $this->generatePropertyName(EmailForward::TABLE_NAME, 'fk', ['stat_id'])
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->getPrefixedTableName(EmailForward::TABLE_NAME));
    }
}
