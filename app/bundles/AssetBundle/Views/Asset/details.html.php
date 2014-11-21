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
            <!-- asset detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-10"></div>
                    <div class="col-xs-2 text-right">
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
            <!--/ asset detail header -->
            <!-- asset detail collapseable -->
            <div class="collapse" id="asset-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                            <?php echo $view->render('MauticCoreBundle:Helper:details.html.php', array('entity' => $activeAsset)); ?>
                            <tr>
                                <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.asset.asset.size'); ?></span></td>
                                <td><?php echo (!is_null($activeAsset->getFileSize())) ? $activeAsset->getFileSize() . ' kB' : ''; ?></td>
                            </tr>
                            <tr>
                                <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.asset.asset.path.relative'); ?></span></td>
                                <td><?php echo (!is_null($activeAsset->getWebPath())) ? $activeAsset->getWebPath() : ''; ?></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ asset detail collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- asset detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="Detail">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse" data-target="#asset-details"><span class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?></a>
                </span>
            </div>
            <!--/ asset detail collapseable toggler -->

            <!-- some stats -->
            <div class="pa-md">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="col-xs-4 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">
                                        <span class="fa fa-download"></span>
                                        <?php echo $view['translator']->trans('mautic.asset.graph.line.downloads'); ?>
                                    </h5>
                                </div>
                                <div class="col-xs-4 va-m text-center">
                                    <span class="text-white dark-md fw-sb mb-xs"><?php echo $view['translator']->trans('mautic.asset.asset.downloads.total', array('count' => $stats['downloads']['total'])); ?></span>
                                    <span class="text-white dark-md fw-sb mb-xs">|</span>
                                    <span class="text-white dark-md fw-sb mb-xs"><?php echo $view['translator']->trans('mautic.asset.asset.downloads.unique', array('count' => $stats['downloads']['unique'])); ?></span>
                                </div>
                                <div class="col-xs-4 va-m">
                                    <div class="dropdown pull-right">
                                        <button id="time-scopes" class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                                            <span class="button-label"><?php echo $view['translator']->trans('mautic.asset.asset.downloads.daily'); ?></span>
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu" role="menu" aria-labelledby="time-scopes">
                                            <li role="presentation">
                                                <a href="#" onclick="Mautic.updateDownloadChart(this, 24, 'H');return false;" role="menuitem" tabindex="-1">
                                                    <?php echo $view['translator']->trans('mautic.asset.asset.downloads.hourly'); ?>
                                                </a>
                                            </li>
                                            <li role="presentation">
                                                <a href="#" class="bg-primary" onclick="Mautic.updateDownloadChart(this, 30, 'D');return false;" role="menuitem" tabindex="-1">
                                                    <?php echo $view['translator']->trans('mautic.asset.asset.downloads.daily'); ?>
                                                </a>
                                            </li>
                                            <li role="presentation">
                                                <a href="#" onclick="Mautic.updateDownloadChart(this, 20, 'W');return false;" role="menuitem" tabindex="-1">
                                                    <?php echo $view['translator']->trans('mautic.asset.asset.downloads.weekly'); ?>
                                                </a>
                                            </li>
                                            <li role="presentation">
                                                <a href="#" onclick="Mautic.updateDownloadChart(this, 24, 'M');return false;" role="menuitem" tabindex="-1">
                                                    <?php echo $view['translator']->trans('mautic.asset.asset.downloads.monthly'); ?>
                                                </a>
                                            </li>
                                            <li role="presentation">
                                                <a href="#" onclick="Mautic.updateDownloadChart(this, 10, 'Y');return false;" role="menuitem" tabindex="-1">
                                                    <?php echo $view['translator']->trans('mautic.asset.asset.downloads.yearly'); ?>
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
            <!--/ stats -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md preview-detail">
            <?php echo $view->render('MauticAssetBundle:Asset:preview.html.php', array('activeAsset' => $activeAsset, 'baseUrl' => $baseUrl)); ?>
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
                value="<?php echo $assetDownloadUrl; ?>" />
                <span class="input-group-btn">
                    <button class="btn btn-default" onclick="window.open('<?php echo $assetDownloadUrl; ?>', '_blank');">
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
