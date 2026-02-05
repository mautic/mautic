<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\LeadBundle\Field\Helper\IndexHelper;

final class Version20211020092759 extends PreUpAssertionMigration
{
    private const TABLE = 'leads';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            function (Schema $schema) {
                $tableName = $this->getPrefixedTableName(self::TABLE);

                // Check index limit
                $indexes = $this->connection->createSchemaManager()->listTableIndexes($tableName);
                if (count($indexes) >= IndexHelper::MAX_COUNT_ALLOWED) {
                    return true;
                }

                // Check if the specific index already exists (reliable way)
                return $this->indexExists($tableName);
            },
            sprintf(
                "Index %s cannot be created because the %s table has hit the table index limit or the index already exists",
                $this->getIndexName(),
                $this->getPrefixedTableName(self::TABLE)
            )
        );
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $tableName = $this->getPrefixedTableName(self::TABLE);
        $indexName = $this->getIndexName();

        // CREATE INDEX works on both platforms, but we branch for future flexibility
        if ($platform instanceof PostgreSQLPlatform) {
            $this->addSql(sprintf(
                'CREATE INDEX %s ON %s (date_modified)',
                $indexName,
                $tableName
            ));
        } else {
            $this->addSql(sprintf(
                'CREATE INDEX %s ON %s (date_modified)',
                $indexName,
                $tableName
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $tableName = $this->getPrefixedTableName(self::TABLE);
        $indexName = $this->getIndexName();

        if ($platform instanceof PostgreSQLPlatform) {
            $this->addSql(sprintf(
                'DROP INDEX IF EXISTS %s',
                $indexName
            ));
        } else {
            $this->addSql(sprintf(
                'DROP INDEX %s ON %s',
                $indexName,
                $tableName
            ));
        }
    }

    private function getIndexName(): string
    {
        return $this->prefix . 'lead_date_modified';
    }

    private function indexExists(string $tableName): bool
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            $sql = "
                SELECT 1
                FROM pg_indexes
                WHERE schemaname = current_schema()
                  AND tablename = ?
                  AND indexname = ?
            ";

            $result = $this->connection->executeQuery($sql, [$tableName, $this->getIndexName()])->fetchOne();

            return (bool) $result;
        }

        // MySQL fallback
        $indexes = $this->connection->createSchemaManager()->listTableIndexes($tableName);

        return isset($indexes[$this->getIndexName()]);
    }
}