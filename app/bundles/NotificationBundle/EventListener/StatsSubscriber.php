<?php

namespace Mautic\NotificationBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\NotificationBundle\Entity\Stat;

class StatsSubscriber extends CommonStatsSubscriber
{
    public function __construct(CorePermissions $security, EntityManager $entityManager)
    {
        parent::__construct($security, $entityManager);
        $this->addContactRestrictedRepositories([Stat::class]);
    }
}
