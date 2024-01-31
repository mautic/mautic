<?php

namespace Mautic\EmailBundle\Stats\Helper;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\DateRangeUnitTrait;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\EmailBundle\Stats\FetchOptions\EmailStatOptions;
use Mautic\StatsBundle\Aggregate\Collection\StatCollection;
use Mautic\StatsBundle\Aggregate\Collector;

abstract class AbstractHelper implements StatHelperInterface
{
    use FilterTrait;
    use DateRangeUnitTrait;

    public function __construct(
        private Collector $collector,
        Connection $connection,
        protected GeneratedColumnsProviderInterface $generatedColumnsProvider,
        private UserHelper $userHelper
    ) {
        $this->connection               = $connection;
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function fetchStats(\DateTime $fromDateTime, \DateTime $toDateTime, EmailStatOptions $options)
    {
        $statCollection = $this->collector->fetchStats($this->getName(), $fromDateTime, $toDateTime, $options);
        $calculator     = $statCollection->getCalculator($fromDateTime, $toDateTime);

        $stats = match ($this->getTimeUnitFromDateRange($fromDateTime, $toDateTime)) {
            'Y'     => $calculator->getSumsByYear(),
            'm'     => $calculator->getSumsByMonth(),
            'W'     => $calculator->getSumsByWeek(),
            'd'     => $calculator->getSumsByDay(),
            default => $calculator->getCountsByHour(),
        };

        // Chart.js only care about the values
        return array_values($stats->getStats());
    }

    /**
     * @return ChartQuery
     */
    protected function getQuery(\DateTime $fromDateTime, \DateTime $toDateTime)
    {
        $unit = $this->getTimeUnitFromDateRange($fromDateTime, $toDateTime);

        if ('W' == $unit) {   // We won't support week storage, we will store it by date
            $unit = 'd';
        }
        $query = new ChartQuery($this->connection, $fromDateTime, $toDateTime, $unit);

        $query->setGeneratedColumnProvider($this->generatedColumnsProvider);

        return $query;
    }

    /**
     * Joins the email table and limits created_by to currently logged in user.
     *
     * @param string $emailIdColumn
     */
    protected function limitQueryToCreator(QueryBuilder $q, $emailIdColumn = 't.email_id')
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'emails', 'e', 'e.id = '.$emailIdColumn)
            ->andWhere('e.created_by = :userId')
            ->setParameter('userId', $this->userHelper->getUser()->getId());
    }

    /**
     * @param string $column
     * @param string $prefix
     */
    protected function limitQueryToEmailIds(QueryBuilder $q, array $ids, $column, $prefix)
    {
        if (0 === count($ids)) {
            return;
        }

        if (1 === count($ids)) {
            $q->andWhere("$prefix.$column = :email_id");
            $q->setParameter('email_id', $ids[0]);

            return;
        }

        $q->andWhere("$prefix.$column IN (:email_ids)");
        $q->setParameter('email_ids', $ids, ArrayParameterType::INTEGER);
    }

    /**
     * @throws \Exception
     */
    protected function fetchAndBindToCollection(QueryBuilder $q, StatCollection $statCollection)
    {
        $results = $q->executeQuery()->fetchAllAssociative();
        foreach ($results as $result) {
            $statCollection->addStatByDateTimeStringInUTC($result['date'], $result['count']);
        }
    }
}
