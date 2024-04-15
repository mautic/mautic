<?php

namespace Mautic\CoreBundle\Helper\Chart;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Methods to get the chart data as native queries to get better performance and work with date/time native SQL queries.
 */
class ChartQuery extends AbstractChart
{
    private DateTimeHelper $dateTimeHelper;

    private ?\Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface $generatedColumnProvider = null;

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
     * Possible values are 'd'/'H'/'i'/'i'/'W'/'m'/'Y'.
     *
     * @see \Mautic\CoreBundle\Helper\Chart\DateRangeUnitTrait::getTimeUnitFromDateRange()
     *
     * @param string|null $unit
     */
    public function __construct(
        protected Connection $connection,
        \DateTime $dateFrom,
        \DateTime $dateTo,
        $unit = null
    ) {
        $this->dateTimeHelper = new DateTimeHelper();
        $this->unit           = $unit ?? $this->getTimeUnitFromDateRange($dateFrom, $dateTo);
        $this->isTimeUnit     = in_array($this->unit, ['H', 'i', 's']);
        $this->setDateRange($dateFrom, $dateTo);
    }

    public function setGeneratedColumnProvider(GeneratedColumnsProviderInterface $generatedColumnProvider): void
    {
        $this->generatedColumnProvider = $generatedColumnProvider;
    }

