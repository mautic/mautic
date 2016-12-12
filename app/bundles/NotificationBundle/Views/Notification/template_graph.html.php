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
<!--
some stats: need more input on what type of form data to show.
delete if it is not require
-->
<div class="pa-md">
    <div class="row">
        <div class="col-sm-12">
            <div class="panel">
                <div class="panel-body box-layout">
                    <div class="col-xs-4 va-m">
                        <h5 class="text-white dark-md fw-sb mb-xs">
                            <span class="fa fa-envelope"></span>
                            <?php echo $view['translator']->trans('mautic.notification.stats'); ?>
                        </h5>
                    </div>
                    <div class="col-xs-6 va-m" id="legend"></div>
                    <div class="col-xs-2 va-m">
                        <?php echo $view->render('MauticCoreBundle:Helper:graph_dateselect.html.php', ['callback' => 'updateNotificationStatsChart']); ?>

                    </div>
                </div>
                <div class="pt-0 pl-15 pb-10 pr-15">
                    <div>
                        <canvas id="stat-chart" height="300"></canvas>
                    </div>
                </div>
                <div id="stat-chart-data" class="hide"><?php echo json_encode($stats); ?></div>
            </div>
        </div>
    </div>
</div>
<!--/ some stats -->
