<?php

namespace Mautic\DynamicContentBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<DynamicContentLeadData>
 */
class DynamicContentLeadDataRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'dcld';
    }
}
