<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250428071743 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'form_projects_xref';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName(self::TABLE_NAME)),
            'Table '.self::TABLE_NAME.' already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $targetIdDataType  = $this->getColumnTypeSignedOrUnsigned($schema, 'forms', 'id');
        $projectIdDataType = $this->getColumnTypeSignedOrUnsigned($schema, 'projects', 'id');

        $table = $schema->createTable($this->prefix.'form_projects_xref');
        $table->addColumn('form_id', 'integer', ['unsigned' => 'UNSIGNED' === $targetIdDataType, 'notnull' => true]);
        $table->addColumn('project_id', 'integer', ['unsigned' => 'UNSIGNED' === $projectIdDataType, 'notnull' => true]);
        $table->setPrimaryKey(['form_id', 'project_id']);
        $table->addForeignKeyConstraint($this->prefix.'forms', ['form_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint($this->prefix.'projects', ['project_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function postUp(Schema $schema): void
    {
        $index = $this->generatePropertyName('form_projects_xref', 'idx', ['form_id']);
        $this->connection->executeStatement(sprintf('DROP INDEX %s ON %s', $index, $this->prefix.'form_projects_xref'));
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->prefix.'form_projects_xref');
    }
}
