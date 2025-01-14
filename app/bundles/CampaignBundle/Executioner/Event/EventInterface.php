<?php

namespace Mautic\CampaignBundle\Executioner\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;

interface EventInterface
{
    /**
     * @return EvaluatedContacts
     */
    public function execute(AbstractEventAccessor $config, ArrayCollection $logs);
}
