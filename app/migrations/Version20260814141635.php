<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20260814141635 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'forms';
    private string $columnName  = 'form_type';

    public function preUp(Schema $schema): void
    {
        if (!$schema->getTable($this->getPrefixedTableName())->hasColumn($this->columnName)) {
            throw new SkipMigration('Column already removed');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `{$this->getPrefixedTableName()}` DROP COLUMN `{$this->columnName}`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `{$this->getPrefixedTableName()}` ADD COLUMN `{$this->columnName}` VARCHAR(255) DEFAULT NULL");
    }
}
