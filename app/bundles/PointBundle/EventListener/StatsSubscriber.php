<?php

namespace Mautic\PointBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PointBundle\Entity\LeadPointLog;
use Mautic\PointBundle\Entity\LeadTriggerLog;

class StatsSubscriber extends CommonStatsSubscriber
{
    public function __construct(CorePermissions $security, EntityManager $entityManager)
    {
        parent::__construct($security, $entityManager);
        $this->addContactRestrictedRepositories(
            [
                LeadPointLog::class,
                LeadTriggerLog::class,
            ]
        );
    }
}
