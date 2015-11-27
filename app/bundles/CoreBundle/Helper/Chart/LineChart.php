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
class LineChart extends BarChart implements ChartInterface {}
