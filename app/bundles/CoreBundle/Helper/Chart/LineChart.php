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
use Doctrine\DBAL\Connection;

/**
 * Class LineChart
 *
 * Line chart requires the same data as Bar chart
 */
class LineChart extends BarChart implements ChartInterface {


    /**
     * Colors which can be used in the line graphs
     *
     * @var array
     */
    public $colors = array(
        array(
            'fillColor' => 'rgba(78, 93, 157, 0.5)',
            'strokeColor' => 'rgba(78, 93, 157, 0.8)',
            'pointColor' => 'rgba(78, 93, 157, 0.75)',
            'pointHighlightStroke' => 'rgba(78, 93, 157, 1)'),
        array(
            'fillColor' => 'rgba(0, 180, 156, 0.5)',
            'strokeColor' => 'rgba(0, 180, 156, 0.8)',
            'pointColor' => 'rgba(0, 180, 156, 0.75)',
            'pointHighlightStroke' => 'rgba(0, 180, 156, 1)'),
        array(
            'fillColor' => 'rgba(253, 149, 114, 0.5)',
            'strokeColor' => 'rgba(253, 149, 114, 0.8)',
            'pointColor' => 'rgba(253, 149, 114, 0.75)',
            'pointHighlightStroke' => 'rgba(253, 149, 114, 1)'),
        array(
            'fillColor' => 'rgba(253, 185, 51, 0.5)',
            'strokeColor' => 'rgba(253, 185, 51, 0.8)',
            'pointColor' => 'rgba(253, 185, 51, 0.75)',
            'pointHighlightStroke' => 'rgba(253, 185, 51, 1)'),
        array(
            'fillColor' => 'rgba(117, 117, 117, 0.5)',
            'strokeColor' => 'rgba(117, 117, 117, 0.8)',
            'pointColor' => 'rgba(117, 117, 117, 0.75)',
            'pointHighlightStroke' => 'rgba(117, 117, 117, 1)'),
        array(
            'fillColor' => 'rgba(156, 78, 92, 0.5)',
            'strokeColor' => 'rgba(156, 78, 92, 0.8)',
            'pointColor' => 'rgba(156, 78, 92, 0.75)',
            'pointHighlightStroke' => 'rgba(156, 78, 92, 1)'),
        array(
            'fillColor' => 'rgba(105, 69, 53, 0.5)',
            'strokeColor' => 'rgba(105, 69, 53, 0.8)',
            'pointColor' => 'rgba(105, 69, 53, 0.75)',
            'pointHighlightStroke' => 'rgba(105, 69, 53, 1)'),
        array(
            'fillColor' => 'rgba(89, 105, 53, 0.5)',
            'strokeColor' => 'rgba(89, 105, 53, 0.8)',
            'pointColor' => 'rgba(89, 105, 53, 0.75)',
            'pointHighlightStroke' => 'rgba(89, 105, 53, 1)'),
    );
}
