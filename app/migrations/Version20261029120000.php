<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\LeadBundle\Entity\TrackingPreference;

final class Version20261029120000 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName(TrackingPreference::TABLE_NAME)),
            'Table '.TrackingPreference::TABLE_NAME.' already exists'
        );
    }

    public function getDescription(): string
    {
        return 'Store the tracking choices a contact makes';
    }

    public function up(Schema $schema): void
    {
        $leadIdDataType = $this->getColumnTypeSignedOrUnsigned($schema, 'leads', 'id');

        $table = $schema->createTable($this->getPrefixedTableName(TrackingPreference::TABLE_NAME));
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('lead_id', 'bigint', ['unsigned' => self::COLUMN_TYPE_UNSIGNED === $leadIdDataType]);
        $table->addColumn('purpose', 'string', ['length' => 50]);
        $table->addColumn('allowed', 'boolean');
        $table->addColumn('source', 'string', ['length' => 191, 'notnull' => false]);
        $table->addColumn('date_modified', 'datetime_immutable');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['lead_id'], $this->generatePropertyName(TrackingPreference::TABLE_NAME, 'idx', ['lead_id']));
        $table->addUniqueIndex(['lead_id', 'purpose'], 'unique_lead_purpose');
        $table->addForeignKeyConstraint(
            $this->getPrefixedTableName('leads'),
            ['lead_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            $this->generatePropertyName(TrackingPreference::TABLE_NAME, 'fk', ['lead_id'])
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->getPrefixedTableName(TrackingPreference::TABLE_NAME));
    }
}
