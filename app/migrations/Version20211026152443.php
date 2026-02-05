<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20211026152443 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'lead_fields';
    private const INDEX_NAME = 'idx_object_field_order_is_published';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn () => $this->indexExists(),
            sprintf("Index %s already exists", self::INDEX_NAME)
        );
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);
        $indexName = $this->getPrefixedIndexName();

        // CREATE INDEX syntax is the same on both platforms in this case
        $this->addSql(sprintf(
            'CREATE INDEX %s ON %s ("object", field_order, is_published)',
            $indexName,
            $tableName
        ));
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $indexName = $this->getPrefixedIndexName();

        if ($platform instanceof PostgreSQLPlatform) {
            $this->addSql(sprintf('DROP INDEX IF EXISTS %s', $indexName));
        } else {
            $this->addSql(sprintf(
                'DROP INDEX %s ON %s',
                $indexName,
                $this->getPrefixedTableName(self::TABLE_NAME)
            ));
        }
    }

    private function indexExists(): bool
    {
        $platform = $this->connection->getDatabasePlatform();
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);
        $indexName = $this->getPrefixedIndexName();

        if ($platform instanceof PostgreSQLPlatform) {
            $sql = '
                SELECT 1
                FROM pg_indexes
                WHERE schemaname = current_schema()
                  AND tablename  = ?
                  AND indexname  = ?
            ';

            $result = $this->connection->executeQuery($sql, [$tableName, $indexName])->fetchOne();

            return (bool) $result;
        }

        // MySQL fallback
        $indexes = $this->connection->createSchemaManager()->listTableIndexes($tableName);

        return isset($indexes[$indexName]);
    }

    private function getPrefixedIndexName(): string
    {
        return $this->prefix . self::INDEX_NAME;
    }
}