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
<div class="col-sm-12">
    <div class="panel">
        <div class="panel-body box-layout">
            <div class="col-xs-8 va-m">
                <h5 class="text-white dark-md fw-sb mb-xs"><?php echo $view['translator']->trans($graph['name']); ?></h5>
            </div>
            <div class="col-xs-4 va-t text-right">
                <h3 class="text-white dark-sm"><span class="fa fa-<?php echo isset($graph['iconClass']) ? $graph['iconClass'] : ''; ?>"></span></h3>
            </div>
        </div>

        <?php echo $view->render('MauticCoreBundle:Helper:chart.html.php', ['chartData' => $graph, 'chartType' => 'line', 'chartHeight' => 300]); ?>
    </div>
</div>

