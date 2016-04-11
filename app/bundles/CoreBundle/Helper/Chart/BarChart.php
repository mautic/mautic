<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper\Chart;

use Mautic\CoreBundle\Helper\Chart\AbstactChart;
use Mautic\CoreBundle\Helper\Chart\ChartInterface;

/**
 * Class BarChart
 */
class BarChart extends AbstractChart implements ChartInterface
{
    /**
     * Order
     *
     * @var string (ASC|DESC)
     */
    protected $order;

    /**
     * Configurable date format
     *
     * @var string
     */
    protected $dateFormat;

    /**
     * Match date/time unit to a humanly readable label
     * {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     *
     * @var array
     */
    protected $labelFormats = array(
        's' => 'H:i:s',
        'i' => 'H:i',
        'H' => 'M j ga',
        'd' => 'M j, y', 'D' => 'M j, y', // ('D' is BC. Can be removed when all charts use this class)
        'W' => 'W',
        'm' => 'M Y', 'M' => 'M Y', // ('M' is BC. Can be removed when all charts use this class)
        'Y' => 'Y',
    );

    /**
     * Defines the basic chart values, generates the time axe labels from it
     *
     * @param string   $unit {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @param string   $dateFormat
     * @param string   $order (DESC|ASC)
     */
    public function __construct($unit, $dateFrom, $dateTo, $dateFormat = null, $order = 'DESC')
    {
        $this->setDateRange($dateFrom, $dateTo);
        $this->unit  = !$unit ? $this->getTimeUnitFromDateRange() : $unit;
        $this->order = $order;
        $this->dateFormat = $dateFormat;
        $this->amount = $this->countAmountFromDateRange();
        $this->generateTimeLabels($this->amount, $order);
    }

    /**
     * Render chart data
     */
    public function render() {
        return array(
            'labels'   => $this->labels,
            'datasets' => $this->datasets
        );
    }

    /**
     * Define a dataset by name and data. Method will add the rest.
     *
     * @param  string $label
     * @param  array  $data
     *
     * @return $this
     */
    public function setDataset($label = null, array $data)
    {
        $datasetId = count($this->datasets);

        $baseData = array(
            'label' => $label,
            'data'  => $data,
        );
        
        $this->datasets[] = array_merge($baseData, $this->colors[$datasetId]);

        return $this;
    }

    /**
     * Generate array of labels from the form data
     *
     * @param  integer  $amount
     * @param  string   $order
     */
    public function generateTimeLabels($amount, $order = 'DESC')
    {
        if (!isset($this->labelFormats[$this->unit])) {
            throw new \UnexpectedValueException('Date/Time unit "' . $this->unit . '" is not available for a label.');
        }

        $date    = clone $this->dateTo;
        $oneUnit = $this->getUnitObject($this->unit);
        $format  = !empty($this->dateFormat) ? $this->dateFormat : $this->labelFormats[$this->unit];

        for ($i = 0; $i < $amount; $i++) {
            $this->labels[] = $date->format($format);
            if ($order == 'DESC') {
                $date->sub($oneUnit);
            } else {
                $date->add($oneUnit);
            }
        }
        
        $this->labels = array_reverse($this->labels);
    }
}
