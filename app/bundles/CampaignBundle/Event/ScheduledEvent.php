<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Event as CampaignEvent;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

final class ScheduledEvent extends Event
{
    /**
     * @var array<int|string, array<string, mixed>>
     */
    private array $eventArray = [];

    private readonly ?Lead $lead;

    private readonly ?CampaignEvent $event;

    private bool $systemTriggered = true;

    private readonly ?\DateTimeInterface $dateScheduled;

    public function __construct(
        private readonly AbstractEventAccessor $eventConfig,
        private readonly LeadEventLog $eventLog,
        private readonly bool $isReschedule = false,
    ) {
        $this->event           = $eventLog->getEvent();
        $this->lead            = $eventLog->getLead();
        $this->dateScheduled   = $eventLog->getTriggerDate();
    }

    /**
     * Check if an event is applicable.
     */
    public function checkContext($eventType): bool
    {
        if (!$this->event instanceof CampaignEvent) {
            return false;
        }

        return strtolower((string) $eventType) === strtolower((string) $this->event->getType());
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

    public function getLead(): ?Lead
    {
        return $this->lead;
    }

    public function getEvent(): array
    {
        return ($this->event instanceof CampaignEvent) ? $this->getEventArray($this->event) : $this->event;
    }

    public function getConfig(): array
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

    public function getDateScheduled(): ?\DateTimeInterface
    {
        return $this->dateScheduled;
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
