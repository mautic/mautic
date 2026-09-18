<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20220120123310 extends PreUpAssertionMigration
{
    protected const TABLE = 'lead_lists';

    private function getTableName(): string
    {
        return $this->prefix.self::TABLE;
    }

    private function getIndexName(): string
    {
        return $this->prefix.'segment_deleted';
    }

    protected function preUpAssertions(): void
    {
        $tableName = $this->getTableName();
        $indexName = $this->getIndexName();

        $this->skipAssertion(
            fn (Schema $schema) => $this->indexExists($tableName, $indexName),
            "Index {$indexName} cannot be created because the index already exists"
        );
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getTableName();
        $indexName = $this->getIndexName();

        if (!$schema->hasTable($tableName) || $this->indexExists($tableName, $indexName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        $table->addIndex(['deleted'], $indexName);
    }
}
