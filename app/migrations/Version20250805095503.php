<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\PointBundle\Entity\Group;

final class Version20250805095503 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = Group::TABLE_NAME;

    public function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->hasColumn('uuid'),
            'Column uuid already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $table  = $this->platform->quoteIdentifier($this->getPrefixedTableName());
        $column = $this->platform->quoteIdentifier('uuid');
        $uuid   = DatabasePlatform::isPostgreSQL($this->platform) ? 'gen_random_uuid()::text' : 'UUID()';

        $this->addSql("ALTER TABLE {$table} ADD COLUMN {$column} CHAR(36) DEFAULT NULL");
        $this->addSql("UPDATE {$table} SET {$column} = {$uuid} WHERE {$column} IS NULL");
    }
}
