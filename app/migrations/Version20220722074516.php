<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20220722074516 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'notifications';
    protected const INDEX_NAME = 'deduplicate_date_added';

    public function preUp(Schema $schema): void
    {
        $table = $this->getPrefixedTableName(self::TABLE_NAME);

        if ($schema->getTable($table)->hasColumn('deduplicate')) {
            throw new SkipMigration("The deduplicate column has already been added to the {$table} table.");
        }
    }

    public function up(Schema $schema): void
    {
        $table = $this->getPrefixedTableName(self::TABLE_NAME);

        $this->addSql("ALTER TABLE {$table} ADD deduplicate VARCHAR(32) DEFAULT NULL");

        $this->createIndex(
            $table,
            $this->getPrefixedIndexName(self::INDEX_NAME),
            ['deduplicate', 'date_added']
        );
    }

    public function down(Schema $schema): void
    {
        $table = $this->getPrefixedTableName(self::TABLE_NAME);

        $this->dropIndex(
            $table,
            $this->getPrefixedIndexName(self::INDEX_NAME),
        );

        $this->addSql("ALTER TABLE {$table} DROP deduplicate");
    }
}
