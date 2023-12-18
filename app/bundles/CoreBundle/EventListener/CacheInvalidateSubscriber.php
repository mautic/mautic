<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Mautic\CoreBundle\Cache\ResultCacheHelper;
use Mautic\CoreBundle\Entity\CacheInvalidateInterface;
use Mautic\CoreBundle\Entity\FormEntity;

class CacheInvalidateSubscriber implements EventSubscriber
{
    private const ACTION_PERSIST = 'persist';
    private const ACTION_UPDATE  = 'update';
    private const ACTION_REMOVE  = 'remove';

    public function __construct(private Configuration $ormConfiguration)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->invalidateCache($args, self::ACTION_PERSIST);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->invalidateCache($args, self::ACTION_UPDATE);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->invalidateCache($args, self::ACTION_REMOVE);
    }

    private function invalidateCache(LifecycleEventArgs $args, string $action): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof CacheInvalidateInterface) {
            return;
        }

        if (self::ACTION_UPDATE === $action && $entity instanceof FormEntity && !$this->isEntityChanged($entity)) {
            return;
        }

        $namespacesToDelete = $entity->getCacheNamespacesToDelete();

        if (!$namespacesToDelete) {
            return;
        }

        $cache = ResultCacheHelper::getCache($this->ormConfiguration);

        if (!$cache) {
            return;
        }

        $cache = clone $cache;

        foreach ($namespacesToDelete as $namespace) {
            $cache->setNamespace($namespace);
            $cache->deleteAll();
        }
    }

    private function isEntityChanged(FormEntity $entity): bool
    {
        $changes = $entity->getChanges(true);

        return (bool) $changes;
    }
}
