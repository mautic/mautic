<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\Collection;
use Mautic\CampaignBundle\Entity\LeadEventLog;

final class ExecutedBatchEvent extends AbstractLogCollectionEvent
{
    /**
     * @return Collection<int, LeadEventLog>
     */
    public function getExecuted(): Collection
    {
        return $this->logs;
    }
}
