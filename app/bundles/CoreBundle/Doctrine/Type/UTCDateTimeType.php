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
        } else {
            $value = clone $value;
        }

        $value->setTimezone(self::$utc);

        return parent::convertToDatabaseValue($value, $platform);
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

        // Set to UTC before converting to DateTime
        $timezone = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $value = parent::convertToPHPValue($value, $platform);

        // Set to local timezone
        date_default_timezone_set($timezone);
        $value->setTimezone(new \DateTimeZone($timezone));

        return $value;
    }
}
