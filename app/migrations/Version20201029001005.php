<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\PageBundle\Entity\PageDraft;

final class Version20201029001005 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->hasTable($this->getPrefixedTableName(PageDraft::TABLE_NAME))) {
            throw new SkipMigration(sprintf('Table %s already exists. Skipping migration', $this->getPrefixedTableName(PageDraft::TABLE_NAME)));
        }
    }

    public function up(Schema $schema): void
    {
        $table = new Table($this->getPrefixedTableName(PageDraft::TABLE_NAME));
        $table->addColumn('id', Types::INTEGER, ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('page_id', Types::INTEGER, ['unsigned' => $schema->getTable($this->getPrefixedTableName('pages'))->getColumn('id')->getUnsigned()]);
        $table->addColumn('html', Types::TEXT, ['length' => 4294967295, 'notnull' => false]);
        $table->addColumn('template', Types::STRING, ['length' => 191, 'notnull' => false]);
        $table->addColumn('public_preview', Types::BOOLEAN, ['default' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['page_id'], $this->generatePropertyName('pages_draft', 'uniq', ['page_id']));
        $table->addForeignKeyConstraint($this->getPrefixedTableName('pages'), ['page_id'], ['id'], [], $this->generatePropertyName('pages_draft', 'fk', ['page_id']));
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
        $schema->dropTable($this->getPrefixedTableName(PageDraft::TABLE_NAME));
    }
}
