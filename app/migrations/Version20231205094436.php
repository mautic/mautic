<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\LeadBundle\Entity\ListLead;

final class Version20231205094436 extends PreUpAssertionMigration
{
    private function getTableName(): string
    {
        return $this->prefix.ListLead::TABLE_NAME;
    }

    private function getIndexName(): string
    {
        return "{$this->prefix}lead_id_lists_id_removed";
    }

    protected function preUpAssertions(): void
    {
        $tableName = $this->getTableName();
        $indexName = $this->getIndexName();

        $this->skipAssertion(
            fn (Schema $schema) => $this->indexExists($tableName, $indexName),
            sprintf('Index %s already exists', $indexName)
        );
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getTableName();
        $indexName = $this->getIndexName();
        $table     = $schema->getTable($tableName);

        if (!$this->indexExists($tableName, $indexName)) {
            $table->addIndex(
                ['lead_id', 'leadlist_id', 'manually_removed'],
                $this->getIndexName()
            );
        }
    }

    public function down(Schema $schema): void
    {
        $tableName = $this->getTableName();
        $indexName = $this->getIndexName();
        $table     = $schema->getTable($tableName);

        if ($this->indexExists($tableName, $indexName)) {
            $table->dropIndex($this->getIndexName());
        }
    }
}
