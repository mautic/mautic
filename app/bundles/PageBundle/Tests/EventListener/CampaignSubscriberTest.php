<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\DecisionEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\EventListener\CampaignSubscriber;
use Mautic\PageBundle\Helper\TrackingHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CampaignSubscriberTest extends TestCase
{
    private CampaignSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new CampaignSubscriber(
            $this->createStub(LeadModel::class),
            $this->createStub(TrackingHelper::class),
            $this->createStub(RealTimeExecutioner::class),
        );
    }

    public function testOnCampaignTriggerDecisionMatchesPlainTextUrlFilter(): void
    {
        $event = $this->createDecisionEvent(
            $this->createHitMock('https://example.com/product/1234', null),
            ['url' => 'product/123']
        );

        $this->subscriber->onCampaignTriggerDecision($event);

        $this->assertTrue($event->wasDecisionApplicable());
    }

    public function testOnCampaignTriggerDecisionMatchesLegacyWildcardRefererFilter(): void
    {
        $event = $this->createDecisionEvent(
            $this->createHitMock('https://example.com/page', 'https://ref.example.com/source/123'),
            ['referer' => '*source/123*']
        );

        $this->subscriber->onCampaignTriggerDecision($event);

        $this->assertTrue($event->wasDecisionApplicable());
    }

    public function testOnCampaignTriggerDecisionReturnsFalseWhenUrlDoesNotMatch(): void
    {
        $event = $this->createDecisionEvent(
            $this->createHitMock('https://example.com/product/1234', null),
            ['url' => 'does-not-match']
        );

        $this->subscriber->onCampaignTriggerDecision($event);

        $this->assertFalse($event->wasDecisionApplicable());
    }

    public function testOnCampaignTriggerDecisionMatchesCommaSeparatedUrlFilters(): void
    {
        $event = $this->createDecisionEvent(
            $this->createHitMock('https://example.com/product/1234', null),
            ['url' => 'alpha,product/123,omega']
        );

        $this->subscriber->onCampaignTriggerDecision($event);

        $this->assertTrue($event->wasDecisionApplicable());
    }

    private function createHitMock(string $url, ?string $referer): Hit&MockObject
    {
        $hit = $this->createMock(Hit::class);
        $hit->method('getUrl')->willReturn($url);
        $hit->method('getReferer')->willReturn($referer);
        $hit->method('getPage')->willReturn(null);

        return $hit;
    }

    /**
     * @param array<string, string> $properties
     */
    private function createDecisionEvent(Hit&MockObject $hit, array $properties): DecisionEvent
    {
        $campaign = new Campaign();
        $campaign->setName('Test Campaign');

        $campaignEvent = new Event();
        $campaignEvent->setType('page.pagehit');
        $campaignEvent->setProperties($properties);
        $campaignEvent->setCampaign($campaign);

        $log = $this->createStub(LeadEventLog::class);
        $log->method('getEvent')->willReturn($campaignEvent);
        $log->method('getLead')->willReturn(null);

        $config = $this->createStub(DecisionAccessor::class);
        $config->method('getConfig')->willReturn([]);

        return new DecisionEvent($config, $log, $hit);
    }
}
