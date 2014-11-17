<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'campaign');
$view['slots']->set("headerTitle", $campaign->getName());
?>
<?php
$view['slots']->start('actions');
if ($permissions['campaign:campaigns:edit']): ?>
    <a href="<?php echo $this->container->get('router')->generate(
        'mautic_campaign_action', array("objectAction" => "edit", "objectId" => $campaign->getId())); ?>"
       data-toggle="ajax"
       data-menu-link="#mautic_campaign_index"
       class="btn btn-default">
        <i class="fa fa-fw fa-pencil-square-o"></i><?php echo $view["translator"]->trans("mautic.core.form.edit"); ?>
    </a>
<?php endif; ?>
<?php if ($permissions['campaign:campaigns:delete']): ?><a href="javascript:void(0);"
   onclick="Mautic.showConfirmation(
       '<?php echo $view->escape($view["translator"]->trans("mautic.campaign.confirmdelete",
       array("%name%" => $campaign->getName() . " (" . $campaign->getId() . ")")), 'js'); ?>',
       '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
       'executeAction',
       ['<?php echo $view['router']->generate('mautic_campaign_action',
       array("objectAction" => "delete", "objectId" => $campaign->getId())); ?>',
       '#mautic_campaign_index'],
       '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);"
    class="btn btn-default">
    <span><i class="fa fa-fw fa-trash-o"></i><?php echo $view['translator']->trans('mautic.core.form.delete'); ?></span>
</a>
<?php endif; ?>
<?php $view['slots']->stop(); ?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- campaign detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-6 va-m">
                        <p class="text-white dark-sm mb-0"><?php echo $campaign->getDescription(); ?></p>
                    </div>
                    <div class="col-xs-6 va-m text-right">
                        <?php
                        $publishedStatus = $campaign->getPublishStatus();
                        switch ($publishedStatus) {
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
                        <?php $labelText = strtoupper($view['translator']->trans('mautic.core.form.' . $publishedStatus)); ?>
                        <h4 class="fw-sb"><span class="label label-<?php echo $labelColor; ?>"><?php echo $labelText; ?></span></h4>
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
                                <?php echo $view->render('MauticCoreBundle:Helper:details.html.php', array('entity' => $campaign)); ?>
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
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse" data-target="#campaign-details"><span class="caret"></span>  <?php echo $view['translator']->trans('mautic.core.details'); ?></a>
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
                                <div>
                                    <canvas id="campaign-leads-chart" height="93"></canvas>
                                </div>
                            </div>
                            <div id="campaign-leads-chart-data" class="hide"><?php echo json_encode($leadStats); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel ovf-h bg-auto bg-light-xs">
                            <div class="panel-body box-layout pb-0">
                                <div class="col-xs-8 va-m">
                                    <h5 class="dark-md fw-sb mb-xs">
                                        <?php echo $view['translator']->trans('mautic.campaign.campaign.new.returning'); ?>
                                    </h5>
                                </div>
                                <div class="col-xs-4 va-t text-right">
                                    <h3 class="text-white dark-sm"><span class="fa fa-bookmark-o"></span></h3>
                                </div>
                            </div>
                            <div class="text-center">
                                <canvas id="emails-sent-rate" width="110" height="110"></canvas>
                                <div id="emails-sent-data" class="hide">
                                    <?php echo json_encode($emailsSent); ?>
                                </div>
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
                                <div>
                                    <canvas id="campaign-views-chart" height="93"></canvas>
                                </div>
                            </div>
                            <div id="campaign-views-chart-data" class="hide"><?php echo json_encode($hits); ?></div>
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
            </ul>
            <!--/ tabs controls -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <!-- #events-container -->
            <div class="tab-pane active fade in bdr-w-0" id="decisions-container">
                <?php echo $view->render('MauticCampaignBundle:Campaign:events.html.php', array('events' => $events, 'eventType' => 'decision')); ?>
            </div>
            <div class="tab-pane fade in bdr-w-0" id="actions-container">
                <?php echo $view->render('MauticCampaignBundle:Campaign:events.html.php', array('events' => $events, 'eventType' => 'action')); ?>
            </div>
            <!--/ #events-container -->
        </div>
        <!--/ end: tab-content -->
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">

        <!-- recent activity -->
        <?php echo $view->render('MauticCoreBundle:Default:recentactivity.html.php', array('logs' => $logs)); ?>

    </div>
    <!--/ right section -->
</div>
<!--/ end: box layout -->