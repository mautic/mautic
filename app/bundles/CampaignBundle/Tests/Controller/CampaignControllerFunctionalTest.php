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

    //protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaignModel      = self::$container->get('mautic.model.factory')->getModel('campaign');
        $this->campaignLeadsLabel = self::$container->get('translator')->trans('mautic.campaign.campaign.leads');
    }

    public function testCampaignContactCountThroughStats(): void
    {
        $campaign   = $this->saveSomeCampaignLeadEventLogs();
        $campaignId = $campaign->getId();

        // Campaign Summary OFF
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => false]);

        $totalContacts = $this->getStatTotalContacts($campaignId);
        Assert::assertSame(2, $totalContacts);

        // Campaign Summary ON
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => true]);
        $totalContacts = $this->getStatTotalContacts($campaignId);
        Assert::assertSame(2, $totalContacts);
    }

    public function testCampaignContactCountOnCanvas(): void
    {
        $campaign   = $this->saveSomeCampaignLeadEventLogs();
        $campaignId = $campaign->getId();

        // Campaign Summary OFF, Campaign Range OFF
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => false, self::CAMPAIGN_RANGE_PARAM => false]);
        $totalContacts = $this->getCanvasTotalContacts($campaignId);
        Assert::assertSame(2, $totalContacts);

        // Campaign Summary ON, Campaign Range OFF
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => true, self::CAMPAIGN_RANGE_PARAM => false]);
        $totalContacts = $this->getCanvasTotalContacts($campaignId);
        Assert::assertSame(2, $totalContacts);

        // Campaign Summary OFF, Campaign Range ON
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => false, self::CAMPAIGN_RANGE_PARAM => true]);
        $totalContacts = $this->getCanvasTotalContacts($campaignId);
        Assert::assertSame(2, $totalContacts);

        // Campaign Summary ON, Campaign Range ON
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => true, self::CAMPAIGN_RANGE_PARAM => true]);
        $totalContacts = $this->getCanvasTotalContacts($campaignId);
        Assert::assertSame(2, $totalContacts);
    }

    public function testCampaignCountsBeforeSummarizeCommand(): void
    {
        $campaign   = $this->saveSomeCampaignLeadEventLogs();
        $campaignId = $campaign->getId();

        // Campaign Summary OFF, Campaign Range OFF
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => false, self::CAMPAIGN_RANGE_PARAM => false]);
        $actionCounts = $this->getActionCounts($campaignId);
        Assert::assertSame('100%', $actionCounts['successPercent']);
        Assert::assertSame('2', $actionCounts['completed']);
        Assert::assertSame('0', $actionCounts['pending']);

        // Campaign Summary ON, Campaign Range OFF
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => true, self::CAMPAIGN_RANGE_PARAM => false]);
        $actionCounts = $this->getActionCounts($campaignId);
        Assert::assertSame('0%', $actionCounts['successPercent']);
        Assert::assertSame('0', $actionCounts['completed']);
        Assert::assertSame('0', $actionCounts['pending']);

        // Campaign Summary OFF, Campaign Range ON
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => false, self::CAMPAIGN_RANGE_PARAM => true]);
        $actionCounts = $this->getActionCounts($campaignId);
        Assert::assertSame('100%', $actionCounts['successPercent']);
        Assert::assertSame('2', $actionCounts['completed']);
        Assert::assertSame('0', $actionCounts['pending']);

        // Campaign Summary ON, Campaign Range ON
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => true, self::CAMPAIGN_RANGE_PARAM => true]);
        $actionCounts = $this->getActionCounts($campaignId);
        Assert::assertSame('0%', $actionCounts['successPercent']);
        Assert::assertSame('0', $actionCounts['completed']);
        Assert::assertSame('0', $actionCounts['pending']);
    }

    public function testCampaignCountsAfterSummarizeCommand(): void
    {
        $campaign   = $this->saveSomeCampaignLeadEventLogs();
        $campaignId = $campaign->getId();

        $this->runCommand(
            SummarizeCommand::NAME,
            [
                '--env'       => 'test',
                '--max-hours' => 9999999,
            ]
        );

        // Campaign Summary OFF, Campaign Range OFF
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => false, self::CAMPAIGN_RANGE_PARAM => false]);
        $actionCounts = $this->getActionCounts($campaignId);
        Assert::assertSame('100%', $actionCounts['successPercent']);
        Assert::assertSame('2', $actionCounts['completed']);
        Assert::assertSame('0', $actionCounts['pending']);

        // Campaign Summary ON, Campaign Range OFF
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => true, self::CAMPAIGN_RANGE_PARAM => false]);
        $actionCounts = $this->getActionCounts($campaignId);
        Assert::assertSame('100%', $actionCounts['successPercent']);
        Assert::assertSame('2', $actionCounts['completed']);
        Assert::assertSame('0', $actionCounts['pending']);

        // Campaign Summary OFF, Campaign Range ON
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => false, self::CAMPAIGN_RANGE_PARAM => true]);
        $actionCounts = $this->getActionCounts($campaignId);
        Assert::assertSame('100%', $actionCounts['successPercent']);
        Assert::assertSame('2', $actionCounts['completed']);
        Assert::assertSame('0', $actionCounts['pending']);

        // Campaign Summary ON, Campaign Range ON
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => true, self::CAMPAIGN_RANGE_PARAM => true]);
        $actionCounts = $this->getActionCounts($campaignId);
        Assert::assertSame('100%', $actionCounts['successPercent']);
        Assert::assertSame('2', $actionCounts['completed']);
        Assert::assertSame('0', $actionCounts['pending']);
    }

    public function testCampaignPendingCounts(): void
    {
        // emulate pending count
        $campaign   = $this->saveSomeCampaignLeadEventLogs(true);
        $campaignId = $campaign->getId();

        $this->runCommand(
            SummarizeCommand::NAME,
            [
                '--env'       => 'test',
                '--max-hours' => 9999999,
            ]
        );

        // Campaign Summary OFF, Campaign Range OFF
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => false, self::CAMPAIGN_RANGE_PARAM => false]);
        $actionCounts = $this->getActionCounts($campaignId);

        Assert::assertSame('100%', $actionCounts['successPercent']);
        Assert::assertSame('2', $actionCounts['completed']);
        Assert::assertSame('1', $actionCounts['pending']);

        // Campaign Summary ON, Campaign Range OFF
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => true, self::CAMPAIGN_RANGE_PARAM => false]);
        $actionCounts = $this->getActionCounts($campaignId);
        Assert::assertSame('100%', $actionCounts['successPercent']);
        Assert::assertSame('2', $actionCounts['completed']);
        Assert::assertSame('1', $actionCounts['pending']);

        // Campaign Summary OFF, Campaign Range ON
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => false, self::CAMPAIGN_RANGE_PARAM => true]);
        $actionCounts = $this->getActionCounts($campaignId);
        Assert::assertSame('100%', $actionCounts['successPercent']);
        Assert::assertSame('2', $actionCounts['completed']);
        Assert::assertSame('1', $actionCounts['pending']);

        // Campaign Summary ON, Campaign Range ON
        $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => true, self::CAMPAIGN_RANGE_PARAM => true]);
        $actionCounts = $this->getActionCounts($campaignId);
        Assert::assertSame('100%', $actionCounts['successPercent']);
        Assert::assertSame('2', $actionCounts['completed']);
        Assert::assertSame('1', $actionCounts['pending']);
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

    private function setSummaryCoreParameter(array $parameters): void
    {
        $this->setUpSymfony($parameters);
    }

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
}
