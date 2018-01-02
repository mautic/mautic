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
$view['slots']->set('mauticContent', 'list');
$view['slots']->set('headerTitle', $list->getName());
$customButtons = [];

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'item'            => $list,
            'customButtons'   => (isset($customButtons)) ? $customButtons : [],
            'templateButtons' => [
                'edit' => $view['security']->hasEntityAccess(
                    $permissions['lead:leads:editown'],
                    $permissions['lead:lists:editother'],
                    $list->getCreatedBy()
                ),
                'delete' => $view['security']->hasEntityAccess(
                    $permissions['lead:lists:deleteother'],
                    $permissions['lead:lists:editother'],
                    $list->getCreatedBy()
                ),
                'close' => $view['security']->hasEntityAccess(
                    $permissions['lead:leads:editown'],
                    $permissions['lead:lists:viewother'],
                    $list->getCreatedBy()
                ),
            ],
            'routeBase' => 'segment',
        ]
    )
);

$view['slots']->set(
    'publishStatus',
    $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $list])
);

?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- page detail header -->
            <!-- sms detail collapseable toggler -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-10">
                        <div class="text-white dark-sm mb-0"><?php echo $list->getDescription(); ?></div>
                    </div>
                </div>
            </div>
            <div class="collapse" id="sms-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:details.html.php',
                                ['entity' => $list]
                            ); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!--/ sms detail collapseable toggler -->
        <div class="bg-auto bg-dark-xs">
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="Detail">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse"
                       data-target="#sms-details">
                        <span class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?>
                    </a>
                </span>
            </div>
            <!-- some stats -->

            <!--/ stats -->
            <div class="pa-md">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="col-md-3 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">
                                        <span class="fa fa-line-chart"></span>
                                        <?php echo $view['translator']->trans('mautic.segment.stats'); ?>
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
            <!-- tabs controls -->
            <!-- search bar-->
            <form method="post" action="<?php echo $view['router']->path('mautic_segment_contacts', ['objectId' => $list->getId()]); ?>" class="panel" id="segment-contact-filters">
                <?php if (isset($events['types']) && is_array($events['types'])) : ?>
                    <div class="history-search panel-footer text-muted">
                        <div class="col-sm-5">
                            <select name="includeEvents[]" multiple="multiple" class="form-control bdr-w-0" data-placeholder="<?php echo $view['translator']->trans('mautic.lead.lead.filter.bundles.include.placeholder'); ?>">
                                <?php foreach ($events['types'] as $typeKey => $typeName) : ?>
                                    <option value="<?php echo $typeKey; ?>">
                                        <?php echo $typeName; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>

            </form>
            <!--/ search bar -->
            <ul class="nav nav-tabs pr-md pl-md">
                <li class="active">
                    <a href="#contacts-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.lead.leads'); ?>
                    </a>
                </li>
            </ul>
            <!--/ tabs controls -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <div class="tab-pane active bdr-w-0 page-list" id="contacts-container">
                <?php echo $contacts; ?>
            </div>
        </div>
        <!-- end: tab-content -->
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- activity feed -->
        <?php // echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]);?>
    </div>
    <!--/ right section -->
    <input name="entityId" id="entityId" type="hidden" value="<?php echo $list->getId(); ?>" />
</div>
<!--/ end: box layout -->