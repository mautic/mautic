<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

class Version20190410143658 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        $tableName    = $this->getTableName();
        $newIndexName = $this->getNewIndexName();

        // Check real existence via SQL (case-insensitive where needed)
        if ($this->indexExists($tableName, $newIndexName)) {
            throw new SkipMigration('The composite index already exists - skipping');
        }
    }

    public function up(Schema $schema): void
    {
        $tableName    = $this->getTableName();
        $newIndexName = $this->getNewIndexName();

        // Skip creation if already exists (extra safety)
        if ($this->indexExists($tableName, $newIndexName)) {
            return;
        }

        // Add new composite index (same syntax OK on both platforms)
        $this->addSql("CREATE INDEX {$newIndexName} ON {$tableName} (lead_id, channel, reason)");

        // Drop any old single-column lead_id indexes (find dynamically)
        $oldIndexes = $this->findSingleLeadIdIndexes($tableName);

        foreach ($oldIndexes as $oldName) {
            $this->addSql("DROP INDEX IF EXISTS {$oldName}");
        }
    }

    /**
     * Check if an index exists (platform-aware, case-insensitive lookup).
     */
    private function indexExists(string $tableName, string $indexName): bool
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPg     = $platform instanceof PostgreSQLPlatform;

        if ($isPg) {
            // PostgreSQL: query pg_index + pg_class (case-insensitive name match)
            $sql = <<<SQL
                SELECT 1
                FROM pg_indexes
                WHERE tablename = ?
                  AND LOWER(indexname) = LOWER(?)
                  AND schemaname = current_schema()
SQL;
            $stmt = $this->connection->executeQuery($sql, [$tableName, $indexName]);

            return (bool) $stmt->fetchOne();
        }

        // MySQL fallback (original hasIndex would work, but for consistency)
        $indexes = $this->connection->createSchemaManager()->listTableIndexes($tableName);

        return isset($indexes[$indexName]);
    }

    /**
     * Find any single-column indexes on lead_id (to safely drop old ones).
     */
    private function findSingleLeadIdIndexes(string $tableName): array
    {
        $indexes = $this->connection->getSchemaManager()->listTableIndexes($tableName);

        $toDrop = [];
        foreach ($indexes as $index) {
            $columns = $index->getColumns();
            if (1 === count($columns) && 'lead_id' === $columns[0]) {
                $toDrop[] = $index->getName();
            }
        }

        return $toDrop;
    }

    private function getNewIndexName(): string
    {
        return "{$this->prefix}leadid_reason_channel";
    }

    private function getTableName(): string
    {
        return "{$this->prefix}lead_donotcontact";
    }
}
