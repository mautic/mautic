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
     * Defines the basic chart values, generates the time axe labels from it
     *
     * @param array $labels
     */
    public function __construct(array $labels)
    {
        $this->labels = $labels;
    }

    /**
     * Render chart data
     */
    public function render() {
        ksort($this->datasets);
        return array(
            'labels'   => $this->labels,
            'datasets' => $this->datasets
        );
    }

    /**
     * Define a dataset by name and data. Method will add the rest.
     *
     * @param  string  $label
     * @param  array   $data
     * @param  integer $order
     *
     * @return $this
     */
    public function setDataset($label = null, array $data, $order = null)
    {
        $datasetId = count($this->datasets);

        $baseData = array(
            'label' => $label,
            'data'  => $data,
        );

        if ($order === null) {
            $order = count($this->datasets);
        }
        
        $this->datasets[$order] = array_merge($baseData, $this->generateColors($datasetId));

        return $this;
    }

    /**
     * Generate unique color for the dataset
     *
     * @param  integer  $datasetId
     *
     * @return array
     */
    public function generateColors($datasetId)
    {
        $color = $this->configureColorHelper($datasetId);

        return array(
            'fill' => true,
            'backgroundColor'           => $color->toRgba(0.7),
            'borderColor'               => $color->toRgba(0.8),
            'pointHoverBackgroundColor' => $color->toRgba(0.9),
            'pointHoverBorderColor'     => $color->toRgba(1)
        );
    }
}
