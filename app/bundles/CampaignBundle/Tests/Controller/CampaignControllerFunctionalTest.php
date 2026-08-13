<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CampaignBundle\Command\SummarizeCommand;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Tests\Campaign\AbstractCampaignTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Group('non-parallel')]
final class CampaignControllerFunctionalTest extends AbstractCampaignTestCase
{
    private const string CAMPAIGN_SUMMARY_PARAM = 'campaign_use_summary';

    private const string CAMPAIGN_RANGE_PARAM   = 'campaign_by_range';

    private CampaignModel $campaignModel;

    private string $campaignLeadsLabel;

    protected function setUp(): void
    {
        $functionForUseSummary = ['testCampaignContactCountThroughStatsWithSummary',
            'testCampaignContactCountOnCanvasWithSummaryWithoutRange', 'testCampaignContactCountOnCanvasWithSummaryAndRange',
            'testCampaignCountsBeforeSummarizeCommandWithSummaryWithoutRange', 'testCampaignCountsBeforeSummarizeCommandWithSummaryAndRange',
            'testCampaignCountsAfterSummarizeCommandWithSummaryWithoutRange', 'testCampaignCountsAfterSummarizeCommandWithSummaryAndRange',
            'testCampaignPendingCountsWithSummaryWithoutRange', 'testCampaignPendingCountsWithSummaryAndRange', 'testCampaignRemovedLeadCountsWithSummaryAndRange', 'testCampaignRemovedLeadAndPendingCountsWithSummaryAndRange', ];
        $functionForUseRange = ['testCampaignContactCountOnCanvasWithoutSummaryWithRange', 'testCampaignContactCountOnCanvasWithSummaryAndRange',
            'testCampaignCountsBeforeSummarizeCommandWithoutSummaryWithRange', 'testCampaignCountsBeforeSummarizeCommandWithSummaryAndRange',
            'testCampaignCountsAfterSummarizeCommandWithoutSummaryWithRange', 'testCampaignCountsAfterSummarizeCommandWithSummaryAndRange',
            'testCampaignPendingCountsWithoutSummaryAndRange', 'testCampaignPendingCountsWithoutSummaryWithRange', 'testCampaignRemovedLeadCountsWithoutSummaryWithRange', 'testCampaignRemovedLeadCountsWithSummaryAndRange', 'testCampaignRemovedLeadAndPendingCountsWithSummaryAndRange', 'testCampaignRemovedLeadAndPendingCountsWithoutSummaryWithRange', ];
        $this->configParams[self::CAMPAIGN_SUMMARY_PARAM] = in_array($this->name(), $functionForUseSummary);
        $this->configParams[self::CAMPAIGN_RANGE_PARAM]   = in_array($this->name(), $functionForUseRange);
        parent::setUp();

        $model = self::getContainer()->get(CampaignModel::class);

        $this->campaignModel                                           = $model;
        $this->campaignLeadsLabel                                      = self::getContainer()->get(TranslatorInterface::class)->trans('mautic.campaign.campaign.leads');
        $this->configParams['delete_campaign_event_log_in_background'] = false;
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
        $this->getCountAndDetails(false, false, false, ['100%', '100%'], ['2', '2'], ['0', '0']);
    }

    public function testCampaignCountsBeforeSummarizeCommandWithSummaryWithoutRange(): void
    {
        $this->getCountAndDetails(false, false, false, ['0%', '0%'], ['0', '0'], ['0', '0']);
    }

    public function testCampaignCountsBeforeSummarizeCommandWithoutSummaryWithRange(): void
    {
        $this->getCountAndDetails(false, false, false, ['100%', '100%'], ['2', '2'], ['0', '0']);
    }

    public function testCampaignCountsBeforeSummarizeCommandWithSummaryAndRange(): void
    {
        $this->getCountAndDetails(false, false, false, ['0%', '0%'], ['0', '0'], ['0', '0']);
    }

    public function testCampaignCountsAfterSummarizeCommandWithoutSummaryAndRange(): void
    {
        $this->getCountAndDetails(false, false, true, ['100%', '100%'], ['2', '2'], ['0', '0']);
    }

