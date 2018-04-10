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
$view['slots']->set('mauticContent', 'asset');
$view['slots']->set('headerTitle', $activeAsset->getTitle());

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'item'            => $activeAsset,
            'templateButtons' => [
                'edit' => $security->hasEntityAccess(
                    $permissions['asset:assets:editown'],
                    $permissions['asset:assets:editother'],
                    $activeAsset->getCreatedBy()
                ),
                'clone'  => $permissions['asset:assets:create'],
                'delete' => $security->hasEntityAccess(
                    $permissions['asset:assets:deleteown'],
                    $permissions['asset:assets:deleteother'],
                    $activeAsset->getCreatedBy()
                ),
                'close' => $security->hasEntityAccess(
                    $permissions['asset:assets:viewown'],
                    $permissions['asset:assets:viewother'],
                    $activeAsset->getCreatedBy()
                ),
            ],
            'routeBase'  => 'asset',
            'langVar'    => 'asset.asset',
            'nameGetter' => 'getTitle',
        ]
    )
);

$view['slots']->set(
    'publishStatus',
    $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $activeAsset])
);

?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- asset detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-10 va-m">
                        <div class="text-white dark-sm mb-0"><?php echo $activeAsset->getDescription(); ?></div>
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
                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:details.html.php',
                                ['entity' => $activeAsset]
                            ); ?>
                            <tr>
                                <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans(
                                            'mautic.asset.asset.size'
                                        ); ?></span></td>
                                <td><?php echo $activeAsset->getSize(); ?></td>
                            </tr>
                            <tr>
                                <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans(
                                            'mautic.asset.asset.path.relative'
                                        ); ?></span></td>
                                <td><?php echo $assetDownloadUrl; ?></td>
                            </tr>
                            <tr>
                                <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans(
                                            'mautic.asset.filename.original'
                                        ); ?></span></td>
                                <td><?php echo $activeAsset->getOriginalFilename(); ?></td>
                            </tr>
                            <tr>
                                <?php $location = $activeAsset->getStorageLocation(); ?>
                                <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans(
                                            'mautic.asset.filename.'.$location
                                        ); ?></span></td>
                                <td><?php echo ($location == 'local') ? $activeAsset->getPath()
                                        : $activeAsset->getRemotePath(); ?></td>
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
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse"
                       data-target="#asset-details"><span class="caret"></span> <?php echo $view['translator']->trans(
                            'mautic.core.details'
                        ); ?></a>
                </span>
            </div>
            <!--/ asset detail collapseable toggler -->

            <!-- some stats -->
            <div class="pa-md">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="col-md-2 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">
                                        <span class="fa fa-download"></span>
                                        <?php echo $view['translator']->trans('mautic.asset.graph.line.downloads'); ?>
                                    </h5>
                                </div>
                                <div class="col-md-2 va-m text-center">
                                    <span class="text-white dark-md fw-sb mb-xs"><?php echo $view['translator']->trans('mautic.asset.asset.downloads.total', ['count' => $stats['downloads']['total']]); ?></span>
                                    <span class="text-white dark-md fw-sb mb-xs">|</span>
                                    <span class="text-white dark-md fw-sb mb-xs"><?php echo $view['translator']->trans('mautic.asset.asset.downloads.unique', ['count' => $stats['downloads']['unique']]); ?></span>
                                </div>
                                <div class="col-md-8 va-m">
                                    <?php echo $view->render('MauticCoreBundle:Helper:graph_dateselect.html.php', ['dateRangeForm' => $dateRangeForm, 'class' => 'pull-right']); ?>
                                </div>
                            </div>
                            <div class="pt-0 pl-15 pb-10 pr-15">
                                <?php echo $view->render('MauticCoreBundle:Helper:chart.html.php', ['chartData' => $stats['downloads']['timeStats'], 'chartType' => 'line', 'chartHeight' => 300]); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ stats -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md preview-detail">
            <?php echo $view->render(
                'MauticAssetBundle:Asset:preview.html.php',
                ['activeAsset' => $activeAsset, 'assetDownloadUrl' => $view['router']->generate(
                    'mautic_asset_action',
                    ['objectAction' => 'preview', 'objectId' => $activeAsset->getId()]
                )]
            ); ?>
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
                    <input onclick="this.setSelectionRange(0, this.value.length);" type="text" class="form-control"
                           readonly value="<?php echo $view->escape($assetDownloadUrl); ?>"/>
                <span class="input-group-btn">
                    <button class="btn btn-default btn-nospin"
                            onclick="window.open('<?php echo $assetDownloadUrl; ?>', '_blank');">
                        <i class="fa fa-external-link"></i>
                    </button>
                </span>
                </div>
            </div>
        </div>
        <!--/ preview URL -->

        <hr class="hr-w-2" style="width:50%">

        <!-- activity feed -->
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]); ?>
    </div>
    <!--/ right section -->
    <input name="entityId" id="entityId" type="hidden" value="<?php echo $view->escape($activeAsset->getId()); ?>"/>
</div>
<!--/ end: box layout -->
