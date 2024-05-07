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

    public function getEntities(array $args = [])
    {
        // Without qb it returns entities indexed by id instead of array indexes
        $args['qb'] = $this->createQueryBuilder($this->getTableAlias());

        return parent::getEntities($args);
    }
}
