<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeImmutableType;

class UTCDateTimeImmutableType extends DateTimeImmutableType
{
    /**
     * Persist the date in UTC.
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof \DateTimeImmutable) {
            $value = $value->setTimezone(new \DateTimeZone('UTC'));
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    /**
     * Convert the UTC persisted date to the current timezone (determined by date_default_timezone_get()).
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?\DateTimeImmutable
    {
        $value = parent::convertToPHPValue($value, $platform);

        if (!$value instanceof \DateTimeImmutable) {
            return null;
        }

        $value = new \DateTimeImmutable($value->format($platform->getDateTimeFormatString()), new \DateTimeZone('UTC'));

        return $value->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    }
}
