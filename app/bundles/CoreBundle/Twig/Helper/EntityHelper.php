<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EntityHelper extends AbstractExtension
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Registers the custom Twig functions.
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getEntities', [$this, 'getEntities']),
        ];
    }

    /**
     * Retrieves entities based on the provided class name and ID(s).
     *
     * @param string           $entityName the fully qualified class name of the entity
     * @param int|string|array $ids        a single ID or an array of IDs to retrieve
     *
     * @return array the array of retrieved entities
     */
    public function getEntities(string $entityName, int|string|array $ids): array
    {
        // If $ids is not an array, convert it into an array
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        // Fetch entities using the repository's findBy method
        $entities = $this->entityManager
                         ->getRepository($entityName)
                         ->findBy(['id' => $ids]);

        // Ensure the result is always an array
        return is_array($entities) ? $entities : [$entities];
    }
}
