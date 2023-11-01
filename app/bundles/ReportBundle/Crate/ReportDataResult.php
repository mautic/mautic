<?php

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

    /**
     * @param string $column
     *
     * @return string
     */
    public function getType($column)
    {
        return isset($this->types[$column]) ? $this->types[$column] : 'string';
    }

    /**
     * @param array $data
     */
    private function buildHeader($data)
    {
        if (!isset($this->data[0])) {
            return;
        }

        $row = $this->data[0];
        foreach ($row as $k => $v) {
            $dataColumn      = $data['dataColumns'][$k];
            $label           = $data['columns'][$dataColumn]['label'];

            // Aggregated column
            if (isset($data['aggregatorColumns'][$k])) {
                $this->headers[] = str_replace($dataColumn, $label, $k);
            } else {
                $this->headers[] = $data['columns'][$dataColumn]['label'];
            }
        }
    }

    /**
     * @param array $data
     */
    private function buildTypes($data)
    {
        if (!isset($this->data[0])) {
            return;
        }

        $row = $this->data[0];
        foreach ($row as $k => $v) {
            if (isset($data['aggregatorColumns']) && array_key_exists($k, $data['aggregatorColumns'])) {
                $this->types[$k] = 'int';
            } else {
                $dataColumn      = $data['dataColumns'][$k];
                $this->types[$k] = $data['columns'][$dataColumn]['type'];
            }
        }
    }
}
