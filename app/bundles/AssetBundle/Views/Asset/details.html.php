<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo - add landing asset stats/analytics
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'asset');
$view['slots']->set("headerTitle", $activeAsset->getTitle());?>

<?php
$view['slots']->start('actions');
if ($security->hasEntityAccess($permissions['asset:assets:editown'], $permissions['asset:assets:editother'],
    $activeAsset->getCreatedBy())): ?>
    <a href="<?php echo $this->container->get('router')->generate(
        'mautic_asset_action', array("objectAction" => "edit", "objectId" => $activeAsset->getId())); ?>"
       data-toggle="ajax"
       class="btn btn-default"
       data-menu-link="#mautic_asset_index">
        <i class="fa fa-fw fa-pencil-square-o"></i>
        <?php echo $view["translator"]->trans("mautic.core.form.edit"); ?>
    </a>
<?php endif; ?>
<?php if ($security->hasEntityAccess($permissions['asset:assets:deleteown'], $permissions['asset:assets:deleteother'],
    $activeAsset->getCreatedBy())): ?>
    <a href="javascript:void(0);"
       class="btn btn-default"
       onclick="Mautic.showConfirmation(
           '<?php echo $view->escape($view["translator"]->trans("mautic.asset.asset.confirmdelete",
           array("%name%" => $activeAsset->getTitle() . " (" . $activeAsset->getId() . ")")), 'js'); ?>',
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
           'executeAction',
           ['<?php echo $view['router']->generate('mautic_asset_action',
           array("objectAction" => "delete", "objectId" => $activeAsset->getId())); ?>',
           '#mautic_asset_index'],
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
        <span><i class="fa fa-fw fa-trash-o"></i><?php echo $view['translator']->trans('mautic.core.form.delete'); ?></span>
    </a>
<?php endif; ?>

