<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper\Chart;

use Mautic\CoreBundle\Helper\Chart\BarChart;
use Mautic\CoreBundle\Helper\Chart\ChartInterface;

/**
 * Class LineChart
 *
 * Line chart requires the same data as Bar chart
 */
class LineChart extends BarChart implements ChartInterface
{
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
            'fillColor'             => $color->toRgba(0.1),
            'strokeColor'           => $color->toRgba(0.8),
            'pointColor'            => $color->toRgba(0.75),
            'pointHighlightStroke'  => $color->toRgba(1)
        );
    }
}
