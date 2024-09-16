<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class PointsChangeEvent.
 */
class PointsChangeEvent extends CommonEvent
{
    protected $old;
    protected $new;

    public function __construct(Lead &$lead, $old, $new)
    {
        $this->entity = &$lead;
        $this->old    = (int) $old;
        $this->new    = (int) $new;
    }

    /**
     * Returns the Lead entity.
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->entity;
    }

    /**
     * Returns the new points.
     *
     * @return int
     */
    public function getNewPoints()
    {
        return $this->new;
    }

    /**
     * Returns the old points.
     *
     * @return int
     */
    public function getOldPoints()
    {
        return $this->old;
    }
}
