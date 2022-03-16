<?php

namespace Mautic\ReportBundle\Event;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ReportBundle\Entity\Report;

/**
 * Class ReportGeneratorEvent.
 */
class ReportGraphEvent extends AbstractReportEvent
{
    /**
     * @var array
     */
    private $requestedGraphs = [];

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * Constructor.
     */
    public function __construct(Report $report, array $graphs, QueryBuilder $queryBuilder)
    {
        $this->report          = $report;
        $this->context         = $report->getSource();
        $this->requestedGraphs = $graphs;
        $this->queryBuilder    = $queryBuilder;
    }

    /**
     * Fetch the graphs.
     *
     * @return array
     */
    public function getGraphs()
    {
        return $this->requestedGraphs;
    }

    /**
     * Set the graph array.
     *
     * @param string $graph
     * @param array  $data  prepared for this chart
     */
    public function setGraph($graph, $data)
    {
        if (!isset($this->requestedGraphs[$graph]['data'])) {
            $this->requestedGraphs[$graph]['data'] = [];
        }
        $this->requestedGraphs[$graph]['data'] = $data;
    }

    /**
     * Fetch the options array for the graph.
     *
     * @return array
     */
    public function getOptions($graph)
    {
        if (isset($this->requestedGraphs[$graph]['options'])) {
            return $this->requestedGraphs[$graph]['options'];
        }

        return [];
    }

    /**
     * Set an option for the graph.
     *
     * @param string $graph
     * @param string $key
     * @param string $value
     */
    public function setOption($graph, $key, $value)
    {
        if (!isset($this->requestedGraphs[$graph]['options'])) {
            $this->requestedGraphs[$graph]['options'] = [];
        }
        $this->requestedGraphs[$graph]['options'][$key] = $value;
    }

    /**
     * Set the options for a graph.
     *
     * @param string $graph
     * @param array  $options
     */
    public function setOptions($graph, $options)
    {
        $this->requestedGraphs[$graph]['options'] = $options;
    }

    /**
     * Get graphs that are requested.
     *
     * @return array
     */
    public function getRequestedGraphs()
    {
        return array_keys($this->requestedGraphs);
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }
}
