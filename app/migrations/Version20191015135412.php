<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191015135412 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $emailSatsTable            = $schema->getTable("{$this->prefix}email_stats");
        $pageHitsTable             = $schema->getTable("{$this->prefix}page_hits");
        $campaignLeadEventLogTable = $schema->getTable("{$this->prefix}campaign_lead_event_log");

        if (!$emailSatsTable->hasIndex("{$this->prefix}is_failed_date_sent")) {
            $this->addSql("alter table {$this->prefix}email_stats add index {$this->prefix}is_failed_date_sent (is_failed, date_sent)");
        }
        if (!$pageHitsTable->hasIndex("{$this->prefix}lead_id_date_hit")) {
            $this->addSql("alter table {$this->prefix}page_hits add index {$this->prefix}lead_id_date_hit (lead_id, date_hit)");
        }
        if (!$campaignLeadEventLogTable->hasIndex("{$this->prefix}event_id_lead_id_is_scheduled")) {
            $this->addSql("alter table {$this->prefix}campaign_lead_event_log add index {$this->prefix}event_id_lead_id_is_scheduled (event_id, lead_id, is_scheduled)");
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $emailSatsTable            = $schema->getTable("{$this->prefix}email_stats");
        $pageHitsTable             = $schema->getTable("{$this->prefix}page_hits");
        $campaignLeadEventLogTable = $schema->getTable("{$this->prefix}campaign_lead_event_log");

        if ($emailSatsTable->hasIndex("{$this->prefix}is_failed_date_sent")) {
            $this->addSql("alter table {$this->prefix}email_stats drop index {$this->prefix}is_failed_date_sent");
        }
        if ($pageHitsTable->hasIndex("{$this->prefix}lead_id_date_hit")) {
            $this->addSql("alter table {$this->prefix}page_hits drop index {$this->prefix}lead_id_date_hit");
        }
        if ($campaignLeadEventLogTable->hasIndex("{$this->prefix}event_id_lead_id_is_scheduled")) {
            $this->addSql("alter table {$this->prefix}campaign_lead_event_log drop index {$this->prefix}event_id_lead_id_is_scheduled");
        }
    }
}
