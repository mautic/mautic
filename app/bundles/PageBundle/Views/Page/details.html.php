<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var \Mautic\PageBundle\Entity\Page $activePage */
//@todo - add landing page stats/analytics
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'page');
$view['slots']->set("headerTitle", $activePage->getTitle());

$variantContent = $view->render(
    'MauticCoreBundle:Variant:index.html.php',
    [
        'activeEntity'  => $activePage,
        'variants'      => $variants,
        'abTestResults' => $abTestResults,
        'model'         => 'page',
        'actionRoute'   => 'mautic_page_action',
        'nameGetter'    => 'getTitle',
    ]
);
$showVariants    = !empty(trim($variantContent));

$translationContent = $view->render(
    'MauticCoreBundle:Translation:index.html.php',
    [
        'activeEntity' => $activePage,
        'translations' => $translations,
        'model'        => 'page',
        'actionRoute'  => 'mautic_page_action',
        'nameGetter'   => 'getTitle',
    ]
);
$showTranslations   = !empty(trim($translationContent));

if ((empty($variants['parent']) || ($variants['parent']->getId() == $activePage->getId())) && $permissions['page:pages:create']) {
    $customButtons[] = [
        'attr'      => [
            'data-toggle' => 'ajax',
            'href'        => $view['router']->path(
                'mautic_page_action',
                ["objectAction" => 'abtest', 'objectId' => $activePage->getId()]
            ),
        ],
        'iconClass' => 'fa fa-sitemap',
        'btnText'   => $view['translator']->trans('mautic.core.form.ab_test'),
    ];
}

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'item'            => $activePage,
            'customButtons'   => (isset($customButtons)) ? $customButtons : [],
            'templateButtons' => [
                'edit'   => $view['security']->hasEntityAccess(
                    $permissions['page:pages:editown'],
                    $permissions['page:pages:editother'],
                    $activePage->getCreatedBy()
                ),
                'clone'  => $view['security']->hasEntityAccess(
                    $permissions['page:pages:editown'],
                    $permissions['page:pages:editother'],
                    $activePage->getCreatedBy()
                ),
                'delete' => $permissions['page:pages:create'],
                'close'  => $view['security']->hasEntityAccess(
                    $permissions['page:pages:viewown'],
                    $permissions['page:pages:viewother'],
                    $activePage->getCreatedBy()
                ),
            ],
            'routeBase'       => 'page',
        ]
    )
);
$view['slots']->set(
    'publishStatus',
    $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $activePage])
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
                        <div class="text-muted"><?php echo $activePage->getMetaDescription(); ?></div>
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
                            <?php echo $view->render('MauticCoreBundle:Helper:details.html.php', ['entity' => $activePage]); ?>
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
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse" data-target="#page-details">
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
                                        <?php echo $view['translator']->trans('mautic.page.pageviews'); ?>
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
                                    ['chartData' => $stats['pageviews'], 'chartType' => 'line', 'chartHeight' => 300]
                                ); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ stats -->

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <?php if ($showVariants): ?>
                    <li class="active">
                        <a href="#variants-container" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.core.variants'); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($showTranslations): ?>
                    <li class="<?php echo ($showVariants) ? '' : 'active'; ?>">
                        <a href="#translation-container" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.core.translations'); ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <!--/ tabs controls -->
        </div>

        <?php if ($showVariants || $showTranslations): ?>
        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <?php if ($showVariants): ?>
            <!-- #variants-container -->
            <div class="tab-pane active bdr-w-0" id="variants-container">
                <?php echo $variantContent; ?>
            </div>
            <!--/ #variants-container -->
            <?php endif; ?>
            <!-- #translation-container -->
            <?php if ($showTranslations): ?>
            <div class="tab-pane <?php echo ($showVariants) ? '' : 'active '; ?>fade in bdr-w-0" id="translation-container">
                <?php echo $translationContent; ?>
            </div>
            <?php endif; ?>
            <!--/ #translation-container -->
        </div>
        <!--/ end: tab-content -->
    <?php elseif ((empty($variants['parent']) || ($variants['parent']->getId() == $activePage->getId())) && $permissions['page:pages:create']): ?>
        <div class="pa-md">
            <div class="text-center" style="height: 100%; width: 100%; ">
                <h3 style="padding: 30px;">
                    <a class="create-abtest-link" href="<?php echo $view['router']->path('mautic_page_action', ['objectAction' => 'abtest', 'objectId' => $activePage->getId()]); ?>" data-toggle="ajax">
                        <?php echo $view['translator']->trans('mautic.core.ab_test.create'); ?> <i class="fa fa-angle-right"></i>
                    </a>
                </h3>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- preview URL -->
        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mt-sm mb-0">
            <div class="panel-heading">
                <div class="panel-title"><?php echo $view['translator']->trans('mautic.page.url'); ?></div>
            </div>
            <div class="panel-body pt-xs">
                <div class="input-group">
                    <input onclick="this.setSelectionRange(0, this.value.length);" type="text" class="form-control"
                           readonly
                           value="<?php echo $pageUrl; ?>"/>
                    <span class="input-group-btn">
                        <button class="btn btn-default btn-nospin" onclick="window.open('<?php echo $pageUrl; ?>', '_blank');">
                            <i class="fa fa-external-link"></i>
                        </button>
                    </span>
                </div>
            </div>
        </div>
        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mt-sm mb-0">
            <div class="panel-heading">
                <div class="panel-title"><?php echo $view['translator']->trans('mautic.page.preview.url'); ?></div>
            </div>
            <div class="panel-body pt-xs">
                <div class="input-group">
                    <input onclick="this.setSelectionRange(0, this.value.length);" type="text" class="form-control"
                           readonly
                           value="<?php echo $previewUrl; ?>"/>
                    <span class="input-group-btn">
                    <button class="btn btn-default btn-nospin"
                            onclick="window.open('<?php echo $previewUrl; ?>', '_blank');">
                        <i class="fa fa-external-link"></i>
                    </button>
                </span>
                </div>
            </div>
        </div>
        <!--/ preview URL -->
        <hr class="hr-w-2" style="width:50%">
        <!-- recent activity -->
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]); ?>
    </div>
    <!--/ right section -->
</div>

<!--/ end: box layout -->
