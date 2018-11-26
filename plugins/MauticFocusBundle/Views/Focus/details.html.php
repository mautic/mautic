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
$view['slots']->set('mauticContent', 'focus');
$view['slots']->set('headerTitle', $item->getName());

echo $view['assets']->includeScript('plugins/MauticFocusBundle/Assets/js/focus.js');

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'item'            => $item,
            'templateButtons' => [
                'edit' => $view['security']->hasEntityAccess(
                    $permissions['plugin:focus:items:editown'],
                    $permissions['plugin:focus:items:editother'],
                    $item->getCreatedBy()
                ),
                'clone'  => $permissions['plugin:focus:items:create'],
                'delete' => $view['security']->hasEntityAccess(
                    $permissions['plugin:focus:items:deleteown'],
                    $permissions['plugin:focus:items:deleteother'],
                    $item->getCreatedBy()
                ),
                'close' => $view['security']->isGranted('plugin:focus:items:view'),
            ],
            'routeBase' => 'focus',
            'langVar'   => 'focus',
        ]
    )
);

?>
<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- form detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-10">
                        <div class="text-muted"><?php echo $item->getDescription(); ?></div>
                    </div>
                    <div class="col-xs-2 text-right">
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $item]); ?>
                    </div>
                </div>
            </div>
            <!--/ form detail header -->

            <!-- form detail collapseable -->
            <div class="collapse" id="focus-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                            <?php echo $view->render('MauticCoreBundle:Helper:details.html.php', ['entity' => $item]); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ form detail collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- form detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.details'); ?>">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse" data-target="#focus-details"><span class="caret"></span> <?php echo $view['translator']->trans(
                            'mautic.core.details'
                        ); ?></a>
                </span>
            </div>
            <!--/ form detail collapseable toggler -->

            <!-- stats -->
            <div class="pa-md">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="col-xs-4 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">
                                        <span class="fa fa-line-chart"></span>
                                        <?php echo $view['translator']->trans('mautic.focus.graph.stats'); ?>
                                    </h5>
                                </div>
                                <div class="col-xs-8 va-m">
                                    <?php echo $view->render(
                                        'MauticCoreBundle:Helper:graph_dateselect.html.php',
                                        ['dateRangeForm' => $dateRangeForm, 'class' => 'pull-right']
                                    ); ?>
                                </div>
                            </div>
                            <div class="pt-0 pl-15 pb-10 pr-15">
                                <?php echo $view->render(
                                    'MauticCoreBundle:Helper:chart.html.php',
                                    ['chartData' => $stats, 'chartType' => 'line', 'chartHeight' => 300]
                                ); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ stats -->

            <?php echo $view['content']->getCustomContent('details.stats.graph.below', $mauticTemplateVars); ?>

            <!-- tabs controls -->
            <?php if (!empty($trackables)): ?>
            <ul class="nav nav-tabs pr-md pl-md">
                <li class="active">
                    <a href="#clicks-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.trackable.click_counts'); ?>
                    </a>
                </li>
            </ul>
            <!--/ tabs controls -->

            <!-- start: tab-content -->
                <div class="tab-content pa-md">
                    <div class="tab-pane active bdr-w-0" id="clicks-container">
                        <?php echo $view->render('MauticPageBundle:Trackable:click_counts.html.php', ['trackables' => $trackables]); ?>
                    </div>
                </div>
                <!-- end: tab-content -->
            <?php endif; ?>

        </div>
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- form HTML -->
        <div class="pa-md">
            <div class="panel bg-info bg-light-lg bdr-w-0 mb-0">
                <div class="panel-body">
                    <h5 class="fw-sb mb-sm"><?php echo $view['translator']->trans('mautic.focus.install.header'); ?></h5>
                    <p class="mb-sm"><?php echo $view['translator']->trans('mautic.focus.install.description'); ?></p>

                    <input onclick="this.setSelectionRange(0, this.value.length);" type="text" class="form-control" readonly value="&lt;script src=&quot;<?php echo $view['router']->url(
                        'mautic_focus_generate',
                        ['id' => $item->getId()],
                        true
                    ); ?>&quot; type=&quot;text/javascript&quot; charset=&quot;utf-8&quot; async=&quot;async&quot;&gt;&lt;/script&gt;"/>
                </div>
            </div>
        </div>
        <!--/ form HTML -->

        <hr class="hr-w-2" style="width:50%">

        <!--
        we can leverage data from audit_log table
        and build activity feed from it
        -->
        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mb-0">

            <!-- recent activity -->
            <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]); ?>

        </div>
    </div>
    <!--/ right section -->
</div>
<!--/ end: box layout -->

<input type="hidden" name="entityId" id="entityId" value="<?php echo $item->getId(); ?>"/>
