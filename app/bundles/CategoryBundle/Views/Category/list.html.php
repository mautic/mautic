<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
    $view->extend('MauticCategoryBundle:Category:index.html.php');
?>

<?php if (count($items)): ?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <?php echo $view['translator']->trans('mautic.category.header.index'); ?>
        </h3>
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
            <table class="table table-hover table-striped table-bordered category-list">
                <thead>
                <tr>
                    <th class="col-page-actions"></th>
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'category',
                        'orderBy'    => 'c.title',
                        'text'       => 'mautic.category.thead.title',
                        'class'      => 'col-category-title',
                        'default'    => true
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'category',
                        'orderBy'    => 'c.description',
                        'text'       => 'mautic.category.thead.description',
                        'class'      => 'visible-md visible-lg col-category-description'
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'category',
                        'orderBy'    => 'c.id',
                        'text'       => 'mautic.category.thead.id',
                        'class'      => 'visible-md visible-lg col-page-id'
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
                                'edit'      => $permissions[$bundle.':categories:edit'],
                                'delete'    => $permissions[$bundle.':categories:delete'],
                                'routeBase' => 'category',
                                'menuLink'  => 'mautic_category_index',
                                'langVar'   => 'category',
                                'nameGetter' => 'getTitle',
                                'extra'      => array(
                                    'bundle' => $bundle
                                )
                            ));
                            ?>
                        </td>
                        <td>
                            <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                                'item'  => $item,
                                'model' => 'category',
                                'extra' => 'bundle=' . $bundle
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
        
        <div class="panel-footer">
        <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
            "totalItems"      => count($items),
            "page"            => $page,
            "limit"           => $limit,
            "menuLinkId"      => 'mautic_category_index',
            "baseUrl"         => $view['router']->generate('mautic_category_index', array(
                'bundle' => $bundle
            )),
            'sessionVar'      => 'category'
        )); ?>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="well well-small">
        <h4><?php echo $view['translator']->trans('mautic.core.noresults.header'); ?></h4>
        <p><?php echo $view['translator']->trans('mautic.core.noresults'); ?></p>
    </div>
<?php endif; ?>