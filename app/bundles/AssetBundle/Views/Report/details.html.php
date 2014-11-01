<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$header = $view['translator']->trans('mautic.report.report.header.view', array('%name%' => $view['translator']->trans($report->getTitle())));

if ($tmpl == 'index') {
    $view->extend('MauticCoreBundle:Default:content.html.php');
    $view['slots']->set('mauticContent', 'report');

    $view['slots']->set("headerTitle", $header);
}
?>

<?php
$view['slots']->start('actions');
if ($security->hasEntityAccess($permissions['report:reports:editown'], $permissions['report:reports:editother'],
    $report->getCreatedBy())): ?>
    <a href="<?php echo $this->container->get('router')->generate(
        'mautic_report_action', array("objectAction" => "edit", "objectId" => $report->getId())); ?>"
       data-toggle="ajax"
       class="btn btn-default"
       data-menu-link="#mautic_report_index">
        <i class="fa fa-fw fa-pencil-square-o"></i>
        <?php echo $view["translator"]->trans("mautic.core.form.edit"); ?>
    </a>
<?php endif; ?>
<?php if ($security->hasEntityAccess($permissions['report:reports:deleteown'], $permissions['report:reports:deleteother'],
    $report->getCreatedBy())): ?>
    <a href="javascript:void(0);"
       class="btn btn-default"
       onclick="Mautic.showConfirmation(
           '<?php echo $view->escape($view["translator"]->trans("mautic.report.report.confirmdelete",
           array("%name%" => $report->getTitle() . " (" . $report->getId() . ")")), 'js'); ?>',
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
           'executeAction',
           ['<?php echo $view['router']->generate('mautic_report_action',
           array("objectAction" => "delete", "objectId" => $report->getId())); ?>',
           '#mautic_report_index'],
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
        <span><i class="fa fa-fw fa-trash-o"></i><?php echo $view['translator']->trans('mautic.core.form.delete'); ?></span>
    </a>
<?php endif; ?>

<?php $view['slots']->stop(); ?>

<!--<div class="panel panel-default page-list">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $header; ?></h3>
    </div>
    <div class="table-responsive panel-collapse pull out page-list">
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
</div>-->

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- report detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-6 va-m">
                        <p class="text-white dark-lg mb-0">Created on <?php echo $view['date']->toDate($report->getDateAdded()); ?></p>
                    </div>
                    <!-- <div class="col-xs-6 va-m text-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default">Export As</button>
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li><a href="#"><span class="fa fa-file-excel-o fs-14 mr-2"></span> MS Excel</a></li>
                                <li><a href="#"><span class="fa fa-file-pdf-o fs-14 mr-2"></span> PDF</a></li>
                                <li><a href="#"><span class="fa fa-file-word-o fs-14 mr-2"></span> MS Word</a></li>
                            </ul>
                        </div>
                    </div> -->
                </div>
            </div>
            <!--/ report detail header -->
        </div>

        <div class="bg-auto bg-dark-xs">

            <!-- Campaign Overview Chart -->
            <div class="pa-md mb-lg">
                <!-- area spline chart -->
                <div class="box-layout mb-xs">
                    <div class="col-xs-6 va-m">
                        <h5 class="fw-sb pull-left"><span class="fa fa-clock-o mr-xs"></span> Campaign Performance</h5>
                    </div>
                    <div class="col-xs-6 va-m text-right">
                        <div class="btn-group">
                            <a href="#" class="btn btn-sm btn-default">Hourly</a>
                            <a href="#" class="btn btn-sm btn-default active">Day</a>
                            <a href="#" class="btn btn-sm btn-default">Week</a>
                            <a href="#" class="btn btn-sm btn-default">Month</a>
                        </div>
                    </div>
                </div>
                <div class="flotchart" data-type="area" style="height:300px;">
                    <!-- put generated data inside .flotdata -->
                    <span class="flotdata">
                        [{
                            "label": "Redemptions",
                            "color": "#00B49C",
                            "data": [
                                ["jan", 7574],
                                ["feb", 6085],
                                ["mac", 9775],
                                ["apr", 6739],
                                ["may", 9002],
                                ["jun", 8525],
                                ["jul", 7555],
                                ["aug", 9137],
                                ["sept", 7799],
                                ["oct", 9966],
                                ["nov", 9897],
                                ["dec", 6185]
                            ]
                        },{
                            "label": "Total visits",
                            "color": "#FDB933",
                            "data": [
                                ["jan", 5303],
                                ["feb", 9130],
                                ["mac", 5246],
                                ["apr", 9549],
                                ["may", 9538],
                                ["jun", 7546],
                                ["jul", 6930],
                                ["aug", 5705],
                                ["sept", 6027],
                                ["oct", 7310],
                                ["nov", 6152],
                                ["dec", 9928]
                            ]
                        }]
                    </span>
                </div>
                <!--/ area spline chart -->
            </div>
            <!--/ Campaign Overview Chart -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <!-- #email-stats-container -->
            <div class="tab-pane active fade in bdr-w-0" id="email-stats-container">
                <div class="row">
                    <div class="col-md-4">
                        <div class="panel mb-0">
                            <div class="flotchart" data-type="donut" style="height:150px;">
                                <!-- put generated data inside .flotdata -->
                                <span class="flotdata">
                                    [{
                                        "label": "Overall",
                                        "color": "#eee",
                                        "data": 60
                                    },{
                                        "label": "Open Rate",
                                        "color": "#4E5D9D",
                                        "data": 40
                                    }]
                                </span>
                            </div>
                            <ul class="list-group">
                                <li class="list-group-item">Email Delivered <span class="badge pull-right">100</span></li>
                                <li class="list-group-item">Total Open <span class="badge pull-right">40</span></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel mb-0">
                            <div class="flotchart" data-type="donut" style="height:150px;">
                                <!-- put generated data inside .flotdata -->
                                <span class="flotdata">
                                    [{
                                        "label": "Overall",
                                        "color": "#eee",
                                        "data": 10
                                    },{
                                        "label": "Click Rate",
                                        "color": "#35B4B9",
                                        "data": 90
                                    }]
                                </span>
                            </div>
                            <ul class="list-group">
                                <li class="list-group-item">Email Delivered <span class="badge pull-right">100</span></li>
                                <li class="list-group-item">Total Click <span class="badge pull-right">90</span></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel mb-0">
                            <div class="flotchart" data-type="donut" style="height:150px;">
                                <!-- put generated data inside .flotdata -->
                                <span class="flotdata">
                                    [{
                                        "label": "Overall",
                                        "color": "#eee",
                                        "data": 40
                                    },{
                                        "label": "View Rate",
                                        "color": "#4E5D9D",
                                        "data": 60
                                    }]
                                </span>
                            </div>
                            <ul class="list-group">
                                <li class="list-group-item">Email Delivered <span class="badge pull-right">100</span></li>
                                <li class="list-group-item">Total View <span class="badge pull-right">60</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ #email-stats-container -->

            
        </div>
        <!--/ end: tab-content -->
    </div>
    <!--/ left section -->
</div>
<!--/ end: box layout -->
