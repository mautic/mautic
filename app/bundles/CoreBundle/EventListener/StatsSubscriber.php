<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

final class StatsSubscriber extends CommonStatsSubscriber
{
    public function __construct(
        CorePermissions $security,
        EntityManagerInterface $entityManager,
        AuditLogRepository $auditLogRepository,
        IpAddressRepository $ipAddressRepository,
    ) {
        parent::__construct($security, $entityManager);
        $this->repositories['MauticCoreBundle:AuditLog'] = $auditLogRepository;
        $this->permissions['MauticCoreBundle:AuditLog']  = ['admin'];

        $this->repositories['MauticCoreBundle:IpAddress'] = $ipAddressRepository;
    }
}
