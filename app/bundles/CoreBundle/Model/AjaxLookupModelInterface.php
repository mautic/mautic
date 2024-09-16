<?php

namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Interface AjaxLookupModelInterface.
 *
 * Defines methods required by AjaxLookupControllerTrait to find matching records
 */
interface AjaxLookupModelInterface
{
    /**
     * @param        $type
     * @param string $filter
     * @param int    $limit
     * @param int    $start
     *
     * @return mixed
     */
    public function getLookupResults($type, $filter = '', $limit = 10, $start = 0);

    /**
     * @return CommonRepository
     */
    public function getRepository();
}
