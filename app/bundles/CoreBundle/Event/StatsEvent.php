<?php

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\UserBundle\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Used to get statistical data from subscribed tables.
 */
final class StatsEvent extends Event
{
    /**
     * Database table containing statistical data available to get the results from.
     */
    private readonly string $table;

    /**
     * Array of columns to fetch.
     */
    private ?array $select = null;

    /**
     * Database tables which the subscribers already asked for.
     */
    private array $tables = [];

    private array $tableColumns = [];

    /**
     * Array of the result data.
     */
    private array $results = [];

    /**
     * Flag if some results were set.
     */
    private bool $hasResults = false;

    /**
     * Source repository to fetch the results from.
     *
     * @var CommonRepository<object>
     */
    private ?\Mautic\CoreBundle\Entity\CommonRepository $repository = null;

    public function __construct(
        $table,
        /**
         * The page where to start with.
         */
        private readonly int $start,
        /**
         * The rows per page limit.
         */
        private readonly int $limit,
        private readonly array $order,
        /**
         * Array of where filters.
         */
        private array $where,
        private readonly User $user,
    ) {
        $this->table = strtolower(trim(str_replace(MAUTIC_TABLE_PREFIX, '', strip_tags($table))));
    }

    /**
     * Returns if event is for this table.
     *
     * @param CommonRepository<object>|null $repository
     */
    public function isLookingForTable($table, ?CommonRepository $repository = null): bool
    {
        $this->tables[] = $table = str_replace(MAUTIC_TABLE_PREFIX, '', $table);
        if ($repository) {
            $this->tableColumns[$table] = $repository->getTableColumns();
        }

        return $this->table === $table;
    }

    /**
     * Set the source repository to fetch the results from.
     *
     * @param CommonRepository<object> $repository
     */
    public function setRepository(CommonRepository $repository, array $permissions = []): static
    {
        $this->repository = $repository;
        $this->setResults(
            $this->repository->getRows(
                $this->start,
                $this->limit,
                $this->order,
                $this->where,
                $this->select,
                $permissions
            )
        );

        return $this;
    }

    public function getSelect(): ?array
    {
        return $this->select;
    }

    public function setSelect(?array $select = null): static
    {
        $this->select = $select;

        return $this;
    }

    /**
     * Returns the start.
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * Returns the limit.
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Returns the order.
     */
    public function getOrder(): array
    {
        return $this->order;
    }

    /**
     * Returns the where.
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    public function addWhere(array $where): static
    {
        $this->where[] = $where;

        return $this;
    }

    /**
     * Add an array of results and if so, stop propagation.
     *
     * @param array<string, mixed> $results
     */
    public function setResults(array $results): void
    {
        $this->results    = $results;
        $this->hasResults = true;

        $this->stopPropagation();
    }

    /**
     * Returns the results.
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Returns the subscribed tables untill the match was found.
     */
    public function getTables(): array
    {
        sort($this->tables);

        return $this->tables;
    }

    /**
     * @return mixed
     */
    public function getTableColumns($table = null)
    {
        ksort($this->tableColumns);

        return ($table) ? $this->tableColumns[$table] : $this->tableColumns;
    }

    /**
     * Returns boolean if the results were set or not.
     */
    public function hasResults(): bool
    {
        return $this->hasResults;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
