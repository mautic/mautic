<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260616075543 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable("{$this->prefix}point_insights"),
            "Table {$this->prefix}point_insights already exists"
        );
    }

    public function up(Schema $schema): void
    {
        $table = new Table("{$this->prefix}point_insights");
        $table->addColumn('id', Types::INTEGER, ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('category_id', Types::INTEGER, ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('is_published', Types::BOOLEAN);

        foreach (['date_added', 'date_modified', 'checked_out'] as $column) {
            $table->addColumn($column, Types::DATETIME_MUTABLE, ['notnull' => false]);
        }
        foreach (['created_by', 'modified_by', 'checked_out_by'] as $column) {
            $table->addColumn($column, Types::INTEGER, ['notnull' => false]);
            $table->addColumn($column.'_user', Types::STRING, ['length' => 191, 'notnull' => false]);
        }
        foreach (['name', 'insight_type', 'insight_action'] as $column) {
            $table->addColumn($column, Types::STRING, ['length' => 191]);
        }
        $table->addColumn('custom_field', Types::STRING, ['length' => 191, 'notnull' => false]);
        $table->addColumn('description', Types::TEXT, ['notnull' => false]);
        $table->addColumn('point_groups', Types::JSON);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['category_id'], $this->generatePropertyName('point_insights', 'idx', ['category_id']));
        $table->addForeignKeyConstraint("{$this->prefix}categories", ['category_id'], ['id'], ['onDelete' => 'SET NULL'], $this->generatePropertyName('point_insights', 'fk', ['category_id']));

        foreach ($this->platform->getCreateTableSQL($table, AbstractPlatform::CREATE_INDEXES | AbstractPlatform::CREATE_FOREIGNKEYS) as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable("{$this->prefix}point_insights")) {
            $this->addSql($this->platform->getDropTableSQL("{$this->prefix}point_insights"));
        }
    }
}
