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
     * @param array $dates
     */
    public function __construct(array $dates)
    {
        $this->dates = $dates;
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return $this->dates;
    }

    public function getEvents()
    {
    }
}
