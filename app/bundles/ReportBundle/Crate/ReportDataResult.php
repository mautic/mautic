<?php

namespace Mautic\ReportBundle\Crate;

use DateTime;
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
     * @var DateTime|null
     */
    private $dateFrom;

    /**
     * @var DateTime|null
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
        $this->dateFrom     = $data['dateFrom'] ?? null;
        $this->dateTo       = $data['dateTo'] ?? null;
        $this->limit        = isset($data['limit']) ? (int) $data['limit'] : null;
        $this->page         = isset($data['page']) ? (int) $data['page'] : 1;
        $this->isLastBatch  = $isLastBatch;
        $this->columnKeys   = isset($this->data[0]) ? array_keys($this->data[0]) : [];

        // Use the calculated totals for previous batch to continue
        $this->preBatchSize = $preBatchSize;
        $this->totals       = $preTotals;

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
     * @return DateTime|null
     */
    public function getDateTo()
    {
        return $this->dateTo;
    }

    /**
     * @return DateTime|null
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
     * @return array<int, string>
     */
    public function getColumnKeys()
    {
        return $this->columnKeys;
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
    public function calcTotal(string $calcFunction, int $rowsCount, array &$aggregatorVal, ?float $previousVal = null)
    {
        switch ($calcFunction) {
            case 'COUNT':
            case 'SUM':
                return ($previousVal ?? 0) + array_sum($aggregatorVal);
            case 'AVG':
                $sum = ($previousVal ?? 0) + array_sum($aggregatorVal);
                if ($this->isLastBatch) {
                    return round($sum / $rowsCount, FormatterHelper::FLOAT_PRECISION);
                }

                return $sum;
            case 'MAX':
                if (!is_null($previousVal)) {
                    $aggregatorVal[] = $previousVal;
                }

                return max($aggregatorVal);
            case 'MIN':
                if (!is_null($previousVal)) {
                    $aggregatorVal[] = $previousVal;
                }

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
                    $this->totals[$j] = $this->calcTotal($calcFunc, $dataCount, $aggregatorVal, $this->totals[$j] ?? null);
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
}
