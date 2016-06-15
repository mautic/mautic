<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>
<div class="bg-auto bg-dark-xs col-xs-12 pa-md mb-lg">
    <div class="panel">
        <div class="panel-body box-layout">
            <div class="col-xs-6 va-m">
                <h5 class="text-white dark-md fw-sb mb-xs pull-left">
                    <span class="fa fa-download"></span>
                    <?php echo $view['translator']->trans($graph['name']); ?>
                </h5>
            </div>
        </div>
        <?php echo $view->render('MauticCoreBundle:Helper:chart.html.php', array('chartData' => $graph, 'chartType' => 'line', 'chartHeight' => 300)); ?>
    </div>
</div>
