<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CalendarBundle;

/**
 * Class CalendarEvents
 *
 * Events available for CalendarBundle
 */
final class CalendarEvents
{

    /**
     * The mautic.calendar_on_generate event is thrown when generating a calendar view
     *
     * The event listener receives a Mautic\CalendarBundle\Event\CalendarGeneratorEvent instance.
     *
     * @var string
     */
    const CALENDAR_ON_GENERATE = 'mautic.calendar_on_generate';
}
