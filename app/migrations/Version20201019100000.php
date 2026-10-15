<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20201019100000 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'emails_draft';

    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->hasTable($this->getPrefixedTableName())) {
            throw new SkipMigration(sprintf('Table %s already exists. Skipping migration', $this->getPrefixedTableName()));
        }
    }

    public function up(Schema $schema): void
    {
        $table = new Table($this->getPrefixedTableName());
        $table->addColumn('id', Types::INTEGER, ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('email_id', Types::INTEGER, ['unsigned' => $schema->getTable($this->getPrefixedTableName('emails'))->getColumn('id')->getUnsigned()]);
        $table->addColumn('html', Types::TEXT, ['length' => 4294967295, 'notnull' => false]);
        $table->addColumn('template', Types::STRING, ['length' => 191, 'notnull' => false]);
        $table->addColumn('public_preview', Types::BOOLEAN, ['default' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email_id'], $this->generatePropertyName('emails_draft', 'uniq', ['email_id']));
        $table->addForeignKeyConstraint($this->getPrefixedTableName('emails'), ['email_id'], ['id'], [], $this->generatePropertyName('emails_draft', 'fk', ['email_id']));
        $table->addOption('charset', 'utf8mb4');
        $table->addOption('collation', 'utf8mb4_unicode_ci');
        $table->addOption('engine', 'InnoDB');
        $table->addOption('row_format', 'DYNAMIC');

        foreach ($this->platform->getCreateTableSQL($table, AbstractPlatform::CREATE_INDEXES | AbstractPlatform::CREATE_FOREIGNKEYS) as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->getPrefixedTableName());
    }
}
