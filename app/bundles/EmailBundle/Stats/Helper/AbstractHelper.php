<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Stats\Helper;

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

    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var UserHelper
     */
    private $userHelper;

    /**
     * @var GeneratedColumnsProviderInterface
     */
    protected $generatedColumnsProvider;

    /**
     * @param Collector                         $collector
     * @param Connection                        $connection
     * @param GeneratedColumnsProviderInterface $generatedColumnsProvider
     * @param UserHelper                        $userHelper
     */
    public function __construct(
        Collector $collector,
        Connection $connection,
        GeneratedColumnsProviderInterface $generatedColumnsProvider,
        UserHelper $userHelper
    ) {
        $this->collector                = $collector;
        $this->connection               = $connection;
        $this->generatedColumnsProvider = $generatedColumnsProvider;
        $this->userHelper               = $userHelper;
    }

    /**
     * @param \DateTime        $fromDateTime
     * @param \DateTime        $toDateTime
     * @param EmailStatOptions $options
     *
     * @return array
     *
     * @throws \Exception
     */
    public function fetchStats(\DateTime $fromDateTime, \DateTime $toDateTime, EmailStatOptions $options)
    {
        $statCollection = $this->collector->fetchStats($this->getName(), $fromDateTime, $toDateTime, $options);
        $calculator     = $statCollection->getCalculator($fromDateTime, $toDateTime);

        // Format into what is required for the graphs
        switch ($this->getTimeUnitFromDateRange($fromDateTime, $toDateTime)) {
            case 'Y': // year
                $stats = $calculator->getSumsByYear();
                break;
            case 'm': // month
                $stats = $calculator->getSumsByMonth();
                break;
            case 'd': // day
                $stats = $calculator->getSumsByDay();
                break;
            case 'H': // hour
            default:
                $stats = $calculator->getCountsByHour();
                break;
        }

        // Chart.js only care about the values
        return array_values($stats->getStats());
    }

    /**
     * @param \DateTime $fromDateTime
     * @param \DateTime $toDateTime
     *
     * @return ChartQuery
     */
    protected function getQuery(\DateTime $fromDateTime, \DateTime $toDateTime)
    {
        $unit  = $this->getTimeUnitFromDateRange($fromDateTime, $toDateTime);
        $query = new ChartQuery($this->connection, $fromDateTime, $toDateTime, $unit);

        $query->setGeneratedColumnProvider($this->generatedColumnsProvider);

        return $query;
    }

    /**
     * Joins the email table and limits created_by to currently logged in user.
     *
     * @param QueryBuilder $q
     * @param string       $emailIdColumn
     */
    protected function limitQueryToCreator(QueryBuilder $q, $emailIdColumn = 't.email_id')
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'emails', 'e', 'e.id = '.$emailIdColumn)
            ->andWhere('e.created_by = :userId')
            ->setParameter('userId', $this->userHelper->getUser()->getId());
    }

    /**
     * @param QueryBuilder $q
     * @param array        $ids
     * @param string       $column
     * @param string       $prefix
     */
    protected function limitQueryToEmailIds(QueryBuilder $q, array $ids, $column, $prefix)
    {
        if (count($ids) === 0) {
            return;
        }

        if (count($ids) === 1) {
            $q->andWhere("$prefix.$column = :email_id");
            $q->setParameter('email_id', $ids[0]);

            return;
        }

        $q->andWhere("$prefix.$column IN (:email_ids)");
        $q->setParameter('email_ids', $ids, Connection::PARAM_INT_ARRAY);
    }

    /**
     * @param QueryBuilder   $q
     * @param StatCollection $statCollection
     *
     * @throws \Exception
     */
    protected function fetchAndBindToCollection(QueryBuilder $q, StatCollection $statCollection)
    {
        $results = $q->execute()->fetchAll();
        foreach ($results as $result) {
            $statCollection->addStatByDateTimeStringInUTC($result['date'], $result['count']);
        }
    }
}
