<?php

namespace Mautic\CampaignBundle\Tests\EventListener;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Event\NotifyOfFailureEvent;
use Mautic\CampaignBundle\EventListener\NotifyOfFailureSubscriber;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NotifyOfFailureSubscriberTest extends TestCase
{
    private MockObject&NotificationHelper $notificationHelper;

    private NotifyOfFailureSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->notificationHelper = $this->createMock(NotificationHelper::class);
        $this->subscriber         = new NotifyOfFailureSubscriber($this->notificationHelper);
    }

    public function testNotifyOfFailure(): void
    {
        $lead  = new Lead();
        $event = $this->createMock(Event::class);

        $notifyEvent = new NotifyOfFailureEvent($lead, $event);

        // Mock the notifyOfFailure method to expect the lead and event
        $this->notificationHelper->expects($this->once())
            ->method('notifyOfFailure')
            ->with(
                $this->equalTo($lead),
                $this->equalTo($event)
            );

        $this->subscriber->notifyOfFailure($notifyEvent);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = NotifyOfFailureSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey('mautic.campaign_failure_notify', $events);
        $this->assertEquals('notifyOfFailure', $events['mautic.campaign_failure_notify']);
    }
}
