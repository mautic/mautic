<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

class DateTimeHelper
{
    /**
     * @var string
     */
    private $string;

    /**
     * @var string
     */
    private $format;

    /**
     * @var string
     */
    private $timezone;

    /**
     * @var \DateTimeZone
     */
    private $utc;

    /**
     * @var \DateTimeZone
     */
    private $local;

    /**
     * @var \DateTimeInterface
     */
    private $datetime;

    /**
     * @param \DateTimeInterface|string $string
     * @param string                    $fromFormat Format the string is in
     * @param string                    $timezone   Timezone the string is in
     */
    public function __construct($string = '', $fromFormat = 'Y-m-d H:i:s', $timezone = 'UTC')
    {
        $this->setDateTime($string, $fromFormat, $timezone);
    }

    /**
     * Sets date/time.
     *
     * @param \DateTimeInterface|string $datetime
     * @param string                    $fromFormat
     * @param string                    $timezone
     */
    public function setDateTime($datetime = '', $fromFormat = 'Y-m-d H:i:s', $timezone = 'local')
    {
        $localTimezone = date_default_timezone_get();
        if ('local' == $timezone) {
            $timezone = $localTimezone;
        } elseif (empty($timezone)) {
            $timezone = 'UTC';
        }

        $this->format   = (empty($fromFormat)) ? 'Y-m-d H:i:s' : $fromFormat;
        $this->timezone = $timezone;

        $this->utc   = new \DateTimeZone('UTC');
        $this->local = new \DateTimeZone($localTimezone);

        if ($datetime instanceof \DateTimeInterface) {
            $this->datetime = $datetime;
            $this->timezone = $datetime->getTimezone()->getName();
            $this->string   = $this->datetime->format($fromFormat);
        } elseif (empty($datetime)) {
            $this->datetime = new \DateTime('now', new \DateTimeZone($this->timezone));
            $this->string   = $this->datetime->format($fromFormat);
        } elseif (null == $fromFormat) {
            $this->string   = $datetime;
            $this->datetime = new \DateTime($datetime, new \DateTimeZone($this->timezone));
        } else {
            $this->string = $datetime;

            $this->datetime = \DateTime::createFromFormat(
                $this->format,
                $this->string,
                new \DateTimeZone($this->timezone)
            );

            if (false === $this->datetime) {
                //the format does not match the string so let's attempt to fix that
                $this->string   = date($this->format, strtotime($datetime));
                $this->datetime = \DateTime::createFromFormat(
                    $this->format,
                    $this->string
                );
            }
        }
    }

    /**
     * @param string $format
     *
     * @return string
     */
    public function toUtcString($format = null)
    {
        if ($this->datetime) {
            $utc = ('UTC' == $this->timezone) ? $this->datetime : $this->datetime->setTimezone($this->utc);
            if (empty($format)) {
                $format = $this->format;
            }

            return $utc->format($format);
        }

        return $this->string;
    }

    /**
     * @param string $format
     *
     * @return string
     */
    public function toLocalString($format = null)
    {
        if ($this->datetime) {
            $local = $this->datetime->setTimezone($this->local);
            if (empty($format)) {
                $format = $this->format;
            }

            return $local->format($format);
        }

        return $this->string;
    }

    /**
     * @return \DateTime
     */
    public function getUtcDateTime()
    {
        return $this->datetime->setTimezone($this->utc);
    }

    /**
     * @return \DateTime
     */
    public function getLocalDateTime()
    {
        return $this->datetime->setTimezone($this->local);
    }

    /**
     * @param null $format
     *
     * @return string
     */
    public function getString($format = null)
    {
        if (empty($format)) {
            $format = $this->format;
        }

        return $this->datetime->format($format);
    }

    /**
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->datetime;
    }

    /**
     * @return bool|int
     */
    public function getLocalTimestamp()
    {
        if ($this->datetime) {
            $local = $this->datetime->setTimezone($this->local);

            return $local->getTimestamp();
        }

        return false;
    }

    /**
     * @return bool|int
     */
    public function getUtcTimestamp()
    {
        if ($this->datetime) {
            $utc = $this->datetime->setTimezone($this->utc);

            return $utc->getTimestamp();
        }

        return false;
    }

