<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20211026153057 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable($this->getTableName())->hasIndex($this->getIndexName());
        }, sprintf('Index %s already exists', $this->getIndexName()));
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE INDEX {$this->getIndexName()} ON {$this->getTableName()} (`lead_id`, `date_added`)");
    }

    private function getTableName(): string
    {
        return $this->getPrefixedTableName('lead_frequencyrules');
    }

    private function getIndexName(): string
    {
        return "{$this->prefix}idx_frequency_date_added";
    }
}
