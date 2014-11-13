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
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered asset-list" id="assetTable">
            <thead>
            <tr>
                <th class="visible-md visible-lg col-asset-actions pl-20">
                    <div class="checkbox-inline custom-primary">
                        <label class="mb-0 pl-10">
                            <input type="checkbox" id="customcheckbox-one0" value="1" data-toggle="checkall" data-target="#assetTable">
                            <span></span>
                        </label>
                    </div>
                </th>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'asset',
                    'orderBy'    => 'a.title',
                    'text'       => 'mautic.asset.asset.thead.title',
                    'class'      => 'col-asset-title',
                    'default'    => true
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'asset',
                    'orderBy'    => 'c.title',
                    'text'       => 'mautic.asset.asset.thead.category',
                    'class'      => 'visible-md visible-lg col-asset-category'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'asset',
                    'orderBy'    => 'a.createdByUser',
                    'text'       => 'mautic.asset.asset.thead.author',
                    'class'      => 'visible-md visible-lg col-asset-author'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'asset',
                    'orderBy'    => 'a.language',
                    'text'       => 'mautic.asset.asset.thead.language',
                    'class'      => 'visible-md visible-lg col-asset-lang'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'asset',
                    'orderBy'    => 'a.download_count',
                    'text'       => 'mautic.asset.asset.thead.download.count',
                    'class'      => 'visible-md visible-lg col-asset-download-count'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'asset',
                    'orderBy'    => 'a.id',
                    'text'       => 'mautic.asset.asset.thead.id',
                    'class'      => 'visible-md visible-lg col-asset-id'
                ));
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td class="visible-md visible-lg">
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
                    <td class="visible-md visible-lg"><?php echo $item->getCreatedByUser(); ?></td>
                    <td class="visible-md visible-lg"><?php echo $item->getLanguage(); ?></td>
                    <td class="visible-md visible-lg"><?php echo $item->getDownloadCount(); ?></td>
                    <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="panel-footer">
        <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
            "totalItems"      => count($items),
            "page"            => $page,
            "limit"           => $limit,
            "menuLinkId"      => 'mautic_asset_index',
            "baseUrl"         => $view['router']->generate('mautic_asset_index'),
            'sessionVar'      => 'asset'
        )); ?>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Default:noresults.html.php'); ?>
<?php endif; ?>
