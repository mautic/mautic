<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!$isEmbedded) {
    $view->extend('MauticCoreBundle:Default:content.html.php');

    $view['slots']->set('mauticContent', 'monitoring');
    $view['slots']->set('headerTitle', $activeMonitoring->getTitle());
// @todo finish ACL here
    $view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', [
        'item'            => $activeMonitoring,
        'templateButtons' => [
            'edit'   => $view['security']->isGranted('plugin:mauticSocial:monitoring:edit'),
            'delete' => $view['security']->isGranted('plugin:mauticSocial:monitoring:delete'),
            'close'  => $view['security']->isGranted('plugin:mauticSocial:monitoring:view'),
        ],
        'routeBase'  => 'social',
        'langVar'    => 'monitoring',
        'nameGetter' => 'getTitle',
    ]));
}
echo $view['assets']->includeScript('plugins/MauticSocialBundle/Assets/js/social.js');
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- monitoring detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-10 va-m">
                        <div class="text-white dark-sm mb-0"><?php echo $activeMonitoring->getDescription(); ?></div>
                    </div>
                    <div class="col-xs-2 text-right">
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $activeMonitoring]); ?>
                    </div>
                </div>
            </div>
            <!--/ monitoring detail header -->
            <!-- monitoring detail collapseable -->
            <div class="collapse" id="asset-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                                <?php echo $view->render('MauticCoreBundle:Helper:details.html.php', ['entity' => $activeMonitoring]); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/  monitoring collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- stats -->
            <div class="pa-md">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="col-md-3 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">
                                        <span class="fa fa-twitter"></span>
                                        <?php echo $view['translator']->trans('mautic.social.monitoring.'.$activeMonitoring->getNetworkType().'.popularity'); ?>                                    </h5>
                                </div>
                                <div class="col-md-9 va-m">
                                    <?php echo $view->render('MauticCoreBundle:Helper:graph_dateselect.html.php', ['dateRangeForm' => $dateRangeForm, 'class' => 'pull-right']); ?>
                                </div>
                            </div>
                            <div class="pt-0 pl-15 pb-10 pr-15">
                                <?php echo $view->render('MauticCoreBundle:Helper:chart.html.php', ['chartData' => $leadStats, 'chartType' => 'line', 'chartHeight' => 300]); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ stats -->

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <li class="active">
                    <a href="#leads-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.lead.leads'); ?>
                    </a>
                </li>
            </ul>
            <!--/ tabs controls -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <!-- #events-container -->

            <div class="tab-pane active fade in bdr-w-0 page-list" id="leads-container">
                <?php echo $monitorLeads; ?>
            </div>
        </div>

    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- recent activity -->
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]); ?>
    </div>
    <!--/ right section -->

    <input id="itemId" type="hidden" value="<?php echo $activeMonitoring->getId(); ?>" />
</div>
<!--/ end: box layout -->