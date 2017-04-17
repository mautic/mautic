<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticReportBundle:Report:details.html.php');
}

$dataCount       = count($data);
$columnOrder     = $report->getColumns();
$graphOrder      = $report->getGraphs();
$aggregatorOrder = $report->getAggregators();
$aggregatorCount = count($aggregatorOrder);
$groupBy         = $report->getGroupBy();
$groupByCount    = count($groupBy);
$startCount      = ($totalResults > $limit) ? ($reportPage * $limit) - $limit + 1 : 1;
?>

<?php if (!empty($columnOrder)): ?>
<!-- table section -->
<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <div class="page-list">
        <div class="table-responsive table-responsive-force">
            <table class="table table-hover table-striped table-bordered report-list" id="reportTable">
                <thead>
                <tr>
                    <th class="col-report-count"></th>
                    <?php
                    if ($aggregatorCount) :
                        foreach ($aggregatorOrder as $aggregator): ?>
                            <?php
                            $columnName = explode('.', $aggregator['column']);
                            $columnName = isset($columnName[1]) ? $columnName[1] : $columnName[0];
                            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                                'sessionVar' => 'report.'.$report->getId(),
                                'orderBy'    => $aggregator['function'],
                                'text'       => $aggregator['function'].' '.strtoupper($columnName),
                                'dataToggle' => '',
                                'target'     => '.report-content',
                            ]);
                            ?>
                    <?php
                        endforeach;
                    endif;
                    ?>
                    <?php foreach ($columnOrder as $key): ?>
                        <?php
                        if (isset($columns[$key])):
                            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                                'sessionVar' => 'report.'.$report->getId(),
                                'orderBy'    => strpos($key, 'channel.') === 0 ? str_replace('.', '_', $key) : $key,
                                'text'       => $columns[$key]['label'],
                                'class'      => 'col-report-'.$columns[$key]['type'],
                                'dataToggle' => in_array($columns[$key]['type'], ['date', 'datetime']) ? 'date' : '',
                                'target'     => '.report-content',
                            ]);
                        else:
                            unset($columnOrder[$key]);
                        endif;
                        ?>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php if ($dataCount): ?>
                    <?php foreach ($data as $row): ?>
                        <tr <?php if ($groupByCount && $startCount == $dataCount) :
                echo 'class="cm-strong"'; endif; ?>>
                            <td><?php
                                if ($groupByCount && $startCount == $dataCount) :
                                    echo $view['translator']->trans('mautic.report.report.groupby.totals');
                                else:
                                    echo $startCount;
                                endif;
                                 ?></td>
                            <?php

                            if ($aggregatorCount) :
                                foreach ($aggregatorOrder as $aggregator): ?>
                                        <td>
                                            <?php
                                                if (isset($row[$aggregator['function']])) {
                                                    echo $view['formatter']->_($row[$aggregator['function']], 'text');
                                                }
                                            ?>
                                        </td>
                            <?php endforeach;
                            endif;
                            ?>
                            <?php foreach ($columnOrder as $key): ?>
                                <?php if (isset($columns[$key])): ?>
                                <td>
                                    <?php $closeLink = false; ?>
                                    <?php if (isset($columns[$key]['link']) && !empty($row[$columns[$key]['alias']])): ?>
                                    <?php $closeLink = true;
                                    if (array_key_exists('comp.id', $columns)) {
                                        $objectAction = 'edit';
                                    } else {
                                        $objectAction = 'view';
                                    }
                                    ?>
                                    <a href="<?php echo $view['router']->path($columns[$key]['link'], ['objectAction' => $objectAction, 'objectId' => $row[$columns[$key]['alias']]]); ?>" class="label label-success">
                                    <?php endif; ?>
                                    <?php echo $view['formatter']->_($row[$columns[$key]['alias']], $columns[$key]['type']); ?>
                                    <?php if ($closeLink): ?></a><?php endif; ?>
                                </td>
                                <?php endif; ?>
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
        </div>
        <div class="panel-footer">
            <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', [
                'totalItems' => $totalResults,
                'page'       => $reportPage,
                'limit'      => $limit,
                'baseUrl'    => $view['router']->path('mautic_report_view', [
                    'objectId' => $report->getId(),
                ]),
                'sessionVar' => 'report.'.$report->getId(),
                'target'     => '.report-content',
            ]); ?>
        </div>
    </div>
</div>
<!--/ table section -->
<?php endif; ?>

<?php if (!empty($graphOrder) && !empty($graphs)): ?>
<div class="mt-lg pa-md">
    <div class="row equal">
    <?php
    $rowCount = 0;
    foreach ($graphOrder as $key):
        $details = $graphs[$key];
        if (!isset($details['data'])) {
            continue;
        }
        if ($rowCount >= 12):
            echo '</div><div class="row equal">';
            $rowCount = 0;
        endif;
        echo $view->render('MauticReportBundle:Graph:'.ucfirst($details['type']).'.html.php', ['graph' => $details['data'], 'options' => $details['options'], 'report' => $report]);
        $rowCount += ($details['type'] == 'line') ? 12 : 4;
    endforeach;
    ?>
    </div>
</div>
<?php endif; ?>