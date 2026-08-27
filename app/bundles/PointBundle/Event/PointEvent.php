<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PointBundle\Entity\Point;

final class PointEvent extends CommonEvent
{
    public function __construct(Point &$point, bool $isNew = false)
    {
        $this->entity = &$point;
        $this->isNew  = $isNew;
    }

    /**
     * @return Point
     */
    public function getPoint()
    {
        return $this->entity;
    }

    public function setPoint(Point $point): void
    {
        $this->entity = $point;
    }
}
