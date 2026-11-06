<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\LeadBundle\Field\Helper\IndexHelper;

final class Version20260629065551 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            $table = $schema->getTable($this->getTableName());

            return count($table->getIndexes()) >= IndexHelper::MAX_COUNT_ALLOWED || $table->hasIndex($this->getIndexName());
        }, sprintf('Index %s cannot be created because the %s table has hit the index limit or the index already exists', $this->getIndexName(), $this->getTableName()));
    }

    public function up(Schema $schema): void
    {
        $query = sprintf(
            'ALTER TABLE %s ADD INDEX %s (last_active)',
            $this->getTableName(),
            $this->getIndexName()
        );

        $this->addSql($query);
    }

    private function getTableName(): string
    {
        return "{$this->prefix}leads";
    }

    private function getIndexName(): string
    {
        return "{$this->prefix}last_active";
    }
}
