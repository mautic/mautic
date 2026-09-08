<?php

declare(strict_types=1);

namespace Mautic\PageBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\RedirectRepository;
use Mautic\PageBundle\Entity\TrackableRepository;
use Mautic\PageBundle\Entity\VideoHit;

final class StatsSubscriber extends CommonStatsSubscriber
{
    public function __construct(
        CorePermissions $security,
        EntityManagerInterface $entityManager,
        RedirectRepository $redirectRepository,
        TrackableRepository $trackableRepository,
    ) {
        parent::__construct($security, $entityManager);
        $this->addContactRestrictedRepositories(
            [
                Hit::class,
                VideoHit::class,
            ]
        );

        $this->repositories[] = $redirectRepository;
        $this->repositories[] = $trackableRepository;
    }
}
