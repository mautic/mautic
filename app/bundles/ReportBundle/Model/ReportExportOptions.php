<?php

namespace Mautic\ReportBundle\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

final class ReportExportOptions
{
    private readonly int $batchSize;

    private int $page;

    /**
     * @var \DateTimeInterface
     */
    private $dateFrom;

    /**
     * @var \DateTimeInterface
     */
    private $dateTo;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->batchSize = $coreParametersHelper->get('report_export_batch_size');
        $this->page      = 1;
    }

    public function beginExport(): void
    {
        $this->page = 1;
    }

    public function nextBatch(): void
    {
        ++$this->page;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getNumberOfProcessedResults(): int
    {
        return $this->page * $this->batchSize;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * @param \DateTime $dateFrom
     */
    public function setDateFrom($dateFrom): void
    {
        $this->dateFrom = $dateFrom;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateTo()
    {
        return $this->dateTo;
    }

    /**
     * @param \DateTime $dateTo
     */
    public function setDateTo($dateTo): void
    {
        $this->dateTo = $dateTo;
    }
}
