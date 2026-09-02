<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;
use Mautic\PageBundle\EventListener\CampaignSubscriber;
use Mautic\PageBundle\Helper\TrackingHelper;
use PHPUnit\Framework\TestCase;

final class CampaignSubscriberTest extends TestCase
{
    public function testActionPassesAllWhenServicesConfigured(): void
    {
        $trackingHelper = $this->createMock(TrackingHelper::class);
        $trackingHelper->expects($this->once())->method('updateCacheItem');

        $subscriber   = $this->getSubscriber($trackingHelper);
        $pendingEvent = $this->createPendingEvent([
            'services' => ['google_analytics'],
            'category' => 'cat',
            'action'   => 'act',
            'label'    => 'lbl',
        ]);

        $subscriber->onCampaignTriggerAction($pendingEvent);

        $this->assertCount(1, $pendingEvent->getSuccessful());
        $this->assertCount(0, $pendingEvent->getFailures());
    }

    public function testActionFailsAllWhenNoServices(): void
    {
        $trackingHelper = $this->createMock(TrackingHelper::class);
        $trackingHelper->expects($this->never())->method('updateCacheItem');

        $subscriber   = $this->getSubscriber($trackingHelper);
        $pendingEvent = $this->createPendingEvent(['services' => []]);

        $subscriber->onCampaignTriggerAction($pendingEvent);

        $this->assertCount(0, $pendingEvent->getSuccessful());
        $this->assertCount(1, $pendingEvent->getFailures());
    }

    private function getSubscriber(TrackingHelper $trackingHelper): CampaignSubscriber
    {
        return new CampaignSubscriber(
            $this->createStub(LeadDeviceRepository::class),
            $trackingHelper,
            $this->createStub(RealTimeExecutioner::class)
        );
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
        $event->setType('tracking.pixel.send');
        $event->setProperties($properties);
        $log->setEvent($event);

        return new PendingEvent(new ActionAccessor([]), $event, new ArrayCollection([$log]));
    }
}
