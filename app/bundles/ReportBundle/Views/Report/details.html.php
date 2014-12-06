<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$header = $view['translator']->trans('mautic.report.report.header.view', array('%name%' => $view['translator']->trans($report->getTitle())));

if ($tmpl == 'index') {
    $view->extend('MauticCoreBundle:Default:content.html.php');
}
$view['slots']->set('mauticContent', 'report');

$view['slots']->set("headerTitle", $header);

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'item' => $report,
    'templateButtons' => array(
        'edit'    => $security->hasEntityAccess($permissions['report:reports:editown'], $permissions['report:reports:editother'], $report->getCreatedBy()),
        'delete'  => $security->hasEntityAccess($permissions['report:reports:deleteown'], $permissions['report:reports:deleteother'], $report->getCreatedBy())
    ),
    'routeBase' => 'report',
    'langVar'   => 'report.report'
)));
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- report detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-10 va-m">
                        <p class="text-white dark-sm mb-0"><?php echo $report->getDescription(); ?></p>
                    </div>
                    <div class="col-xs-2 text-right">
                        <?php switch ($report->getPublishStatus()) {
                            case 'published':
                                $labelColor = "success";
                                break;
                            case 'unpublished':
                            case 'expired'    :
                                $labelColor = "danger";
                                break;
                            case 'pending':
                                $labelColor = "warning";
                                break;
                        } ?>
                        <?php $labelText = strtoupper($view['translator']->trans('mautic.core.form.' . $report->getPublishStatus())); ?>
                        <h4 class="fw-sb"><span class="label label-<?php echo $labelColor; ?>"><?php echo $labelText; ?></span></h4>
                    </div>
                </div>
            </div>
            <!--/ report detail header -->
            <!-- report detail collapseable -->
            <div class="collapse" id="report-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                            <?php echo $view->render('MauticCoreBundle:Helper:details.html.php', array('entity' => $report)); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ report detail collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- report detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="Detail">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse" data-target="#report-details"><span class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?></a>
                </span>
            </div>
            <!--/ report detail collapseable toggler -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <?php if (isset($graphs['line']) && $graphs['line']) : ?>
            <?php foreach ($graphs['line'] as $graph) : ?>
            <!-- Overview Chart -->
            <div class="pa-md mb-lg">
                <!-- area spline chart -->
                <?php echo $view->render('MauticReportBundle:Graph:Line.html.php', array('graph' => $graph)); ?>
                <!--/ area spline chart -->
            </div>
            <!--/ Overview Chart -->
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="pa-md">
            <div class="row">
                <?php if (isset($graphs['pie']) && $graphs['pie']) : ?>
                <?php foreach ($graphs['pie'] as $graph) : ?>
                <!-- Overview Chart -->
                <div class="col-md-4">
                    <!-- area spline chart -->
                    <?php echo $view->render('MauticReportBundle:Graph:Pie.html.php', array('graph' => $graph)); ?>
                    <!--/ area spline chart -->
                </div>
                <!--/ Overview Chart -->
                <?php endforeach; ?>
                <?php endif; ?>

                <?php if (isset($graphs['table']) && $graphs['table']) : ?>
                <?php foreach ($graphs['table'] as $graph) : ?>
                <!-- Overview Chart -->
                <div class="col-md-4">
                    <!-- area spline chart -->
                    <?php echo $view->render('MauticReportBundle:Graph:Table.html.php', array('graph' => $graph, 'report' => $report)); ?>
                    <!--/ area spline chart -->
                </div>
                <!--/ Overview Chart -->
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- table section -->
        <!-- <div class="panel panel-default page-list">
            <div class="table-responsive panel-collapse pull out">
                <?php // We need to dynamically create the table headers based on the result set ?>
                <?php if (count($result) > 0) : ?>
                <table class="table table-hover table-striped table-bordered report-list" id="reportTable">
                    <thead>
                        <tr>
                            <?php foreach ($result[0] as $key => $value) : ?>
                            <?php
                            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                                'sessionVar' => 'report.' . $report->getId(),
                                'orderBy'    => $key,
                                'text'       => ucfirst($key),
                                'class'      => 'col-page-' . $key
                            )); ?>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $row) : ?>
                        <tr>
                            <?php foreach ($row as $cell) : ?>
                            <td><?php echo $cell; ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                <h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
                <?php endif; ?>
                <div class="panel-footer">
                    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
                        "totalItems"      => count($result),
                        "page"            => $reportPage,
                        "limit"           => $limit,
                        "menuLinkId"      => 'mautic_report_action',
                        "baseUrl"         => $view['router']->generate('mautic_report_action', array(
                            "objectAction" => 'view',
                            "objectId"     => $report->getId(),
                            "reportPage"   => $reportPage
                        )),
                        'sessionVar'      => 'report.' . $report->getId()
                    )); ?>
                </div>
            </div>
        </div> -->
        <!--/ table section -->
    </div>
    <!--/ left section -->
</div>
<!--/ end: box layout -->
<input type="hidden" name="reportId" id="reportId" value="<?php echo $report->getId(); ?>" />
