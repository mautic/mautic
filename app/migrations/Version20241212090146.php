<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20241212090146 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'sync_object_mapping';

    private string $indexName = MAUTIC_TABLE_PREFIX.'internal_object_id_idx';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => !$schema->hasTable($this->getPrefixedTableName(self::TABLE_NAME))
                || $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME))->hasIndex($this->indexName),
            "Table {$this->getPrefixedTableName(self::TABLE_NAME)} does not exist or the index {$this->indexName} already exists."
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE INDEX {$this->indexName} ON {$this->getPrefixedTableName(self::TABLE_NAME)} (internal_object_id);");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX {$this->indexName} ON {$this->getPrefixedTableName(self::TABLE_NAME)};");
    }
}
