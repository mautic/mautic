<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20210420113309 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'lead_lists';
    private const INDEX_NAME   = 'lead_list_alias';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $this->indexExists(),
            sprintf('Index %s already exists', self::INDEX_NAME)
        );
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);

        $this->addSql(sprintf(
            'CREATE INDEX %s ON %s (alias)',
            self::INDEX_NAME,
            $tableName
        ));
    }

    public function down(Schema $schema): void
    {
        $platform  = $this->connection->getDatabasePlatform();
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);

        if ($platform instanceof PostgreSQLPlatform) {
            $this->addSql(sprintf(
                'DROP INDEX IF EXISTS %s',
                self::INDEX_NAME
            ));
        } else {
            $this->addSql(sprintf(
                'DROP INDEX %s ON %s',
                self::INDEX_NAME,
                $tableName
            ));
        }
    }

    private function indexExists(): bool
    {
        $platform  = $this->connection->getDatabasePlatform();
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);

        if ($platform instanceof PostgreSQLPlatform) {
            $sql = '
                SELECT 1
                FROM pg_indexes
                WHERE schemaname = current_schema()
                  AND tablename = ?
                  AND indexname = ?
            ';

            $result = $this->connection->executeQuery($sql, [$tableName, self::INDEX_NAME])->fetchOne();

            return (bool) $result;
        }

        // MySQL fallback
        $indexes = $this->connection->createSchemaManager()->listTableIndexes($tableName);

        return isset($indexes[self::INDEX_NAME]);
    }
}
