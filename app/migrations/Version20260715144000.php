<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20260715144000 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $eventLogTable = $this->getPrefixedTableName(LeadEventLog::TABLE_NAME);
        $eventTable    = $this->getPrefixedTableName(Event::TABLE_NAME);

        $this->addSql(
            "UPDATE {$eventLogTable} l
                INNER JOIN {$eventTable} e ON e.id = l.event_id
                SET l.non_action_path_taken = 0
                WHERE e.event_type IN ('condition', 'decision')
                    AND l.non_action_path_taken IS NULL
                    AND l.date_triggered IS NOT NULL
        );
    }
}
