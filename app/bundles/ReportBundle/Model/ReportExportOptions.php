<?php

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
        $this->batchSize = $coreParametersHelper->get('report_export_batch_size');
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
