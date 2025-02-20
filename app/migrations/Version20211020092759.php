<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\LeadBundle\Field\Helper\IndexHelper;

final class Version20211020092759 extends PreUpAssertionMigration
{
    private const TABLE = 'leads';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            function (Schema $schema) {
                $table = $schema->getTable($this->getPrefixedTableName(self::TABLE));

                return count($table->getIndexes()) >= IndexHelper::MAX_COUNT_ALLOWED || $table->hasIndex($this->getIndexName());
            },
            "Index {$this->getIndexName()} cannot be created because the {$this->getPrefixedTableName(self::TABLE)} has hit the table index limit or the index already exists"
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE INDEX {$this->getIndexName()} ON {$this->getPrefixedTableName(self::TABLE)} (date_modified)");
    }

    private function getIndexName(): string
    {
        return $this->prefix.'lead_date_modified';
    }
}
