<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
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
            $this->createMock(LeadModel::class),
            $this->createMock(TrackingHelper::class),
            $this->createMock(RealTimeExecutioner::class),
        );
    }

    public function testOnCampaignTriggerDecisionMatchesPlainTextUrlFilter(): void
    {
        $hit = $this->createHitMock('https://example.com/product/1234', null);

        $event = new CampaignExecutionEvent([
            'lead'            => null,
            'event'           => ['type' => 'page.pagehit', 'parent' => [], 'properties' => ['url' => 'product/123']],
            'eventDetails'    => $hit,
            'systemTriggered' => true,
            'eventSettings'   => [],
        ], true);

        $this->subscriber->onCampaignTriggerDecision($event);

        self::assertTrue($event->getResult());
    }

    public function testOnCampaignTriggerDecisionMatchesLegacyWildcardRefererFilter(): void
    {
        $hit = $this->createHitMock('https://example.com/page', 'https://ref.example.com/source/123');

        $event = new CampaignExecutionEvent([
            'lead'            => null,
            'event'           => ['type' => 'page.pagehit', 'parent' => [], 'properties' => ['referer' => '*source/123*']],
            'eventDetails'    => $hit,
            'systemTriggered' => true,
            'eventSettings'   => [],
        ], true);

        $this->subscriber->onCampaignTriggerDecision($event);

        self::assertTrue($event->getResult());
    }

    public function testOnCampaignTriggerDecisionReturnsFalseWhenUrlDoesNotMatch(): void
    {
        $hit = $this->createHitMock('https://example.com/product/1234', null);

        $event = new CampaignExecutionEvent([
            'lead'            => null,
            'event'           => ['type' => 'page.pagehit', 'parent' => [], 'properties' => ['url' => 'does-not-match']],
            'eventDetails'    => $hit,
            'systemTriggered' => true,
            'eventSettings'   => [],
        ], true);

        $this->subscriber->onCampaignTriggerDecision($event);

        self::assertFalse($event->getResult());
    }

    public function testOnCampaignTriggerDecisionMatchesCommaSeparatedUrlFilters(): void
    {
        $hit = $this->createHitMock('https://example.com/product/1234', null);

        $event = new CampaignExecutionEvent([
            'lead'            => null,
            'event'           => ['type' => 'page.pagehit', 'parent' => [], 'properties' => ['url' => 'alpha,product/123,omega']],
            'eventDetails'    => $hit,
            'systemTriggered' => true,
            'eventSettings'   => [],
        ], true);

        $this->subscriber->onCampaignTriggerDecision($event);

        self::assertTrue($event->getResult());
    }

    private function createHitMock(string $url, ?string $referer): Hit&MockObject
    {
        $hit = $this->createMock(Hit::class);
        $hit->method('getUrl')->willReturn($url);
        $hit->method('getReferer')->willReturn($referer);
        $hit->method('getPage')->willReturn(null);

        return $hit;
    }
}
