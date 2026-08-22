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
        private readonly ?int $leadId,
        private readonly string $alias,
        private readonly string $func,
        private readonly QueryBuilder $queryBuilder,
        EntityManagerInterface $entityManager,
    ) {
        $this->em              = $entityManager;
        $this->leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function getLeadId(): ?int
    {
        return $this->leadId;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getFunc(): string
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

    public function getQueryBuilder(): QueryBuilder
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

    public function setDetails(array $details): void
    {
        $this->details = $details;
    }

    public function getLeadsTableAlias(): string
    {
        return $this->leadsTableAlias;
    }
}
