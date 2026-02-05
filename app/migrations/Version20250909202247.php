<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\ProjectBundle\Entity\Project;

final class Version20250909202247 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = Project::TABLE_NAME;

    private function getTableName(): string
    {
        return $this->prefix.self::TABLE_NAME;
    }

    private function getNewIndexName(): string
    {
        return $this->prefix.'unique_project_name';
    }

    private function getOldIndexName(): string
    {
        return $this->prefix.'project_name';
    }

    private function indexExists(string $indexName): bool
    {
        $tableName = $this->getTableName();

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
            fn (Schema $schema) => $this->indexExists($this->getNewIndexName()),
            sprintf('Index %s already exists', $this->getNewIndexName())
        );
    }

    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $tableName = $this->getTableName();
        $table     = $schema->getTable($tableName);

        $oldIndexName = $this->getOldIndexName();
        $newIndexName = $this->getNewIndexName();

        // Drop old non-unique index if it exists
        if ($this->indexExists($oldIndexName)) {
            $table->dropIndex($oldIndexName);
        }

        // Add new unique index (idempotent)
        if (!$this->indexExists($newIndexName)) {
            $table->addUniqueIndex(['name'], $newIndexName);
        }
    }

    /**
     * @throws SchemaException
     */
    public function down(Schema $schema): void
    {
        $tableName = $this->getTableName();
        $table     = $schema->getTable($tableName);

        $oldIndexName = $this->getOldIndexName();
        $newIndexName = $this->getNewIndexName();

        // Drop new unique index if it exists
        if ($this->indexExists($newIndexName)) {
            $table->dropIndex($newIndexName);
        }

        // Add back old non-unique index (idempotent)
        if (!$this->indexExists($oldIndexName)) {
            $table->addIndex(['name'], $oldIndexName);
        }
    }
}
