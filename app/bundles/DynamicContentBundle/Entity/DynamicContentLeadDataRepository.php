<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<DynamicContentLeadData>
 */
final class DynamicContentLeadDataRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'dcld';
    }
}
