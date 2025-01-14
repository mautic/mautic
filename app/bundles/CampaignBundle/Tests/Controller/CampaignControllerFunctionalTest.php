<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CampaignBundle\Command\SummarizeCommand;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Tests\Campaign\AbstractCampaignTest;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class CampaignControllerFunctionalTest extends AbstractCampaignTest
{
    private const CAMPAIGN_SUMMARY_PARAM = 'campaign_use_summary';
    private const CAMPAIGN_RANGE_PARAM   = 'campaign_by_range';

    /**
     * @var CampaignModel
     */
    private $campaignModel;

    /**
     * @var string
     */
    private $campaignLeadsLabel;

    protected function setUp(): void
    {
        $functionForUseSummary = ['testCampaignContactCountThroughStatsWithSummary',
            'testCampaignContactCountOnCanvasWithSummaryWithoutRange', 'testCampaignContactCountOnCanvasWithSummaryAndRange',
            'testCampaignCountsBeforeSummarizeCommandWithSummaryWithoutRange', 'testCampaignCountsBeforeSummarizeCommandWithSummaryAndRange',
            'testCampaignCountsAfterSummarizeCommandWithSummaryWithoutRange', 'testCampaignCountsAfterSummarizeCommandWithSummaryAndRange',
            'testCampaignPendingCountsWithSummaryWithoutRange', 'testCampaignPendingCountsWithSummaryAndRange', ];
        $functionForUseRange = ['testCampaignContactCountOnCanvasWithoutSummaryWithRange', 'testCampaignContactCountOnCanvasWithSummaryAndRange',
            'testCampaignCountsBeforeSummarizeCommandWithoutSummaryWithRange', 'testCampaignCountsBeforeSummarizeCommandWithSummaryAndRange',
            'testCampaignCountsAfterSummarizeCommandWithoutSummaryWithRange', 'testCampaignCountsAfterSummarizeCommandWithSummaryAndRange',
            'testCampaignPendingCountsWithoutSummaryAndRange', 'testCampaignPendingCountsWithoutSummaryWithRange', ];
        $this->configParams[self::CAMPAIGN_SUMMARY_PARAM] = in_array($this->getName(), $functionForUseSummary);
        $this->configParams[self::CAMPAIGN_RANGE_PARAM]   = in_array($this->getName(), $functionForUseRange);
        parent::setUp();
        $this->campaignModel      = self::$container->get('mautic.model.factory')->getModel('campaign');
        $this->campaignLeadsLabel = self::$container->get('translator')->trans('mautic.campaign.campaign.leads');
    }

    public function testCampaignContactCountThroughStatsWithSummary(): void
    {
        $this->campaignContactCountThroughStats();
    }

    public function testCampaignContactCountThroughStatsWithoutSummary(): void
    {
        $this->campaignContactCountThroughStats();
    }

    public function testCampaignContactCountOnCanvasWithoutSummaryAndRange(): void
    {
        $this->campaignContactCountOnCanvas();
    }

    public function testCampaignContactCountOnCanvasWithSummaryWithoutRange(): void
    {
        $this->campaignContactCountOnCanvas();
    }

    public function testCampaignContactCountOnCanvasWithoutSummaryWithRange(): void
    {
        $this->campaignContactCountOnCanvas();
    }

    public function testCampaignContactCountOnCanvasWithSummaryAndRange(): void
    {
        $this->campaignContactCountOnCanvas();
    }

    public function testCampaignCountsBeforeSummarizeCommandWithoutSummaryAndRange(): void
    {
        $this->getCountAndDetails(false, false, 100, 2, 0);
    }

    public function testCampaignCountsBeforeSummarizeCommandWithSummaryWithoutRange(): void
    {
        $this->getCountAndDetails(false, false, 0, 0, 0);
    }

    public function testCampaignCountsBeforeSummarizeCommandWithoutSummaryWithRange(): void
    {
        $this->getCountAndDetails(false, false, 100, 2, 0);
    }

    public function testCampaignCountsBeforeSummarizeCommandWithSummaryAndRange(): void
    {
        $this->getCountAndDetails(false, false, 0, 0, 0);
    }

    public function testCampaignCountsAfterSummarizeCommandWithoutSummaryAndRange(): void
    {
        $this->getCountAndDetails(false, true, 100, 2, 0);
    }

    public function testCampaignCountsAfterSummarizeCommandWithSummaryWithoutRange(): void
    {
        $this->getCountAndDetails(false, true, 100, 2, 0);
    }

    public function testCampaignCountsAfterSummarizeCommandWithoutSummaryWithRange(): void
    {
        $this->getCountAndDetails(false, true, 100, 2, 0);
    }

    public function testCampaignCountsAfterSummarizeCommandWithSummaryAndRange(): void
    {
        $this->getCountAndDetails(false, true, 100, 2, 0);
    }

    public function testCampaignPendingCountsWithoutSummaryAndRange(): void
    {
        $this->getCountAndDetails(true, true, 100, 2, 1);
    }

    public function testCampaignPendingCountsWithSummaryWithoutRange(): void
    {
        $this->getCountAndDetails(true, true, 100, 2, 1);
    }

    public function testCampaignPendingCountsWithoutSummaryWithRange(): void
    {
        $this->getCountAndDetails(true, true, 100, 2, 1);
    }

    public function testCampaignPendingCountsWithSummaryAndRange(): void
    {
        $this->getCountAndDetails(true, true, 100, 2, 1);
    }

    private function getStatTotalContacts(int $campaignId): int
    {
        $stats = $this->campaignModel->getCampaignMetricsLineChartData(
            null,
            new \DateTime('2020-10-21'),
            new \DateTime('2020-11-22'),
            null,
            ['campaign_id' => $campaignId]
        );
        $datasets      = $stats['datasets'] ?? [];

        return $this->processTotalContactStats($datasets);
    }

    private function getCanvasTotalContacts(int $campaignId): int
    {
        $crawler       = $this->getCrawlers($campaignId);
        $canvasJson    = trim($crawler->filter('canvas')->html());
        $canvasData    = json_decode($canvasJson, true);
        $datasets      = $canvasData['datasets'] ?? [];

        return $this->processTotalContactStats($datasets);
    }

    /**
     * @param array<string, array<int|string>> $datasets
     */
    private function processTotalContactStats(array $datasets): int
    {
        $totalContacts = 0;

        foreach ($datasets as $dataset) {
            if ($dataset['label'] === $this->campaignLeadsLabel) {
                $data          = $dataset['data'] ?? [];
                $totalContacts = array_sum($data);
                break;
            }
        }

        return $totalContacts;
    }

    private function getCrawlers(int $campaignId): Crawler
    {
        $parameters = [
            'daterange' => [
                'date_from' => 'Nov 1, 2020',
                'date_to'   => 'Nov 30, 2020',
            ],
        ];

        return $this->client->request(Request::METHOD_POST, '/s/campaigns/view/'.$campaignId, $parameters);
    }

    /**
     * @return array<string, string>
     */
    private function getActionCounts(int $campaignId): array
    {
        $crawler        = $this->getCrawlers($campaignId);
        $successPercent = trim($crawler->filter('#actions-container')->filter('span')->eq(0)->html());
        $completed      = trim($crawler->filter('#actions-container')->filter('span')->eq(1)->html());
        $pending        = trim($crawler->filter('#actions-container')->filter('span')->eq(2)->html());

        return [
            'successPercent' => $successPercent,
            'completed'      => $completed,
            'pending'        => $pending,
        ];
    }

    private function campaignContactCountThroughStats(): void
    {
        $campaign   = $this->saveSomeCampaignLeadEventLogs();
        $campaignId = $campaign->getId();

        $totalContacts = $this->getStatTotalContacts($campaignId);
        Assert::assertSame(2, $totalContacts);
    }

    private function campaignContactCountOnCanvas(): void
    {
        $campaign      = $this->saveSomeCampaignLeadEventLogs();
        $campaignId    = $campaign->getId();
        $totalContacts = $this->getCanvasTotalContacts($campaignId);
        Assert::assertSame(2, $totalContacts);
    }

    private function getCountAndDetails(bool $emulatePendingCount, bool $runCommand, int $expectedSuccessPercent, int $expectedCompleted, int $expectedPending): void
    {
        $campaign   = $this->saveSomeCampaignLeadEventLogs($emulatePendingCount);
        $campaignId = $campaign->getId();

        if ($runCommand) {
            $this->runCommand(
                SummarizeCommand::NAME,
                [
                    '--env'       => 'test',
                    '--max-hours' => 9999999,
                ]
            );
        }

        $actionCounts = $this->getActionCounts($campaignId);
        Assert::assertSame($expectedSuccessPercent.'%', $actionCounts['successPercent']);
        Assert::assertSame($expectedCompleted, (int) $actionCounts['completed']);
        Assert::assertSame($expectedPending, (int) $actionCounts['pending']);
    }
}
