<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20210112162046 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'sync_object_mapping';
    private const INDEX_NAME   = 'integration_integration_object_name_last_sync_date';

    public function preUp(Schema $schema): void
    {
        $this->skipIf(
            $this->indexExists($schema),
            sprintf('Index `%s` already exists. Skipping the migration', static::INDEX_NAME)
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf(
            'ALTER TABLE `%s` ADD INDEX `%s` (`integration`, `internal_object_name`, `last_sync_date`);',
            $this->getPrefixedTableName(),
            static::INDEX_NAME
        ));
    }

    public function preDown(Schema $schema): void
    {
        $this->skipIf(
            !$this->indexExists($schema),
            sprintf('Index `%s` doesn\'t exist. Skipping reverting the migration', static::INDEX_NAME)
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(sprintf(
            'ALTER TABLE `%s` DROP INDEX `%s`;',
            $this->getPrefixedTableName(),
            static::INDEX_NAME
        ));
    }

    private function indexExists(Schema $schema): bool
    {
        return $schema->getTable($this->getPrefixedTableName())->hasIndex(static::INDEX_NAME);
    }
}
