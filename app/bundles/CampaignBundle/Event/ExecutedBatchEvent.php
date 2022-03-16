<?php

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;

class ExecutedBatchEvent extends AbstractLogCollectionEvent
{
    /**
     * @return ArrayCollection
     */
    public function getExecuted()
    {
        return $this->logs;
    }
}
