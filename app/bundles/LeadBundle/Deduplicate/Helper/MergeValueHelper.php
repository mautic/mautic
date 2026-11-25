<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Deduplicate\Helper;

use Mautic\LeadBundle\Deduplicate\Exception\ValueNotMergeableException;

final class MergeValueHelper
{
    /**
     * @param \DateTimeInterface|mixed[]|string|int|float|bool|null $newerValue
     * @param \DateTimeInterface|mixed[]|string|int|float|bool|null $olderValue
     * @param \DateTimeInterface|mixed[]|string|int|float|bool|null $currentValue
     * @param \DateTimeInterface|mixed[]|string|int|float|bool|null $defaultValue
     *
     * @return mixed
     *
     * @throws ValueNotMergeableException
     */
    public static function getMergeValue(\DateTimeInterface|array|string|int|float|bool|null $newerValue, \DateTimeInterface|array|string|int|float|bool|null $olderValue, \DateTimeInterface|array|string|int|float|bool|null $currentValue = null, \DateTimeInterface|array|string|int|float|bool|null $defaultValue = null, bool $newIsAnonymous = false)
    {
        if ($newerValue === $olderValue) {
            throw new ValueNotMergeableException($newerValue, $olderValue);
        }

        if (null !== $currentValue && $newerValue === $currentValue) {
            throw new ValueNotMergeableException($newerValue, $olderValue);
        }

        $isDefaultValue = null !== $defaultValue && $newerValue === $defaultValue;

        if (self::isNotEmpty($newerValue) && (!$newIsAnonymous || !$isDefaultValue)) {
            return $newerValue;
        }

        if (self::isNotEmpty($olderValue)) {
            return $olderValue;
        }

        throw new ValueNotMergeableException($newerValue, $olderValue);
    }

    public static function isNotEmpty($value): bool
    {
        return null !== $value && '' !== $value;
    }
}
