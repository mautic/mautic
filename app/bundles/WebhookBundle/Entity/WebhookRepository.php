<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Entity;

use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class WebhookRepository.
 */
class WebhookRepository extends CommonRepository
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

    /***
     * @param QueryBuilder $q
     * @param              $filter
     * @return array
     */
    protected function addCatchAllWhereClause(QueryBuilder $q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, ['e.name']);
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
     * @return array
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * @return string
     */
    protected function getDefaultOrder()
    {
        return [
            [$this->getTableAlias().'.name', 'ASC'],
        ];
    }
}
