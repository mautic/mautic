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
     * @param $datetime
     */
    public function toFull($datetime, $timezone = 'local', $fromFormat = 'Y-m-d H:i:s')
    {
        return $this->format('datetime', $datetime, $timezone, $fromFormat);
    }

    /**
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
     * @return string
     */
    public function getName()
    {
        return 'date';
    }
}