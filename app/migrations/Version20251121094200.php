<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20251121094200 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'email_stats_open_details';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName()),
            sprintf('Table %s already exists', self::TABLE_NAME)
        );
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable($this->getPrefixedTableName());
        $table->addColumn('id', Types::BIGINT, ['unsigned' => true, 'notnull' => true, 'autoincrement' => true]);
        $table->addColumn('stat_id', Types::BIGINT, ['unsigned' => true, 'notnull' => true]);
        $table->addColumn('date_sent', Types::DATETIME_MUTABLE, ['notnull' => true]);
        $table->addColumn('open_detail', Types::TEXT, ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['date_sent'], 'email_date_sent');
        $table->addForeignKeyConstraint($this->prefix.'email_stats', ['stat_id'], ['id'], ['onDelete' => 'CASCADE']);
    }
}
