<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Event;

use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class ApiInitializeEvent extends Event
{
    /**
     * @param string[]                     $serializerGroups
     * @param ExclusionStrategyInterface[] $exclusionStrategies
     */
    public function __construct(
        private string $entityClass,
        private array $serializerGroups,
        private array $exclusionStrategies,
    ) {
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @return string[]
     */
    public function getSerializerGroups(): array
    {
        return $this->serializerGroups;
    }

    public function addSerializerGroup(string $serializerGroup): void
    {
        $this->serializerGroups[] = $serializerGroup;
    }

    /**
     * @return ExclusionStrategyInterface[]
     */
    public function getExclusionStrategies(): array
    {
        return $this->exclusionStrategies;
    }

    public function addExclusionStrategy(ExclusionStrategyInterface $exclusionStrategy): void
    {
        $this->exclusionStrategies[] = $exclusionStrategy;
    }
}
