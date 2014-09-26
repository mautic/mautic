<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
$view->extend('MauticAssetBundle:Asset:index.html.php');
?>
<?php if (count($items)): ?>
<div class="panel panel-default">
    <div class="panel-heading">
        <div class="panel-title">
            <?php echo $view['translator']->trans('mautic.asset.asset.header.index'); ?>
        </div>
    </div>
    <div class="panel-toolbar-wrapper">
        <div class="panel-toolbar">
            <div class="checkbox custom-checkbox pull-left">
                <input type="checkbox" id="customcheckbox-one0" value="1" data-toggle="checkall" data-target="#reportTable">
                <label for="customcheckbox-one0"><?php echo $view['translator']->trans('mautic.core.table.selectall'); ?></label>
            </div>
        </div>
        <div class="panel-toolbar text-right">
            <button type="button" class="btn btn-sm btn-warning"><i class="fa fa-files-o"></i></button>
            <button type="button" class="btn btn-sm btn-danger"><i class="fa fa-trash-o"></i></button>
        </div>
    </div>
    <div class="table-responsive scrollable body-white padding-sm page-list">
            <table class="table table-hover table-striped table-bordered asset-list">
                <thead>
                <tr>
                    <th class="col-asset-actions"></th>
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'asset',
                        'orderBy'    => 'p.title',
                        'text'       => 'mautic.asset.asset.thead.title',
                        'class'      => 'col-page-title',
                        'default'    => true
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'asset',
                        'orderBy'    => 'c.title',
                        'text'       => 'mautic.asset.asset.thead.category',
                        'class'      => 'visible-md visible-lg col-page-category'
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'asset',
                        'orderBy'    => 'p.author',
                        'text'       => 'mautic.asset.asset.thead.author',
                        'class'      => 'visible-md visible-lg col-page-author'
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'asset',
                        'orderBy'    => 'p.language',
                        'text'       => 'mautic.asset.asset.thead.language',
                        'class'      => 'visible-md visible-lg col-page-lang'
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'asset',
                        'orderBy'    => 'p.download_count',
                        'text'       => 'mautic.asset.asset.thead.download.count',
                        'class'      => 'col-page-download-count'
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'asset',
                        'orderBy'    => 'p.id',
                        'text'       => 'mautic.asset.asset.thead.id',
                        'class'      => 'col-page-id'
                    ));
                    ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?php
                            echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                                'item'      => $item,
                                'edit'      => $security->hasEntityAccess(
                                    $permissions['asset:assets:editown'],
                                    $permissions['asset:assets:editother'],
                                    $item->getCreatedBy()
                                ),
                                'delete'    => $security->hasEntityAccess(
                                    $permissions['asset:assets:deleteown'],
                                    $permissions['asset:assets:deleteother'],
                                    $item->getCreatedBy()),
                                'routeBase' => 'asset',
                                'menuLink'  => 'mautic_asset_index',
                                'langVar'   => 'asset.asset',
                                'nameGetter' => 'getTitle'
                            ));
                            ?>
                        </td>
                        <td>
                            <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                                'item'       => $item,
                                'model'      => 'asset.asset'
                            )); ?>
                            <a href="<?php echo $view['router']->generate('mautic_asset_action',
                                array("objectAction" => "view", "objectId" => $item->getId())); ?>"
                               data-toggle="ajax">
                                <?php echo $item->getTitle(); ?> (<?php echo $item->getAlias(); ?>)
                            </a>
                            <i class="<?php echo $item->getIconClass(); ?>"></i>
                        </td>
                        <td class="visible-md visible-lg">
                            <?php $catName = ($category = $item->getCategory()) ? $category->getTitle() :
                                $view['translator']->trans('mautic.core.form.uncategorized'); ?>
                            <span><?php echo $catName; ?></span>
                        </td>
                        <td class="visible-md visible-lg"><?php echo $item->getAuthor(); ?></td>
                        <td class="visible-md visible-lg"><?php echo $item->getLanguage(); ?></td>
                        <td class="visible-md visible-lg"><?php echo $item->getDownloadCount(); ?></td>
                        <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
    </div>
</div>
<?php else: ?>
    <div class="well well-small">
        <h4><?php echo $view['translator']->trans('mautic.core.noresults.header'); ?></h4>
        <p><?php echo $view['translator']->trans('mautic.core.noresults'); ?></p>
    </div>
<?php endif; ?>