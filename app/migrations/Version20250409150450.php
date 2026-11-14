<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250409150450 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'lead_list_projects_xref';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName(self::TABLE_NAME)),
            'Table '.self::TABLE_NAME.' already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $leadListIdDataType = $this->getColumnTypeSignedOrUnsigned($schema, 'lead_lists', 'id');
        $projectIdDataType  = $this->getColumnTypeSignedOrUnsigned($schema, 'projects', 'id');

        $table = $schema->createTable(self::TABLE_NAME);
        $table->addColumn('leadlist_id', 'integer', ['unsigned' => 'UNSIGNED' === $leadListIdDataType, 'notnull' => true]);
        $table->addColumn('project_id', 'integer', ['unsigned' => 'UNSIGNED' === $projectIdDataType, 'notnull' => true]);
        $table->setPrimaryKey(['leadlist_id', 'project_id']);
        $table->addForeignKeyConstraint($this->prefix.'lead_lists', ['leadlist_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint($this->prefix.'projects', ['project_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function postUp(Schema $schema): void
    {
        $indexName = $this->generatePropertyName(self::TABLE_NAME, 'idx', ['leadlist_id']);

        $this->dropIndex(
            $this->getPrefixedTableName(self::TABLE_NAME),
            $indexName
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->getPrefixedTableName(self::TABLE_NAME));
    }
}
