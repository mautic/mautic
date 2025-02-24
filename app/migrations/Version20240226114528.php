<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
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
        $tableName = $this->getTableName();

        $this->addSql(sprintf('
            CREATE TABLE %s (
                email_id INT %s NOT NULL,
                leadlist_id INT %s NOT NULL,
                INDEX %s (email_id),
                INDEX %s (leadlist_id),
                PRIMARY KEY(email_id, leadlist_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC',
            $tableName,
            $this->getIntegerRange($schema, 'emails'),
            $this->getIntegerRange($schema, 'lead_lists'),
            $this->getIndexName('email_id'),
            $this->getIndexName('leadlist_id')
        ));
        $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (email_id) REFERENCES %semails (id) ON DELETE CASCADE',
            $tableName,
            $this->getForeignKeyName('email_id'),
            $this->prefix
        ));
        $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (leadlist_id) REFERENCES %slead_lists (id) ON DELETE CASCADE',
            $tableName,
            $this->getForeignKeyName('leadlist_id'),
            $this->prefix
        ));
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

    private function getIntegerRange(Schema $schema, string $bareTableName): string
    {
        $unsigned = $schema->getTable("{$this->prefix}{$bareTableName}")
            ->getColumn('id')
            ->getUnsigned();

        return $unsigned ? 'UNSIGNED' : '';
    }
}
