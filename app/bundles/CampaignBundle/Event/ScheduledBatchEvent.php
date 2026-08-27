<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

final class ScheduledBatchEvent extends AbstractLogCollectionEvent
{
    public function __construct(
        AbstractEventAccessor $config,
        Event $event,
        ArrayCollection $logs,
        private bool $isReschedule = false,
    ) {
        parent::__construct($config, $event, $logs);
    }

    /**
     * @return ArrayCollection
     */
    public function getScheduled()
    {
        return $this->logs;
    }

    /**
     * @return bool
     */
    public function isReschedule()
    {
        return $this->isReschedule;
    }
}
