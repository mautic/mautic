<?php

namespace Mautic\PageBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Entity\VideoHit;

class StatsSubscriber extends CommonStatsSubscriber
{
    public function __construct(CorePermissions $security, EntityManager $entityManager)
    {
        parent::__construct($security, $entityManager);
        $this->addContactRestrictedRepositories(
            [
                Hit::class,
                VideoHit::class,
            ]
        );

        $this->repositories[] = $entityManager->getRepository(Redirect::class);
        $this->repositories[] = $entityManager->getRepository(Trackable::class);
    }
}
