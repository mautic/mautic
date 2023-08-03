<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Model;

use Doctrine\DBAL\Exception;

/**
 * Interface MapModelInterface.
 *
 * @template T of object
 */
interface MapModelInterface
{
    /**
     * @param T $entity
     *
     * @return array<int, array<string, int|string>>
     *
     * @throws Exception
     */
    public function getEmailCountryStats($entity, \DateTime $dateFrom, \DateTime $dateTo, bool $includeVariants = false): array;

    /**
     * Get a specific entity.
     */
    public function getContextEntity(int $id = null): ?object;
}
