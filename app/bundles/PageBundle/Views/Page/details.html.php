<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var \Mautic\PageBundle\Entity\Page $activePage */
//@todo - add landing page stats/analytics
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'page');
$view['slots']->set("headerTitle", $activePage->getTitle()); ?>

<?php
$view['slots']->start('actions');
if ($security->hasEntityAccess($permissions['page:pages:editown'], $permissions['page:pages:editother'],
    $activePage->getCreatedBy())): ?>
    <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate('mautic_page_action', array("objectAction" => "edit", "objectId" => $activePage->getId())); ?>" data-toggle="ajax" data-menu-link="#mautic_page_index">
        <i class="fa fa-fw fa-pencil-square-o"></i>
        <?php echo $view["translator"]->trans("mautic.core.form.edit"); ?>
    </a>
<?php endif; ?>
<?php if ($security->hasEntityAccess($permissions['page:pages:deleteown'], $permissions['page:pages:deleteother'],
    $activePage->getCreatedBy())): ?>
    <a class="btn btn-default" href="javascript:void(0);"
       onclick="Mautic.showConfirmation(
           '<?php echo $view->escape($view["translator"]->trans("mautic.page.page.confirmdelete",
           array("%name%" => $activePage->getTitle() . " (" . $activePage->getId() . ")")), 'js'); ?>',
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
           'executeAction',
           ['<?php echo $view['router']->generate('mautic_page_action',
           array("objectAction" => "delete", "objectId" => $activePage->getId())); ?>',
           '#mautic_page_index'],
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
        <i class="fa fa-fw fa-trash-o text-danger"></i>
        <?php echo $view['translator']->trans('mautic.core.form.delete'); ?>
    </a>
<?php endif; ?>
<?php if (empty($variants['parent']) && $permissions['page:pages:create']): ?>
    <a class="btn btn-default" href="<?php echo $view['router']->generate('mautic_page_action',
           array("objectAction" => "abtest", "objectId" => $activePage->getId())); ?>"
        data-toggle="ajax"
        data-menu-link="mautic_page_index">
        <i class="fa fa-sitemap"></i>
        <?php echo $view['translator']->trans('mautic.page.page.form.abtest'); ?>
    </a>
<?php endif; ?>
<?php $view['slots']->stop(); ?>

<!--<div class="scrollable">
    <div class="bundle-main-header">
        <span class="bundle-main-item-primary">
            <?php
            if ($category = $activePage->getCategory()):
                $catSearch = $view['translator']->trans('mautic.core.searchcommand.category') . ":" . $category->getAlias();
                $catName = $category->getTitle();
            else:
                $catSearch = $view['translator']->trans('mautic.core.searchcommand.is') . ":" .
                    $view['translator']->trans('mautic.core.searchcommand.isuncategorized');
                $catName = $view['translator']->trans('mautic.core.form.uncategorized');
            endif;
            ?>
            <a href="<?php echo $view['router']->generate('mautic_page_index', array('search' => $catSearch))?>"
               data-toggle="ajax">
                <?php echo $catName; ?>
            </a>
            <span>
                <?php
                $author     = $activePage->getCreatedBy();
                $authorId   = ($author) ? $author->getId() : 0;
                $authorName = ($author) ? $author->getName() : "";
                ?>
                <a href="<?php echo $view['router']->generate('mautic_user_action', array(
                    'objectAction' => 'contact',
                    'objectId'     => $authorId,
                    'entity'       => 'page.page',
                    'id'           => $activePage->getId(),
                    'returnUrl'    => $view['router']->generate('mautic_page_action', array(
                        'objectAction' => 'view',
                        'objectId'     => $activePage->getId()
                    ))
                )); ?>">
                    <?php echo $authorName; ?>
                </a>
            </span>
            <span>
            <?php $langSearch = $view['translator']->trans('mautic.core.searchcommand.lang').":".$activePage->getLanguage(); ?>
                <a href="<?php echo $view['router']->generate('mautic_page_index', array('search' => $langSearch)); ?>"
                   data-toggle="ajax">
                    <?php echo $activePage->getLanguage(); ?>
                </a>
            </span>
        </span>
    </div>

    <div class="form-group margin-md-top">
        <?php if (!empty($variants['parent'])): ?>
        <label><?php echo $view['translator']->trans('mautic.page.page.urlvariant'); ?></label>
        <?php else: ?>
        <label><?php echo $view['translator']->trans('mautic.page.page.url'); ?></label>
        <?php endif; ?>
        <div class="input-group">
            <input onclick="this.setSelectionRange(0, this.value.length);" type="text" class="form-control" readonly
                   value="<?php echo $pageUrl; ?>" />
            <span class="input-group-btn">
                <button class="btn btn-default" onclick="window.open('<?php echo $pageUrl; ?>', '_blank');">
                    <i class="fa fa-external-link"></i>
                </button>
            </span>
        </div>
    </div>

    <h3>@todo - landing page stats/analytics/AB test results will go here</h3>
    <?php echo "<pre>".print_r($stats, true)."</pre>"; ?>

    <?php
    echo $view->render('MauticPageBundle:Page:translations.html.php', array(
        'page'         => $activePage,
        'translations' => $translations
    ));
    ?>

    <?php if (!empty($variants['parent']) || !empty($variants['children'])): ?>
    <?php echo $view->render('MauticPageBundle:AbTest:details.html.php', array(
        'page'          => $activePage,
        'abTestResults' => $abTestResults,
        'variants'      => $variants
    )); ?>
    <?php endif; ?>
    '
