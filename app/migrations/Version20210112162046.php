<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20210112162046 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'sync_object_mapping';
    private const INDEX_NAME   = 'integration_integration_object_name_last_sync_date';

    public function preUp(Schema $schema): void
    {
        if ($this->indexExists()) {
            throw new SkipMigration(sprintf('Index `%s` already exists. Skipping the migration', self::INDEX_NAME));
        }
    }

    public function up(Schema $schema): void
    {
        $platform  = $this->connection->getDatabasePlatform();
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);

        if ($platform instanceof PostgreSQLPlatform) {
            $this->addSql(sprintf(
                'CREATE INDEX %s ON %s (integration, internal_object_name, last_sync_date)',
                self::INDEX_NAME,
                $tableName
            ));
        } else {
            $this->addSql(sprintf(
                'ALTER TABLE %s ADD INDEX %s (integration, internal_object_name, last_sync_date)',
                $tableName,
                self::INDEX_NAME
            ));
        }
    }

    public function preDown(Schema $schema): void
    {
        if (!$this->indexExists()) {
            throw new SkipMigration(sprintf('Index `%s` doesn\'t exist. Skipping reverting the migration', self::INDEX_NAME));
        }
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
                'ALTER TABLE %s DROP INDEX %s',
                $tableName,
                self::INDEX_NAME
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
