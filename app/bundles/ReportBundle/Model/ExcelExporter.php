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
     * @param array  $reportData
     * @param string $name
     *
     * @throws \Exception
     */
    public function export(array $reportData, $name)
    {
        if (!class_exists('PHPExcel')) {
            throw new \Exception('PHPExcel is required to export to Excel spreadsheets');
        }

        if (!array_key_exists('data', $reportData) || !array_key_exists('columns', $reportData)) {
            throw new \InvalidArgumentException("Keys 'data' and 'columns' have to be provided");
        }

        try {
            $objPHPExcel = new \PHPExcel();
            $objPHPExcel->getProperties()->setTitle($name);

            $objPHPExcel->createSheet();

            $header = [];

            //build the data rows
            foreach ($reportData['data'] as $count => $data) {
                $row = [];
                foreach ($data as $k => $v) {
                    if ($count === 0) {
                        //set the header
                        $header[] = $k;
                    }
                    $row[] = htmlspecialchars_decode($this->formatterHelper->_($v, $reportData['columns'][$reportData['dataColumns'][$k]]['type'], true), ENT_QUOTES);
                }

                if ($count === 0) {
                    //write the column names row
                    $objPHPExcel->getActiveSheet()->fromArray($header);
                }
                //write the row
                $rowCount = $count + 2;
                $objPHPExcel->getActiveSheet()->fromArray($row, null, "A{$rowCount}");
                //free memory
                unset($row, $reportData['data'][$count]);
            }

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->setPreCalculateFormulas(false);

            $objWriter->save('php://output');
        } catch (\PHPExcel_Exception $e) {
            throw new \Exception('PHPExcel Error', 0, $e);
        }
    }
}
