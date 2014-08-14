<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
    $view->extend('MauticAssetBundle:Category:index.html.php');
?>

<div class="table-responsive scrollable body-white padding-sm asset-list">
    <?php if (count($items)): ?>
        <table class="table table-hover table-striped table-bordered asset-category-list">
            <thead>
            <tr>
                <th class="col-asset-actions"></th>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'assetcategory',
                    'orderBy'    => 'c.title',
                    'text'       => 'mautic.asset.category.thead.title',
                    'class'      => 'col-assetcategory-title',
                    'default'    => true
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'assetcategory',
                    'orderBy'    => 'c.description',
                    'text'       => 'mautic.asset.category.thead.description',
                    'class'      => 'visible-md visible-lg col-assetcategory-description'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'assetcategory',
                    'orderBy'    => 'c.id',
                    'text'       => 'mautic.asset.category.thead.id',
                    'class'      => 'visible-md visible-lg col-asset-id'
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
                            'edit'      => $permissions['asset:categories:edit'],
                            'delete'    => $permissions['asset:categories:delete'],
                            'routeBase' => 'assetcategory',
                            'menuLink'  => 'mautic_assetcategory_index',
                            'langVar'   => 'asset.category',
                            'nameGetter' => 'getTitle'
                        ));
                        ?>
                    </td>
                    <td>
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                            'item'       => $item,
                            'dateFormat' => (!empty($dateFormat)) ? $dateFormat : 'F j, Y g:i a',
                            'model'      => 'asset.category'
                        )); ?>
                        <?php echo $item->getTitle(); ?> (<?php echo $item->getAlias(); ?>)
                    </td>
                    <td class="visible-md visible-lg">
                        <?php echo $item->getDescription(); ?>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
    <?php endif; ?>
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems"      => count($items),
        "page"            => $page,
        "limit"           => $limit,
        "menuLinkId"      => 'mautic_assetcategory_index',
        "baseUrl"         => $view['router']->generate('mautic_assetcategory_index'),
        'sessionVar'      => 'assetcategory'
    )); ?>
    <div class="footer-margin"></div>
</div>