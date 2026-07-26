<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260726100000 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'campaign_lead_event_log';
    protected const INDEX_NAME = 'campaign_log_campaign_lead_rotation';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME))->hasIndex("{$this->prefix}".self::INDEX_NAME),
            'Index campaign_log_campaign_lead_rotation already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE INDEX {$this->prefix}".self::INDEX_NAME." ON {$this->getPrefixedTableName(self::TABLE_NAME)} (campaign_id, lead_id, rotation);");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX {$this->prefix}".self::INDEX_NAME." ON {$this->getPrefixedTableName(self::TABLE_NAME)};");
    }
}
