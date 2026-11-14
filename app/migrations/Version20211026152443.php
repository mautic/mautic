<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20211026152443 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'lead_fields';
    protected const INDEX_NAME = 'idx_object_field_order_is_published';

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
        $this->createIndex(
            $this->getPrefixedTableName(self::TABLE_NAME),
            $this->getPrefixedIndexName(self::INDEX_NAME),
            ['"object"', 'field_order', 'is_published'] // object is reserved keyword
        );
    }

    public function down(Schema $schema): void
    {
        $this->dropIndex(
            $this->getPrefixedTableName(self::TABLE_NAME),
            $this->getPrefixedIndexName(self::INDEX_NAME),
        );
    }
}
