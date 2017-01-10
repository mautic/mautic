<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class UTCDateTimeType.
 */
class UTCDateTimeType extends DateTimeType
{
    /**
     * @var \DateTimeZone
     */
    private static $utc;

    /**
     * @param \DateTime        $value
     * @param AbstractPlatform $platform
     *
     * @return string|null
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (!self::$utc) {
            self::$utc = new \DateTimeZone('UTC');
        }

        if (!is_object($value)) {
            $dateHelper = new DateTimeHelper($value);
            $value      = $dateHelper->getDateTime();
        }

        $tz          = $value->getTimeZone();
        $utcDatetime = new \DateTime($value->format($platform->getDateTimeFormatString()), $tz);
        $utcDatetime->setTimezone(self::$utc);

        return $utcDatetime->format($platform->getDateTimeFormatString());
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     *
     * @return \DateTime|null
     *
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (!self::$utc) {
            self::$utc = new \DateTimeZone('UTC');
        }

        $val = \DateTime::createFromFormat(
            $platform->getDateTimeFormatString(),
            $value,
            self::$utc
        );
        if (!$val) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        //set to local timezone
        $tz = new \DateTimeZone(date_default_timezone_get());
        $val->setTimezone($tz);

        return $val;
    }
}
