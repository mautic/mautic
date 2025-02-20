<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Loader\ParameterLoader;

class DateTimeHelper
{
    public const FORMAT_DB = 'Y-m-d H:i:s';

    private static ?string $defaultLocalTimezone = null;

    /**
     * @var string
     */
    private $string;

    private string $format;

    private ?string $timezone = null;

    private \DateTimeZone $utc;

    private \DateTimeZone $local;

    /**
     * @var \DateTimeInterface
     */
    private $datetime;

    /**
     * @param \DateTimeInterface|string $string
     * @param string                    $fromFormat Format the string is in
     * @param string                    $timezone   Timezone the string is in
     */
    public function __construct($string = '', $fromFormat = self::FORMAT_DB, $timezone = 'UTC')
    {
        $this->setDefaultTimezone();
        $this->setDateTime($string, $fromFormat, $timezone);
    }

    /**
     * @param \DateTimeInterface|string $datetime
     */
    public function setDateTime($datetime = '', ?string $fromFormat = self::FORMAT_DB, string $timezone = 'local'): void
    {
        if ('local' == $timezone) {
            $timezone = self::$defaultLocalTimezone;
        } elseif (empty($timezone)) {
            $timezone = 'UTC';
        }

        $this->format   = (empty($fromFormat)) ? self::FORMAT_DB : $fromFormat;
        $this->timezone = $timezone;

        $this->utc   = new \DateTimeZone('UTC');
        $this->local = new \DateTimeZone(self::$defaultLocalTimezone);

        if ($datetime instanceof \DateTimeInterface) {
            $this->datetime = $datetime;
            $this->timezone = $datetime->getTimezone()->getName();
            $this->string   = $fromFormat ? $this->datetime->format($fromFormat) : $datetime;
        } elseif (empty($datetime)) {
            $this->datetime = new \DateTime('now', new \DateTimeZone($this->timezone));
            $this->string   = $fromFormat ? $this->datetime->format($fromFormat) : $datetime;
        } elseif (null === $fromFormat) {
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
                // the format does not match the string so let's attempt to fix that
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
            $dateTime = clone $this->datetime;
            $utc      = ('UTC' == $this->timezone) ? $dateTime : $dateTime->setTimezone($this->utc);
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
     * @param string|null $format
     */
    public function getString($format = null): string
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
     * @param string|\DateTime $compare
     * @param bool|false       $resetTime
     *
     * @return bool|\DateInterval|string
     */
    public function getDiff($compare = 'now', $format = null, $resetTime = false)
    {
        if ('now' == $compare) {
            $compare = new \DateTime('now', $this->datetime->getTimezone());
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
     * @param bool|false $clone If true, return a new \DateTime rather than update current one
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
     * @param bool|false $clone If true, return a new \DateTime rather than update current one
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
     * @throws \Exception
     */
    public function buildInterval($interval, $unit): \DateInterval
    {
        $possibleUnits = ['Y', 'M', 'D', 'I', 'H', 'S'];
        $unit          = strtoupper($unit);

        if (!in_array($unit, $possibleUnits)) {
            throw new \InvalidArgumentException($unit.' is invalid unit for DateInterval');
        }

        $spec = match ($unit) {
            'I' => "PT{$interval}M",
            'H', 'S' => "PT{$interval}{$unit}",
            default => "P{$interval}{$unit}",
        };

        return new \DateInterval($spec);
    }

    /**
     * Modify datetime.
     *
     * @param bool|false $clone If true, return a new \DateTime rather than update current one
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
     * @return bool|string
     */
    public function getTextDate($interval = null)
    {
        if (null == $interval) {
            $interval = $this->getDiff('now', null, true);
        }

        $diffDays = (int) $interval->format('%R%a');

        return match ($diffDays) {
            0       => 'today',
            -1      => 'yesterday',
            +1      => 'tomorrow',
            default => false,
        };
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

        $timezone = timezone_name_from_abbr('', $offset, 0);

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
    public static function validateMysqlDateTimeUnit($unit): void
    {
        $possibleUnits   = ['s', 'i', 'H', 'd', 'W', 'm', 'Y'];

        if (!in_array($unit, $possibleUnits, true)) {
            $possibleUnitsString = implode(', ', $possibleUnits);
            throw new \InvalidArgumentException("Unit '$unit' is not supported. Use one of these: $possibleUnitsString");
        }
    }

    public function getLocalTimezoneOffset(): string
    {
        return $this->getLocalDateTime()->format('P');
    }

    protected function setDefaultTimezone(): void
    {
        if (null === self::$defaultLocalTimezone) {
            $parameterLoader            = new ParameterLoader();
            self::$defaultLocalTimezone = $parameterLoader->getParameterBag()->get('default_timezone') ?? date_default_timezone_get();
        }
    }
}
