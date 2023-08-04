<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Mapping;

class RemappedObjectDAO
{
    /**
     * @var string
     */
    private $integration;

    /**
     * @var mixed
     */
    private $oldObjectId;

    /**
     * @var string
     */
    private $oldObjectName;

    /**
     * @var string
     */
    private $newObjectName;

    /**
     * @var mixed
     */
    private $newObjectId;

    /**
     * @param mixed $oldObjectId
     * @param mixed $newObjectId
     */
    public function __construct(string $integration, string $oldObjectName, $oldObjectId, string $newObjectName, $newObjectId)
    {
        $this->integration   = $integration;
        $this->oldObjectName = $oldObjectName;
        $this->oldObjectId   = $oldObjectId;
        $this->newObjectName = $newObjectName;
        $this->newObjectId   = $newObjectId;
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
