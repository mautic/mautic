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
$view['slots']->set('headerTitle', $campaign->getName());

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'item'            => $campaign,
            'templateButtons' => [
                'edit'   => $permissions['campaign:campaigns:edit'],
                'clone'  => $permissions['campaign:campaigns:create'],
                'delete' => $permissions['campaign:campaigns:delete'],
                'close'  => $permissions['campaign:campaigns:view'],
            ],
            'routeBase' => 'campaign',
        ]
    )
);
$view['slots']->set(
    'publishStatus',
    $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $campaign])
);

$session = $this->get('session');

$campaignId = $campaign->getId();

$preview = trim($view->render('MauticCampaignBundle:Campaign:preview.html.php', [
    'campaignId'      => $campaignId,
    'campaignEvents'  => $campaignEvents,
    'campaignSources' => $campaignSources,
    'eventSettings'   => $eventSettings,
    'canvasSettings'  => $campaign->getCanvasSettings(),
]));

$decisions  = trim($view->render('MauticCampaignBundle:Campaign:events.html.php', ['events' => $events['decision']]));
$actions    = trim($view->render('MauticCampaignBundle:Campaign:events.html.php', ['events' => $events['action']]));
$conditions = trim($view->render('MauticCampaignBundle:Campaign:events.html.php', ['events' => $events['condition']]));

switch (true) {
    case !empty($preview):
        $firstTab = 'preview';
        break;
    case !empty($decisions):
        $firstTab = 'decision';
        break;
    case !empty($actions):
        $firstTab = 'action';
        break;
    case !empty($conditions):
        $firstTab = 'condition';
        break;
}
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
                                ['entity' => $campaign]
                            ); ?>
                            <?php foreach ($sources as $sourceType => $typeNames): ?>
                            <?php if (!empty($typeNames)): ?>
                            <tr>
                                <td width="20%"><span class="fw-b">
                                    <?php echo $view['translator']->trans('mautic.campaign.leadsource.'.$sourceType); ?>
                                </td>
                                <td>
                                    <?php echo implode(', ', $typeNames); ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
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

            <?php echo $view['content']->getCustomContent('left.section.top', $mauticTemplateVars); ?>
            <!-- some stats -->
            <div class="pa-md">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="col-md-3 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">
                                        <span class="fa fa-line-chart"></span>
                                        <?php echo $view['translator']->trans('mautic.campaign.stats'); ?>
                                    </h5>
                                </div>
                                <div class="col-md-9 va-m">
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

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <?php if ($preview): ?>
                     <li class="<?php if ('preview' == $firstTab): echo 'active'; endif; ?>">
                        <a href="#preview-container" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.campaign.preview.header'); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($decisions): ?>
                    <li class="<?php if ('decision' == $firstTab): echo 'active'; endif; ?>">
                        <a href="#decisions-container" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.campaign.event.decisions.header'); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($actions): ?>
                    <li class="<?php if ('action' == $firstTab): echo 'active'; endif; ?>">
                        <a href="#actions-container" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.campaign.event.actions.header'); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($conditions): ?>
                    <li class="<?php if ('condition' == $firstTab): echo 'active'; endif; ?>">
                        <a href="#conditions-container" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.campaign.event.conditions.header'); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="">
                    <a href="#leads-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.lead.leads'); ?>
                    </a>
                </li>
                <?php echo $view['content']->getCustomContent('tabs', $mauticTemplateVars); ?>
            </ul>
            <!--/ tabs controls -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <!-- #events-container -->
            <div class="<?php if ('preview' == $firstTab): echo 'active '; endif; ?> tab-pane fade in bdr-w-0" id="preview-container">
               <?php echo $preview; ?>
            </div>
            <?php if ($decisions): ?>
                <div class="<?php if ('decision' == $firstTab): echo 'active '; endif; ?> tab-pane fade in bdr-w-0" id="decisions-container">
                    <?php echo $decisions; ?>
                </div>
            <?php endif; ?>
            <?php if ($actions): ?>
                <div class="<?php if ('action' == $firstTab): echo 'active '; endif; ?> tab-pane fade in bdr-w-0" id="actions-container">
                    <?php echo $actions; ?>
                </div>
            <?php endif; ?>
            <?php if ($conditions): ?>
                <div class="<?php if ('condition' == $firstTab): echo 'active '; endif; ?> tab-pane fade in bdr-w-0" id="conditions-container">
                    <?php echo $conditions; ?>
                </div>
            <?php endif; ?>
            <!--/ #events-container -->
            <div class="tab-pane fade in bdr-w-0 page-list" id="leads-container">
                <?php echo $campaignLeads; ?>
                <div class="clearfix"></div>
            </div>
            <?php echo $view['content']->getCustomContent('tabs.content', $mauticTemplateVars); ?>
        </div>
        <!--/ end: tab-content -->

        <?php echo $view['content']->getCustomContent('left.section.bottom', $mauticTemplateVars); ?>
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <?php echo $view['content']->getCustomContent('right.section.top', $mauticTemplateVars); ?>
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]); ?>
        <?php echo $view['content']->getCustomContent('right.section.bottom', $mauticTemplateVars); ?>
    </div>
    <!--/ right section -->
</div>
<!--/ end: box layout -->
