<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ('index' == $tmpl) {
    $view->extend('MauticReportBundle:Report:details.html.php');
}

$showGraphsAboveTable = (true === !empty($report->getSettings()['showGraphsAboveTable']));
$dataCount            = count($data);
$columnOrder          = $report->getColumns();
$graphOrder           = $report->getGraphs();
$aggregatorOrder      = $report->getAggregators();
$aggregatorCount      = count($aggregatorOrder);
$groupBy              = $report->getGroupBy();
$groupByCount         = count($groupBy);
$startCount           = ($totalResults > $limit) ? ($reportPage * $limit) - $limit + 1 : 1;
function getTotal($a, $f, $t, $allrows, $ac)
{
    switch ($f) {
        case 'COUNT':
        case 'SUM':
            return (int) $t + (int) $a;
        case 'AVG':
            return ($ac == $allrows) ? round(((int) $t + (int) $a) / (int) $allrows, 2) : (int) $t + (int) $a;
        case 'MAX':
            return ((int) $a >= (int) $t) ? (int) $a : (int) $t;
        case 'MIN':
            return ((int) $a <= (int) $t) ? (int) $a : (int) $t;
        default:
            return (int) $t;
    }
}

$graphContent = $view->render(
    'MauticReportBundle:Report:details_data_graphs.html.php',
    [
        'graphOrder' => $graphOrder,
        'graphs'     => $graphs,
        'report'     => $report,
    ]
);
?>

<?php if (!empty($showGraphsAboveTable)): echo $graphContent; endif; ?>

<?php if (!empty($columnOrder) || !empty($aggregatorOrder)): ?>
    <!-- table section -->
    <div class="col-xs-12">
        <div class="panel panel-default bdr-t-wdh-0 mb-0">
            <div class="page-list">
                <div class="table-responsive table-responsive-force">
                    <table class="table table-hover table-striped table-bordered report-list" id="reportTable">
                        <thead>
                        <tr>
                            <th class="col-report-count"></th>

                            <?php foreach ($columnOrder as $key): ?>
                                <?php
                                if (isset($columns[$key])):
                                    // order by alias if exists, if not then by column name
                                    $orderBy = $columns[$key]['alias'] ??
                                        (0 === strpos($key, 'channel.') ? str_replace('.', '_', $key) : $key);
                                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                                        'sessionVar' => 'report.'.$report->getId(),
                                        'orderBy'    => $orderBy,
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
                            <?php
                            if ($aggregatorCount) :
                                $index = 0;
                                foreach ($aggregatorOrder as $aggregator): ?>
                                    <?php
                                    $columnName = isset($columns[$aggregator['column']]['alias']) ? $columns[$aggregator['column']]['label'] : '';
                                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                                        'sessionVar' => 'report.'.$report->getId(),
                                        'text'       => $aggregator['function'].' '.$columnName,
                                        'orderBy'    => '`'.$aggregator['function'].' '.$aggregator['column'].'`',
                                        'dataToggle' => '',
                                        'target'     => '.report-content',
                                    ]);
                                    ?>
                                    <?php
                                    $total[$index] = 0;
                                    ++$index;
                                endforeach;
                            endif;
                            ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($dataCount):
                            $avgCounter = 0;
                            ?>
                            <?php foreach ($data as $row): ?>
                            <tr>
                                <td><?php echo $startCount; ?></td>
                                <?php foreach ($columnOrder as $key): ?>
                                    <?php if (isset($columns[$key])): ?>
                                        <td>
                                            <?php $closeLink = false; ?>
                                            <?php if (isset($columns[$key]['link']) && !empty($row[$columns[$key]['alias']])): ?>
                                        <?php $closeLink = true; ?>
                                            <a href="<?php echo $view['router']->path($columns[$key]['link'], ['objectAction' => 'view', 'objectId' => $row[$columns[$key]['alias']]]); ?>" class="label label-success">
                                                <?php endif; ?>
                                                <?php
                                                $cellType = $columns[$key]['type'];
                                                $cellVal  = $row[$columns[$key]['alias']];

                                                // For grouping by datetime fields, so we don't get the timestamp on them
                                                if ('datetime' === $cellType && 10 === strlen($cellVal)) {
                                                    $cellType = 'date';
                                                }
                                                ?>
                                                <?php
                                                if ($cellVal) {
                                                    switch ($cellType) {
                                                        case 'datetime':
                                                            echo $view['date']->toFullConcat($cellVal, 'UTC');
                                                            break;
                                                        case 'date':
                                                            echo $view['date']->toShort($cellVal, 'UTC');
                                                            break;
                                                        default:
                                                            echo $view['formatter']->_($cellVal, $cellType);
                                                            break;
                                                    }
                                                }
                                                ?>
                                                <?php if ($closeLink): ?></a><?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php
                                if ($aggregatorCount) :
                                    $index = 0;
                                    ++$avgCounter;
                                    foreach ($aggregatorOrder as $aggregator): ?>
                                        <td>
                                            <?php
                                            if (isset($row[$aggregator['function'].' '.$aggregator['column']])) {
                                                echo $view['formatter']->_($row[$aggregator['function'].' '.$aggregator['column']], 'text');
                                                $total[$index] = getTotal($row[$aggregator['function'].' '.$aggregator['column']], $aggregator['function'], (isset($total[$index])) ? $total[$index] : 0, $dataCount, $avgCounter);
                                            }
                                            ?>
                                        </td>
                                        <?php
                                        ++$index;
                                    endforeach;
                                endif;
                                ?>

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
                        <tr class="cm-strong">
                            <td><?php echo $view['translator']->trans('mautic.report.report.groupby.totals'); ?></td>
                            <?php
                            $index = 0;
                            foreach ($columnOrder as $key): ?>
                                <td>&nbsp;</td>
                            <?php endforeach;
                            if ($aggregatorCount) :
                                foreach ($aggregatorOrder as $aggregator): ?>
                                    <td>
                                        <?php
                                        if (isset($total[$index])) :
                                            echo $view['formatter']->_($total[$index], 'text');
                                        endif;
                                        ?>

                                    </td>
                                    <?php
                                    ++$index;
                                endforeach;
                            endif; ?>
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
    </div>
    <!--/ table section -->
<?php endif; ?>

<?php if (empty($showGraphsAboveTable)): echo $graphContent; endif; ?>

<script>
    mQuery(document).ready(function() {
        mQuery('.datetimepicker').datetimepicker({
            format:'Y-m-d H:i:s',
            closeOnDateSelect: true,
            validateOnBlur: false,
            scrollMonth: false,
            scrollInput: false
        });
    });
    mQuery(document).ready(function() {
        mQuery('.datepicker').datetimepicker({
            format:'Y-m-d',
            closeOnDateSelect: true,
            validateOnBlur: false,
            scrollMonth: false,
            scrollInput: false
        });
    });
</script>
