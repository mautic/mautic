<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;

/**
 * Interface CustomFieldRepositoryInterface.
 */
interface CustomFieldRepositoryInterface
{
    /**
     * Return an array of groups supported by the custom fields for this entity.
     *
     * @return array
     */
    public function getFieldGroups();

    /**
     * Get the base DBAL query builder for entities.
     *
     * @return DbalQueryBuilder
     */
    public function getEntitiesDbalQueryBuilder();

    /**
     * Get the base DBAL query builder for entities.
     *
     * @param $order
     *
     * @return OrmQueryBuilder
     */
    public function getEntitiesOrmQueryBuilder($order);

    /**
     * Requires table alias.
     *
     * @return mixed
     */
    public function getTableAlias();
}
