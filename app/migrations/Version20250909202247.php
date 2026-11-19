<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\ProjectBundle\Entity\Project;

final class Version20250909202247 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = Project::TABLE_NAME;

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME))->hasIndex($this->getIndexName());
        }, sprintf('Index %s already exists', $this->getIndexName()));
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);
        $table     = $schema->getTable($tableName);

        $oldIndexName = $this->prefix.'project_name';

        if ($table->hasIndex($oldIndexName)) {
            $this->addSql("ALTER TABLE {$tableName} DROP INDEX {$oldIndexName};");
        }

        $table->addUniqueIndex(['name'], $this->getIndexName());
    }

    /**
     * @throws SchemaException
     */
    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME));

        $table->dropIndex($this->getIndexName());
        $table->addIndex(['name'], $this->prefix.'project_name');
    }

    private function getIndexName(): string
    {
        return $this->prefix.'unique_project_name';
    }
}
