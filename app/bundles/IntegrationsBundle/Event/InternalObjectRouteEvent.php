<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ObjectInterface;
use Symfony\Contracts\EventDispatcher\Event;

class InternalObjectRouteEvent extends Event
{
    private ?string $route = null;

    public function __construct(
        private ObjectInterface $object,
        private int $id
    ) {
    }

    public function getObject(): ObjectInterface
    {
        return $this->object;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): void
    {
        $this->route = $route;
    }
}