    public function testCampaignCountsAfterSummarizeCommandWithSummaryWithoutRange(): void
    {
        $this->getCountAndDetails(false, false, true, ['100%', '100%'], ['2', '2'], ['0', '0']);
    }

    public function testCampaignCountsAfterSummarizeCommandWithoutSummaryWithRange(): void
    {
        $this->getCountAndDetails(false, false, true, ['100%', '100%'], ['2', '2'], ['0', '0']);
    }

    public function testCampaignCountsAfterSummarizeCommandWithSummaryAndRange(): void
    {
        $this->getCountAndDetails(false, false, true, ['100%', '100%'], ['2', '2'], ['0', '0']);
    }

    public function testCampaignPendingCountsWithoutSummaryAndRange(): void
    {
        $this->getCountAndDetails(true, false, true, ['100%', '100%'], ['3', '2'], ['0', '1']);
    }

    public function testCampaignPendingCountsWithSummaryWithoutRange(): void
    {
        $this->getCountAndDetails(true, false, true, ['100%', '100%'], ['3', '2'], ['0', '1']);
    }

    public function testCampaignPendingCountsWithoutSummaryWithRange(): void
    {
        $this->getCountAndDetails(true, false, true, ['100%', '100%'], ['3', '2'], ['0', '1']);
    }

    public function testCampaignPendingCountsWithSummaryAndRange(): void
    {
        $this->getCountAndDetails(true, false, true, ['100%', '100%'], ['3', '2'], ['0', '1']);
    }

    public function testCampaignRemovedLeadCountsWithSummaryAndRange(): void
    {
        $this->getCountAndDetails(false, true, true, ['100%', '100%'], ['3', '2'], ['0', '0']);
    }

    public function testCampaignRemovedLeadCountsWithoutSummaryWithRange(): void
    {
        $this->getCountAndDetails(false, true, true, ['100%', '100%'], ['3', '2'], ['0', '0']);
    }

    public function testCampaignRemovedLeadAndPendingCountsWithSummaryAndRange(): void
    {
        $this->getCountAndDetails(true, true, true, ['100%', '100%'], ['4', '2'], ['0', '1']);
    }

