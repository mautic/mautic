<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Event;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ReportDataEvent
 */
class ReportDataEvent extends Event
{

    /**
     * Report entity
     *
     * @var Report
     */
    private $report;

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
     * @var string
     */
    private $context;

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
     * Retrieve the event context
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return Report
     */
    public function getReport()
    {
        return $this->report;
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

    /**
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->options['translator'];
    }
}
