<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Order;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;

class ObjectMappingsDAO
{
    /**
     * @var ObjectMapping[]
     */
    private array $updatedMappings = [];

    /**
     * @var ObjectMapping[]
     */
    private array $newMappings = [];

    public function addUpdatedObjectMapping(ObjectMapping $objectMapping): void
    {
        $this->updatedMappings[] = $objectMapping;
    }

    public function addNewObjectMapping(ObjectMapping $objectMapping): void
    {
        $this->newMappings[] = $objectMapping;
    }

    /**
     * @return ObjectMapping[]
     */
    public function getUpdatedMappings(): array
    {
        return $this->updatedMappings;
    }

    /**
     * @return ObjectMapping[]
     */
    public function getNewMappings(): array
    {
        return $this->newMappings;
    }
}
