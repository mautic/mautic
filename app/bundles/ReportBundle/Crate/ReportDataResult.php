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

    /**
     * @var array
     */
    private $totals = [];

    /**
     * @var array
     */
    private $columnKeys = [];

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

        $this->buildColumnKeys();
        $this->buildHeader($data);
        $this->buildTypes($data);
        $this->buildTotals($data['aggregatorColumns'] ?? []);
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
     * @return array
     */
    public function getTotals()
    {
        return $this->totals;
    }

    /**
     * @return array
     */
    public function getTotalsToExport()
    {
        if (empty($this->totals)) {
            return [];
        }

        foreach ($this->columnKeys as $key) {
            $totalsRow[$key] = $this->totals[$key] ?? '';
        }

        return $totalsRow ?? [];
    }

    /**
     * @param array $data
     */
    private function buildHeader($data)
    {
        if (empty($this->columnKeys)) {
            return;
        }

        foreach ($this->columnKeys as $k) {
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
        if (empty($this->columnKeys)) {
            return;
        }

        foreach ($this->columnKeys as $k) {
            if (isset($data['aggregatorColumns']) && array_key_exists($k, $data['aggregatorColumns'])) {
                $this->types[$k] = ('AVG' === substr($k, 0, 3)) ? 'float' : 'int';
            } else {
                $dataColumn      = $data['dataColumns'][$k];
                $this->types[$k] = $data['columns'][$dataColumn]['type'];
            }
        }
    }

    /**
     * @return float
     */
    private function calcTotal(string $calcFunction, float $cellVal, float $previousVal, int $avgCounter)
    {
        switch ($calcFunction) {
            case 'COUNT':
            case 'SUM':
                return $previousVal + $cellVal;
            case 'AVG':
                return ($avgCounter == $this->totalResults) ? round(($previousVal + $cellVal) / $this->totalResults, 4) : $previousVal + $cellVal;
            case 'MAX':
                return ($cellVal >= $previousVal) ? $cellVal : $previousVal;
            case 'MIN':
                return ($cellVal <= $previousVal) ? $cellVal : $previousVal;
            default:
                return $previousVal;
        }
    }

    /**
     * @return void
     */
    private function buildTotals(array $aggregators)
    {
        if ($aggregators) {
            $avgCounter   = 0;
            $this->totals = [];

            for ($i = 0; $i < $this->totalResults; ++$i) {
                ++$avgCounter;

                foreach ($aggregators as $j => $v) {
                    if ($cellVal = $this->data[$i][$j] ?? null) {
                        $calcFunc         = $this->getAggregatorCalcFunc($j, $v);
                        $this->totals[$j] = $this->calcTotal($calcFunc, $cellVal, $this->totals[$j] ?? 0, $avgCounter);
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    private function getAggregatorCalcFunc(string $index, string $value)
    {
        return trim(str_replace($value, '', $index));
    }

    private function buildColumnKeys()
    {
        if (!isset($this->data[0])) {
            $this->columnKeys = [];

            return;
        }

        $row              = $this->data[0];
        $this->columnKeys =  array_keys($row);
    }
}
