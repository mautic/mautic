<?php

namespace Mautic\CoreBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;
use Mautic\CoreBundle\Helper\DateTimeHelper;

class UTCDateTimeType extends DateTimeType
{
    private static ?\DateTimeZone $utc = null;

    /**
     * @param \DateTime $value
     *
     * @return string|null
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
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
     * @param mixed $value
     *
     * @return \DateTimeInterface|null
     *
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
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
        if ($value instanceof \DateTime) {
            $value->setTimezone(new \DateTimeZone($timezone));
        }

        return $value;
    }
}
