<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20231206152313 extends PreUpAssertionMigration
{
    private function getTableName(): string
    {
        return "{$this->prefix}email_stats";
    }

    private function getSentIndexName(): string
    {
        return "{$this->prefix}stat_email_lead_id_date_sent";
    }

    private function getIsReadIndexName(): string
    {
        return "{$this->prefix}stat_email_email_id_is_read";
    }

    private function getOldLeadIndexName(): string
    {
        return $this->generatePropertyName('email_stats', 'idx', ['lead_id']);
    }

    private function getOldEmailIndexName(): string
    {
        return $this->generatePropertyName('email_stats', 'idx', ['email_id']);
    }

    private function indexExists(string $indexName): bool
    {
        $tableName = $this->getTableName();
        $platform  = $this->connection->getDatabasePlatform();

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

        foreach ($indexes as $index) {
            if (strtolower($index->getName()) === strtolower($indexName)) {
                return true;
            }
        }

        return false;
    }

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $this->indexExists($this->getSentIndexName()),
            sprintf('Index %s already exists', $this->getSentIndexName())
        );

        $this->skipAssertion(
            fn (Schema $schema) => $this->indexExists($this->getIsReadIndexName()),
            sprintf('Index %s already exists', $this->getIsReadIndexName())
        );
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getTableName());

        if (!$this->indexExists($this->getSentIndexName())) {
            $table->addIndex(['lead_id', 'date_sent'], $this->getSentIndexName());
        }

        if (!$this->indexExists($this->getIsReadIndexName())) {
            $table->addIndex(['email_id', 'is_read'], $this->getIsReadIndexName());
        }
    }

    public function postUp(Schema $schema): void
    {
        $platform   = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof PostgreSQLPlatform;

        $oldIndexes = [
            $this->getOldLeadIndexName(),
            $this->getOldEmailIndexName(),
        ];

        foreach ($oldIndexes as $oldIndexName) {
            if ($this->indexExists($oldIndexName)) {
                if ($isPostgres) {
                    $this->connection->executeStatement("DROP INDEX IF EXISTS {$oldIndexName}");
                } else {
                    $this->connection->executeStatement("DROP INDEX {$oldIndexName} ON {$this->getTableName()}");
                }
            }
        }
    }
}