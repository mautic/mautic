<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class ReportExportOptions
{
    /**
     * @var int
     */
    private $batchSize;

    /**
     * @var int
     */
    private $page;

    /**
     * @var \DateTime
     */
    private $dateFrom;

    /**
     * @var \DateTime
     */
    private $dateTo;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->batchSize = $coreParametersHelper->getParameter('report_export_batch_size');
        $this->page      = 1;
    }

    public function beginExport()
    {
        $this->page = 1;
    }

    public function nextBatch()
    {
        ++$this->page;
    }

    /**
     * @return int
     */
    public function getBatchSize()
    {
        return $this->batchSize;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getNumberOfProcessedResults()
    {
        return $this->page * $this->getBatchSize();
    }

    /**
     * @return \DateTime
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * @param \DateTime $dateFrom
     */
    public function setDateFrom($dateFrom)
    {
        $this->dateFrom = $dateFrom;
    }

    /**
     * @return \DateTime
     */
    public function getDateTo()
    {
        return $this->dateTo;
    }

    /**
     * @param \DateTime $dateTo
     */
    public function setDateTo($dateTo)
    {
        $this->dateTo = $dateTo;
    }
}
