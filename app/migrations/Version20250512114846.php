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
            fn (Schema $schema) => $schema->getTable($this->getTableName())
                ->hasColumn('redirect_event_id'),
            'Column redirect_event_id already exists in campaign_events table'
        );
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getTableName();

        $table = $schema->getTable($tableName);
        $table->addColumn('redirect_event_id', 'integer', ['unsigned' => $table->getColumn('id')->getUnsigned(), 'notnull' => false]);
        $table->addForeignKeyConstraint($tableName, ['redirect_event_id'], ['id'], ['onDelete' => 'SET NULL'], $this->getForeignKeyName());
    }

    public function down(Schema $schema): void
    {
        $tableName = $this->getTableName();

        $table = $schema->getTable($tableName);
        $table->removeForeignKey($this->getForeignKeyName());
        $table->dropColumn('redirect_event_id');
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
