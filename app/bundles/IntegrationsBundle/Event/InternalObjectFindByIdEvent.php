<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ObjectInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class InternalObjectFindByIdEvent extends Event
{
    private ?int $id = null;

    private ?object $entity = null;

    public function __construct(
        private ObjectInterface $object
    ) {
    }

    public function getObject(): ObjectInterface
    {
        return $this->object;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getEntity(): ?object
    {
        return $this->entity;
    }

    public function setEntity(object $entity): void
    {
        $this->entity = $entity;
    }
}
