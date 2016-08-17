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
 * Class PieChart
 */
class PieChart extends AbstractChart implements ChartInterface
{
    /**
     * Holds the suma of the all dataset values
     *
     * @var float
     */
    protected $totalCount = 0;

    /**
     * Render chart data
     */
    public function render($withCounts = true)
    {
        $data = array('data' => array(), 'backgroundColor' => array(), 'hoverBackgroundColor' => array());

        foreach ($this->datasets as $datasetId => $value) {
            $color = $this->configureColorHelper($datasetId);
            $data['data'][] = $value;
            $data['backgroundColor'][] = $color->toRgba(0.8);
            $data['hoverBackgroundColor'][] = $color->toRgba(0.9);
            if ($withCounts) {
                $this->labels[$datasetId] = $this->buildFullLabel($this->labels[$datasetId], $value);
            }
        }

        return array(
            'labels'   => $this->labels,
            'datasets' => array($data)
        );
    }

    /**
     * Define a dataset by name and count number. Method will add the rest.
     *
     * @param  string  $label
     * @param  integer $value
     *
     * @return $this
     */
    public function setDataset($label, $value)
    {
        $this->totalCount += $value;
        $this->datasets[] = $value;
        $this->labels[] = $label;

        return $this;
    }

    /**
     * Adds to the label also the value and the percentage
     *
     * @param  string  $label
     * @param  integer $value
     *
     * @return string
     */
    public function buildFullLabel($label, $value)
    {
        if (!$this->totalCount) return $label;
        $percentage = round($value / $this->totalCount * 100, 2);
        return $label . '; ' . $value . 'x, ' . $percentage . '%';
    }
}
