<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Entity\CommonRepository;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class StatsEvent.
 * Used to get statistical data from subscribed tables.
 */
class StatsEvent extends Event
{
    /**
     * Database table containing statistical data available to get the results from.
     *
     * @var string
     */
    protected $table;

    /**
     * Array of columns to fetch.
     *
     * @var null|array
     */
    protected $select = null;

    /**
     * The page where to start with.
     *
     * @var int
     */
    protected $start;

    /**
     * The rows per page limit.
     *
     * @var int
     */
    protected $limit;

    /**
     * Database tables which the subscribers already asked for.
     *
     * @var array
     */
    protected $tables = [];

    /**
     * Array of order by statements.
     *
     * @var array
     */
    protected $order;

    /**
     * Array of where filters.
     *
     * @var array
     */
    protected $where;

    /**
     * Array of the result data.
     *
     * @var array
     */
    protected $results = [];

    /**
     * Flag if some results were set.
     *
     * @var bool
     */
    protected $hasResults = false;

    /**
     * Source repository to fetch the results from.
     *
     * @var CommonRepository
     */
    protected $repository;

    /**
     * StatsEvent constructor.
     *
     * @param       $table
     * @param int   $start
     * @param int   $limit
     * @param array $order
     * @param array $where
     */
    public function __construct($table, $start = 0, $limit = 100, array $order = [], array $where = [])
    {
        $this->table = strtolower(trim(strip_tags($table)));
        $this->start = (int) $start;
        $this->limit = (int) $limit;
        $this->order = $order;
        $this->where = $where;
    }

    /**
     * Returns if event is for this table.
     *
     * @param $table
     *
     * @return bool
     */
    public function isLookingForTable($table)
    {
        $this->tables[] = $table;

        $testTable = $this->table;
        if ($testTable && MAUTIC_TABLE_PREFIX && strpos($testTable, MAUTIC_TABLE_PREFIX) !== 0) {
            $testTable = MAUTIC_TABLE_PREFIX.$testTable;
        }

        return $testTable === $table;
    }

    /**
     * Set the source repository to fetch the results from.
     *
     * @param CommonRepository $repository
     *
     * @return string
     */
    public function setRepository(CommonRepository $repository)
    {
        $this->repository = $repository;
        $this->setResults(
            $this->repository->getRows(
                $this->getStart(),
                $this->getLimit(),
                $this->getOrder(),
                $this->getWhere(),
                $this->getSelect()
            )
        );

        return $this;
    }

    /**
     * @return array
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * @param array|null $select
     *
     * @return $this
     */
    public function setSelect(array $select = null)
    {
        $this->select = $select;

        return $this;
    }

    /**
     * Returns the start.
     *
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Returns the limit.
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Returns the order.
     *
     * @return array
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Returns the where.
     *
     * @return array
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * Add an array of results and if so, stop propagation.
     *
     * @param array $results
     */
    public function setResults(array $results)
    {
        $this->results    = $results;
        $this->hasResults = true;

        $this->stopPropagation();
    }

    /**
     * Returns the results.
     *
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Returns the subscribed tables untill the match was found.
     *
     * @return array
     */
    public function getTables()
    {
        sort($this->tables);

        return $this->tables;
    }

    /**
     * Returns boolean if the results were set or not.
     *
     * @return bool
     */
    public function hasResults()
    {
        return $this->hasResults;
    }
}
