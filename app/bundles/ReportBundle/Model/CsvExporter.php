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
class CsvExporter
{
    /**
     * @var FormatterHelper
     */
    protected $formatterHelper;

    public function __construct(FormatterHelper $formatterHelper)
    {
        $this->formatterHelper = $formatterHelper;
    }

    public function export(array $reportData, $handle, $page)
    {
        if (!array_key_exists('data', $reportData) || !array_key_exists('columns', $reportData)) {
            throw new \InvalidArgumentException("Keys 'data' and 'columns' have to be provided");
        }

        foreach ($reportData['data'] as $count => $data) {
            if ($count === 0 && $page === 1) {
                $this->putHeader($data, $handle);
            }

            $row = [];
            foreach ($data as $k => $v) {
                $type       = $reportData['columns'][$reportData['dataColumns'][$k]]['type'];
                $typeString = $type !== 'string';

                $row[] = $typeString ? $this->formatterHelper->_($v, $type, true) : $v;
            }

            fputcsv($handle, $row);
            unset($row, $reportData['data'][$count]);
        }
    }

    private function putHeader(array $data, $handle)
    {
        $header = [];
        foreach ($data as $k => $v) {
            $header[] = $k;
        }

        fputcsv($handle, $header);
    }
}
