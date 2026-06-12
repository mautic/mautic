<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20211020092759 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'leads';
    protected const INDEX_NAME = 'lead_date_modified';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            function () {
                $tableName = $this->getPrefixedTableName(self::TABLE_NAME);
                $indexName = $this->getPrefixedIndexName(self::INDEX_NAME);
                $platform  = $this->connection->getDatabasePlatform();

                // Check index limit
                $indexes = $this->getIndexes($tableName);

                if (count($indexes) >= DatabasePlatform::getMaxIndexAllowed($platform)) {
                    return true;
                }

                // Check if the specific index already exists (reliable way)
                return $this->indexExists($tableName, $indexName);
            },
            sprintf(
                'Index %s cannot be created because the %s table has hit the table index limit or the index already exists',
                $this->getPrefixedIndexName(self::INDEX_NAME),
                $this->getPrefixedTableName(self::TABLE_NAME)
            )
        );
    }

    public function up(Schema $schema): void
    {
        $this->createIndex(
            $this->getPrefixedTableName(self::TABLE_NAME),
            $this->getPrefixedIndexName(self::INDEX_NAME),
            ['date_modified']
        );
    }

    public function down(Schema $schema): void
    {
        $this->dropIndex(
            $this->getPrefixedTableName(self::TABLE_NAME),
            $this->getPrefixedIndexName(self::INDEX_NAME)
        );
    }
}
