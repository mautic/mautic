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

    /**
     * @return mixed
     */
    public function getNewerValue()
    {
        return $this->newerValue;
    }

    /**
     * @return mixed
     */
    public function getOlderValue()
    {
        return $this->olderValue;
    }
}
