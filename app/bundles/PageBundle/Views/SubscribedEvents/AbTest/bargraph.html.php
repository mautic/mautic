<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$support = $results['support'];
$label   = $view['translator']->trans($variants['criteria'][$results['basedOn']]['label']);

$barData = \Mautic\CoreBundle\Helper\GraphHelper::prepareBarGraphData($support['labels'], $support['data']);

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
    <div class="row">
        <div class="col-sm-7">
            <canvas id="abtest-bar-chart" height="300"></canvas>
        </div>
        <div class="col-sm-5">
            <div class="abtest-bar-legend legend-container"></div>
        </div>
    </div>
</div>

<script>
    mQuery(document).ready(function() {
        mQuery('#abStatsModal').on('shown.bs.modal', function (event) {
            var canvas = document.getElementById("abtest-bar-chart");
            var barData = mQuery.parseJSON('<?php echo json_encode($barData); ?>');
            var barGraph = new Chart(canvas.getContext("2d")).Bar(barData, {
                responsive: true,
                animation: false,
                tooltipTitleFontSize: 0,
                scaleOverride: true,
                <?php if (isset($support['step_width'])) : ?>
                scaleSteps: <?php echo ($support['step_width'] > 10) ? 11 : 10; ?>,
                scaleStepWidth: <?php echo ($support['step_width'] > 10) ? (ceil($support['step_width']/10)) : 1; ?>,
                <?php endif; ?>
                scaleStartValue: 0
            });

            var legendHolder = document.createElement('div');
            legendHolder.innerHTML = barGraph.generateLegend();
            mQuery('.abtest-bar-legend').html(legendHolder.firstChild);
            barGraph.update();
        });
    });
</script>