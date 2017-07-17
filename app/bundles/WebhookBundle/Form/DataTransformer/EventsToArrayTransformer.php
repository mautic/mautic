<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Form\DataTransformer;

use Doctrine\ORM\PersistentCollection;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Webhook;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class EventsToArrayTransformer.
 */
class EventsToArrayTransformer implements DataTransformerInterface
{
    private $webhook;

    public function __construct(Webhook $webhook)
    {
        $this->webhook = $webhook;
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

        /** @var PersistentCollection $events */
        $events     = $this->webhook->getEvents();
        $eventTypes = $events->getKeys();

        // Check to see what events have been removed
        $removed = array_diff($eventTypes, $submittedArray);
        if ($removed) {
            foreach ($removed as $type) {
                $this->webhook->removeEvent($events[$type]);
            }
        }

        // Now check to see what events have been added
        $added = array_diff($submittedArray, $eventTypes);
        if ($added) {
            foreach ($added as $type) {
                // Create a new entity
                $event = new Event();
                $event->setWebhook($this->webhook)->setEventType($type);
                $events[] = $event;
            }
        }

        $this->webhook->setEvents($events);

        return $events;
    }
}