    /**
     * Gets a difference.
     *
     * @param string     $compare
     * @param null       $format
     * @param bool|false $resetTime
     *
     * @return bool|\DateInterval|string
     */
    public function getDiff($compare = 'now', $format = null, $resetTime = false)
    {
        if ('now' == $compare) {
            $compare = new \DateTime();
        }

        $with = clone $this->datetime;

        if ($resetTime) {
            $compare->setTime(0, 0, 0);
            $with->setTime(0, 0, 0);
        }

        $interval = $compare->diff($with);

        return (null == $format) ? $interval : $interval->format($format);
    }

    /**
     * Add to datetime.
     *
     * @param            $intervalString
     * @param bool|false $clone          If true, return a new \DateTime rather than update current one
     *
     * @return \DateTimeInterface
     */
    public function add($intervalString, $clone = false)
    {
        $interval = new \DateInterval($intervalString);

        if ($clone) {
            $dt = clone $this->datetime;
            $dt->add($interval);

            return $dt;
        } else {
            $this->datetime->add($interval);
        }
    }

    /**
     * Subtract from datetime.
     *
     * @param            $intervalString
     * @param bool|false $clone          If true, return a new \DateTime rather than update current one
     *
     * @return \DateTimeInterface
     */
    public function sub($intervalString, $clone = false)
    {
        $interval = new \DateInterval($intervalString);

        if ($clone) {
            $dt = clone $this->datetime;
            $dt->sub($interval);

            return $dt;
        } else {
            $this->datetime->sub($interval);
        }
    }

    /**
     * Returns interval based on $interval number and $unit.
     *
     * @param int    $interval
     * @param string $unit
     *
     * @return \DateInterval
     *
     * @throws \Exception
     */
    public function buildInterval($interval, $unit)
    {
        $possibleUnits = ['Y', 'M', 'D', 'I', 'H', 'S'];
        $unit          = strtoupper($unit);

        if (!in_array($unit, $possibleUnits)) {
            throw new \InvalidArgumentException($unit.' is invalid unit for DateInterval');
        }

        switch ($unit) {
            case 'I':
                $spec = "PT{$interval}M";
                break;
            case 'H':
            case 'S':
                $spec = "PT{$interval}{$unit}";
                break;
            default:
                $spec = "P{$interval}{$unit}";
        }

        return new \DateInterval($spec);
    }

    /**
     * Modify datetime.
     *
     * @param            $string
     * @param bool|false $clone  If true, return a new \DateTime rather than update current one
     *
     * @return \DateTimeInterface
     */
    public function modify($string, $clone = false)
    {
        if ($clone) {
            $dt = clone $this->datetime;
            $dt->modify($string);

            return $dt;
        } else {
            $this->datetime->modify($string);
        }
    }

    /**
     * Returns today, yesterday, tomorrow or false if before yesterday or after tomorrow.
     *
     * @param $interval
     *
     * @return bool|string
     */
    public function getTextDate($interval = null)
    {
        if (null == $interval) {
            $interval = $this->getDiff('now', null, true);
        }

        $diffDays = (int) $interval->format('%R%a');

        switch ($diffDays) {
            case 0:
                return 'today';
            case -1:
                return 'yesterday';
            case +1:
                return 'tomorrow';
            default:
                return false;
        }
    }

