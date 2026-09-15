<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20210112162046 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'sync_object_mapping';
    protected const INDEX_NAME = 'integration_integration_object_name_last_sync_date';

    public function preUp(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);

        if ($this->indexExists($tableName, self::INDEX_NAME)) {
            throw new SkipMigration(sprintf('Index `%s` already exists. Skipping the migration', self::INDEX_NAME));
        }
    }

    public function up(Schema $schema): void
    {
        $this->createIndex(
            $this->getPrefixedTableName(self::TABLE_NAME),
            self::INDEX_NAME,
            ['integration', 'internal_object_name', 'last_sync_date'],
        );
    }

    public function preDown(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);

        if (!$this->indexExists($tableName, self::INDEX_NAME)) {
            throw new SkipMigration(sprintf('Index `%s` doesn\'t exist. Skipping reverting the migration', self::INDEX_NAME));
        }
    }

    public function down(Schema $schema): void
    {
        $this->dropIndex(
            $this->getPrefixedTableName(self::TABLE_NAME),
            self::INDEX_NAME,
        );
    }
}
