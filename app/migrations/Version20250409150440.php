<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\ProjectBundle\Entity\Project;

final class Version20250409150440 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName(Project::TABLE_NAME)),
            'Table '.Project::TABLE_NAME.' already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $projectsTable = $schema->createTable($this->getPrefixedTableName(Project::TABLE_NAME));

        $projectsTable->addColumn('id', Types::INTEGER, ['autoincrement' => true, 'unsigned' => true]);
        $projectsTable->addColumn('name', Types::STRING, ['length' => 191]);
        $projectsTable->addColumn('description', Types::TEXT, ['notnull' => false]);
        $projectsTable->addColumn('properties', Types::JSON, ['notnull' => true]);
        $projectsTable->addColumn('uuid', Types::GUID, ['notnull' => false]);
        $projectsTable->addColumn('is_published', Types::BOOLEAN, ['notnull' => true]);
        $projectsTable->addColumn('checked_out', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $projectsTable->addColumn('checked_out_by', Types::INTEGER, ['notnull' => false]);
        $projectsTable->addColumn('checked_out_by_user', Types::STRING, ['length' => 191, 'notnull' => false]);
        $projectsTable->addColumn('date_added', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $projectsTable->addColumn('created_by', Types::INTEGER, ['notnull' => false]);
        $projectsTable->addColumn('created_by_user', Types::STRING, ['length' => 191, 'notnull' => false]);
        $projectsTable->addColumn('date_modified', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $projectsTable->addColumn('modified_by', Types::INTEGER, ['notnull' => false]);
        $projectsTable->addColumn('modified_by_user', Types::STRING, ['length' => 191, 'notnull' => false]);

        $projectsTable->setPrimaryKey(['id']);
        $projectsTable->addIndex(['name'], $this->prefix.'project_name');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->getPrefixedTableName(Project::TABLE_NAME));
    }
}
