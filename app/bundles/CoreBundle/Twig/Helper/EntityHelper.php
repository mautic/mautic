<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Twig\Attribute\AsTwigFunction;

final readonly class EntityHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Retrieves an entity based on the provided class name and ID.
     *
     * @param class-string    $entityName The fully qualified class name of the entity
     * @param int|string|null $id         The ID of the entity to retrieve
     *
     * @return object|null The retrieved entity or null if not found
     */
    #[AsTwigFunction(name: 'getEntity')]
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
    #[AsTwigFunction(name: 'getEntities')]
    public function getEntities(string $entityName, array $ids): array
    {
        return $this->entityManager->getRepository($entityName)->findBy(['id' => $ids]);
    }
}
