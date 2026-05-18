<?php

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
     * @return mixed
     */
    public function getLookupResults(string $type, string|array $filter = '', int $limit = 10, int $start = 0, array $options = []);

    /**
     * @return CommonRepository<T>
     */
    public function getRepository();
}
