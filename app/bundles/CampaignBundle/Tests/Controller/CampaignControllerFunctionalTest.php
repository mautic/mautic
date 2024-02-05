<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CampaignBundle\Command\SummarizeCommand;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Tests\Campaign\AbstractCampaignTest;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $this->campaignModel      = static::getContainer()->get('mautic.model.factory')->getModel('campaign');
        $this->campaignLeadsLabel = static::getContainer()->get('translator')->trans('mautic.campaign.campaign.leads');
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
        $from = date('Y-m-d', strtotime('-2 months'));
        $to   = date('Y-m-d', strtotime('-1 month'));

        $stats = $this->campaignModel->getCampaignMetricsLineChartData(
            null,
            new \DateTime($from),
            new \DateTime($to),
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
        $from = date('F d, Y', strtotime('-2 months'));
        $to   = date('F d, Y', strtotime('-1 month'));

        $parameters = [
            'daterange' => [
                'date_from' => $from,
                'date_to'   => $to,
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
            $this->testSymfonyCommand(
                SummarizeCommand::NAME,
                [
                    '--env'       => 'test',
                    '--max-hours' => 768,
                ]
            );
        }

        $actionCounts = $this->getActionCounts($campaignId);
        Assert::assertSame($expectedSuccessPercent.'%', $actionCounts['successPercent']);
        Assert::assertSame($expectedCompleted, (int) $actionCounts['completed']);
        Assert::assertSame($expectedPending, (int) $actionCounts['pending']);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createCampaignWithEmail(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test campaign');
        $this->em->persist($campaign);
        $this->em->flush();

        // Create email
        $email = new Email();
        $email->setName('Test email');
        $this->em->persist($email);
        $this->em->flush();

        // Create email events
        $event = new Event();
        $event->setName('Send email');
        $event->setType('email.send');
        $event->setEventType('action');
        $event->setChannel('email');
        $event->setChannelId($email->getId());
        $event->setCampaign($campaign);
        $this->em->persist($event);
        $this->em->flush();

        // Add events to campaign
        $campaign->addEvent(0, $event);
        $this->em->flush();

        return $campaign;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createCampaignNoEmail(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test campaign');
        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function addLeadsFromCountry(Campaign $campaign, int $leadCount, string $country): void
    {
        for ($i = 0; $i < $leadCount; ++$i) {
            $lead = new Lead();
            $lead->setCountry($country);
            $this->em->persist($lead);
            $this->em->flush();

            $campaignLead = new CampaignLead();
            $campaignLead->setLead($lead);
            $campaignLead->setCampaign($campaign);
            $campaignLead->setDateAdded(new \DateTime());
            $this->em->persist($campaignLead);
            $campaign->addLead($i, $campaignLead);
        }

        $this->em->flush();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testGetData(): void
    {
        $campaign = $this->createCampaignWithEmail();
        $this->addLeadsFromCountry($campaign, 4, 'Finland');

        var_dump($campaign->isEmailCampaign());

        $this->client->request(Request::METHOD_GET, 's/campaign/countries-stats/preview/'.$campaign->getId());
        $clientResponse = $this->client->getResponse();

        $contentDom      = new \DOMDocument();
        $responseContent = $clientResponse->getContent();
        $contentDom->loadHTML($responseContent);
        $crawler             = new Crawler($contentDom);
        $crawlerTable        = $crawler->filter('table')->first();
        $crawlerTableHeaders = $crawlerTable->filter('thead tr td');
        $crawlerTableValues  = $crawlerTable->filter('tbody tr td');

        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertSame('Country', $crawlerTableHeaders->eq(0)->text());
        $this->assertSame('Contacts', $crawlerTableHeaders->eq(1)->text());
        $this->assertSame('Finland', $crawlerTableValues->eq(0)->text());
        $this->assertSame('4', $crawlerTableValues->eq(1)->text());
    }
}