<?php $view['slots']->stop(); ?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- page detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-6 va-m">
                        <h4 class="fw-sb text-primary">
                            <i class="<?php echo $activeAsset->getIconClass(); ?>"></i>
                            <span> | </span>
                            <?php echo $activeAsset->getFileSize(); ?> kB
                            <span> | </span>
                            <?php
                            if ($category = $activeAsset->getCategory()):
                                $catSearch = $view['translator']->trans('mautic.core.searchcommand.category') . ":" . $category->getAlias();
                                $catName = $category->getTitle();
                            else:
                                $catSearch = $view['translator']->trans('mautic.core.searchcommand.is') . ":" .
                                    $view['translator']->trans('mautic.core.searchcommand.isuncategorized');
                                $catName = $view['translator']->trans('mautic.core.form.uncategorized');
                            endif;
                            ?>
                            <a href="<?php echo $view['router']->generate('mautic_asset_index', array('search' => $catSearch))?>"
                               data-toggle="ajax">
                                <?php echo $catName; ?>
                            </a>
                            <span> | </span>
                            <span>
                                <?php
                                $author     = $activeAsset->getCreatedBy();
                                $authorId   = ($author) ? $author->getId() : 0;
                                $authorName = ($author) ? $author->getName() : "";
                                ?>
                                <a href="<?php echo $view['router']->generate('mautic_user_action', array(
                                    'objectAction' => 'contact',
                                    'objectId'     => $authorId,
                                    'entity'       => 'asset.asset',
                                    'id'           => $activeAsset->getId(),
                                    'returnUrl'    => $view['router']->generate('mautic_asset_action', array(
                                        'objectAction' => 'view',
                                        'objectId'     => $activeAsset->getId()
                                    ))
                                )); ?>">
                                    <?php echo $authorName; ?>
                                </a>
                            </span>
                            <span> | </span>
                            <span>
                            <?php $langSearch = $view['translator']->trans('mautic.asset.asset.searchcommand.lang').":".$activeAsset->getLanguage(); ?>
                                <a href="<?php echo $view['router']->generate('mautic_asset_index', array('search' => $langSearch)); ?>"
                                   data-toggle="ajax">
                                    <?php echo $activeAsset->getLanguage(); ?>
                                </a>
                            </span>
                        </h4>
                        <p class="text-white dark-lg mb-0">Created on <?php echo $view['date']->toDate($activeAsset->getDateAdded()); ?></p>
                    </div>
                    <div class="col-xs-6 va-m text-right">
                        <?php switch ($activeAsset->getPublishStatus()) {
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
                        <?php $labelText = strtoupper($view['translator']->trans('mautic.core.form.' . $activeAsset->getPublishStatus())); ?>
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
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.core.author'); ?></span></td>
                                    <td><?php echo $activeAsset->getAuthor(); ?></td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.core.category'); ?></span></td>
                                    <td><?php echo is_object($activeAsset->getCategory()) ? $activeAsset->getCategory()->getTitle() : ''; ?></td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.page.page.publish.up'); ?></span></td>
                                    <td><?php echo (!is_null($activeAsset->getPublishUp())) ? $view['date']->toFull($activeAsset->getPublishUp()) : ''; ?></td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.page.page.publish.down'); ?></span></td>
                                    <td><?php echo (!is_null($activeAsset->getPublishDown())) ? $view['date']->toFull($activeAsset->getPublishDown()) : ''; ?></td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.asset.asset.size'); ?></span></td>
                                    <td><?php echo (!is_null($activeAsset->getFileSize())) ? $activeAsset->getFileSize() . ' kB' : ''; ?></td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.asset.asset.path.relative'); ?></span></td>
                                    <td><?php echo (!is_null($activeAsset->getWebPath())) ? $activeAsset->getWebPath() : ''; ?></td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.asset.asset.url'); ?></span></td>
                                    <td><?php echo (!is_null($assetUrl)) ? $assetUrl : ''; ?></td>
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
                    <div class="col-sm-12">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="col-xs-4 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">
                                        <span class="fa fa-download"></span>
                                        Downloads
                                    </h5>
                                </div>
                                <div class="col-xs-4 va-m text-center">
                                    <span class="text-white dark-md fw-sb mb-xs">Total: <?php echo $stats['downloads']['total']; ?></span>
                                    <span class="text-white dark-md fw-sb mb-xs">|</span>
                                    <span class="text-white dark-md fw-sb mb-xs">Unique: <?php echo $stats['downloads']['unique']; ?></span>
                                </div>
                                <div class="col-xs-4 va-m">
                                    <div class="dropdown pull-right">
                                        <button id="time-scopes" class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                                            <span class="button-label">Daily</span>
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu" role="menu" aria-labelledby="time-scopes">
                                            <li role="presentation">
                                                <a href="#" onclick="Mautic.updateDownloadChart(this, 24, 'H');return false;" role="menuitem" tabindex="-1">
                                                    Hourly
                                                </a>
                                            </li>
                                            <li role="presentation">
                                                <a href="#" class="bg-primary" onclick="Mautic.updateDownloadChart(this, 30, 'D');return false;" role="menuitem" tabindex="-1">
                                                    Dayly
                                                </a>
                                            </li>
                                            <li role="presentation">
                                                <a href="#" onclick="Mautic.updateDownloadChart(this, 20, 'W');return false;" role="menuitem" tabindex="-1">
                                                    Weekly
                                                </a>
                                            </li>
                                            <li role="presentation">
                                                <a href="#" onclick="Mautic.updateDownloadChart(this, 24, 'M');return false;" role="menuitem" tabindex="-1">
                                                    Monthly
                                                </a>
                                            </li>
                                            <li role="presentation">
                                                <a href="#" onclick="Mautic.updateDownloadChart(this, 10, 'Y');return false;" role="menuitem" tabindex="-1">
                                                    Yearly
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-0 pl-15 pb-10 pr-15">
                                <div>
                                    <canvas id="download-chart" height="80"></canvas>
                                </div>
                            </div>
                            <div id="download-chart-data" class="hide"><?php echo json_encode($stats['downloads']['timeStats']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ some stats -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md">

        </div>
        <!--/ end: tab-content -->
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- preview URL -->
        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mt-sm mb-0">
            <div class="panel-heading">
                <div class="panel-title"><?php echo $view['translator']->trans('mautic.asset.asset.url'); ?></div>
            </div>
            <div class="panel-body pt-xs">
                <div class="input-group">
                <input onclick="this.setSelectionRange(0, this.value.length);" type="text" class="form-control" readonly
                value="<?php echo $assetUrl; ?>" />
                <span class="input-group-btn">
                    <button class="btn btn-default" onclick="window.open('<?php echo $assetUrl; ?>', '_blank');">
                        <i class="fa fa-external-link"></i>
                    </button>
                </span>
            </div>
            </div>
        </div>
        <!--/ preview URL -->

        <hr class="hr-w-2" style="width:50%">

        <!-- activity feed -->
        <?php echo $view->render('MauticCoreBundle:Default:recentactivity.html.php', array('logs' => $logs)); ?>
    </div>
    <!--/ right section -->
    <input id="itemId" value="<?php echo $activeAsset->getId(); ?>" />
</div>
<!--/ end: box layout -->
