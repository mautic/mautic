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
    public function export(array $reportData, $name)
    {
        if (!class_exists(Spreadsheet::class)) {
            throw new \Exception('PHPSpreadsheet is required to export to Excel spreadsheets');
        }

        if (!array_key_exists('data', $reportData) || !array_key_exists('columns', $reportData)) {
            throw new \InvalidArgumentException("Keys 'data' and 'columns' have to be provided");
        }

        try {
            $objPHPExcel = new Spreadsheet();
            $objPHPExcel->getProperties()->setTitle($name);

            $objPHPExcel->createSheet();

            $header = [];

            $reportDataResult = new ReportDataResult($reportData);
            //build the data rows
            foreach ($reportDataResult->getData() as $count=>$data) {
                $row = [];
                foreach ($data as $k => $v) {
                    if (0 === $count) {
                        //set the header
                        foreach ($reportData['columns'] as $c) {
                            if ($c['alias'] == $k) {
                                $header[] = $c['label'];
                                break;
                            }
                        }
                    }
                    $row[] = htmlspecialchars_decode($this->formatterHelper->_($v, $reportData['columns'][$reportData['dataColumns'][$k]]['type'], true), ENT_QUOTES);
                }

                if (0 === $count) {
                    //write the column names row
                    $objPHPExcel->getActiveSheet()->fromArray($reportDataResult->getHeaders());
                }
                //write the row
                $rowCount = $count + 2;
                $objPHPExcel->getActiveSheet()->fromArray($row, null, "A{$rowCount}");
                //free memory
                unset($row, $reportData['data'][$count]);
            }

            $objWriter = IOFactory::createWriter($objPHPExcel, 'Xlsx');
            $objWriter->setPreCalculateFormulas(false);

            $objWriter->save('php://output');
        } catch (Exception $e) {
            throw new \Exception('PHPSpreadsheet Error', 0, $e);
        }
    }
}
