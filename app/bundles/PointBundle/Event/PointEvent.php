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

/**
 * Class PointEvent
 *
 * @package Mautic\PointBundle\Event
 */
class PointEvent extends CommonEvent
{
    /**
     * @param Point $form
     * @param bool $isNew
     */
    public function __construct(Point &$form, $isNew = false)
    {
        $this->entity  =& $form;
        $this->isNew = $isNew;
    }

    /**
     * Returns the Point entity
     *
     * @return Point
     */
    public function getPoint()
    {
        return $this->entity;
    }

    /**
     * Sets the Point entity
     *
     * @param Point $form
     */
    public function setPoint(Point $form)
    {
        $this->entity = $form;
    }
}