<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CalendarBundle\Event;

use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CalendarGeneratorEvent
 */
class CalendarGeneratorEvent extends Event
{

    /**
     * @var array
     */
    private $dates;

    /**
     * @var array
     */
    private $events = array();

    /**
     * @param array $dates
     */
    public function __construct(array $dates)
    {
        $this->dates = $dates;
    }

    /**
     * Adds an array of events to the container
     *
     * @param array $events
     *
     * @return void
     */
    public function addEvents(array $events)
    {
        $this->events = array_merge($this->events, $events);
    }

    /**
     * Fetches the event dates
     *
     * @return array
     */
    public function getDates()
    {
        return $this->dates;
    }

    /**
     * Fetches the events container
     *
     * @return array
     */
    public function getEvents()
    {
        return $this->events;
    }
}
