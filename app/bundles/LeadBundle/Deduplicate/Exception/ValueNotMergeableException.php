<?php

namespace Mautic\LeadBundle\Deduplicate\Exception;

class ValueNotMergeableException extends \Exception
{
    /**
     * ValueNotMergeableException constructor.
     */
    public function __construct(private mixed $newerValue, private mixed $olderValue)
    {
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
