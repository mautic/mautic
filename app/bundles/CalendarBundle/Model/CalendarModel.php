<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CalendarBundle\Model;

use Mautic\CalendarBundle\CalendarEvents;
use Mautic\CalendarBundle\Event\CalendarGeneratorEvent;
use Mautic\CalendarBundle\Event\EventGeneratorEvent;
use Mautic\CoreBundle\Model\FormModel;

/**
 * Class CalendarModel.
 */
class CalendarModel extends FormModel
{
    /**
     * Collects data for the calendar display.
     *
     * @param array $dates Associative array containing a 'start_date' and 'end_date' key
     *
     * @return array
     */
    public function getCalendarEvents(array $dates)
    {
        $event = new CalendarGeneratorEvent($dates);
        $this->dispatcher->dispatch(CalendarEvents::CALENDAR_ON_GENERATE, $event);

        return $event->getEvents();
    }

    /**
     * Collects data for the calendar display.
     *
     * @param string $bundle
     * @param int    $id
     *
     * @return array
     */
    public function editCalendarEvent($bundle, $id)
    {
        $event = new EventGeneratorEvent($bundle, $id);
        $this->dispatcher->dispatch(CalendarEvents::CALENDAR_EVENT_ON_GENERATE, $event);

        return $event;
    }
}
