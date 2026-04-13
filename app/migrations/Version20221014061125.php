<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;

final class Version20221014061125 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'webhook_logs';
    private const INDEX_NAME   = 'webhook_id_date_added';

    public function preUp(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);
        $indexName = $this->getPrefixedIndexName();

        if ($this->indexExists($tableName, $indexName)) {
            throw new SkipMigration(sprintf('Index %s already exists', self::INDEX_NAME));
        }
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
                ['webhook_id', 'date_added']
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
