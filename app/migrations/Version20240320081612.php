<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20240320081612 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable("{$this->prefix}campaign_events")->hasColumn('trigger_window'),
            'Column trigger_window already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}campaign_events ADD COLUMN `trigger_window` INT NULL DEFAULT NULL AFTER `trigger_restricted_dow`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}campaign_events DROP COLUMN `trigger_window`");
    }
}
