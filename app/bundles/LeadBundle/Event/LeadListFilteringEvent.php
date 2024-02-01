<?php

namespace Mautic\LeadBundle\Event;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Please refer to LeadListRepository.php, inside getListFilterExprCombined method, for examples.
 */
class LeadListFilteringEvent extends CommonEvent
{
    protected bool $isFilteringDone;

    protected string $subQuery;

    private string $leadsTableAlias;

    /**
     * @param array        $details
     * @param int          $leadId
     * @param string       $alias
     * @param string       $func
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(
        protected $details,
        protected $leadId,
        protected $alias,
        protected $func,
        protected $queryBuilder,
        EntityManager $entityManager
    ) {
        $this->em              = $entityManager;
        $this->isFilteringDone = false;
        $this->subQuery        = '';
        $this->leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @return int
     */
    public function getLeadId()
    {
        return $this->leadId;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getFunc()
    {
        return $this->func;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @param bool $status
     */
    public function setFilteringStatus($status): void
    {
        $this->isFilteringDone = $status;
    }

    /**
     * @param string $query
     */
    public function setSubQuery($query): void
    {
        $this->subQuery = $query;

        $this->setFilteringStatus(true);
    }

    public function isFilteringDone(): bool
    {
        return $this->isFilteringDone;
    }

    public function getSubQuery(): string
    {
        return $this->subQuery;
    }

    /**
     * @param array $details
     */
    public function setDetails($details): void
    {
        $this->details = $details;
    }

    public function getLeadsTableAlias(): string
    {
        return $this->leadsTableAlias;
    }
}
