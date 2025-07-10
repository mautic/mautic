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
     *
     * @return Lead
     */
    public function getVictor()
    {
        return $this->victor;
    }

    /**
     * Returns the loser (loser merges into the victor).
     */
    public function getLoser()
    {
        return $this->loser;
    }
}
