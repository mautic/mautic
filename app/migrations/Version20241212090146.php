<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20241212090146 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'sync_object_mapping';

    private function getTableName(): string
    {
        return $this->prefix.self::TABLE_NAME;
    }

    private function getIndexName(): string
    {
        return $this->prefix.'internal_object_id_idx';
    }

    protected function preUpAssertions(): void
    {
        $tableName = $this->getTableName();
        $indexName = $this->getIndexName();
        $this->skipAssertion(
            fn (Schema $schema) => !$schema->hasTable($tableName) || $this->indexExists($tableName, $indexName),
            sprintf('Table %s does not exist or the index %s already exists.', $tableName, $indexName)
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
        $table->addIndex(['internal_object_id'], $indexName);
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
