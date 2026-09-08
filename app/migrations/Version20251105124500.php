<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20251105124500 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'email_stats_data';

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
        $table->addColumn('stat_id', Types::BIGINT, ['unsigned' => true, 'notnull' => true]);
        $table->addColumn('date_sent', Types::DATETIME_MUTABLE, ['notnull' => true]);
        $table->addColumn('tokens', Types::JSON, ['notnull' => false]);
        $table->setPrimaryKey(['stat_id']);
        $table->addIndex(['date_sent'], 'email_date_sent');
        $table->addForeignKeyConstraint($this->prefix.'email_stats', ['stat_id'], ['id'], ['onDelete' => 'CASCADE']);
    }
}
