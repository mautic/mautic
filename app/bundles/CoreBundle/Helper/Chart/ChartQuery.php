<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper\Chart;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class ChartQuery.
 *
 * Methods to get the chart data as native queries to get better performance and work with date/time native SQL queries.
 */
class ChartQuery extends AbstractChart
{
    /**
     * Doctrine's Connetion object.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Match date/time unit to a SQL datetime format
     * {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}.
     *
     * @var array
     */
    protected $sqlFormats = [
        's' => 'Y-m-d H:i:s',
        'i' => 'Y-m-d H:i:00',
        'H' => 'Y-m-d H:00:00',
        'd' => 'Y-m-d 00:00:00',
        'D' => 'Y-m-d 00:00:00', // ('D' is BC. Can be removed when all charts use this class)
        'W' => 'Y-m-d 00:00:00',
        'm' => 'Y-m-01 00:00:00',
        'M' => 'Y-m-00 00:00:00', // ('M' is BC. Can be removed when all charts use this class)
        'Y' => 'Y-01-01 00:00:00',
    ];

    /**
     * Match date/time unit to a MySql datetime format
     * {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * {@link dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_date-format}.
     *
     * @var array
     */
    protected $mysqlTimeUnits = [
        's' => '%Y-%m-%d %H:%i:%s',
        'i' => '%Y-%m-%d %H:%i',
        'H' => '%Y-%m-%d %H:00',
        'd' => '%Y-%m-%d',
        'D' => '%Y-%m-%d', // ('D' is BC. Can be removed when all charts use this class)
        'W' => '%Y %U',
        'm' => '%Y-%m',
        'M' => '%Y-%m', // ('M' is BC. Can be removed when all charts use this class)
        'Y' => '%Y',
    ];

    /**
     * Construct a new ChartQuery object.
     *
     * @param Connection $connection
     * @param DateTime   $dateFrom
     * @param DateTime   $dateTo
     * @param string     $unit
     */
    public function __construct(Connection $connection, \DateTime $dateFrom, \DateTime $dateTo, $unit = null)
    {
        $this->unit       = (null === $unit) ? $this->getTimeUnitFromDateRange($dateFrom, $dateTo) : $unit;
        $this->isTimeUnit = (in_array($this->unit, ['H', 'i', 's']));
        $this->setDateRange($dateFrom, $dateTo);
        $this->connection = $connection;
    }

    /**
     * Apply where filters to the query.
     *
     * @param QueryBuilder $query
     * @param array        $filters
     */
    public function applyFilters(&$query, $filters)
    {
        if ($filters && is_array($filters)) {
            foreach ($filters as $column => $value) {
                $valId = $column.'_val';

                // Special case: Lead list filter
                if ($column === 'leadlist_id') {
                    $query->join('t', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'lll', 'lll.lead_id = '.$value['list_column_name']);
                    $query->andWhere('lll.leadlist_id = :'.$valId);
                    $query->setParameter($valId, $value['value']);
                } elseif (isset($value['expression']) && method_exists($query->expr(), $value['expression'])) {
                    $query->andWhere($query->expr()->{$value['expression']}($column));
                    if (isset($value['value'])) {
                        $query->setParameter($valId, $value['value']);
                    }
                } else {
                    if (is_array($value)) {
                        $query->andWhere($query->expr()->in('t.'.$column, $value));
                    } else {
                        $query->andWhere('t.'.$column.' = :'.$valId);
                        $query->setParameter($valId, $value);
                    }
                }
            }
        }
    }

