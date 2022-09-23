<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20220923162706 extends AbstractMauticMigration
{

    /**
     * @throws SkipMigration
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable("{$this->prefix}campaign_lead_event_log")->hasIndex("{$this->prefix}search_1")) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE INDEX {$this->prefix}search_1 ON {$this->prefix}campaign_lead_event_log (lead_id, is_scheduled, date_triggered)"
        );
    }
}
