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
    protected $totalCount = 0;

    /**
     * Render chart data
     */
    public function render() {

        // Add numbers to the labels
        if ($this->totalCount) {
            foreach ($this->datasets as &$dataset) {
                $percentige = round($dataset['value'] / $this->totalCount * 100, 2);
                $dataset['label'] .= '; ' . $dataset['value'] . 'x, ' . $percentige . '%';
            }
        }

        return $this->datasets;
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
        $datasetId = count($this->datasets);
        $color = $this->configureColorHelper($datasetId);

        $this->datasets[] = array(
            'label'     => $label,
            'value'     => $value,
            'color'     => $color->toRgba(0.6),
            'highlight' => $color->toRgba(1)
        );

        return $this;
    }
}
