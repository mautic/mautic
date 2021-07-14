<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Functional\Entity;

use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

class LeadEventLogRepositoryTest extends MauticMysqlTestCase
{
    public function testThatRemoveEventLogsMethodRemovesLogs()
    {
        $eventId    = random_int(200, 2000);
        $connection = $this->em->getConnection();

        /** @var LeadEventLogRepository $leadEventLogRepository */
        $leadEventLogRepository = $this->em->getRepository('MauticCampaignBundle:LeadEventLog');

        $insertStatement = $connection->prepare('INSERT INTO `campaign_lead_event_log` (`event_id`, `lead_id`, `rotation`, `is_scheduled`, `system_triggered`) VALUES (?, ?, ?, ?, ?);');

        $connection->query('SET FOREIGN_KEY_CHECKS=0;');
        foreach ($this->getLeadCampaignEventData($eventId) as $row) {
            $insertStatement->execute($row);
        }
        $connection->query('SET FOREIGN_KEY_CHECKS=1;');

        Assert::assertCount(3, $leadEventLogRepository->findAll());

        $leadEventLogRepository->removeEventLogs($eventId);

        Assert::assertCount(0, $leadEventLogRepository->findAll());
    }

    private function getLeadCampaignEventData(int $eventId): array
    {
        return [
            [$eventId, 100, 200, 1, 1],
            [$eventId, 101, 201, 1, 1],
            [$eventId, 102, 202, 1, 1],
        ];
    }
}
