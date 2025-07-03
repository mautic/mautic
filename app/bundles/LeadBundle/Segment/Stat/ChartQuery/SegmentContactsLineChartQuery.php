<?php

namespace Mautic\LeadBundle\Segment\Stat\ChartQuery;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Segment\Exception\SegmentNotFoundException;

class SegmentContactsLineChartQuery extends ChartQuery
{
    /**
     * @var int
     */
    private $segmentId;

    private ?array $addedEventLogStats = null;

    private ?array $removedEventLogStats = null;

    /**
     * @param string|null $unit
     *
     * @throws SegmentNotFoundException
     */
    public function __construct(
        Connection $connection,
        \DateTime $dateFrom,
        \DateTime $dateTo,
        private array $filters = [],
        $unit = null,
    ) {
        $this->connection = $connection;
        $this->dateFrom   = $dateFrom;
        $this->dateTo     = $dateTo;
        $this->unit       = $unit;

        if (!isset($this->filters['leadlist_id']['value'])) {
            throw new SegmentNotFoundException('Segment ID required');
        }
        $this->segmentId  = $this->filters['leadlist_id']['value'];
        parent::__construct($connection, $dateFrom, $dateTo, $unit);
    }

    public function setDateRange(\DateTimeInterface $dateFrom, \DateTimeInterface $dateTo): void
    {
        parent::setDateRange($dateFrom, $dateTo);
        $this->init();
    }

    public function getTotalStats(int $total): array
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
     */
    public function getDataFromLeadEventLog($action): array
    {
        $qb = $this->prepareTimeDataQuery(
            'lead_event_log',
            'date_added',
            [
                'object'    => 'segment',
                'bundle'    => 'lead',
                'action'    => $action,
                'object_id' => $this->segmentId,
            ]
        );
        $qb = $this->optimizeSearchInLeadEventLog($qb);

        return $this->loadAndBuildTimeData($qb);
    }

    /**
     * @return int
     */
    public function getSegmentId()
    {
        return $this->segmentId;
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
        $this->addedEventLogStats   = $this->getDataFromLeadEventLog('added');
        $this->removedEventLogStats = $this->getDataFromLeadEventLog('removed');
    }

    private function optimizeSearchInLeadEventLog(QueryBuilder $qb): QueryBuilder
    {
        $fromPart             = $qb->getQueryPart('from');
        $fromPart[0]['alias'] = sprintf('%s USE INDEX (%s)', $fromPart[0]['alias'], MAUTIC_TABLE_PREFIX.LeadEventLog::INDEX_SEARCH);
        $qb->resetQueryPart('from');
        $qb->from($fromPart[0]['table'], $fromPart[0]['alias']);

        return $qb;
    }
}
