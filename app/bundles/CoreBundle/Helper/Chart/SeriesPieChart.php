<?php

namespace Mautic\CoreBundle\Helper\Chart;

class SeriesPieChart extends AbstractChart implements ChartInterface
{
    /**
     * Holds the suma of the all dataset values.
     *
     * @var float
     */
    protected $totalCount = 0;

    /**
     * @return array{labels: mixed[], datasets: mixed[]}
     */
    public function render(bool $withCounts = true): array
    {
        $dataset   = [];

        foreach ($this->datasets as $datasetId => $value) {
            $data        = ['data' => [], 'backgroundColor' => [], 'hoverBackgroundColor' => []];

            foreach ($value as $key => $item) {
                $color = $this->configureColorHelper($key);

                $data['data'][]                 = $item;
                $data['backgroundColor'][]      = $color->toRgba(0.8);
                $data['hoverBackgroundColor'][] = $color->toRgba(0.9);
            }

            $data['label'] = $this->labels[$datasetId];
            $dataset[]     = $data;
        }

        return [
            'labels'   => $this->labels,
            'datasets' => $dataset,
        ];
    }

    /**
     * Define a dataset by name and count number. Method will add the rest.
     *
     * @param int[] $value
     *
     * @return $this
     */
    public function setDataset(array $value): static
    {
        if (0 == $this->totalCount) {
            foreach ($value as $item) {
                $this->totalCount += $item;
            }
        }

        $this->datasets[] = $value;

        return $this;
    }

    /**
     * Adds to the label also the value and the percentage.
     *
     * @param string $label
     * @param int    $value
     *
     * @return string
     */
    public function buildFullLabel($label, $value)
    {
        if (!$this->totalCount) {
            return $label;
        }
        $percentage = round($value / $this->totalCount * 100, 2);

        return $label.'; '.$value.'x, '.$percentage.'%';
    }

    public function setTotalCount(float|int $totalCount): void
    {
        $this->totalCount = $totalCount;
    }

    /**
     * @param string[] $labels
     */
    public function setLabes(array $labels): void
    {
        $this->labels = $labels;
    }
}
