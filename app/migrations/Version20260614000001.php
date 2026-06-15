<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\LeadBundle\Entity\FieldGroup;

final class Version20260614000001 extends PreUpAssertionMigration
{
    private string $tableName;

    private function initTableNames(): void
    {
        $this->tableName = $this->prefix.FieldGroup::TABLE_NAME;
    }

    protected function preUpAssertions(): void
    {
        $this->initTableNames();

        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->tableName)->hasColumn('field_order'),
            "Column field_order already exists in {$this->tableName}"
        );
    }

    public function up(Schema $schema): void
    {
        $this->initTableNames();

        $this->addSql("ALTER TABLE `{$this->tableName}` ADD `field_order` INT NOT NULL DEFAULT 0");
    }

    public function down(Schema $schema): void
    {
        $this->initTableNames();

        if ($schema->getTable($this->tableName)->hasColumn('field_order')) {
            $this->addSql("ALTER TABLE `{$this->tableName}` DROP COLUMN `field_order`");
        }
    }
}
