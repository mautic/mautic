<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Entity;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

class LeadEventLogRepositoryTest extends MauticMysqlTestCase
{
    public function testThatRemoveEventLogsByCampaignIdMethodRemovesLogs(): void
    {
        $campaignId = random_int(200, 2000);
        $eventId    = random_int(300, 3000);
        $connection = $this->em->getConnection();

        $leadEventLogRepository = $this->em->getRepository(LeadEventLog::class);
        \assert($leadEventLogRepository instanceof LeadEventLogRepository);

        $insertStatement = $connection->prepare('INSERT INTO `'.MAUTIC_TABLE_PREFIX.'campaign_lead_event_log` (`campaign_id`, `event_id`, `lead_id`, `rotation`, `is_scheduled`, `system_triggered`) VALUES (?, ?, ?, ?, ?, ?);');

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0;');
        foreach ($this->getLeadCampaignEventData($campaignId, $eventId) as $row) {
            $insertStatement->executeQuery($row);
        }
        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1;');

        Assert::assertCount(3, $leadEventLogRepository->findAll());

        $leadEventLogRepository->removeEventLogs([(string) $eventId]);

        Assert::assertCount(0, $leadEventLogRepository->findAll());
    }

    private function getLeadCampaignEventData(int $campaignId, int $eventId): array
    {
        return [
            [$campaignId, $eventId, 100, 200, 1, 1],
            [$campaignId, $eventId, 101, 201, 1, 1],
            [$campaignId, $eventId, 102, 202, 1, 1],
        ];
    }
}
