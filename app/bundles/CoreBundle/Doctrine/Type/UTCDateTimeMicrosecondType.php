<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;

final class UTCDateTimeMicrosecondType extends UTCDateTimeType
{
    public const NAME = 'datetime_microsecond';

    protected const FORMAT_SUFFIX = '.u';

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
