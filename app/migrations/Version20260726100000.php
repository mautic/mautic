<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260726100000 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'campaign_lead_event_log';
    protected const INDEX_NAME = 'campaign_leads';

    private const CAMPAIGN_FIRST_COLUMNS = ['campaign_id', 'lead_id', 'rotation'];
    private const LEAD_FIRST_COLUMNS     = ['lead_id', 'campaign_id', 'rotation'];

    private const LEAD_ID_INDEX_NAME = 'campaign_lead_id';
    private const LEAD_ID_COLUMNS    = ['lead_id'];

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $this->hasIndexWithColumns($schema, self::CAMPAIGN_FIRST_COLUMNS),
            'A campaign-first campaign lead event log index already exists'
        );
        $this->skipAssertion(
            fn (Schema $schema) => $this->hasIndexWithColumns($schema, self::LEAD_ID_COLUMNS),
            'A lead_id campaign lead event log index already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);
        $table     = $schema->getTable($tableName);
        $indexName = $this->getIndexName();

        if (!$table->hasIndex($indexName)) {
            $this->addSql("CREATE INDEX {$indexName} ON {$tableName} (campaign_id, lead_id, rotation);");
        } else {
            $index = $table->getIndex($indexName);
            if ($this->indexHasColumns($index, self::LEAD_FIRST_COLUMNS)) {
                $this->addSql("ALTER TABLE {$tableName} DROP INDEX {$indexName}, ADD INDEX {$indexName} (campaign_id, lead_id, rotation);");
            }
        }

        // The campaign-first index above can no longer serve lead_id-only lookups
        // (e.g. LeadEventLogRepository::updateLead() during lead merges), so keep a
        // dedicated single-column index for that access path.
        if (!$this->hasIndexWithColumns($schema, self::LEAD_ID_COLUMNS)) {
            $leadIdIndexName = "{$this->prefix}".self::LEAD_ID_INDEX_NAME;
            $this->addSql("CREATE INDEX {$leadIdIndexName} ON {$tableName} (lead_id);");
        }
    }

    public function down(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName(self::TABLE_NAME);
        $table     = $schema->getTable($tableName);
        $indexName = $this->getIndexName();

        if ($table->hasIndex($indexName) && $this->indexHasColumns($table->getIndex($indexName), self::CAMPAIGN_FIRST_COLUMNS)) {
            $this->addSql("ALTER TABLE {$tableName} DROP INDEX {$indexName}, ADD INDEX {$indexName} (lead_id, campaign_id, rotation);");
        }

        $leadIdIndexName = "{$this->prefix}".self::LEAD_ID_INDEX_NAME;
        if ($table->hasIndex($leadIdIndexName)) {
            $this->addSql("DROP INDEX {$leadIdIndexName} ON {$tableName};");
        }
    }

    /**
     * @param string[] $columns
     */
    private function hasIndexWithColumns(Schema $schema, array $columns): bool
    {
        foreach ($schema->getTable($this->getPrefixedTableName(self::TABLE_NAME))->getIndexes() as $index) {
            if ($this->indexHasColumns($index, $columns)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[] $columns
     */
    private function indexHasColumns(Index $index, array $columns): bool
    {
        return $index->getColumns() === $columns;
    }

    private function getIndexName(): string
    {
        return "{$this->prefix}".self::INDEX_NAME;
    }
}
