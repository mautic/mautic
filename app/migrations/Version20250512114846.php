<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250512114846 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName(Event::TABLE_NAME))
                ->hasColumn('redirect_event_id'),
            'Column redirect_event_id already exists in campaign_events table'
        );
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getTableName();

        $this->addSql("ALTER TABLE {$tableName} ADD COLUMN redirect_event_id INT UNSIGNED NULL AFTER deleted");
        $this->addSql("ALTER TABLE {$tableName} ADD CONSTRAINT {$this->getForeignKeyName()} FOREIGN KEY (redirect_event_id) REFERENCES {$tableName} (id) ON DELETE SET NULL");
    }

    public function down(Schema $schema): void
    {
        $tableName = $this->getTableName();

        $this->addSql("ALTER TABLE {$tableName} DROP FOREIGN KEY {$this->getForeignKeyName()}");
        $this->addSql("ALTER TABLE {$tableName} DROP COLUMN redirect_event_id");
    }

    private function getTableName(): string
    {
        return $this->getPrefixedTableName(Event::TABLE_NAME);
    }

    private function getForeignKeyName(): string
    {
        return $this->generatePropertyName($this->getTableName(), 'fk', ['redirect_event_id']);
    }
}
