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

$showVariants     = (count($variants['children'])
    || (!empty($variants['parent'])
        && $variants['parent']->getId() != $activePage->getId()));
$showTranslations = (count($translations['children'])
    || (!empty($translations['parent'])
        && $translations['parent']->getId() != $activePage->getId()));

if ((empty($variants['parent']) || ($variants['parent']->getId() == $activePage->getId()))
    && $permissions['page:pages:create']
) {
    $customButtons[] = array(
        'attr'      => array(
            'data-toggle' => 'ajax',
            'href'        => $view['router']->path(
                'mautic_page_action',
                array("objectAction" => 'abtest', 'objectId' => $activePage->getId())
            ),
        ),
        'iconClass' => 'fa fa-sitemap',
        'btnText'   => $view['translator']->trans('mautic.core.form.abtest')
    );
}

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        array(
            'item'            => $activePage,
            'customButtons'   => (isset($customButtons))?$customButtons:array(),
            'templateButtons' => array(
                'edit'   => $security->hasEntityAccess(
                    $permissions['page:pages:editown'],
                    $permissions['page:pages:editother'],
                    $activePage->getCreatedBy()
                ),
                'clone'  => $security->hasEntityAccess(
                    $permissions['page:pages:editown'],
                    $permissions['page:pages:editother'],
                    $activePage->getCreatedBy()
                ),
                'delete' => $permissions['page:pages:create'],
                'close'  => $security->hasEntityAccess(
                    $permissions['page:pages:viewown'],
                    $permissions['page:pages:viewother'],
                    $activePage->getCreatedBy()
                ),
            ),
            'routeBase'       => 'page'
        )
    )
);
$view['slots']->set(
    'publishStatus',
    $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', array('entity' => $activePage))
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
                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:details.html.php',
                                array('entity' => $activePage)
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
                                    <?php echo $view->render('MauticCoreBundle:Helper:graph_dateselect.html.php', array('dateRangeForm' => $dateRangeForm, 'class' => 'pull-right')); ?>
                                </div>
                            </div>
                            <div class="pt-0 pl-15 pb-10 pr-15">
                                <?php echo $view->render('MauticCoreBundle:Helper:chart.html.php', array('chartData' => $stats['pageviews'], 'chartType' => 'line', 'chartHeight' => 300)); ?>
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
                            <?php echo $view['translator']->trans('mautic.page.variants'); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($showTranslations): ?>
                    <li class="<?php echo ($showVariants) ? '' : 'active'; ?>">
                        <a href="#translation-container" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.page.translations'); ?>
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
                        <!-- header -->
                        <?php if ($variants['parent']->getVariantStartDate() != null): ?>
                            <div class="box-layout mb-lg">
                                <div class="col-xs-10 va-m">
                                    <h4><?php echo $view['translator']->trans(
                                            'mautic.page.variantstartdate',
                                            array(
                                                '%time%' => $view['date']->toTime(
                                                    $variants['parent']->getVariantStartDate()
                                                ),
                                                '%date%' => $view['date']->toShort(
                                                    $variants['parent']->getVariantStartDate()
                                                ),
                                                '%full%' => $view['date']->toTime(
                                                    $variants['parent']->getVariantStartDate()
                                                )
                                            )
                                        ); ?></h4>
                                </div>
                                <!-- button -->
                                <div class="col-xs-2 va-m text-right">
                                    <a href="#" data-toggle="modal" data-target="#abStatsModal"
                                       class="btn btn-primary"><?php echo $view['translator']->trans(
                                            'mautic.page.ab.test.stats'
                                        ); ?></a>
                                </div>
                            </div>
                        <?php endif; ?>
                        <!--/ header -->

                        <!-- start: variants list -->
                        <ul class="list-group">
                            <?php if ($variants['parent']) : ?>
                                <?php $isWinner = (isset($abTestResults['winners'])
                                    && in_array(
                                        $variants['parent']->getId(),
                                        $abTestResults['winners']
                                    )
                                    && $variants['parent']->getVariantStartDate()
                                    && $variants['parent']->isPublished()); ?>
                                <li class="list-group-item bg-auto bg-light-xs">
                                    <div class="box-layout">
                                        <div class="col-md-8 va-m">
                                            <div class="row">
                                                <div class="col-xs-1">
                                                    <h3>
                                                        <?php echo $view->render(
                                                            'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                                            array(
                                                                'item'  => $variants['parent'],
                                                                'model' => 'page',
                                                                'size'  => '',
                                                                'query' => 'size='
                                                            )
                                                        ); ?>
                                                    </h3>
                                                </div>
                                                <div class="col-xs-11">
                                                    <?php if ($isWinner): ?>
                                                        <div class="mr-xs pull-left" data-toggle="tooltip"
                                                             title="<?php echo $view['translator']->trans(
                                                                 'mautic.page.abtest.parentwinning'
                                                             ); ?>">
                                                            <a class="btn btn-default disabled"
                                                               href="javascript:void(0);">
                                                                <i class="fa fa-trophy"></i>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                    <h5 class="fw-sb text-primary">
                                                        <a href="<?php echo $view['router']->path(
                                                            'mautic_page_action',
                                                            array(
                                                                'objectAction' => 'view',
                                                                'objectId'     => $variants['parent']->getId()
                                                            )
                                                        ); ?>"
                                                           data-toggle="ajax"><?php echo $variants['parent']->getTitle(
                                                            ); ?>
                                                            <?php if ($variants['parent']->getId()
                                                                == $activePage->getId()
                                                            ) : ?>
                                                                <span>[<?php echo $view['translator']->trans(
                                                                        'mautic.core.current'
                                                                    ); ?>]</span>
                                                            <?php endif; ?>
                                                            <span>[<?php echo $view['translator']->trans(
                                                                    'mautic.core.parent'
                                                                ); ?>]</span>
                                                        </a>
                                                    </h5>
                                                    <span
                                                        class="text-white dark-sm"><?php echo $variants['parent']->getAlias(
                                                        ); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 va-t text-right">
                                            <em class="text-white dark-sm"><span
                                                    class="label label-success"><?php echo (int) $variants['properties'][$variants['parent']->getId(
                                                    )]['weight']; ?>%</span></em>
                                        </div>
                                    </div>
                                </li>
                            <?php endif; ?>
                            <?php $totalWeight = (int) $variants['properties'][$variants['parent']->getId(
                            )]['weight']; ?>
                            <?php if (count($variants['children'])): ?>
                                <?php /** @var \Mautic\PageBundle\Entity\Page $variant */ ?>
                                <?php foreach ($variants['children'] as $id => $variant) :
                                    if (!isset($variants['properties'][$id])):
                                        $settings                    = $variant->getVariantSettings();
                                        $variants['properties'][$id] = $settings;
                                    endif;

                                    if (!empty($variants['properties'][$id])):
                                        $thisCriteria  = $variants['properties'][$id]['winnerCriteria'];
                                        $weight        = (int) $variants['properties'][$id]['weight'];
                                        $criteriaLabel = ($thisCriteria) ? $view['translator']->trans(
                                            $variants['criteria'][$thisCriteria]['label']
                                        ) : '';
                                    else:
                                        $thisCriteria = $criteriaLabel = '';
                                        $weight       = 0;
                                    endif;

                                    $isPublished = $variant->isPublished();
                                    $totalWeight += ($isPublished) ? $weight : 0;
                                    $firstCriteria = (!isset($firstCriteria)) ? $thisCriteria : $firstCriteria;
                                    $isWinner      = (isset($abTestResults['winners'])
                                        && in_array(
                                            $variant->getId(),
                                            $abTestResults['winners']
                                        )
                                        && $variants['parent']->getVariantStartDate()
                                        && $isPublished);
                                    ?>

                                    <li class="list-group-item bg-auto bg-light-xs">
                                        <div class="box-layout">
                                            <div class="col-md-8 va-m">
                                                <div class="row">
                                                    <div class="col-xs-1">
                                                        <h3>
                                                            <?php echo $view->render(
                                                                'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                                                array(
                                                                    'item'  => $variant,
                                                                    'model' => 'page',
                                                                    'size'  => '',
                                                                    'query' => 'size='
                                                                )
                                                            ); ?>
                                                        </h3>
                                                    </div>
                                                    <div class="col-xs-11">
                                                        <?php if ($isWinner): ?>
                                                            <div class="mr-xs pull-left" data-toggle="tooltip"
                                                                 title="<?php echo $view['translator']->trans(
                                                                     'mautic.page.abtest.makewinner'
                                                                 ); ?>">
                                                                <a class="btn btn-warning"
                                                                   data-toggle="confirmation"
                                                                   href="<?php echo $view['router']->path(
                                                                       'mautic_page_action',
                                                                       array(
                                                                           'objectAction' => 'winner',
                                                                           'objectId'     => $variant->getId()
                                                                       )
                                                                   ); ?>"
                                                                   data-message="<?php echo $view->escape(
                                                                       $view["translator"]->trans(
                                                                           "mautic.page.abtest.confirmmakewinner",
                                                                           array("%name%" => $variant->getTitle())
                                                                       )
                                                                   ); ?>"
                                                                   data-confirm-text="<?php echo $view->escape(
                                                                       $view["translator"]->trans(
                                                                           "mautic.page.abtest.makewinner"
                                                                       )
                                                                   ); ?>"
                                                                   data-confirm-callback="executeAction"
                                                                   data-cancel-text="<?php echo $view->escape(
                                                                       $view["translator"]->trans(
                                                                           "mautic.core.form.cancel"
                                                                       )
                                                                   ); ?>">
                                                                    <i class="fa fa-trophy"></i>
                                                                </a>
                                                            </div>
                                                        <?php endif; ?>
                                                        <h5 class="fw-sb text-primary">
                                                            <a href="<?php echo $view['router']->path(
                                                                'mautic_page_action',
                                                                array(
                                                                    'objectAction' => 'view',
                                                                    'objectId'     => $variant->getId()
                                                                )
                                                            ); ?>" data-toggle="ajax"><?php echo $variant->getTitle(
                                                                ); ?>
                                                                <?php if ($variant->getId() == $activePage->getId(
                                                                    )
                                                                ) : ?>
                                                                    <span>[<?php echo $view['translator']->trans(
                                                                            'mautic.core.current'
                                                                        ); ?>]</span>
                                                                <?php endif; ?>
                                                            </a>
                                                        </h5>
                                                        <span class="text-white dark-sm"><?php echo $variant->getAlias(
                                                            ); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 va-t text-right">
                                                <em class="text-white dark-sm">
                                                    <?php if ($isPublished
                                                        && ($totalWeight > 100
                                                            || ($thisCriteria
                                                                && $firstCriteria != $thisCriteria))
                                                    ): ?>
                                                        <div class="text-danger" data-toggle="label label-danger"
                                                             title="<?php echo $view['translator']->trans(
                                                                 'mautic.page.variant.misconfiguration'
                                                             ); ?>">
                                                            <div><span class="badge"><?php echo $weight; ?>%</span>
                                                            </div>
                                                            <div>
                                                                <i class="fa fa-fw fa-exclamation-triangle"></i><?php echo $criteriaLabel; ?>
                                                            </div>
                                                        </div>
                                                    <?php elseif ($isPublished && $criteriaLabel): ?>
                                                        <div class="text-success">
                                                            <div><span
                                                                    class="label label-success"><?php echo $weight; ?>
                                                                    %</span></div>
                                                            <div>
                                                                <i class="fa fa-fw fa-check"></i><?php echo $criteriaLabel; ?>
                                                            </div>
                                                        </div>
                                                    <?php elseif ($thisCriteria): ?>
                                                        <div class="text-muted">
                                                            <div><span
                                                                    class="label label-default"><?php echo $weight; ?>
                                                                    %</span></div>
                                                            <div><?php echo $criteriaLabel; ?></div>
                                                        </div>
                                                    <?php endif; ?>
                                                </em>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                        <!--/ end: variants list -->
                    </div>
                <?php endif; ?>
                <!--/ #variants-container -->
                <!-- #translation-container -->
                <?php if ($showTranslations): ?>
                    <div class="tab-pane <?php echo ($showVariants) ? '' : 'active '; ?>fade in bdr-w-0"
                         id="translation-container">

                        <!-- start: related translations list -->
                        <?php if (count($translations['children']) || $translations['parent']) : ?>
                            <ul class="list-group">
                                <?php if ($translations['parent']) : ?>
                                    <li class="list-group-item bg-auto bg-light-xs">
                                        <div class="box-layout">
                                            <div class="col-md-1 va-m">
                                                <h3>
                                                    <?php echo $view->render(
                                                        'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                                        array(
                                                            'item'  => $translations['parent'],
                                                            'model' => 'page.page',
                                                            'size'  => ''
                                                        )
                                                    ); ?>
                                                </h3>
                                            </div>
                                            <div class="col-md-7 va-m">
                                                <h5 class="fw-sb text-primary">
                                                    <a href="<?php echo $view['router']->path(
                                                        'mautic_page_action',
                                                        array(
                                                            'objectAction' => 'view',
                                                            'objectId'     => $translations['parent']->getId()
                                                        )
                                                    ); ?>"
                                                       data-toggle="ajax"><?php echo $translations['parent']->getTitle(
                                                        ); ?>
                                                        <?php if ($translations['parent']->getId()
                                                            == $activePage->getId()
                                                        ) : ?>
                                                            <span>[<?php echo $view['translator']->trans(
                                                                    'mautic.core.current'
                                                                ); ?>]</span>
                                                        <?php endif; ?>
                                                        <span>[<?php echo $view['translator']->trans(
                                                                'mautic.core.parent'
                                                            ); ?>]</span>
                                                    </a>
                                                </h5>
                                                <span
                                                    class="text-white dark-sm"><?php echo $translations['parent']->getAlias(
                                                    ); ?></span>
                                            </div>
                                            <div class="col-md-4 va-m text-right">
                                                <em class="text-white dark-sm"><?php echo $translations['parent']->getLanguage(
                                                    ); ?></em>
                                            </div>
                                        </div>
                                    </li>
                                <?php endif; ?>
                                <?php if (count($translations['children'])) : ?>
                                    <?php /** @var \Mautic\PageBundle\Entity\Page $translation */ ?>
                                    <?php foreach ($translations['children'] as $translation) : ?>
                                        <li class="list-group-item bg-auto bg-light-xs">
                                            <div class="box-layout">
                                                <div class="col-md-1 va-m">
                                                    <h3>
                                                        <?php echo $view->render(
                                                            'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                                            array(
                                                                'item'  => $translation,
                                                                'model' => 'page',
                                                                'size'  => '',
                                                                'query' => 'size='
                                                            )
                                                        ); ?>
                                                    </h3>
                                                </div>
                                                <div class="col-md-7 va-m">
                                                    <h5 class="fw-sb text-primary">
                                                        <a href="<?php echo $view['router']->path(
                                                            'mautic_page_action',
                                                            array(
                                                                'objectAction' => 'view',
                                                                'objectId'     => $translation->getId()
                                                            )
                                                        ); ?>" data-toggle="ajax"><?php echo $translation->getTitle(
                                                            ); ?>
                                                            <?php if ($translation->getId() == $activePage->getId(
                                                                )
                                                            ) : ?>
                                                                <span>[<?php echo $view['translator']->trans(
                                                                        'mautic.core.current'
                                                                    ); ?>]</span>
                                                            <?php endif; ?>
                                                        </a>
                                                    </h5>
                                                    <span class="text-white dark-sm"><?php echo $translation->getAlias(
                                                        ); ?></span>
                                                </div>
                                                <div class="col-md-4 va-m text-right">
                                                    <em class="text-white dark-sm"><?php echo $translation->getLanguage(
                                                        ); ?></em>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        <?php endif; ?>
                        <!--/ end: related translations list -->
                    </div>
                <?php endif; ?>
                <!--/ #translation-container -->
            </div>
            <!--/ end: tab-content -->
        <?php elseif ((empty($variants['parent']) || ($variants['parent']->getId() == $activePage->getId()))
            && $permissions['page:pages:create']
        ): ?>
            <div class="pa-md">
                <div class="text-center" style="height: 100%; width: 100%; ">
                    <h3 style="padding: 30px;">
                        <a class="create-abtest-link" href="<?php echo $view['router']->path(
                            'mautic_page_action',
                            array('objectAction' => 'abtest', 'objectId' => $activePage->getId())
                        ); ?>" data-toggle="ajax">
                            <?php echo $view['translator']->trans('mautic.page.abtest.create'); ?> <i
                                class="fa fa-angle-right"></i>
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
                        <button class="btn btn-default btn-nospin"
                                onclick="window.open('<?php echo $pageUrl; ?>', '_blank');">
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
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', array('logs' => $logs)); ?>
    </div>
    <!--/ right section -->
</div>

<?php echo $view->render(
    'MauticCoreBundle:Helper:modal.html.php',
    array(
        'id'     => 'abStatsModal',
        'header' => false,
        'body'   => (isset($abTestResults['supportTemplate'])) ? $view->render(
            $abTestResults['supportTemplate'],
            array('results' => $abTestResults, 'variants' => $variants)
        ) : $view['translator']->trans('mautic.page.abtest.noresults'),
        'size'   => 'lg'
    )
); ?>

<!--/ end: box layout -->
