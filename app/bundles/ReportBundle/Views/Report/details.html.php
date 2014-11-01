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




        </div>
        <!--/ end: tab-content -->
    </div>
    <!--/ left section -->
</div>
<!--/ end: box layout -->

<!-- <div class="panel panel-default page-list">
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
</div> -->
