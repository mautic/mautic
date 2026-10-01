<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\Collection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

final class ScheduledBatchEvent extends AbstractLogCollectionEvent
{
    /**
     * @param Collection<int, LeadEventLog> $logs
     */
    public function __construct(
        AbstractEventAccessor $config,
        Event $event,
        Collection $logs,
        private readonly bool $isReschedule = false,
    ) {
        parent::__construct($config, $event, $logs);
    }

    /**
     * @return Collection<int, LeadEventLog>
     */
    public function getScheduled(): Collection
    {
        return $this->logs;
    }

    public function isReschedule(): bool
    {
        return $this->isReschedule;
    }
}
