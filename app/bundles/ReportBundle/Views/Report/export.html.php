<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:slim.html.php');
$view['slots']->set('pageTitle', $pageTitle);
$view['slots']->set('headerTitle', $report->getName());
$view['slots']->set('mauticContent', 'report');

$showGraphsAboveTable = (!empty($report->getSettings()['showGraphsAboveTable']) === true);
$dataCount            = count($data);
$columnOrder          = $report->getColumns();
$graphOrder           = $report->getGraphs();
$startCount           = 1;
?>

<div class="pa-md">
    <h3><?php echo $report->getName(); ?></h3>
    <div class="small">
        <?php echo $view['date']->toDate($dateFrom, 'UTC').' - '.$view['date']->toDate($dateTo, 'UTC'); ?>
    </div>
</div>

<?php if (!empty($showGraphsAboveTable)): ?>
    <?php echo $view->render(
        'MauticReportBundle:Report:details_data_graphs.html.php',
        [
            'graphOrder' => $graphOrder,
            'graphs'     => $graphs,
            'report'     => $report,
        ]);
    ?>
<?php endif; ?>


<?php if (!empty($columnOrder)):?>
<table class="table table-hover table-striped table-bordered report-list" id="reportTable">
    <thead>
    <tr>
        <th class="col-report-count"></th>
        <?php foreach ($columnOrder as $key): ?>
            <th class="col-report-<?php echo $columns[$key]['type']; ?>"><?php echo $columns[$key]['label']; ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php if ($dataCount): ?>
        <?php foreach ($data as $row): ?>
            <tr>
                <td><?php echo $startCount; ?></td>
                <?php foreach ($columnOrder as $key): ?>
                    <td><?php echo $view['formatter']->_($row[$columns[$key]['alias']], $columns[$key]['type']); ?></td>
                <?php endforeach; ?>
            </tr>
            <?php ++$startCount; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td>&nbsp;</td>
            <?php foreach ($columnOrder as $key): ?>
                <td>&nbsp;</td>
            <?php endforeach; ?>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if (empty($showGraphsAboveTable)): ?>
    <?php echo $view->render(
        'MauticReportBundle:Report:details_data_graphs.html.php',
        [
            'graphOrder' => $graphOrder,
            'graphs'     => $graphs,
            'report'     => $report,
        ]);
    ?>
<?php endif; ?>
