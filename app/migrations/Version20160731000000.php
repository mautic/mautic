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
 * Class Version20160731000000.
 */
class Version20160731000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("update {$this->prefix}campaign_lead_event_log log inner join {$this->prefix}campaign_events events on log.event_id = events.id set log.metadata = 'a:0:{}' where events.type = 'email.send' and log.metadata = 'a:2:{s:6:\"failed\";i:1;s:6:\"reason\";s:50:\"mautic.notification.campaign.failed.not_subscribed\";}'");
    }
}
