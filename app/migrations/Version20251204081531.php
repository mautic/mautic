<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

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
        $sql = "ALTER TABLE `{$this->getPrefixedTableName()}` ADD COLUMN `{$this->uuidColumn}` char(36) default NULL; ";
        $sql .= "UPDATE `{$this->getPrefixedTableName()}` SET `{$this->uuidColumn}` = UUID() WHERE `{$this->uuidColumn}` IS NULL;";
        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `{$this->getPrefixedTableName()}` DROP COLUMN `{$this->uuidColumn}`");
    }
}
