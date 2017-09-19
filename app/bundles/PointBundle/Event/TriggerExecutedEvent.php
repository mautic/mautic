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

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\TriggerEvent as TriggerEventEntity;
use Symfony\Component\EventDispatcher\Event;

class TriggerExecutedEvent extends Event
{
    /** @var TriggerEventEntity */
    private $triggerEvent;

    /** @var Lead */
    private $lead;

    public function __construct(TriggerEventEntity $triggerEvent, Lead $lead)
    {
        $this->triggerEvent = $triggerEvent;
        $this->lead = $lead;
    }

    /**
     * @return TriggerEventEntity
     */
    public function getTriggerEvent()
    {
        return $this->triggerEvent;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }
}
