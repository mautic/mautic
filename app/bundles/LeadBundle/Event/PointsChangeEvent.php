<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

final class PointsChangeEvent extends CommonEvent
{
    public function __construct(
        Lead &$lead,
        private readonly int $old,
        private readonly int $new
    )
    {
        $this->entity = &$lead;
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
     */
    public function getNewPoints(): int
    {
        return $this->new;
    }

    /**
     * Returns the old points.
     */
    public function getOldPoints(): int
    {
        return $this->old;
    }
}
