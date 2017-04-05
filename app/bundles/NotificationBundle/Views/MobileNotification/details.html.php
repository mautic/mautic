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
$view['slots']->set('mauticContent', 'notification');
$view['slots']->set('headerTitle', $notification->getName());

$notificationType = $notification->getNotificationType();
if (empty($notificationType)) {
    $notificationType = 'template';
}

$customButtons = [];

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'item'            => $notification,
            'customButtons'   => (isset($customButtons)) ? $customButtons : [],
            'templateButtons' => [
                'edit' => $view['security']->hasEntityAccess(
                    $permissions['notification:mobile_notifications:editown'],
                    $permissions['notification:mobile_notifications:editother'],
                    $notification->getCreatedBy()
                ),
                'delete' => $permissions['notification:mobile_notifications:create'],
                'close'  => $view['security']->hasEntityAccess(
                    $permissions['notification:mobile_notifications:viewown'],
                    $permissions['notification:mobile_notifications:viewother'],
                    $notification->getCreatedBy()
                ),
            ],
            'routeBase' => 'mobile_notification',
        ]
    )
);
$view['slots']->set(
    'publishStatus',
    $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $notification])
);
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto bg-dark-xs">
            <!-- notification detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.details'); ?>">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse" data-target="#notification-details"><span class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?></a>
                </span>
            </div>
            <!--/ notification detail collapseable toggler -->

            <!-- some stats -->
            <div class="pa-md">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="col-md-3 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">
                                        <span class="fa fa-line-chart"></span>
                                        <?php echo $view['translator']->trans('mautic.core.stats'); ?>
                                    </h5>
                                </div>
                                <div class="col-md-9 va-m">
                                    <?php echo $view->render('MauticCoreBundle:Helper:graph_dateselect.html.php', ['dateRangeForm' => $dateRangeForm, 'class' => 'pull-right']); ?>
                                </div>
                            </div>
                            <div class="pt-0 pl-15 pb-10 pr-15">
                                <?php echo $view->render('MauticCoreBundle:Helper:chart.html.php', ['chartData' => $entityViews, 'chartType' => 'line', 'chartHeight' => 300]); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ stats -->

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <li class="active">
                    <a href="#clicks-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.trackable.click_counts'); ?>
                    </a>
                </li>
                <li class="">
                    <a href="#contacts-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.lead.leads'); ?>
                    </a>
                </li>
            </ul>
            <!--/ tabs controls -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <div class="tab-pane active bdr-w-0" id="clicks-container">
                <?php echo $view->render('MauticPageBundle:Trackable:click_counts.html.php', ['trackables' => $trackables]); ?>
            </div>

            <div class="tab-pane fade in bdr-w-0 page-list" id="contacts-container">
                <?php echo $contacts; ?>
            </div>
        </div>
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- activity feed -->
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]); ?>
    </div>
    <!--/ right section -->
    <input name="entityId" id="entityId" type="hidden" value="<?php echo $notification->getId(); ?>" />
</div>
<!--/ end: box layout -->
