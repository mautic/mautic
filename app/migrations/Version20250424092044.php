<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250424092044 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'campaign_projects_xref';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName(self::TABLE_NAME)),
            'Table '.self::TABLE_NAME.' already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $targetIdDataType  = $this->getColumnTypeSignedOrUnsigned($schema, 'campaigns', 'id');
        $projectIdDataType = $this->getColumnTypeSignedOrUnsigned($schema, 'projects', 'id');

        $table = $schema->createTable($this->getPrefixedTableName(self::TABLE_NAME));
        $table->addColumn('campaign_id', 'integer', ['unsigned' => 'UNSIGNED' === $targetIdDataType, 'notnull' => true]);
        $table->addColumn('project_id', 'integer', ['unsigned' => 'UNSIGNED' === $projectIdDataType, 'notnull' => true]);
        $table->setPrimaryKey(['campaign_id', 'project_id']);
        $table->addForeignKeyConstraint($this->prefix.'campaigns', ['campaign_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint($this->prefix.'projects', ['project_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function postUp(Schema $schema): void
    {
        $index = $this->generatePropertyName(self::TABLE_NAME, 'idx', ['campaign_id']);

        $this->dropIndex(
            $this->getPrefixedTableName(self::TABLE_NAME),
            $index
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->getPrefixedTableName(self::TABLE_NAME));
    }
}
