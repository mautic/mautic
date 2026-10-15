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
        $schema->getTable($this->getPrefixedTableName('campaign_events'))->addColumn('trigger_window', 'integer', ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName('campaign_events'))->dropColumn('trigger_window');
    }
}
