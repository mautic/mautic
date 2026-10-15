<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;

final class Version20251204081531 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'monitoring';
    private string $uuidColumn = 'uuid';

    public function preUp(Schema $schema): void
    {
        if ($schema->getTable($this->getPrefixedTableName())->hasColumn($this->uuidColumn)) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $table  = $this->platform->quoteIdentifier($this->getPrefixedTableName());
        $column = $this->platform->quoteIdentifier('uuid');
        $uuid   = DatabasePlatform::isPostgreSQL($this->platform) ? 'gen_random_uuid()::text' : 'UUID()';

        $this->addSql("ALTER TABLE {$table} ADD COLUMN {$column} CHAR(36) DEFAULT NULL");
        $this->addSql("UPDATE {$table} SET {$column} = {$uuid} WHERE {$column} IS NULL");
    }

    public function down(Schema $schema): void
    {
        $table  = $this->platform->quoteIdentifier($this->getPrefixedTableName());
        $column = $this->platform->quoteIdentifier($this->uuidColumn);
        $this->addSql("ALTER TABLE {$table} DROP COLUMN {$column}");
    }
}
