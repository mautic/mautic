<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var \Mautic\DynamicContentBundle\Entity\DynamicContent $entity */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'dynamicContent');
$view['slots']->set('headerTitle', $entity->getName());

$showVariants = (count($variants['children'])
    || (!empty($variants['parent'])
        && $variants['parent']->getId() != $entity->getId()));

$customButtons = [];
//if ((empty($variants['parent']) || ($variants['parent']->getId() == $entity->getId()))
//    && $permissions['dynamicContent:dynamicContents:create']
//) {
//    $customButtons[] = [
//        'attr' => [
//            'data-toggle' => 'ajax',
//            'href' => $view['router']->generate(
//                'mautic_dynamicContent_action',
//                ['objectAction' => 'addvariant', 'objectId' => $entity->getId()]
//            ),
//        ],
//        'iconClass' => 'fa fa-sitemap',
//        'btnText' => $view['translator']->trans('mautic.core.form.addvariant'),
//    ];
//}

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'item' => $entity,
            'customButtons' => (isset($customButtons)) ? $customButtons : [],
            'templateButtons' => [
                'edit' => $security->hasEntityAccess(
                    $permissions['dynamicContent:dynamicContents:editown'],
                    $permissions['dynamicContent:dynamicContents:editother'],
                    $entity->getCreatedBy()
                ),
                'clone' => $security->hasEntityAccess(
                    $permissions['dynamicContent:dynamicContents:editown'],
                    $permissions['dynamicContent:dynamicContents:editother'],
                    $entity->getCreatedBy()
                ),
                'delete' => $permissions['dynamicContent:dynamicContents:create'],
                'close' => $security->hasEntityAccess(
                    $permissions['dynamicContent:dynamicContents:viewown'],
                    $permissions['dynamicContent:dynamicContents:viewother'],
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
                       data-target="#page-details"><span class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?></a>
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

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <li class="active">
                    <a href="#clicks-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.trackable.click_counts'); ?>
                    </a>
                </li>
                <?php if ($showVariants): ?>
                <li class>
                    <a href="#variants-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.dynamicContent.variants'); ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <!--/ tabs controls -->
        </div>
        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <div class="tab-pane active bdr-w-0" id="clicks-container">
                <?php echo $view->render('MauticPageBundle:Trackable:click_counts.html.php', ['trackables' => $trackables]); ?>
            </div>
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
