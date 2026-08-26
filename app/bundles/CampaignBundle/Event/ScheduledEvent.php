<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Event as CampaignEvent;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Symfony\Contracts\EventDispatcher\Event;

final class ScheduledEvent extends Event
{
    use ContextTrait;

    /**
     * @var array<int|string, array<string, mixed>>
     */
    private array $eventArray = [];

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var CampaignEvent|null
     */
    private $event;

    private bool $systemTriggered = true;

    /**
     * @var \DateTimeInterface
     */
    private $dateScheduled;

    /**
     * @var array
     */
    private $eventSettings;

    public function __construct(
        private readonly AbstractEventAccessor $eventConfig,
        private readonly LeadEventLog $eventLog,
        private bool $isReschedule = false,
    ) {
        $this->eventSettings   = $eventConfig->getConfig();
        $this->event           = $eventLog->getEvent();
        $this->lead            = $eventLog->getLead();
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

    public function isReschedule(): bool
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
        return ($this->event instanceof CampaignEvent) ? $this->getEventArray($this->event) : $this->event;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->getEvent()['properties'];
    }

    public function getEventDetails(): null
    {
        return null;
    }

    public function getSystemTriggered(): bool
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

    /**
     * Used to convert entities to the old array format; tried to minimize the need for this except where needed.
     *
     * @return array<string, mixed>
     */
    private function getEventArray(CampaignEvent $event): array
    {
        $eventId = $event->getId() ?? '';
        if (isset($this->eventArray[$eventId])) {
            return $this->eventArray[$eventId];
        }

        $eventArray = $event->convertToArray();
        $campaign   = $event->getCampaign();

        $eventArray['campaign'] = [
            'id'        => $campaign->getId(),
            'name'      => $campaign->getName(),
            'createdBy' => $campaign->getCreatedBy(),
        ];

        $eventArray['parent'] = null;
        if ($parent = $event->getParent()) {
            $eventArray['parent']             = $parent->convertToArray();
            $eventArray['parent']['campaign'] = $eventArray['campaign'];
        }

        $eventArray['children'] = [];
        if ($children = $event->getChildren()) {
            /** @var CampaignEvent $child */
            foreach ($children as $child) {
                $childArray             = $child->convertToArray();
                $childArray['parent']   =&$eventArray;
                $childArray['campaign'] =&$eventArray['campaign'];
                unset($childArray['children']);

                $eventArray['children'] = $childArray;
            }
        }

        $this->eventArray[$eventId] = $eventArray;

        return $this->eventArray[$eventId];
    }
}
