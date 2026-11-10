<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Deduplicate\Exception;

final class ValueNotMergeableException extends \Exception
{
    public function __construct(
        private readonly mixed $newerValue,
        private readonly mixed $olderValue,
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
