<?php

namespace Mautic\LeadBundle\Deduplicate\Exception;

class ValueNotMergeableException extends \Exception
{
    /**
     * @param mixed $newerValue
     * @param mixed $olderValue
     */
    public function __construct(
        private $newerValue,
        private $olderValue
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
