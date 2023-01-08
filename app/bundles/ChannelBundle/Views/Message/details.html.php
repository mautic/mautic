<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set('mauticContent', 'message');
$view['slots']->set('headerTitle', $item->getName());

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', [
    'item'            => $item,
    'templateButtons' => [
        'edit'   => $view['security']->hasEntityAccess($permissions['channel:messages:editown'], $permissions['channel:messages:editother'], $item->getCreatedBy()),
        'clone'  => $permissions['channel:messages:create'],
        'delete' => $view['security']->hasEntityAccess($permissions['channel:messages:deleteown'], $permissions['channel:messages:deleteown'], $item->getCreatedBy()),
    ],
    'routeBase' => 'message',
]));
$view['slots']->set(
    'publishStatus',
    $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $item])
);
?>
    <!-- start: box layout -->
    <div class="box-layout">
        <!-- left section -->
        <div class="col-md-9 height-auto">
            <div class="bg-auto">
                <!-- form detail header -->
                <div class="pr-md pl-md pt-lg pb-lg">
                    <div class="box-layout">
                        <div class="col-xs-10">
                            <div class="text-muted"><?php echo $item->getDescription(); ?></div>
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

        <!--/ form detail collapseable toggler -->
            <div class="bg-auto bg-dark-xs">
                <!-- form detail collapseable toggler -->
                <div class="hr-expand nm">
                        <span data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.details'); ?>">
                            <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse" data-target="#focus-details"><span class="caret"></span> <?php echo $view['translator']->trans(
                                    'mautic.core.details'
                                ); ?></a>
                        </span>
                </div>
                <!-- stats -->
                <div class="pa-md">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="panel">
                                <div class="panel-body box-layout">
                                    <div class="col-md-6 va-m">
                                        <h5 class="text-white dark-md fw-sb mb-xs">
                                            <div><i class="fa fa-line-chart pull-left"></i>
                                                <span class="pull-left"> <?php echo $view['translator']->trans('mautic.messages.processed.messages'); ?></span></div>
                                        </h5>
                                    </div>
                                    <div class="col-md-9 va-m">
                                        <?php echo $view->render('MauticCoreBundle:Helper:graph_dateselect.html.php', ['dateRangeForm' => $dateRangeForm, 'class' => 'pull-right']); ?>
                                    </div>
                                </div>
                                <div class="pt-0 pl-15 pb-10 pr-15">
                                    <?php echo $view->render('MauticCoreBundle:Helper:chart.html.php', ['chartData' => $eventCounts, 'chartType' => 'line', 'chartHeight' => 300]); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--/ stats -->

                <?php echo $view['content']->getCustomContent('details.stats.graph.below', $mauticTemplateVars); ?>

                <!-- tabs controls -->
                <ul class="nav nav-tabs pr-md pl-md">
                    <?php $active = 'active'; ?>
                    <?php foreach ($messagedLeads as $channel => $contacts): ?>
                    <li class="<?php echo $active; ?>">
                        <a href="#contacts-<?php echo $channel; ?>" role="tab" data-toggle="tab">
                            <?php echo ('all' !== $channel) ? $channels[$channel]['label'] : $view['translator']->trans('mautic.lead.leads'); ?>
                        </a>
                    </li>
                    <?php $active = ''; ?>
                    <?php endforeach; ?>
                </ul>
                <!--/ tabs controls -->
            </div>

            <!-- start: tab-content -->
            <div class="tab-content pa-md">
                <?php $active = ' active in'; ?>
                <?php foreach ($messagedLeads as $channel => $contacts): ?>
                <div class="tab-pane bdr-w-0 page-list<?php echo $active; ?>" id="contacts-<?php echo $channel; ?>">
                    <?php $message = ('all' === $channel) ? 'mautic.channel.message.all_contacts' : 'mautic.channel.message.channel_contacts'; ?>
                    <div class="alert alert-info"><strong><?php echo $view['translator']->trans($message); ?></strong></div>
                    <div class="message-<?php echo $channel; ?>">
                        <?php echo $contacts; ?>
                    </div>
                </div>
                <?php $active = ''; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- right section -->
        <div class="col-md-3 bg-white bdr-l height-auto">
            <!-- recent activity -->
            <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]);
            $view['slots']->start('rightFormContent');
            $view['slots']->stop();
            ?>
        </div>
    </div>
<?php
