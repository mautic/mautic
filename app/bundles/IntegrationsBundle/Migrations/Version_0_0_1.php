<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

final class Version_0_0_1 extends AbstractMigration
{
    private string $table = 'sync_object_mapping';

    protected function isApplicable(Schema $schema): bool
    {
        try {
            return !$schema->getTable($this->concatPrefix($this->table))->hasColumn('integration_reference_id');
        } catch (SchemaException) {
            return false;
        }
    }

    protected function up(): void
    {
        $connection    = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $currentTable  = $schemaManager->introspectTable($this->concatPrefix($this->table));
        $updatedTable  = clone $currentTable;

        $updatedTable->dropIndex($this->concatPrefix('integration_object'));
        $updatedTable->addColumn('integration_reference_id', 'string', ['length' => 191, 'notnull' => false]);
        $updatedTable->addIndex(
            ['integration', 'integration_object_name', 'integration_object_id', 'integration_reference_id'],
            $this->concatPrefix('integration_object')
        );
        $updatedTable->addIndex(
            ['integration', 'integration_object_name', 'integration_reference_id', 'integration_object_id'],
            $this->concatPrefix('integration_reference')
        );

        $tableDiff = $schemaManager->createComparator()->compareTables($currentTable, $updatedTable);
        foreach ($connection->getDatabasePlatform()->getAlterTableSQL($tableDiff) as $sql) {
            $this->addSql($sql);
        }
    }
}
