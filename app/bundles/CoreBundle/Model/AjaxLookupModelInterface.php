<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Interface AjaxLookupModelInterface.
 *
 * Defines methods required by AjaxLookupControllerTrait to find matching records
 *
 * @template T of object
 */
interface AjaxLookupModelInterface
{
    /**
     * @param string|array<int,string> $filter
     * @param array<string, mixed>     $options
     *
     * @return array<string, array<int, string>>|array<array<string, mixed>>|array<int|string, string>
     */
    public function getLookupResults(string $type, string|array $filter = '', int $limit = 10, int $start = 0, array $options = []): array;

    /**
     * @return CommonRepository<T>
     */
    public function getRepository();
}