    /**
     * Apply date filters to the query.
     *
     * @param QueryBuilder $query
     * @param string       $dateColumn
     * @param string       $tablePrefix
     */
    public function applyDateFilters(&$query, $dateColumn, $tablePrefix = 't')
    {
        // Check if the date filters have already been applied
        if ($parameters = $query->getParameters()) {
            if (array_key_exists('dateTo', $parameters) || array_key_exists('dateFrom', $parameters)) {
                return;
            }
        }

        if ($dateColumn) {
            if ($this->dateFrom && $this->dateTo) {
                // Between is faster so if we know both dates...
                $dateFrom = clone $this->dateFrom;
                $dateTo   = clone $this->dateTo;
                if ($this->isTimeUnit) {
                    $dateFrom->setTimeZone(new \DateTimeZone('UTC'));
                }
                if ($this->isTimeUnit) {
                    $dateTo->setTimeZone(new \DateTimeZone('UTC'));
                }
                $query->andWhere($tablePrefix.'.'.$dateColumn.' BETWEEN :dateFrom AND :dateTo');
                $query->setParameter('dateFrom', $dateFrom->format('Y-m-d H:i:s'));
                $query->setParameter('dateTo', $dateTo->format('Y-m-d H:i:s'));
            } else {
                // Apply the start date/time if set
                if ($this->dateFrom) {
                    $dateFrom = clone $this->dateFrom;
                    if ($this->isTimeUnit) {
                        $dateFrom->setTimeZone(new \DateTimeZone('UTC'));
                    }
                    $query->andWhere($tablePrefix.'.'.$dateColumn.' >= :dateFrom');
                    $query->setParameter('dateFrom', $dateFrom->format('Y-m-d H:i:s'));
                }

                // Apply the end date/time if set
                if ($this->dateTo) {
                    $dateTo = clone $this->dateTo;
                    if ($this->isTimeUnit) {
                        $dateTo->setTimeZone(new \DateTimeZone('UTC'));
                    }
                    $query->andWhere($tablePrefix.'.'.$dateColumn.' <= :dateTo');
                    $query->setParameter('dateTo', $dateTo->format('Y-m-d H:i:s'));
                }
            }
        }
    }

    /**
     * Get the right unit for current database platform.
     *
     * @param string $unit {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     *
     * @return string
     */
    public function translateTimeUnit($unit)
    {
        if (!isset($this->mysqlTimeUnits[$unit])) {
            throw new \UnexpectedValueException('Date/Time unit "'.$unit.'" is not available for MySql.');
        }

        return $this->mysqlTimeUnits[$unit];
    }

    /**
     * Prepare database query for fetching the line time chart data.
     *
     * @param string $table   without prefix
     * @param string $column  name. The column must be type of datetime
     * @param array  $filters will be added to where claues
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function prepareTimeDataQuery($table, $column, $filters = [], $countColumn = '*')
    {
        // Convert time unitst to the right form for current database platform
        $query = $this->connection->createQueryBuilder();
        $query->from($this->prepareTable($table), 't');

        $this->modifyTimeDataQuery($query, $column, 't', $countColumn);
        $this->applyFilters($query, $filters);
        $this->applyDateFilters($query, $column);

        return $query;
    }

    /**
     * Modify database query for fetching the line time chart data.
     *
     * @param QueryBuilder $query
     * @param string       $column      name
     * @param string       $tablePrefix
     * @param string       $countColumn
     */
    public function modifyTimeDataQuery(&$query, $column, $tablePrefix = 't', $countColumn = '*')
    {
        // Convert time unitst to the right form for current database platform
        $dbUnit  = $this->translateTimeUnit($this->unit);
        $limit   = $this->countAmountFromDateRange($this->unit);
        $groupBy = '';

        if (isset($filters['groupBy'])) {
            $groupBy = ', '.$tablePrefix.'.'.$filters['groupBy'];
            unset($filters['groupBy']);
        }
        $dateConstruct = 'DATE_FORMAT('.$tablePrefix.'.'.$column.', \''.$dbUnit.'\')';
        $query->select($dateConstruct.' AS date, COUNT('.$countColumn.') AS count')
            ->groupBy($dateConstruct.$groupBy);

        $query->orderBy($dateConstruct, 'ASC')->setMaxResults($limit);
    }

    /**
     * Fetch data for a time related dataset.
     *
     * @param string $table   without prefix
     * @param string $column  name. The column must be type of datetime
     * @param array  $filters will be added to where claues
     *
     * @return array
     */
    public function fetchTimeData($table, $column, $filters = [])
    {
        $query = $this->prepareTimeDataQuery($table, $column, $filters);

        return $this->loadAndBuildTimeData($query);
    }

    /**
     * Loads data from prepared query and builds the chart data.
     *
     * @param QueryBuilder $query
     *
     * @return array
     */
    public function loadAndBuildTimeData($query)
    {
        $rawData = $query->execute()->fetchAll();

        return $this->completeTimeData($rawData);
    }

