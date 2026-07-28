<?php

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;

final class ExecutedBatchEvent extends AbstractLogCollectionEvent
{
    /**
     * @return ArrayCollection
     */
    public function getExecuted()
    {
        return $this->logs;
    }
}
