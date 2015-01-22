<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
    $view->extend('MauticReportBundle:Report:details.html.php');
?>

<?php if ($dataCount = count($data)): ?>
    <?php $startCount = ($reportPage * $limit) - ($dataCount - 1); ?>
    <div class="table-responsive table-responsive-force">
        <table class="table table-hover table-striped table-bordered report-list" id="reportTable">
            <thead>
            <tr>
                <th class="col-report-count"></th>
                <?php foreach ($data[0] as $key => $value): ?>
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'report.' . $report->getId(),
                        'orderBy'    => $columns[$key]['column'],
                        'text'       => $key,
                        'class'      => 'col-report-' . $columns[$key]['type'],
                        'filterBy'   => $columns[$key]['column'],
                        'dataToggle' => in_array($columns[$key]['type'], array('date', 'datetime')) ? 'date' : ''
                    )); ?>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($data as $row) : ?>
                <tr>
                    <td><?php echo $startCount; ?></td>
                    <?php foreach ($row as $key => $cell) : ?>
                        <td><?php echo $view['formatter']->_($cell, $columns[$key]['type']); ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php $startCount++; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-footer">
        <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
            "totalItems"      => $totalResults,
            "page"            => $reportPage,
            "limit"           => $limit,
            "baseUrl"         => $view['router']->generate('mautic_report_view', array(
                "objectId" => $report->getId()
            )),
            'sessionVar'      => 'report.' . $report->getId()
        )); ?>
    </div>
<?php else: ?>
    <h4><?php echo $view['translator']->trans('mautic.core.noresults', array('message' => 'mautic.report.table.noresults')); ?></h4>
<?php endif; ?>