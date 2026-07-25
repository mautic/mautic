<?php

namespace Mautic\CoreBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

final class StatsSubscriber extends CommonStatsSubscriber
{
    public function __construct(
        CorePermissions $security,
        EntityManager $entityManager,
        AuditLogRepository $auditLogRepository,
        IpAddressRepository $ipAddressRepository,
    ) {
        parent::__construct($security, $entityManager);
        $this->repositories['MauticCoreBundle:AuditLog'] = $auditLogRepository;
        $this->permissions['MauticCoreBundle:AuditLog']  = ['admin'];

        $this->repositories['MauticCoreBundle:IpAddress'] = $ipAddressRepository;
    }
}
