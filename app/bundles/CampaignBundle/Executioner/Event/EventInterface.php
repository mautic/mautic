<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Executioner\Event;

use Doctrine\Common\Collections\Collection;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;

interface EventInterface
{
    /**
     * @param Collection<int, LeadEventLog> $logs
     *
     * @return EvaluatedContacts
     */
    public function execute(AbstractEventAccessor $config, Collection $logs);
}
