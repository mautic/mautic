<?php

namespace Mautic\CampaignBundle\Tests\EventListener;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Event\NotifyOfUnpublishEvent;
use Mautic\CampaignBundle\EventListener\NotifyOfUnpublishSubscriber;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NotifyOfUnpublishSubscriberTest extends TestCase
{
    private MockObject&NotificationHelper $notificationHelper;
    private NotifyOfUnpublishSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->notificationHelper = $this->createMock(NotificationHelper::class);
        $this->subscriber         = new NotifyOfUnpublishSubscriber($this->notificationHelper);
    }

    public function testNotifyOfUnpublish(): void
    {
        $event = $this->createMock(Event::class);

        $notifyEvent = new NotifyOfUnpublishEvent($event);

        // Mock the notifyOfUnpublish method to expect the event
        $this->notificationHelper->expects($this->once())
            ->method('notifyOfUnpublish')
            ->with(
                $this->equalTo($event)
            );

        $this->subscriber->notifyOfUnpublish($notifyEvent);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = NotifyOfUnpublishSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey('mautic.campaign_unpublish_notify', $events);
        $this->assertEquals('notifyOfUnpublish', $events['mautic.campaign_unpublish_notify']);
    }
}
