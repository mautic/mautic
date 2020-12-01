<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
