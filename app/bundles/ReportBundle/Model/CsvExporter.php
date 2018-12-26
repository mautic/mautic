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
use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use Mautic\ReportBundle\Crate\ReportDataResult;

/**
 * Class CsvExporter.
 */
class CsvExporter
{
    /**
     * @var FormatterHelper
     */
    protected $formatterHelper;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(FormatterHelper $formatterHelper, CoreParametersHelper $coreParametersHelper)
    {
        $this->formatterHelper      = $formatterHelper;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param ReportDataResult $reportDataResult
     * @param resource         $handle
     * @param int              $page
     */
    public function export(ReportDataResult $reportDataResult, $handle, $page = 1)
    {
        if ($page === 1) {
            $this->putHeader($reportDataResult, $handle);
        }

        foreach ($reportDataResult->getData() as $data) {
            $row = [];
            foreach ($data as $k => $v) {
                $type       = $reportDataResult->getType($k);
                $typeString = $type !== 'string';
                $row[]      = $typeString ? $this->formatterHelper->_($v, $type, true) : $v;
            }
            $this->putRow($handle, $row);
        }
    }

    /**
     * @param ReportDataResult $reportDataResult
     * @param resource         $handle
     */
    private function putHeader(ReportDataResult $reportDataResult, $handle)
    {
        $this->putRow($handle, $reportDataResult->getHeaders());
    }

    /**
     * @param resource $handle
     * @param array    $row
     */
    private function putRow($handle, array $row)
    {
        if ($this->coreParametersHelper->getParameter('csv_always_enclose')) {
            fputs($handle, '"'.implode('","', $row).'"'."\n");
        } else {
            fputcsv($handle, $row);
        }
    }
}
