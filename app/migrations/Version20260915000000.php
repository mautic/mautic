<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260915000000 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema): bool => $this->hasEmailFirstIndex($schema),
            'An e-mail lookup index already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf('CREATE INDEX %s ON %s (email)', $this->getIndexName(), $this->getTableName()));
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable($this->getTableName())->hasIndex($this->getIndexName())) {
            $this->addSql(sprintf('DROP INDEX %s ON %s', $this->getIndexName(), $this->getTableName()));
        }
    }

    private function hasEmailFirstIndex(Schema $schema): bool
    {
        foreach ($schema->getTable($this->getTableName())->getIndexes() as $index) {
            if ('email' === ($index->getColumns()[0] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function getTableName(): string
    {
        return "{$this->prefix}leads";
    }

    private function getIndexName(): string
    {
        return "{$this->prefix}email_search";
    }
}
