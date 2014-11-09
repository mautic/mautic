<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Templating\Helper\Helper;

class DateHelper extends Helper
{
    /**
     * @var array
     */
    protected $formats;

    public function __construct(MauticFactory $factory)
    {
        $this->formats = array(
            'datetime' => $factory->getParameter('date_format_full'),
            'short'    => $factory->getParameter('date_format_short'),
            'date'     => $factory->getParameter('date_format_dateonly'),
            'time'     => $factory->getParameter('date_format_timeonly'),
        );

        $this->helper  = $factory->getDate();
    }

    protected function format($type, $datetime, $timezone, $fromFormat)
    {
        $this->helper->setDateTime($datetime, $fromFormat, $timezone);
        return $this->helper->toLocalString(
            $this->formats[$type]
        );
    }

    /**
     * Returns full date. eg. October 8, 2014 21:19
     *
     * @param $datetime
     */
    public function toFull($datetime, $timezone = 'local', $fromFormat = 'Y-m-d H:i:s')
    {
        return $this->format('datetime', $datetime, $timezone, $fromFormat);
    }

    /**
     * Returns date and time concat eg 2014-08-02 5:00am
     *
     * @param        $datetime
     * @param string $fromFormat
     * @param string $timezone
     */
    public function toFullConcat($datetime, $timezone = 'local', $fromFormat = 'Y-m-d H:i:s')
    {
        $this->helper->setDateTime($datetime, $fromFormat, $timezone);
        return $this->helper->toLocalString(
            $this->formats['date'] . ' ' . $this->formats['time']
        );
    }

    /**
     * Returns short date format eg Sun, Oct 8
     *
     * @param        $datetime
     * @param string $timezone
     * @param string $fromFormat
     *
     * @return mixed
     */
    public function toShort($datetime, $timezone = 'local', $fromFormat = 'Y-m-d H:i:s')
    {
        return $this->format('short', $datetime, $timezone, $fromFormat);
    }

    /**
     * Returns date only e.g. 2014-08-09
     *
     * @param        $datetime
     * @param string $fromFormat
     * @param string $timezone
     *
     * @return mixed
     */
    public function toDate($datetime, $timezone = 'local', $fromFormat = 'Y-m-d H:i:s')
    {
        return $this->format('date', $datetime, $timezone, $fromFormat);
    }

    /**
     * Returns time only e.g. 21:19
     *
     * @param        $datetime
     * @param string $fromFormat
     * @param string $timezone
     *
     * @return mixed
     */
    public function toTime($datetime, $timezone = 'local', $fromFormat = 'Y-m-d H:i:s')
    {
        return $this->format('time', $datetime, $timezone, $fromFormat);
    }

    /**
     * @return mixed
     */
    public function getFullFormat()
    {
        return $this->formats['datetime'];
    }

    /**
     * @return mixed
     */
    public function getDateFormat()
    {
        return $this->formats['date'];
    }

    /**
     * @return mixed
     */
    public function getTimeFormat()
    {
        return $this->formats['time'];
    }

    /**
     * @return mixed
     */
    public function getShortFormat()
    {
        return $this->formats['short'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'date';
    }
}