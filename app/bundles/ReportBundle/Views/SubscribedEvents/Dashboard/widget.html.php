<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (isset($chartData['data']) and isset($chartType)) :
    $chartData = $chartData['data'];

if ($chartType === 'table') {
    echo $view->render(
        'MauticCoreBundle:Helper:table.html.php',
        [
            'headItems' => isset($chartData[0]) ? array_keys($chartData[0]) : [],
            'bodyItems' => $chartData,
        ]
    );
} else {
    echo $view->render(
        'MauticCoreBundle:Helper:chart.html.php',
        [
            'chartData'   => $chartData,
            'chartType'   => $chartType,
            'chartHeight' => $chartHeight,
        ]
    );
}

if (is_array($dateFrom)) {
    // Using cached data
    $dateFrom = new \DateTime($dateFrom['date'], new \DateTimeZone($dateFrom['timezone']));
    $dateTo   = new \DateTime($dateTo['date'], new \DateTimeZone($dateTo['timezone']));
}

?>

<div class="pull-right mr-md mb-md">
    <a href="<?php echo $view['router']->path('mautic_report_action', ['objectId' => $reportId, 'objectAction' => 'view', 'daterange' => ['date_to' => $dateTo->format('Y-m-d H:i:s'), 'date_from' => $dateFrom->format('Y-m-d H:i:s')]]); ?>">
        <span class="label label-success"><?php echo $view['translator']->trans('mautic.report.dashboard.widgets.full_report'); ?></span>
    </a>
</div>
<div class="clearfix"></div>
<?php else : ?>
    <div class="ml-20 mr-20 mt-lg px-2 alert alert-danger" role="alert">
        <a href="#" class="alert-link"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> <?php echo $view['translator']->trans('mautic.report.dashboard.widgets.no_report_found'); ?>
    </div>
    <div class="clearfix"></div>
<?php endif; ?>

