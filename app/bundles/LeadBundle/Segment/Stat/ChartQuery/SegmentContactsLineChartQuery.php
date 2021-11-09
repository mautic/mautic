<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Stat\ChartQuery;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\LeadBundle\Segment\Exception\SegmentNotFoundException;

class SegmentContactsLineChartQuery extends ChartQuery
{
    /**
     * @var array
     */
    private $filters;

    /**
     * @var int
     */
    private $segmentId;

    /**
     * @var bool|string
     */
    private $firstEventLog;

    /**
     * @var array
     */
    private $addedEventLogStats;

    /**
     * @var array
     */
    private $removedEventLogStats;

    /**
     * @var array
     */
    private $addedLeadListStats;

    /**
     * @var bool
     */
    private $statsFromEventLog;

    /**
     * @param string|null $unit
     *
     * @throws SegmentNotFoundException
     */
    public function __construct(Connection $connection, \DateTime $dateFrom, \DateTime $dateTo, array $filters = [], $unit = null)
    {
        $this->connection = $connection;
        $this->dateFrom   = $dateFrom;
        $this->dateTo     = $dateTo;
        $this->unit       = $unit;
        $this->filters    = $filters;

        if (!isset($this->filters['leadlist_id']['value'])) {
            throw new SegmentNotFoundException('Segment ID required');
        }
        $this->segmentId  = $this->filters['leadlist_id']['value'];
        parent::__construct($connection, $dateFrom, $dateTo, $unit);
    }

    public function setDateRange(\DateTime $dateFrom, \DateTime $dateTo)
    {
        parent::setDateRange($dateFrom, $dateTo);
        $this->init();
    }

    /**
     * @return array
     */
    public function getTotalStats(int $total)
    {
        $totalCountDateTo = $this->getTotalToDateRange($total);
        // count array SUM and then reverse
        // require start from end and  substract added/removed logs
        $sums     = array_reverse(ArrayHelper::sub($this->getAddedEventLogStats(), $this->getRemovedEventLogStats()));
        $totalSum = 0;
        $totals   = array_map(function ($sum) use ($totalCountDateTo, &$totalSum) {
            $total = $totalCountDateTo - $totalSum;
            $totalSum += $sum;
            if ($total > -1) {
                return $total;
            } else {
                return 0;
            }
        }, $sums);

        return array_reverse($totals);
    }

    /**
     * Return total of contact to date end of graph.
     */
    private function getTotalToDateRange(int $total): int
    {
        $queryForTotal = clone $this;
        // try figure out total count in dateTo
        $queryForTotal->setDateRange($this->dateTo, new \DateTime());

        return $total - array_sum(ArrayHelper::sub($queryForTotal->getAddedEventLogStats(), $queryForTotal->getRemovedEventLogStats()));
    }

    /**
     * Get data about add/remove from segment based on LeadEventLog.
     *
     * @param string $action
     *
     * @return array
     */
    public function getDataFromLeadEventLog($action)
    {
        return $this->loadAndBuildTimeData(
            $this->prepareTimeDataQuery(
                'lead_event_log',
                'date_added',
                [
                    'object'    => 'segment',
                    'bundle'    => 'lead',
                    'action'    => $action,
                    'object_id' => $this->segmentId,
                ]
            )
        );
    }

    /**
     * Get data about add from segment based on LeadListLead before upgrade to 2.15.
     *
     * @return array
     */
    public function getDataFromLeadListLeads()
    {
        $q = $this->prepareTimeDataQuery('lead_lists_leads', 'date_added', $this->filters);
        if ($this->firstEventLog) {
            $q->andWhere($q->expr()->lt('t.date_added', $q->expr()->literal($this->firstEventLog)));
        }

        return  $this->loadAndBuildTimeData($q);
    }

    /**
     * @return bool|string
     */
    private function getFirstDateAddedSegmentEventLog()
    {
        $subQuery = $this->connection->createQueryBuilder();
        $subQuery->select('MIN(el.date_added) - INTERVAL 10 SECOND')
            ->from(MAUTIC_TABLE_PREFIX.'lead_event_log', 'el')
            ->where(
                $subQuery->expr()->andX(
                    $subQuery->expr()->eq('el.object', $subQuery->expr()->literal('segment')),
                    $subQuery->expr()->eq('el.bundle', $subQuery->expr()->literal('lead')),
                    $subQuery->expr()->eq('el.object_id', $this->segmentId)
                )
            );

        return $subQuery->execute()->fetchColumn();
    }

    /**
     * @return int
     */
    public function getSegmentId()
    {
        return $this->segmentId;
    }

    /**
     * @return bool
     */
    public function isStatsFromEventLog()
    {
        return $this->statsFromEventLog;
    }

    /**
     * @return array
     */
    public function getAddedEventLogStats()
    {
        return $this->addedEventLogStats;
    }

    /**
     * @return array
     */
    public function getRemovedEventLogStats()
    {
        return $this->removedEventLogStats;
    }

    /**
     * Init basic stats.
     */
    private function init(): void
    {
        $this->firstEventLog        = $this->getFirstDateAddedSegmentEventLog();
        $this->addedLeadListStats   = $this->getDataFromLeadListLeads();
        $this->addedEventLogStats   = $this->getDataFromLeadEventLog('added');
        $this->removedEventLogStats = $this->getDataFromLeadEventLog('removed');
        $this->statsFromEventLog    = (
            empty(array_filter($this->addedLeadListStats))
            && (!empty(array_filter($this->addedEventLogStats))
            || !empty(array_filter($this->removedEventLogStats)))
        );
    }
}
