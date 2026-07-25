<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EventSubscriberSmokeTest extends AbstractContainerSmokeTestCase
{
    /**
     * There are 368 event subscribers in the container, keep a small reserve for removed ones.
     */
    private const MINIMAL_EVENT_SUBSCRIBER_COUNT = 365;

    public function testAllEventSubscribersCanBeCreated(): void
    {
        $eventSubscribers = array_filter(
            $this->createAllServices(),
            static fn (object $service): bool => $service instanceof EventSubscriberInterface
        );

        $this->assertGreaterThanOrEqual(self::MINIMAL_EVENT_SUBSCRIBER_COUNT, count($eventSubscribers));
    }
}
