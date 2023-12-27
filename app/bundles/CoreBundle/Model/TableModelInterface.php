<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Model;

use Doctrine\DBAL\Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * @param T                                        $entity
     * @param array<int|string, array<string, string>> $dataResult
     *
     **/
    public function exportStats(string $format, $entity, array $dataResult): StreamedResponse|Response;
}
