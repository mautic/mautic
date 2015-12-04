<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div>
    <canvas class="chart <?php echo $chartType; ?>-chart" height="<?php echo $chartHeight; ?>"><?php echo json_encode($chartData); ?></canvas>
</div>
