<?php

namespace Mautic\ReportBundle\Crate;

use Mautic\CoreBundle\Templating\Helper\FormatterHelper;

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
     * @var array<mixed>
     */
    private $totals = [];

    /**
     * @var array<string>
     */
    private $columnKeys = [];

    /**
     * @var array<mixed>
     */
    private $graphs = [];

    /**
     * @var string
     */
    private $dateFrom;

    /**
     * @var string
     */
    private $dateTo;

    /**
     * @var int|null
     */
    private $limit;

    /**
     * @var int
     */
    private $page;

    /**
     * @var int
     */
    private $preBatchSize;

    /**
     * @var bool
     */
    private $isLastBatch;

    /**
     * @param array<mixed> $data
     * @param array<mixed> $preTotals
     */
    public function __construct(array $data, array $preTotals = [], int $preBatchSize = 0, bool $isLastBatch = true)
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
        $this->graphs       = $data['graphs'] ?? [];
        $this->dateFrom     = $data['dateFrom'];
        $this->dateTo       = $data['dateTo'];
        $this->limit        = $data['limit'] ? (int) $data['limit'] : null;
        $this->page         = $data['page'] ? (int) $data['page'] : 1;
        $this->isLastBatch  = $isLastBatch;

        // Use the calculated totals for previous batch to continue
        $this->preBatchSize = $preBatchSize;
        $this->totals       = $preTotals;

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
     * @return int
     */
    public function getDataCount()
    {
        return count($this->data);
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
     * @return array<mixed>
     */
    public function getTotals()
    {
        return $this->totals;
    }

    /**
     * @return array<mixed>
     */
    public function getGraphs()
    {
        return $this->graphs;
    }

    /**
     * @return string
     */
    public function getDateTo()
    {
        return $this->dateTo;
    }

    /**
     * @return string
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * @return array<string>
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
     * @return bool
     */
    public function isLastPage()
    {
        // No limit set
        if (empty($this->limit)) {
            return true;
        }

        return $this->page == ceil($this->totalResults / $this->limit);
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
     * @param array<mixed> $aggregatorVal
     *
     * @return float
     */
    private function calcTotal(string $calcFunction, float $previousVal, array &$aggregatorVal, int $rowsCount)
    {
        switch ($calcFunction) {
            case 'COUNT':
            case 'SUM':
                return $previousVal + array_sum($aggregatorVal);
            case 'AVG':
                $sum= $previousVal + array_sum($aggregatorVal);
                if ($this->isLastBatch) {
                    return round($sum / $rowsCount, FormatterHelper::FLOAT_PRECISION);
                }

                return $sum;
            case 'MAX':
                $aggregatorVal[] = $previousVal;

                return max($aggregatorVal);
            case 'MIN':
                $aggregatorVal[] = $previousVal;

                return min($aggregatorVal);
            default:
                return $previousVal;
        }
    }

    /**
     * @param array<string> $aggregators
     *
     * @return void
     */
    private function buildTotals(array $aggregators)
    {
        $dataCount = count($this->data) + $this->preBatchSize;

        if ($aggregators && !empty(array_keys($this->data))) {
            foreach ($aggregators as $j => $v) {
                $aggregatorVal = array_column($this->data, $j);

                if ($aggregatorVal) {
                    $calcFunc         = $this->getAggregatorCalcFunc($j, $v);
                    $this->totals[$j] = $this->calcTotal($calcFunc, $this->totals[$j] ?? 0, $aggregatorVal, $dataCount);
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

    /**
     * @return void
     */
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
