<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20230131133732 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     * @throws SchemaException
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable("{$this->prefix}lead_lists")->hasColumn('last_built_time')) {
            throw new SkipMigration("The last_built_time column has already been added to the {$this->prefix}lead_lists table.");
        }
    }

    private function getTable(): string
    {
        return $this->prefix.'lead_lists';
    }

    public function up(Schema $schema): void
    {
        $table = $this->getTable();
        $this->addSql(sprintf('ALTER TABLE `%s` ADD COLUMN `last_built_time` FLOAT NULL DEFAULT NULL AFTER `last_built_date`', $table));
    }

    public function down(Schema $schema): void
    {
        $this->addSql(sprintf('ALTER TABLE `%s` DROP COLUMN `last_built_time`', $this->getTable()));
    }
}
