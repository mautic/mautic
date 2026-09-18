<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20210420113309 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'lead_lists';
    protected const INDEX_NAME = 'lead_list_alias';

    protected function preUpAssertions(): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);

        $this->skipAssertion(
            fn (Schema $schema) => $this->indexExists($tableName, self::INDEX_NAME),
            sprintf('Index %s already exists', self::INDEX_NAME)
        );
    }

    public function up(Schema $schema): void
    {
        $this->createIndex(
            $this->getPrefixedTableName(self::TABLE_NAME),
            self::INDEX_NAME,
            ['alias']
        );
    }

    public function down(Schema $schema): void
    {
        $this->dropIndex(
            $this->getPrefixedTableName(self::TABLE_NAME),
            self::INDEX_NAME
        );
    }
}
