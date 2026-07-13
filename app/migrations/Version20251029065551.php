<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20251029065551 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        // Idempotency: if the index already exists, skip running the async ALTER.
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable($this->getTableName())->hasIndex($this->getIndexName());
        }, sprintf('Index %s already exists', $this->getIndexName()));
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
