<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Executioner\Helper\EventRedirectionHelper;
use Mautic\CoreBundle\Test\IsolatedTestTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class EventRedirectionHelperTest extends TestCase
{
    use IsolatedTestTrait;

    /**
     * Test that handleEventRedirection correctly processes a single-level redirection.
     */
    public function testHandleEventRedirection(): void
    {
        $campaign = new Campaign();

        $deletedEvent = $this->createEventWithId(1);
        $deletedEvent->setCampaign($campaign);
        $deletedEvent->setDeleted(new \DateTime());

        $redirectEvent = $this->createEventWithId(2);
        $redirectEvent->setCampaign($campaign);

        $deletedEvent->setRedirectEvent($redirectEvent);

        $events = new ArrayCollection([$deletedEvent, $redirectEvent]);
        $this->setPrivateProperty($campaign, 'events', $events);

        $logs   = new ArrayCollection([0 => $deletedEvent]);
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Event ID 1 was deleted, redirected to event ID 2 for execution'));

        $helper = new EventRedirectionHelper($logger);
        $result = $helper->handleEventRedirection($deletedEvent, $logs, 0);

        $this->assertSame($redirectEvent, $result);
        $this->assertSame($redirectEvent, $logs->get(0));
    }

    /**
     * Test that handleEventRedirection returns the original event for non-deleted events.
     */
    public function testHandleEventRedirectionNonDeleted(): void
    {
        $campaign = new Campaign();
        $event    = $this->createEventWithId(1);
        $event->setCampaign($campaign);

        $logs   = new ArrayCollection([0 => $event]);
        $logger = $this->createMock(LoggerInterface::class);
        $helper = new EventRedirectionHelper($logger);
        $result = $helper->handleEventRedirection($event, $logs, 0);

        $this->assertSame($event, $result);
    }

    /**
     * Test that handleEventRedirection returns the original event when no redirect event is found.
     */
    public function testHandleEventRedirectionNoRedirect(): void
    {
        $campaign = new Campaign();

        $deletedEvent     = $this->createEventWithId(1);
        $nonExistentEvent = $this->createMock(Event::class);
        $nonExistentEvent->method('getId')->willReturn(null);
        $deletedEvent->setRedirectEvent($nonExistentEvent);
        $deletedEvent->setCampaign($campaign);
        $deletedEvent->setDeleted(new \DateTime());

        $deletedEvent = $this->getMockBuilder(Event::class)
            ->onlyMethods(['shouldBeRedirected', 'getId', 'getRedirectEvent'])
            ->disableOriginalConstructor()
            ->getMock();
        $deletedEvent->method('shouldBeRedirected')->willReturn(false);
        $deletedEvent->method('getId')->willReturn(1);
        $deletedEvent->method('getRedirectEvent')->willReturn(null);

        $events = new ArrayCollection([$deletedEvent]);
        $this->setPrivateProperty($campaign, 'events', $events);

        $logs = new ArrayCollection([0 => $deletedEvent]);

        $logger = $this->createMock(LoggerInterface::class);

        $helper = new EventRedirectionHelper($logger);

        $result = $helper->handleEventRedirection($deletedEvent, $logs, 0);

        $this->assertSame($deletedEvent, $result);

        $this->assertSame($deletedEvent, $logs->get(0));
    }

    /**
     * Test that event redirection correctly finds a redirect event within a campaign's events collection.
     */
    public function testRedirectEventInCampaign(): void
    {
        $campaign = new Campaign();

        $deletedEvent = new Event();
        $this->setPrivateProperty($deletedEvent, 'id', 1);
        $deletedEvent->setDeleted(new \DateTime());
        $deletedEvent->setCampaign($campaign);

        $redirectEvent = new Event();
        $this->setPrivateProperty($redirectEvent, 'id', 2);
        $redirectEvent->setCampaign($campaign);

        $deletedEvent->setRedirectEvent($redirectEvent);

        $otherEvent = new Event();
        $this->setPrivateProperty($otherEvent, 'id', 3);
        $otherEvent->setCampaign($campaign);

        $events = new ArrayCollection([$deletedEvent, $redirectEvent, $otherEvent]);
        $this->setPrivateProperty($campaign, 'events', $events);

        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Event ID 1 was deleted, redirected to event ID 2'));

        $helper = new EventRedirectionHelper($logger);

        $testCollection = new ArrayCollection([$deletedEvent]);

        $result = $helper->handleEventRedirection($deletedEvent, $testCollection, 0);

        $this->assertSame($redirectEvent, $result);

        $this->assertSame($redirectEvent, $testCollection->get(0));
    }

    /**
     * Test that event redirection correctly follows chains of redirections.
     */
    public function testRedirectEventChain(): void
    {
        $campaign = new Campaign();

        $event1 = new Event();
        $this->setPrivateProperty($event1, 'id', 1);
        $event1->setDeleted(new \DateTime());
        $event1->setCampaign($campaign);

        $event2 = new Event();
        $this->setPrivateProperty($event2, 'id', 2);
        $event2->setDeleted(new \DateTime());
        $event2->setCampaign($campaign);

        $event3 = new Event();
        $this->setPrivateProperty($event3, 'id', 3);
        $event3->setCampaign($campaign);

        $event1->setRedirectEvent($event2);
        $event2->setRedirectEvent($event3);

        $events = new ArrayCollection([$event1, $event2, $event3]);
        $this->setPrivateProperty($campaign, 'events', $events);

        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->atLeast(2))
            ->method('debug')
            ->with($this->logicalOr(
                $this->stringContains('Redirect event ID 2 is also deleted'),
                $this->stringContains('Event ID 1 was deleted, redirected to event ID 3')
            ));

        $helper = new EventRedirectionHelper($logger);

        $testCollection = new ArrayCollection([$event1]);

        $result = $helper->handleEventRedirection($event1, $testCollection, 0);

        $this->assertSame($event3, $result);

        $this->assertSame($event3, $testCollection->get(0));
    }

    /**
     * Test that handleEventRedirection returns the original event when no redirect event ID is set.
     */
    public function testHandleEventRedirectionNoRedirectId(): void
    {
        $campaign = new Campaign();

        $event = new Event();
        $this->setPrivateProperty($event, 'id', 1);
        $event->setDeleted(new \DateTime());
        $event->setCampaign($campaign);

        $testCollection = new ArrayCollection([$event]);

        $logger = $this->createMock(LoggerInterface::class);

        $helper = new EventRedirectionHelper($logger);

        $result = $helper->handleEventRedirection($event, $testCollection, 0);

        $this->assertSame($event, $result);

        $this->assertSame($event, $testCollection->get(0));
    }

    /**
     * Test that handleEventRedirection returns the original event when not a deleted event.
     */
    public function testHandleEventRedirectionNotDeleted(): void
    {
        $campaign = new Campaign();

        $event = new Event();
        $this->setPrivateProperty($event, 'id', 1);

        $targetEvent = new Event();
        $this->setPrivateProperty($targetEvent, 'id', 2);
        $targetEvent->setCampaign($campaign);

        $event->setRedirectEvent($targetEvent);

        $event->setCampaign($campaign);

        $testCollection = new ArrayCollection([$event]);

        $logger = $this->createMock(LoggerInterface::class);

        $helper = new EventRedirectionHelper($logger);

        $result = $helper->handleEventRedirection($event, $testCollection, 0);

        $this->assertSame($event, $result);

        $this->assertSame($event, $testCollection->get(0));
    }

    /**
     * Creates an Event entity with the given ID for testing.
     */
    private function createEventWithId(int $id): Event
    {
        $event = new Event();
        $this->setPrivateProperty($event, 'id', $id);

        return $event;
    }

    /**
     * Helper method to set private or protected properties on an object using reflection.
     *
     * @param mixed $value The value to set on the property
     */
    private function setPrivateProperty(object $object, string $property, $value): void
    {
        $reflection = new \ReflectionClass($object);
        $prop       = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }
}
