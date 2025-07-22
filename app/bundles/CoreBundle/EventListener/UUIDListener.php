<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Mautic\CoreBundle\Entity\UuidInterface;
use Ramsey\Uuid\Uuid;

class UUIDListener implements EventSubscriber
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @return mixed[]
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
        ];
    }

    /**
     * Populate `uuid` column in `prePersist` event if the Entity is desired to have a uuid.
     *
     * @throws \Exception
     */
    public function prePersist(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();

        if (false === $object instanceof UuidInterface) {
            return;
        }

        $this->updateDuplicateUUID($object);

        if (empty($object->getUuid())) {
            $object->setUuid((string) Uuid::uuid4());
        }
    }

    /**
     * Resets the UUID of an entity if that entity is new.
     *
     * @throws \Exception
     */
    private function updateDuplicateUUID(object $object): void
    {
        if (empty($object->getUuid()) || (method_exists($object::class, 'getId') && null !== $object->getId())) {
            return;
        }

        $entityExists = $this->em->getRepository($object::class)->findBy([
            'uuid' => $object->getUuid(),
        ]);

        if ($entityExists) {
            $object->setUuid((string) Uuid::uuid4());
        }
    }
}
