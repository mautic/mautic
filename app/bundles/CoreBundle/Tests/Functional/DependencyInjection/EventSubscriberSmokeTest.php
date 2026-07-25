<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EventSubscriberSmokeTest extends AbstractContainerSmokeTestCase
{
    /**
     * There are 276 local event subscribers in the container, keep a small reserve for removed ones.
     */
    private const MINIMAL_EVENT_SUBSCRIBER_COUNT = 273;

    public function testAllEventSubscribersCanBeCreated(): void
    {
        // vendor and plugin subscribers are out of scope
        $eventSubscribers = array_filter(
            $this->createAllServices(),
            static fn (object $service): bool => $service instanceof EventSubscriberInterface
                && str_starts_with($service::class, 'Mautic\\')
        );

        $this->assertGreaterThanOrEqual(self::MINIMAL_EVENT_SUBSCRIBER_COUNT, count($eventSubscribers));
    }
}
