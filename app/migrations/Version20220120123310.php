<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20220120123310 extends PreUpAssertionMigration
{
    protected const TABLE = 'lead_lists';

    private function getTableName(): string
    {
        return $this->prefix . self::TABLE;
    }

    private function getIndexName(): string
    {
        return $this->prefix . 'segment_deleted';
    }

    private function indexExists(): bool
    {
        $tableName = $this->getTableName();
        $indexName = $this->getIndexName();

        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            $sql = '
                SELECT 1
                FROM pg_indexes
                WHERE schemaname = current_schema()
                  AND tablename = ?
                  AND lower(indexname) = lower(?)
            ';

            return (bool) $this->connection->fetchOne($sql, [$tableName, $indexName]);
        }

        // MySQL/MariaDB fallback
        $schemaManager = $this->connection->createSchemaManager();
        $indexes       = $schemaManager->listTableIndexes($tableName);

        $lowerIndexName = strtolower($indexName);
        foreach ($indexes as $index) {
            if (strtolower($index->getName()) === $lowerIndexName) {
                return true;
            }
        }

        return false;
    }

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $this->indexExists(),
            "Index {$this->getIndexName()} cannot be created because the index already exists"
        );
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getTableName();

        if (!$schema->hasTable($tableName) || $this->indexExists()) {
            return;
        }

        $table = $schema->getTable($tableName);
        $table->addIndex(['deleted'], $this->getIndexName());
    }
}