<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CampaignScheduledEvent.
 *
 * @deprecated 2.13.0; to be removed in 3.0
 */
class CampaignScheduledEvent extends Event
{
    use EventArrayTrait;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    protected $lead;

    /**
     * @var array
     */
    protected $event;

    /**
     * @var array
     */
    protected $eventDetails;

    /**
     * @var bool
     */
    protected $systemTriggered;

    /**
     * @var \DateTime
     */
    protected $dateScheduled;

    /**
     * @var array
     */
    protected $eventSettings;

    /**
     * @var LeadEventLog
     */
    protected $log;

    /**
     * CampaignScheduledEvent constructor.
     *
     * @param                   $args
     * @param LeadEventLog|null $log
     */
    public function __construct(array $args, LeadEventLog $log = null)
    {
        $this->lead            = $args['lead'];
        $this->event           = $args['event'];
        $this->eventDetails    = $args['eventDetails'];
        $this->systemTriggered = $args['systemTriggered'];
        $this->dateScheduled   = $args['dateScheduled'];
        $this->eventSettings   = $args['eventSettings'];

        $this->log = $log;
    }

    /**
     * @return \Mautic\LeadBundle\Entity\Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return array
     */
    public function getEvent()
    {
        return ($this->event instanceof \Mautic\CampaignBundle\Entity\Event) ? $this->getEventArray($this->event) : $this->event;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->getEvent()['properties'];
    }

    /**
     * @return array
     */
    public function getEventDetails()
    {
        return $this->eventDetails;
    }

    /**
     * @return bool
     */
    public function getSystemTriggered()
    {
        return $this->systemTriggered;
    }

    /**
     * @return \DateTime
     */
    public function getDateScheduled()
    {
        return $this->dateScheduled;
    }

    /**
     * @return mixed
     */
    public function getEventSettings()
    {
        return $this->eventSettings;
    }

    /**
     * @return LeadEventLog|null
     */
    public function getLog()
    {
        return $this->log;
    }
}
