<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20240226114528 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->hasTable($this->getTableName());
        }, sprintf('Table %s already exists', $this->getTableName()));

        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable($this->getTableName())
                ->hasForeignKey($this->getForeignKeyName('email_id'));
        }, sprintf('Foreign key %s already exists', $this->getForeignKeyName('email_id')));

        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable($this->getTableName())
                ->hasForeignKey($this->getForeignKeyName('leadlist_id'));
        }, sprintf('Foreign key %s already exists', $this->getForeignKeyName('leadlist_id')));
    }

    public function up(Schema $schema): void
    {
        $table = new Table($this->getTableName());
        $table->addColumn('email_id', Types::INTEGER, ['unsigned' => $schema->getTable($this->getPrefixedTableName('emails'))->getColumn('id')->getUnsigned()]);
        $table->addColumn('leadlist_id', Types::INTEGER, ['unsigned' => $schema->getTable($this->getPrefixedTableName('lead_lists'))->getColumn('id')->getUnsigned()]);
        $table->addIndex(['email_id'], $this->getIndexName('email_id'));
        $table->addIndex(['leadlist_id'], $this->getIndexName('leadlist_id'));
        $table->setPrimaryKey(['email_id', 'leadlist_id']);
        $table->addForeignKeyConstraint($this->getPrefixedTableName('emails'), ['email_id'], ['id'], ['onDelete' => 'CASCADE'], $this->getForeignKeyName('email_id'));
        $table->addForeignKeyConstraint($this->getPrefixedTableName('lead_lists'), ['leadlist_id'], ['id'], ['onDelete' => 'CASCADE'], $this->getForeignKeyName('leadlist_id'));
        $table->addOption('charset', 'utf8mb4');
        $table->addOption('collation', 'utf8mb4_unicode_ci');
        $table->addOption('engine', 'InnoDB');
        $table->addOption('row_format', 'DYNAMIC');

        foreach ($this->platform->getCreateTableSQL($table, AbstractPlatform::CREATE_INDEXES | AbstractPlatform::CREATE_FOREIGNKEYS) as $sql) {
            $this->addSql($sql);
        }
    }

    private function getTableName(): string
    {
        return "{$this->prefix}{$this->getBareTableName()}";
    }

    private function getBareTableName(): string
    {
        return 'email_list_excluded';
    }

    private function getIndexName(string $column): string
    {
        return $this->generatePropertyName($this->getBareTableName(), 'idx', [$column]);
    }

    private function getForeignKeyName(string $column): string
    {
        return $this->generatePropertyName($this->getBareTableName(), 'fk', [$column]);
    }
}
