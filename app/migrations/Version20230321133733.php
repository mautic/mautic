<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20230321133733 extends PreUpAssertionMigration
{
    // Make sonar qube happy
    private const ALTER_TABLE_SQL = 'ALTER TABLE';
    // Make sonar qube happy
    private const VARCHAR_DEF_SQL = 'VARCHAR(191) DEFAULT NULL';

    protected const TABLE_NAME = 'asset_downloads';

    private const COLUMNS = [
        'utm_campaign' => self::VARCHAR_DEF_SQL,
        'utm_content'  => self::VARCHAR_DEF_SQL,
        'utm_medium'   => self::VARCHAR_DEF_SQL,
        'utm_source'   => self::VARCHAR_DEF_SQL,
        'utm_term'     => self::VARCHAR_DEF_SQL,
    ];

    protected function preUpAssertions(): void
    {
        $tableName     = $this->getPrefixedTableName(self::TABLE_NAME);
        $schemaManager = $this->connection->createSchemaManager();
        $columns       = $schemaManager->listTableColumns($tableName);

        foreach (array_keys(self::COLUMNS) as $column) {
            $this->skipAssertion(
                fn () => isset($columns[$column]),
                sprintf('Column %s.%s already exists', $tableName, $column)
            );
        }
    }

    public function up(Schema $schema): void
    {
        $platform  = $this->connection->getDatabasePlatform();
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);

        $alterStatements = [];

        foreach (self::COLUMNS as $column => $definition) {
            if ($platform instanceof PostgreSQLPlatform) {
                // PostgreSQL: separate ALTER COLUMN statements (more reliable)
                $alterStatements[] = sprintf(
                    'ADD COLUMN %s %s',
                    $this->connection->quoteIdentifier($column),
                    $definition
                );
            } else {
                // MySQL: can do multiple in one ALTER TABLE
                $alterStatements[] = sprintf(
                    'ADD %s %s',
                    $this->connection->quoteIdentifier($column),
                    $definition
                );
            }
        }

        if ($platform instanceof PostgreSQLPlatform) {
            // PostgreSQL: one ALTER per column is safer (avoids long syntax issues)
            foreach ($alterStatements as $stmt) {
                $this->addSql(sprintf(
                    self::ALTER_TABLE_SQL.' %s %s',
                    $tableName,
                    $stmt
                ));
            }
        } else {
            // MySQL: one ALTER TABLE with multiple ADD
            $this->addSql(sprintf(
                self::ALTER_TABLE_SQL.' %s %s',
                $tableName,
                implode(', ', $alterStatements)
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $platform  = $this->connection->getDatabasePlatform();
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);

        $columns = array_keys(self::COLUMNS);

        if ($platform instanceof PostgreSQLPlatform) {
            foreach ($columns as $column) {
                $this->addSql(sprintf(
                    self::ALTER_TABLE_SQL.' %s DROP COLUMN IF EXISTS %s',
                    $tableName,
                    $this->connection->quoteIdentifier($column)
                ));
            }
        } else {
            $dropParts = [];
            foreach ($columns as $column) {
                $dropParts[] = sprintf('DROP %s', $this->connection->quoteIdentifier($column));
            }
            $this->addSql(sprintf(
                self::ALTER_TABLE_SQL.' %s %s',
                $tableName,
                implode(', ', $dropParts)
            ));
        }
    }
}
