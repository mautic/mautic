<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\CoreBundle\Doctrine\Type\ArrayType;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatData;

final class Version20261117153000 extends PreUpAssertionMigration
{
    protected const string TABLE_NAME = StatData::TABLE_NAME;

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema): bool => $schema->hasTable($this->getPrefixedTableName()),
            sprintf('Table %s already exists', self::TABLE_NAME)
        );
    }

    public function up(Schema $schema): void
    {
        $statIdDataType = $this->getColumnTypeSignedOrUnsigned($schema, Stat::TABLE_NAME, 'id');

        $table = $schema->createTable($this->getPrefixedTableName());
        $table->addColumn('stat_id', Types::BIGINT, ['unsigned' => self::COLUMN_TYPE_UNSIGNED === $statIdDataType, 'notnull' => true]);
        $table->addColumn('date_sent', Types::DATETIME_MUTABLE, ['notnull' => true]);
        $table->addColumn('tokens', ArrayType::ARRAY, ['notnull' => false]);
        $this->setPrimaryKey($table, ['stat_id']);
        $table->addIndex(['date_sent'], $this->prefix.'email_date_sent');
        $table->addForeignKeyConstraint($this->getPrefixedTableName(Stat::TABLE_NAME), ['stat_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->getPrefixedTableName());
    }
}
