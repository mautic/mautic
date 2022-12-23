<?php

namespace Mautic\DynamicContentBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<DynamicContentLeadData>
 */
class DynamicContentLeadDataRepository extends CommonRepository
{
    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'dcld';
    }
}
