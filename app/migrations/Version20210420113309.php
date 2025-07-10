<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20210420113309 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getTableName())->hasIndex($this->getIndexName()),
            "Index {$this->getIndexName()} already exists"
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE INDEX {$this->getIndexName()} ON {$this->getTableName()} (alias)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX {$this->getIndexName()} ON {$this->getTableName()}");
    }

    private function getTableName(): string
    {
        return "{$this->prefix}lead_lists";
    }

    private function getIndexName(): string
    {
        return "{$this->prefix}lead_list_alias";
    }
}
