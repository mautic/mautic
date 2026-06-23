<?php

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

class LeadMergeEvent extends Event
{
    public function __construct(
        private Lead $victor,
        private Lead $loser,
    ) {
    }

    /**
     * Returns the victor (loser merges into the victor).
     */
    public function getVictor(): \Mautic\LeadBundle\Entity\Lead
    {
        return $this->victor;
    }

    /**
     * Returns the loser (loser merges into the victor).
     */
    public function getLoser(): \Mautic\LeadBundle\Entity\Lead
    {
        return $this->loser;
    }
}
