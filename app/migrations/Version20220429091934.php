<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20220429091934 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->hasTable("{$this->prefix}contact_export_scheduler");
        }, sprintf('Table %s already exists', "{$this->prefix}contact_export_scheduler"));
    }

    public function up(Schema $schema): void
    {
        $table = new Table($this->getPrefixedTableName('contact_export_scheduler'));
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('user_id', Types::INTEGER, ['unsigned' => $schema->getTable($this->getPrefixedTableName('users'))->getColumn('id')->getUnsigned()]);
        $table->addColumn('scheduled_datetime', Types::DATETIME_IMMUTABLE);
        $table->addColumn('data', 'array', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint($this->getPrefixedTableName('users'), ['user_id'], ['id'], [], $this->generatePropertyName('users', 'fk', ['user_id']));
        $table->addOption('charset', 'utf8mb4');
        $table->addOption('collation', 'utf8mb4_unicode_ci');
        $table->addOption('engine', 'InnoDB');

        foreach ($this->platform->getCreateTableSQL($table, AbstractPlatform::CREATE_INDEXES | AbstractPlatform::CREATE_FOREIGNKEYS) as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql($this->platform->getDropTableSQL($this->getPrefixedTableName('contact_export_scheduler')));
    }
}
