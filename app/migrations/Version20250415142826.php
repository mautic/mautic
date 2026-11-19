<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250415142826 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'campaign_lead_event_log';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME))->hasIndex("{$this->prefix}idx_scheduled_events"),
            'Index idx_scheduled_events already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE INDEX {$this->prefix}idx_scheduled_events ON {$this->getPrefixedTableName(self::TABLE_NAME)} (is_scheduled, event_id, trigger_date);");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX {$this->prefix}idx_scheduled_events ON {$this->getPrefixedTableName(self::TABLE_NAME)};");
    }
}
