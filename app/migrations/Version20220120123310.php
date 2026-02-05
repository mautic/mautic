<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20220120123310 extends PreUpAssertionMigration
{
    private const TABLE = 'lead_lists';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            function (Schema $schema) {
                $table = $schema->getTable($this->getPrefixedTableName(self::TABLE));

                return $table->hasIndex($this->getIndexName());
            },
            "Index {$this->getIndexName()} cannot be created because the index already exists"
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE INDEX {$this->getIndexName()} ON {$this->getPrefixedTableName(self::TABLE)} (deleted)");
    }

    private function getIndexName(): string
    {
        return $this->prefix.'segment_deleted';
    }
}
