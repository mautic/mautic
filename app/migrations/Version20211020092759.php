<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20211020092759 extends PreUpAssertionMigration
{
    private const TABLE = 'leads';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            function () {
                $tableName = $this->getPrefixedTableName(self::TABLE);
                $platform  = $this->connection->getDatabasePlatform();
                $indexName = $this->getIndexName();

                // Check index limit
                $indexes = DatabasePlatform::listTableIndexes(
                    $this->connection,
                    $tableName
                );
                if (count($indexes) >= DatabasePlatform::getMaxIndexAllowed($platform)) {
                    return true;
                }

                // Check if the specific index already exists (reliable way)
                return $this->indexExists($tableName, $indexName);
            },
            sprintf(
                'Index %s cannot be created because the %s table has hit the table index limit or the index already exists',
                $this->getIndexName(),
                $this->getPrefixedTableName(self::TABLE)
            )
        );
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE);
        $platform  = $this->connection->getDatabasePlatform();
        $indexName = $this->getIndexName();

        $this->addSql(
            DatabasePlatform::getCreateIndexSql(
                $platform,
                $tableName,
                $indexName,
                ['date_modified']
            )
        );
    }

    public function down(Schema $schema): void
    {
        $platform  = $this->connection->getDatabasePlatform();
        $tableName = $this->getPrefixedTableName(self::TABLE);
        $indexName = $this->getIndexName();

        $this->addSql(
            DatabasePlatform::getDropIndexSql(
                $platform,
                $tableName,
                $indexName,
                false,
                true
            )
        );
    }

    private function getIndexName(): string
    {
        return $this->prefix.'lead_date_modified';
    }
}
