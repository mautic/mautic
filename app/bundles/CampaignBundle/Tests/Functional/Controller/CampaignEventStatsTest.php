<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;

class CampaignEventStatsTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    protected function setUp(): void
    {
        $this->configParams['campaign_use_summary']     = false;
        $this->configParams['campaign_event_cache_ttl'] = 3;

        parent::setUp();
    }

    public function testCountsProcessedCampaignsMethodCountsProcessedCampaignsCorrectly(): void
    {
        $campaign = $this->createCampaign('Test Campaign');

        $lead= $this->createLead('Test Lead');

        $campaignEvent1 = $this->createEvent('Send Email 1', $campaign, 'email.send', 'action');

        $campaignEvent2 = $this->createEvent('ump to send email 1', $campaign, 'campaign.jump_to_event', 'action');

        $this->addContactToCampaign($lead, $campaign);

        $this->createCampaignLeadEventLog($lead, $campaignEvent1, $campaign, 1, true);

        $this->createCampaignLeadEventLog($lead, $campaignEvent1, $campaign, 2);

        $this->em->flush();

        $eventsStatistics         = $this->getEventsStatistics($campaign);

        $expectedEventsStatistics = [
            0 => [
                'successPercent' => '100%',
                'completed'      => '1',
                'pending'        => '1',
            ],
            1 => [
                'successPercent' => '0%',
                'completed'      => '0',
                'pending'        => '0',
            ],
        ];

        Assert::assertSame($expectedEventsStatistics, $eventsStatistics);

        $this->createCampaignLeadEventLog($lead, $campaignEvent2, $campaign, 1);

        $this->em->flush();

        $eventsStatistics         = $this->getEventsStatistics($campaign);
        Assert::assertSame($expectedEventsStatistics, $eventsStatistics);

        sleep(5);

        $eventsStatistics         = $this->getEventsStatistics($campaign);

        $expectedEventsStatistics = [
            0 => [
                'successPercent' => '100%',
                'completed'      => '1',
                'pending'        => '1',
            ],
            1 => [
                'successPercent' => '100%',
                'completed'      => '1',
                'pending'        => '0',
            ],
        ];

        Assert::assertSame($expectedEventsStatistics, $eventsStatistics);
    }

    private function getTestCrawler(Campaign $campaign): Crawler
    {
        $now    = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $before = $now->modify('-1 month');
        $after  = $now->modify('+1 month');
        $url    = sprintf('/s/campaigns/event/stats/%d/%s/%s', $campaign->getId(), $before->format('Y-m-d'), $after->format('Y-m-d'));
        $this->client->request('GET', $url);
        $response = $this->client->getResponse();
        $body     = \json_decode($response->getContent(), true);

        return new Crawler($body['actions']);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getEventsStatistics(Campaign $campaign): array
    {
        $crawler = $this->getTestCrawler($campaign);
        $events  = [];
        for ($eventIndex = 0;; ++$eventIndex) {
            $crawlerFilter = $crawler->filter('.campaign-event-list')->filter('span');
            $node          = $crawlerFilter->eq($eventIndex * 3);
            if (1 > $node->count()) {
                break;
            }
            $events[] = [
                'successPercent' => trim($crawlerFilter->eq($eventIndex * 3)->html()),
                'completed'      => trim($crawlerFilter->eq($eventIndex * 3 + 1)->html()),
                'pending'        => trim($crawlerFilter->eq($eventIndex * 3 + 2)->html()),
            ];
        }

        return $events;
    }
}
