<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;

class UTCDateTimeType extends DateTimeType
{

    private static $utc;

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (!self::$utc)
            self::$utc = new \DateTimeZone('UTC');

        $tz = $value->getTimeZone();
        $utcDatetime = new \Datetime($value->format($platform->getDateTimeFormatString()), $tz);
        $utcDatetime->setTimezone(self::$utc);

        return $utcDatetime->format($platform->getDateTimeFormatString());
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (!self::$utc)
            self::$utc = new \DateTimeZone('UTC');

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