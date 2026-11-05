<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<PointInsight>
 */
final class PointInsightRepository extends CommonRepository
{
    public function getEntities(array $args = []): iterable
    {
        $q = $this->createQueryBuilder($this->getTableAlias())
            ->select($this->getTableAlias().', cat')
            ->leftJoin($this->getTableAlias().'.category', 'cat');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    public function getTableAlias(): string
    {
        return 'pi';
    }
}
