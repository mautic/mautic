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
$view['slots']->set('mauticContent', 'dwc');
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
//                'mautic_dwc_action',
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
            'routeBase' => 'dwc',
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
                       data-target="#page-details"><span class="caret"></span> <?php echo $view['translator']->trans(
                            'mautic.core.details'
                        ); ?></a>
                </span>
            </div>
            <!--/ page detail collapseable toggler -->

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <?php if ($showVariants): ?>
                    <li class="active">
                        <a href="#variants-container" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.dynamicContent.variants'); ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <!--/ tabs controls -->
        </div>
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
