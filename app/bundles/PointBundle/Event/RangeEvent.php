<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Entity\Range;

/**
 * Class PointEvent
 *
 * @package Mautic\PointBundle\Event
 */
class RangeEvent extends CommonEvent
{
    /**
     * @param Point $range
     * @param bool $isNew
     */
    public function __construct(Range &$range, $isNew = false)
    {
        $this->entity  =& $range;
        $this->isNew = $isNew;
    }

    /**
     * Returns the Range entity
     *
     * @return Point
     */
    public function getRange()
    {
        return $this->entity;
    }

    /**
     * Sets the Range entity
     *
     * @param Range $range
     */
    public function setRange(Range $range)
    {
        $this->entity = $range;
    }
}