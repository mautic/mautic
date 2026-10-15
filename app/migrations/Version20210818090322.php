<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20210818090322 extends PreUpAssertionMigration
{
    public function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable("{$this->prefix}roles")->hasColumn('uuid');
        }, sprintf('Column %s already exists', 'uuid'));
    }

    public function up(Schema $schema): void
    {
        $table  = $this->platform->quoteIdentifier($this->getPrefixedTableName('roles'));
        $column = $this->platform->quoteIdentifier('uuid');
        $uuid   = DatabasePlatform::isPostgreSQL($this->platform) ? 'gen_random_uuid()::text' : 'UUID()';

        $this->addSql("ALTER TABLE {$table} ADD COLUMN {$column} CHAR(36) DEFAULT NULL");
        $this->addSql("UPDATE {$table} SET {$column} = {$uuid} WHERE {$column} IS NULL");
    }
}
