<?php

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class LeadEvent.
 */
class LeadMergeEvent extends Event
{
    private $victor;

    private $loser;

    public function __construct(Lead $victor, Lead $loser)
    {
        $this->victor = $victor;
        $this->loser  = $loser;
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
