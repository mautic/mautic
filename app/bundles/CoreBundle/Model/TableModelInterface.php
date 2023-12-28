<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Model;

use Doctrine\DBAL\Exception;

/**
 * Interface TableModelInterface.
 *
 * @template T of object
 */
interface TableModelInterface
{
    /**
     * @param T $entity
     *
     * @return array<int|string, array<string, int|string|null>>
     *
     * @throws Exception
     */
    public function getCountryStats($entity, bool $includeVariants = false): array;

    /**
     * @param int|array id
     *
     * @return object|null
     *
     * @phpstan-ignore-next-line
     */
    public function getEntity($id = null);
}