    /**
     * Go through the raw data and add the missing times.
     *
     * @param string $table   without prefix
     * @param string $column  name. The column must be type of datetime
     * @param array  $filters will be added to where claues
     *
     * @return array
     */
    public function completeTimeData($rawData, $countAverage = false)
    {
        $data          = [];
        $averageCounts = [];
        $oneUnit       = $this->getUnitInterval();
        $limit         = $this->countAmountFromDateRange($this->unit);
        $previousDate  = clone $this->dateFrom;
        $utcTz         = new \DateTimeZone('UTC');

        if ($this->unit === 'Y') {
            $previousDate->modify('first day of January');
        } elseif ($this->unit == 'm') {
            $previousDate->modify('first day of this month');
        } elseif ($this->unit === 'W') {
            $previousDate->modify('Monday this week');
        }

        // Convert data from DB to the chart.js format
        for ($i = 0; $i < $limit; ++$i) {
            $nextDate = clone $previousDate;

            if ($this->unit === 'm') {
                $nextDate->modify('first day of next month');
            } elseif ($this->unit === 'W') {
                $nextDate->modify('Monday next week');
            } else {
                $nextDate->add($oneUnit);
            }

            foreach ($rawData as $key => &$item) {
                if (!isset($item['date_comparison'])) {
                    if (!$item['date'] instanceof \DateTime) {
                        /*
                         * PHP DateTime cannot parse the Y W (ex 2016 09)
                         * format, so we transform it into d-M-Y.
                         */
                        if ($this->unit === 'W' && isset($item['date'])) {
                            list($year, $week) = explode(' ', $item['date']);
                            $newDate           = new \DateTime();
                            $newDate->setISODate($year, $week);
                            $item['date'] = $newDate->format('d-M-Y');
                            unset($newDate);
                        }

                        // Time based data from the database will always in UTC; otherwise assume local
                        // since changing the timezone could result in wrong placement
                        $itemDate = new \DateTime($item['date'], ($this->isTimeUnit) ? $utcTz : $this->timezone);
                    } else {
                        $itemDate = clone $item['date'];
                    }

                    if (!$this->isTimeUnit) {
                        // Hours do not matter so let's reset to 00:00:00 for date comparison
                        $itemDate->setTime(0, 0, 0);
                    } else {
                        // Convert to the timezone used for comparison
                        $itemDate->setTimezone($this->timezone);
                    }

                    $item['date_comparison'] = $itemDate;
                } else {
                    $itemDate = $item['date_comparison'];
                }

                // Place the right suma is between the time unit and time unit +1
                if (isset($item['count']) && $itemDate >= $previousDate && $itemDate < $nextDate) {
                    $data[$i] = $item['count'];
                    unset($rawData[$key]);
                    continue;
                }

                // Add the right item is between the time unit and time unit +1
                if (isset($item['data']) && $itemDate >= $previousDate && $itemDate < $nextDate) {
                    if (isset($data[$i])) {
                        $data[$i] += $item['data'];
                        if ($countAverage) {
                            ++$averageCounts[$i];
                        }
                    } else {
                        $data[$i] = $item['data'];
                        if ($countAverage) {
                            $averageCounts[$i] = 1;
                        }
                    }
                    unset($rawData[$key]);
                    continue;
                }
            }

            // Chart.js requires the 0 for empty data, but the array slot has to exist
            if (!isset($data[$i])) {
                $data[$i] = 0;
                if ($countAverage) {
                    $averageCounts[$i] = 0;
                }
            }

            $previousDate = $nextDate;
        }

        if ($countAverage) {
            foreach ($data as $key => $value) {
                if (!empty($averageCounts[$key])) {
                    $data[$key] = round($data[$key] / $averageCounts[$key], 2);
                }
            }
        }

        return $data;
    }

    /**
     * Count occurences of a value in a column.
     *
     * @param string $table        without prefix
     * @param string $uniqueColumn name
     * @param string $dateColumn   name
     * @param array  $filters      will be added to where claues
     * @param array  $options      for special behavior
     *
     * @return QueryBuilder $query
     */
    public function getCountQuery($table, $uniqueColumn, $dateColumn = null, $filters = [], $options = [], $tablePrefix = 't')
    {
        $query = $this->connection->createQueryBuilder();
        $query->from($this->prepareTable($table), $tablePrefix);
        $this->modifyCountQuery($query, $uniqueColumn, $dateColumn, $tablePrefix);
        $this->applyFilters($query, $filters);
        $this->applyDateFilters($query, $dateColumn);

        return $query;
    }

