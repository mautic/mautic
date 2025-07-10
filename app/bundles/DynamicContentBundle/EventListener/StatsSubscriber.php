<?php

namespace Mautic\DynamicContentBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DynamicContentBundle\Entity\DynamicContentLeadData;
use Mautic\DynamicContentBundle\Entity\Stat;

class StatsSubscriber extends CommonStatsSubscriber
{
    public function __construct(CorePermissions $security, EntityManager $entityManager)
    {
        parent::__construct($security, $entityManager);
        $this->addContactRestrictedRepositories(
            [
                Stat::class,
                DynamicContentLeadData::class,
            ]
        );
    }
}
