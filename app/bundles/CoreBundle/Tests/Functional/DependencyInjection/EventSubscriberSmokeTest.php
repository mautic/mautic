<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EventSubscriberSmokeTest extends AbstractContainerSmokeTestCase
{
    /**
     * There are 300 local event subscribers in the container, keep a small reserve for removed ones.
     */
    private const MINIMAL_EVENT_SUBSCRIBER_COUNT = 297;

    public function testAllEventSubscribersCanBeCreated(): void
    {
        $eventSubscribers = array_filter(
            $this->createAllServices(),
            fn (object $service): bool => $service instanceof EventSubscriberInterface && $this->isLocalService($service)
        );

        $this->assertGreaterThanOrEqual(self::MINIMAL_EVENT_SUBSCRIBER_COUNT, count($eventSubscribers));
    }
}
