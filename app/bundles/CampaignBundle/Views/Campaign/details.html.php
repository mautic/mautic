<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'campaign');
$view['slots']->set("headerTitle", $campaign->getName());

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        array(
            'item'            => $campaign,
            'templateButtons' => array(
                'edit'   => $permissions['campaign:campaigns:edit'],
                'clone'  => $permissions['campaign:campaigns:create'],
                'delete' => $permissions['campaign:campaigns:delete'],
                'close'  => $permissions['campaign:campaigns:view'],
            ),
            'routeBase'       => 'campaign'
        )
    )
);
$view['slots']->set(
    'publishStatus',
    $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', array('entity' => $campaign))
);
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- campaign detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-6 va-m">
                        <div class="text-white dark-sm mb-0"><?php echo $campaign->getDescription(); ?></div>
                    </div>

                </div>
            </div>
            <!--/ campaign detail header -->

            <!-- campaign detail collapseable -->
            <div class="collapse" id="campaign-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:details.html.php',
                                array('entity' => $campaign)
                            ); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ campaign detail collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- campaign detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="Detail">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse"
                       data-target="#campaign-details"><span
                            class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?></a>
                </span>
            </div>
            <!--/ campaign detail collapseable toggler -->

            <!-- stats -->
            <div class="pa-md">
                <div class="row stat-boxes">
                    <div class="col-md-4">
                        <div class="panel ovf-h bg-auto bg-light-xs campaign-stat-charts">
                            <div class="panel-body box-layout">
                                <div class="col-xs-8 va-m">
                                    <h5 class="dark-md fw-sb mb-xs">
                                        <?php echo $view['translator']->trans('mautic.campaign.campaign.leads'); ?>
                                    </h5>
                                </div>
                                <div class="col-xs-4 va-t text-right">
                                    <h3 class="text-white dark-sm"><span class="fa fa-user"></span></h3>
                                </div>
                            </div>
                            <div class="pt-0 pl-10 pb-0 pr-10">
                                <?php echo $view->render('MauticCoreBundle:Helper:chart.html.php', array('chartData' => $leadStats, 'chartType' => 'simple-bar', 'chartHeight' => 93)); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel ovf-h bg-auto bg-light-xs">
                            <div class="panel-body box-layout pb-0">
                                <div class="col-xs-8 va-m">
                                    <h5 class="dark-md fw-sb mb-xs">
                                        <?php echo $view['translator']->trans(
                                            'mautic.campaign.campaign.new.returning'
                                        ); ?>
                                    </h5>
                                </div>
                                <div class="col-xs-4 va-t text-right">
                                    <h3 class="text-white dark-sm"><span class="fa fa-bookmark-o"></span></h3>
                                </div>
                            </div>
                            <div class="text-center">
                                <?php echo $view->render('MauticCoreBundle:Helper:chart.html.php', array('chartData' => $emailsSent, 'chartType' => 'pie', 'chartHeight' => 93)); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel ovf-h bg-auto bg-light-xs campaign-stat-charts">
                            <div class="panel-body box-layout">
                                <div class="col-xs-8 va-m">
                                    <h5 class="dark-md fw-sb mb-xs">
                                        <?php echo $view['translator']->trans('mautic.campaign.campaign.pageviews'); ?>
                                    </h5>
                                </div>
                                <div class="col-xs-4 va-t text-right">
                                    <h3 class="text-white dark-sm"><span class="fa fa-eye"></span></h3>
                                </div>
                            </div>
                            <div class="pt-0 pl-10 pb-0 pr-10">
                                <?php echo $view->render('MauticCoreBundle:Helper:chart.html.php', array('chartData' => $hits, 'chartType' => 'simple-bar', 'chartHeight' => 93)); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ stats -->

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <li class="active">
                    <a href="#decisions-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.campaign.event.decisions.header'); ?>
                    </a>
                </li>
                <li class="">
                    <a href="#actions-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.campaign.event.actions.header'); ?>
                    </a>
                </li>
                <li>
                    <a href="#conditions-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.campaign.event.conditions.header'); ?>
                    </a>
                </li>
                <li class="">
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
            <div class="tab-pane active fade in bdr-w-0" id="decisions-container">
                <?php echo $view->render(
                    'MauticCampaignBundle:Campaign:events.html.php',
                    array('events' => $events, 'eventType' => 'decision')
                ); ?>
            </div>
            <div class="tab-pane fade in bdr-w-0" id="actions-container">
                <?php echo $view->render(
                    'MauticCampaignBundle:Campaign:events.html.php',
                    array('events' => $events, 'eventType' => 'action')
                ); ?>
            </div>
            <div class="tab-pane fade in bdr-w-0" id="conditions-container">
                <?php echo $view->render(
                    'MauticCampaignBundle:Campaign:events.html.php',
                    array('events' => $events, 'eventType' => 'condition')
                ); ?>
            </div>
            <!--/ #events-container -->

            <div class="tab-pane fade in bdr-w-0 page-list" id="leads-container">
                <?php echo $campaignLeads; ?>
            </div>
        </div>
        <!--/ end: tab-content -->
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">

        <!-- recent activity -->
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', array('logs' => $logs)); ?>

    </div>
    <!--/ right section -->
</div>
<!--/ end: box layout -->
