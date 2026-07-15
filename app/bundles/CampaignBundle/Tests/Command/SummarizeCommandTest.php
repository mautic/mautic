<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Command;

use Mautic\CampaignBundle\Command\SummarizeCommand;
use Mautic\CampaignBundle\Entity\Summary;
use Mautic\CampaignBundle\Entity\SummaryRepository;
use Mautic\CampaignBundle\Tests\Campaign\AbstractCampaignTestCase;

final class SummarizeCommandTest extends AbstractCampaignTestCase
{
    /**
     * @throws \Exception
     */
    public function testBackwardSummarizationWhenThereAreNoCampaignEventLogs(): void
    {
        $commandResult = $this->testSymfonyCommand(
            SummarizeCommand::NAME,
            [
                '--env'       => 'test',
                '--max-hours' => 768,
            ]
        );

        /** @var SummaryRepository $summaryRepo */
        $summaryRepo = $this->em->getRepository(Summary::class);
        $this->assertCount(0, $summaryRepo->findAll());
        $this->assertStringContainsString('There are no records in the campaign lead event log table. Nothing to summarize.', $commandResult->getDisplay());
    }

    /**
     * @throws \Exception
     */
    public function testBackwardSummarizationWhenThereAreLogs(): void
    {
        $relativeDate = date('Y-m-d', strtotime('-1 month'));

        $campaign = $this->saveSomeCampaignLeadEventLogs();

        $this->testSymfonyCommand(
            SummarizeCommand::NAME,
            [
                '--env'       => 'test',
                '--max-hours' => 768,
            ]
        );

        /** @var SummaryRepository $summaryRepo */
        $summaryRepo = $this->em->getRepository(Summary::class);

        /** @var Summary[] $summaries */
        $summaries = $summaryRepo->findAll();

        $this->assertCount(3, $summaries);

        $this->assertSame($relativeDate.'T17:00:00+00:00', $summaries[0]->getDateTriggered()->format(DATE_ATOM));
        $this->assertSame(1, $summaries[0]->getTriggeredCount());
        $this->assertSame($campaign->getId(), $summaries[0]->getCampaign()->getId());
        $this->assertSame('Event B', $summaries[0]->getEvent()->getName());

        $this->assertSame($relativeDate.'T16:00:00+00:00', $summaries[1]->getDateTriggered()->format(DATE_ATOM));
        $this->assertSame(2, $summaries[1]->getTriggeredCount());
        $this->assertSame($campaign->getId(), $summaries[1]->getCampaign()->getId());
        $this->assertSame('Event A', $summaries[1]->getEvent()->getName());

        $this->assertSame($relativeDate.'T16:00:00+00:00', $summaries[2]->getDateTriggered()->format(DATE_ATOM));
        $this->assertSame(1, $summaries[2]->getTriggeredCount());
        $this->assertSame($campaign->getId(), $summaries[2]->getCampaign()->getId());
        $this->assertSame('Event B', $summaries[2]->getEvent()->getName());
    }
}
