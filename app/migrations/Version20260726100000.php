<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260726100000 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'campaign_lead_event_log';
    protected const INDEX_NAME = 'campaign_event_lead_ids';

    public const CAMPAIGN_FIRST_COLUMNS = ['campaign_id', 'lead_id', 'rotation'];
    public const LEAD_FIRST_COLUMNS     = ['lead_id', 'campaign_id', 'rotation'];

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $this->indexExists($this->getPrefixedTableName(), $this->getPrefixedIndexName(), self::CAMPAIGN_FIRST_COLUMNS),
            'A campaign-first campaign lead event log index already exists'
        );
    }

    public function up(Schema $schema): void
    {
        if (!$this->indexExists($this->getPrefixedTableName(), $this->getPrefixedIndexName())) {
            $this->createIndex(
                $this->getPrefixedTableName(),
                $this->getPrefixedIndexName(),
                self::CAMPAIGN_FIRST_COLUMNS
            );

            return;
        }

        // Index exists, but its not campaign first
        if (!$this->indexExists($this->getPrefixedTableName(), $this->getPrefixedIndexName(), self::CAMPAIGN_FIRST_COLUMNS)) {
            $this->dropIndex(
                $this->getPrefixedTableName(),
                $this->getPrefixedIndexName()
            );

            $this->createIndex(
                $this->getPrefixedTableName(),
                $this->getPrefixedIndexName(),
                self::CAMPAIGN_FIRST_COLUMNS
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->indexExists($this->getPrefixedTableName(), $this->getPrefixedIndexName(), self::CAMPAIGN_FIRST_COLUMNS)) {
            $this->dropIndex(
                $this->getPrefixedTableName(),
                $this->getPrefixedIndexName()
            );

            $this->createIndex(
                $this->getPrefixedTableName(),
                $this->getPrefixedIndexName(),
                self::LEAD_FIRST_COLUMNS
            );
        }
    }
}
