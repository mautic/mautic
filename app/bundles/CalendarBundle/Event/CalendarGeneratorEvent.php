<?php

namespace Mautic\CalendarBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class CalendarGeneratorEvent.
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
    private $events = [];

    public function __construct(array $dates)
    {
        $this->dates = $dates;
    }

    /**
     * Adds an array of events to the container.
     */
    public function addEvents(array $events)
    {
        // Modify colors
        foreach ($events as &$event) {
            if (isset($event['color']) && $event['color']) {
                $event['textColor'] = $this->getContrastColor($event['color']);
                $event['color']     = '#'.$event['color'];
            }
        }

        $this->events = array_merge($this->events, $events);
    }

    /**
     * Fetches the event dates.
     *
     * @return array
     */
    public function getDates()
    {
        return $this->dates;
    }

    /**
     * Fetches the events container.
     *
     * @return array
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Returns contrast hexadecimal color from color in the param.
     * It is used for picking contrast font color on $hex background.
     *
     * @param string $hex
     *
     * @return string
     */
    public function getContrastColor($hex)
    {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        if ($r + $g + $b > 382) {
            //bright color, use dark font
            return '#47535f';
        } else {
            //dark color, use bright font
            return '#ffffff';
        }
    }
}
