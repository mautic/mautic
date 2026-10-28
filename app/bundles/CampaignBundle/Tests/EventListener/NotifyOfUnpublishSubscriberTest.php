<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Event\NotifyOfUnpublishEvent;
use Mautic\CampaignBundle\EventListener\NotifyOfUnpublishSubscriber;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Mautic\CoreBundle\Test\ReflectionHelper;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class NotifyOfUnpublishSubscriberTest extends TestCase
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
        $event = new Event();

        $notifyEvent = new NotifyOfUnpublishEvent($event);

        // Mock the notifyOfUnpublish method to expect the event
        $this->notificationHelper->expects($this->once())
            ->method('notifyOfUnpublish')
            ->with(
                $event
            );

        $this->subscriber->notifyOfUnpublish($notifyEvent);
    }
<<<<<<< HEAD
=======

    public function testGetSubscribedEvents(): void
    {
        $expected = [
            [NotifyOfUnpublishEvent::class, 'notifyOfUnpublish', 0],
        ];
        sort($expected);

        $this->assertSame($expected, ReflectionHelper::getAttributeListeners(NotifyOfUnpublishSubscriber::class));
    }
>>>>>>> 75f52d21d9 (Keep form subscribers on EventSubscriberInterface and fix tests after attribute migration)
}