    /**
     * Modify the query to count occurences of a value in a column.
     *
     * @param QueryBuilder $table        without prefix
     * @param string       $uniqueColumn name
     * @param array        $options      for special behavior
     * @param string       $tablePrefix
     */
    public function modifyCountQuery(QueryBuilder &$query, $uniqueColumn, $options = [], $tablePrefix = 't')
    {
        $query->select('COUNT('.$tablePrefix.'.'.$uniqueColumn.') AS count');

        // Count only unique values
        if (!empty($options['getUnique'])) {
            $selectAlso = '';
            if (isset($options['selectAlso'])) {
                $selectAlso = ', '.implode(', ', $options['selectAlso']);
            }
            // Modify the previous query
            $query->select($tablePrefix.'.'.$uniqueColumn.$selectAlso);
            $query->having('COUNT(*) = 1')
                ->groupBy($tablePrefix.'.'.$uniqueColumn.$selectAlso);

            // Create a new query with subquery of the previous query
            $uniqueQuery = $this->connection->createQueryBuilder();
            $uniqueQuery->select('COUNT('.$tablePrefix.'.'.$uniqueColumn.') AS count')
                ->from('('.$query->getSql().')', $tablePrefix);

            // Apply params from the previous query to the new query
            $uniqueQuery->setParameters($query->getParameters());

            // Replace the new query with previous query
            $query = $uniqueQuery;
        }

        return $query;
    }

    /**
     * Count occurences of a value in a column.
     *
     * @param string $table        without prefix
     * @param string $uniqueColumn name
     * @param string $dateColumn   name
     * @param array  $filters      will be added to where claues
     * @param array  $options      for special behavior
     *
     * @return int
     */
    public function count($table, $uniqueColumn, $dateColumn = null, $filters = [], $options = [])
    {
        $query = $this->getCountQuery($table, $uniqueColumn, $dateColumn, $filters);

        return $this->fetchCount($query);
    }

    /**
     * Fetch the count integet from a query.
     *
     * @param QueryBuilder $query
     *
     * @return int
     */
    public function fetchCount(QueryBuilder $query)
    {
        $data = $query->execute()->fetch();

        return (int) $data['count'];
    }

    /**
     * Get the query to count how many rows is between a range of date diff in seconds.
     *
     * @param string $table       without prefix
     * @param string $dateColumn1
     * @param string $dateColumn2
     * @param int    $startSecond
     * @param int    $endSecond
     * @param array  $filters     will be added to where claues
     * @param string $tablePrefix
     *
     * @return QueryBuilder $query
     */
    public function getCountDateDiffQuery($table, $dateColumn1, $dateColumn2, $startSecond = 0, $endSecond = 60, $filters = [], $tablePrefix = 't')
    {
        $query = $this->connection->createQueryBuilder();
        $query->from($this->prepareTable($table), $tablePrefix);
        $this->modifyCountDateDiffQuery($query, $dateColumn1, $dateColumn2, $startSecond, $endSecond, $tablePrefix);
        $this->applyFilters($query, $filters);
        $this->applyDateFilters($query, $dateColumn1);

        return $query;
    }

    /**
     * Modify the query to count how many rows is between a range of date diff in seconds.
     *
     * @param QueryBuilder $query
     * @param string       $dateColumn1
     * @param string       $dateColumn2
     * @param int          $startSecond
     * @param int          $endSecond
     * @param array        $filters     will be added to where claues
     * @param string       $tablePrefix
     */
    public function modifyCountDateDiffQuery(QueryBuilder &$query, $dateColumn1, $dateColumn2, $startSecond = 0, $endSecond = 60, $tablePrefix = 't')
    {
        $query->select('COUNT('.$tablePrefix.'.'.$dateColumn1.') AS count');
        $query->where('TIMESTAMPDIFF(SECOND, '.$tablePrefix.'.'.$dateColumn1.', '.$tablePrefix.'.'.$dateColumn2.') >= :startSecond');
        $query->andWhere('TIMESTAMPDIFF(SECOND, '.$tablePrefix.'.'.$dateColumn1.', '.$tablePrefix.'.'.$dateColumn2.') < :endSecond');

        $query->setParameter('startSecond', $startSecond);
        $query->setParameter('endSecond', $endSecond);
    }

    /**
     * Count how many rows is between a range of date diff in seconds.
     *
     * @param string $query
     *
     * @return int
     */
    public function fetchCountDateDiff($query)
    {
        $data = $query->execute()->fetch();

        return (int) $data['count'];
    }

    /**
     * @param $table
     *
     * @return mixed
     */
    protected function prepareTable($table)
    {
        if (MAUTIC_TABLE_PREFIX && strpos($table, MAUTIC_TABLE_PREFIX) === 0) {
            return $table;
        }

        if (strpos($table, '(') === 0) {
            return $table;
        }

        return MAUTIC_TABLE_PREFIX.$table;
    }
}
