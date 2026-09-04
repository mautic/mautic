<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Helper\TrackingHelper;
use MauticPlugin\MauticFocusBundle\EventListener\CampaignSubscriber;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

#[AllowMockObjectsWithoutExpectations]
final class CampaignSubscriberTest extends TestCase
{
    public function testActionPassesWhenFocusIsConfigured(): void
    {
        $trackingHelper = $this->createMock(TrackingHelper::class);
        $trackingHelper->expects($this->once())->method('updateCacheItem');

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('https://mautic.org/focus/1');

        $subscriber   = new CampaignSubscriber($trackingHelper, $router);
        $pendingEvent = $this->createPendingEvent(['focus' => 1]);

        $subscriber->onCampaignTriggerAction($pendingEvent);

        $this->assertCount(1, $pendingEvent->getSuccessful());
        $this->assertCount(0, $pendingEvent->getFailures());
    }

    public function testActionFailsWhenFocusIsMissing(): void
    {
        $trackingHelper = $this->createMock(TrackingHelper::class);
        $trackingHelper->expects($this->never())->method('updateCacheItem');

        $subscriber   = new CampaignSubscriber($trackingHelper, $this->createStub(RouterInterface::class));
        $pendingEvent = $this->createPendingEvent(['focus' => 0]);

        $subscriber->onCampaignTriggerAction($pendingEvent);

        $this->assertCount(0, $pendingEvent->getSuccessful());
        $this->assertCount(1, $pendingEvent->getFailures());
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function createPendingEvent(array $properties): PendingEvent
    {
        $log = new LeadEventLog();
        $log->setLead($this->createStub(Lead::class));

        $event = new Event();
        $event->setCampaign(new Campaign());
        $event->setType('focus.show');
        $event->setProperties($properties);
        $log->setEvent($event);

        return new PendingEvent(new ActionAccessor([]), $event, new ArrayCollection([$log]));
    }
}
