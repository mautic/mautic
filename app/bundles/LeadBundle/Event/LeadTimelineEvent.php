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
     *
     * @param array $data Data array for the table
     *
     * @return void
     * @todo Document container
     */
    public function addEvent(array $data)
    {
        $this->events[] = $data;
    }

    /**
     * Fetch the events
     *
     * @return array
     */
    public function getEvents()
    {
        return $this->events;
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
