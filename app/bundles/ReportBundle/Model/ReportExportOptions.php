<?php

namespace Mautic\ReportBundle\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

final class ReportExportOptions
{
    private readonly int $batchSize;

    private int $page = 1;

    private ?\DateTime $dateFrom = null;

    private ?\DateTime $dateTo = null;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->batchSize = $coreParametersHelper->get('report_export_batch_size');
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

    public function setDateFrom(\DateTime $dateFrom): void
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

    public function setDateTo(\DateTime $dateTo): void
    {
        $this->dateTo = $dateTo;
    }
}
