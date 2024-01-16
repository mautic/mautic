<?php

namespace Mautic\LeadBundle\Deduplicate\Exception;

class ValueNotMergeableException extends \Exception
{
    public function __construct(
        private $newerValue,
        private $olderValue
    ) {
        parent::__construct();
    }

    public function getNewerValue()
    {
        return $this->newerValue;
    }

    public function getOlderValue()
    {
        return $this->olderValue;
    }
}
