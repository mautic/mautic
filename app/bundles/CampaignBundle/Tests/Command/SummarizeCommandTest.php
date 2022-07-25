<?php

namespace Mautic\CampaignBundle\Tests\Command;

use Mautic\CampaignBundle\Command\SummarizeCommand;
use Mautic\CampaignBundle\Entity\Summary;
use Mautic\CampaignBundle\Entity\SummaryRepository;
use Mautic\CampaignBundle\Tests\Campaign\AbstractCampaignTest;
use PHPUnit\Framework\Assert;

final class SummarizeCommandTest extends AbstractCampaignTest
{
    /**
     * @throws \Exception
     */
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
            'There are no records in the campaign lead event log table. Nothing to summarize.',
            $output
        );
    }

    /**
     * @throws \Exception
     */
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
}
