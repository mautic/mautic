<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\BooleanType as BaseBooleanType;

final class BooleanType extends BaseBooleanType
{
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ('' === $value) {
            $value = false;
        }

        return parent::convertToDatabaseValue($value, $platform);
    }
}
