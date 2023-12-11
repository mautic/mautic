<?php

namespace Mautic\WebhookBundle\Form\DataTransformer;

use Doctrine\ORM\PersistentCollection;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Webhook;
use Symfony\Component\Form\DataTransformerInterface;

class EventsToArrayTransformer implements DataTransformerInterface
{
    public function __construct(
        private Webhook $webhook
    ) {
    }

    /**
     * Convert from the PersistentCollection of Event entities to a simple array.
     *
     * @return array
     */
    public function transform($events)
    {
        $eventArray = [];
        foreach ($events as $event) {
            $eventArray[] = $event->getEventType();
        }

        return $eventArray;
    }

    /**
     * Convert a simple array into a PersistentCollection of Event entities.
     *
     * @return PersistentCollection
     */
    public function reverseTransform($submittedArray)
    {
        // Get a list of existing events and types

        //  /** @v ar PersistentCollection[] $events */
        $events     = $this->webhook->getEvents();
        $eventTypes = $events->getKeys();

        // Check to see what events have been removed
        $removed = array_diff($eventTypes, $submittedArray);
        foreach ($removed as $type) {
            $this->webhook->removeEvent($events[$type]);
        }

        // Now check to see what events have been added
        $added = array_diff($submittedArray, $eventTypes);
        foreach ($added as $type) {
            // Create a new entity
            $event = new Event();
            $event->setWebhook($this->webhook)->setEventType($type);
            $events[] = $event;
        }

        $this->webhook->setEvents($events);

        return $events;
    }
}
