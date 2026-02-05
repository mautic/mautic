<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20221014061125 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'webhook_logs';
    private const INDEX_NAME = 'webhook_id_date_added';

    public function preUp(Schema $schema): void
    {
        if ($this->indexExists()) {
            throw new SkipMigration(
                sprintf('Index %s already exists', self::INDEX_NAME)
            );
        }
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);
        $indexName = $this->getPrefixedIndexName();

        // CREATE INDEX syntax is the same on MySQL and PostgreSQL
        $this->addSql(sprintf(
            'CREATE INDEX %s ON %s (webhook_id, date_added)',
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