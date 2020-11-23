<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Command;

use Mautic\CampaignBundle\Command\SummarizeCommand;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\Summary;
use Mautic\CampaignBundle\Entity\SummaryRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use PHPUnit\Framework\Assert;

final class SummarizeCommandTest extends MauticMysqlTestCase
{
    public function testBackwardSummarizationWhenThereAreNoCampaignEventLogs(): void
    {
        $output = $this->runCommand(
            SummarizeCommand::NAME,
            [
                '--env'       => 'test',
                '--max-hours' => 9999,
            ]
        );

        /** @var SummaryRepository $summaryRepo */
        $summaryRepo = $this->em->getRepository(Summary::class);
        Assert::assertCount(0, $summaryRepo->findAll());
        Assert::assertStringContainsString(
            'There are no records in the campaign lead event log table. Nothng to summarize.',
            $output
        );
    }

    public function testBackwardSummarizationWhenThereAreLogs(): void
    {
        $campaign = $this->saveSomeCampaignLeadEventLogs();

        $this->runCommand(
            SummarizeCommand::NAME,
            [
                '--env'       => 'test',
                '--max-hours' => 9999999,
            ]
        );

        /** @var SummaryRepository $summaryRepo */
        $summaryRepo = $this->em->getRepository(Summary::class);

        /** @var Summary[] $summaries */
        $summaries = $summaryRepo->findAll();

        Assert::assertCount(3, $summaries);

        Assert::assertSame('2020-11-21T17:00:00+00:00', $summaries[0]->getDateTriggered()->format(DATE_ATOM));
        Assert::assertSame(1, $summaries[0]->getTriggeredCount());
        Assert::assertSame($campaign->getId(), $summaries[0]->getCampaign()->getId());
        Assert::assertSame('Event B', $summaries[0]->getEvent()->getName());

        Assert::assertSame('2020-11-21T16:00:00+00:00', $summaries[1]->getDateTriggered()->format(DATE_ATOM));
        Assert::assertSame(2, $summaries[1]->getTriggeredCount());
        Assert::assertSame($campaign->getId(), $summaries[1]->getCampaign()->getId());
        Assert::assertSame('Event A', $summaries[1]->getEvent()->getName());

        Assert::assertSame('2020-11-21T16:00:00+00:00', $summaries[2]->getDateTriggered()->format(DATE_ATOM));
        Assert::assertSame(1, $summaries[2]->getTriggeredCount());
        Assert::assertSame($campaign->getId(), $summaries[2]->getCampaign()->getId());
        Assert::assertSame('Event B', $summaries[2]->getEvent()->getName());
    }

    private function saveSomeCampaignLeadEventLogs(): Campaign
    {
        /** @var LeadEventLogRepository $leadEventLogRepo */
        $leadEventLogRepo = $this->em->getRepository(LeadEventLog::class);

        /** @var CampaignRepository $campaignRepo */
        $campaignRepo = $this->em->getRepository(Campaign::class);

        /** @var LeadRepository $contactRepo */
        $contactRepo = $this->em->getRepository(Lead::class);

        $contactA = new Lead();
        $contactB = new Lead();

        $contactRepo->saveEntities([$contactA, $contactB]);

        $campaign = new Campaign();
        $campaign->setName('Campaign ABC');

        $eventA = new Event();
        $eventA->setName('Event A');
        $eventA->setType('type.a');
        $eventA->setEventType('type.a');
        $eventA->setCampaign($campaign);

        $eventB = new Event();
        $eventB->setName('Event B');
        $eventB->setType('type.b');
        $eventB->setEventType('type.b');
        $eventB->setCampaign($campaign);

        $campaign->addEvent(0, $eventA);
        $campaign->addEvent(1, $eventB);

        $campaignRepo->saveEntity($campaign);

        $leadEventLogA = new LeadEventLog();
        $leadEventLogA->setCampaign($campaign);
        $leadEventLogA->setEvent($eventA);
        $leadEventLogA->setLead($contactA);
        $leadEventLogA->setDateTriggered(new \DateTime('2020-11-21 16:34:00', new \DateTimeZone('UTC')));

        $leadEventLogB = new LeadEventLog();
        $leadEventLogB->setCampaign($campaign);
        $leadEventLogB->setEvent($eventA);
        $leadEventLogB->setLead($contactB);
        $leadEventLogB->setDateTriggered(new \DateTime('2020-11-21 16:54:00', new \DateTimeZone('UTC')));

        $leadEventLogC = new LeadEventLog();
        $leadEventLogC->setCampaign($campaign);
        $leadEventLogC->setEvent($eventB);
        $leadEventLogC->setLead($contactA);
        $leadEventLogC->setDateTriggered(new \DateTime('2020-11-21 16:55:00', new \DateTimeZone('UTC')));

        $leadEventLogD = new LeadEventLog();
        $leadEventLogD->setCampaign($campaign);
        $leadEventLogD->setEvent($eventB);
        $leadEventLogD->setLead($contactB);
        $leadEventLogD->setDateTriggered(new \DateTime('2020-11-21 17:04:00', new \DateTimeZone('UTC')));

        $leadEventLogRepo->saveEntities([$leadEventLogA, $leadEventLogB, $leadEventLogC, $leadEventLogD]);

        return $campaign;
    }
}
