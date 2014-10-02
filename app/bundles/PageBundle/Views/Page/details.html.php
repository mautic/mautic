<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo - add landing page stats/analytics
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'page');
$view['slots']->set("headerTitle", $activePage->getTitle());?>

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

<!-- reset container-fluid padding -->
<div class="mna-md">
    <!-- start: box layout -->
    <div class="box-layout">
        <!-- left section -->
        <div class="col-md-9 bg-white height-auto">
            <div class="bg-auto">
                <!-- page detail header -->
                <div class="pr-md pl-md pt-lg pb-lg">
                    <div class="box-layout">
                        <div class="col-xs-6 va-m">
                            <h4 class="fw-sb text-primary">Super Awesome Page</h4>
                            <p class="text-white dark-lg mb-0">Created on 7 Jan 2014</p>
                        </div>
                        <div class="col-xs-6 va-m text-right">
                            <h4 class="fw-sb"><span class="label label-success">PUBLISH UP</span></h4>
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
                                        <td width="20%"><span class="fw-b">Description</span></td>
                                        <td>Page description Lorem ipsum dolor sit amet, consectetur adipisicing.</td>
                                    </tr>
                                    <tr>
                                        <td width="20%"><span class="fw-b">Created By</span></td>
                                        <td>Dan Counsell</td>
                                    </tr>
                                    <tr>
                                        <td width="20%"><span class="fw-b">Category</span></td>
                                        <td>Some category</td>
                                    </tr>
                                    <tr>
                                        <td width="20%"><span class="fw-b">Publish Up</span></td>
                                        <td>Mar 30, 2014</td>
                                    </tr>
                                    <tr>
                                        <td width="20%"><span class="fw-b">Publish Down</span></td>
                                        <td>Apr 10, 2014</td>
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
                        <a href="javascript:void(0)" class="arrow" data-toggle="collapse" data-target="#page-details"><span class="caret"></span></a>
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
                                        <h5 class="text-white dark-md fw-sb mb-xs">Page Views</h5>
                                        <h2 class="fw-b">112</h2>
                                    </div>
                                    <div class="col-xs-4 va-t text-right">
                                        <h3 class="text-white dark-sm"><span class="fa fa-eye"></span></h3>
                                    </div>
                                </div>
                                <div class="plugin-sparkline text-right pr-md pl-md"
                                sparkHeight="34"
                                sparkWidth="180"
                                sparkType="bar"
                                sparkBarWidth="8"
                                sparkBarSpacing="3"
                                sparkZeroAxis="false"
                                sparkBarColor="#00B49C">
                                    129,137,186,167,200,115,118,162,112,106,104,106
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="panel ovf-h bg-auto bg-light-xs">
                                <div class="panel-body box-layout">
                                    <div class="col-xs-8 va-m">
                                        <h5 class="text-white dark-md fw-sb mb-xs">Page Conversions</h5>
                                        <h2 class="fw-b">162</h2>
                                    </div>
                                    <div class="col-xs-4 va-t text-right">
                                        <h3 class="text-white dark-sm"><span class="fa fa-arrows-h"></span></h3>
                                    </div>
                                </div>
                                <div class="plugin-sparkline text-right pr-md pl-md"
                                sparkHeight="34"
                                sparkWidth="180"
                                sparkType="bar"
                                sparkBarWidth="8"
                                sparkBarSpacing="3"
                                sparkZeroAxis="false"
                                sparkBarColor="#F86B4F">
                                    156,162,185,102,144,156,150,114,198,117,120,138
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="panel ovf-h bg-auto bg-light-xs">
                                <div class="panel-body box-layout">
                                    <div class="col-xs-8 va-m">
                                        <h5 class="text-white dark-md fw-sb mb-xs">Ads Click</h5>
                                        <h2 class="fw-b">192</h2>
                                    </div>
                                    <div class="col-xs-4 va-t text-right">
                                        <h3 class="text-white dark-sm"><span class="fa fa-newspaper-o"></span></h3>
                                    </div>
                                </div>
                                <div class="plugin-sparkline text-right pr-md pl-md"
                                sparkHeight="34"
                                sparkWidth="180"
                                sparkType="bar"
                                sparkBarWidth="8"
                                sparkBarSpacing="3"
                                sparkZeroAxis="false"
                                sparkBarColor="#FDB933">
                                    115,195,185,110,182,192,168,185,138,176,119,109
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--/ some stats -->

                <!-- tabs controls -->
                <ul class="nav nav-tabs pr-md pl-md">
                    <li class="active"><a href="#translation-container" role="tab" data-toggle="tab">Translations</a></li>
                    <li class=""><a href="#variants-container" role="tab" data-toggle="tab">Variants</a></li>
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
                    <ul class="list-group">
                        <li class="list-group-item bg-auto bg-light-xs">
                            <div class="box-layout">
                                <div class="col-md-1 va-m">
                                    <h3><span class="fa fa-check-circle-o fw-sb text-success" data-toggle="tooltip" data-placement="right" title="Published"></span></h3>
                                </div>
                                <div class="col-md-7 va-m">
                                    <h5 class="fw-sb text-primary"><a href="">Kaleidoscope Conference 2014 <span>[current]</span> <span>[parent]</span></a></h5>
                                    <span class="text-white dark-sm">kaleidoscope-conference-2014</span>
                                </div>
                                <div class="col-md-4 va-m text-right">
                                    <em class="text-white dark-sm">en</em>
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item bg-auto bg-light-xs">
                            <div class="box-layout">
                                <div class="col-md-1 va-m">
                                    <h3><span class="fa fa-check-circle-o fw-sb text-success" data-toggle="tooltip" data-placement="right" title="Published"></span></h3>
                                </div>
                                <div class="col-md-7 va-m">
                                    <h5 class="fw-sb text-primary"><a href="">Kaleidoscope Conference 2014</a></h5>
                                    <span class="text-white dark-sm">kaleidoscope-conference-2014</span>
                                </div>
                                <div class="col-md-4 va-m text-right">
                                    <em class="text-white dark-sm">en_MX</em>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <!--/ end: related translations list -->
                </div>
                <!--/ #translation-container -->

                <!-- #variants-container -->
                <div class="tab-pane fade bdr-w-0" id="variants-container">
                    <!-- header -->
                    <div class="box-layout mb-lg">
                        <!-- form -->
                        <form action="" class="panel col-xs-8 va-m">
                            <div class="form-control-icon pa-xs">
                                <input type="text" class="form-control bdr-w-0" placeholder="Filter variants...">
                                <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
                            </div>
                        </form>
                        <!--/ form -->

                        <!-- button -->
                        <div class="col-xs-4 va-m text-right">
                            <a href="#" class="btn btn-primary">A/B Test Stats</a>
                        </div>
                    </div>
                    <!--/ header -->

                    <!-- start: variants list -->
                    <ul class="list-group">
                        <li class="list-group-item bg-auto bg-light-xs">
                            <div class="box-layout">
                                <div class="col-md-1 va-m">
                                    <h3><span class="fa fa-check-circle-o fw-sb text-success" data-toggle="tooltip" data-placement="right" title="Published"></span></h3>
                                </div>
                                <div class="col-md-7 va-m">
                                    <h5 class="fw-sb text-primary"><a href="">Kaleidoscope Conference 2014 <span>[current]</span> <span>[parent]</span></a></h5>
                                    <span class="text-white dark-sm">kaleidoscope-conference-2014</span>
                                </div>
                                <div class="col-md-4 va-m text-right">
                                    <!--<a href="#" class="btn btn-default"><span class="fa fa-trophy mr-xs"></span> Make Winner</a>-->
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item bg-auto bg-light-xs">
                            <div class="box-layout">
                                <div class="col-md-1 va-m">
                                    <h3><span class="fa fa-check-circle-o fw-sb text-success" data-toggle="tooltip" data-placement="right" title="Published"></span></h3>
                                </div>
                                <div class="col-md-7 va-m">
                                    <h5 class="fw-sb text-primary"><a href="">Kaleidoscope Conference 2014</a></h5>
                                    <span class="text-white dark-sm">kaleidoscope-conference-2014</span>
                                </div>
                                <div class="col-md-4 va-m text-right">
                                    <a href="#" class="btn btn-default"><span class="fa fa-trophy mr-xs"></span> Make Winner</a>
                                </div>
                            </div>
                        </li>
                    </ul>
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
                    <div class="panel-title">Preview URL</div>
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

            <!--
            we can leverage data from audit_log table
            and build activity feed from it
            -->
            <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mb-0">
                <div class="panel-heading">
                    <div class="panel-title">Recent Activity</div>
                </div>
                <div class="panel-body pt-xs">
                    <ul class="media-list media-list-feed">
                        <li class="media">
                            <div class="media-object pull-left mt-xs">
                                <span class="figure"></span>
                            </div>
                            <div class="media-body">
                                Dan Counsell Create <strong class="text-primary">Super Awesome Page</strong>
                                <p class="fs-12 text-white dark-sm">Jan 16, 2014</p>
                            </div>
                        </li>
                        <li class="media">
                            <div class="media-object pull-left mt-xs">
                                <span class="figure"></span>
                            </div>
                            <div class="media-body">
                                Ima Steward Update <strong class="text-primary">Super Awesome Page</strong> action
                                <p class="fs-12 text-white dark-sm">May 1, 2015</p>
                            </div>
                        </li>
                        <li class="media">
                            <div class="media-object pull-left mt-xs">
                                <span class="figure"></span>
                            </div>
                            <div class="media-body">
                                Ima Steward Update <strong class="text-primary">Super Awesome Page</strong> leads
                                <p class="fs-12 text-white dark-sm">Aug 2, 2014</p>
                            </div>
                        </li>
                        <li class="media">
                            <div class="media-object pull-left">
                                <span class="figure featured bg-success"><span class="fa fa-check"></span></span>
                            </div>
                            <div class="media-body">
                                Dan Counsell Publish <strong class="text-primary">Super Awesome Page</strong>
                                <p class="fs-12 text-white dark-sm">Sep 23, 2014</p>
                            </div>
                        </li>
                        <li class="media">
                            <div class="media-object pull-left">
                                <span class="figure"></span>
                            </div>
                            <div class="media-body">
                                Dan Counsell Unpublish <strong class="text-primary">Super Awesome Page</strong>
                                <p class="fs-12 text-white dark-sm">Sep 29, 2014</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!--/ right section -->
    </div>
    <!--/ end: box layout -->
</div>
<!--/ reset container-fluid padding -->