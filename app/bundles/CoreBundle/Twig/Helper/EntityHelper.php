<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EntityHelper extends AbstractExtension
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Registers the custom Twig functions.
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getEntity', [$this, 'getEntity']),
            new TwigFunction('getEntities', [$this, 'getEntities']),
        ];
    }

    /**
     * Retrieves an entity based on the provided class name and ID.
     *
     * @param class-string    $entityName The fully qualified class name of the entity
     * @param int|string|null $id         The ID of the entity to retrieve
     *
     * @return object|null The retrieved entity or null if not found
     */
    public function getEntity(string $entityName, int|string|null $id): ?object
    {
        return null !== $id ? $this->entityManager->getRepository($entityName)->find($id) : null;
    }

    /**
     * Retrieves multiple entities based on the provided class name and an array of IDs.
     *
     * @param class-string   $entityName The fully qualified class name of the entity
     * @param int[]|string[] $ids        An array of IDs to retrieve
     *
     * @return object[] The array of retrieved entities
     */
    public function getEntities(string $entityName, array $ids): array
    {
        return $this->entityManager->getRepository($entityName)->findBy(['id' => $ids]);
    }
}
