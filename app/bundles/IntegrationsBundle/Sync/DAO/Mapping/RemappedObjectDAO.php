<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Mapping;

class RemappedObjectDAO
{
    public function __construct(private string $integration, private string $oldObjectName, private mixed $oldObjectId, private string $newObjectName, private mixed $newObjectId)
    {
    }

    public function getIntegration(): string
    {
        return $this->integration;
    }

    public function getOldObjectName(): string
    {
        return $this->oldObjectName;
    }

    /**
     * @return mixed
     */
    public function getOldObjectId()
    {
        return $this->oldObjectId;
    }

    public function getNewObjectName(): string
    {
        return $this->newObjectName;
    }

    /**
     * @return mixed
     */
    public function getNewObjectId()
    {
        return $this->newObjectId;
    }
}
