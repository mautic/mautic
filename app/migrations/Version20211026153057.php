<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20211026153057 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'lead_frequencyrules';
    private const INDEX_NAME   = 'idx_frequency_date_added';

    protected function preUpAssertions(): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);
        $indexName = $this->getPrefixedIndexName();

        $this->skipAssertion(
            fn () => $this->indexExists($tableName, $indexName),
            sprintf('Index %s already exists', self::INDEX_NAME)
        );
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);
        $platform  = $this->connection->getDatabasePlatform();
        $indexName = $this->getPrefixedIndexName();

        $this->addSql(
            DatabasePlatform::getCreateIndexSql(
                $platform,
                $tableName,
                $indexName,
                ['lead_id', 'date_added']
            )
        );
    }

    public function down(Schema $schema): void
    {
        $platform  = $this->connection->getDatabasePlatform();
        $indexName = $this->getPrefixedIndexName();

        $this->addSql(
            DatabasePlatform::getDropIndexSql(
                $platform,
                $indexName,
                $this->getPrefixedTableName(self::TABLE_NAME)
            ),
        );
    }

    private function getPrefixedIndexName(): string
    {
        return $this->prefix.self::INDEX_NAME;
    }
}
