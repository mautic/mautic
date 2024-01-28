<?php

namespace Mautic\ReportBundle\Model;

use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use Mautic\ReportBundle\Crate\ReportDataResult;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Class CsvExporter.
 */
class ExcelExporter
{
    /**
     * @var FormatterHelper
     */
    protected $formatterHelper;

    public function __construct(FormatterHelper $formatterHelper)
    {
        $this->formatterHelper = $formatterHelper;
    }

    /**
     * @param string $name
     *
     * @throws \Exception
     */
    public function export(ReportDataResult $reportDataResult, $name, string $output = 'php://output')
    {
        if (!class_exists(Spreadsheet::class)) {
            throw new \Exception('PHPSpreadsheet is required to export to Excel spreadsheets');
        }

        try {
            $objPHPExcel = new Spreadsheet();
            $objPHPExcel->getProperties()->setTitle($name);
            $objPHPExcel->createSheet();

            //build the data rows
            foreach ($reportDataResult->getData() as $count=>$data) {
                $row = [];
                foreach ($data as $k => $v) {
                    $type      = $reportDataResult->getType($k);
                    $formatted = htmlspecialchars_decode($this->formatterHelper->_($v, $type, true), ENT_QUOTES);
                    $row[]     = $formatted;
                }

                if (0 === $count) {
                    //write the column names row
                    $objPHPExcel->getActiveSheet()->fromArray($reportDataResult->getHeaders());
                }
                //write the row
                $rowCount = $count + 2;
                $objPHPExcel->getActiveSheet()->fromArray($row, null, "A{$rowCount}");
                //free memory
                unset($row);
            }

            $objWriter = IOFactory::createWriter($objPHPExcel, 'Xlsx');
            $objWriter->setPreCalculateFormulas(false);

            $objWriter->save($output);
        } catch (Exception $e) {
            throw new \Exception('PHPSpreadsheet Error', 0, $e);
        }
    }
}
