<?php

namespace Mautic\PointBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PointBundle\Entity\LeadPointLog;
use Mautic\PointBundle\Entity\LeadTriggerLog;

final class StatsSubscriber extends CommonStatsSubscriber
{
    public function __construct(CorePermissions $security, EntityManagerInterface $entityManager)
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
