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
 * Class LeadEvent.
 */
class LeadEvent extends CommonEvent
{
    /**
     * @param Lead $lead
     * @param bool $isNew
     */
    public function __construct(Lead &$lead, $isNew = false)
    {
        $this->entity = &$lead;
        $this->isNew  = $isNew;
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
     * Sets the Lead entity.
     *
     * @param Lead $lead
     */
    public function setLead(Lead $lead)
    {
        $this->entity = $lead;
    }
}
