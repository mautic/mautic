<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20241212090146 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'sync_object_mapping';

    private function getTableName(): string
    {
        return $this->prefix.self::TABLE_NAME;
    }

    private function getIndexName(): string
    {
        return $this->prefix.'internal_object_id_idx';
    }

    private function indexExists(): bool
    {
        $tableName = $this->getTableName();

        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist([$tableName])) {
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
            $indexes        = $schemaManager->listTableIndexes($tableName);
            $lowerIndexName = strtolower($indexName);

            foreach ($indexes as $index) {
                if (strtolower($index->getName()) === $lowerIndexName) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => !$schema->hasTable($this->getTableName()) || $this->indexExists(),
            sprintf('Table %s does not exist or the index %s already exists.', $this->getTableName(), $this->getIndexName())
        );
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getTableName();

        if (!$schema->hasTable($tableName) || $this->indexExists()) {
            return;
        }

        $table = $schema->getTable($tableName);
        $table->addIndex(['internal_object_id'], $this->getIndexName());
    }

    public function down(Schema $schema): void
    {
        $tableName = $this->getTableName();

        if (!$schema->hasTable($tableName) || !$this->indexExists()) {
            return;
        }

        $table = $schema->getTable($tableName);
        $table->dropIndex($this->getIndexName());
    }
}
