<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Crate;

class ReportDataResult
{
    /**
     * @var int
     */
    private $totalResults;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var array
     */
    private $types = [];

    public function __construct(array $data)
    {
        if (
            !array_key_exists('data', $data) ||
            !array_key_exists('dataColumns', $data) ||
            !array_key_exists('columns', $data)
        ) {
            throw new \InvalidArgumentException("Keys 'data', 'dataColumns' and 'columns' have to be provided");
        }

        $this->totalResults = (int) $data['totalResults'];
        $this->data         = $data['data'];

        $this->buildHeader($data);
        $this->buildTypes($data);
    }

    /**
     * @return int
     */
    public function getTotalResults()
    {
        return $this->totalResults;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    public function getType($column)
    {
        return isset($this->types[$column]) ? $this->types[$column] : 'string';
    }

    private function buildHeader($data)
    {
        if (!isset($this->data[0])) {
            return;
        }

        $row = $this->data[0];
        foreach ($row as $k => $v) {
            $dataColumn = $data['dataColumns'][$k];

            $this->headers[] = $data['columns'][$dataColumn]['label'];
        }
    }

    private function buildTypes($data)
    {
        if (!isset($this->data[0])) {
            return;
        }

        $row = $this->data[0];
        foreach ($row as $k => $v) {
            $dataColumn = $data['dataColumns'][$k];

            $this->types[$k] = $data['columns'][$dataColumn]['type'];
        }
    }
}
