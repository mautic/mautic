<?php

namespace Mautic\LeadBundle\Event;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Please refer to LeadListRepository.php, inside getListFilterExprCombined method, for examples.
 */
final class LeadListFilteringEvent extends CommonEvent
{
    private bool $isFilteringDone = false;

    private string $subQuery = '';

    private readonly string $leadsTableAlias;

    public function __construct(
        private array $details,
        private ?int $leadId,
        private string $alias,
        private string $func,
        private QueryBuilder $queryBuilder,
        EntityManagerInterface $entityManager,
    ) {
        $this->em              = $entityManager;
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

    public function setFilteringStatus(bool $status): void
    {
        $this->isFilteringDone = $status;
    }

    public function setSubQuery(string $query): void
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
