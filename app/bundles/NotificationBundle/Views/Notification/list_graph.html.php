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
            <?php if ($showVariants): ?>
            <div class="text-right small">
                <span>
                    <?php if ($isVariant): ?>
                    <span data-chart="variant"><?php echo $view['translator']->trans('mautic.notification.variant.graph.variant'); ?></span>
                    <?php else: ?>
                    <a data-chart="variant" href="javascript:void(0)"><?php echo $view['translator']->trans('mautic.notification.variant.graph.variant'); ?></a>
                    <?php endif; ?>
                </span>
                </span> | </span>
                <span>
                    <?php if ($isVariant): ?>
                    <a data-chart="all" href="javascript:void(0)"><?php echo $view['translator']->trans('mautic.notification.variant.graph.all'); ?></a>
                    <?php else: ?>
                    <span data-chart="all"><?php echo $view['translator']->trans('mautic.notification.variant.graph.all'); ?></span>
                    <?php endif; ?>
                </span>
            </div>
            <?php endif; ?>
            <div class="panel">
                <div class="panel-body box-layout">
                    <div class="col-xs-4 va-m">
                        <h5 class="text-white dark-md fw-sb mb-xs">
                            <span class="fa fa-envelope"></span>
                            <?php echo $view['translator']->trans('mautic.notification.lead.list.comparison'); ?>
                        </h5>
                    </div>
                    <div class="col-xs-8 va-m" id="legend"></div>
                </div>
                <div class="pt-0 pl-15 pb-10 pr-15">
                    <div>
                        <canvas id="list-compare-chart" height="300"></canvas>
                    </div>
                </div>
                <div id="list-compare-chart-data" class="hide"><?php echo json_encode($stats); ?></div>
            </div>
        </div>
    </div>
</div>
<!--/ some stats -->
