<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\Point;

/**
 * Class PointActionEvent.
 */
class PointActionEvent extends CommonEvent
{
    /**
     * @var Point
     */
    protected $point;

    /**
     * @var Lead
     */
    protected $lead;

    /**
     * @param Point $point
     * @param Lead  $lead
     */
    public function __construct(Point &$point, Lead &$lead)
    {
        $this->point = $point;
        $this->lead  = $lead;
    }

    /**
     * Returns the Point entity.
     *
     * @return Point
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * Sets the Point entity.
     *
     * @param Point $point
     */
    public function setPoint(Point $point)
    {
        $this->point = $point;
    }

    /**
     * Returns the Lead entity.
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * Sets the Lead entity.
     *
     * @param $lead
     */
    public function setLead($lead)
    {
        $this->lead = $lead;
    }
}
