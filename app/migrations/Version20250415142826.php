<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250415142826 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'campaign_lead_event_log';

    private function getTableName(): string
    {
        return $this->prefix.self::TABLE_NAME;
    }

    private function getIndexName(): string
    {
        return "{$this->prefix}idx_scheduled_events";
    }

    protected function preUpAssertions(): void
    {
        $tableName = $this->getTableName();
        $indexName = $this->getIndexName();

        $this->skipAssertion(
            fn (Schema $schema) => $this->indexExists($tableName, $indexName),
            'Index idx_scheduled_events already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getTableName();
        $indexName = $this->getIndexName();

        if (!$schema->hasTable($tableName) || $this->indexExists($tableName, $indexName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        $table->addIndex(['is_scheduled', 'event_id', 'trigger_date'], $indexName);
    }

    public function down(Schema $schema): void
    {
        $tableName = $this->getTableName();
        $indexName = $this->getIndexName();

        if (!$schema->hasTable($tableName) || !$this->indexExists($tableName, $indexName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        $table->dropIndex($indexName);
    }
}
