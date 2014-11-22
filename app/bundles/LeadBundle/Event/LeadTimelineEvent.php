<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class LeadTimelineEvent
 */
class LeadTimelineEvent extends Event
{

    /**
     * Container with all filtered events
     *
     * @var array
     */
    private $events = array();

    /**
     * Container with all registered events types
     *
     * @var array
     */
    private $eventTypes = array();

    /**
     * Container of filtered event types. If empty, all events will be loaded.
     *
     * @var array
     */
    private $eventFilter = array();

    /**
     * Lead entity for the lead the timeline is being generated for
     *
     * @var Lead
     */
    private $lead;

    /**
     * Constructor
     *
     * @param Lead $lead Lead entity for the lead the timeline is being generated for
     * @param array $eventFilter contains all event types which should be loaded. Empty == all.
     */
    public function __construct(Lead $lead, array $eventFilter = array())
    {
        $this->lead = $lead;
        $this->eventFilter = $eventFilter;
    }

    /**
     * Add an event to the container.
     *
     * The data should be an associative array with the following data:
     * 'event'     => string    The event name
     * 'timestamp' => \DateTime The timestamp of the event
     * 'extra'     => array     An optional array of extra data for the event
     *
     * @param array $data Data array for the table
     *
     * @return void
     */
    public function addEvent(array $data)
    {
        $this->events[] = $data;
    }

    /**
     * Fetch the events
     *
     * @return array Events sorted by timestamp with most recent event first
     */
    public function getEvents()
    {
        $events = $this->events;

        usort($events, function($a, $b) {
            if ($a['timestamp'] == $b['timestamp']) {
                return 0;
            }

            return ($a['timestamp'] > $b['timestamp']) ? -1 : 1;
        });

        return $events;
    }

    /**
     * Add an event type to the container.
     *
     * @param string $eventTypeKey Identifier of the event type
     * @param string $eventTypeName Name of the event type for humans
     *
     * @return void
     */
    public function addEventType($eventTypeKey, $eventTypeName)
    {
        $this->eventTypes[$eventTypeKey] = $eventTypeName;
    }

    /**
     * Fetch the event types
     *
     * @return array of available types
     */
    public function getEventTypes()
    {
        return $this->eventTypes;
    }

    /**
     * Fetch the event filter array
     *
     * @return array of wanted filteres. Empty == all.
     */
    public function getEventFilter()
    {
        return $this->eventFilter;
    }

    /**
     * Fetches the lead being acted on
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }
}
