<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\PreferenceBuilder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;

final class ChannelPreferences
{
    /**
     * @var ArrayCollection[]
     */
    private array $organizedByPriority = [];

    public function __construct(
        private readonly Event $event,
    ) {
    }

    public function addPriority(int $priority): static
    {
        $priority = (int) $priority;

        $this->organizedByPriority[$priority] ??= new ArrayCollection();

        return $this;
    }

    public function addLog(LeadEventLog $log, int $priority): static
    {
        $priority = (int) $priority;

        $this->addPriority($priority);

        // We have to clone the log to not affect the original assocaited with the MM event itself

        // Clone to remove from Doctrine's ORM memory since we're having to apply a pseudo event
        $log = clone $log;
        $log->setEvent($this->event);

        $this->organizedByPriority[$priority]->set($log->getId(), $log);

        return $this;
    }

    /**
     * Removes a log from all prioritized groups.
     */
    public function removeLog(LeadEventLog $log): static
    {
        foreach ($this->organizedByPriority as $logs) {
            /** @var ArrayCollection<int, LeadEventLog> $logs */
            $logs->remove($log->getId());
        }

        return $this;
    }

    /**
     * @return Collection<int, LeadEventLog>
     */
    public function getLogsByPriority(int $priority): Collection
    {
        $priority = (int) $priority;

        return $this->organizedByPriority[$priority] ?? new ArrayCollection();
    }
}
