<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\Collection;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;
use Symfony\Contracts\EventDispatcher\Event;

final class DecisionResultsEvent extends Event
{
    /**
     * @param Collection<int, LeadEventLog> $eventLogs
     */
    public function __construct(
        private readonly AbstractEventAccessor $eventConfig,
        private readonly Collection $eventLogs,
        private readonly EvaluatedContacts $evaluatedContacts,
    ) {
    }

    public function getEventConfig(): AbstractEventAccessor
    {
        return $this->eventConfig;
    }

    /**
     * @return Collection<int, LeadEventLog>
     */
    public function getLogs(): Collection
    {
        return $this->eventLogs;
    }

    public function getEvaluatedContacts(): EvaluatedContacts
    {
        return $this->evaluatedContacts;
    }
}
