<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Class Version20160506000000.
 */
class Version20160506000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $tagTable = $schema->getTable($this->prefix.'asset_downloads');
        if ($tagTable->hasIndex(MAUTIC_TABLE_PREFIX.'asset_date_download')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('CREATE INDEX '.$this->prefix.'asset_date_download ON '.$this->prefix.'asset_downloads (date_download)');
        $this->addSql('CREATE INDEX '.$this->prefix.'campaign_leads_date_added ON '.$this->prefix.'campaign_leads (date_added)');
        $this->addSql('CREATE INDEX '.$this->prefix.'campaign_date_triggered ON '.$this->prefix.'campaign_lead_event_log (date_triggered)');
        $this->addSql('CREATE INDEX '.$this->prefix.'email_date_sent ON '.$this->prefix.'email_stats (date_sent)');
        $this->addSql('CREATE INDEX '.$this->prefix.'email_date_read ON '.$this->prefix.'email_stats (date_read)');
        $this->addSql('CREATE INDEX '.$this->prefix.'form_date_submitted ON '.$this->prefix.'form_submissions (date_submitted)');
        $this->addSql('CREATE INDEX '.$this->prefix.'lead_date_added ON '.$this->prefix.'leads (date_added)');
        $this->addSql('CREATE INDEX '.$this->prefix.'page_date_hit ON '.$this->prefix.'page_hits (date_hit)');
        $this->addSql('CREATE INDEX '.$this->prefix.'point_date_added ON '.$this->prefix.'lead_points_change_log (date_added)');
    }
}
