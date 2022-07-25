<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20201207140911 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'campaign_lead_event_log');
        if ($table->hasIndex('campaign_trigger_date_order')) {
            throw new SkipMigration('Schema includes this migration');
        }

        parent::preUp($schema);
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE INDEX campaign_trigger_date_order ON {$this->prefix}campaign_lead_event_log (trigger_date)");
    }
}
