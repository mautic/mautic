<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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
     * Container with all registered events
     *
     * @var array
     */
    private $events = array();

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
     */
    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
    }

    /**
     * Add an event to the container.
     *
     * The data should be an associative array with the following data:
     * 'event' =>     string    The event name
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
     * Fetches the lead being acted on
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }
}
