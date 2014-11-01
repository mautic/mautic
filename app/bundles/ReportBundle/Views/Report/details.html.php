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
                        <h4 class="fw-sb text-primary"><?php echo $view['translator']->trans($report->getTitle()); ?></h4>
                        <p class="text-white dark-lg mb-0">Created on <?php echo $view['date']->toDate($report->getDateAdded()); ?></p>
                    </div>
                    <div class="col-xs-6 va-m text-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default">Export As</button>
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li><a href="#"><span class="fa fa-file-excel-o fs-14 mr-2"></span> MS Excel</a></li>
                                <li><a href="#"><span class="fa fa-file-pdf-o fs-14 mr-2"></span> PDF</a></li>
                                <li><a href="#"><span class="fa fa-file-word-o fs-14 mr-2"></span> MS Word</a></li>
                            </ul>
                        </div>
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
                                <tr>
                                    <td width="20%"><span class="fw-b">Source</span></td>
                                    <td><?php echo $report->getSource(); ?></td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b">Created By</span></td>
                                    <td><?php //echo $report->getAuthor()->getName(); ?></td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b">Created on</span></td>
                                    <td><?php echo $view['date']->toDate($report->getDateAdded()); ?></td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b">Modified on</span></td>
                                    <td><?php echo $view['date']->toDate($report->getDateModified()); ?></td>
                                </tr>
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
                    <a href="javascript:void(0)" class="arrow" data-toggle="collapse" data-target="#report-details"><span class="caret"></span></a>
                </span>
            </div>
            <!--/ report detail collapseable toggler -->

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

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <li class="active"><a href="#email-stats-container" role="tab" data-toggle="tab">Email Stats</a></li>
                <li class=""><a href="#page-stats-container" role="tab" data-toggle="tab">Page Stats</a></li>
            </ul>
            <!--/ tabs controls -->
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

            <!-- #page-stats-container -->
            <div class="tab-pane fade bdr-w-0" id="page-stats-container">
                <div class="pa-md clearfix">
                    <h6 class="pull-left mr-lg"><span class="fa fa-square text-primary mr-xs"></span> Hit</h6>
                    <h6 class="pull-left mr-lg"><span class="fa fa-square text-warning mr-xs"></span> Conversion</h6>
                    <h6 class="pull-left"><span class="fa fa-square text-success mr-xs"></span> View</h6>
                </div>
                <ul class="list-group mb-0">
                    <li class="list-group-item bg-auto bg-light-xs">
                        <div class="box-layout">
                            <div class="col-md-1 va-m">
                                <h3><span class="fa fa-check-circle-o fw-sb text-success" data-toggle="tooltip" data-placement="right" title="" data-original-title="Published"></span></h3>
                            </div>
                            <div class="col-md-7 va-m">
                                <h5 class="fw-sb text-primary"><a href="">Kaleidoscope Conference 2014 <span>[current]</span> <span>[parent]</span></a></h5>
                                <span class="text-white dark-sm">kaleidoscope-conference-2014</span>
                            </div>
                            <div class="col-md-4 va-m text-right">
                                <a href="javascript:void(0)" class="btn btn-sm btn-primary">5729</a>
                                <a href="javascript:void(0)" class="btn btn-sm btn-warning">3426</a>
                                <a href="javascript:void(0)" class="btn btn-sm btn-success">354</a>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item bg-auto bg-light-xs">
                        <div class="box-layout">
                            <div class="col-md-1 va-m">
                                <h3><span class="fa fa-check-circle-o fw-sb text-success" data-toggle="tooltip" data-placement="right" title="" data-original-title="Published"></span></h3>
                            </div>
                            <div class="col-md-7 va-m">
                                <h5 class="fw-sb text-primary"><a href="">Kaleidoscope Conference 2014</a></h5>
                                <span class="text-white dark-sm">kaleidoscope-conference-2014</span>
                            </div>
                            <div class="col-md-4 va-m text-right">
                                <a href="javascript:void(0)" class="btn btn-sm btn-primary">652</a>
                                <a href="javascript:void(0)" class="btn btn-sm btn-warning">115</a>
                                <a href="javascript:void(0)" class="btn btn-sm btn-success">342</a>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item bg-auto bg-light-xs">
                        <div class="box-layout">
                            <div class="col-md-1 va-m">
                                <h3><span class="fa fa-check-circle-o fw-sb text-success" data-toggle="tooltip" data-placement="right" title="" data-original-title="Published"></span></h3>
                            </div>
                            <div class="col-md-7 va-m">
                                <h5 class="fw-sb text-primary"><a href="">Copenhagen Conference 2014</a></h5>
                                <span class="text-white dark-sm">copenhagen-conference-2014</span>
                            </div>
                            <div class="col-md-4 va-m text-right">
                                <a href="javascript:void(0)" class="btn btn-sm btn-primary">943</a>
                                <a href="javascript:void(0)" class="btn btn-sm btn-warning">7598</a>
                                <a href="javascript:void(0)" class="btn btn-sm btn-success">551</a>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <!--/ #page-stats-container -->
        </div>
        <!--/ end: tab-content -->
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!--
        we can leverage data from audit_log table
        and build activity feed from it
        -->
        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mt-sm">
            
            <!-- recent activity -->
            <?php echo $view->render('MauticCoreBundle:Default:recentactivity.html.php', array('logs' => $logs)); ?>
        
        </div>
    </div>
    <!--/ right section -->
</div>
<!--/ end: box layout -->
