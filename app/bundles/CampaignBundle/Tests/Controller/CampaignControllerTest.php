<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CampaignBundle\Command\SummarizeCommand;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Tests\Campaign\AbstractCampaignTest;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class CampaignControllerTest extends AbstractCampaignTest
{
    public function setUp(): void
    {
        parent::setUp();

        $coreParam = new class($this->container) extends CoreParametersHelper {
            public function get($name, $default = null)
            {
                if ('campaign_use_summary' === $name) {
                    return true;
                }

                return parent::get($name, $default);
            }
        };
        $this->container->set('mautic.helper.core_parameters', $coreParam);
    }

    public function testContactCountWithSummaryThroughStats(): void
    {
        /** @var CampaignModel $campaignModel */
        $campaignModel = $this->container->get('mautic.model.factory')->getModel('campaign');

        $campaign = $this->saveSomeCampaignLeadEventLogs();

        $stats = $campaignModel->getCampaignMetricsLineChartData(
            null,
            new \DateTime('2020-10-21'),
            new \DateTime('2020-11-22'),
            null,
            ['campaign_id' => $campaign->getId()]
        );
        $datasets      = $stats['datasets'] ?? [];
        $totalContacts = $this->processTotalContactStats($datasets);

        Assert::assertSame(2, $totalContacts);
    }

    public function testContactCountWithSummaryThroughCanvas(): void
    {
        $campaign      = $this->saveSomeCampaignLeadEventLogs();
        $crawler       = $this->getCrawler($campaign->getId());
        $canvasJson    = trim($crawler->filter('canvas')->html());
        $canvasData    = json_decode($canvasJson, true);
        $datasets      = $canvasData['datasets'] ?? [];
        $totalContacts = $this->processTotalContactStats($datasets);

        Assert::assertSame(2, $totalContacts);
    }

    public function testCampaignPendingCountWithSummary(): void
    {
        $campaign = $this->saveSomeCampaignLeadEventLogs();

        $actionCounts = $this->getActionCounts($campaign->getId());

        Assert::assertSame('0%', $actionCounts['successPercent']);
        Assert::assertSame('0', $actionCounts['completed']);
        Assert::assertSame('2', $actionCounts['pending']);
    }

    public function testCampaignCompleteCountWithSummary(): void
    {
        $campaign = $this->saveSomeCampaignLeadEventLogs();

        $this->runCommand(
            SummarizeCommand::NAME,
            [
                '--env'       => 'test',
                '--max-hours' => 9999999,
            ]
        );

        $actionCounts = $this->getActionCounts($campaign->getId());

        Assert::assertSame('100%', $actionCounts['successPercent']);
        Assert::assertSame('2', $actionCounts['completed']);
        Assert::assertSame('0', $actionCounts['pending']);
    }

    private function processTotalContactStats(array $datasets): int
    {
        $datasetLabel  = $this->container->get('translator')->trans('mautic.campaign.campaign.leads');
        $totalContacts = 0;

        foreach ($datasets as $dataset) {
            if ($dataset['label'] === $datasetLabel) {
                $data          = $dataset['data'] ?? [];
                $totalContacts = array_sum($data);
                break;
            }
        }

        return $totalContacts;
    }

    private function getCrawler(int $campaignId): Crawler
    {
        $parameters = [
            'daterange' => [
                'date_from' => 'Nov 1, 2020',
                'date_to'   => 'Nov 30, 2020',
            ],
        ];

        return $this->client->request(Request::METHOD_POST, '/s/campaigns/view/'.$campaignId, $parameters);
    }

    private function getActionCounts(int $campaignId): array
    {
        $crawler        = $this->getCrawler($campaignId);
        $successPercent = trim($crawler->filter('#actions-container')->filter('span')->eq(0)->html());
        $completed      = trim($crawler->filter('#actions-container')->filter('span')->eq(1)->html());
        $pending        = trim($crawler->filter('#actions-container')->filter('span')->eq(2)->html());

        return [
            'successPercent' => $successPercent,
            'completed'      => $completed,
            'pending'        => $pending,
        ];
    }
}
