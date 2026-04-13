<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20230506113422 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'dynamic_content_stats';
    private const INDEX_NAME   = 'stat_dynamic_content_date_sent';

    protected function preUpAssertions(): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);
        $indexName = $this->getPrefixedIndexName();

        $this->skipAssertion(
            fn () => $this->indexExists($tableName, $indexName),
            sprintf(
                'The index "%s" has already been added to the table "%s".',
                $indexName,
                $tableName
            )
        );
    }

    public function up(Schema $schema): void
    {
        $platform  = $this->connection->getDatabasePlatform();
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);
        $indexName = $this->getPrefixedIndexName();

        $this->addSql(
            DatabasePlatform::getCreateIndexSql(
                $platform,
                $tableName,
                $indexName,
                ['date_sent']
            )
        );
    }

    public function down(Schema $schema): void
    {
        $platform  = $this->connection->getDatabasePlatform();
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);
        $indexName = $this->getPrefixedIndexName();

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

    private function getPrefixedIndexName(): string
    {
        return $this->prefix.self::INDEX_NAME;
    }
}
