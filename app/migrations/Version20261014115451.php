<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20261014115451 extends PreUpAssertionMigration
{
    protected const TABLE_NAME   = 'dynamic_content_stats';
    private const COLUMN_NAME  = 'source_data';

    protected function preUpAssertions(): void
    {
        $tableName = $this->getPrefixedTableName();

        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($tableName)->hasColumn(self::COLUMN_NAME),
            'Column '.self::COLUMN_NAME.' already exists on table '.$tableName
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf(
            'ALTER TABLE %s ADD %s JSON NULL',
            $this->getPrefixedTableName(),
            self::COLUMN_NAME
        ));
    }

    public function down(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName();
        $table     = $schema->getTable($tableName);

        $indexName = $this->prefix.'source_data_uri';

        // Drop the generated index if it exists
        if ($table->hasIndex($indexName)) {
            $this->addSql(sprintf(
                'ALTER TABLE %s DROP INDEX `%s`',
                $tableName,
                $indexName
            ));
        }

        // Drop the generated column if it exists
        if ($table->hasColumn('source_data_uri')) {
            $this->addSql(sprintf(
                'ALTER TABLE %s DROP COLUMN source_data_uri',
                $tableName
            ));
        }

        // Drop the main source_data column if it exists
        if ($table->hasColumn(self::COLUMN_NAME)) {
            $this->addSql(sprintf(
                'ALTER TABLE %s DROP COLUMN %s',
                $tableName,
                self::COLUMN_NAME
            ));
        }
    }
}
