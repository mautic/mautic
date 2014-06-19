<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

class DateTimeHelper
{

    private $string;
    private $format;
    private $timezone;
    private $utc;
    private $local;
    private $datetime;

    /**
     * @param string $string Datetime string
     * @param string $inFormat format the string is in
     * @param string $timezone Timezone the string is in
     */
    public function __construct($string = '', $fromFormat = 'Y-m-d H:i:s', $timezone = 'UTC')
    {
        if ($timezone == 'local') {
            $timezone = date_default_timezone_get();
        }

        $this->format   = $fromFormat;
        $this->timezone = $timezone;

        $this->utc   = new \DateTimeZone('UTC');
        $this->local = new \DateTimeZone(date_default_timezone_get());

        if (empty($string)) {
            $this->datetime = new \DateTime("now", new \DateTimeZone($this->timezone));
            $this->string = $this->datetime->format($fromFormat);
        } else {
            $this->string = $string;

            $this->datetime = \DateTime::createFromFormat(
                $this->format,
                $this->string,
                new \DateTimeZone($this->timezone)
            );
        }
    }

    public function getUtcString($format = null)
    {
        if ($this->datetime) {
            $utc = $this->datetime->setTimezone($this->utc);
            if (empty($format)) {
                $format = $this->format;
            }
            return $utc->format($format);
        } else {
            return $this->string;
        }
    }

    public function getLocalString($format = null)
    {
        if ($this->datetime) {
            $local = $this->datetime->setTimezone($this->local);
            if (empty($format)) {
                $format = $this->format;
            }
            return $local->format($format);
        } else {
            return $this->string;
        }
    }

    public function getUtcDateTime()
    {
        $utc = $this->datetime->setTimezone($this->utc);
        return $utc;
    }

    public function getLocalDateTime()
    {
        $local = $this->datetime->setTimezone($this->local);
        return $local;
    }

    public function getString($format = null)
    {
        if (empty($format)) {
            $format = $this->format;
        }
        return $this->datetime->format($format);
    }

    public function getDateTime()
    {
        return $this->datetime;
    }
}