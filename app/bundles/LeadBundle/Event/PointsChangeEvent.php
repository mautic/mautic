<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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

    /**
     * @param Lead $lead
     * @param bool $isNew
     */
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
