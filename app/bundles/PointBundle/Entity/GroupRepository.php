<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Group>
 */
class GroupRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'pl';
    }
}
