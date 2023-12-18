<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Request;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;

class RequestDAO
{
    private int $syncIteration;

    /**
     * @var ObjectDAO[]
     */
    private array $objects = [];

    public function __construct(
        private string $syncToIntegration,
        int $syncIteration,
        private InputOptionsDAO $inputOptionsDAO
    ) {
        $this->syncIteration     = (int) $syncIteration;
    }

    /**
     * @return self
     */
    public function addObject(ObjectDAO $objectDAO)
    {
        $this->objects[] = $objectDAO;

        return $this;
    }

    /**
     * @return ObjectDAO[]
     */
    public function getObjects(): array
    {
        return $this->objects;
    }

    public function getSyncIteration(): int
    {
        return $this->syncIteration;
    }

    public function isFirstTimeSync(): bool
    {
        return $this->inputOptionsDAO->isFirstTimeSync();
    }

    /**
     * The integration that will be synced to.
     */
    public function getSyncToIntegration(): string
    {
        return $this->syncToIntegration;
    }

    /**
     * Returns DAO object with all input options.
     */
    public function getInputOptionsDAO(): InputOptionsDAO
    {
        return $this->inputOptionsDAO;
    }

    /**
     * Returns true if there are objects to sync.
     */
    public function shouldSync(): bool
    {
        return !empty($this->objects);
    }
}
