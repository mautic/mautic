<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Entity;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class MonitoringRepository.
 */
class MonitoringRepository extends CommonRepository
{
    /**
     * Get a list of entities.
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        return parent::getEntities($args);
    }

    /**
     * @param array $args
     *
     * @return Paginator
     */
    public function getPublishedEntities($args = [])
    {
        $q    = $this->createQueryBuilder($this->getTableAlias());
        $expr = $this->getPublishedByDateExpression($q);

        $q->where($expr);
        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * @return float|int
     */
    public function getPublishedEntitiesCount()
    {
        $q    = $this->createQueryBuilder($this->getTableAlias());
        $expr = $this->getPublishedByDateExpression($q);
        $q->where($expr);
        $args['qb'] = $q;

        return parent::getEntities($args)->count();
    }

    /**
     * @param QueryBuilder $q
     * @param              $filter
     *
     * @return array
     */
    protected function addCatchAllWhereClause(&$q, $filter)
    {
        return $this->addStandardCatchAllWhereClause(
            $q,
            $filter,
            [
                $this->getTableAlias().'.title',
                $this->getTableAlias().'.description',
            ]
        );
    }

    /**
     * @param QueryBuilder $q
     * @param              $filter
     *
     * @return array
     */
    protected function addSearchCommandWhereClause(QueryBuilder $q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'e';
    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }
}
