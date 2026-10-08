<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Model;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Model\SummaryModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;

final class SummaryModelFunctionalTest extends MauticMysqlTestCase
{
    private SummaryModel $summaryModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->summaryModel = self::getContainer()->get(SummaryModel::class);
    }

    public function testPersistSummariesGeneratesStableHourlyRowsPerEvent(): void
    {
        $campaign = $this->createCampaign('Summary campaign');
        $lead     = $this->createLead('Summary lead');
        $this->createCampaignLead($campaign, $lead);

        $firstEvent  = $this->createEvent($campaign, 'First event');
        $secondEvent = $this->createEvent($campaign, 'Second event');

        $firstLog  = $this->createEventLog($campaign, $firstEvent, $lead, '2026-03-18 19:12:34');
        $secondLog = $this->createEventLog($campaign, $secondEvent, $lead, '2026-03-19 09:45:10');

        $this->em->flush();

        $this->summaryModel->updateSummary([$firstLog, $secondLog]);
        $this->summaryModel->persistSummaries();

        $rows = $this->connection->createQueryBuilder()
            ->select('event_id', 'date_triggered', 'triggered_count', 'log_counts_processed')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_summary')
            ->where('campaign_id = :campaignId')
            ->setParameter('campaignId', $campaign->getId())
            ->orderBy('event_id', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $this->assertCount(2, $rows);

        $rowsByEvent = [];
        foreach ($rows as $row) {
            $rowsByEvent[(int) $row['event_id']] = $row;
        }

        $this->assertSame('2026-03-18 19:00:00', $rowsByEvent[$firstEvent->getId()]['date_triggered']);
        $this->assertSame('1', $rowsByEvent[$firstEvent->getId()]['triggered_count']);
        $this->assertSame('1', $rowsByEvent[$firstEvent->getId()]['log_counts_processed']);

        $this->assertSame('2026-03-19 09:00:00', $rowsByEvent[$secondEvent->getId()]['date_triggered']);
        $this->assertSame('1', $rowsByEvent[$secondEvent->getId()]['triggered_count']);
        $this->assertSame('1', $rowsByEvent[$secondEvent->getId()]['log_counts_processed']);
    }

    public function testPersistSummariesClearsBufferedLogsAfterFlush(): void
    {
        $campaign = $this->createCampaign('Buffered summary campaign');
        $lead     = $this->createLead('Buffered summary lead');
        $this->createCampaignLead($campaign, $lead);

        $event = $this->createEvent($campaign, 'Buffered event');
        $log   = $this->createEventLog($campaign, $event, $lead, '2026-03-18 19:12:34');

        $this->em->flush();

        $this->summaryModel->updateSummary([$log]);
        $this->summaryModel->persistSummaries();

        $this->assertSame(1, $this->countSummaryRowsForCampaign($campaign));

        $this->connection->delete(MAUTIC_TABLE_PREFIX.'campaign_summary', ['campaign_id' => $campaign->getId()]);

        $this->summaryModel->persistSummaries();

        $this->assertSame(0, $this->countSummaryRowsForCampaign($campaign));
    }

    private function countSummaryRowsForCampaign(Campaign $campaign): int
    {
        return (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_summary')
            ->where('campaign_id = :campaignId')
            ->setParameter('campaignId', $campaign->getId())
            ->executeQuery()
            ->fetchOne();
    }

    private function createCampaign(string $name): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName($name);
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);

        return $campaign;
    }

    private function createLead(string $firstname): Lead
    {
        $lead = new Lead();
        $lead->setFirstname($firstname);
        $this->em->persist($lead);

        return $lead;
    }

    private function createCampaignLead(Campaign $campaign, Lead $lead): CampaignLead
    {
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime('2026-03-18 18:00:00'));
        $this->em->persist($campaignLead);

        return $campaignLead;
    }

    private function createEvent(Campaign $campaign, string $name): Event
    {
        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName($name);
        $event->setType(Event::TYPE_ACTION);
        $event->setEventType('email.send');
        $event->setTriggerMode('immediate');
        $event->setTriggerInterval(1);
        $this->em->persist($event);

        return $event;
    }

    private function createEventLog(Campaign $campaign, Event $event, Lead $lead, string $dateTriggered): LeadEventLog
    {
        $eventLog = new LeadEventLog();
        $eventLog->setCampaign($campaign);
        $eventLog->setEvent($event);
        $eventLog->setLead($lead);
        $eventLog->setRotation(1);
        $eventLog->setDateTriggered(new \DateTime($dateTriggered, new \DateTimeZone('UTC')));
        $this->em->persist($eventLog);

        return $eventLog;
    }
}