</div>-->

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- page detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-10">
                        <p class="text-muted"><?php echo $activePage->getMetaDescription(); ?></p>
                    </div>
                    <div class="col-xs-2 text-right">
                        <?php switch ($activePage->getPublishStatus()) {
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
                        <?php $labelText = strtoupper($view['translator']->trans('mautic.core.form.' . $activePage->getPublishStatus())); ?>
                        <h4 class="fw-sb"><span class="label label-<?php echo $labelColor; ?>"><?php echo $labelText; ?></span></h4>
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
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.core.created'); ?></span></td>
                                    <td><?php echo $view['date']->toDate($activePage->getDateAdded()); ?></td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.core.author'); ?></span></td>
                                    <td><?php echo $activePage->getAuthor(); ?></td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.core.category'); ?></span></td>
                                    <td><?php echo is_object($activePage->getCategory()) ? $activePage->getCategory()->getTitle() : ''; ?></td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.page.page.publish.up'); ?></span></td>
                                    <td><?php echo (!is_null($activePage->getPublishUp())) ? $view['date']->toFull($activePage->getPublishUp()) : ''; ?></td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.page.page.publish.down'); ?></span></td>
                                    <td><?php echo (!is_null($activePage->getPublishDown())) ? $view['date']->toFull($activePage->getPublishDown()) : ''; ?></td>
                                </tr>
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
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse" data-target="#page-details"><span class="caret"></span> <?php echo $view['translator']->trans('mautic.page.page.details'); ?></a>
                </span>
            </div>
            <!--/ page detail collapseable toggler -->

            <!--
            some stats: need more input on what type of form data to show.
            delete if it is not require
            -->
            <div class="pa-md">
                <div class="row">
                    <div class="col-md-4">
                        <div class="panel ovf-h bg-auto bg-light-xs">
                            <div class="panel-body box-layout">
                                <div class="col-xs-8 va-m">
                                    <h5 class="dark-md fw-sb mb-xs">
                                        <?php echo $view['translator']->trans('mautic.page.page.pageviews'); ?>
                                    </h5>
                                </div>
                                <div class="col-xs-4 va-t text-right">
                                    <h3 class="text-white dark-sm"><span class="fa fa-eye"></span></h3>
                                </div>
                            </div>
                            <div class="pt-0 pl-10 pb-0 pr-10">
                                <div>
                                    <canvas id="page-views-chart" height="93"></canvas>
                                </div>
                            </div>
                            <div id="page-views-chart-data" class="hide"><?php echo json_encode($last30); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel ovf-h bg-auto bg-light-xs">
                            <div class="panel-body box-layout pb-0">
                                <div class="col-xs-8 va-m">
                                    <h5 class="dark-md fw-sb mb-xs">
                                        <?php echo $view['translator']->trans('mautic.page.page.new.returning'); ?>
                                    </h5>
                                </div>
                                <div class="col-xs-4 va-t text-right">
                                    <h3 class="text-white dark-sm"><span class="fa fa-bookmark-o"></span></h3>
                                </div>
                            </div>
                            <div class="text-center">
                                <canvas id="returning-rate" width="110" height="110"></canvas>
                                <div id="returning-data" class="hide">
                                    <?php echo json_encode($stats['dwellTime'][$activePage->getId()]['newVsReturning']); ?>
                                </div>
                            </div>
                            <pre><?php print_r($stats['dwellTime'][$activePage->getId()]['newVsReturning']); ?></pre>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel ovf-h bg-auto bg-light-xs">
                            <div class="panel-body box-layout pb-0">
                                <div class="col-xs-8 va-m">
                                    <h5 class="dark-md fw-sb mb-xs">
                                        <?php echo $view['translator']->trans('mautic.page.page.time.on.page'); ?>
                                    </h5>
                                </div>
                                <div class="col-xs-4 va-t text-right">
                                    <h3 class="text-white dark-sm"><span class="fa fa-clock-o"></span></h3>
                                </div>
                            </div>
                            <div class="text-center">
                                <canvas 
                                    id="time-rate" 
                                    width="110" 
                                    height="110">
                                </canvas>
                                <div id="times-on-site-data" class="hide">
                                    <?php echo json_encode($stats['dwellTime'][$activePage->getId()]['timesOnSite']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ some stats -->

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <li class="active">
                    <a href="#translation-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.page.page.translations'); ?>
                    </a>
                </li>
                <li class="">
                    <a href="#variants-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.page.page.variants'); ?>
                    </a>
                </li>
            </ul>
            <!--/ tabs controls -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <!-- #translation-container -->
            <div class="tab-pane active fade in bdr-w-0" id="translation-container">
                <!-- header -->
                <div class="mb-lg">
                    <!-- form -->
                    <form action="" class="panel mb-0">
                        <div class="form-control-icon pa-xs">
                            <input type="text" class="form-control bdr-w-0" placeholder="Filter translation...">
                            <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
                        </div>
                    </form>
                    <!--/ form -->
                </div>
                <!--/ header -->

                <!-- start: related translations list -->
                <?php if (count($translations['children']) || $translations['parent']) : ?>
                <ul class="list-group">
                    <?php if ($translations['parent']) : ?>
                    <li class="list-group-item bg-auto bg-light-xs">
                        <div class="box-layout">
                            <div class="col-md-1 va-m">
                                <h3>
                                    <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php', array(
                                        'item'  => $translations['parent'],
                                        'model' => 'page.page',
                                        'size'  => ''
                                    )); ?>
                                </h3>
                            </div>
                            <div class="col-md-7 va-m">
                                <h5 class="fw-sb text-primary">
                                    <a href="<?php echo $view['router']->generate('mautic_page_action', array('objectAction' => 'view', 'objectId' => $translations['parent']->getId())); ?>" data-toggle="ajax"><?php echo $translations['parent']->getTitle(); ?>
                                        <?php if ($translations['parent']->getId() == $activePage->getId()) : ?>
                                        <span>[<?php echo $view['translator']->trans('mautic.page.page.current'); ?>]</span>
                                        <?php endif; ?>
                                        <span>[<?php echo $view['translator']->trans('mautic.page.page.parent'); ?>]</span>
                                    </a>
                                </h5>
                                <span class="text-white dark-sm"><?php echo $translations['parent']->getAlias(); ?></span>
                            </div>
                            <div class="col-md-4 va-m text-right">
                                <em class="text-white dark-sm"><?php echo $translations['parent']->getLanguage(); ?></em>
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
                                    <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php', array(
                                        'item'  => $translation,
                                        'model' => 'page.page',
                                        'size'  => ''
                                    )); ?>
                                </h3>
                            </div>
                            <div class="col-md-7 va-m">
                                <h5 class="fw-sb text-primary">
                                    <a href="<?php echo $view['router']->generate('mautic_page_action', array('objectAction' => 'view', 'objectId' => $translation->getId())); ?>" data-toggle="ajax"><?php echo $translation->getTitle(); ?>
                                        <?php if ($translation->getId() == $activePage->getId()) : ?>
                                        <span>[<?php echo $view['translator']->trans('mautic.page.page.current'); ?>]</span>
                                        <?php endif; ?>
                                    </a>
                                </h5>
                                <span class="text-white dark-sm"><?php echo $translation->getAlias(); ?></span>
                            </div>
                            <div class="col-md-4 va-m text-right">
                                <em class="text-white dark-sm"><?php echo $translation->getLanguage(); ?></em>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                <?php endif; ?>
                <!--/ end: related translations list -->
            </div>
            <!--/ #translation-container -->

            <!-- #variants-container -->
            <div class="tab-pane fade bdr-w-0" id="variants-container">
                <!-- header -->
                <div class="box-layout mb-lg">
                    <!-- form -->
                    <form action="" class="panel col-xs-10 va-m">
                        <div class="form-control-icon pa-xs">
                            <input type="text" class="form-control bdr-w-0" placeholder="Filter variants...">
                            <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
                        </div>
                    </form>
                    <!--/ form -->

                    <!-- button -->
                    <div class="col-xs-2 va-m text-right">
                        <a href="#" class="btn btn-primary"><?php echo $view['translator']->trans('mautic.page.page.ab.test.stats'); ?></a>
                    </div>
                </div>
                <!--/ header -->

                <!-- start: variants list -->
                <?php if (count($variants['children']) || $variants['parent']) : ?>
                <ul class="list-group">
                    <?php if ($variants['parent']) : ?>
                    <li class="list-group-item bg-auto bg-light-xs">
                        <div class="box-layout">
                            <div class="col-md-1 va-m">
                                <h3>
                                    <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php', array(
                                        'item'  => $variants['parent'],
                                        'model' => 'page.page',
                                        'size'  => ''
                                    )); ?>
                                </h3>
                            </div>
                            <div class="col-md-7 va-m">
                                <h5 class="fw-sb text-primary">
                                    <a href="<?php echo $view['router']->generate('mautic_page_action', array('objectAction' => 'view', 'objectId' => $variants['parent']->getId())); ?>" data-toggle="ajax"><?php echo $variants['parent']->getTitle(); ?>
                                        <?php if ($variants['parent']->getId() == $activePage->getId()) : ?>
                                        <span>[<?php echo $view['translator']->trans('mautic.page.page.current'); ?>]</span>
                                        <?php endif; ?>
                                        <span>[<?php echo $view['translator']->trans('mautic.page.page.parent'); ?>]</span>
                                    </a>
                                </h5>
                                <span class="text-white dark-sm"><?php echo $variants['parent']->getAlias(); ?></span>
                            </div>
                            <div class="col-md-4 va-m text-right"></div>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (count($variants['children'])) : ?>
                    <?php /** @var \Mautic\PageBundle\Entity\Page $variant */ ?>
                    <?php foreach ($variants['children'] as $variant) : ?>
                    <li class="list-group-item bg-auto bg-light-xs">
                        <div class="box-layout">
                            <div class="col-md-1 va-m">
                                <h3>
                                    <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php', array(
                                        'item'  => $variant,
                                        'model' => 'page.page',
                                        'size'  => ''
                                    )); ?>
                                </h3>
                            </div>
                            <div class="col-md-7 va-m">
                                <h5 class="fw-sb text-primary">
                                    <a href="<?php echo $view['router']->generate('mautic_page_action', array('objectAction' => 'view', 'objectId' => $variant->getId())); ?>" data-toggle="ajax"><?php echo $variant->getTitle(); ?>
                                        <?php if ($variant->getId() == $activePage->getId()) : ?>
                                        <span>[<?php echo $view['translator']->trans('mautic.page.page.current'); ?>]</span>
                                        <?php endif; ?>
                                    </a>
                                </h5>
                                <span class="text-white dark-sm"><?php echo $variant->getAlias(); ?></span>
                            </div>
                            <div class="col-md-4 va-m text-right">
                                <?php if (isset($abTestResults['winners']) && $variants['parent']->getVariantStartDate() && $variant->isPublished()): ?>
                                    <a href="<?php echo $view['router']->generate('mautic_page_action', array('objectAction' => 'winner', 'objectId' => $variant->getId())); ?>" data-toggle="ajax" data-method="post" class="btn btn-default">
                                        <span class="fa fa-trophy mr-xs"></span> <?php echo $view['translator']->trans('mautic.page.page.abtest.makewinner'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                <?php endif; ?>
                <!--/ end: variants list -->
            </div>
            <!--/ #variants-container -->
        </div>
        <!--/ end: tab-content -->
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- preview URL -->
        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mt-sm mb-0">
            <div class="panel-heading">
                <?php $trans = (!empty($variants['parent'])) ? 'mautic.page.page.urlvariant' : 'mautic.page.page.url'; ?>
                <div class="panel-title"><?php echo $view['translator']->trans($trans); ?></div>
            </div>
            <div class="panel-body pt-xs">
                <div class="input-group">
                <input onclick="this.setSelectionRange(0, this.value.length);" type="text" class="form-control" readonly
                value="<?php echo $pageUrl; ?>" />
                <span class="input-group-btn">
                    <button class="btn btn-default" onclick="window.open('<?php echo $pageUrl; ?>', '_blank');">
                        <i class="fa fa-external-link"></i>
                    </button>
                </span>
            </div>
            </div>
        </div>
        <!--/ preview URL -->

        <hr class="hr-w-2" style="width:50%">

        <!-- recent activity -->
        <?php echo $view->render('MauticCoreBundle:Default:recentactivity.html.php', array('logs' => $logs)); ?>
        
    </div>
    <!--/ right section -->
</div>
<!--/ end: box layout -->