<?php

/*
 * @package     Mautic
 * @copyright   2018 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Add indexes to speed up campaign view rendering.
 */
class Version20180516211256 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $table = $schema->getTable("{$this->prefix}campaign_lead_event_log");
        if ($table->hasIndex("{$this->prefix}campaign_actions")) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("CREATE INDEX {$this->prefix}campaign_actions ON {$this->prefix}campaign_lead_event_log (campaign_id, event_id, date_triggered)");
        $this->addSql("CREATE INDEX {$this->prefix}campaign_stats ON {$this->prefix}campaign_lead_event_log (campaign_id, date_triggered, event_id, non_action_path_taken)");
    }
}
