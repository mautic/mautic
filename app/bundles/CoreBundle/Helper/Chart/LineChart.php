<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper\Chart;

/**
 * Class LineChart.
 *
 * Line chart requires the same data as Bar chart
 */
class LineChart extends AbstractChart implements ChartInterface
{
    /**
     * Configurable date format.
     *
     * @var string
     */
    protected $dateFormat;

    /**
     * Match date/time unit to a humanly readable label
     * {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}.
     *
     * @var array
     */
    protected $labelFormats = [
        's' => 'H:i:s',
        'i' => 'H:i',
        'H' => 'M j ga',
        'd' => 'M j, y',
        'D' => 'M j, y', // ('D' is BC. Can be removed when all charts use this class)
        'W' => '\W\e\e\k W', // (Week is escaped here so it's not interpreted when creating labels)
        'm' => 'M Y',
        'M' => 'M Y', // ('M' is BC. Can be removed when all charts use this class)
        'Y' => 'Y',
    ];

    /**
     * Defines the basic chart values, generates the time axe labels from it.
     *
     * @param string    $unit       {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string    $dateFormat
     */
    public function __construct($unit = null, $dateFrom = null, $dateTo = null, $dateFormat = null)
    {
        $this->unit       = (null === $unit) ? $this->getTimeUnitFromDateRange($dateFrom, $dateTo) : $unit;
        $this->isTimeUnit = (in_array($this->unit, ['H', 'i', 's']));
        $this->setDateRange($dateFrom, $dateTo);

        $this->dateFormat = $dateFormat;
        $this->amount     = $this->countAmountFromDateRange();
        $this->generateTimeLabels($this->amount);
        $this->addOneUnitMinusOneSec($this->dateTo);
    }

    /**
     * Render chart data.
     */
    public function render()
    {
        return [
            'labels'   => $this->labels,
            'datasets' => $this->datasets,
        ];
    }

    /**
     * Define a dataset by name and data. Method will add the rest.
     *
     * @param string $label
     * @param array  $data
     *
     * @return $this
     */
    public function setDataset($label, array $data)
    {
        $datasetId = count($this->datasets);

        $baseData = [
            'label' => $label,
            'data'  => $data,
        ];

        $this->datasets[] = array_merge($baseData, $this->generateColors($datasetId));

        return $this;
    }

    /**
     * Generate array of labels from the form data.
     *
     * @param int $amount
     */
    public function generateTimeLabels($amount)
    {
        if (!isset($this->labelFormats[$this->unit])) {
            throw new \UnexpectedValueException('Date/Time unit "'.$this->unit.'" is not available for a label.');
        }

        $date    = clone $this->dateFrom;
        $oneUnit = $this->getUnitInterval();
        $format  = !empty($this->dateFormat) ? $this->dateFormat : $this->labelFormats[$this->unit];

        for ($i = 0; $i < $amount; ++$i) {
            $this->labels[] = $date->format($format);

            // Special case for months because PHP behaves weird with February
            if ($this->unit === 'm') {
                $date->modify('first day of next month');
            } else {
                $date->add($oneUnit);
            }
        }

        $this->labels = $this->labels;
    }

    /**
     * Generate unique color for the dataset.
     *
     * @param int $datasetId
     *
     * @return array
     */
    public function generateColors($datasetId)
    {
        $color = $this->configureColorHelper($datasetId);

        return [
            'backgroundColor'           => $color->toRgba(0.1),
            'borderColor'               => $color->toRgba(0.8),
            'pointHoverBackgroundColor' => $color->toRgba(0.75),
            'pointHoverBorderColor'     => $color->toRgba(1),
        ];
    }
}
