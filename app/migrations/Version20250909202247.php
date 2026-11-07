<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\ProjectBundle\Entity\Project;

final class Version20250909202247 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = Project::TABLE_NAME;

    private function getTableName(): string
    {
        return $this->prefix.self::TABLE_NAME;
    }

    private function getNewIndexName(): string
    {
        return $this->prefix.'unique_project_name';
    }

    private function getOldIndexName(): string
    {
        return $this->prefix.'project_name';
    }

    protected function preUpAssertions(): void
    {
        $tableName = $this->getTableName();

        $this->skipAssertion(
            fn (Schema $schema) => $this->indexExists($tableName, $this->getNewIndexName()),
            sprintf('Index %s already exists', $this->getNewIndexName())
        );
    }

    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $tableName = $this->getTableName();
        $table     = $schema->getTable($tableName);

        $oldIndexName = $this->getOldIndexName();
        $newIndexName = $this->getNewIndexName();

        // Drop old non-unique index if it exists
        if ($this->indexExists($tableName, $oldIndexName)) {
            $table->dropIndex($oldIndexName);
        }

        // Add new unique index (idempotent)
        if (!$this->indexExists($tableName, $newIndexName)) {
            $table->addUniqueIndex(['name'], $newIndexName);
        }
    }

    /**
     * @throws SchemaException
     */
    public function down(Schema $schema): void
    {
        $tableName = $this->getTableName();
        $table     = $schema->getTable($tableName);

        $oldIndexName = $this->getOldIndexName();
        $newIndexName = $this->getNewIndexName();

        // Drop new unique index if it exists
        if ($this->indexExists($tableName, $newIndexName)) {
            $table->dropIndex($newIndexName);
        }

        // Add back old non-unique index (idempotent)
        if (!$this->indexExists($tableName, $oldIndexName)) {
            $table->addIndex(['name'], $oldIndexName);
        }
    }
}
