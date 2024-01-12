<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Mapping;

class RemappedObjectDAO
{
    public function __construct(
        private string $integration,
        private string $oldObjectName,
        private $oldObjectId,
        private string $newObjectName,
        private $newObjectId
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

    public function getOldObjectId()
    {
        return $this->oldObjectId;
    }

    public function getNewObjectName(): string
    {
        return $this->newObjectName;
    }

    public function getNewObjectId()
    {
        return $this->newObjectId;
    }
}
