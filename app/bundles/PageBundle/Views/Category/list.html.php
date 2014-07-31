<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
    $view->extend('MauticPageBundle:Category:index.html.php');
?>

<div class="table-responsive scrollable body-white padding-sm page-list">
    <?php if (count($items)): ?>
        <table class="table table-hover table-striped table-bordered page-category-list">
            <thead>
            <tr>
                <th class="col-page-actions"></th>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'pagecategory',
                    'orderBy'    => 'c.title',
                    'text'       => 'mautic.page.category.thead.title',
                    'class'      => 'col-pagecategory-title',
                    'default'    => true
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'pagecategory',
                    'orderBy'    => 'c.description',
                    'text'       => 'mautic.page.category.thead.description',
                    'class'      => 'visible-md visible-lg col-pagecategory-description'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'pagecategory',
                    'orderBy'    => 'c.id',
                    'text'       => 'mautic.page.category.thead.id',
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
                            'edit'      => $permissions['page:categories:edit'],
                            'delete'    => $permissions['page:categories:delete'],
                            'routeBase' => 'pagecategory',
                            'menuLink'  => 'mautic_pagecategory_index',
                            'langVar'   => 'page.category',
                            'nameGetter' => 'getTitle'
                        ));
                        ?>
                    </td>
                    <td>
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                            'item'       => $item,
                            'dateFormat' => (!empty($dateFormat)) ? $dateFormat : 'F j, Y g:i a',
                            'model'      => 'page.category'
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
        "menuLinkId"      => 'mautic_pagecategory_index',
        "baseUrl"         => $view['router']->generate('mautic_pagecategory_index'),
        'sessionVar'      => 'pagecategory'
    )); ?>
    <div class="footer-margin"></div>
</div>