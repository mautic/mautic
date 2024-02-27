<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\TextType;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20200917152259 extends AbstractMauticMigration
{
    /**
     * @var string
     */
    private $table = 'lead_fields';

    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable($this->getTableName())->getColumn('default_value')->getType() instanceof TextType) {
            throw new SkipMigration('default_value is already the correct type.');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->getTableName()} MODIFY `default_value` LONGTEXT NULL DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->getTableName()} MODIFY `default_value` VARCHAR(191) NULL DEFAULT NULL");
    }

    private function getTableName(): string
    {
        return $this->prefix.$this->table;
    }
}
