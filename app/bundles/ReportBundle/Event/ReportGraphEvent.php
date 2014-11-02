<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Event;

use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ReportGeneratorEvent
 */
class ReportGraphEvent extends Event
{
    /**
     * Array of graphs
     *
     * @var QueryBuilder
     */
    private $graphs = array();

    /**
     * Report entity
     *
     * @var Report
     */
    private $report;

    /**
     * Constructor
     *
     * @param Report $report Entity
     */
    public function __construct($report)
    {
        $this->report = $report;
    }

    /**
     * Retrieve the event context
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Fetch the QueryBuilder object
     *
     * @return array
     */
    public function getGraphs()
    {
        return $this->graphs;
    }

    /**
     * Set the QueryBuilder object
     *
     * @param string $type (line, bar, pie, ..)
     * @param array $data prepared for this chart
     *
     * @return array
     */
    public function setGraph($type, $data)
    {
        if (!isset($this->graph[$type])) {
            $this->graphs[$type] = array();
        }
        $this->graphs[$type][] = $data;
    }

    /**
     * Report Entity
     *
     * @return Report
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * Build where clause according to Report filter settings
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function buildWhere(\Doctrine\DBAL\Query\QueryBuilder &$queryBuilder)
    {
        // Add filters as AND values to the WHERE clause if present
        $filters = $this->report->getFilters();

        if (count($filters)) {
            $expr = $queryBuilder->expr();
            $and  = $expr->andX();

            foreach ($filters as $filter) {
                if ($filter['condition'] == 'notEmpty') {
                    $and->add(
                        $expr->isNotNull($filter['column'])
                    );
                    $and->add(
                        $expr->neq($filter['column'], $expr->literal(''))
                    );
                } elseif ($filter['condition'] == 'empty') {
                    $and->add(
                        $expr->isNull($filter['column'])
                    );
                    $and->add(
                        $expr->eq($filter['column'], $expr->literal(''))
                    );
                } else {
                    $and->add(
                        $expr->{$filter['condition']}($filter['column'], $expr->literal($filter['value']))
                    );
                }
            }

            $queryBuilder->where($and);
        }
    }
}