    public function testCampaignRemovedLeadAndPendingCountsWithoutSummaryWithRange(): void
    {
        $this->getCountAndDetails(true, true, true, ['100%', '100%'], ['4', '2'], ['0', '1']);
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
        $from = date('Y-m-d', strtotime('-2 months'));
        $to   = date('Y-m-d', strtotime('-1 month'));
        $this->client->request(Request::METHOD_GET, sprintf('s/campaigns/graph/%d/%s/%s', $campaignId, $from, $to));
        $response      = $this->client->getResponse();
        $body          = json_decode($response->getContent(), true);
        $crawler       = new Crawler($body['newContent']);
        $canvasJson    = trim($crawler->filter('canvas')->html());
        $canvasData    = json_decode($canvasJson, true);
        $datasets      = $canvasData['datasets'] ?? [];
        $this->client->restart();

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
        $from = date('Y-m-d', strtotime('-2 months'));
        $to   = date('Y-m-d', strtotime('-1 month'));
        $url  = sprintf('s/campaigns/event/stats/%d/%s/%s', $campaignId, $from, $to);
        $this->client->request(Request::METHOD_GET, $url);
        $response = $this->client->getResponse();
        $body     = json_decode($response->getContent(), true);
        $this->client->restart();

        return new Crawler($body['actions']);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function getActionCounts(int $campaignId): array
    {
        $crawler        = $this->getCrawlers($campaignId);
        $successPercent = [
            trim($crawler->filter('.campaign-event-list li:nth-child(1) .label-success')->text()),
            trim($crawler->filter('.campaign-event-list li:nth-child(2) .label-success')->text()),
        ];

        $completed = [
            trim($crawler->filter('.campaign-event-list li:nth-child(1) .label-warning')->text()),
            trim($crawler->filter('.campaign-event-list li:nth-child(2) .label-warning')->text()),
        ];

        $pending = [
            trim($crawler->filter('.campaign-event-list li:nth-child(1) .label-gray')->text()),
            trim($crawler->filter('.campaign-event-list li:nth-child(2) .label-gray')->text()),
        ];

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
        $this->assertSame(2, $totalContacts);
    }

    private function campaignContactCountOnCanvas(): void
    {
        $campaign      = $this->saveSomeCampaignLeadEventLogs();
        $campaignId    = $campaign->getId();
        $totalContacts = $this->getCanvasTotalContacts($campaignId);
        $this->assertSame(2, $totalContacts);
    }

    /**
     * @param array<int, string> $expectedSuccessPercent
     * @param array<int, string> $expectedCompleted
     * @param array<int, string> $expectedPending
     */
    private function getCountAndDetails(bool $withPendingAction, bool $withActionOfRemovedLead, bool $runCommand, array $expectedSuccessPercent, array $expectedCompleted, array $expectedPending): void
    {
        $campaign   = $this->saveSomeCampaignLeadEventLogs($withPendingAction, $withActionOfRemovedLead);
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
        $this->assertSame($expectedSuccessPercent, $actionCounts['successPercent']);
        $this->assertSame($expectedCompleted, $actionCounts['completed']);
        $this->assertSame($expectedPending, $actionCounts['pending']);
    }

    public function testDeleteCampaign(): void
    {
        $lead              = $this->createLead();
        $campaign          = $this->createCampaign();
        $event             = $this->createEvent('Event 1', $campaign);
        $this->createEventLog($lead, $event, $campaign);

        $this->client->request(Request::METHOD_POST, '/s/campaigns/delete/'.$campaign->getId());

        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful($response->getContent());

        $eventLogs = $this->em->getRepository(LeadEventLog::class)->findAll();
        $this->assertCount(0, $eventLogs);
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('Test');
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('My campaign');
        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    private function createEvent(string $name, Campaign $campaign): Event
    {
        $event = new Event();
        $event->setName($name);
        $event->setCampaign($campaign);
        $event->setType('email.send');
        $event->setEventType('action');
        $this->em->persist($event);
        $this->em->flush();

        return $event;
    }

    private function createEventLog(Lead $lead, Event $event, Campaign $campaign): LeadEventLog
    {
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setLead($lead);
        $leadEventLog->setEvent($event);
        $leadEventLog->setCampaign($campaign);
        $this->em->persist($leadEventLog);
        $this->em->flush();

        return $leadEventLog;
    }

    public function testCampaignView(): void
    {
        $campaign = $this->saveSomeCampaignLeadEventLogs();
        $crawler  = $this->client->request(Request::METHOD_GET, sprintf('/s/campaigns/view/%d', $campaign->getId()));
        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();
        $this->assertStringContainsString('Campaign ABC', (string) $response->getContent());
        $this->assertSame('', trim($crawler->filter('#decisions-container')->text()));
        $this->assertSame('', trim($crawler->filter('#actions-container')->text()));
        $this->assertSame('', trim($crawler->filter('#conditions-container')->text()));
        $this->assertSame('', trim($crawler->filter('#campaign-graph-div')->text()));
    }

    public function testCampaignViewEvents(): void
    {
        $from     = date('Y-m-d', strtotime('-2 months'));
        $to       = date('Y-m-d', strtotime('-1 month'));
        $campaign = $this->saveSomeCampaignLeadEventLogs();
        $this->client->request(Request::METHOD_GET, sprintf('s/campaigns/event/stats/%d/%s/%s', $campaign->getId(), $from, $to));
        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();
        $body     = json_decode($response->getContent(), true);
        $this->assertCount(2, $body);
        self::arrayHasKey('actions');
        $this->assertStringContainsString('100% 2 0 Event A mautic.campaign.type.a 100% 2 0 Event B mautic.campaign.type.b', (string) preg_replace('/\s+/', ' ', strip_tags($body['actions'])));
    }
}
