<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\LeadBundle\Entity\ListLead;

final class Version20231205094436 extends PreUpAssertionMigration
{
    private function getTableName(): string
    {
        return $this->prefix.ListLead::TABLE_NAME;
    }

    private function getIndexName(): string
    {
        return "{$this->prefix}lead_id_lists_id_removed";
    }

    private function indexExists(): bool
    {
        $tableName = $this->getTableName();
        $indexName = $this->getIndexName();

        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            // PostgreSQL folds unquoted identifiers to lowercase
            $indexNameLower = strtolower($indexName);

            $sql = '
                SELECT 1
                FROM pg_indexes
                WHERE schemaname = current_schema()
                  AND tablename = ?
                  AND indexname = ?
            ';

            return (bool) $this->connection->fetchOne($sql, [$tableName, $indexNameLower]);
        }

        // Fallback for MySQL/MariaDB (Doctrine schema manager is reliable here)
        $schemaManager = $this->connection->createSchemaManager();
        $indexes       = $schemaManager->listTableIndexes($tableName);

        $indexNameLower = strtolower($indexName);
        foreach ($indexes as $index) {
            if (strtolower($index->getName()) === $indexNameLower) {
                return true;
            }
        }

        return false;
    }

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $this->indexExists(),
            sprintf('Index %s already exists', $this->getIndexName())
        );
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getTableName());

        if (!$this->indexExists()) {
            $table->addIndex(
                ['lead_id', 'leadlist_id', 'manually_removed'],
                $this->getIndexName()
            );
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->getTableName());

        if ($this->indexExists()) {
            $table->dropIndex($this->getIndexName());
        }
    }
}
