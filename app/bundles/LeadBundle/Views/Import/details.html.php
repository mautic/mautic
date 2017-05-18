<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'asset');
$view['slots']->set('headerTitle', $item->getName());
$view['slots']->set(
    'publishStatus',
    $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $item])
);

?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- asset detail collapseable -->
            <div class="collapse" id="asset-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                                <?php echo $view->render('MauticCoreBundle:Helper:details.html.php', ['entity' => $item]); ?>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.lead.import.source.file'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $item->getOriginalFile(); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.lead.import.status'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="label label-<?php echo $item->getSatusLabelClass(); ?>">
                                            <?php echo $view['translator']->trans('mautic.lead.import.status.'.$item->getStatus()); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.lead.import.status.info'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $item->getStatusInfo(); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.lead.import.line.count'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $item->getLineCount(); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.lead.import.inserted.count'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $item->getInsertedCount(); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.lead.import.updated.count'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $item->getUpdatedCount(); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.lead.import.ignored.count'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $item->getIgnoredCount(); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.lead.import.date.started'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $view['date']->toFull($item->getDateStarted()); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.lead.import.date.ended'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $view['date']->toFull($item->getDateEnded()); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.lead.import.runtime'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $view['date']->formatRange($item->getRunTime()); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.lead.import.progress'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $item->getProgressPercentage(); ?>%
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.asset.filename.local'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $item->getFilePath(); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.lead.import.mapped.fields'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $view['formatter']->arrayToString($item->getMatchedFields()); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.lead.import.default.options'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $view['formatter']->arrayToString($item->getDefaults()); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.lead.import.csv.headers'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $view['formatter']->arrayToString($item->getHeaders()); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%">
                                        <span class="fw-b">
                                            <?php echo $view['translator']->trans('mautic.lead.import.csv.parser.config'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $view['formatter']->arrayToString($item->getParserConfig()); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ asset detail collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- asset detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="Detail">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse"
                       data-target="#asset-details"><span class="caret"></span> <?php echo $view['translator']->trans(
                            'mautic.core.details'
                        ); ?></a>
                </span>
            </div>
            <!--/ asset detail collapseable toggler -->

            <!-- some stats -->
            <div class="pa-md">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="col-md-2 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">
                                        <span class="fa fa-download"></span>
                                        <?php echo $view['translator']->trans('mautic.asset.graph.line.downloads'); ?>
                                    </h5>
                                </div>
                                <div class="col-md-8 va-m">
                                    <?php //echo $view->render('MauticCoreBundle:Helper:graph_dateselect.html.php', ['dateRangeForm' => $dateRangeForm, 'class' => 'pull-right']);?>
                                </div>
                            </div>
                            <div class="pt-0 pl-15 pb-10 pr-15">
                                chart
                                <?php //echo $view->render('MauticCoreBundle:Helper:chart.html.php', ['chartData' => $stats['downloads']['timeStats'], 'chartType' => 'line', 'chartHeight' => 300]);?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ stats -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md preview-detail">
            content
        </div>
        <!--/ end: tab-content -->
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">

        <!-- activity feed -->
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]); ?>
    </div>
    <!--/ right section -->
    <input name="entityId" id="entityId" type="hidden" value="<?php echo $item->getId(); ?>"/>
</div>
<!--/ end: box layout -->
