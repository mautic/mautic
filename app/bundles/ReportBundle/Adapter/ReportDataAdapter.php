<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Adapter;

use Mautic\ReportBundle\Crate\ReportDataResult;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Model\ReportExportOptions;
use Mautic\ReportBundle\Model\ReportModel;

class ReportDataAdapter
{
    /**
     * @var ReportModel
     */
    private $reportModel;

    public function __construct(ReportModel $reportModel)
    {
        $this->reportModel = $reportModel;
    }

    public function getReportData(Report $report, ReportExportOptions $reportExportOptions)
    {
        $options                    = [];
        $options['paginate']        = true;
        $options['limit']           = $reportExportOptions->getBatchSize();
        $options['ignoreGraphData'] = true;
        $options['page']            = $reportExportOptions->getPage();

        $data = $this->reportModel->getReportData($report, null, $options);

        return new ReportDataResult($data);
    }
}
