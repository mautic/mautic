<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Deduplicate\Exception;

final class ValueNotMergeableException extends \Exception
{
    /**
     * @param \DateTimeInterface|mixed[]|string|int|float|bool|null $newerValue
     * @param \DateTimeInterface|mixed[]|string|int|float|bool|null $olderValue
     */
    public function __construct(
        private readonly \DateTimeInterface|array|string|int|float|bool|null $newerValue,
        private readonly \DateTimeInterface|array|string|int|float|bool|null $olderValue,
    ) {
        parent::__construct();
    }

    public function getNewerValue(): \DateTimeInterface|string|int|float|bool|array|null
    {
        return $this->newerValue;
    }

    public function getOlderValue(): \DateTimeInterface|string|int|float|bool|array|null
    {
        return $this->olderValue;
    }
}
