<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\LeadBundle\Entity\LeadListTranslation;

final class Version20261104090000 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName(LeadListTranslation::TABLE_NAME)),
            'Table '.LeadListTranslation::TABLE_NAME.' already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $leadListIdDataType = $this->getColumnTypeSignedOrUnsigned($schema, 'lead_lists', 'id');

        $table = $schema->createTable($this->getPrefixedTableName(LeadListTranslation::TABLE_NAME));
        $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
        $table->addColumn('leadlist_id', 'integer', ['unsigned' => 'UNSIGNED' === $leadListIdDataType]);
        $table->addColumn('locale', 'string', ['length' => 16]);
        $table->addColumn('public_name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['leadlist_id', 'locale'], $this->prefix.'lead_list_translation_locale');
        $table->addForeignKeyConstraint($this->getPrefixedTableName('lead_lists'), ['leadlist_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->getPrefixedTableName(LeadListTranslation::TABLE_NAME));
    }
}
