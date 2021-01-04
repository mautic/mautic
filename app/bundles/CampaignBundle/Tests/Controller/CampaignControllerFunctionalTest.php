<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CampaignBundle\Command\SummarizeCommand;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Tests\Campaign\AbstractCampaignTest;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use PHPUnit\Framework\Assert;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
        parent::setUp();
        $this->campaignModel      = $this->container->get('mautic.model.factory')->getModel('campaign');
        $this->campaignLeadsLabel = $this->container->get('translator')->trans('mautic.campaign.campaign.leads');
    }

    public function testCampaignContactCountThroughStats(): void
    {
        $campaign   = $this->saveSomeCampaignLeadEventLogs();
        $campaignId = $campaign->getId();

        // Campaign Summary OFF
        $coreParam = $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => false]);
        $this->campaignModel->setCoreParametersHelper($coreParam);
        $totalContacts = $this->getStatTotalContacts($campaignId);
        Assert::assertSame(2, $totalContacts);

        // Campaign Summary ON
        $coreParam = $this->setSummaryCoreParameter([self::CAMPAIGN_SUMMARY_PARAM => true]);
        $this->campaignModel->setCoreParametersHelper($coreParam);
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
        $crawler       = $this->getCrawler($campaignId);
        $canvasJson    = trim($crawler->filter('canvas')->html());
        $canvasData    = json_decode($canvasJson, true);
        $datasets      = $canvasData['datasets'] ?? [];

        return $this->processTotalContactStats($datasets);
    }

    private function setSummaryCoreParameter(array $parameters): CoreParametersHelper
    {
        $coreParam = new class($this->container, $parameters) extends CoreParametersHelper {
            private $parameters;

            public function __construct(ContainerInterface $container, array $parameters)
            {
                $this->parameters = $parameters;
                parent::__construct($container);
            }

            public function get($name, $default = null)
            {
                return $this->parameters[$name] ?? parent::get($name, $default);
            }
        };
        $this->container->set('mautic.helper.core_parameters', $coreParam);

        return $coreParam;
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
