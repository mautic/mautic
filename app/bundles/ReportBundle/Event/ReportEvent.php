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

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\ReportBundle\Entity\Report;

/**
 * Class ReportEvent.
 */
class ReportEvent extends CommonEvent
{
    /**
     * @param Report $report
     * @param bool   $isNew
     */
    public function __construct(Report $report, $isNew = false)
    {
        $this->entity = &$report;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Report entity.
     *
     * @return Report
     */
    public function getReport()
    {
        return $this->entity;
    }

    /**
     * Sets the Report entity.
     *
     * @param Report $report
     */
    public function setReport(Report $report)
    {
        $this->entity = $report;
    }
}
