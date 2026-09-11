<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20221014061125 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'webhook_logs';
    protected const INDEX_NAME = 'webhook_id_date_added';

    public function preUp(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);
        $indexName = $this->getPrefixedIndexName(self::INDEX_NAME);

        if ($this->indexExists($tableName, $indexName)) {
            throw new SkipMigration(sprintf('Index %s already exists', self::INDEX_NAME));
        }
    }

    public function up(Schema $schema): void
    {
        $this->createIndex(
            $this->getPrefixedTableName(self::TABLE_NAME),
            $this->getPrefixedIndexName(self::INDEX_NAME),
            ['webhook_id', 'date_added']
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
