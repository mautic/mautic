<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Symfony\Contracts\EventDispatcher\Event;

final class ScheduledEvent extends Event
{
    use ContextTrait;
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
     * @var array|null
     */
    protected $eventDetails;

    protected bool $systemTriggered;

    /**
     * @var \DateTimeInterface
     */
    protected $dateScheduled;

    /**
     * @var array
     */
    protected $eventSettings;

    /**
     * @param bool $isReschedule
     */
    public function __construct(
        private AbstractEventAccessor $eventConfig,
        private LeadEventLog $eventLog,
        private $isReschedule = false,
    ) {
        $this->eventSettings   = $eventConfig->getConfig();
        $this->eventDetails    = null;
        $this->event           = $eventLog->getEvent();
        $this->lead            = $eventLog->getLead();
        $this->systemTriggered = true;
        $this->dateScheduled   = $eventLog->getTriggerDate();
    }

    public function getEventConfig(): AbstractEventAccessor
    {
        return $this->eventConfig;
    }

    public function getLog(): LeadEventLog
    {
        return $this->eventLog;
    }

    /**
     * @return bool
     */
    public function isReschedule()
    {
        return $this->isReschedule;
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
     * @return array|null
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
     * @return \DateTimeInterface
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
}
