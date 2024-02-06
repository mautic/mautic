<?php

namespace Mautic\CoreBundle\Helper\Chart;

class BarChart extends AbstractChart implements ChartInterface
{
    /**
     * Defines the basic chart values, generates the time axe labels from it.
     */
    public function __construct(array $labels)
    {
        $this->labels = $labels;
    }

    /**
     * @return array{labels: mixed[], datasets: mixed[]}
     */
    public function render(): array
    {
        ksort($this->datasets);

        return [
            'labels'   => $this->labels,
            'datasets' => $this->datasets,
        ];
    }

    /**
     * Define a dataset by name and data. Method will add the rest.
     *
     * @param string $label
     * @param int    $order
     *
     * @return $this
     */
    public function setDataset($label, array $data, $order = null)
    {
        $datasetId = count($this->datasets);

        $baseData = [
            'label' => $label,
            'data'  => $data,
        ];

        if (null === $order) {
            $order = count($this->datasets);
        }

        $this->datasets[$order] = array_merge($baseData, $this->generateColors($datasetId));

        return $this;
    }

    /**
     * Generate unique color for the dataset.
     *
     * @param int $datasetId
     */
    public function generateColors($datasetId): array
    {
        $color = $this->configureColorHelper($datasetId);

        return [
            'fill'                      => true,
            'backgroundColor'           => $color->toRgba(0.7),
            'borderColor'               => $color->toRgba(0.8),
            'pointHoverBackgroundColor' => $color->toRgba(0.9),
            'pointHoverBorderColor'     => $color->toRgba(1),
        ];
    }
}
