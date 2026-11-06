<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Tag;

final class Version20260218113000 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = Company::TAGS_XREF_TABLE_NAME;

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName(self::TABLE_NAME)),
            'Table '.self::TABLE_NAME.' already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $companyIdDataType = $this->getColumnTypeSignedOrUnsigned($schema, Company::TABLE_NAME, 'id');
        $tagIdDataType     = $this->getColumnTypeSignedOrUnsigned($schema, Tag::TABLE_NAME, 'id');

        $table = $schema->createTable($this->prefix.self::TABLE_NAME);
        $table->addColumn('company_id', Types::INTEGER, ['unsigned' => 'UNSIGNED' === $companyIdDataType, 'notnull' => true]);
        $table->addColumn('tag_id', Types::INTEGER, ['unsigned' => 'UNSIGNED' === $tagIdDataType, 'notnull' => true]);
        $table->setPrimaryKey(['company_id', 'tag_id']);
        $table->addIndex(['tag_id'], $this->generatePropertyName(self::TABLE_NAME, 'idx', ['tag_id']));
        $table->addForeignKeyConstraint($this->prefix.Company::TABLE_NAME, ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint($this->prefix.Tag::TABLE_NAME, ['tag_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->prefix.self::TABLE_NAME);
    }
}
