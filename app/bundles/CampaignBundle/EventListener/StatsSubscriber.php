<?php

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Entity\Lead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

class StatsSubscriber extends CommonStatsSubscriber
{
    public function __construct(CorePermissions $security, EntityManager $entityManager)
    {
        parent::__construct($security, $entityManager);
        $this->addContactRestrictedRepositories(
            [
                Lead::class,
                LeadEventLog::class,
            ]
        );
    }
}
