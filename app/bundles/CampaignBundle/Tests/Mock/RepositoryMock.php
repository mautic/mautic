<?php

namespace Mautic\CampaignBundle\Tests\Mock;

class RepositoryMock
{
    private $queuedEventCounts = 0;

    private $queuedEvents = [];

    public function saveEntity()
    {
    }

    /**
     * @return int
     */
    public function getQueuedEventsCount()
    {
        return $this->queuedEventCounts;
    }

    /**
     * @param int $queuedEventCounts
     *
     * @return mixed
     */
    public function setQueuedEventsCount($queuedEventCounts)
    {
        return $this->queuedEventCounts = $queuedEventCounts;
    }

    public function getQueuedEvents()
    {
        return $this->queuedEvents;
    }

    public function setQueuedEvents($queuedEvents)
    {
        return $this->queuedEvents = $queuedEvents;
    }

    public function getCampaignActionAndConditionEvents()
    {
        return [];
    }
}
