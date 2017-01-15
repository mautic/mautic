<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PointBundle\Entity\Point;

/**
 * Class PointEvent.
 */
class PointEvent extends CommonEvent
{
    /**
     * @param Point $point
     * @param bool  $isNew
     */
    public function __construct(Point &$point, $isNew = false)
    {
        $this->entity = &$point;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Point entity.
     *
     * @return Point
     */
    public function getPoint()
    {
        return $this->entity;
    }

    /**
     * Sets the Point entity.
     *
     * @param Point $point
     */
    public function setPoint(Point $point)
    {
        $this->entity = $point;
    }
}
