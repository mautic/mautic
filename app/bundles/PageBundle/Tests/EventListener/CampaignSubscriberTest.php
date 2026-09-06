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
            $this->createStub(LeadModel::class),
            $this->createStub(TrackingHelper::class),
            $this->createStub(RealTimeExecutioner::class),
        );
    }

    public function testOnCampaignTriggerDecisionMatchesPlainTextUrlFilter(): void
    {
        $event = $this->createCampaignExecutionEvent(
            $this->createHitMock('https://example.com/product/1234', null),
            ['url' => 'product/123']
        );

        $this->subscriber->onCampaignTriggerDecision($event);

        $this->assertTrue($event->getResult());
    }

    public function testOnCampaignTriggerDecisionMatchesLegacyWildcardRefererFilter(): void
    {
        $event = $this->createCampaignExecutionEvent(
            $this->createHitMock('https://example.com/page', 'https://ref.example.com/source/123'),
            ['referer' => '*source/123*']
        );

        $this->subscriber->onCampaignTriggerDecision($event);

        $this->assertTrue($event->getResult());
    }

    public function testOnCampaignTriggerDecisionReturnsFalseWhenUrlDoesNotMatch(): void
    {
        $event = $this->createCampaignExecutionEvent(
            $this->createHitMock('https://example.com/product/1234', null),
            ['url' => 'does-not-match']
        );

        $this->subscriber->onCampaignTriggerDecision($event);

        $this->assertFalse($event->getResult());
    }

    public function testOnCampaignTriggerDecisionMatchesCommaSeparatedUrlFilters(): void
    {
        $event = $this->createCampaignExecutionEvent(
            $this->createHitMock('https://example.com/product/1234', null),
            ['url' => 'alpha,product/123,omega']
        );

        $this->subscriber->onCampaignTriggerDecision($event);

        $this->assertTrue($event->getResult());
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
    private function createCampaignExecutionEvent(Hit&MockObject $hit, array $properties): CampaignExecutionEvent
    {
        // @phpstan-ignore-next-line (CampaignExecutionEvent is deprecated but needed for this test)
        return new CampaignExecutionEvent([
            'lead'            => null,
            'event'           => ['type' => 'page.pagehit', 'parent' => [], 'properties' => $properties],
            'eventDetails'    => $hit,
            'systemTriggered' => true,
            'eventSettings'   => [],
        ], true);
    }
}
