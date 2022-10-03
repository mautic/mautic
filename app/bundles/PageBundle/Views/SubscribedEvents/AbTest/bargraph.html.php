<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$support = $results['support'];
$label   = $view['translator']->trans($variants['criteria'][$results['basedOn']]['label']);
$chart   = new \Mautic\CoreBundle\Helper\Chart\BarChart($support['labels']);

if ($support['data']) {
    foreach ($support['data'] as $datasetLabel => $values) {
        $chart->setDataset($datasetLabel, $values);
    }
}

?>

<div class="panel ovf-h bg-auto bg-light-xs abtest-bar-chart">
    <div class="panel-body box-layout">
        <div class="col-xs-8 va-m">
            <h5 class="text-white dark-md fw-sb mb-xs">
                <?php echo $label; ?>
            </h5>
        </div>
        <div class="col-xs-4 va-t text-right">
            <h3 class="text-white dark-sm"><span class="fa fa-bar-chart"></span></h3>
        </div>
    </div>
    <?php echo $view->render(
        'MauticCoreBundle:Helper:chart.html.php',
        ['chartData' => $chart->render(), 'chartType' => 'bar', 'chartHeight' => 300]
    ); ?>
</div>
