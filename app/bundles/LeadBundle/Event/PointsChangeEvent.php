<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

final class PointsChangeEvent extends CommonEvent
{
    private readonly int $old;

    private readonly int $new;

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
