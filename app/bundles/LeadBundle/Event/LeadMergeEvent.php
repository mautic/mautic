<?php

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

class LeadMergeEvent extends Event
{
    public function __construct(
        private readonly Lead $victor,
        private readonly Lead $loser,
    ) {
    }

    /**
     * Returns the victor (loser merges into the victor).
     */
    public function getVictor(): Lead
    {
        return $this->victor;
    }

    /**
     * Returns the loser (loser merges into the victor).
     */
    public function getLoser(): Lead
    {
        return $this->loser;
    }
}
