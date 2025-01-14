<?php

namespace Mautic\DynamicContentBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class DownloadRepository.
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
