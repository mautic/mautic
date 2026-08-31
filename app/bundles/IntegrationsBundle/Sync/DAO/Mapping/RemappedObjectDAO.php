<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Mapping;

final readonly class RemappedObjectDAO
{
    public function __construct(
        private string $integration,
        private string $oldObjectName,
        private string|int|null $oldObjectId,
        private string $newObjectName,
        private string|int|null $newObjectId,
    ) {
    }

    public function getIntegration(): string
    {
        return $this->integration;
    }

    public function getOldObjectName(): string
    {
        return $this->oldObjectName;
    }

    public function getOldObjectId(): string|int|null
    {
        return $this->oldObjectId;
    }

    public function getNewObjectName(): string
    {
        return $this->newObjectName;
    }

    public function getNewObjectId(): string|int|null
    {
        return $this->newObjectId;
    }
}