    /**
     * Apply where filters to the query.
     *
     * @param QueryBuilder $query
     * @param array        $filters
     */
    public function applyFilters(&$query, $filters): void
    {
        if ($filters && is_array($filters)) {
            foreach ($filters as $column => $value) {
                $valId = $column.'_val';

                // Special case: Lead list filter
                if ('leadlist_id' === $column) {
                    $query->join('t', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'lll', 'lll.lead_id = '.$value['list_column_name']);
                    $query->andWhere('lll.leadlist_id = :'.$valId);
                    $query->setParameter($valId, $value['value']);
                } elseif (isset($value['expression']) && method_exists($query->expr(), $value['expression'])) {
                    $query->andWhere($query->expr()->{$value['expression']}($column));
                    if (isset($value['value'])) {
                        $query->setParameter($valId, $value['value']);
                    }
                } elseif (isset($value['subquery'])) {
                    $query->andWhere($value['subquery']);
                } else {
                    $column = str_replace('t.', '', $column);
                    $valId  = str_replace('t.', '', $valId);
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
    public function applyDateFilters(&$query, $dateColumn, $tablePrefix = 't'): void
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
                /** @var \DateTime $dateFrom */
                $dateFrom = clone $this->dateFrom;
                /** @var \DateTime $dateTo */
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
                    /** @var \DateTime $dateFrom */
                    $dateFrom = clone $this->dateFrom;
                    if ($this->isTimeUnit) {
                        $dateFrom->setTimeZone(new \DateTimeZone('UTC'));
                    }
                    $query->andWhere($tablePrefix.'.'.$dateColumn.' >= :dateFrom');
                    $query->setParameter('dateFrom', $dateFrom->format('Y-m-d H:i:s'));
                }

                // Apply the end date/time if set
                if ($this->dateTo) {
                    /** @var \DateTime $dateTo */
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
    public function translateTimeUnit($unit = null)
    {
        if (null === $unit) {
            $unit = $this->unit;
        }

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
    public function prepareTimeDataQuery($table, $column, $filters = [], $countColumn = '*', $isEnumerable = true, bool $useSqlOrder = true)
    {
        // Convert time unitst to the right form for current database platform
        $query = $this->connection->createQueryBuilder();
        $query->from($this->prepareTable($table), 't');

        $this->modifyTimeDataQuery($query, $column, 't', $countColumn, $isEnumerable, $useSqlOrder);
        $this->applyFilters($query, $filters);
        $this->applyDateFilters($query, $column);

        return $query;
    }

    /**
     * Modify database query for fetching the line time chart data.
     *
     * @param QueryBuilder $query
     * @param string       $column       name
     * @param string       $tablePrefix
     * @param string       $countColumn
     * @param bool|string  $isEnumerable true = COUNT, string sum = SUM
     */
    public function modifyTimeDataQuery($query, $column, $tablePrefix = 't', $countColumn = '*', $isEnumerable = true, bool $useSqlOrder = true): void
    {
        // Convert time units to the right form for current database platform
        $limit         = $this->countAmountFromDateRange();
        $dateConstruct = $this->getDateConstruct($tablePrefix, $column);

        if (true === $isEnumerable) {
            $count = 'COUNT('.$countColumn.') AS count';
        } elseif ('sum' == $isEnumerable) {
            $count = 'SUM('.$countColumn.') AS count';
        } else {
            $count = $countColumn.' AS count';
        }

        $query->select($dateConstruct.' AS date, '.$count);
        $query->groupBy($dateConstruct);
        if ($useSqlOrder) {
            // Some queries needs to avoid this because of query performance
            $query->orderBy($dateConstruct, 'ASC');
        }
        $query->setMaxResults($limit);
    }

    /**
     * Fetch data for a time related dataset.
     *
     * @param string $table   without prefix
     * @param string $column  name. The column must be type of datetime
     * @param array  $filters will be added to where claues
     */
    public function fetchTimeData($table, $column, $filters = []): array
    {
        $query = $this->prepareTimeDataQuery($table, $column, $filters);

        return $this->loadAndBuildTimeData($query);
    }

    /**
     * Fetch data and sum it for a time related dataset.
     *
     * @param string $table     without prefix
     * @param string $column    name. The column must be type of datetime
     * @param array  $filters   will be added to where claues
     * @param string $sumColumn name that will be summed
     */
    public function fetchSumTimeData($table, $column, $filters, $sumColumn): array
    {
        $query = $this->prepareTimeDataQuery($table, $column, $filters, $sumColumn, 'sum');

        return $this->loadAndBuildTimeData($query);
    }

    /**
     * Loads data from prepared query and builds the chart data.
     *
     * @param QueryBuilder $query
     */
    public function loadAndBuildTimeData($query): array
    {
        $rawData =  $query->executeQuery()->fetchAllAssociative();

        return $this->completeTimeData($rawData);
    }

    /**
     * Go through the raw data and add the missing times.
     */
    public function completeTimeData($rawData, $countAverage = false): array
    {
        $data          = [];
        $averageCounts = [];
        $oneUnit       = $this->getUnitInterval();
        $limit         = $this->countAmountFromDateRange();
        /** @var \DateTime $previousDate */
        $previousDate  = clone $this->dateFrom;
        $utcTz         = new \DateTimeZone('UTC');

        // Do not let hours to mess with date comparisions.
        $previousDate->setTime(0, 0, 0);

        if ('Y' === $this->unit) {
            $previousDate->modify('first day of January');
        } elseif ('m' == $this->unit) {
            $previousDate->modify('first day of this month');
        } elseif ('W' === $this->unit) {
            $previousDate->modify('Monday this week');
        }

        // Convert data from DB to the chart.js format
        for ($i = 0; $i < $limit; ++$i) {
            $nextDate = clone $previousDate;

            if ('m' === $this->unit) {
                $nextDate->modify('first day of next month');
            } elseif ('W' === $this->unit) {
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
                        if ('W' === $this->unit && isset($item['date'])) {
                            [$year, $week]     = explode(' ', $item['date']);
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
     * @param string $uniqueColumn name
     * @param array  $options      for special behavior
     * @param string $tablePrefix
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
     */
    public function count($table, $uniqueColumn, $dateColumn = null, $filters = [], $options = []): int
    {
        $query = $this->getCountQuery($table, $uniqueColumn, $dateColumn, $filters);

        return $this->fetchCount($query);
    }

    /**
     * Fetch the count integet from a query.
     */
    public function fetchCount(QueryBuilder $query): int
    {
        $data = $query->executeQuery()->fetchAssociative();

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
     * @param string $dateColumn1
     * @param string $dateColumn2
     * @param int    $startSecond
     * @param int    $endSecond
     * @param string $tablePrefix
     */
    public function modifyCountDateDiffQuery(QueryBuilder &$query, $dateColumn1, $dateColumn2, $startSecond = 0, $endSecond = 60, $tablePrefix = 't'): void
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
     */
    public function fetchCountDateDiff($query): int
    {
        $data = $query->execute()->fetchAssociative();

        return (int) $data['count'];
    }

    /**
     * @return mixed
     */
    protected function prepareTable($table)
    {
        if (MAUTIC_TABLE_PREFIX && str_starts_with($table, MAUTIC_TABLE_PREFIX)) {
            return $table;
        }

        if (str_starts_with($table, '(')) {
            return $table;
        }

        return MAUTIC_TABLE_PREFIX.$table;
    }

    /**
     * @param string $tablePrefix
     * @param string $column
     */
    private function getDateConstruct($tablePrefix, $column): string
    {
        if ($this->generatedColumnProvider) {
            $generatedColumns = $this->generatedColumnProvider->getGeneratedColumns();

            try {
                $generatedColumn = $generatedColumns->getForOriginalDateColumnAndUnit($column, $this->unit);

                return $tablePrefix.'.'.$generatedColumn->getColumnName();
            } catch (\UnexpectedValueException) {
                // Alright. Use the original column then.
            }
        }

        $dbUnit                = $this->translateTimeUnit($this->unit);
        $columnName            = $tablePrefix.'.'.$column;
        $defaultTimezoneOffset = $this->dateTimeHelper->getLocalTimezoneOffset();
        $columnName            = "CONVERT_TZ($columnName, '+00:00', '{$defaultTimezoneOffset}')";

        return 'DATE_FORMAT('.$columnName.', \''.$dbUnit.'\')';
    }
}
