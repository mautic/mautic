<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Event;

use Mautic\ReportBundle\Entity\Report;

/**
 * Class ReportDataEvent.
 */
class ReportDataEvent extends AbstractReportEvent
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var int
     */
    private $totalResults = 0;

    /**
     * ReportDataEvent constructor.
     *
     * @param Report $report
     * @param array  $data
     * @param        $totalResults
     * @param array  $options
     */
    public function __construct(Report $report, array $data, $totalResults, array $options)
    {
        $this->context      = $report->getSource();
        $this->report       = $report;
        $this->data         = $data;
        $this->options      = $options;
        $this->totalResults = (int) $totalResults;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     *
     * @return ReportDataEvent
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return int
     */
    public function getTotalResults()
    {
        return $this->totalResults;
    }
}
