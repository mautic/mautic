<?php

namespace MauticPlugin\MauticSocialBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use MauticPlugin\MauticSocialBundle\Entity\Monitoring;

final class SocialMonitorEvent extends CommonEvent
{
    private readonly int $newLeadCount;

    private readonly int $updatedLeadCount;

    /**
     * @param string $integrationName
     * @param int    $newLeadCount
     * @param int    $updatedLeadCount
     */
    public function __construct(
        private $integrationName,
        Monitoring $monitoring,
        private readonly array $leadIds,
        $newLeadCount,
        $updatedLeadCount,
    ) {
        $this->entity           = $monitoring;
        $this->newLeadCount     = (int) $newLeadCount;
        $this->updatedLeadCount = (int) $updatedLeadCount;
    }

    /**
     * Returns the Monitoring entity.
     *
     * @return Monitoring
     */
    public function getMonitoring()
    {
        return $this->entity;
    }

    /**
     * Get count of new leads.
     */
    public function getNewLeadCount(): int
    {
        return $this->newLeadCount;
    }

    /**
     * Get count of updated leads.
     */
    public function getUpdatedLeadCount(): int
    {
        return $this->updatedLeadCount;
    }

    public function getTotalLeadCount(): int
    {
        return $this->updatedLeadCount + $this->newLeadCount;
    }

    public function getLeadIds(): array
    {
        return $this->leadIds;
    }

    /**
     * @return mixed
     */
    public function getIntegrationName()
    {
        return $this->integrationName;
    }
}
