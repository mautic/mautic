<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Helper\DateTimeHelper;
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
     * Array of filters
     *  search => (string) search term
     *  includeEvents => (array) event types to include
     *  excludeEvents => (array) event types to exclude
     *
     * @var array
     */
    private $filters = array();

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
     * @param array $filters  Array of filter string, include filter types and exclude filter types
     */
    public function __construct(Lead $lead, array $filters = array())
    {
        $this->lead    = $lead;
        $this->filters = $filters;
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
    public function getEvents($returnGrouped = false)
    {
        $events = $this->events;

        $byDate = array();

        // Group by date
        foreach ($events as $e) {
            if (!$e['timestamp'] instanceof \DateTime) {
                $dt = new DateTimeHelper($e['timestamp'], 'Y-m-d H:i:s', 'UTC');
                $e['timestamp'] = $dt->getDateTime();
                unset($dt);
            }
            $dateString = $e['timestamp']->format('Y-m-d H:i:s');
            if (!isset($byDate[$dateString])) {
                $byDate[$dateString] = array();
            }

            $byDate[$dateString][] = $e;
        }

        // Sort by date
        krsort($byDate);

        // Sort by certain event actions
        $order = array(
            'page.hit',
            'asset.download',
            'form.submitted',
            'lead.merge',
            'lead.create',
            'lead.ipadded',
            'lead.identified'
        );

        $events = array();
        foreach ($byDate as $date => $dateEvents) {
            usort(
                $dateEvents,
                function ($a, $b) use ($order) {
                    if (!in_array($a['event'], $order) || !in_array($b['event'], $order)) {
                        // No specific order so push to the end

                        return 1;
                    }

                    $pos_a = array_search($a['event'], $order);
                    $pos_b = array_search($b['event'], $order);

                    return $pos_a - $pos_b;
                }
            );

            $byDate[$date] = $dateEvents;
            $events = array_merge($events, array_reverse($dateEvents));
        }

        return ($returnGrouped) ? $byDate : $events;
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
    public function getEventFilters()
    {
        return $this->filters;
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

    /**
     * Determine if an event type should be included
     *
     * @param $eventType
     *
     * @return bool
     */
    public function isApplicable($eventType)
    {
        if (in_array($eventType, $this->filters['excludeEvents'])) {
            return false;
        }

        if (!empty($this->filters['includeEvents'])) {
            if (!in_array($eventType, $this->filters['includeEvents'])) {
                return false;
            }
        }

        return true;
    }
}
