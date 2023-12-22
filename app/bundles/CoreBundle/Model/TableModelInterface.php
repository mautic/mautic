<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Model;

use Doctrine\DBAL\Exception;

/**
 * Interface MapModelInterface.
 *
 * @template T of object
 */
interface TableModelInterface
{
    /**
     * @param T $entity
     *
     * @return array<string, array<int, array<string, int|string>>>
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

    public function exportResults($object, string $format);
}
