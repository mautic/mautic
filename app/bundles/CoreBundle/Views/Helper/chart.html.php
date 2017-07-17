<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="chart-wrapper">
    <div class="pt-sd pr-md pb-md pl-md">
        <div style="height:<?php echo $chartHeight; ?>px">
            <canvas class="chart <?php echo $chartType; ?>-chart" <?php echo isset($disableLegend) ? 'data-disable-legend' : ''?>>
                <?php echo json_encode($chartData); ?>
            </canvas>
        </div>
    </div>
</div>
