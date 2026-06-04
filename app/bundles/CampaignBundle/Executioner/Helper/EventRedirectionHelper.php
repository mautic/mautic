<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Executioner\Helper;

use Doctrine\Common\Collections\Collection;
use Mautic\CampaignBundle\Entity\Event;
use Psr\Log\LoggerInterface;

/**
 * Helper class for handling event redirection.
 * This helper centralizes logic for finding and following chains of redirected events.
 * It also provides methods to handle the replacement of deleted events with their redirects.
 */
class EventRedirectionHelper
{
    private const MAX_DEPTH = 20;

    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Handle redirection for a deleted event and replace it in the event's collection.
     * If a redirection is found, returns the redirected event; otherwise returns the original event.
     *
     * @param Event           $event  The event to check for redirection
     * @param ?Collection     $events The collection containing the event
     * @param int|string|null $key    The key of the event in the collection
     *
     * @return Event The redirected event if redirection was successful, or the original event if not
     */
    public function handleEventRedirection(Event $event, ?Collection $events, int|string|null $key): Event
    {
        $visited       = [];
        $redirectEvent = $this->findRedirectEventInCampaign($event, $visited, 1);

        if (!$redirectEvent) {
            return $event;
        }

        // Replace the current event with the redirected event in the collection
        if ($events && null !== $key) {
            $events->set($key, $redirectEvent);
        }

        $this->logger->debug(
            sprintf(
                'CAMPAIGN: Event ID %d was deleted, redirected to event ID %d for execution',
                $event->getId(),
                $redirectEvent->getId()
            )
        );

        return $redirectEvent;
    }

    /**
     * Internal helper to check for recursion depth and cycles in redirection chains.
     * Returns true if a limit or cycle is detected, false otherwise.
     *
     * @param int   $id      The event ID to check
     * @param int[] $visited The array of visited event IDs
     * @param int   $depth   The current recursion depth
     *
     * @return bool True if a limit/cycle is detected, false otherwise
     */
    private function isRedirectionLimitOrCycle(int $id, array $visited, int $depth): bool
    {
        if ($depth > self::MAX_DEPTH) {
            $this->logger->warning('CAMPAIGN: Maximum redirection depth reached for event ID '.$id);

            return true;
        }
        if (in_array($id, $visited, true)) {
            $this->logger->warning('CAMPAIGN: Detected redirection cycle for event ID '.$id);

            return true;
        }

        return false;
    }

    /**
     * Find a redirect event within a campaign's event collection.
     * This variation is used when working with in-memory event collections.
     *
     * @param int[] $visited (internal) Used to track visited event IDs for cycle detection
     * @param int   $depth   (internal) Used to track recursion depth
     */
    private function findRedirectEventInCampaign(Event $event, array $visited = [], int $depth = 0): ?Event
    {
        if (!$event->shouldBeRedirected()) {
            return null;
        }

        if ($this->isRedirectionLimitOrCycle($event->getId(), $visited, $depth)) {
            return null;
        }

        $visited[]     = $event->getId();
        $redirectEvent = $event->getRedirectEvent();

        if (!$redirectEvent) {
            return null;
        }

        if (!$redirectEvent->shouldBeRedirected()) {
            return $redirectEvent;
        }

        $this->logger->debug(
            sprintf('CAMPAIGN: Redirect event ID %d is also deleted, following redirect chain',
                $redirectEvent->getId())
        );

        return $this->findRedirectEventInCampaign($redirectEvent, $visited, $depth + 1);
    }
}
