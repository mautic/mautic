<?php

namespace Mautic\CoreBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

class StatsSubscriber extends CommonStatsSubscriber
{
    public function __construct(CorePermissions $security, EntityManager $entityManager)
    {
        parent::__construct($security, $entityManager);
        $this->repositories['MauticCoreBundle:AuditLog'] = $entityManager->getRepository(AuditLog::class);
        $this->permissions['MauticCoreBundle:AuditLog']  = ['admin'];

        $this->repositories['MauticCoreBundle:IpAddress'] = $entityManager->getRepository(IpAddress::class);
    }
}
