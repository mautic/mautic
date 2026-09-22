<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Mautic\CoreBundle\Doctrine\Query\QueryBuilder;

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
     * @return QueryBuilder
     */
    public function getEntitiesDbalQueryBuilder();

    /**
     * Get the base DBAL query builder for entities.
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
