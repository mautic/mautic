<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var \Mautic\DynamicContentBundle\Entity\DynamicContent $entity */
if (!$isEmbedded) {
    $view->extend('MauticCoreBundle:Default:content.html.php');
}
$view['slots']->set('mauticContent', 'dynamicContent');
$view['slots']->set('headerTitle', $entity->getName());

$translationContent = $view->render(
    'MauticCoreBundle:Translation:index.html.php',
    [
        'activeEntity' => $entity,
        'translations' => $translations,
        'model'        => 'dynamicContent',
        'actionRoute'  => 'mautic_dynamicContent_action',
    ]
);
$showTranslations = !empty(trim($translationContent));

$customButtons = [];
if (!$isEmbedded) {
    $view['slots']->set(
        'actions',
        $view->render(
            'MauticCoreBundle:Helper:page_actions.html.php',
            [
                'item'            => $entity,
                'customButtons'   => (isset($customButtons)) ? $customButtons : [],
                'templateButtons' => [
                    'edit' => $view['security']->hasEntityAccess(
                        $permissions['dynamiccontent:dynamiccontents:editown'],
                        $permissions['dynamiccontent:dynamiccontents:editother'],
                        $entity->getCreatedBy()
                    ),
                    'clone'  => $permissions['dynamiccontent:dynamiccontents:create'],
                    'delete' => $view['security']->hasEntityAccess(
                        $permissions['dynamiccontent:dynamiccontents:deleteown'],
                        $permissions['dynamiccontent:dynamiccontents:deleteother'],
                        $entity->getCreatedBy()
                    ),
                    'close' => $view['security']->hasEntityAccess(
                        $permissions['dynamiccontent:dynamiccontents:viewown'],
                        $permissions['dynamiccontent:dynamiccontents:viewother'],
                        $entity->getCreatedBy()
                    ),
                ],
                'routeBase' => 'dynamicContent',
            ]
        )
    );
    $view['slots']->set(
        'publishStatus',
        $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $entity])
    );
}
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- page detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-10">
                        <div class="text-muted"><?php echo $entity->getDescription(); ?></div>
                        <?php if ($entity->isVariant(true)): ?>
                            <div class="small">
                                <a href="<?php echo $view['router']->path('mautic_dynamicContent_action', ['objectAction' => 'view', 'objectId' => $variants['parent']->getId()]); ?>" data-toggle="ajax">
                                    <?php echo $view['translator']->trans('mautic.core.variant_of', ['%parent%' => $variants['parent']->getName()]); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if ($entity->isTranslation(true)): ?>
                            <div class="small">
                                <a href="<?php echo $view['router']->path('mautic_dynamicContent_action', ['objectAction' => 'view', 'objectId' => $translations['parent']->getId()]); ?>" data-toggle="ajax">
                                    <?php echo $view['translator']->trans('mautic.core.translation_of', ['%parent%' => $translations['parent']->getName()]); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if (!$entity->getIsCampaignBased()): ?>
                            <div class="small">
                                <?php echo $view['translator']->trans('mautic.dynamicContent.header.is_filter_based', ['%slot%' => $entity->getSlotName()]); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!--/ page detail header -->
            <!-- page detail collapseable -->
            <div class="collapse" id="page-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:details.html.php',
                                ['entity' => $entity]
                            ); ?>
                            <tr>
                                <td width="20%"><span class="fw-b">
                                    <?php echo $view['translator']->trans('mautic.dynamicContent.slot.campaign'); ?>
                                </td>
                                <td>
                                    <?php echo $entity->getIsCampaignBased() ? 'Yes' : 'No'; ?>
                                </td>
                            </tr>
                            <?php if (!$entity->getIsCampaignBased()) : ?>
                            <tr>
                                <td width="20%"><span class="fw-b">
                                    <?php echo $view['translator']->trans('mautic.dynamicContent.label.slot_name'); ?>
                                </td>
                                <td>
                                    <?php echo $entity->getSlotName(); ?>
                                </td>
                            </tr>
                            <?php endif ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ page detail collapseable -->
        </div>
        <div class="bg-auto bg-dark-xs">
            <!-- page detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="Detail">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse"
                       data-target="#page-details">
                        <span class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?>
                    </a>
                </span>
            </div>
            <!--/ page detail collapseable toggler -->

            <!-- some stats -->
            <div class="pa-md">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="col-md-3 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">
                                        <span class="fa fa-line-chart"></span>
                                        <?php echo $view['translator']->trans('mautic.dynamicContent.views'); ?>
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

            <?php echo $view['content']->getCustomContent('details.stats.graph.below', $mauticTemplateVars); ?>

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <li class="active">
                    <a href="#clicks-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.trackable.click_counts'); ?>
                    </a>
                </li>
                <?php if ($showTranslations): ?>
                <li class>
                    <a href="#translation-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.core.translations'); ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <!--/ tabs controls -->
        </div>
        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <div class="tab-pane active active bdr-w-0" id="clicks-container">
                <?php echo $view->render('MauticPageBundle:Trackable:click_counts.html.php', [
                    'trackables' => $trackables,
                    'entity'     => $entity,
                    'channel'    => 'dynamicContent',
                ]); ?>

            </div>
            <!-- #translation-container -->
            <?php if ($showTranslations): ?>
                <div class="tab-pane bdr-w-0" id="translation-container">
                    <?php echo $translationContent; ?>
                </div>
            <?php endif; ?>
            <!--/ #translation-container -->
        </div>
        <!-- end: tab-content -->
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <hr class="hr-w-2" style="width:50%">
        <!-- recent activity -->
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]); ?>
    </div>
    <!--/ right section -->
</div>

<!--/ end: box layout -->
