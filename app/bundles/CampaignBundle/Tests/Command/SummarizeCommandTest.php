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
use Mautic\CampaignBundle\Entity\Summary;
use Mautic\CampaignBundle\Entity\SummaryRepository;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Tests\Campaign\AbstractCampaignTest;
use PHPUnit\Framework\Assert;

final class SummarizeCommandTest extends AbstractCampaignTest
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

        /** @var CampaignModel $campaignModel */
        $campaignModel = $this->container->get('mautic.model.factory')->getModel('campaign');

        $datasetLabel = $this->container->get('translator')->trans('mautic.campaign.campaign.leads');

        $stats = $campaignModel->getCampaignMetricsLineChartData(
            null,
            new \DateTime('2020-10-21'),
            new \DateTime('2020-11-22'),
            null,
            ['campaign_id' => $campaign->getId()]
        );
        $datasets      = $stats['datasets'] ?? [];
        $totalContacts = 0;

        foreach ($datasets as $dataset) {
            if ($dataset['label'] === $datasetLabel) {
                $data          = $dataset['data'] ?? [];
                $totalContacts = array_sum($data);
                break;
            }
        }

        Assert::assertSame(2, $totalContacts);
    }
}
