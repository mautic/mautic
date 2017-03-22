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


<?php

foreach ($chartItems as $key => $chartData) :
    ?>
    <div style="float: left; width: <?php echo $width; ?>%; " class="pt-sd pr-md pb-md pl-md">

<div class="chart-wrapper" >
    <div >
        <div class="chart-legend pull-left-lg"><h4><?php echo $columnName[$key]; ?></h4></div>
        <div class="clearfix"></div>
        <div class="pull-left"> <a href="<?php echo $link[$key]; ?>">  <?php echo $value[$key]; ?> Contacts</div></a>
        <div class="clearfix"></div>
        <div style="height:<?php echo $chartHeight / 2.3; ?>px;">

            <canvas class="chart <?php echo $chartType; ?>-chart"
                    style="font-size: 9px!important;"><?php echo json_encode($chartData); ?></canvas>

        </div>
        <div class="legend" style="font-size: 9px;"></div>
    </div>
</div>
    <?php if ($stages[$key]) : ?>
        <div class="chart-wrapper">
                <div>
                    <div class="chart-legend pt-sd pr-md pb-md pl-md"><h5><?php echo $view['translator']->trans('mautic.lead.lifecycle.graph.stage.cycle'); ?></h5></div>
                    <div class="clearfix"></div>
                    <div style="height:<?php echo $chartHeight / 2.3; ?>px;">
                     <canvas class="chart liefechart-bar-chart" style="font-size: 9px!important;"><?php echo json_encode($stages[$key]); ?></canvas>
                    </div>
                    <div class="legend" style="font-size: 9px;"></div>
                </div>
        </div>
    <?php endif; ?>
        <?php if ($devices[$key]) : ?>
            <div class="chart-wrapper">
                <div>
                    <div class="chart-legend pt-sd pr-md pb-md pl-md"><h5><?php echo $view['translator']->trans('mautic.lead.lifecycle.graph.device.granularity'); ?></h5></div>
                    <div class="clearfix"></div>
                    <div style="height:<?php echo $chartHeight / 5; ?>px;">
                        <canvas class="chart horizontal-bar-chart" style="font-size: 9px!important;"><?php echo json_encode($devices[$key]); ?></canvas>
                    </div>
                    <div class="legend" style="font-size: 9px;"></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?php endforeach; ?>
<div class="clearfix"></div>
