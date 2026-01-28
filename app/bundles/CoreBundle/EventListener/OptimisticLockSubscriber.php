<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Mautic\CoreBundle\Entity\OptimisticLockInterface;
use Mautic\CoreBundle\Service\OptimisticLockServiceInterface;

#[AsDoctrineListener(Events::postUpdate)]
class OptimisticLockSubscriber
{
    public function __construct(private OptimisticLockServiceInterface $optimisticLockService)
    {
    }

    /**
     * If the object implements OptimisticLockInterface and is marked for incrementing the version, object's version column/field is incremented.
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();

        if (!$object instanceof OptimisticLockInterface || !$object->isMarkedForVersionIncrement()) {
            return;
        }

        $this->optimisticLockService->incrementVersion($object);
    }
}
