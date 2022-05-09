<?php

namespace Mautic\ReportBundle\Adapter;

use Mautic\ReportBundle\Crate\ReportDataResult;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Model\ReportExportOptions;
use Mautic\ReportBundle\Model\ReportModel;

class ReportDataAdapter
{
    public function __construct(
        private ReportModel $reportModel,
    ) {
    }

    public function getReportData(Report $report, ReportExportOptions $reportExportOptions): ReportDataResult
    {
        $exportData                 = $reportExportOptions->getData();
        $options                    = [];
        $options['paginate']        = true;
        $options['limit']           = $reportExportOptions->getBatchSize();
        $options['ignoreGraphData'] = true;
        $options['page']            = $reportExportOptions->getPage();
        $options['dateTo']          = $reportExportOptions->getDateTo();
        $options['dateFrom']        = $reportExportOptions->getDateFrom();
        if (!empty($exportData['dynamicFilters'])) {
            $options['dynamicFilters']  = $exportData['dynamicFilters'];
        }

        $data = $this->reportModel->getReportData($report, null, $options);

        return new ReportDataResult($data);
    }
}