    const TIMEZONES = [
        '-720,0'   => 'Etc/GMT+12',
        '-660,0'   => 'Pacific/Pago_Pago',
        '-660,1,s' => 'Pacific/Apia',
        '-600,1'   => 'America/Adak',
        '-600,0'   => 'Pacific/Honolulu',
        '-570,0'   => 'Pacific/Marquesas',
        '-540,0'   => 'Pacific/Gambier',
        '-540,1'   => 'America/Anchorage',
        '-480,1'   => 'America/Los_Angeles',
        '-480,0'   => 'Pacific/Pitcairn',
        '-420,0'   => 'America/Phoenix',
        '-420,1'   => 'America/Denver',
        '-360,0'   => 'America/Guatemala',
        '-360,1'   => 'America/Chicago',
        '-360,1,s' => 'Pacific/Easter',
        '-300,0'   => 'America/Bogota',
        '-300,1'   => 'America/New_York',
        '-270,0'   => 'America/Caracas',
        '-240,1'   => 'America/Halifax',
        '-240,0'   => 'America/Santo_Domingo',
        '-240,1,s' => 'America/Asuncion',
        '-210,1'   => 'America/St_Johns',
        '-180,1'   => 'America/Godthab',
        '-180,0'   => 'America/Argentina/Buenos_Aires',
        '-180,1,s' => 'America/Montevideo',
        '-120,0'   => 'America/Noronha',
        '-120,1'   => 'America/Noronha',
        '-60,1'    => 'Atlantic/Azores',
        '-60,0'    => 'Atlantic/Cape_Verde',
        '0,0'      => 'UTC',
        '0,1'      => 'Europe/London',
        '60,1'     => 'Europe/Berlin',
        '60,0'     => 'Africa/Lagos',
        '60,1,s'   => 'Africa/Windhoek',
        '120,1'    => 'Asia/Beirut',
        '120,0'    => 'Africa/Johannesburg',
        '180,0'    => 'Asia/Baghdad',
        '180,1'    => 'Europe/Moscow',
        '210,1'    => 'Asia/Tehran',
        '240,0'    => 'Asia/Dubai',
        '240,1'    => 'Asia/Baku',
        '270,0'    => 'Asia/Kabul',
        '300,1'    => 'Asia/Yekaterinburg',
        '300,0'    => 'Asia/Karachi',
        '330,0'    => 'Asia/Kolkata',
        '345,0'    => 'Asia/Kathmandu',
        '360,0'    => 'Asia/Dhaka',
        '360,1'    => 'Asia/Omsk',
        '390,0'    => 'Asia/Rangoon',
        '420,1'    => 'Asia/Krasnoyarsk',
        '420,0'    => 'Asia/Jakarta',
        '480,0'    => 'Asia/Shanghai',
        '480,1'    => 'Asia/Irkutsk',
        '525,0'    => 'Australia/Eucla',
        '525,1,s'  => 'Australia/Eucla',
        '540,1'    => 'Asia/Yakutsk',
        '540,0'    => 'Asia/Tokyo',
        '570,0'    => 'Australia/Darwin',
        '570,1,s'  => 'Australia/Adelaide',
        '600,0'    => 'Australia/Brisbane',
        '600,1'    => 'Asia/Vladivostok',
        '600,1,s'  => 'Australia/Sydney',
        '630,1,s'  => 'Australia/Lord_Howe',
        '660,1'    => 'Asia/Kamchatka',
        '660,0'    => 'Pacific/Noumea',
        '690,0'    => 'Pacific/Norfolk',
        '720,1,s'  => 'Pacific/Auckland',
        '720,0'    => 'Pacific/Majuro',
        '765,1,s'  => 'Pacific/Chatham',
        '780,0'    => 'Pacific/Tongatapu',
        '780,1,s'  => 'Pacific/Apia',
        '840,0'    => 'Pacific/Kiritimati',
    ];

    /**
     * Tries to guess timezone from timezone offset + DST and hemisphere.
     *
     * @param string $key in jstimezonedetect format
     *
     * @return string|null
     */
    public function guessTimezoneFromJSTZ($key)
    {
        return array_key_exists($key, self::TIMEZONES) ? self::TIMEZONES[$key] : null;
    }

    /**
     * Tries to guess timezone from timezone offset.
     *
     * @param int $offset in seconds
     *
     * @return string
     */
    public function guessTimezoneFromOffset($offset = 0)
    {
        // Sanitize input
        $offset = (int) $offset;

        $timezone = timezone_name_from_abbr('', $offset, false);

        // In case http://bugs.php.net/44780 bug happens
        if (empty($timezone)) {
            foreach (timezone_abbreviations_list() as $abbr) {
                foreach ($abbr as $city) {
                    if ($city['offset'] == $offset) {
                        return $city['timezone_id'];
                    }
                }
            }
        }

        return $timezone;
    }

    /**
     * @param string $unit
     *
     * @throws \InvalidArgumentException
     */
    public static function validateMysqlDateTimeUnit($unit)
    {
        $possibleUnits   = ['s', 'i', 'H', 'd', 'W', 'm', 'Y'];

        if (!in_array($unit, $possibleUnits, true)) {
            $possibleUnitsString = implode(', ', $possibleUnits);
            throw new \InvalidArgumentException("Unit '$unit' is not supported. Use one of these: $possibleUnitsString");
        }
    }
}
