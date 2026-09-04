<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20230506113627 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'message_queue';
    protected const INDEX_NAME = 'message_queue_date_published';

    protected function preUpAssertions(): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);
        $indexName = $this->getPrefixedIndexName(self::INDEX_NAME);

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
        $this->createIndex(
            $this->getPrefixedTableName(self::TABLE_NAME),
            $this->getPrefixedIndexName(self::INDEX_NAME),
            ['date_published']
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
