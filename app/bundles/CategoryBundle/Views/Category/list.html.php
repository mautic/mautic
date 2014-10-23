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
<div class="panel panel-default page-list bdr-t-wdh-0">
    <div class="panel-body">
        <div class="box-layout">
            <div class="col-xs-6 va-m">
                <?php echo isset($currentRoute) ? $view->render('MauticCoreBundle:Helper:search.html.php', array('searchValue' => $searchValue, 'action' => $currentRoute)) : ''; ?>
            </div>
            <div class="col-xs-6 va-m text-right">
                <button type="button" class="btn btn-sm btn-warning"><i class="fa fa-files-o"></i></button>
                <button type="button" class="btn btn-sm btn-danger"><i class="fa fa-trash-o"></i></button>
            </div>
        </div>
    </div>
    <div class="table-responsive scrollable body-white padding-sm page-list">
            <table class="table table-hover table-striped table-bordered category-list">
                <thead>
                <tr>
                    <th class="col-page-actions pl-20">
                        <div class="checkbox-inline custom-primary">
                            <label class="mb-0 pl-10">
                                <input type="checkbox" id="customcheckbox-one0" value="1">
                                <span></span>
                            </label>
                        </div>
                    </th>
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
    <?php echo $view->render('MauticCoreBundle:Default:noresults.html.php'); ?>
<?php endif; ?>