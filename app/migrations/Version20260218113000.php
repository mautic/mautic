<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260218113000 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'companies_tags_xref';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName(self::TABLE_NAME)),
            'Table '.self::TABLE_NAME.' already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $companyIdDataType = $this->getColumnTypeSignedOrUnsigned($schema, 'companies', 'id');
        $tagIdDataType     = $this->getColumnTypeSignedOrUnsigned($schema, 'lead_tags', 'id');

        $table = $schema->createTable($this->prefix.self::TABLE_NAME);
        $table->addColumn('company_id', 'integer', ['unsigned' => 'UNSIGNED' === $companyIdDataType, 'notnull' => true]);
        $table->addColumn('tag_id', 'integer', ['unsigned' => 'UNSIGNED' === $tagIdDataType, 'notnull' => true]);
        $table->setPrimaryKey(['company_id', 'tag_id']);
        $table->addIndex(['tag_id'], $this->generatePropertyName(self::TABLE_NAME, 'idx', ['tag_id']));
        $table->addForeignKeyConstraint($this->prefix.'companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint($this->prefix.'lead_tags', ['tag_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->prefix.self::TABLE_NAME);
    }
}
