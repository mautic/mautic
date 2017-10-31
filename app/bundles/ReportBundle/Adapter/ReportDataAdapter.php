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

    public function getRportData(Report $report)
    {
        $options = [];
        //$options['paginate'] = true;
        //$options['limit'] = 10000;
        $options['ignoreGraphData'] = true;

        $data = $this->reportModel->getReportData($report, null, $options);

        return new ReportDataResult($data);
    }
}
