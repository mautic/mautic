<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class EventSubscriberSmokeTest extends AbstractContainerSmokeTestCase
{
    /**
     * There are 300 local event subscribers in the container, keep a small reserve for removed ones.
     */
    private const MINIMAL_EVENT_SUBSCRIBER_COUNT = 297;

    /**
     * Exact number of local event subscribers per hand-picked event, to catch a subscriber that silently stops listening.
     *
     * @var array<string, int>
     */
    private const EXPECTED_SUBSCRIBER_COUNTS = [
        LogoutEvent::class        => 1,
        CheckPassportEvent::class => 3,
        'kernel.request'          => 8,
        'kernel.response'         => 3,
        'kernel.exception'        => 1,
        'security.interactive_login' => 1,
        'mautic.campaign_on_build'   => 21,
        'mautic.report_on_build'     => 17,
        'mautic.config_on_generate'  => 16,
        'mautic.email_on_send'       => 12,
        'mautic.form_on_build'       => 7,
        'mautic.lead_post_save'      => 5,
    ];

    public function testAllEventSubscribersCanBeCreated(): void
    {
        $this->assertGreaterThanOrEqual(self::MINIMAL_EVENT_SUBSCRIBER_COUNT, count($this->resolveEventSubscribers()));
    }

    public function testEventSubscriberCountsPerEvent(): void
    {
        $subscriberCounts = [];

        foreach ($this->resolveEventSubscribers() as $eventSubscriber) {
            foreach (array_keys($eventSubscriber::getSubscribedEvents()) as $eventName) {
                $subscriberCounts[$eventName] = ($subscriberCounts[$eventName] ?? 0) + 1;
            }
        }

        foreach (self::EXPECTED_SUBSCRIBER_COUNTS as $eventName => $expectedCount) {
            $this->assertSame(
                $expectedCount,
                $subscriberCounts[$eventName] ?? 0,
                sprintf('Unexpected number of local event subscribers for the "%s" event', $eventName)
            );
        }
    }

    /**
     * @return array<int, EventSubscriberInterface>
     */
    private function resolveEventSubscribers(): array
    {
        return array_filter(
            $this->createAllServices(),
            fn (object $service): bool => $service instanceof EventSubscriberInterface && $this->isLocalService($service)
        );
    }
}
