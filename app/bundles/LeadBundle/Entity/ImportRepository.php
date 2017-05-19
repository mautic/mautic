<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * ImportRepository.
 */
class ImportRepository extends CommonRepository
{
    /**
     * Count how many imports with the status is there
     *
     * @param  float $ghostDelay when is the import ghost? In hours
     * @param  int   $limit
     *
     * @return array
     */
    public function getGhostImports(float $ghostDelay = 2, int $limit = null)
    {
        $q = $this->getQueryForStatuses([Import::IN_PROGRESS]);
        $q->select($this->getTableAlias())
            ->andWhere($q->expr()->lt($this->getTableAlias().'.dateModified', '(CURRENT_TIMESTAMP() - :delay)'))
            ->setParameter('delay', (3600 * $ghostDelay));

        if ($limit !== null) {
            $q->setFirstResult(0)
                ->setMaxResults($limit);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * Count how many imports with the status is there
     *
     * @param  array $statuses
     * @param  int   $limit
     *
     * @return array
     */
    public function getImportsWithStatuses(array $statuses, int $limit = null)
    {
        $q = $this->getQueryForStatuses($statuses);
        $q->select($this->getTableAlias())
            ->orderBy($this->getTableAlias().'.priority', 'ASC')
            ->addOrderBy($this->getTableAlias().'.dateAdded', 'DESC');

        if ($limit !== null) {
            $q->setFirstResult(0)
                ->setMaxResults($limit);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * Count how many imports with the status is there
     *
     * @param  array $statuses
     *
     * @return int
     */
    public function countImportsWithStatuses(array $statuses)
    {
        $q = $this->getQueryForStatuses($statuses);
        $q->select('COUNT(DISTINCT '.$this->getTableAlias().'.id) as theCount');

        $results = $q->getQuery()->getSingleResult();

        if (isset($results['theCount'])) {
            return (int) $results['theCount'];
        }

        return 0;
    }

    public function getQueryForStatuses($statuses)
    {
        $q = $this->createQueryBuilder($this->getTableAlias());

        return $q->where($q->expr()->in($this->getTableAlias().'.status', $statuses));
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTableAlias()
    {
        return 'i';
    }
}
