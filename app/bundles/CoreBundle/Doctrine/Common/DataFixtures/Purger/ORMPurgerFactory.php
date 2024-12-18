<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Common\DataFixtures\Purger;

use Doctrine\Bundle\FixturesBundle\Purger\PurgerFactory;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Doctrine\Common\DataFixtures\Event\PreExecuteEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ORMPurgerFactory implements PurgerFactory
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function createForEntityManager(
        ?string $emName,
        EntityManagerInterface $em,
        array $excluded = [],
        bool $purgeWithTruncate = false,
    ): PurgerInterface {
        $this->eventDispatcher->dispatch(
            new PreExecuteEvent(
                $em,
                $purgeWithTruncate ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE
            )
        );

        $purger = new ORMPurger($em, $excluded);
        $purger->setPurgeMode($purgeWithTruncate ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);

        return $purger;
    }
}
